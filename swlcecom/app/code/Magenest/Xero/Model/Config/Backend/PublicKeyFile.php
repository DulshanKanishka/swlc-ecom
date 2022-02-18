<?php
namespace Magenest\Xero\Model\Config\Backend;

use Magenest\Xero\Block\System\Config\Form\Field\PublicKeyFile as SourceFile;

class PublicKeyFile extends XeroFile
{
    protected $fileExtension = SourceFile::FILE_EXTENSION;

    protected $path = "magenest_xero_config/xero_api/public_key";
}