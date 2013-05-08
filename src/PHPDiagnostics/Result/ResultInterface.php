<?php
namespace PHPDiagnostics\Result;

interface ResultInterface
{
    /**
     * Get message related to the result.
     *
     * @return string
     */
    public function getMessage();

    /**
     * Get detailed info on the test result (if available).
     *
     * @return mixed|null
     */
    public function getData();
}
