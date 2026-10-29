<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateMerchant extends Command
{
    protected $signature = 'billing:merchant-create {name : Merchant display name}';

    protected $description = 'Create a merchant workspace and generate its API key';

    public function handle(): int
    {
        $apiKey = Str::random(64);
        $now = now();

        $merchantId = DB::table('merchants')->insertGetId([
            'name' => $this->argument('name'),
            'api_key_hash' => Hash::make($apiKey),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->info('Merchant created successfully.');
        $this->line('Merchant ID: '.$merchantId);
        $this->line('API key (shown once): '.$apiKey);
        $this->comment('Store this key securely. It cannot be recovered from the database.');

        return self::SUCCESS;
    }
}
