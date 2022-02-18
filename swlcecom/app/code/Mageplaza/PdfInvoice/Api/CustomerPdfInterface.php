<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_PdfInvoice
 * @copyright   Copyright (c) Mageplaza (http://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\PdfInvoice\Api;

/**
 * Interface CustomerPdfDocumentInterface
 * @package Mageplaza\PdfInvoice\Api
 */
interface CustomerPdfInterface
{
    /**
     * @param string $id
     * @param string $customerId
     * @return \Mageplaza\PdfInvoice\Api\Data\ErrorResponseInterface|string
     */
    public function getPdfOrder($id, $customerId);
    
    /**
     * @param string $id
     * @param string $customerId
     * @return \Mageplaza\PdfInvoice\Api\Data\ErrorResponseInterface|string
     */
    public function getPdfInvoicesByOrder($id, $customerId);
    
    /**
     * @param string $id
     * @param string $customerId
     * @return \Mageplaza\PdfInvoice\Api\Data\ErrorResponseInterface|string
     */
    public function getPdfShipmentsByOrder($id, $customerId);
    
    /**
     * @param string $id
     * @param string $customerId
     * @return \Mageplaza\PdfInvoice\Api\Data\ErrorResponseInterface|string
     */
    public function getPdfCreditmemosByOrder($id, $customerId);
    
    /**
     * @param string $id
     * @param string $customerId
     * @return \Mageplaza\PdfInvoice\Api\Data\ErrorResponseInterface|string
     */
    public function getPdfInvoice($id, $customerId);
    
    /**
     * @param string $id
     * @param string $customerId
     * @return \Mageplaza\PdfInvoice\Api\Data\ErrorResponseInterface|string
     */
    public function getPdfShipment($id, $customerId);
    
    /**
     * @param string $id
     * @param string $customerId
     * @return \Mageplaza\PdfInvoice\Api\Data\ErrorResponseInterface|string
     */
    public function getPdfCreditmemo($id, $customerId);
}
