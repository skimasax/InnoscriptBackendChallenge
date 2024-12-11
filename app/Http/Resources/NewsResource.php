<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'author' => $this->author,
            'source' => $this->source,
            'title' => $this->title,
            'description' => $this->description,
            'image' => $this->image,
            'category' => $this->category,
            'content' => $this->content
        ];
    }
}
