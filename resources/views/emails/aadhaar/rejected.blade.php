<x-mail::message>
# Aadhaar Verification Rejected

Dear {{ $provider->user->name }},

We regret to inform you that your Aadhaar verification for **{{ $provider->business_name }}** has been rejected by our administration team.

@if($provider->aadhaar_rejection_reason)
**Reason for Rejection:**
> {{ $provider->aadhaar_rejection_reason }}
@endif

Please log in to your account, update your business details, and resubmit your Aadhaar verification document or verify instantly using OTP.

<x-mail::button :url="config('app.url') . '/profile'">
Go to Profile
</x-mail::button>

Thanks,<br>
{{ config('app.name') }} Team
</x-mail::message>
