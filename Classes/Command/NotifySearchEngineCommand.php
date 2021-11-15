<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Command;

use JWeiland\IndexNow\Domain\Repository\StackRepository;
use JWeiland\IndexNow\Notifier\SearchEngineNotifier;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/*
 * Process records of table tx_indexnow_stack and inform searchengine about modifications via indexnow
 */
final class NotifySearchEngineCommand extends Command
{
    /**
     * @var SearchEngineNotifier
     */
    private $searchEngineNotifier;

    /**
     * @var StackRepository
     */
    private $stackRepository;

    /**
     * Will be called by DI, so please don't add extbase classes with inject methods here.
     */
    public function __construct(SearchEngineNotifier $searchEngineNotifier, StackRepository $stackRepository)
    {
        parent::__construct();

        $this->searchEngineNotifier = $searchEngineNotifier;
        $this->stackRepository = $stackRepository;
    }

    protected function configure(): void
    {
        $this->setDescription(
            'Process records of table tx_indexnow_stack and inform searchengine about modifications via ' .
            'indexnow.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $total = 0;
        $skipped = 0;
        $errors = 0;
        foreach ($this->stackRepository->findAll() as $urlRecord) {
            $total++;
            $url = trim($urlRecord['url']);

            if ($url === '') {
                $this->stackRepository->deleteByUid((int)$urlRecord['uid']);
                $output->writeln('<info>URL record with empty URL found. Record deleted. Skip.</info>');
                $skipped++;
                continue;
            }

            if ($this->searchEngineNotifier->notify($url)) {
                if ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
                    $output->writeln('Processed URL: ' . $url);
                }
                $this->stackRepository->deleteByUid((int)$urlRecord['uid']);
                continue;
            }

            $output->writeln(sprintf(
                '<error>URL record with UID %d from stack table could not be processed.</error>',
                (int)$urlRecord['uid']
            ));
            $errors++;
        }

        if ($total === 0) {
            $output->writeln('<info>No URL records available, nothing sent</info>');
            return self::SUCCESS;
        }

        if ($skipped || $errors) {
            $output->writeln(
                sprintf('<warning>%d URL records have been sent in total, %d were skipped, %d had errors</warning>', $total, $skipped, $errors)
            );
            return self::FAILURE;
        }

        $output->writeln(sprintf('<info>%d URL records have been sent successfully</info>', $total));
        return self::SUCCESS;
    }
}
