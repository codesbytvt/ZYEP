# Wallet/Credit System + KYC Integration Plan

Scaffold for two related features discussed but not yet built: a credit-based
wallet (to pay for AI poster generation and future paid actions) and Aadhaar
OTP-based KYC for provider (seller) onboarding. This document describes the
schema, the request flow, and the decisions still open before either goes live.

Code scaffolded alongside this doc: migrations for `mwallets`,
`twallet_transactions`, `mcredit_packages`, `tkyc_verifications`, and the
`kyc_status`/`kyc_verified_at` columns on `mproviders`; models for each;
`WalletService`; and a vendor-agnostic `KycService` behind a
`KycProviderInterface` with a `SetuKycProvider` stub. None of this is wired
into routes/controllers yet — see "Not yet built" below.

---

## 1. Wallet / credit system

### Why credits, not a cash wallet

A wallet that holds a redeemable rupee balance is a prepaid payment
instrument (PPI) under RBI rules and requires its own regulatory approval.
A wallet that holds **credits redeemable only for ZYEP's own features**
(poster generation, future paid actions) is not — it's functionally a
gift-card/credit-bundle model. `mwallets.balance_credits` is an integer
credit count for exactly this reason; keep it that way.

### Schema

- **`mwallets`** — one row per user. Cached `balance_credits` plus lifetime
  purchased/spent counters for reporting.
- **`twallet_transactions`** — append-only ledger. Every balance change is a
  row here; `mwallets.balance_credits` is a derived cache, not the source of
  truth. `reference_type`/`reference_id` is a loose (non-FK) pointer to
  whatever feature spent the credits (e.g. `poster_generation`), so this
  ledger doesn't need to change when that feature's schema does.
- **`mcredit_packages`** — purchasable bundles (e.g. "10 credits — ₹199"),
  same shape as the existing `SubscriptionPackage`.

### Flow

```
Provider taps "Buy credits"
  -> picks a CreditPackage
  -> existing Razorpay order-create/verify flow runs (reuse PaymentController's
     pattern), tagged with the package
  -> on verified payment: WalletService::credit($user, $package->credits,
     'topup', paymentId: $payment->id)

Provider spends credits (e.g. generates a poster)
  -> WalletService::debit($user, $cost, 'poster_generation', $jobId)
     throws InsufficientCreditsException if balance too low -> show "buy more
     credits" in the UI, do not attempt the paid action
  -> if the downstream action then fails (AI generation error, moderation
     reject): WalletService::refund($debitTransaction)
```

`WalletService::credit`/`debit`/`refund` all wrap in `DB::transaction()`
with `lockForUpdate()` on the wallet row, so two concurrent requests (e.g. a
webhook retry racing a user's second click) can't both read the same
starting balance.

### Not yet built

- `CreditPackageController` (list packages) and the Razorpay order/verify
  routes for topping up — should closely mirror the existing
  `PaymentController::createOrder`/`verifyPayment`, just crediting a wallet
  instead of activating a `Subscription`.
- Whatever feature actually *spends* credits (poster generation) — the
  ledger's `reference_type`/`reference_id` columns are ready for it, but
  there's no `debit()` call site yet since that feature doesn't exist.
- Admin visibility into the ledger (a Filament resource for
  `twallet_transactions`, useful for support disputes).

---

## 2. Aadhaar OTP KYC for provider onboarding

### Hard constraint: never store a raw Aadhaar number

Storing full Aadhaar numbers is legally restricted (Aadhaar Act 2016 /
UIDAI regulations) to specifically licensed entities. `tkyc_verifications`
only ever holds `masked_aadhaar` (last 4 digits, as vendors return by
default) and `verified_name`. The Aadhaar number itself is passed to the
vendor API in-memory in `KycService::initiate()` and is never written to a
column, log line, or the `raw_response` payload. `raw_response` is stored
with Laravel's `encrypted` cast and hidden from API serialization
(`KycVerification::$hidden`) as a second layer of protection.

### Schema

- **`tkyc_verifications`** — one row per *attempt* (not per provider), so
  retries are auditable. `vendor` records which provider (Setu,
  BharatEVerify, ...) handled a given attempt, so history survives a vendor
  switch.
- **`mproviders.kyc_status`** (`not_started` / `pending` / `verified` /
  `failed`) and **`kyc_verified_at`** — the current-state summary a
  controller checks, so callers don't have to query the ledger table for
  the common case.

### Flow

```
Provider registration screen (or a "Verify your identity" prompt after
registration) collects an Aadhaar number
  -> POST /api/kyc/aadhaar/initiate  { aadhaar_number }
     -> KycService::initiate($provider, $aadhaarNumber)
        -> calls KycProviderInterface::initiate() (vendor sends OTP via UIDAI)
        -> creates a tkyc_verifications row, status=otp_sent
        -> sets mproviders.kyc_status = pending
     <- { verification_id }

Provider enters the OTP they received by SMS
  -> POST /api/kyc/aadhaar/{verification_id}/verify  { otp }
     -> KycService::verify($verification, $otp)
        -> calls KycProviderInterface::verify()
        -> updates the tkyc_verifications row (status, masked_aadhaar,
           verified_name, raw_response, verified_at)
        -> updates mproviders.kyc_status = verified | failed
     <- { verified: true/false }
```

`KycProviderInterface` is resolved via `AppServiceProvider` (currently bound
to `SetuKycProvider`), so switching vendors — or A/B testing Setu against
BharatEVerify — is a one-line binding change, not a rewrite of
`KycService` or its callers.

### Not yet built

- `KycController` + the two routes above.
- **Vendor confirmation**: `SetuKycProvider`'s endpoint paths, auth headers,
  and response shape are placeholders marked with `TODO` — they must be
  checked against Setu's actual API docs (available after signup/sandbox
  access) before this makes a real call. Do not treat the current stub as
  verified.
- **Rate limiting** on both endpoints — Aadhaar OTP initiate/verify are
  exactly the kind of route that needs `throttle` middleware; unprotected,
  it's an OTP brute-force / UIDAI-cost-abuse vector (the same class of gap
  flagged earlier on the phone-OTP auth endpoints).
- **Retry/expiry UX**: `expires_at` is set to a 10-minute placeholder window
  in `KycService::initiate()` — needs to match whatever OTP TTL the chosen
  vendor actually uses, and the frontend needs a "resend OTP" / "verification
  expired" state.
- **Gating decision**: does `kyc_status = verified` block provider approval
  outright, or is it informational (a "Verified ✅" badge, same visual
  language as `is_verified` on provider cards) alongside the existing
  `Setting::get('provider_auto_approval')` policy? Product decision, not
  a technical one — `AdminController::approveProvider` and
  `PaymentController::verifyPayment`'s auto-approval branch would both need
  to know the answer.

---

## Open decisions before either ships

1. Which KYC vendor (Setu vs. BharatEVerify vs. other) — pending the
   quotes requested separately.
2. Credit pricing: cost per poster generation vs. `mcredit_packages` price,
   with enough margin to absorb the AI provider's failed-generation retry
   rate (discussed as ~10–20% earlier).
3. Whether KYC verification is mandatory before a provider can list
   publicly, or optional/trust-badge only.
