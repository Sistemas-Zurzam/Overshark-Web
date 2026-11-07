<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('combos', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('brand')->constrained('brands')->nullOnDelete();
            $table->boolean('is_draft')->default(false)->after('status');
            $table->decimal('compare_at_price', 12, 2)->nullable()->after('price');
        });

        // Backfill brand_id for existing rows matching the free-text brand column.
        DB::table('combos')->whereNotNull('brand')->get(['id', 'brand'])->each(function ($combo) {
            $brand = DB::table('brands')->whereRaw('UPPER(name) = ?', [mb_strtoupper(trim($combo->brand))])->first();
            if ($brand) {
                DB::table('combos')->where('id', $combo->id)->update(['brand_id' => $brand->id]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('combos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_id');
            $table->dropColumn(['is_draft', 'compare_at_price']);
        });
    }
};
