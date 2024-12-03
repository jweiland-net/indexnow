<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Domain\Repository;

use TYPO3\CMS\Core\Database\Query\QueryBuilder;

/**
 * Repository to collect records from table "tx_indexnow_stack"
 */
class StackRepository
{
    private const TABLE_NAME = 'tx_indexnow_stack';

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    public function findAll(): iterable
    {
        $statement = $this->queryBuilder
            ->select('uid', 'url')
            ->from(self::TABLE_NAME)
            ->execute();

        while ($urlRecord = $statement->fetch()) {
            yield $urlRecord;
        }
    }

    public function deleteByUid(int $uid): void
    {
        $this->queryBuilder
            ->delete(self::TABLE_NAME)
            ->where(
                $this->queryBuilder->expr()->eq(
                    'uid',
                    $this->queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            )
            ->execute();
    }

    public function insert(string $url): void
    {
        $connection = $this->queryBuilder->getConnection();
        $connection->insert(
            self::TABLE_NAME,
            [
                'cruser_id' => $GLOBALS['BE_USER']->user['uid'],
                'tstamp' => time(),
                'crdate' => time(),
                'url' => $url
            ]
        );
    }
}
