<?php
declare(strict_types=1);

namespace Jetty\BackgroundProcessing\BackgroundProcess\Adapter\Out\MySql;

use Jetty\BackgroundProcessing\BackgroundProcess\Application\Port\Out\BatchTable;
use Jetty\BackgroundProcessing\BackgroundProcess\Application\Port\Out\QueueBatchRepository;
use Jetty\BackgroundProcessing\BackgroundProcess\Domain\BatchItem;
use Psr\Log\LoggerInterface;

/**
 * Batch repository implemented using mysqli.
 */
final class MySqlBatchItemRepository implements QueueBatchRepository
{
    /**
     * @var BatchTable
     */
    private $batchTable;

    /**
     * @var string
     */
    private $batchPrefix;

    /**
     * MySqlBatchItemRepository constructor.
     *
     * @param LoggerInterface $logger Implementation to log errors
     * @param \mysqli $mysqli Connection for MySQL database
     * @param string $dbPrefix The MySQL database prefix
     * @param BatchTable $table The BatchTable instance
     * @param string $actionName The background job definition name
     */
    public function __construct(
        LoggerInterface $logger,
        \mysqli $mysqli,
        string $dbPrefix,
        BatchTable $table,
        string $actionName
    ) {
        $this->batchPrefix = $actionName . '_batch_';
        $this->batchTable  = $table;
    }


    /**
     * {@inheritdoc}
     */
    public function createBatchItem($value): QueueBatchRepository
    {
        $key = $this->generateKey();

        $item = new BatchItem($key, $value);

        $this->batchTable->insert($item);

        return $this;
    }


    public function batchItemsExist(): bool
    {
        return $this->batchTable->hasItems();
    }


    /**
     * {@inheritdoc}
     */
    public function readBatchItems(): array
    {
        return $this->batchTable->readAll();
    }


    public function deleteBatchItem(BatchItem $item): QueueBatchRepository
    {
        $this->batchTable->delete($item);

        return $this;
    }


    public function persist(): QueueBatchRepository
    {
        $this->batchTable->persist();

        return $this;
    }


    public function tryGetLock(): bool
    {
        return $this->batchTable->tryGetLock();
    }


    /**
     * Generates a unique key based on microtime. BatchItems are
     * given a unique key to save to the database.
     */
    private function generateKey(): string
    {
        $length = 32;
        $unique = md5(microtime() . rand());
        $unique = substr($unique, 0, $length);
        return $this->batchPrefix . $unique;
    }
}
