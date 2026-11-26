<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'username')) {
                    $table->string('username', 64)->nullable()->unique()->after('id');
                }
                if (! Schema::hasColumn('users', 'full_name')) {
                    $table->string('full_name', 150)->nullable()->after('name');
                }
                if (! Schema::hasColumn('users', 'department')) {
                    $table->string('department', 100)->nullable()->after('role');
                }
                if (! Schema::hasColumn('users', 'status')) {
                    $table->string('status', 20)->default('active')->after('department');
                }
            });
        }

        if (Schema::hasTable('patients')) {
            Schema::table('patients', function (Blueprint $table) {
                if (! Schema::hasColumn('patients', 'account_status')) {
                    $table->string('account_status', 20)->default('active');
                }
                if (! Schema::hasColumn('patients', 'must_change_password')) {
                    $table->boolean('must_change_password')->default(false);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('patients')) {
            Schema::table('patients', function (Blueprint $table) {
                $drop = array_values(array_filter(
                    ['account_status', 'must_change_password'],
                    fn (string $col) => Schema::hasColumn('patients', $col)
                ));
                if ($drop !== []) {
                    $table->dropColumn($drop);
                }
            });
        }
    }
};
