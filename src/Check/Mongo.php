<?php
/**
 * @see       https://github.com/zendframework/zend-diagnostics for the canonical source repository
 * @copyright Copyright (c) 2013-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diagnostics/blob/master/LICENSE.md New BSD License
 */

namespace ZendDiagnostics\Check;

use Exception;
use MongoClient;
use MongoDB\Client as MongoDBClient;
use RuntimeException;
use ZendDiagnostics\Result\Success;
use ZendDiagnostics\Result\Failure;

class Mongo extends AbstractCheck
{
    /**
     * @var string
     */
    private $connectionUri;

    /**
     * @param string $connectionUri
     */
    public function __construct($connectionUri = 'mongodb://127.0.0.1/')
    {
        $this->connectionUri = $connectionUri;
    }

    /**
     * {@inheritdoc}
     */
    public function check()
    {
        try {
            $this->getListDBs();
        } catch (Exception $e) {
            return new Failure(sprintf(
                'Failed to connect to MongoDB server. Reason: `%s`',
                $e->getMessage()
            ));
        }

        return new Success();
    }

    /**
     * @return array|\Iterator
     *
     * @throws \RuntimeException
     * @throws \MongoDB\Driver\Exception
     * @throws \MongoConnectionException
     */
    private function getListDBs()
    {
        if (class_exists(MongoDBClient::class)) {
            return (new MongoDBClient($this->connectionUri))->listDatabases();
        }

        if (class_exists(MongoClient::class)) {
            return (new MongoClient($this->connectionUri))->listDBs();
        }

        throw new RuntimeException('Neither the mongo extension or mongodb are installed');
    }
}
