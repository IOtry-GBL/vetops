<?php

namespace App\Console\Commands;

use App\Services\AdherenceService;
use Illuminate\Console\Command;

class MarkExpiredAdherenceAsMissed extends Command
{
    protected $signature = 'adherence:mark-expired';
    protected $description = 'Mark expired adherence notifications as missed';

    public function handle()
    {
        $count = AdherenceService::markExpiredAsMissed();

        $this->info("Marked {$count} expired adherence doses as missed.");
        return Command::SUCCESS;
    }
}
