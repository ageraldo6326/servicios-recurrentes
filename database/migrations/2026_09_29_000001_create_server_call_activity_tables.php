<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracted_services', function (Blueprint $table): void {
            $table->string('call_monitoring_platform', 20)->nullable()->after('ip');
            $table->boolean('call_monitoring_enabled')->default(false)->after('call_monitoring_platform');
            $table->unsignedSmallInteger('inactivity_threshold_hours')->default(48)->after('call_monitoring_enabled');
            $table->unsignedSmallInteger('report_delay_threshold_hours')->default(36)->after('inactivity_threshold_hours');
            $table->index(['call_monitoring_enabled', 'status'], 'contracted_services_call_monitoring_index');
            $table->index('ip', 'contracted_services_ip_index');
        });

        Schema::create('server_call_activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contracted_service_id')->unique()->constrained()->restrictOnDelete();
            $table->timestamp('last_outbound_at')->nullable();
            $table->timestamp('last_reported_at')->nullable();
            $table->timestamps();
        });

        Schema::create('server_call_activity_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contracted_service_id')->constrained()->restrictOnDelete();
            $table->timestamp('received_at');
            $table->timestamp('reported_last_outbound_at')->nullable();
            $table->boolean('updated_last_outbound')->default(false);
            $table->string('source_ip', 45)->nullable();
            $table->timestamps();

            $table->index(['contracted_service_id', 'received_at'], 'call_activity_reports_service_received_index');
            $table->index('received_at', 'call_activity_reports_received_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_call_activity_reports');
        Schema::dropIfExists('server_call_activities');

        Schema::table('contracted_services', function (Blueprint $table): void {
            $table->dropIndex('contracted_services_call_monitoring_index');
            $table->dropIndex('contracted_services_ip_index');
            $table->dropColumn([
                'call_monitoring_platform',
                'call_monitoring_enabled',
                'inactivity_threshold_hours',
                'report_delay_threshold_hours',
            ]);
        });
    }
};
