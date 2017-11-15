<?php

namespace KingStarter\LaravelSaml\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use neilherbertuk\modules\Traits\MakeController;
use neilherbertuk\modules\Traits\MakeModule;
use neilherbertuk\modules\Traits\MakeRoutes;

class EncodeAssertionUrlCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravel-saml:encodeurl
                    {url? : URL to encode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Helper command to base64 encode a provided url';

    /**
     * Create a new command instance.
     *
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
        // Get user input - Module Name and Filename
        $url = $this->argument('url');

        if(!empty($url)){
            $this->info("URL Given: $url");
            $this->info("Encoded AssertionURL:". base64_encode($url));
            return;
        }

        // Show Usage
        $this->showUsage();
    }

    /**
     *
     */
    protected function showUsage()
    {
        $this->info($this->getDescription());
        $this->warn('Usage: ');
        $this->line('   laravel-saml:encodeurl url');
        $this->line('');
        $this->warn('Arguments:');
        $this->line('   url - The URL to encode');
    }

}