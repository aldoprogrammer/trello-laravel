<?php

namespace App\Services;

use App\Contracts\AIServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService implements AIServiceInterface
{
    public function summarize(string $text): string
    {
        $apiKey = trim((string) config('services.gemini.api_key', ''));
        if ($apiKey === '') throw new \RuntimeException('GEMINI_API_KEY is missing.');

        $models = ['gemini-1.5-flash', 'gemini-1.5-flash-latest'];

        foreach ($models as $model) {
            $response = Http::withoutVerifying()
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                    'contents' => [['parts' => [['text' => $text]]]]
                ]);

            if ($response->successful()) {
                return trim((string) $response->json('candidates.0.content.parts.0.text', ''));
            }
        }

        return '';
    }
}
