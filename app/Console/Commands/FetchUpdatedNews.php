<?php

namespace App\Console\Commands;

use App\Services\NewsService;
use Illuminate\Console\Command;

class FetchUpdatedNews extends Command
{
    // The name and signature of the console command.
    protected $signature = 'news:fetch';

    // The console command description.
    protected $description = 'Fetch news from third-party APIs and update the database';

    // The NewsService instance
    protected $newsService;

    // Constructor to inject the NewsService
    public function __construct(NewsService $newsService)
    {
        parent::__construct();

        $this->newsService = $newsService;
    }

    // The logic to handle the command
    public function handle()
    {
        $categories = ['technology', 'business', 'sports', 'health', 'politics', 'entertainment', 'finance']; 

        foreach ($categories as $category) {
            $this->newsService->fetchNewsFromThirdParty($category);
        }

        $this->info('News fetched and updated successfully.');
    }
}


?>