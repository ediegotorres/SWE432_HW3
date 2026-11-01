<?php
declare(strict_types=1);

abstract class AbstractDatasetAnalyzer
{
    protected array $records;

    public function __construct(array $records)
    {
        $this->records = $records;
        $this->validate();
    }

    abstract protected function validate(): void;
    abstract public function analyze(): array;

    protected function average(array $values): float
    {
        $total = 0.0;
        for ($i = 0; $i < count($values); $i++) {
            $total += $values[$i];
        }
        return $total / count($values);
    }

    protected function median(array $values, int $digits = 2): float
    {
        // Sort first, then use one or two middle values.
        sort($values, SORT_NUMERIC);
        $middle = intdiv(count($values), 2);
        $value = count($values) % 2 === 0
            ? ((float) $values[$middle - 1] + (float) $values[$middle]) / 2
            : (float) $values[$middle];

        return round((float) $value, $digits);
    }

    protected function formatNumber(float|int $value, int $digits = 0): string
    {
        return number_format($value, $digits);
    }
}
