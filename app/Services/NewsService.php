<?php

namespace App\Services;

use App\Models\News;
use Illuminate\Support\Facades\Http;

class NewsService
{

    public function getNews($category = null, $author = null, $source = null, $startDate = null, $endDate = null)
    {

        $newsApiOrgUrl = config('constants.newsapi-org.baseurl');
        $newsApiOrgKey = config('constants.newsapi-org.apiKey');
        $newsApiUrl = config('constants.newsapi.baseurl');



        // Initialize empty news collection
        $allNews = [];

        //get the News from the DB with the search filters'
        $storedNews = $this->queryDbForNews($category, $author, $source, $startDate, $endDate);

        // If news is found in the database, return it
        if ($storedNews->isNotEmpty()) {
            return $storedNews;
        }

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

        // Retrieve all the stored news from the database
        $storedNews = News::all();

        // Return the stored news
        return $storedNews;
    }


    // Normalize article data to a common structure
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
