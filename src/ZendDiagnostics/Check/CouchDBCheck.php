<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Check;

use ZendDiagnostics\Result;

/**
 * Ensures a connection to CouchDB is possible.
 */
class CouchDB extends GuzzleHttpService
{
    /**
     * @param array $couchDbSettings
     *
     * @return self
     */
    public function __construct(array $couchDbSettings)
    {
        $couchDbUrl = '';
        if ($couchDbSettings['port'] === '5984' || $couchDbSettings['port'] === '80') {
            $couchDbUrl .= 'http://';
        } else {
            $couchDbUrl .= 'https://';
        }

        if ($couchDbSettings['username'] && $couchDbSettings['password']) {
            $couchDbUrl .= sprintf(
                '%s:%s@',
                $couchDbSettings['username'],
                $couchDbSettings['password']
            );
        }

        $couchDbUrl .= sprintf(
            '%s:%s/%s',
            $couchDbSettings['host'],
            $couchDbSettings['port'],
            $couchDbSettings['dbname']
        );

        parent::__construct($couchDbUrl);
    }

    /**
     * {@inheritDoc}
     */
    public function check()
    {
        $result = parent::check();
        if ($result instanceof Result\Success) {
            return $result;
        }

        $msg = $result->getMessage();
        $msg = preg_replace('=\/\/(.+):{1}(.+)(\@){1}=i', '//', $msg);

        $failure = new Result\Failure($msg, $result->getData());

        return $failure;
    }
}
