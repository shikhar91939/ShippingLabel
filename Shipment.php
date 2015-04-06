<?php
/**
 *
 * /index.php/admin/sales_order_shipment/print/invoice_id/25/
 *
 *
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   Mage
 * @package    Mage_Sales
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Payment method abstract model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class UMA_BarcodePackingSlip_Model_Shipment extends UMA_BarcodePackingSlip_Model_Abstract
{
    
    public function getPdf($shipments = array())
    {
        $pdf = new Zend_Pdf();
        $style = new Zend_Pdf_Style();
        $style->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 10);
        foreach ($shipments as $shipment) {
            $page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
            $pdf->pages[] = $page;

            $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
            $this->_setFontRegular($page, 10);
            // $page->drawText(".",0,835/*,'UTF-8'*/);

            $this->insertLogo($page);


        }
         return $pdf;
    }

    function insertLogo(&$page, $store = null)
    {
        $this->y = /*$this->y ? $this->y :*/ 835;   //given absolute value 835 . different from insertLogo() in Abstract.php
        $image = Mage::getStoreConfig('sales/identity/logo', $store);
        if($image)
        {
            $image = Mage::getBaseDir('media') . '/sales/store/logo/' . $image;
            if(is_file($image))
            {
                $image       = Zend_Pdf_Image::imageWithPath($image);
                $top         = 840; //top border of the page
                $widthLimit  = 160; //half of the page width
                $heightLimit = 100; //assuming the image is not a "skyscraper"
                $width       = $image->getPixelWidth();
                $height      = $image->getPixelHeight();

                //preserving aspect ratio (proportions)
                $ratio = $width / $height;
                if ($ratio > 1 && $width > $widthLimit) {
                    $width  = $widthLimit;
                    $height = $width / $ratio;
                } elseif ($ratio < 1 && $height > $heightLimit) {
                    $height = $heightLimit;
                    $width  = $height * $ratio;
                } elseif ($ratio == 1 && $height > $heightLimit) {
                    $height = $heightLimit;
                    $width  = $widthLimit;
                }

                $y1 = $top - $height;
                $y2 = $top;
                $x1 = 6;
                $x2 = $x1 + $width;

                //coordinates after transformation are rounded by Zend
                $page->drawImage($image, $x1, $y1, $x2, $y2);

                $this->y = $y1 - 10;
            
            }
        }

    }

}