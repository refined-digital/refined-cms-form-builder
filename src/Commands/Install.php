<?php

namespace RefinedDigital\FormBuilder\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Question\Question;
use Validator;
use Artisan;
use RuntimeException;

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

    protected $siteKey = '';
    protected $secretKey = '';

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
        $this->askQuestions();
        $this->migrate();
        $this->seed();
        $this->createSymLink();
        $this->updateEnvFile();
        $this->info('Form Builder has been successfully installed');
    }

    protected function askQuestions()
    {
        $helper = $this->getHelper('question');

        $question = new Question('reCaptcha Site Key?: ', false);
        $question->setValidator(function ($answer) {
            if(strlen($answer) < 1) {
                throw new RuntimeException('reCaptcha Site Key is required');
            }
            return $answer;
        });
        $question->setMaxAttempts(3);
        $this->siteKey = $helper->ask($this->input, $this->output, $question);

        $question = new Question('reCaptcha Secret Key?: ', false);
        $question->setValidator(function ($answer) {
            if(strlen($answer) < 1) {
                throw new RuntimeException('reCaptcha Secret Key is required');
            }
            return $answer;
        });
        $question->setMaxAttempts(3);
        $this->secretKey = $helper->ask($this->input, $this->output, $question);
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

            if (is_link($link)) {
                return;
            }

            if (! windows_os()) {
                return symlink($target, $link);
            }

            $mode = is_dir($target) ? 'J' : 'H';

            exec("mklink /{$mode} \"{$link}\" \"{$target}\"");
        } catch(\Exception $e) {
            $this->output->writeln('<error>Failed to install symlink</error>');
        }
    }

    protected function updateEnvFile()
    {
        $env = app()->environmentFilePath();
        $file = file_get_contents($env);

        // add in the cache settings
        $file .= "\n\nRECAPTCHA_SITE_KEY=".$this->siteKey."
RECAPTCHA_SECRET_KEY=".$this->secretKey."
RECAPTCHA_SKIP_IP=".config('app.url');
        file_put_contents($env, $file);
    }
}
