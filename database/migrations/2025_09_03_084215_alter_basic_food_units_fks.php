<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $handleMigration = false;
        if (!$handleMigration) {
            return; // هیچ تغییری اعمال نشه
        }
        $table      = 'basic_food_units'; // اگر پیشوند داری همان نام بدون prefix را بگذار؛ لاراول خودش prefix را اعمال می‌کند
        $basicFoods = 'basic_foods';
        $foodUnits  = 'food_units';

        // 1) ستون‌ها nullable (برای SET NULL لازم است)
        Schema::table($table, function (Blueprint $t) {
            if (Schema::hasColumn($t->getTable(), 'basic_food_id')) {
                $t->unsignedBigInteger('basic_food_id')->nullable()->change();
            }
            if (Schema::hasColumn($t->getTable(), 'food_unit_id')) {
                $t->unsignedBigInteger('food_unit_id')->nullable()->change();
            }
        });

        // 2) پاک‌سازی داده‌های یتیم (ارقام نامعتبر → NULL)
        DB::table($table)->where('basic_food_id', 0)->update(['basic_food_id' => null]);
        DB::table($table)->where('food_unit_id', 0)->update(['food_unit_id' => null]);

        DB::table($table)
            ->whereNotNull('basic_food_id')
            ->whereNotIn('basic_food_id', function ($q) use ($basicFoods) {
                $q->select('id')->from($basicFoods);
            })
            ->update(['basic_food_id' => null]);

        DB::table($table)
            ->whereNotNull('food_unit_id')
            ->whereNotIn('food_unit_id', function ($q) use ($foodUnits) {
                $q->select('id')->from($foodUnits);
            })
            ->update(['food_unit_id' => null]);

        // 3) ساخت FKهای جدید با رفتار دلخواه (یکی را انتخاب کن: nullOnDelete یا cascadeOnDelete)
        Schema::table($table, function (Blueprint $t) use ($basicFoods, $foodUnits) {
            $t->foreign('basic_food_id')
                ->references('id')->on($basicFoods)
                ->cascadeOnUpdate()
                ->nullOnDelete();    // اگر حذف آبشاری می‌خواهی: به ->cascadeOnDelete() تغییر بده

            $t->foreign('food_unit_id')
                ->references('id')->on($foodUnits)
                ->cascadeOnUpdate()
                ->nullOnDelete();    // یا ->cascadeOnDelete()
        });
    }

    public function down(): void
    {
        $table = 'basic_food_units';

        Schema::table($table, function (Blueprint $t) {
            // حذف FKها بدون نیاز به دانستن نام (اگر نسخه لاراول اجازه نده، می‌شود با info_schema نام را گرفت)
            if (Schema::hasColumn($t->getTable(), 'basic_food_id')) {
                $t->dropConstrainedForeignId('basic_food_id');
            }
            if (Schema::hasColumn($t->getTable(), 'food_unit_id')) {
                $t->dropConstrainedForeignId('food_unit_id');
            }
        });
    }
};
