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
        Schema::create('products', function (Blueprint $table) {
            $table->id(); // ID (PK)
            $table->string('denumire'); // name (sau denumire)
            $table->string('categorie'); // category
            $table->string('youtube_url')->nullable();
            $table->string('youtube_video_id')->nullable();
            $table->timestamp('youtube_found_at')->nullable();
            $table->boolean('ai_verified')->default(0);
            $table->decimal('ai_accuracy', 5, 2)->nullable(); // scor 0-100
            $table->text('ai_explanation')->nullable(); // de ce a acceptat/respins
            $table->timestamps(); // created_at si updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
