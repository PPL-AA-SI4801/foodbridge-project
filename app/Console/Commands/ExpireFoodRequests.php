<?php

namespace App\Console\Commands;

use App\Models\FoodRequest;
use Illuminate\Console\Command;

class ExpireFoodRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'food-requests:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tandai kebutuhan makanan di Request Board yang melewati batas waktu sebagai kedaluwarsa';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expiredCount = FoodRequest::expireOverdue();

        $this->info("{$expiredCount} kebutuhan makanan ditandai kedaluwarsa.");

        return self::SUCCESS;
    }
}
