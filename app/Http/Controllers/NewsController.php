<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Services\NewsService;
use App\Http\Resources\NewsResource;

class NewsController extends Controller
{
    protected $newsService;
    
    public function __construct(NewsService $newsService)
    {
        $this->newsService = $newsService;
    }

    public function index()
    {
        $category = request('category');
        $author = request('author');
        $source = request('source');
        $startDate = request('startDate');
        $endDate = request('endDate');
        try {
            $response = $this->newsService->getNews($category,$author,$source,$startDate,$endDate);
            $data = NewsResource::collection($response);
            return (new ApiResponse(
                [
                $data,

                ],
                'News Fetched Successfully'
            ))->successResponse();
            //code...
        } catch (\Throwable $th) {
            //throw $th;
            return (new ApiResponse(
                '',
                $th->getMessage()
            ))->errorResponse();
        }
    }

}
