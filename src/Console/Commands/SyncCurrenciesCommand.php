<?php

declare(strict_types=1);

namespace Cbu\Currency\Console\Commands;

use Cbu\Currency\Exceptions\CbuApiException;
use Cbu\Currency\Facades\CbuCurrency;
use Illuminate\Console\Command;

class SyncCurrenciesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cbu:sync-currencies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync currencies from CBU API (today\'s data, without rates)';

    /**
     * Execute the console command.
     *
     * @throws CbuApiException
     */
    public function handle(): int
    {
        $this->info('Syncing currencies from CBU API...');

        $result = CbuCurrency::sync()
            ->onlyCurrencies()
            ->save();

        if ($result['success']) {
            $this->newLine();
            $this->info('✓ '.$result['message']);
            $this->line("New currencies added: {$result['currencies_added']}");
            $this->line("Currencies updated: {$result['currencies_updated']}");
            $this->line("Total currencies: {$result['total_currencies']}");
            $this->newLine();

            return self::SUCCESS;
        }

        $this->error('✗ '.$result['message']);

        return self::FAILURE;
    }
}
