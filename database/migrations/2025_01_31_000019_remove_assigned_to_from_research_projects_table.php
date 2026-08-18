<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('research_projects', 'assigned_to')) {
            return;
        }

        // Each drop is its own statement: SQLite rebuilds the table per call, and
        // batching them leaves the foreign key pointing at an already-gone column.
        // MySQL refuses to drop the index while the foreign key still needs it, so
        // the key has to go first; SQLite tolerates the same order.
        $this->attempt(fn (Blueprint $table) => $table->dropForeign(['assigned_to']));
        $this->attempt(fn (Blueprint $table) => $table->dropIndex(['assigned_to']));

        Schema::table('research_projects', function (Blueprint $table) {
            $table->dropColumn('assigned_to');
        });
    }

    /**
     * Run a schema change, ignoring the case where the constraint or index was
     * never created on this connection.
     */
    private function attempt(callable $change): void
    {
        try {
            Schema::table('research_projects', fn (Blueprint $table) => $change($table));
        } catch (\Throwable $e) {
            // Nothing to drop on this driver — the column drop below still applies.
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('research_projects', 'assigned_to')) {
            Schema::table('research_projects', function (Blueprint $table) {
                $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            });
        }
    }
};