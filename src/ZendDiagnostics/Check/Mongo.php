<?php
namespace ZendDiagnostics\Check;

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
    public function __construct($connectionUri)
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
        } catch (\Exception $e) {
            return new Failure(sprintf('Failed to connect to MongoDB server. Reason: `%s`', $e->getMessage()));
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
        if (class_exists('\MongoDB\Client')) {
            return (new \MongoDB\Client($this->connectionUri))->listDatabases();
        } elseif (class_exists('\MongoClient')) {
            return (new \MongoClient($this->server))->listDBs();
        }

        throw new \RuntimeException('Neither the mongo extension or mongodb are installed');
    }
}
