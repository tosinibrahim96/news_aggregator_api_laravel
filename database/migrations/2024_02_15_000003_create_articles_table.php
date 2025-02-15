<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('external_id')->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->string('author')->nullable();
            $table->string('url');
            $table->string('image_url')->nullable();
            $table->timestamp('published_at');
            $table->timestamps();

            $table->index(['source_id', 'category_id', 'published_at']);
            $table->fullText(['title', 'description']);
            $table->index('external_id');
            $table->index('published_at');
            $table->index('author');
            $table->fullText(['title', 'description', 'content']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
