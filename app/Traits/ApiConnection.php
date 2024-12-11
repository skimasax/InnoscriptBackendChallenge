<?php

namespace App\Traits;
use Error;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

trait ApiConnection
{
    
    public function getNewsFromNewsOrg($baseUrl)
    {
      $response = Http::withHeaders([
      'Content-Type' => 'application/json',
      ])->get("{$baseUrl}");
  
      if ($response->failed()) {
      throw new Error($response->body());
      }
  
      return json_decode($response->body()) ?? [];
    }

    // public function getApi($baseUrl)
    // {
    //   $response = Http::withHeaders([
    //   'Content-Type' => 'application/json',
    //   'Authorization' => "Bearer $token",
    //   ])->get("{$baseUrl}");

    //   dd($response);
  
    //   if ($response->failed()) {
    //   throw new Error($response->body());
    //   }
  
    //   return json_decode($response->body()) ?? [];
    // }

}
