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
        Schema::create('pictures', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('path')->unique();
            $table->string('hash')->unique();
            $table->string('preview')->unique()->nullable();
            $table->dateTime('date');
            $table->string('size');
            $table->string('width');
            $table->string('height');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('album_id')->constrained('albums');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pictures');
    }
};
