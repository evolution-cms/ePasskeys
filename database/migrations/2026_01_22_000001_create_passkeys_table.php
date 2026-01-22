<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passkeys', function (Blueprint $table) {
            $table->id();
            $table->string('context', 8)->default('mgr');
            $table->unsignedBigInteger('authenticatable_id');
            $table->string('name');
            $table->text('credential_id');
            $table->json('data');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['context', 'authenticatable_id']);
            $table->index('credential_id');
            $table->unique(['context', 'credential_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passkeys');
    }
};
