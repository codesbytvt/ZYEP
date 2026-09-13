<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use MysteryInfo\YourBulkSms\YourBulkSmsClient;

class CheckSmsGateway extends Command
{
    protected $signature = 'sms:check';

    protected $description = 'Verify YourBulkSms credentials by checking the account balance';

    public function handle(YourBulkSmsClient $client): int
    {
        $authkey = config('yourbulksms.authkey');

        if (empty($authkey) || $authkey === 'YOUR_AUTH_KEY') {
            $this->warn('No YOURBULKSMS_AUTHKEY configured -- OTPs are being sent via the log-only mock, not a real SMS gateway.');
            return self::FAILURE;
        }

        $this->info('Checking YourBulkSms credentials (authkey: ' . substr($authkey, 0, 4) . '...' . substr($authkey, -4) . ')');

        try {
            $response = $client->getBalance();
        } catch (\Throwable $e) {
            $this->error('Request to YourBulkSms failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        if (!$response->success) {
            $this->error("Gateway did not confirm a valid account -- {$response->message}");
            $this->line('Raw response: ' . trim((string) $response->raw));
            $this->line('Check that YOURBULKSMS_AUTHKEY is your real account key, not the package\'s docs example.');
            return self::FAILURE;
        }

        $this->info("Credentials valid. Account balance: {$response->balance}");
        return self::SUCCESS;
    }
}
