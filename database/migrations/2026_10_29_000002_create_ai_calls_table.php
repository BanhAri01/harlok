<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_calls', function (Blueprint $table) {
            $table->id();
            $table->string('purpose', 30);
            $table->string('driver', 20);
            $table->string('model', 60);
            $table->string('status', 12);
            $table->unsignedInteger('latency_ms')->default(0);
            $table->string('error', 255)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['purpose', 'status']);
        });

        Schema::table('stock_reports', function (Blueprint $table) {
            $table->string('ai_verdict', 20)->nullable()->after('flag_reason');
            $table->string('ai_note', 255)->nullable()->after('ai_verdict');
        });
    }

    public function down(): void
    {
        Schema::table('stock_reports', function (Blueprint $table) {
            $table->dropColumn(['ai_verdict', 'ai_note']);
        });
        Schema::dropIfExists('ai_calls');
    }
};
