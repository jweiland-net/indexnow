<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Domain\Repository;

use Doctrine\DBAL\Exception;
use JWeiland\IndexNow\Domain\Model\Stack;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository to collect records from the table "tx_indexnow_stack"
 */
class StackRepository
{
    public const TABLE = 'tx_indexnow_stack';

    public function __construct(
        protected ConnectionPool $connectionPool,
    ) {}

    /**
     * @return Stack[]
     */
    public function findAll(): array
    {
        $queryBuilder = $this->getQueryBuilder();

        $statement = $queryBuilder
            ->select('uid', 'url')
            ->from(self::TABLE)
            ->executeQuery();

        $urlRecords = [];

        try {
            while ($urlRecord = $statement->fetchAssociative()) {
                $urlRecords[] = new Stack(
                    (int)$urlRecord['uid'],
                    (string)$urlRecord['url'],
                );
            }
        } catch (Exception) {
        }

        return $urlRecords;
    }

    public function deleteByUid(int $uid): void
    {
        $queryBuilder = $this->getQueryBuilder();

        $queryBuilder
            ->delete(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT),
                ),
            )
            ->executeStatement();
    }

    public function insert(string $url): void
    {
        if (!$this->hasUrl($url)) {
            $now = time();

            $queryBuilder = $this->getQueryBuilder();
            $queryBuilder
                ->insert(self::TABLE)
                ->values([
                    'url' => $url,
                    'tstamp' => $now,
                    'crdate' => $now,
                    'url_hash' => $this->hash($url),
                ])
                ->executeStatement();
        }
    }

    public function hasUrl(string $url): bool
    {
        try {
            $queryBuilder = $this->getQueryBuilder();
            $existing = $queryBuilder
                ->select('url_hash')
                ->from(self::TABLE)
                ->where(
                    $queryBuilder->expr()->eq(
                        'url_hash',
                        $queryBuilder->createNamedParameter($this->hash($url)),
                    ),
                )
                ->executeQuery()
                ->fetchOne();
        } catch (Exception) {
            // Return "true" here to prevent the insertion of new records if an exception is thrown during the query execution.
            return true;
        }

        return $existing !== false;
    }

    private function hash(string $url): string
    {
        return sha1($url);
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder;
    }
}
