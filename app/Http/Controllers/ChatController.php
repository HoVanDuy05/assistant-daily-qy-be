<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Gemini;
use Exception;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    /**
     * Handle a standard chat request (returns full JSON).
     */
    public function __invoke(Request $request)
    {
        $request->validate(['message' => 'required|string']);

        try {
            $client = Gemini::client(config('services.gemini.key'));
            $chat = $client->geminiPro()->startChat([]);
            $response = $chat->sendMessage($request->message);

            return response()->json(['response' => $response->text()]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle a streaming chat request (SSE).
     */
    public function stream(Request $request)
    {
        $request->validate(['message' => 'required|string']);

        return new StreamedResponse(function () use ($request) {
            $client = Gemini::client(config('services.gemini.key'));
            
            try {
                $stream = $client->geminiPro()->streamGenerateContent($request->message);

                foreach ($stream as $response) {
                    $text = $response->text();
                    echo "data: " . json_encode(['text' => $text]) . "\n\n";
                    
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }
            } catch (Exception $e) {
                echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
            }
            
            echo "data: [DONE]\n\n";
            flush();
        }, 200, [
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'text/event-stream',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
