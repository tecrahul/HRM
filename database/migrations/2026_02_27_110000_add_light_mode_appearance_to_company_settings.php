<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('company_settings', 'light_bg_color')) {
                $table->string('light_bg_color', 7)->default('#F5F5F5')->after('brand_secondary_color');
            }
            if (! Schema::hasColumn('company_settings', 'light_sidebar_color')) {
                $table->string('light_sidebar_color', 7)->default('#FFFFFF')->after('light_bg_color');
            }
            if (! Schema::hasColumn('company_settings', 'light_header_color')) {
                $table->string('light_header_color', 7)->default('#FFFFFF')->after('light_sidebar_color');
            }
            if (! Schema::hasColumn('company_settings', 'light_bg_image_path')) {
                $table->string('light_bg_image_path')->nullable()->after('light_header_color');
            }
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('company_settings', 'light_bg_image_path')) {
                $table->dropColumn('light_bg_image_path');
            }
            if (Schema::hasColumn('company_settings', 'light_header_color')) {
                $table->dropColumn('light_header_color');
            }
            if (Schema::hasColumn('company_settings', 'light_sidebar_color')) {
                $table->dropColumn('light_sidebar_color');
            }
            if (Schema::hasColumn('company_settings', 'light_bg_color')) {
                $table->dropColumn('light_bg_color');
            }
        });
    }
};

