<?php

namespace App\Commands;

use App\Services\MemesPosterService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PostMemeCommand extends Command
{

    protected static $defaultName = 'memes:post-one';
    private MemesPosterService $memesService;

    public function __construct(MemesPosterService $memesService)
    {
        $this->memesService = $memesService;
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->memesService->postOneFromQuery() ? Command::SUCCESS : Command::FAILURE;
    }
}