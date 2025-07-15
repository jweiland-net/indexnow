<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Command;

use JWeiland\IndexNow\Configuration\ExtConf;
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
    description: 'Process records of table tx_indexnow_stack and inform searchengine about modifications via indexnow.'
)]
class NotifySearchEngineCommand extends Command
{
    /**
     * Will be called by DI, so please don't add extbase classes with inject methods here.
     */
    public function __construct(
        protected SearchEngineNotifier $searchEngineNotifier,
        protected StackRepository $stackRepository,
        protected ExtConf $extConf,
        protected LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->extConf->isNotifyBatchMode()) {
            $output->writeln('<info>Notify batch mode is enabled.</info>');
            $this->useNotifyBatchMode($input, $output);
            return self::SUCCESS;
        }

        $total = 0;
        $skipped = 0;
        $errors = 0;
        foreach ($this->stackRepository->findAll() as $urlRecord) {
            $total++;
            $url = trim($urlRecord['url'] ?? '');

            if ($url === '') {
                $this->stackRepository->deleteByUid((int)$urlRecord['uid']);
                $this->logger->info('URL record with empty URL found. Record deleted. Skip.');
                $output->writeln('<info>URL record with empty URL found. Record deleted. Skip.</info>');
                $skipped++;
                continue;
            }

            if ($this->searchEngineNotifier->notify($url)) {
                if ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
                    $this->logger->debug('Processed URL: ' . $url);
                    $output->writeln('Processed URL: ' . $url);
                }
                $this->stackRepository->deleteByUid((int)$urlRecord['uid']);
                continue;
            }

            $this->logger->error(sprintf(
                'URL record with UID %d from stack table could not be processed.',
                (int)$urlRecord['uid']
            ));
            $output->writeln(sprintf(
                '<error>URL record with UID %d from stack table could not be processed.</error>',
                (int)$urlRecord['uid']
            ));
            $errors++;
        }

        if ($total === 0) {
            $this->logger->info('No URL records available, nothing sent');
            $output->writeln('<info>No URL records available, nothing sent</info>');
            return self::SUCCESS;
        }

        if ($skipped || $errors) {
            $this->logger->warning(
                sprintf('<warning>%d URL records have been sent in total, %d were skipped, %d had errors</warning>', $total, $skipped, $errors)
            );
            $output->writeln(
                sprintf('<warning>%d URL records have been sent in total, %d were skipped, %d had errors</warning>', $total, $skipped, $errors)
            );
            return self::FAILURE;
        }

        $this->logger->info(sprintf('%d URL records have been sent successfully', $total));
        $output->writeln(sprintf('<info>%d URL records have been sent successfully</info>', $total));

        return self::SUCCESS;
    }

    protected function useNotifyBatchMode(InputInterface $input, OutputInterface $output): int
    {
        $records = $this->stackRepository->findAll();
        $urlMap = [];
        $uids = [];

        foreach ($records as $record) {
            $url = trim($record['url'] ?? '');
            if ($url === '') {
                $this->stackRepository->deleteByUid((int)$record['uid']);
                $this->logger->info('URL record with empty URL found. Record deleted. Skip.');
                $output->writeln('<info>URL record with empty URL found. Record deleted. Skip.</info>');
                continue;
            }
            $urlMap[] = $url;
            $uids[] = (int)$record['uid'];
        }

        if (empty($urlMap)) {
            $this->logger->info('No URL records available, nothing sent.');
            $output->writeln('<info>No URL records available, nothing sent.</info>');
            return self::SUCCESS;
        }

        $success = $this->searchEngineNotifier->notifyBatch($urlMap);

        if ($success) {
            foreach ($uids as $uid) {
                $this->stackRepository->deleteByUid($uid);
            }
            $this->logger->info(sprintf('Batch sent (%d URLs), all entries deleted', count($uids)));
            $output->writeln(sprintf('<info>Batch sent (%d URLs), all entries deleted.</info>', count($uids)));
            return self::SUCCESS;
        }

        $this->logger->error('Batch sending failed, entries remain in stack.');
        $output->writeln('<error>Batch sending failed, entries remain in stack.</error>');
        return self::FAILURE;
    }
}
