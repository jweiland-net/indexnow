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

/**
 * Repository for the records of table "tx_indexnow_stack"
 */
readonly class StackRepository
{
    public const TABLE = 'tx_indexnow_stack';

    public function __construct(
        private ConnectionPool $connectionPool,
    ) {}

    /**
     * @return list<Stack>
     */
    public function findAll(): array
    {
        try {
            $rows = $this->getQueryBuilder()
                ->select('uid', 'url', 'target_pid')
                ->from(self::TABLE)
                ->executeQuery()
                ->fetchAllAssociative();
        } catch (Exception) {
            return [];
        }

        return array_map($this->mapRowToStack(...), $rows);
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

    public function insert(string $url, int $pageUid): void
    {
        if ($this->hasUrl($url)) {
            return;
        }

        $now = time();

        $this->getQueryBuilder()
            ->insert(self::TABLE)
            ->values([
                'url' => $url,
                'target_pid' => $pageUid,
                'tstamp' => $now,
                'crdate' => $now,
                'url_hash' => $this->hash($url),
            ])
            ->executeStatement();
    }

    public function hasUrl(string $url): bool
    {
        $queryBuilder = $this->getQueryBuilder();

        try {
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
            // Return "true" to prevent the insertion of new records, if the query can not be executed
            return true;
        }

        return $existing !== false;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapRowToStack(array $row): Stack
    {
        $uid = $row['uid'] ?? 0;
        $url = $row['url'] ?? '';
        $targetPid = $row['target_pid'] ?? 0;

        return Stack::fromRaw(
            is_numeric($uid) ? (int)$uid : 0,
            is_string($url) ? $url : '',
            is_numeric($targetPid) ? (int)$targetPid : 0,
        );
    }

    private function hash(string $url): string
    {
        return sha1($url);
    }

    private function getQueryBuilder(): QueryBuilder
    {
        return $this->connectionPool->getQueryBuilderForTable(self::TABLE);
    }
}
