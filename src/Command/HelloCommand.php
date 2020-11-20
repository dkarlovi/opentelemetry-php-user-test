<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HelloCommand extends Command
{
    protected static $defaultName = 'app:hello';
    
    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        parent::__construct(self::$defaultName);
        
        $this->httpClient = $httpClient;
    }

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->note('Requesting http://127.0.0.1:8000/hello');
        
        global $tracer;
        
        for ($i = 0; $i < 5; $i++) {
            if ($tracer) {
                $tracer->startAndActivateSpan('HTTP GET http://127.0.0.1:8000/hello '. $i);
            }
            $response = $this->httpClient->request('GET', 'http://127.0.0.1:8000/hello');
            if ($tracer) {
                $tracer->endActiveSpan();
            }
            
            $io->note($response->getContent());
        }

        $io->success('Success!');

        return Command::SUCCESS;
    }
}
