<?php
/**
 * Barcode Add-On
 *
 * @author      Matt Johnson <M@ttJohnson.com>
 */
abstract class UMA_BarcodePackingSlip_Model_Abstract extends Mage_Sales_Model_Order_Pdf_Abstract
{
    protected function convertToBarcodeString($toBarcodeString)
    {
        $str = $toBarcodeString;
        $barcode_data = str_replace(' ', chr(128), $str);

        $checksum = 104; # must include START B code 128 value (104) in checksum
        for($i=0;$i<strlen($str);$i++) {
                $code128 = '';
                if (ord($barcode_data{$i}) == 128) {
                        $code128 = 0;
                } elseif (ord($barcode_data{$i}) >= 32 && ord($barcode_data{$i}) <= 126) {
                        $code128 = ord($barcode_data{$i}) - 32;
                } elseif (ord($barcode_data{$i}) >= 126) {
                        $code128 = ord($barcode_data{$i}) - 50;
                }
        $checksum_position = $code128 * ($i + 1);
        $checksum += $checksum_position;
        }
        $check_digit_value = $checksum % 103;
        $check_digit_ascii = '';
        if ($check_digit_value <= 94) {
            $check_digit_ascii = $check_digit_value + 32;
        } elseif ($check_digit_value > 94) {
            $check_digit_ascii = $check_digit_value + 50;
        }
        $barcode_str = chr(154) . $barcode_data . chr($check_digit_ascii) . chr(156);
            
        return $barcode_str;

    }
   /**
     * Insert logo to pdf page
     *
     * @param Zend_Pdf_Page $page
     * @param null $store
     */
    protected function insertLogo(&$page, $store = null)
    {
        $this->y = $this->y ? $this->y : 815;
        $image = Mage::getStoreConfig('sales/identity/logo', $store);
        if ($image) {
            $image = Mage::getBaseDir('media') . '/sales/store/logo/' . $image;
            if (is_file($image)) {
                $image       = Zend_Pdf_Image::imageWithPath($image);
                $top         = 830; //top border of the page
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
                $x1 = 25;
                $x2 = $x1 + $width;

                //coordinates after transformation are rounded by Zend
                $page->drawImage($image, $x1, $y1, $x2, $y2);

                $this->y = $y1 - 10;
            }
        }
    }	
	public function insertOrderTotal(Zend_Pdf_Page $page, $text)
    {
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));
        $this->_setFontRegular($page, 10);
        $docHeader = $this->getDocHeaderCoordinates();
        $page->drawText($text, 350, $docHeader[1] - 15, 'UTF-8');
    }	
	 protected function insertAddress(&$page, $store = null)
    {
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $font = $this->_setFontRegular($page, 10);
        $page->setLineWidth(0);
        $this->y = $this->y ? $this->y : 815;
        $top = 815;
		$address=explode("\n", Mage::getStoreConfig('sales/identity/address', $store));
        for($i=count($address);$i>0;$i--){$value=$address[$i];
            if ($value !== '') {
                $value = preg_replace('/<br[^>]*>/i', "\n", $value);
                foreach (Mage::helper('core/string')->str_split($value, 45, true, true) as $_value) {
                   
                    $top -= 10;
                }
            }
        }
        $this->y = ($this->y > $top) ? $top : $this->y;
    }
	protected function insertOrder(&$page, $obj,$shipmentincremt, $putOrderId = true)
    {
	//echo 'Dinesh';die;
		$tracks = array();
        if ($obj instanceof Mage_Sales_Model_Order) {
            $shipment = null;
            $order = $obj;
			$shipment=$order->getShipmentsCollection();
			$tracks=$order->getTracksCollection();
        } elseif ($obj instanceof Mage_Sales_Model_Order_Shipment) {
            $shipment = $obj;
            $order = $shipment->getOrder();
        }
			
        $this->y = $this->y ? $this->y : 815;
        $top = $this->y;

        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0.45));
        $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.45));
        $page->drawRectangle(25, $top, 570, $top - 55);
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));
        $this->setDocHeaderCoordinates(array(25, $top, 570, $top - 55));
        $this->_setFontRegular($page, 10);

        if ($putOrderId) {
            $page->drawText(
                Mage::helper('sales')->__('Order # ') . $order->getRealOrderId(), 35, ($top -= 30), 'UTF-8'
            );
        }
        $page->drawText(
            Mage::helper('sales')->__('Order Date: ') . Mage::helper('core')->formatDate(
                $order->getCreatedAtStoreDate(), 'medium', false
            ),
            35,
            ($top -= 15),
            'UTF-8'
        );

        $top -= 10;
        $page->setFillColor(new Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
        $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        $page->drawRectangle(25, $top, 275, ($top - 25));
        $page->drawRectangle(275, $top, 570, ($top - 25));

        /* Calculate blocks info */

        /* Billing Address */
        $billingAddress = $this->_formatAddress($order->getBillingAddress()->format('pdf'));

        /* Payment */
        $paymentInfo = Mage::helper('payment')->getInfoBlock($order->getPayment())
            ->setIsSecureMode(true)
            ->toPdf();
        $paymentInfo = htmlspecialchars_decode($paymentInfo, ENT_QUOTES);
        $payment = explode('{{pdf_row_separator}}', $paymentInfo);
        foreach ($payment as $key=>$value){
            if (strip_tags(trim($value)) == '') {
                unset($payment[$key]);
            }
        }
        reset($payment);

        /* Shipping Address and Method */
        if (!$order->getIsVirtual()) {
            /* Shipping Address */
            $shippingAddress = $this->_formatAddress($order->getShippingAddress()->format('pdf'));
            $shippingMethod  = $order->getShippingDescription();
        }

        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $this->_setFontBold($page, 12);
		  if (!$order->getIsVirtual()) {
            $page->drawText(Mage::helper('sales')->__('Deliver To:'), 35, ($top - 15), 'UTF-8');
        } else {
            $page->drawText(Mage::helper('sales')->__('Payment Method:'), 35, ($top - 15), 'UTF-8');
        }
        $page->drawText(Mage::helper('sales')->__('Sold to:'), 285, ($top - 15), 'UTF-8');

      

        $addressesHeight = $this->_calcAddressHeight($billingAddress);
        if (isset($shippingAddress)) {
            $addressesHeight = max($addressesHeight, $this->_calcAddressHeight($shippingAddress));
        }

        $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));
        $page->drawRectangle(25, ($top - 25), 570, $top - 33 - $addressesHeight);
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $this->_setFontRegular($page, 10);
        $this->y = $top - 40;
        $addressesStartY = $this->y;

        foreach ($billingAddress as $value){
            if ($value !== '') {
                $text = array();
                foreach (Mage::helper('core/string')->str_split($value, 45, true, true) as $_value) {
                    $text[] = $_value;
                }
                foreach ($text as $part) {
                    $page->drawText(strip_tags(ltrim($part)), 285, $this->y, 'UTF-8');
                    $this->y -= 15;
                }
            }
        }

        $addressesEndY = $this->y;

        if (!$order->getIsVirtual()) {
            $this->y = $addressesStartY;
            foreach ($shippingAddress as $value){
                if ($value!=='') {
                    $text = array();
                    foreach (Mage::helper('core/string')->str_split($value, 45, true, true) as $_value) {
                        $text[] = $_value;
                    }
                    foreach ($text as $part) {
                        $page->drawText(strip_tags(ltrim($part)), 35, $this->y, 'UTF-8');
                        $this->y -= 15;
                    }
                }
            }

            $addressesEndY = min($addressesEndY, $this->y);
            $this->y = $addressesEndY;

            $page->setFillColor(new Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
            $page->setLineWidth(0.5);
            $page->drawRectangle(25, $this->y, 275, $this->y-25);
            $page->drawRectangle(275, $this->y, 570, $this->y-25);

            $this->y -= 15;
            $this->_setFontBold($page, 12);
            $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
            $page->drawText(Mage::helper('sales')->__('Payment Method'), 35, $this->y, 'UTF-8');
            $page->drawText(Mage::helper('sales')->__('Shipping Method:'), 285, $this->y , 'UTF-8');

            $this->y -=10;
            $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));

            $this->_setFontRegular($page, 10);
            $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));

            $paymentLeft = 35;
            $yPayments   = $this->y - 15;
        }
        else {
            $yPayments   = $addressesStartY;
            $paymentLeft = 285;
        }

        foreach ($payment as $value){
            if (trim($value) != '') {
                //Printing "Payment Method" lines
                $value = preg_replace('/<br[^>]*>/i', "\n", $value);
                foreach (Mage::helper('core/string')->str_split($value, 45, true, true) as $_value) {
                    $page->drawText(strip_tags(trim($_value)), $paymentLeft, $yPayments, 'UTF-8');
                    $yPayments -= 15;
                }
            }
			
        }
			$this->insertBarcodeString($page, $order,$paymentLeft+90,$yPayments-15);
		    $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 7);
			//$this->insertBarPayment($page, $order,$paymentLeft+90,$yPayments-15);

        if ($order->getIsVirtual()) {
            // replacement of Shipments-Payments rectangle block
            $yPayments = min($addressesEndY, $yPayments);
            $page->drawLine(25,  ($top - 25), 25,  $yPayments);
            $page->drawLine(570, ($top - 25), 570, $yPayments);
            $page->drawLine(25,  $yPayments,  570, $yPayments);

            $this->y = $yPayments - 15;
        } else {
            $topMargin    = 15;
            $methodStartY = $this->y;
            $this->y     -= 10;

            foreach (Mage::helper('core/string')->str_split($shippingMethod, 45, true, true) as $_value) {
                $page->drawText(strip_tags(trim($_value)), 285, $this->y, 'UTF-8');
                $this->y =$this->y;
            }

            $yShipments = $this->y;
            $totalShippingChargesText = "(" . Mage::helper('sales')->__('Total Shipping Charges') . " "
                . $order->formatPriceTxt($order->getShippingAmount()) . ")";

            $page->drawText($totalShippingChargesText, 285, $yShipments - $topMargin, 'UTF-8');
            $yShipments -= $topMargin + 5;

            
            if ($shipment) {
				if(!$tracks)
                $tracks = $shipment->getAllTracks();
            }
            if (count($tracks)) {
                $page->setFillColor(new Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
                $page->setLineWidth(0.5);
                $page->drawRectangle(285, $yShipments, 510, $yShipments - 10);
                $page->drawLine(400, $yShipments, 400, $yShipments - 10);
                //$page->drawLine(510, $yShipments, 510, $yShipments - 10);

                $this->_setFontRegular($page, 9);
                $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
                //$page->drawText(Mage::helper('sales')->__('Carrier'), 290, $yShipments - 7 , 'UTF-8');
                $page->drawText(Mage::helper('sales')->__('Title'), 290, $yShipments - 7, 'UTF-8');
                $page->drawText(Mage::helper('sales')->__('Number'), 410, $yShipments - 7, 'UTF-8');

                $yShipments -= 20;
                $this->_setFontRegular($page, 8);
                foreach ($tracks as $track) {

                    $CarrierCode = $track->getCarrierCode();
                    if ($CarrierCode != 'custom') {
                        $carrier = Mage::getSingleton('shipping/config')->getCarrierInstance($CarrierCode);
                        $carrierTitle = $carrier->getConfigData('title');
                    } else {
                        $carrierTitle = Mage::helper('sales')->__('Custom Value');
                    }

                    //$truncatedCarrierTitle = substr($carrierTitle, 0, 35) . (strlen($carrierTitle) > 35 ? '...' : '');
                    $maxTitleLen = 45;
                    $endOfTitle = strlen($track->getTitle()) > $maxTitleLen ? '...' : '';
                    $truncatedTitle = substr($track->getTitle(), 0, $maxTitleLen) . $endOfTitle;
                    //$page->drawText($truncatedCarrierTitle, 285, $yShipments , 'UTF-8');
                    $page->drawText($truncatedTitle, 292, $yShipments , 'UTF-8');
                    $page->drawText($track->getNumber(), 410, $yShipments , 'UTF-8');
                    $yShipments -= 15;
                }
            } else {
                $yShipments -= $topMargin - 15;
            }
			
            $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 9);
			//$this->insertCollectedAmount($page, $shipmentincremt, 285,$yShipments);
			$yShipments -= 15;
			$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 7);
			//$this->insertDimensionweight($page, $order, 285,$yShipments);
			$yShipments -= 15;
			//$this->insertDimensionDimension($page, $order, 285,$yShipments);
			$yShipments -= 15;
			
            $currentY = min($yPayments, $yShipments);

            // replacement of Shipments-Payments rectangle block
            $page->drawLine(25,  $methodStartY, 25,  $currentY); //left
            $page->drawLine(25,  $currentY,     570, $currentY); //bottom
            $page->drawLine(570, $currentY,     570, $methodStartY); //right

            $this->y = $currentY;
            $this->y -= 30;
			
        }
    }
	public function insertStoreAddressShipment($page,$order){
		$storeAddress =  Mage::getStoreConfig('general/store_information/address');
		$retrnaddressaraay=array();
		$retrnaddressaraay=explode("\n",$storeAddress);
		$num_lines = count($retrnaddressaraay);
		$curr_line = 0;
		$line=810;
		foreach($retrnaddressaraay as $rtnaddress)
		{
		if($curr_line==0)
		{
			$labele='Return Address: ';
			$linew=350;
		}
		else{$labele='';$linew=395;}
		
		if ($curr_line < $num_lines)
		{
		if ($rtnaddress!=='')
		{
		$value = wordwrap($rtnaddress, 30, "\n");
		$page->drawText(strip_tags((str_replace("\n",' ',$labele.$value))), $linew, $line, 'UTF-8');
		$line -=10;
		}
		}
		$curr_line += 1;
		}
		
		
	}
	public function insertCollectedAmount($page,$shipmentincremt,$paymentLeft,$yPayments){
		$total=0;
		
		//print_r($shipment);die;
		//
		//echo $shipmentincremt;
		$shipment1 = Mage::getModel('sales/order_shipment')->loadByIncrementId($shipmentincremt);
		//echo $shipment1->getIncrementId();die;
		
		
			//echo $shipment->getIncrementId().'ddd';
        $shippedItems = $shipment1->getItemsCollection();
		
        foreach ($shippedItems as $item) {
			//echo $item->getPriceInclTax().'dd';
			//echo $item->getPrice();die;
				$OrderItem = Mage::getModel('sales/order_item')->load($item->getOrderItemId());
                $shippedItemIds[] = (($OrderItem->getPriceInclTax()*$item->getQty())-$OrderItem->getDiscountAmount());
        
		
		}//die;
	
		if($shippedItemIds){
			 $page->drawText('Amount To Be Collected: Rs. '.array_sum($shippedItemIds), $paymentLeft,$yPayments, 'CP1252');
		}
	}
	public function insertBarPayment($page,$order,$paymentLeft,$yPayments){
	 $barcodeString = $this->getLastTracking($order);

            $page->setFillColor(new Zend_Pdf_Color_RGB(0, 0, 0));
           // $page->setFont(Zend_Pdf_Font::fontWithPath(dirname(__FILE__)  . '/' . 'Code128bWin.ttf'), 18);
			 $page->drawText($barcodeString, $paymentLeft,$yPayments, 'CP1252');
	}
	public function insertBarcodeString($page,$order,$paymentLeft,$yPayments){
    $awbNumber = $this->getLastTracking($order);
	$barcodeString = $this->convertToBarcodeString($awbNumber);
	//echo $barcodeString;die;
	$page->setFont(Zend_Pdf_Font::fontWithPath(dirname(__FILE__)  . '/' . 'Code128bWin.ttf'), 18);
	$page->drawText($barcodeString,  $paymentLeft-90,$yPayments, 'CP1252');
    $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 7);
    $page->drawText('AWB : '.$awbNumber,  $paymentLeft-90, $yPayments-10,'UTF-8');
	}
	public function getLastTracking($order)
	{
		foreach ($order->getTracksCollection() as $_track){
    		$track[]=$_track->getNumber();
		}
		return end($track);
	}
	public function insertDimensionweight($page,$order,$paymentLeft,$yPayments)
	{
		$weight=$this->getLastTrackingWeight($order);
		$page->drawText('Weight(KGS): '.$weight, $paymentLeft,$yPayments, 'CP1252');
	}
	public function insertDimensionDimension($page,$order,$paymentLeft,$yPayments)
	{
		$dimension=$this->getLastTrackingDimension($order);
		$page->drawText('Dimension(CMS): '.$dimension, $paymentLeft,$yPayments, 'CP1252');
	}
	public function getLastTrackingWeight($order)
	{
		foreach ($order->getTracksCollection() as $_track){
			//print_r($_track);die;
    		$track[]=$_track->getWeight();
		}
		//print_r($track);die;
		return end($track);
	}
	public function getLastTrackingDimension($order)
	{
		foreach ($order->getTracksCollection() as $_track){
    		$track[]=$_track->getDescription();
		}
		return end($track);
	}
	protected function insertOrderInvoice(&$page, $order, $putOrderId = true)
     {
         /* @var $order Mage_Sales_Model_Order */
		 
         $page->setFillColor(new Zend_Pdf_Color_GrayScale(0.5));
 
         $page->drawRectangle(25, 790, 570, 755);
 
         $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));
         $this->_setFontRegular($page, 10);
 
 
         if ($putOrderId) {
             $page->drawText(Mage::helper('sales')->__('Order # ').$order->getRealOrderId(), 35, 770, 'UTF-8');
         }
         //$page->drawText(Mage::helper('sales')->__('Order Date: ') . date( 'D M j Y', strtotime( $order->getCreatedAt() ) ), 35, 760, 'UTF-8');
         $page->drawText(Mage::helper('sales')->__('Order Date: ') . Mage::helper('core')->formatDate($order->getCreatedAtStoreDate(), 'medium', false), 35, 760, 'UTF-8');
 
         $page->setFillColor(new Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
         $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
         $page->setLineWidth(0.5);
         $page->drawRectangle(25, 755, 275, 730);
         $page->drawRectangle(275, 755, 570, 730);
 
         /* Calculate blocks info */
 
         /* Billing Address */
         $billingAddress = $this->_formatAddress($order->getBillingAddress()->format('pdf'));
 
         /* Payment */
         $paymentInfo = Mage::helper('payment')->getInfoBlock($order->getPayment())
             ->setIsSecureMode(true)
             ->toPdf();
         $payment = explode('{{pdf_row_separator}}', $paymentInfo);
         foreach ($payment as $key=>$value){
             if (strip_tags(trim($value))==''){
                 unset($payment[$key]);
             }
         }
         reset($payment);
 
         /* Shipping Address and Method */
         if (!$order->getIsVirtual()) {
             /* Shipping Address */
             $shippingAddress = $this->_formatAddress($order->getShippingAddress()->format('pdf'));
 
             $shippingMethod  = $order->getShippingDescription();
         }
 
         $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
         $this->_setFontRegular($page);
         $page->drawText(Mage::helper('sales')->__('SOLD TO:'), 35, 740 , 'UTF-8');
 
         if (!$order->getIsVirtual()) {
             $page->drawText(Mage::helper('sales')->__('SHIP TO:'), 285, 740 , 'UTF-8');
         }
         else {
             $page->drawText(Mage::helper('sales')->__('Payment Method:'), 285, 740 , 'UTF-8');
         }
 
         if (!$order->getIsVirtual()) {
             $y = 730 - (max(count($billingAddress), count($shippingAddress)) * 10 + 5);
         }
         else {
             $y = 730 - (count($billingAddress) * 10 + 5);
         }
 
         $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));
         $page->drawRectangle(25, 730, 570, $y);
         $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
         $this->_setFontRegular($page);
         $this->y = 720;
 
         foreach ($billingAddress as $value){
             if ($value!=='') {
                 $page->drawText(strip_tags(ltrim($value)), 35, $this->y, 'UTF-8');
                 $this->y -=10;
             }
         }
 
         if (!$order->getIsVirtual()) {
             $this->y = 720;
             foreach ($shippingAddress as $value){
                 if ($value!=='') {
                     $page->drawText(strip_tags(ltrim($value)), 285, $this->y, 'UTF-8');
                     $this->y -=10;
                 }
 
             }
 
             $page->setFillColor(new Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
             $page->setLineWidth(0.5);
             $page->drawRectangle(25, $this->y, 275, $this->y-25);
             $page->drawRectangle(275, $this->y, 570, $this->y-25);
 
             $this->y -=15;
             $this->_setFontBold($page);
             $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
             $page->drawText(Mage::helper('sales')->__('Payment Method'), 35, $this->y, 'UTF-8');
             $page->drawText(Mage::helper('sales')->__('Shipping Method:'), 285, $this->y , 'UTF-8');
 
             $this->y -=10;
             $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));
 
             $this->_setFontRegular($page);
             $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
 
             $paymentLeft = 35;
             $yPayments   = $this->y - 15;
         }
         else {
             $yPayments   = 720;
             $paymentLeft = 285;
         }
 
         foreach ($payment as $value){
             if (trim($value)!=='') {
                 $page->drawText(strip_tags(trim($value)), $paymentLeft, $yPayments, 'UTF-8');
                 $yPayments -=10;
             }
         }
 
         if (!$order->getIsVirtual()) {
             $this->y -=15;
 
             $page->drawText($shippingMethod, 285, $this->y, 'UTF-8');
 
             $yShipments = $this->y;
 
 
             $totalShippingChargesText = "(" . Mage::helper('sales')->__('Total Shipping Charges') . " " . $order->formatPriceTxt($order->getShippingAmount()) . ")";
 
             $page->drawText($totalShippingChargesText, 285, $yShipments-7, 'UTF-8');
             $yShipments -=10;
             $tracks = $order->getTracksCollection();
             if (count($tracks)) {
                 $page->setFillColor(new Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
                 $page->setLineWidth(0.5);
                 $page->drawRectangle(285, $yShipments, 510, $yShipments - 10);
                 $page->drawLine(380, $yShipments, 380, $yShipments - 10);
                 //$page->drawLine(510, $yShipments, 510, $yShipments - 10);
 
                 $this->_setFontRegular($page);
                 $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
                 //$page->drawText(Mage::helper('sales')->__('Carrier'), 290, $yShipments - 7 , 'UTF-8');
                 $page->drawText(Mage::helper('sales')->__('Title'), 290, $yShipments - 7, 'UTF-8');
                 $page->drawText(Mage::helper('sales')->__('Number'), 385, $yShipments - 7, 'UTF-8');
 
                 $yShipments -=17;
                 $this->_setFontRegular($page, 6);
                 foreach ($order->getTracksCollection() as $track) {
 
                     $CarrierCode = $track->getCarrierCode();
                     if ($CarrierCode!='custom')
                     {
                         $carrier = Mage::getSingleton('shipping/config')->getCarrierInstance($CarrierCode);
                         $carrierTitle = $carrier->getConfigData('title');
                     }
                     else
                     {
                         $carrierTitle = Mage::helper('sales')->__('Custom Value');
                     }
 
                     //$truncatedCarrierTitle = substr($carrierTitle, 0, 35) . (strlen($carrierTitle) > 35 ? '...' : '');
                     $truncatedTitle = substr($track->getTitle(), 0, 45) . (strlen($track->getTitle()) > 45 ? '...' : '');
                     //$page->drawText($truncatedCarrierTitle, 285, $yShipments , 'UTF-8');
                     $page->drawText($truncatedTitle, 300, $yShipments , 'UTF-8');
                     $page->drawText($track->getNumber(), 395, $yShipments , 'UTF-8');
                     $yShipments -=7;
                 }
             } else {
                 $yShipments -= 7;
             }
 
           $currentY = min($yPayments, $yShipments);

             // replacement of Shipments-Payments rectangle block
            $page->drawLine(25, $this->y + 15, 25, $currentY);
             $page->drawLine(25, $currentY, 570, $currentY);
             $page->drawLine(570, $currentY, 570, $this->y + 15);

            $this->y = $currentY;
            $this->y -= 15;
         }
   }
	
}