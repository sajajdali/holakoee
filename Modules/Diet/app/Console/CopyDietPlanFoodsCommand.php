<?php

namespace Modules\Diet\app\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Diet\Entities\Condition;
use Modules\Diet\Entities\Food;
use Modules\Diet\Enum\ConditionKeyEnum;

class CopyDietPlanFoodsCommand extends Command
{
    protected $signature = 'diet:copy-pattern-foods
                            {source : کلید یا نام دقیق الگوی مبدأ؛ مثال: 12 یا "فست ۱۴:۱۰"}
                            {target : کلید یا نام دقیق الگوی مقصد؛ مثال: 18 یا "فست 16:8"}
                            {--force : بدون درخواست تأیید اجرا کن}';

    protected $description = 'کپی ارتباط غذاهای یک الگوی رژیم به الگوی دیگر';

    public function handle(): int
    {
        $condition = Condition::where('key', ConditionKeyEnum::DIET_PATTERN->value)->first();

        if (! $condition) {
            $this->error('شرط «الگوی رژیم» پیدا نشد.');

            return self::FAILURE;
        }

        $source = $this->resolvePattern($condition, $this->argument('source'), 'مبدأ');
        $target = $this->resolvePattern($condition, $this->argument('target'), 'مقصد');

        if ($source === null || $target === null) {
            return self::FAILURE;
        }

        if ($source['key'] === $target['key']) {
            $this->error('الگوی مبدأ و مقصد نباید یکسان باشند.');

            return self::FAILURE;
        }

        $recordsToCopy = $this->copyQuery($condition->id, $source['key'], $target['key'])->count();

        $this->table(
            ['الگو', 'کلید', 'نام'],
            [
                ['مبدأ', $source['key'], $source['name']],
                ['مقصد', $target['key'], $target['name']],
            ]
        );
        $this->info("{$recordsToCopy} رکورد جدید برای کپی پیدا شد.");

        if ($recordsToCopy === 0) {
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('غذاهای اختصاصی مقصد به‌روزرسانی شوند؟')) {
            $this->info('عملیات لغو شد.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($condition, $source, $target): void {
            $this->copyQuery($condition->id, $source['key'], $target['key'])
                ->update([
                    'options' => DB::raw("JSON_ARRAY_APPEND(`options`, '$', {$target['key']})"),
                ]);
        });

        $this->info("غذاهای الگوی «{$source['name']}» با موفقیت به «{$target['name']}» کپی شد.");

        return self::SUCCESS;
    }

    private function resolvePattern(Condition $condition, string $identifier, string $label): ?array
    {
        $options = $condition->options['items'] ?? [];
        $keys = $options['keys'] ?? [];
        $names = $options['values'] ?? [];

        $position = ctype_digit($identifier)
            ? array_search((int) $identifier, $keys, true)
            : array_search($identifier, $names, true);

        if ($position === false) {
            $this->error("الگوی {$label} «{$identifier}» پیدا نشد. نام یا کلید را دقیق وارد کنید.");

            return null;
        }

        return ['key' => (int) $keys[$position], 'name' => $names[$position]];
    }

    private function copyQuery(int $conditionId, int $sourceKey, int $targetKey)
    {
        return DB::table('conditions_pivot')
            ->join('foods', 'foods.id', '=', 'conditions_pivot.conditionable_id')
            ->where('condition_id', $conditionId)
            ->where('conditionable_type', Food::class)
            ->whereRaw("JSON_CONTAINS(`options`, ?, '$')", [json_encode($sourceKey)])
            ->whereRaw("NOT JSON_CONTAINS(`options`, ?, '$')", [json_encode($targetKey)]);
    }
}
