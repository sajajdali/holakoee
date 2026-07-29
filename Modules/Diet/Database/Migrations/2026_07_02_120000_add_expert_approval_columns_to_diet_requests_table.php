<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diet_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('expert_approved_by')->nullable()->after('sender_id');
            $table->dateTime('expert_approved_at')->nullable()->after('expert_approved_by');

            $table->index('expert_approved_at', 'diet_requests_expert_approved_at_index');
            $table->index(
                ['expert_approved_by', 'expert_approved_at'],
                'diet_requests_approval_report_index'
            );
        });

        DB::table('diet_requests')
            ->whereNotNull(DB::raw("JSON_EXTRACT(detail, '$.expert_approved.user_id')"))
            ->update([
                'expert_approved_by' => DB::raw(
                    "CAST(JSON_UNQUOTE(JSON_EXTRACT(detail, '$.expert_approved.user_id')) AS UNSIGNED)"
                ),
                'expert_approved_at' => DB::raw(
                    "COALESCE(
                        JSON_UNQUOTE(JSON_EXTRACT(detail, '$.expert_approved.approved_at')),
                        CASE
                            WHEN send_at IS NOT NULL
                                AND DATE(send_at) = JSON_UNQUOTE(JSON_EXTRACT(detail, '$.expert_approved.date'))
                                THEN send_at
                            ELSE CONCAT(
                                JSON_UNQUOTE(JSON_EXTRACT(detail, '$.expert_approved.date')),
                                ' 00:00:00'
                            )
                        END
                    )"
                ),
            ]);
    }

    public function down(): void
    {
        Schema::table('diet_requests', function (Blueprint $table) {
            $table->dropIndex('diet_requests_approval_report_index');
            $table->dropIndex('diet_requests_expert_approved_at_index');
            $table->dropColumn(['expert_approved_by', 'expert_approved_at']);
        });
    }
};
