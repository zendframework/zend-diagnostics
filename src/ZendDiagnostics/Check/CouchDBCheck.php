<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Check;

use ZendDiagnostics\Result;

/**
 * Ensures a connection to CouchDB is possible.
 */
class CouchDBCheck extends GuzzleHttpService
{
    /**
     * @param array $couchDbSettings
     * @param array $headers    An array of headers used to create the request
     * @param array $options    An array of guzzle options used to create the request
     *
     * @return self
     */
    public function __construct(array $couchDbSettings, array $headers = array(), array $options = array())
    {
        if (false === array_key_exists('url', $couchDbSettings)) {
            $couchDbUrl = $this->createUrlFromParameters($couchDbSettings);
        } else {
            $couchDbUrl = $couchDbSettings['url'];
        }

        parent::__construct($couchDbUrl, $headers, $options);
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

    /**
     * Assumes CouchDB defaults. Port 80 or 5984 is non-SSL, SSL otherwise.
     * Override with 'protocol' if you run something else.
     *
     * Requires/Supports the following keys in the array:
     *
     *  - dbname
     *  - host
     *  - port
     *  - protocol (optional)
     *  - username (optional)
     *  - password (optional)
     *
     * @param array $couchDbSettings
     *
     * @return string
     */
    private function createUrlFromParameters(array $couchDbSettings)
    {
        $couchDbUrl = '';

        if (array_key_exists('protocol', $couchDbSettings)) {
            $couchDbUrl .= $couchDbSettings['protocol'] . '://';
        } else {
            if ($couchDbSettings['port'] === '5984' || $couchDbSettings['port'] === '80') {
                $couchDbUrl .= 'http://';
            } else {
                $couchDbUrl .= 'https://';
            }
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

        return $couchDbUrl;
    }
}
