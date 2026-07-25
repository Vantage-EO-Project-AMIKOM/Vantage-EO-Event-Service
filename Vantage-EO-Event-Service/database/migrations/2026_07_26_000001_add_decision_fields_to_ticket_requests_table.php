<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('decided_by')->nullable()->index()->after('status');
            $table->timestamp('decided_at')->nullable()->after('decided_by');
            $table->string('rejection_reason')->nullable()->after('decided_at');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_requests', function (Blueprint $table) {
            $table->dropColumn(['decided_by', 'decided_at', 'rejection_reason']);
        });
    }
};
