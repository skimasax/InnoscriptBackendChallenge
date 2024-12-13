<?php

namespace App\Services;

use App\Models\News;
use Illuminate\Support\Facades\Http;

class NewsService
{

    public function getNews($category = null, $author = null, $source = null, $startDate = null, $endDate = null)
    {
        $storedNews = $this->queryDbForNews($category, $author, $source, $startDate, $endDate);
        if ($storedNews->isNotEmpty()) {
            return $storedNews;
        }

        // Fetch fresh news data from third-party sources if not found in DB
        $this->fetchNewsFromThirdParty($category, $startDate, $endDate);
        return News::all();
    }

    public function fetchNewsFromThirdParty($category = null, $startDate = null, $endDate = null)
    {
        $newsApiOrgUrl = config('constants.newsapi-org.baseurl');
        $newsApiOrgKey = config('constants.newsapi-org.apiKey');
        $newsApiUrl = config('constants.newsapi.baseurl');
        $newYorkNewsApiUrl = config('constants.newyork.baseurl');
        $newYorkNewsApiKey = config('constants.newyork.apiKey');

        $allNews = [];

        // Fetch data from NewsAPI.org
        $newsApiOrgQuery = $newsApiOrgUrl . "?q=" . $category . "&apiKey=" . $newsApiOrgKey;
        $newsApiResponse = Http::get($newsApiOrgQuery);

        if ($newsApiResponse->successful()) {
            $newsFromOrgApi = json_decode($newsApiResponse->body());
            foreach ($newsFromOrgApi->articles as $article) {
                $allNews[] = $this->arrangeNewsStructure($article, $category, 'newsApiOrg');
            }
        }

        // Fetch data from NewsAPI
        $payload = $this->ApiNewsPayload($category, $startDate, $endDate);
        $newsApiResponse = Http::post($newsApiUrl, $payload);

        if ($newsApiResponse->successful()) {
            $newsFromApi = json_decode($newsApiResponse->body());
            foreach ($newsFromApi->articles->results as $article) {
                $allNews[] = $this->arrangeNewsStructure($article, $category, 'newsApi');
            }
        }

        // Fetch data from New York Times API
        $newyorkNewsQuery = $newYorkNewsApiUrl . $category . ".json?api-key=" . $newYorkNewsApiKey;
        $newYorkNewsResponse = Http::get($newyorkNewsQuery);

        if ($newYorkNewsResponse->successful()) {
            $newsFromApi = json_decode($newYorkNewsResponse->body());
            foreach ($newsFromApi->results as $article) {
                $allNews[] = $this->arrangeNewsStructure($article, $category, 'newyork-news');
            }
        }

        foreach ($allNews as $article) {
            News::updateOrCreate(
                [
                    'title' => $article['title'],
                    'source' => $article['source'],
                ],
                $article
            );
        }
    }

    private function arrangeNewsStructure($article, $category, $source)
    {
        switch ($source) {
            case 'newsApi':
                return [
                    'author' => $article->author ?? null,
                    'title' => $article->title,
                    'description' => $article->title ?? null,
                    'content' => $article->body,
                    'image' => $article->image ?? null,
                    'date' => $article->dateTime,
                    'category' => $category,
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
                    'category' => $category,
                    'source' => $article->source->name ?? 'Unknown Source',
                ];
            case 'newyork-news':
                return [
                    'author' => $article->author ?? null,
                    'title' => $article->title,
                    'description' => $article->abstract,
                    'content' => $article->abstract,
                    'image' => $article->multimedia['0']->url ?? null,
                    'date' => $article->updated_date,
                    'category' => $category,
                    'source' => $article->source->name ?? 'Newyork Times',
                ];
            default:
                return [];
        }
    }

    private function ApiNewsPayload($category, $startDate, $endDate)
    {
        $apiKey = config('constants.newsapi.apiKey');
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

    private function queryDbForNews($category, $author, $source, $startDate, $endDate)
    {
        $query = News::query();

        if ($category) {
            $query->where('category', 'like', '%' . $category . '%');
        }
        if ($author) {
            $query->where('author', 'like', '%' . $author . '%');
        }
        if ($source) {
            $query->where('source',  'like', '%' . $source . '%');
        }
        if ($startDate) {
            $query->where('published_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('published_at', '<=', $endDate);
        }
        $storedNews = $query->get();
        return $storedNews;
    }
}
