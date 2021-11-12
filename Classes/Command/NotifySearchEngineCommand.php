<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Command;

use JWeiland\IndexNow\Service\SearchEngineService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * Process records of table tx_indexnow_stack and inform searchengine about modifications via indexnow
 */
class NotifySearchEngineCommand extends Command
{
    /**
     * @var SearchEngineService
     */
    protected $searchEngineService;

    /**
     * Will be called by DI, so please don't add extbase classes with inject methods here.
     *
     * @param SearchEngineService $searchEngineService
     * @param string|null $name
     */
    public function __construct(SearchEngineService $searchEngineService, string $name = null)
    {
        parent::__construct($name);

        $this->searchEngineService = $searchEngineService;
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
        $statement = $this->getQueryBuilderForIndexNowStack()->execute();

        while ($urlRecord = $statement->fetch()) {
            $url = trim($urlRecord['url']);
            if ($url) {
                if ($this->searchEngineService->notifySearchEngine($url)) {
                    if ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
                        $output->writeln('Processed URL: ' . $url);
                    }
                    $this->deleteRecordFromStack((int)$urlRecord['uid']);
                } else {
                    $output->writeln(sprintf(
                        '<error>URL record with UID %d from stack table could not be processed.</error>',
                        (int)$urlRecord['uid']
                    ));

                    return 1;
                }
            } else {
                $this->deleteRecordFromStack((int)$urlRecord['uid']);
                $output->writeln('<info>URL record with empty URL found. Record deleted. Skip.</info>');
            }
        }

        $output->writeln('All URL records have been notified at search engine');

        return 0;
    }

    protected function deleteRecordFromStack(int $uid): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('tx_indexnow_stack');
        $connection->delete(
            'tx_indexnow_stack',
            [
                'uid' => $uid
            ]
        );
    }

    protected function getQueryBuilderForIndexNowStack(): QueryBuilder
    {
        // No restriction needed as this table does not have any enableFields or deleted columns
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_indexnow_stack');

        return $queryBuilder
            ->select('uid', 'url')
            ->from('tx_indexnow_stack');
    }

    protected function getConnectionPool(): connectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
