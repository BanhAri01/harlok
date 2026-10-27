<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('slug', 60)->unique();
            $table->string('province', 60);
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->unsignedSmallInteger('sort')->default(0);
        });

        Schema::create('commodities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('slug', 60)->unique();
            $table->json('aliases');
            $table->json('unit_sizes')->nullable();
            $table->unsignedInteger('min_price');
            $table->unsignedInteger('max_price');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('buyer')->after('password')->index();
            $table->string('phone', 20)->nullable()->unique()->after('role');
            $table->string('business_name', 120)->nullable()->after('phone');
            $table->foreignId('region_id')->nullable()->after('business_name')->constrained()->nullOnDelete();
            $table->boolean('notify_wa')->default(false)->after('region_id');
            $table->boolean('is_active')->default(true)->after('notify_wa');
            $table->boolean('is_demo')->default(false)->after('is_active');
        });

        Schema::create('farmers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('phone', 20)->unique();
            $table->foreignId('region_id')->constrained();
            $table->foreignId('commodity_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('channel', 12)->default('wa');
            $table->timestamp('verified_at')->nullable()->index();
            $table->boolean('digest_opt_in')->default(false);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->unique();
            $table->string('state', 30)->default('idle');
            $table->json('data')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('inbound_messages', function (Blueprint $table) {
            $table->id();
            $table->string('external_id', 100)->nullable()->unique();
            $table->string('phone', 20)->index();
            $table->text('body');
            $table->string('channel', 12);
            $table->string('status', 20)->default('received');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('outbound_messages', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20);
            $table->text('body');
            $table->string('channel', 12);
            $table->string('status', 20);
            $table->string('error', 255)->nullable();
            $table->timestamps();
            $table->index(['phone', 'id']);
            $table->index(['channel', 'created_at']);
        });

        Schema::create('stock_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('commodity_id')->constrained();
            $table->foreignId('region_id')->constrained();
            $table->decimal('quantity_kg', 12, 2);
            $table->unsignedInteger('price_per_kg');
            $table->string('status', 20)->index();
            $table->string('flag_reason', 160)->nullable();
            $table->string('parsed_by', 12);
            $table->string('raw_text', 500);
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['commodity_id', 'region_id', 'status', 'confirmed_at']);
            $table->index(['farmer_id', 'status']);
        });

        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('price_per_kg');
            $table->decimal('quantity_kg', 12, 2);
            $table->unsignedInteger('market_avg')->nullable();
            $table->string('status', 20)->index();
            $table->unsignedInteger('counter_price')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->index(['buyer_id', 'status']);
            $table->index(['stock_report_id', 'status']);
        });

        Schema::create('daily_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commodity_id')->constrained();
            $table->foreignId('region_id')->constrained();
            $table->date('date');
            $table->unsignedInteger('avg_price');
            $table->unsignedInteger('min_price');
            $table->unsignedInteger('max_price');
            $table->unsignedInteger('median_price');
            $table->unsignedInteger('reports_count');
            $table->timestamps();
            $table->unique(['commodity_id', 'region_id', 'date']);
            $table->index(['commodity_id', 'date']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('actor', 40);
            $table->string('action', 60)->index();
            $table->nullableMorphs('subject');
            $table->json('meta')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('daily_prices');
        Schema::dropIfExists('offers');
        Schema::dropIfExists('stock_reports');
        Schema::dropIfExists('outbound_messages');
        Schema::dropIfExists('inbound_messages');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('farmers');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('region_id');
            $table->dropColumn(['role', 'phone', 'business_name', 'notify_wa', 'is_active', 'is_demo']);
        });
        Schema::dropIfExists('commodities');
        Schema::dropIfExists('regions');
    }
};
