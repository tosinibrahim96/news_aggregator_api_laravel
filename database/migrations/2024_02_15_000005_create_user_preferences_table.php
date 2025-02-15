<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('source_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('author_name')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'category_id', 'source_id', 'author_name'], 'user_preferences_unique');
            $table->index(['user_id', 'category_id']);
            $table->index(['user_id', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
