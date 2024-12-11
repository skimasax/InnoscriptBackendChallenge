<?php

namespace App\Services;

use App\Traits\ApiConnection;

class NewsApiService
{
    use ApiConnection;

    public function getNews()
    {
        $baseUrl = config("constants.newsapi.baseurl");
        $token = config("constants.newsapi.apiKey");

        $data = $this->getApi($baseUrl,$token);

        dd($data);
    }

}

















?>