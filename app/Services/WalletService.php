<?php

namespace App\Services;

use App\Exceptions\InsufficientCreditsException;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * Every user gets at most one wallet, created lazily on first use.
     */
    public function getOrCreateWallet(User $user): Wallet
    {
        return Wallet::firstOrCreate(['user_id' => $user->id]);
    }

    /**
     * Add credits (a paid top-up, a promotional bonus, or an admin adjustment).
     */
    public function credit(
        User $user,
        int $credits,
        string $type = 'topup',
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $paymentId = null,
        ?string $note = null,
        ?User $createdBy = null,
    ): WalletTransaction {
        if ($credits <= 0) {
            throw new \InvalidArgumentException('Credit amount must be positive.');
        }

        return DB::transaction(function () use ($user, $credits, $type, $referenceType, $referenceId, $paymentId, $note, $createdBy) {
            // Row-lock the wallet for the duration of the transaction so two
            // concurrent requests (e.g. a webhook retry) can't both read the same
            // starting balance and clobber each other's update.
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first()
                ?? Wallet::create(['user_id' => $user->id]);

            $wallet->balance_credits += $credits;
            $wallet->lifetime_purchased_credits += $type === 'topup' ? $credits : 0;
            $wallet->save();

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'type' => $type,
                'credits' => $credits,
                'balance_after' => $wallet->balance_credits,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'payment_id' => $paymentId,
                'note' => $note,
                'created_by' => $createdBy?->id,
            ]);
        });
    }

    /**
     * Spend credits on a feature (e.g. one AI poster generation).
     *
     * @throws InsufficientCreditsException
     */
    public function debit(
        User $user,
        int $credits,
        string $referenceType,
        ?int $referenceId = null,
        ?string $note = null,
    ): WalletTransaction {
        if ($credits <= 0) {
            throw new \InvalidArgumentException('Debit amount must be positive.');
        }

        return DB::transaction(function () use ($user, $credits, $referenceType, $referenceId, $note) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

            if (!$wallet || $wallet->balance_credits < $credits) {
                throw new InsufficientCreditsException($wallet->balance_credits ?? 0, $credits);
            }

            $wallet->balance_credits -= $credits;
            $wallet->lifetime_spent_credits += $credits;
            $wallet->save();

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'type' => 'debit',
                'credits' => -$credits,
                'balance_after' => $wallet->balance_credits,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'note' => $note,
            ]);
        });
    }

    /**
     * Reverse a prior debit (e.g. AI generation failed after credits were spent).
     * Marks the original debit 'reversed' and inserts a new 'refund' row rather
     * than mutating the original — the ledger stays append-only either way.
     */
    public function refund(WalletTransaction $originalDebit, ?string $note = null): WalletTransaction
    {
        if ($originalDebit->type !== 'debit') {
            throw new \InvalidArgumentException('Only a debit transaction can be refunded.');
        }

        if ($originalDebit->status === 'reversed') {
            throw new \RuntimeException('This transaction has already been refunded.');
        }

        return DB::transaction(function () use ($originalDebit, $note) {
            $wallet = Wallet::where('id', $originalDebit->wallet_id)->lockForUpdate()->first();
            $refundedCredits = abs($originalDebit->credits);

            $wallet->balance_credits += $refundedCredits;
            $wallet->lifetime_spent_credits = max(0, $wallet->lifetime_spent_credits - $refundedCredits);
            $wallet->save();

            $originalDebit->update(['status' => 'reversed']);

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $originalDebit->user_id,
                'type' => 'refund',
                'credits' => $refundedCredits,
                'balance_after' => $wallet->balance_credits,
                'reference_type' => $originalDebit->reference_type,
                'reference_id' => $originalDebit->reference_id,
                'note' => $note ?? "Refund of transaction #{$originalDebit->id}",
            ]);
        });
    }
}
