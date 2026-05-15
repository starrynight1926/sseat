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
        Schema::table('floors', function (Blueprint $table) {
            // draft | pending | approved | rejected
            $table->string('status', 20)->default('draft')->after('layout');
            $table->text('rejection_reason')->nullable()->after('status');
            $table->boolean('is_locked')->default(false)->after('rejection_reason');
            $table->timestamp('submitted_at')->nullable()->after('is_locked');
            $table->timestamp('reviewed_at')->nullable()->after('submitted_at');
            $table->foreignId('reviewed_by_id')->nullable()->after('reviewed_at')
                  ->constrained('users')->nullOnDelete();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('floors', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by_id']);
            $table->dropColumn(['status', 'rejection_reason', 'is_locked', 'submitted_at', 'reviewed_at', 'reviewed_by_id']);
        });
    }
};
