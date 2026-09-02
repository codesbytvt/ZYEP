<?php

namespace App\Providers;

use App\Services\Kyc\KycProviderInterface;
use App\Services\Kyc\SetuKycProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Swap this binding to switch KYC vendors (e.g. BharatEVerifyKycProvider)
        // without touching KycService or any controller that depends on it.
        $this->app->bind(KycProviderInterface::class, SetuKycProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
