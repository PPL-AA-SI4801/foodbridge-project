<?php

namespace App\Console\Commands;

use App\Enums\FoodPostStatus;
use App\Models\FoodPost;
use Illuminate\Console\Command;

class ExpireFoodPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'food-posts:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tandai postingan makanan yang melewati batas konsumsi sebagai kedaluwarsa';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expiredCount = FoodPost::query()
            ->expired()
            ->update(['status' => FoodPostStatus::Kedaluwarsa->value]);

        $this->info("{$expiredCount} postingan makanan ditandai kedaluwarsa.");

        return self::SUCCESS;
    }
}
