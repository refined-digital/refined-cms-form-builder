<?php

namespace RefinedDigital\FormBuilder\Commands;

use Illuminate\Console\Command;
use Validator;
use Artisan;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refinedCMS:install-form-builder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Installs the form builder module';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->migrate();
        $this->seed();
        $this->info('Form Builder has been successfully installed');
    }


    protected function migrate()
    {
        $this->output->writeln('<info>Migrating the database</info>');
        Artisan::call('migrate', [
            '--path' => 'vendor/refineddigital/form-builder/src/Database/Migrations',
            '--force' => 1,
        ]);
    }

    protected function seed()
    {
        $this->output->writeln('<info>Seeding the database</info>');
        Artisan::call('db:seed', [
            '--class' => '\\RefinedDigital\\FormBuilder\\Database\\Seeds\\DatabaseSeeder',
            '--force' => 1
        ]);
    }
}
