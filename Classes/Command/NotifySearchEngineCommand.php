<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Command;

use JWeiland\IndexNow\Domain\Model\Stack;
use JWeiland\IndexNow\Domain\Repository\StackRepository;
use JWeiland\IndexNow\Notifier\SearchEngineNotifier;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Process records of table tx_indexnow_stack and inform search engines about modifications via IndexNow
 */
#[AsCommand(
    name: 'indexnow:notify',
    description: 'Process records of table tx_indexnow_stack and inform searchengine about modifications via indexnow.',
)]
final class NotifySearchEngineCommand extends Command
{
    public function __construct(
        private readonly SearchEngineNotifier $searchEngineNotifier,
        private readonly StackRepository $stackRepository,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stacks = $this->collectValidStacks($output);

        if ($stacks === []) {
            $this->report($output, 'info', 'No URL records available, nothing sent.');

            return Command::SUCCESS;
        }

        if (!$this->searchEngineNotifier->notify($stacks)) {
            $this->report($output, 'error', 'Batch sending failed, entries remain in stack.');

            return Command::FAILURE;
        }

        foreach ($stacks as $stack) {
            $this->stackRepository->deleteByUid($stack->getUid());
        }

        $this->report($output, 'info', sprintf('Batch sent (%d URLs), all entries deleted.', count($stacks)));

        return Command::SUCCESS;
    }

    /**
     * Stacks with an invalid URL can never be sent. They will be deleted.
     *
     * @return list<Stack>
     */
    private function collectValidStacks(OutputInterface $output): array
    {
        $validStacks = [];

        foreach ($this->stackRepository->findAll() as $stack) {
            if ($stack->hasValidUrl()) {
                $validStacks[] = $stack;
                continue;
            }

            $this->stackRepository->deleteByUid($stack->getUid());
            $this->report($output, 'info', 'URL record with invalid URL found. Record deleted. Skip.');
        }

        return $validStacks;
    }

    /**
     * @param 'info'|'error' $level
     */
    private function report(OutputInterface $output, string $level, string $message): void
    {
        $this->logger->log($level, $message);
        $output->writeln(sprintf('<%1$s>%2$s</%1$s>', $level, $message));
    }
}
