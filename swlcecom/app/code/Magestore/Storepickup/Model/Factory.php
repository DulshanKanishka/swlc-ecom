<?php

/**
 * Magestore.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_Storepickup
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

namespace Magestore\Storepickup\Model;

/**
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class Factory
{
    /**#@+
     * Allowed object types
     */
    const MODEL_SPECIALDAY = 'specialday';
    const MODEL_HOLIDAY = 'holiday';
    const MODEL_TAG = 'tag';
    const MODEL_SCHEDULE = 'schedule';

    /**
     * Map of types which are references to classes.
     *
     * @var array
     */
    protected $_typeMap = [
        self::MODEL_SPECIALDAY => 'Magestore\Storepickup\Model\Specialday',
        self::MODEL_HOLIDAY => 'Magestore\Storepickup\Model\Holiday',
        self::MODEL_TAG => 'Magestore\Storepickup\Model\Tag',
        self::MODEL_SCHEDULE => 'Magestore\Storepickup\Model\Schedule',
    ];

    /**
     * @var ObjectManagerInterface
     */
    private $_objectManager;

    /**
     * Constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param array                  $typeMap
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        array $typeMap = []
    ) {
        $this->_objectManager = $objectManager;
        $this->mergeTypes($typeMap);
    }

    /**
     * Add or override object types.
     *
     * @param array $typeMap
     */
    protected function mergeTypes(array $typeMap)
    {
        foreach ($typeMap as $typeInfo) {
            if (isset($typeInfo['type']) && isset($typeInfo['class'])) {
                $this->_typeMap[$typeInfo['type']] = $typeInfo['class'];
            }
        }
    }

    /**
     * @param $type
     * @param array $arguments
     *
     * @return mixed
     */
    public function create($type, array $arguments = [])
    {
        if (empty($this->_typeMap[$type])) {
            throw new \InvalidArgumentException('"' . $type . ': isn\'t allowed');
        }

        $instance = $this->_objectManager->create($this->_typeMap[$type], $arguments);
        if (!$instance instanceof \Magestore\Storepickup\Model\AbstractModelManageStores) {
            throw new \InvalidArgumentException(
                get_class($instance)
                . ' isn\'t instance of \Magestore\Storepickup\Model\AbstractModelManageStores'
            );
        }

        return $instance;
    }
}
