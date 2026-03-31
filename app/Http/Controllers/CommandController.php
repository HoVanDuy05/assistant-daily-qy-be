<?php

namespace App\Http\Controllers;

use App\Models\Command;
use App\Models\Reminder;
use App\Models\Content;
use App\Models\SocialPost;
use Illuminate\Http\Request;
use Gemini;
use Exception;
use Carbon\Carbon;

class CommandController extends Controller
{
    public function execute(Request $request)
    {
        $request->validate(['input' => 'required|string']);
        $user = $request->user();

        // 1. Create Initial Command record
        $command = Command::create([
            'user_id' => $user->id,
            'raw_input' => $request->input,
            'status' => 'processing',
        ]);

        try {
            // 2. Use AI to parse the command into actions
            $parsedResponse = $this->parseWithAI($request->input);
            $command->update(['parsed_actions' => $parsedResponse]);

            // 3. Execute actions (simplified for now)
            $results = [];
            foreach ($parsedResponse as $action) {
                $results[] = $this->handleAction($user, $command, $action);
            }

            // 4. Finalize
            $command->update([
                'status' => 'completed',
                'results' => $results,
                'executed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $command->load('user'),
            ]);

        } catch (Exception $e) {
            $command->update([
                'status' => 'failed',
                'results' => [['error' => $e->getMessage()]],
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function parseWithAI($input)
    {
        $client = Gemini::client(config('services.gemini.key'));
        
        $prompt = "Bạn là một AI trợ lý thông minh. Phân tích câu lệnh sau của người dùng và chuyển nó thành mảng JSON của các hành động (actions).
        Các loại hành động hỗ trợ: 
        1. create_reminder: params { content, remind_at (ISO datetime), type (push/telegram/email) }
        2. generate_content: params { topic, tone (professional/friendly/casual/persuasive) }
        3. schedule_post: params { platform (facebook/instagram), content, scheduled_at (ISO datetime) }
        
        Nếu câu lệnh không rõ thời gian nhắc nhở, hãy mặc định là 1 tiếng sau từ bây giờ (" . now()->toIso8601String() . ").
        
        Chỉ trả về DUY NHẤT mảng JSON, không giải thích gì thêm.
        Ví dụ: [{\"type\": \"create_reminder\", \"params\": {\"content\": \"Họp team\", \"remind_at\": \"2024-03-31T15:00:00Z\"}}]
        
        Câu lệnh người dùng: \"$input\"";

        $response = $client->geminiPro()->generateContent($prompt);
        $text = $response->text();
        
        // Clean markdown backticks if any
        $cleanJson = preg_replace('/^```json\s+|```$/', '', trim($text));
        
        return json_decode($cleanJson, true) ?: [];
    }

    private function handleAction($user, $command, $action)
    {
        $type = $action['type'] ?? '';
        $params = $action['params'] ?? [];

        switch ($type) {
            case 'create_reminder':
                $reminder = Reminder::create([
                    'user_id' => $user->id,
                    'command_id' => $command->id,
                    'content' => $params['content'] ?? 'Reminder',
                    'remind_at' => Carbon::parse($params['remind_at'] ?? now()),
                    'type' => $params['type'] ?? 'push',
                ]);
                return ['action' => 'create_reminder', 'result' => ['status' => 'success', 'id' => $reminder->id]];

            case 'generate_content':
                $topic = $params['topic'] ?? 'No topic';
                // You could call AI again here to generate full content, but for MVP we'll just draft it
                $content = Content::create([
                    'user_id' => $user->id,
                    'command_id' => $command->id,
                    'topic' => $topic,
                    'generated_content' => "Draft content for: $topic",
                    'tone' => $params['tone'] ?? 'professional',
                ]);
                return ['action' => 'generate_content', 'result' => ['status' => 'success', 'id' => $content->id, 'content' => $content->generated_content]];

            case 'schedule_post':
                $post = SocialPost::create([
                    'user_id' => $user->id,
                    'content' => $params['content'] ?? '',
                    'platform' => $params['platform'] ?? 'facebook',
                    'scheduled_at' => Carbon::parse($params['scheduled_at'] ?? now()),
                ]);
                return ['action' => 'schedule_post', 'result' => ['status' => 'success', 'id' => $post->id]];

            default:
                return ['action' => $type, 'result' => ['status' => 'error', 'message' => 'Unknown action type']];
        }
    }

    public function history(Request $request)
    {
        $commands = Command::where('user_id', $request->user()->id)
            ->orderBy('id', 'desc')
            ->limit(20)
            ->get();
        return response()->json(['success' => true, 'data' => $commands]);
    }

    public function getReminders(Request $request)
    {
        $reminders = Reminder::where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->orderBy('remind_at', 'asc')
            ->get();
        return response()->json(['success' => true, 'data' => $reminders]);
    }

    public function deleteReminder(Request $request, $id)
    {
        $reminder = Reminder::where('user_id', $request->user()->id)->find($id);
        if ($reminder) {
            $reminder->delete();
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 404);
    }
}
