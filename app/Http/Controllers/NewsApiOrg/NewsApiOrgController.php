<?php

namespace App\Http\Controllers\NewsApiOrg;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Services\NewsApiOrgService;
use App\Http\Controllers\Controller;
use App\Http\Resources\NewsResource;

class NewsApiOrgController extends Controller
{
    protected $newsApiOrgService;
    
    public function __construct(NewsApiOrgService $newsApiOrgService)
    {
        $this->newsApiOrgService = $newsApiOrgService;
    }

    public function index()
    {
        $category = request('category');
        try {
            $response = $this->newsApiOrgService->getNews($category);
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
