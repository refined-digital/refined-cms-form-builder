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
        $this->createSymLink();
        $this->info('Form Builder has been successfully installed');
    }


    protected function migrate()
    {
        $this->output->writeln('<info>Migrating the database</info>');
        Artisan::call('migrate', [
            '--path' => 'vendor/refineddigital/cms-form-builder/src/Database/Migrations',
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

    protected function createSymLink()
    {
        $this->output->writeln('<info>Creating Symlink</info>');
        try {
            $link = public_path('vendor/');
            $target = '../../../vendor/refineddigital/cms-form-builder/assets/';

            // create the directories
            if (!is_dir($link)) {
                mkdir($link);
            }
            $link .= 'refined/';
            if (!is_dir($link)) {
                mkdir($link);
            }
            $link .= 'form-builder';
            if (! windows_os()) {
                return symlink($target, $link);
            }

            $mode = is_dir($target) ? 'J' : 'H';

            exec("mklink /{$mode} \"{$link}\" \"{$target}\"");
        } catch(\Exception $e) {
            $this->output->writeln('<error>Failed to install symlink</error>');
        }
    }
}
