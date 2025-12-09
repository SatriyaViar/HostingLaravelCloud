<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

$apiKey = config('services.gemini.api_key');
$model = config('services.gemini.model', 'gemini-2.0-flash');
$endpoint = config('services.gemini.endpoint') . '/' . $model . ':generateContent?key=' . $apiKey;

echo "Testing Gemini API for Quiz Generation\n";
echo "========================================\n";
echo "Endpoint: $endpoint\n";
echo "Model: $model\n\n";

$prompt = "Generate a quiz with 3 multiple-choice questions about Laravel framework. 

For each question, provide:
- question_text: the question
- question_type: 'multiple_choice'
- points: 10
- explanation: brief explanation of the correct answer
- answers: array of 4 answer options, only 1 is correct

Return ONLY valid JSON in this exact format:
{
  \"questions\": [
    {
      \"question_text\": \"...\",
      \"question_type\": \"multiple_choice\",
      \"points\": 10,
      \"explanation\": \"...\",
      \"answers\": [
        {\"answer_text\": \"...\", \"is_correct\": true},
        {\"answer_text\": \"...\", \"is_correct\": false},
        {\"answer_text\": \"...\", \"is_correct\": false},
        {\"answer_text\": \"...\", \"is_correct\": false}
      ]
    }
  ]
}";

try {
    echo "Sending request to Gemini...\n\n";
    
    $response = Http::timeout(90)->post($endpoint, [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 8192,
            'responseMimeType' => 'application/json',
        ],
    ]);

    echo "Response Status: " . $response->status() . "\n\n";

    if (!$response->successful()) {
        echo "ERROR: API request failed\n";
        echo "Response Body:\n";
        echo $response->body() . "\n";
        exit(1);
    }

    $aiResponse = $response->json();
    
    echo "Raw API Response:\n";
    echo json_encode($aiResponse, JSON_PRETTY_PRINT) . "\n\n";

    $content = $aiResponse['candidates'][0]['content']['parts'][0]['text'] ?? '';
    
    echo "Extracted Content:\n";
    echo $content . "\n\n";

    $parsedContent = json_decode($content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "ERROR: Failed to parse JSON\n";
        echo "JSON Error: " . json_last_error_msg() . "\n";
        exit(1);
    }

    $questionsData = $parsedContent['questions'] ?? [];

    echo "Parsed Questions Count: " . count($questionsData) . "\n\n";

    if (empty($questionsData)) {
        echo "ERROR: No questions generated\n";
        exit(1);
    }

    echo "✅ SUCCESS! Generated questions:\n";
    foreach ($questionsData as $i => $q) {
        echo "\nQuestion " . ($i + 1) . ":\n";
        echo "  Text: " . $q['question_text'] . "\n";
        echo "  Answers: " . count($q['answers']) . "\n";
    }

} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
