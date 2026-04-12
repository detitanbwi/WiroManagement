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
        Schema::table('quotations', function (Blueprint $table) {
            $table->string('attachment_pdf')->nullable()->after('status');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('attachment_pdf')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn('attachment_pdf');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('attachment_pdf');
        });
    }
};
