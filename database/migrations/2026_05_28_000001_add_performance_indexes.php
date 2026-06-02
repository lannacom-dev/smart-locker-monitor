<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add composite indexes that cover the most common query patterns:
 *
 *  locker_events:
 *    - (company_id, event_type, created_at) — dashboard event-type counts + trend queries
 *    - (locker_id, event_type, created_at)  — top-lockers aggregation
 *
 *  locker_status_logs:
 *    - (locker_id, created_at DESC)         — "latest status per locker" look-up
 *
 *  lockers:
 *    - (company_id, is_active, status)      — filtered locker list
 *    - (company_id, location_id, is_active) — location-scoped queries
 *
 *  locker_boxes:
 *    - (locker_id, is_active, status)       — boxes-per-locker queries
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── locker_events ─────────────────────────────────────────
        Schema::table('locker_events', function (Blueprint $table) {
            // Covers: WHERE company_id = ? AND event_type IN (…) AND created_at BETWEEN … AND …
            $table->index(
                ['company_id', 'event_type', 'created_at'],
                'le_company_type_date'
            );
            // Covers: WHERE locker_id = ? AND event_type IN (…) ORDER BY created_at
            $table->index(
                ['locker_id', 'event_type', 'created_at'],
                'le_locker_type_date'
            );
        });

        // ── locker_status_logs ────────────────────────────────────
        Schema::table('locker_status_logs', function (Blueprint $table) {
            // Covers: WHERE locker_id = ? ORDER BY created_at DESC LIMIT 1
            $table->index(
                ['locker_id', 'created_at'],
                'lsl_locker_date'
            );
        });

        // ── lockers ───────────────────────────────────────────────
        Schema::table('lockers', function (Blueprint $table) {
            // Covers: WHERE company_id = ? AND is_active = 1 AND status = ?
            $table->index(
                ['company_id', 'is_active', 'status'],
                'l_company_active_status'
            );
            // Covers: WHERE company_id = ? AND location_id = ? AND is_active = 1
            $table->index(
                ['company_id', 'location_id', 'is_active'],
                'l_company_location_active'
            );
        });

        // ── locker_boxes ──────────────────────────────────────────
        Schema::table('locker_boxes', function (Blueprint $table) {
            // Covers: WHERE locker_id = ? AND is_active = 1 (box counts per locker)
            $table->index(
                ['locker_id', 'is_active', 'status'],
                'lb_locker_active_status'
            );
        });
    }

    public function down(): void
    {
        Schema::table('locker_events', function (Blueprint $table) {
            $table->dropIndex('le_company_type_date');
            $table->dropIndex('le_locker_type_date');
        });

        Schema::table('locker_status_logs', function (Blueprint $table) {
            $table->dropIndex('lsl_locker_date');
        });

        Schema::table('lockers', function (Blueprint $table) {
            $table->dropIndex('l_company_active_status');
            $table->dropIndex('l_company_location_active');
        });

        Schema::table('locker_boxes', function (Blueprint $table) {
            $table->dropIndex('lb_locker_active_status');
        });
    }
};
