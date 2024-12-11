<?php

namespace App\Http\Controllers\NewsApi;

use App\Http\Controllers\Controller;
use App\Services\NewsApiService;
use Illuminate\Http\Request;

class NewsApiController extends Controller
{
    //
    protected $newsApiService;
    
    public function __construct(NewsApiService $newsApiService)
    {
        $this->newsApiService = $newsApiService;
    }

    public function index()
    {
        try {
            $data = $this->newsApiService->getNews();
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
}
