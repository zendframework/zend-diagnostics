<?php

namespace ZendDiagnostics\Check;

use XMLReader;
use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\Success;
use ZendDiagnostics\Result\ResultInterface;

/**
 * Checks if an XML file is available and valid
 */
class XmlFile extends AbstractFileCheck
{
    /**
     * @param string $file
     * @return ResultInterface
     */
    protected function validateFile($file)
    {
        $xmlReader = new XMLReader();

        if (!$xmlReader->open($file)) {
            return new Failure(sprintf('Could not open "%s" with XMLReader!', $file));
        }

        $xmlReader->setParserProperty(XMLReader::VALIDATE, true);

        if (!$xmlReader->isValid()) {
            return new Failure(sprintf('File "%s" is not valid XML!', $file));
        }

        if (!@simplexml_load_file($file)) {
            return new Failure(sprintf('File "%s" is not well-formed XML!', $file));
        }

        return new Success();
    }
}
