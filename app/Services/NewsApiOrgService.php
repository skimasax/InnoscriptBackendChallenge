<?php

namespace App\Services;

use App\Models\News;
use Illuminate\Support\Facades\Http;

class NewsApiOrgService
{

    public function getNews($category = null, $author = null, $startDate = null, $endDate = null)
    {

        $newsApiOrgUrl = config('constants.newsapi-org.baseurl');
        $newsApiOrgKey = config('constants.newsapi-org.apiKey');
        $newsApiUrl = config('constants.newsapi.baseurl');
        $newsApiKey = config('constants.newsapi.apiKey');



        // Initialize empty news collection
        $allNews = [];

        // Fetch data from NewsAPI.org
        $newsApiOrgQuery = $newsApiOrgUrl . "?q=" . $category . "&apiKey=" . $newsApiOrgKey;
        $newsApiResponse = Http::get($newsApiOrgQuery);

        if ($newsApiResponse->successful()) {
            $newsFromOrgApi = json_decode($newsApiResponse->body());
            foreach ($newsFromOrgApi->articles as $article) {
                $allNews[] = $this->normalizeArticleData($article, 'newsApiOrg');
            }
        }

        // Fetch data from NewsAPI
        $baseUrl = config('constants.newsapi.baseurl');
        $newsApiQuery = $this->buildNewsApiQuery($category, $startDate, $endDate);
        $newsApiResponse = Http::post($baseUrl, $newsApiQuery);

       if ($newsApiResponse->successful()) {
           $newsFromApi = json_decode($newsApiResponse->body());
           foreach ($newsFromApi->articles->results as $article) {
               $allNews[] = $this->normalizeArticleData($article, 'newsApi');
           }
       }

        // // Fetch data from BBC News
        // $bbcNewsQuery = $bbcNewsUrl . "?q=" . $category;
        // $bbcNewsResponse = Http::get($bbcNewsQuery);

        // if ($bbcNewsResponse->successful()) {
        //     $bbcNews = $bbcNewsResponse->json()['data']['stories'];
        //     foreach ($bbcNews as $article) {
        //         $allNews[] = $this->normalizeArticleData($article, 'bbc');
        //     }
        // }


        // Store the news data in the database
        foreach ($allNews as $article) {
            News::updateOrCreate(
                [
                    'title' => $article['title'],
                    'source' => $article['source'],
                ],
                $article
            );
        }

        return $allNews;
    }

    // Normalize article data to a common structure
    private function normalizeArticleData($article, $source)
    {
        switch ($source) {
            case 'newsApi':
                dd($article);
                return [
                    'author' => $article->author ?? null,
                    'title' => $article->title,
                    'description' => $article->title,
                    'content' => $article->body,
                    'image' => $article->image ?? null,
                    'date' => $article->dateTime,
                    'category' => $article->category ?? null,
                    'source' => $article->source->title ?? 'Unknown Source',
                ];
            case 'newsApiOrg':
                return [
                    'author' => $article->author ?? null,
                    'title' => $article->title,
                    'description' => $article->description,
                    'content' => $article->content,
                    'image' => $article->urlToImage ?? null,
                    'date' => $article->publishedAt,
                    'category' => $article->category ?? null,
                    'source' => $article->source->name ?? 'Unknown Source',
                ];
            // case 'bbc':
            //     return [
            //         'author' => $article['author'] ?? null,
            //         'title' => $article['title'],
            //         'description' => $article['description'],
            //         'content' => $article['content'],
            //         'image' => $article['image'],
            //         'date' => $article['publishedAt'],
            //         'category' => $article['category'] ?? null,
            //         'source' => 'BBC News',
            //     ];
            default:
                return [];
        }
    }

    private function buildNewsApiQuery($category, $startDate, $endDate)
    {
        $apiKey = config('constants.newsapi.apiKey');
    
        // Prepare the query parameters for the request
        $query = [
            'action' => 'getArticles',
            'query' => [
                '$query' => [
                    '$and' => [
                        [
                            'dateStart' => $startDate ?: '2024-01-01', 
                            'dateEnd' => $endDate ?: '2024-12-31',
                            'categoryUri' => 'dmoz/' . $category,
                        ],
                        [
                            '$or' => [
                                [
                                    'conceptUri' => 'http://en.wikipedia.org/wiki/' . $category,
                                ],
                                [
                                    'keyword' => $category,
                                ],
                            ]
                        ],
                    ]
                ]
            ],
            'articlesPage' => 1,
            'articlesCount' => 100,
            'articlesSortBy' => 'socialScore',
            'articlesSortByAsc' => false,
            'articlesArticleBodyLen' => -1,
            'includeArticleSocialScore' => true,
            'resultType' => 'articles',
            'apiKey' => $apiKey,
        ];


        return $query;
    }
}
