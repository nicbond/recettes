<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create:api-key',
    description: 'Create an API-KEY for the back-office',
)]
class CreateApiKeyCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    /**
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $apiKey = bin2hex(random_bytes(32));

        $io->note(sprintf(
            'PUBLIC_API_KEY is: %s (stores it in your .env.local)',
            $apiKey
        ));

        return Command::SUCCESS;
    }
}
