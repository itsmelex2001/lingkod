<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpsertChatbotFaqRequest;
use App\Models\ChatbotFaq;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ChatbotFaqController extends Controller
{
    public const PUBLIC_CACHE_KEY = 'lingkod.chatbot.faqs.public';

    public function __construct(private readonly SettingsService $settings) {}

    public function index(Request $request): JsonResponse
    {
        $query = ChatbotFaq::query()->orderBy('locale')->orderBy('sort_order')->orderBy('id');

        if ($locale = $request->query('locale')) {
            $query->where('locale', $locale);
        }
        if ($persona = $request->query('persona')) {
            $query->where('persona', $persona);
        }
        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                  ->orWhere('answer_html', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    public function store(UpsertChatbotFaqRequest $request): JsonResponse
    {
        $data = $request->validated();
        $nextOrder = $data['sort_order'] ?? ((int) ChatbotFaq::where('locale', $data['locale'])->max('sort_order') + 1);

        $faq = ChatbotFaq::create([
            'slug' => $data['slug'],
            'locale' => $data['locale'],
            'persona' => $data['persona'],
            'question' => trim($data['question']),
            'answer_html' => $this->sanitizeAnswer($data['answer_html']),
            'keywords_json' => array_values(array_filter(array_map('trim', $data['keywords'] ?? []))),
            'follow_ups_json' => array_values(array_filter(array_map('trim', $data['follow_ups'] ?? []))),
            'sort_order' => $nextOrder,
            'is_active' => $data['is_active'] ?? true,
            'updated_by' => $request->user()?->id ?? ($_SESSION['user_id'] ?? null),
        ]);

        $this->bustCacheAndAudit($request, 'Chatbot FAQ Created', "Created FAQ '{$faq->slug}' ({$faq->locale}).");

        return response()->json([
            'success' => true,
            'message' => 'FAQ created.',
            'data' => $faq,
        ], 201);
    }

    public function update(UpsertChatbotFaqRequest $request, ChatbotFaq $faq): JsonResponse
    {
        $data = $request->validated();

        $faq->update([
            'slug' => $data['slug'],
            'locale' => $data['locale'],
            'persona' => $data['persona'],
            'question' => trim($data['question']),
            'answer_html' => $this->sanitizeAnswer($data['answer_html']),
            'keywords_json' => array_values(array_filter(array_map('trim', $data['keywords'] ?? []))),
            'follow_ups_json' => array_values(array_filter(array_map('trim', $data['follow_ups'] ?? []))),
            'sort_order' => $data['sort_order'] ?? $faq->sort_order,
            'is_active' => $data['is_active'] ?? $faq->is_active,
            'updated_by' => $request->user()?->id ?? ($_SESSION['user_id'] ?? null),
        ]);

        $this->bustCacheAndAudit($request, 'Chatbot FAQ Updated', "Updated FAQ '{$faq->slug}' ({$faq->locale}).");

        return response()->json([
            'success' => true,
            'message' => 'FAQ updated.',
            'data' => $faq->fresh(),
        ]);
    }

    public function destroy(Request $request, ChatbotFaq $faq): JsonResponse
    {
        $slug = $faq->slug;
        $locale = $faq->locale;
        $faq->delete();

        $this->bustCacheAndAudit($request, 'Chatbot FAQ Deleted', "Deleted FAQ '{$slug}' ({$locale}).");

        return response()->json([
            'success' => true,
            'message' => 'FAQ deleted.',
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:chatbot_faqs,id'],
            'items.*.sort_order' => ['required', 'integer', 'min:0', 'max:999'],
        ]);

        foreach ($validated['items'] as $item) {
            ChatbotFaq::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        Cache::forget(self::PUBLIC_CACHE_KEY);

        return response()->json([
            'success' => true,
            'message' => 'FAQ order updated.',
        ]);
    }

    public function publicList(): JsonResponse
    {
        $payload = Cache::remember(self::PUBLIC_CACHE_KEY, 1800, function () {
            $rows = ChatbotFaq::where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            $grouped = ['EN' => [], 'TL' => []];
            foreach ($rows as $row) {
                $bucket = strtoupper($row->locale) === 'TL' ? 'TL' : 'EN';
                $grouped[$bucket][] = [
                    'id' => $row->slug,
                    'persona' => $row->persona,
                    'question' => $row->question,
                    'keywords' => $row->keywords_json ?? [],
                    'answer' => $row->answer_html,
                    'followUps' => $row->follow_ups_json ?? [],
                ];
            }
            return $grouped;
        });

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }

    private function sanitizeAnswer(string $html): string
    {
        $noScripts = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html) ?? $html;
        if (!class_exists(\DOMDocument::class)) {
            return htmlspecialchars(strip_tags($noScripts), ENT_QUOTES, 'UTF-8');
        }

        $allowed = ['strong', 'b', 'em', 'br'];
        $dom = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?><div>' . $noScripts . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $dom->getElementsByTagName('div')->item(0);
        if (!$root) {
            return htmlspecialchars(strip_tags($noScripts), ENT_QUOTES, 'UTF-8');
        }

        $walk = static function (\DOMNode $node) use (&$walk, $allowed): void {
            for ($i = $node->childNodes->length - 1; $i >= 0; $i--) {
                $child = $node->childNodes->item($i);
                if ($child instanceof \DOMElement) {
                    $tag = strtolower($child->tagName);
                    if (!in_array($tag, $allowed, true)) {
                        $text = $child->ownerDocument->createTextNode($child->textContent);
                        $node->replaceChild($text, $child);
                    } else {
                        while ($child->attributes->length > 0) {
                            $child->removeAttributeNode($child->attributes->item(0));
                        }
                        $walk($child);
                    }
                }
            }
        };
        $walk($root);

        $inner = '';
        foreach ($root->childNodes as $child) {
            $inner .= $dom->saveHTML($child);
        }
        return trim($inner);
    }

    private function bustCacheAndAudit(Request $request, string $action, string $details): void
    {
        Cache::forget(self::PUBLIC_CACHE_KEY);
        $actor = $request->user()?->name ?? $request->user()?->email ?? ($_SESSION['user'] ?? 'admin');
        $this->settings->writeAuditLog('info', $action, $actor, $details);
    }
}
