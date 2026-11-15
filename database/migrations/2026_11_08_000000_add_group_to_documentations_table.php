<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Folder that groups an endpoint inside its module in the generated docs.
     * Declared per endpoint with the `group:` argument in DocumentationCases.
     */
    public function up(): void
    {
        if (Schema::hasColumn('documentations', 'group')) {
            return;
        }

        Schema::table('documentations', function (Blueprint $table) {
            $table->string('group')->nullable()->after('category');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('documentations', 'group')) {
            return;
        }

        Schema::table('documentations', function (Blueprint $table) {
            $table->dropColumn('group');
        });
    }
};
