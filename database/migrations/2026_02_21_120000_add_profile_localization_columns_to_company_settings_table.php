<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table): void {
            $table->string('legal_entity_name')->nullable()->after('company_name');
            $table->string('legal_entity_type')->nullable()->after('legal_entity_name');
            $table->string('registration_number')->nullable()->after('legal_entity_type');
            $table->string('incorporation_country', 2)->nullable()->after('registration_number');
            $table->string('brand_tagline')->nullable()->after('company_code');
            $table->string('brand_primary_color', 7)->default('#7C3AED')->after('company_logo_path');
            $table->string('brand_secondary_color', 7)->default('#EC4899')->after('brand_primary_color');
            $table->string('brand_font_family', 40)->default('manrope')->after('brand_secondary_color');
            $table->string('locale', 10)->default('en_US')->after('timezone');
            $table->string('default_country', 2)->default('US')->after('locale');
            $table->string('date_format', 20)->default('M j, Y')->after('default_country');
            $table->string('time_format', 20)->default('h:i A')->after('date_format');
            $table->integer('financial_year_start_day')->default(1)->after('financial_year_start_month');
            $table->integer('financial_year_end_month')->nullable()->after('financial_year_start_day');
            $table->integer('financial_year_end_day')->nullable()->after('financial_year_end_month');
            $table->json('branch_directory')->nullable()->after('company_address');
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'legal_entity_name',
                'legal_entity_type',
                'registration_number',
                'incorporation_country',
                'brand_tagline',
                'brand_primary_color',
                'brand_secondary_color',
                'brand_font_family',
                'locale',
                'default_country',
                'date_format',
                'time_format',
                'financial_year_start_day',
                'financial_year_end_month',
                'financial_year_end_day',
                'branch_directory',
            ]);
        });
    }
};
