<?php

declare(strict_types=1);

namespace Cbu\Currency\Console\Commands;

use Cbu\Currency\Exceptions\CbuApiException;
use Cbu\Currency\Facades\CbuCurrency;
use Cbu\Currency\Helpers\CurrencyHelper;
use Illuminate\Console\Command;

/**
 * Sync Currency Rates Command
 *
 * Syncs currency rates for the last N days from CBU API to database.
 */
class SyncRatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cbu:sync-rates
                            {days=7 : Number of days to sync (default: 7)}
                            {--date= : Sync specific date (Y-m-d format)}
                            {--from= : Start date (Y-m-d format)}
                            {--to= : End date (Y-m-d format)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync currency rates for a specific date, last N days, or between two dates from CBU API';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $date = $this->option('date');
        $fromDate = $this->option('from');
        $toDate = $this->option('to');

        // If specific date is provided
        if ($date) {
            // Validate date
            try {
                CurrencyHelper::isValidDate($date);
            } catch (CbuApiException $e) {
                $this->error($e->getMessage());
                return self::FAILURE;
            }

            return $this->syncSpecificDate($date);
        }

        // If date range is provided, use it
        if ($fromDate && $toDate) {
            return $this->syncDateRange($fromDate, $toDate);
        }

        // If only one date is provided, show error
        if ($fromDate || $toDate) {
            $this->error('Both --from and --to options must be provided when using date range');
            return self::FAILURE;
        }

        // Otherwise, use days argument
        $days = (int) $this->argument('days');

        if ($days < 1 || $days > 365) {
            $this->error('Number of days must be between 1 and 365');
            return self::FAILURE;
        }

        return $this->syncLastDays($days);
    }

    /**
     * Sync rates for a specific date
     */
    protected function syncSpecificDate(string $date): int
    {
        $this->info("Syncing currency rates for date: {$date}");
        $this->newLine();

        try {
            $result = CbuCurrency::sync()
                ->date($date)
                ->save();

            $this->info('✓ ' . $result['message']);
            $this->newLine();
            $this->line("Rates saved: {$result['rates_saved']}");
            $this->line("Rates updated: {$result['rates_updated']}");
            $this->line("Total rates: {$result['total_rates']}");
            $this->newLine();

            return self::SUCCESS;
        } catch (CbuApiException $e) {
            $this->error('✗ ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Sync rates for the last N days
     */
    protected function syncLastDays(int $days): int
    {
        $this->info("Syncing currency rates for the last {$days} days...");
        $this->newLine();

        $dates = [];
        for ($i = 0; $i < $days; $i++) {
            $dates[] = now()->subDays($i)->format('Y-m-d');
        }

        return $this->processDates($dates);
    }

    /**
     * Sync rates between two dates
     */
    protected function syncDateRange(string $fromDate, string $toDate): int
    {
        // Validate dates
        try {
            CurrencyHelper::isValidDate($fromDate);
            CurrencyHelper::isValidDate($toDate);
        } catch (CbuApiException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $from = \Carbon\Carbon::parse($fromDate);
        $to = \Carbon\Carbon::parse($toDate);

        // Ensure from date is before or equal to date
        if ($from->greaterThan($to)) {
            $this->error('Start date must be before or equal to end date');
            return self::FAILURE;
        }

        // Check if range is not too large (max 365 days)
        $daysDifference = $from->diffInDays($to) + 1;
        if ($daysDifference > 365) {
            $this->error('Date range cannot exceed 365 days');
            return self::FAILURE;
        }

        $this->info("Syncing currency rates from {$fromDate} to {$toDate} ({$daysDifference} days)...");
        $this->newLine();

        // Generate all dates in the range
        $dates = [];
        $current = $from->copy();
        while ($current->lessThanOrEqualTo($to)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        return $this->processDates($dates);
    }

    /**
     * Process sync for given dates
     *
     * @param array<string> $dates Array of dates in Y-m-d format
     */
    protected function processDates(array $dates): int
    {
        $totalRatesSaved = 0;
        $totalRatesUpdated = 0;
        $totalRates = 0;
        $successfulDays = 0;
        $failedDays = 0;

        $progressBar = $this->output->createProgressBar(count($dates));
        $progressBar->start();

        foreach ($dates as $date) {
            try {
                $result = CbuCurrency::sync()
                    ->date($date)
                    ->save();

                $totalRatesSaved += $result['rates_saved'];
                $totalRatesUpdated += $result['rates_updated'];
                $totalRates += $result['total_rates'];
                $successfulDays++;

                CurrencyHelper::logInfo("Rates synced for date: {$date}", [
                    'rates_saved' => $result['rates_saved'],
                    'rates_updated' => $result['rates_updated'],
                ]);
            } catch (CbuApiException $e) {
                $failedDays++;
                CurrencyHelper::logError("Failed to sync rates for date: {$date}", [
                    'error' => $e->getMessage(),
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display summary
        $this->info('✓ Sync completed');
        $this->newLine();
        $this->line("Days processed: " . count($dates));
        $this->line("Successful: {$successfulDays}");

        if ($failedDays > 0) {
            $this->warn("Failed: {$failedDays}");
        }

        $this->newLine();
        $this->line("New rates saved: {$totalRatesSaved}");
        $this->line("Rates updated: {$totalRatesUpdated}");
        $this->line("Total rates processed: {$totalRates}");
        $this->newLine();

        return $failedDays > 0 ? self::FAILURE : self::SUCCESS;
    }
}
