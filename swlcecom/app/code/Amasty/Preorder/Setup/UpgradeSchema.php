<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Preorder
 */


namespace Amasty\Preorder\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * @var Operation\AddPreorderNote
     */
    private $addPreorderNote;

    public function __construct(
        Operation\AddPreorderNote $addPreorderNote
    ) {
        $this->addPreorderNote = $addPreorderNote;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.3.0', '<')) {
            $this->addPreorderNote->execute($setup);
        }

        $setup->endSetup();
    }
}
