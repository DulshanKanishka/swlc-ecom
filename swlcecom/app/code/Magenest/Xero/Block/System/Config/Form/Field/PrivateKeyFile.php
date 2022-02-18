<?php
namespace Magenest\Xero\Block\System\Config\Form\Field;

class PrivateKeyFile extends XeroFile
{
    const FILE_EXTENSION = "pem";

    protected $fileExtension = ".".self::FILE_EXTENSION;
}
