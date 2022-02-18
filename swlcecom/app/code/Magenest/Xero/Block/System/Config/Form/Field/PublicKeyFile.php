<?php
namespace Magenest\Xero\Block\System\Config\Form\Field;

class PublicKeyFile extends XeroFile
{
    const FILE_EXTENSION = "cer";

    protected $fileExtension = ".".self::FILE_EXTENSION;
}
