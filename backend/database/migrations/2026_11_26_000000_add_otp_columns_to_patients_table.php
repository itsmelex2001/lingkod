<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('patients')) {
            return;
        }

        Schema::table('patients', function (Blueprint $table) {
            if (! Schema::hasColumn('patients', 'otp_code')) {
                $table->string('otp_code')->nullable();
            }
            if (! Schema::hasColumn('patients', 'otp_expires_at')) {
                $table->timestamp('otp_expires_at')->nullable();
            }
            if (! Schema::hasColumn('patients', 'is_otp_enabled')) {
                $table->boolean('is_otp_enabled')->default(false);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('patients')) {
            return;
        }

        Schema::table('patients', function (Blueprint $table) {
            $drop = array_values(array_filter(
                ['otp_code', 'otp_expires_at', 'is_otp_enabled'],
                fn (string $col) => Schema::hasColumn('patients', $col)
            ));
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
