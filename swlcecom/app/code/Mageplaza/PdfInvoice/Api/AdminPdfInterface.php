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
 * Interface PdfDocumentInterface
 * @package Mageplaza\PdfInvoice\Api
 */
interface AdminPdfInterface
{
    /**
     * @param string $id
     * @return \Mageplaza\PdfInvoice\Api\Data\ErrorResponseInterface|string
     */
    public function getPdfOrder($id);
    
    /**
     * @param string $id
     * @return \Mageplaza\PdfInvoice\Api\Data\ErrorResponseInterface|string
     */
    public function getPdfInvoicesByOrder($id);
    
    /**
     * @param string $id
     * @return \Mageplaza\PdfInvoice\Api\Data\ErrorResponseInterface|string
     */
    public function getPdfShipmentsByOrder($id);
    
    /**
     * @param string $id
     * @return \Mageplaza\PdfInvoice\Api\Data\ErrorResponseInterface|string
     */
    public function getPdfCreditmemosByOrder($id);
    
    /**
     * @param string $id
     * @return \Mageplaza\PdfInvoice\Api\Data\ErrorResponseInterface|string
     */
    public function getPdfInvoice($id);
    
    /**
     * @param string $id
     * @return \Mageplaza\PdfInvoice\Api\Data\ErrorResponseInterface|string
     */
    public function getPdfShipment($id);
    
    /**
     * @param string $id
     * @return \Mageplaza\PdfInvoice\Api\Data\ErrorResponseInterface|string
     */
    public function getPdfCreditmemo($id);
}
