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
        Schema::create('video_candidates', function (Blueprint $table) {
            $table->id();
            // Legatura cu tabelul products
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            $table->string('video_id');
            $table->string('title');
            $table->string('channel');
            $table->dateTime('published_at')->nullable();
            $table->text('description_snippet')->nullable();
            $table->json('raw_payload')->nullable(); // opțional, pentru datele brute

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_candidates');
    }
};
