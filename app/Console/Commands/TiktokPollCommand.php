<?php

namespace App\Console\Commands;

use App\Services\OrderSyncService;
use Illuminate\Console\Command;

class TiktokPollCommand extends Command
{
    protected $signature = 'tiktok:poll';

    protected $description = 'Poll TikTok Shop untuk order baru dan masukkan ke DB';

    public function handle(OrderSyncService $sync): int
    {
        $result = $sync->sync();

        $this->info(sprintf(
            'Fetched %d, created %d, updated %d',
            $result['fetched'],
            $result['created'],
            $result['updated']
        ));

        return self::SUCCESS;
    }
}
