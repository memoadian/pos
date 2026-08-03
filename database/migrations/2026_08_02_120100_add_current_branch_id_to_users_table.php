<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('current_branch_id')->nullable()->after('branch_id')
                ->constrained('branches')->nullOnDelete();
        });

        // Switchers (Admin/Manager) and fixed-branch users alike start on their home branch.
        DB::table('users')->whereNotNull('branch_id')->update([
            'current_branch_id' => DB::raw('branch_id'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_branch_id');
        });
    }
};
