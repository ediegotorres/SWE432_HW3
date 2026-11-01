<?php
declare(strict_types=1);

require_once __DIR__ . '/AbstractDatasetAnalyzer.php';

final class GapminderAnalyzer extends AbstractDatasetAnalyzer
{
    private const REQUIRED_FIELDS = ['country', 'continent', 'year', 'lifeExp', 'pop', 'gdpPercap'];

    protected function validate(): void
    {
        if (count($this->records) < 1000) {
            throw new InvalidArgumentException('The dataset must contain at least 1,000 records.');
        }

        foreach ($this->records as $index => $record) {
            if (!is_array($record)) {
                throw new InvalidArgumentException("Record " . ($index + 1) . " is not a JSON object.");
            }
            foreach (self::REQUIRED_FIELDS as $field) {
                if (!array_key_exists($field, $record)) {
                    throw new InvalidArgumentException("Record " . ($index + 1) . " is missing required field: {$field}.");
                }
            }
            if (!is_numeric($record['year']) || !is_numeric($record['lifeExp']) ||
                !is_numeric($record['pop']) || !is_numeric($record['gdpPercap'])) {
                throw new InvalidArgumentException("Record " . ($index + 1) . " contains a nonnumeric statistic.");
            }
        }
    }

    public function analyze(): array
    {
        // Map record objects to numeric lists used by the mean and median methods.
        $lifeExpectancies = array_map(fn(array $row): float => (float) $row['lifeExp'], $this->records);
        $gdpValues = array_map(fn(array $row): float => (float) $row['gdpPercap'], $this->records);
        $years = array_map(fn(array $row): int => (int) $row['year'], $this->records);
        $latestYear = max($years);

        $largest = $this->records[0];
        $smallest = $this->records[0];
        $highestGdp = $this->records[0];
        foreach ($this->records as $record) {
            if ((float) $record['pop'] > (float) $largest['pop']) $largest = $record;
            if ((float) $record['pop'] < (float) $smallest['pop']) $smallest = $record;
            if ((float) $record['gdpPercap'] > (float) $highestGdp['gdpPercap']) $highestGdp = $record;
        }

        // Only sum the latest year so countries are not counted once per historical year.
        $latestRecords = array_filter(
            $this->records,
            fn(array $row): bool => (int) $row['year'] === $latestYear
        );
        $continentTotals = [];
        foreach ($latestRecords as $record) {
            $continent = (string) $record['continent'];
            $continentTotals[$continent] = ($continentTotals[$continent] ?? 0) + (float) $record['pop'];
        }
        arsort($continentTotals, SORT_NUMERIC);
        $largestContinent = (string) array_key_first($continentTotals);

        // Group observations so each country's first and last populations can be compared.
        $byCountry = [];
        foreach ($this->records as $record) {
            $byCountry[(string) $record['country']][] = $record;
        }
        $growthCountry = '';
        $growthPercent = -INF;
        foreach ($byCountry as $country => $countryRecords) {
            usort($countryRecords, fn(array $a, array $b): int => (int) $a['year'] <=> (int) $b['year']);
            $first = $countryRecords[0];
            $last = $countryRecords[count($countryRecords) - 1];
            $growth = (((float) $last['pop'] - (float) $first['pop']) / (float) $first['pop']) * 100;
            if ($growth > $growthPercent) {
                $growthPercent = $growth;
                $growthCountry = (string) $country;
            }
        }

        $seventyOrMore = array_filter(
            $this->records,
            fn(array $row): bool => (float) $row['lifeExp'] >= 70
        );
        // array_push is intentionally used to build the ten question/answer results.
        $results = [];
        array_push($results, ['question' => '1. How many records are in the dataset?', 'answer' => $this->formatNumber(count($this->records)) . ' records']);
        array_push($results, ['question' => '2. What is the average life expectancy across all records?', 'answer' => $this->formatNumber($this->average($lifeExpectancies), 2) . ' years']);
        array_push($results, ['question' => '3. What is the median life expectancy across all records?', 'answer' => $this->formatNumber($this->median($lifeExpectancies), 2) . ' years']);
        array_push($results, ['question' => '4. Which country-year record has the largest population?', 'answer' => "{$largest['country']} ({$largest['year']}) with " . $this->formatNumber((float) $largest['pop']) . ' people']);
        array_push($results, ['question' => '5. Which country-year record has the smallest population?', 'answer' => "{$smallest['country']} ({$smallest['year']}) with " . $this->formatNumber((float) $smallest['pop']) . ' people']);
        array_push($results, ['question' => '6. Which country-year record has the highest GDP per capita?', 'answer' => "{$highestGdp['country']} ({$highestGdp['year']}) at $" . $this->formatNumber((float) $highestGdp['gdpPercap'], 2)]);
        array_push($results, ['question' => '7. What is the average GDP per capita across all records?', 'answer' => '$' . $this->formatNumber($this->average($gdpValues), 2)]);
        array_push($results, ['question' => "8. Which continent had the largest total population in the latest year ({$latestYear})?", 'answer' => $largestContinent . ' with ' . $this->formatNumber($continentTotals[$largestContinent]) . ' people']);
        array_push($results, ['question' => '9. Which country had the greatest population growth from its first year to its last year in the dataset?', 'answer' => $growthCountry . ' with ' . $this->formatNumber($growthPercent, 2) . '% growth']);
        array_push($results, ['question' => '10. How many records have a life expectancy of at least 70 years?', 'answer' => $this->formatNumber(count($seventyOrMore)) . ' records']);
        return $results;
    }
}
