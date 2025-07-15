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
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

/**
 * Repository to collect records from the table "tx_indexnow_stack"
 */
class StackRepository
{
    private const TABLE_NAME = 'tx_indexnow_stack';

    public function __construct(
        protected QueryBuilder $queryBuilder,
    ) {}

    /**
     * @return Stack[]
     */
    public function findAll(): array
    {
        $statement = $this->queryBuilder
            ->select('uid', 'url')
            ->from(self::TABLE_NAME)
            ->executeQuery();

        $urlRecords = [];

        try {
            while ($urlRecord = $statement->fetchAssociative()) {
                $urlRecords[] = new Stack(
                    (int)$urlRecord['uid'],
                    (string)$urlRecord['url'],
                );
            }
        } catch (Exception $e) {
        }

        return $urlRecords;
    }

    public function deleteByUid(int $uid): void
    {
        $this->queryBuilder
            ->delete(self::TABLE_NAME)
            ->where(
                $this->queryBuilder->expr()->eq(
                    'uid',
                    $this->queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                )
            )
            ->executeStatement();
    }

    public function insert(string $url): void
    {
        if (!$this->hasUrl($url)) {
            $now = time();

            $this->queryBuilder
                ->insert(self::TABLE_NAME)
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
            $existing = $this->queryBuilder
                ->select('url_hash')
                ->from(self::TABLE_NAME)
                ->where(
                    $this->queryBuilder->expr()->eq(
                        'url_hash',
                        $this->queryBuilder->createNamedParameter($this->hash($url))
                    )
                )
                ->executeQuery()
                ->fetchOne();
        } catch (Exception $e) {
            // Return "true" here to prevent the insertion of new records if an exception is thrown during the query execution.
            return true;
        }

        return $existing !== false;
    }

    private function hash(string $url): string
    {
        return sha1($url);
    }
}
