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
        Schema::create('albums', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('path');
            $table->unique(['user_id','name']);
            $table->unique(['user_id', 'path']);
            $table->unique(['user_id', 'id']);
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
        });
        Schema::create('album_accesses', function (Blueprint $table) {
           $table->primary(['album_id','user_id']);
           $table->foreignId('album_id')->constrained('albums');
           $table->foreignId('user_id')->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('albums');
    }
};
