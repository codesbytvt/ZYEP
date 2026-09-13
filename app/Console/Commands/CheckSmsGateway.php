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

        $routeType = (int) config('yourbulksms.route', 1);

        $this->info('Checking YourBulkSms credentials (authkey: ' . substr($authkey, 0, 4) . '...' . substr($authkey, -4) . ", route type {$routeType})");

        try {
            $response = $client->getBalance($routeType);
        } catch (\Throwable $e) {
            $this->error('Request to YourBulkSms failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $raw = trim((string) $response->raw);

        // The package's ResponseParser doesn't recognize this gateway's plain-text
        // "Total Balance : N" reply, so it marks $response->success false even for
        // a real balance -- parse the raw text ourselves rather than trust that flag.
        if (preg_match('/Total Balance\s*:\s*(\d+)/i', $raw, $matches)) {
            $balance = (int) $matches[1];

            if ($balance > 0) {
                $this->info("Credentials valid for route {$routeType}. Balance: {$balance}");
                return self::SUCCESS;
            }

            $this->error("Route {$routeType} has a zero balance on this account.");
            $this->line('Either top up that route, or set YOURBULKSMS_ROUTE to a route this account has credits on.');
            return self::FAILURE;
        }

        $this->error("Gateway did not return a recognizable balance -- {$response->message}");
        $this->line('Raw response: ' . $raw);
        return self::FAILURE;
    }
}
