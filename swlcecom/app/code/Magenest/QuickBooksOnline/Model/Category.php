<?php
/**
 * Copyright © 2017 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magenest\QuickBooksOnline\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Class Category
 *
 * @package Magenest\QuickBooksOnline\Model
 * @method int getQboId()
 * @method string getCategoryName()
 * @method int getCatId()
 * @method Category setCatId(int $id)
 * @method Category setCategoryName(string $name)
 * @method Category setQboId(int $id)
 * @method Category setSyncToken(int $id)
 * @method  getSyncToken()
 */
class Category extends AbstractModel
{
    /**
     * Initize
     */
    public function _construct()
    {
        $this->_init('Magenest\QuickBooksOnline\Model\ResourceModel\Category');
    }

    /**
     * Load By Category Id
     *
     * @param $id
     * @return $this
     */
    public function loadByCategoryId($id)
    {
        return $this->load($id, 'cat_id');
    }
}
