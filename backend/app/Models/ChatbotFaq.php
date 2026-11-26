<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotFaq extends Model
{
    protected $table = 'chatbot_faqs';

    protected $fillable = [
        'slug',
        'locale',
        'persona',
        'question',
        'answer_html',
        'keywords_json',
        'follow_ups_json',
        'sort_order',
        'is_active',
        'updated_by',
    ];

    protected $casts = [
        'keywords_json' => 'array',
        'follow_ups_json' => 'array',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];
}
