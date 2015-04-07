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

            //drawing page border
            $pageWidth = $page->getWidth();
            $pageHeight = $page->getHeight();
            // Zend_debug::dump(array($pageWidth, $pageHeight));
            // Zend_debug::dump($this->_pageDictionary->Rotate->value);die;+
            $marginWidth = 7;
            $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));
            $page->drawRectangle($marginWidth, $marginWidth, $pageWidth-$marginWidth, $pageHeight-$marginWidth);

            //draw bottom line of row1 (row 1 contains the logo, address box, and invoice number box)
            $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));
            $y_row1ends = $pageHeight-$marginWidth-74;//74 is the hieght of the top row
            $page->drawLine($marginWidth, $y_row1ends, $pageWidth-$marginWidth, $y_row1ends);
            
            //using the insertLogo() function in this file (oveidding the one in abstract.php)
            $this->_setFontRegular($page, 10);
            $this->insertLogo($page);

            //draw logo box's right line
            $x_logoboxRightBorder = $marginWidth + 200;
            $page->drawLine($x_logoboxRightBorder,$pageHeight-$marginWidth,$x_logoboxRightBorder, $y_row1ends);
                
            
            //insert Warehouse address after reading the assigned warehouse from the shipment details in mageno
            // $this->address_kalkaji($page);//this function is not working and call different functions according to bangalore/ kalkaji warehouse

            // remove the following code after the function for inserting warehouse address 
            $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
            $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);
            $page->setFont($font, 11);
            $page->drawText("OVERCART",$x_logoboxRightBorder+42,$pageHeight -$marginWidth -10/*,'UTF-8'*/);
            //Address: "line 2 to 5: B 37/A, Basement, Kalka Ji"
            $this->_setFontRegular($page, 10);
            $page->drawText("B 37/A, Basement, Kalka Ji",$x_logoboxRightBorder+17,$pageHeight -$marginWidth -22);
            $page->drawText("New Delhi - 110019, India",$x_logoboxRightBorder+19,$pageHeight -$marginWidth -34);
            $page->drawText("Phone : +91-828-752-2555",$x_logoboxRightBorder+20,$pageHeight -$marginWidth -46);
            $page->drawText("Email: help@overcart.com",$x_logoboxRightBorder+19,$pageHeight -$marginWidth -58);
            $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);//to write the TIN number in bold
            $page->drawText("TIN : 383837437483",$x_logoboxRightBorder+30,$pageHeight -$marginWidth -70);
            $this->_setFontRegular($page, 10);

                //where am i..
                // $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
                // $this->_setFontRegular($page, 10);
                // $page->drawText("Overcart",$x_logoboxRightBorder+30,$pageHeight -$marginWidth -10/*,'UTF-8'*/);


            //x=11+160  y=835(top) y=835-55=780
            



        }
         return $pdf;
    }


    function address_kalkaji(&$page){   // POSSIBLE ERROR : variables called in this function are outsite their scope (e.g. $pageHeight)
            //Address: "line 1: Overcart"
            $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
            $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);
            $page->setFont($font, 11);
            $page->drawText("OVERCART",$x_logoboxRightBorder+42,$pageHeight -$marginWidth -10/*,'UTF-8'*/);
            //Address: "line 2 to 5: B 37/A, Basement, Kalka Ji"
            $this->_setFontRegular($page, 10);
            $page->drawText("B 37/A, Basement, Kalka Ji",$x_logoboxRightBorder+17,$pageHeight -$marginWidth -22);
            $page->drawText("New Delhi - 110019, India",$x_logoboxRightBorder+19,$pageHeight -$marginWidth -34);
            $page->drawText("Phone : +91-828-752-2555",$x_logoboxRightBorder+20,$pageHeight -$marginWidth -46);
            $page->drawText("Email: help@overcart.com",$x_logoboxRightBorder+19,$pageHeight -$marginWidth -58);
            $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);//to write the TIN number in bold
            $page->drawText("TIN : 383837437483",$x_logoboxRightBorder+30,$pageHeight -$marginWidth -70);
            $this->_setFontRegular($page, 10);
    }

    function insertLogo(&$page, $store = null)//overrides the function in abstract file
    {
        $this->y = /*$this->y ? $this->y :*/ 830;   //given absolute value 835 . different from insertLogo() in Abstract.php
        $image = Mage::getStoreConfig('sales/identity/logo', $store);
        if($image)
        {
            $image = Mage::getBaseDir('media') . '/sales/store/logo/' . $image;
            if(is_file($image))
            {
                $image       = Zend_Pdf_Image::imageWithPath($image);
                $top         = 835; //top border of the page
                $widthLimit  = 160; //half of the page width
                $heightLimit = 100; //assuming the image is not a "skyscraper"
                $width       = $image->getPixelWidth();//294px
                $height      = $image->getPixelHeight();//55px
                // Zend_debug::dump(array($width , $height));die;
                
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
                $x1 = 11;
                $x2 = $x1 + $width;

                //coordinates after transformation are rounded by Zend
                $page->drawImage($image, $x1, $y1, $x2, $y2);

                $this->y = $y1 - 10;
            }
        }

    }
}