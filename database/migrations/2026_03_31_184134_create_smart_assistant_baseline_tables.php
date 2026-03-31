<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Commands
        Schema::create('commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('raw_input');
            $table->json('parsed_actions')->nullable(); // Lưu kết quả phân tích JSON từ AI
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->json('results')->nullable(); // Kết quả chi tiết của từng hành động
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
        });

        // 2. Reminders
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('command_id')->nullable()->constrained()->onDelete('set null');
            $table->text('content');
            $table->dateTime('remind_at');
            $table->string('type')->default('push'); // push, telegram, email
            $table->string('status')->default('pending'); // pending, sent, cancelled
            $table->timestamps();
        });

        // 3. AI Generated Contents
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('command_id')->nullable()->constrained()->onDelete('set null');
            $table->string('topic')->nullable();
            $table->text('generated_content');
            $table->string('tone')->default('professional');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // 4. Social Posts (Facebook/Instagram etc)
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('content_id')->nullable()->constrained()->onDelete('set null');
            $table->string('platform'); // facebook, instagram, linkedin
            $table->text('content');
            $table->string('media_url')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('posted_at')->nullable();
            $table->string('status')->default('pending'); // pending, scheduled, posted, failed, cancelled
            $table->string('external_post_id')->nullable(); // ID từ Facebook API
            $table->json('response_data')->nullable(); // Response từ Social API
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_posts');
        Schema::dropIfExists('contents');
        Schema::dropIfExists('reminders');
        Schema::dropIfExists('commands');
    }
};
