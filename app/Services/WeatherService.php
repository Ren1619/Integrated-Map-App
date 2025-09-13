<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class WeatherService
{
    public function getWeatherData($lat, $lng)
    {
        $cacheKey = "weather_{$lat}_{$lng}";
        
        return Cache::remember($cacheKey, 300, function () use ($lat, $lng) {
            $response = Http::get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $lat,
                'longitude' => $lng,
                'current_weather' => true,
                'hourly' => 'temperature_2m,relative_humidity_2m,wind_speed_10m,wind_direction_10m',
                'timezone' => 'auto'
            ]);

            return $response->successful() ? $response->json() : null;
        });
    }

    public function getNearbyPOIs($lat, $lng, $radius = 1000)
    {
        $cacheKey = "pois_{$lat}_{$lng}_{$radius}";
        
        return Cache::remember($cacheKey, 600, function () use ($lat, $lng, $radius) {
            $overpassQuery = "
                [out:json][timeout:25];
                (
                    node[\"amenity\"~\"^(restaurant|cafe|shop|bank|hospital|pharmacy|school|fuel|hotel|tourism)$\"](around:{$radius},{$lat},{$lng});
                    node[\"shop\"](around:{$radius},{$lat},{$lng});
                    node[\"tourism\"](around:{$radius},{$lat},{$lng});
                );
                out center meta;
            ";

            $response = Http::post('https://overpass-api.de/api/interpreter', $overpassQuery);
            
            return $response->successful() ? $response->json() : null;
        });
    }
}