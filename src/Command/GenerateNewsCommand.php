<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use App\Service\RemoteImageService;

class GenerateNewsCommand extends Command
{
    protected static $defaultName = 'app:generate-news';

    /**
     * @var RemoteImageService
     */
    private $remoteImageService;

    public function __construct(RemoteImageService $remoteImageService)
    {
        $this->remoteImageService = $remoteImageService;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Generate news from rbk')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->success('Start.');

        $all = $this->remoteImageService->getAll();

        dd($all);

        return Command::SUCCESS;
    }
}
