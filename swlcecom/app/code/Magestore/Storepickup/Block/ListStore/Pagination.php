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

namespace Magestore\Storepickup\Block\ListStore;

/**
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class Pagination extends \Magestore\Storepickup\Block\AbstractBlock
{
    const FIRST_PAGE = 1;
    /**
     * template.
     *
     * @var string
     */
    protected $_template = 'Magestore_Storepickup::liststore/pagination.phtml';

    /**
     * @var int
     */
    protected $_minPage;

    /**
     * @var int
     */
    protected $_maxPage;

    /**
     * @var \Magento\Framework\Data\Collection
     */
    protected $_collection;

    /**
     * Block constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array                                            $data
     */
    public function __construct(
        \Magestore\Storepickup\Block\Context $context,
        \Magento\Framework\Data\Collection $collection = null,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_collection = $collection;
    }

    /**
     * get collection.
     *
     * @return \Magento\Framework\Data\Collection
     */
    public function getCollection()
    {
        return $this->_collection;
    }

    /**
     * set collection for pagination.
     *
     * @param \Magento\Framework\Data\Collection $collection
     */
    public function setCollection(\Magento\Framework\Data\Collection $collection)
    {
        $this->_collection = $collection;
    }

    /**
     * Internal constructor, that is called from real constructor.
     */
    protected function _construct()
    {
        parent::_construct();

        if (!$this->hasData('range')) {
            $this->setData('range', 5);
        }
    }

    /**
     * @return mixed
     */
    public function getMinPage()
    {
        return $this->_minPage;
    }

    /**
     * @param mixed $minPage
     */
    public function setMinPage($minPage)
    {
        $this->_minPage = $minPage;
    }

    /**
     * @return mixed
     */
    public function getMaxPage()
    {
        return $this->_maxPage;
    }

    /**
     * @param mixed $maxPage
     */
    public function setMaxPage($maxPage)
    {
        $this->_maxPage = $maxPage;
    }

    /**
    /**
     * @return mixed
     */
    public function getPageSize()
    {
        return $this->getCollection()->getPageSize();
    }

    /**
     * @return mixed
     */
    public function getCurPage()
    {
        return $this->getCollection()->getCurPage();
    }

    /**
     * check has next page.
     *
     * @return bool
     */
    public function hasNextPage()
    {
        return $this->getCurPage() < $this->getTotalPage();
    }

    /**
     * check has previous page.
     *
     * @return bool
     */
    public function hasPrevPage()
    {
        return $this->getCurPage() > self::FIRST_PAGE;
    }

    /**
     * @return mixed
     */
    public function getNextPage()
    {
        return $this->hasNextPage() ? $this->getCurPage() + 1 : $this->getTotalPage();
    }

    public function getPrevPage()
    {
        return $this->hasPrevPage() ? $this->getCurPage() - 1 : $this->getTotalPage();
    }

    /**
     * @return mixed
     */
    public function getTotalPage()
    {
        return $this->getCollection()->getLastPageNumber();
    }

    /**
     * check current page is the first page.
     *
     * @param $page
     *
     * @return bool
     */
    public function currentIsFirstPage()
    {
        return $this->getCurPage() == self::FIRST_PAGE;
    }

    /**
     * check current page is last page.
     *
     * @param $page
     *
     * @return bool
     */
    public function currentIsLastPage()
    {
        return $this->getCurPage() == $this->getTotalPage();
    }

    /**
     * @return $this
     */
    protected function _preparePagination()
    {
        $middle = ceil($this->getRange() / 2);
        $totalPage = $this->getTotalPage();

        if ($totalPage < $this->getRange()) {
            $this->setMinPage(self::FIRST_PAGE);
            $this->setMaxPage($totalPage);
        } else {
            $this->setMinPage($this->getCurPage() - $middle + 1);
            $this->setMaxPage($this->getCurPage() + $middle - 1);

            if ($this->getMinPage() < self::FIRST_PAGE) {
                $this->setMinPage(self::FIRST_PAGE);
                $this->setMaxPage($this->getRange());
            } elseif ($this->getMaxPage() > $totalPage) {
                $this->setMinPage($totalPage - $this->getRange() + 1);
                $this->setMaxPage($totalPage);
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->_preparePagination();

        return $this;
    }

    /**
     * Set collection page size.
     *
     * @param int $size
     *
     * @return $this
     */
    public function setPageSize($size)
    {
        $this->getCollection()->setPageSize($size);

        return $this;
    }

    /**
     * Set current page.
     *
     * @param int $page
     *
     * @return $this
     */
    public function setCurPage($page)
    {
        $this->getCollection()->setCurPage($page);

        return $this;
    }
}
