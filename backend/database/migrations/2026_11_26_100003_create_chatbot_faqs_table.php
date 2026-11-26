<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('chatbot_faqs')) {
            Schema::create('chatbot_faqs', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 80);
                $table->string('locale', 8)->default('en');
                $table->string('persona', 20)->default('both');
                $table->string('question', 255);
                $table->text('answer_html');
                $table->json('keywords_json')->nullable();
                $table->json('follow_ups_json')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('updated_by')->nullable();
                $table->timestamps();

                $table->unique(['slug', 'locale'], 'uq_faq_slug_locale');
                $table->index(['locale', 'is_active', 'sort_order'], 'idx_faq_lookup');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_faqs');
    }
};
