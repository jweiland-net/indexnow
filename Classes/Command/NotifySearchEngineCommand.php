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
 * Process records of table tx_indexnow_stack and inform searchengine about modifications via indexnow
 */
#[AsCommand(
    name: 'indexnow:notify',
    description: 'Process records of table tx_indexnow_stack and inform searchengine about modifications via indexnow.',
)]
class NotifySearchEngineCommand extends Command
{
    /**
     * Will be called by DI, so please don't add extbase classes with inject methods here.
     */
    public function __construct(
        protected SearchEngineNotifier $searchEngineNotifier,
        protected StackRepository $stackRepository,
        protected LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stackStorage = $this->getStackStorage($output);

        if ($stackStorage->count() === 0) {
            $this->logger->info('No URL records available, nothing sent.');
            $output->writeln('<info>No URL records available, nothing sent.</info>');
            return self::SUCCESS;
        }

        if (!$this->searchEngineNotifier->notify($stackStorage)) {
            $this->logger->error('Batch sending failed, entries remain in stack.');
            $output->writeln('<error>Batch sending failed, entries remain in stack.</error>');
            return self::FAILURE;
        }

        foreach ($stackStorage as $stack) {
            $this->stackRepository->deleteByUid($stack->getUid());
        }

        $this->logger->info(sprintf('Batch sent (%d URLs), all entries deleted', count($uids)));
        $output->writeln(sprintf('<info>Batch sent (%d URLs), all entries deleted.</info>', count($uids)));

        return self::SUCCESS;
    }

    protected function getStackStorage(OutputInterface $output): \SplObjectStorage
    {
        /** @var \SplObjectStorage<Stack> $stackStorage */
        $stackStorage = new \SplObjectStorage();

        foreach ($this->stackRepository->findAll() as $stack) {
            if (!$stack->hasValidUrl()) {
                $this->deleteInvalidStack($stack, $output);
                continue;
            }

            $stackStorage->attach($stack);
        }

        return $stackStorage;
    }

    protected function deleteInvalidStack(Stack $stack, OutputInterface $output): void
    {
        $this->stackRepository->deleteByUid($stack->getUid());

        $this->logger->info('URL record with invalid URL found. Record deleted. Skip.');
        $output->writeln('<info>URL record with invalid URL found. Record deleted. Skip.</info>');
    }
}
