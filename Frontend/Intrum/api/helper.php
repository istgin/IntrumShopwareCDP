<?php

function getClientIp() {
    $ipaddress = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    } else if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else if(!empty($_SERVER['HTTP_X_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    } else if(!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } else if(!empty($_SERVER['HTTP_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    } else if(!empty($_SERVER['REMOTE_ADDR'])) {
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    } else {
        $ipaddress = 'UNKNOWN';
    }
    $ipd = explode(",", $ipaddress);
    return trim(end($ipd));
}

function mapPaymentMethodToSpecs($ShopwarePaymentName) {

    $method = strtolower(str_replace(" ", "", $ShopwarePaymentName));
    $IntrumMapping = array(
        'cashondelivery'	=> 'CASH-ON-DELIVERY',
        'banktransfer'		=> 'PRE-PAY',
        'ccsave'			=> 'CREDIT-CARD',
        'paypal'			=> 'E-PAYMENT',
        'bankwire'			=> 'INVOICE',
        'bill'			    => 'INVOICE',
        'invoice'			=> 'INVOICE',
        'invoicepayment'	=> 'INVOICE',
        'visa'	            => 'CREDIT-CARD',
        'maestro'	        => 'CREDIT-CARD',
        'mastercard'	    => 'CREDIT-CARD',
    );

    if(strpos($method, 'paypal')!==false){
        if(array_key_exists('paypal', $IntrumMapping)){
            return $IntrumMapping['paypal'];
        }
    }
    if(strpos($method, 'invoice')!==false){
        return $IntrumMapping['invoice'];
    }
    if(strpos($method, 'maestro')!==false){
        return $IntrumMapping['maestro'];
    }
    if(strpos($method, 'mastercard')!==false){
        return $IntrumMapping['mastercard'];
    }
    if(strpos($method, 'visa')!==false){
        return $IntrumMapping['visa'];
    }
    if(array_key_exists($method, $IntrumMapping)){
        return $IntrumMapping[$method];
    }
    return $method;
}


function CreateShopWareShopRequest($user, $billing, $shipping, $totalAmount, Enlight_Config $config) {

    $sql     = 'SELECT `countryiso` FROM s_core_countries WHERE id = ' . intval($billing["countryID"]);
    $countryBilling = Shopware()->Db()->fetchOne($sql);
    $sql     = 'SELECT `countryiso` FROM s_core_countries WHERE id = ' . intval($shipping["countryID"]);
    $countryShipping = Shopware()->Db()->fetchOne($sql);
    $request = new IntrumRequest();
    $request->setClientId($config->get("client_id"));
    $request->setUserID($config->get("user_id"));
    $request->setPassword($config->get("password"));
    $request->setVersion("1.00");
    $request->setRequestEmail($config->get("technical_contact"));
	
	
    $sql     = 'SELECT `language` FROM s_core_locales WHERE id = ' . intval($user["additional"]["user"]["language"]);
    $langName = Shopware()->Db()->fetchOne($sql);	
	if (isset($langName) && $langName == 'English') {
		$request->setLanguage('en');
	} else if (isset($langName) && $langName == 'German') {
		$request->setLanguage('de');	
	} else {
		$request->setLanguage('de');	
	}
    $request->setRequestId(uniqid($billing["id"]."_"));
    $reference = $billing["id"];
    if (empty($reference)) {
        $request->setCustomerReference("guest_".$billing["id"]);
    } else {
        $request->setCustomerReference($billing["id"]);
    }
    $request->setFirstName((String)$billing['firstname']);
    $request->setLastName((String)$billing['lastname']);
    $request->setFirstLine(trim((String)$billing['street'].' '.$billing['streetnumber']));
    $request->setCountryCode(strtoupper((String)$countryBilling));
    $request->setPostCode((String)$billing['zipcode']);
    $request->setTown((String)$billing['city']);
    $request->setFax((String)$billing['fax']);
	
	$request->setGender(0);
	if (!empty($billing['salutation'])) {
		if (strtolower($billing['salutation']) == 'ms') {
			$request->setGender(2);	
		} else if (strtolower($billing['salutation']) == 'mr') {
			$request->setGender(1);	
		}	
	}

	if (!empty($user["additional"]["user"]['birthday']) && substr($user["additional"]["user"]['birthday'], 0, 4) != '0000') {
		$timestamp = strtotime($user["additional"]["user"]['birthday']);
		if ($timestamp !== false) {
			$request->setDateOfBirth(date('Y-m-d', $timestamp));	
		}		
	}

    $request->setTelephonePrivate((String)$billing['phone']);
    $request->setEmail((String)$user["additional"]["user"]["email"]);

    $extraInfo["Name"] = 'ORDERCLOSED';
    $extraInfo["Value"] = 'NO';
    $request->setExtraInfo($extraInfo);

    $extraInfo["Name"] = 'ORDERAMOUNT';
    $extraInfo["Value"] = $totalAmount;
    $request->setExtraInfo($extraInfo);

    $extraInfo["Name"] = 'ORDERCURRENCY';
    $extraInfo["Value"] = Shopware()->Config()->currency;
    $request->setExtraInfo($extraInfo);

    $extraInfo["Name"] = 'IP';
    $extraInfo["Value"] = getClientIp();
    $request->setExtraInfo($extraInfo);

    $tmx_enable = $config->get("tmx_enable");
    $tmxorgid = $config->get("tmxorgid");
    if (isset($tmx_enable) && $tmx_enable == 'enable' && isset($tmxorgid) && $tmxorgid != '' && !empty($_SESSION["intrum_tmx"])) {
        $extraInfo["Name"] = 'DEVICE_FINGERPRINT_ID';
        $extraInfo["Value"] = $_SESSION["intrum_tmx"];
        $request->setExtraInfo($extraInfo);
    }

    /* shipping information */
    $extraInfo["Name"] = 'DELIVERY_FIRSTNAME';
    $extraInfo["Value"] = $shipping['firstname'];
    $request->setExtraInfo($extraInfo);

    $extraInfo["Name"] = 'DELIVERY_LASTNAME';
    $extraInfo["Value"] = $shipping['lastname'];
    $request->setExtraInfo($extraInfo);

    $extraInfo["Name"] = 'DELIVERY_FIRSTLINE';
    $extraInfo["Value"] = trim($shipping['street'].' '.$shipping['streetnumber']);
    $request->setExtraInfo($extraInfo);

    $extraInfo["Name"] = 'DELIVERY_HOUSENUMBER';
    $extraInfo["Value"] = '';
    $request->setExtraInfo($extraInfo);

    $extraInfo["Name"] = 'DELIVERY_COUNTRYCODE';
    $extraInfo["Value"] = $countryShipping;
    $request->setExtraInfo($extraInfo);

    $extraInfo["Name"] = 'DELIVERY_POSTCODE';
    $extraInfo["Value"] = $shipping['zipcode'];
    $request->setExtraInfo($extraInfo);

    $extraInfo["Name"] = 'DELIVERY_TOWN';
    $extraInfo["Value"] = $shipping['city'];
    $request->setExtraInfo($extraInfo);

    $extraInfo["Name"] = 'CONNECTIVTY_MODULE';
    $extraInfo["Value"] = 'Intrum ShopWare 5.2.X module 1.4.2';
    $request->setExtraInfo($extraInfo);	

    return $request;

}


function CreateShopWareOrderRequest($user, $billing, $shipping, \Shopware\Models\Order\Order $order, Enlight_Config $config) {

    $sql     = 'SELECT `countryiso` FROM s_core_countries WHERE id = ' . intval($billing["countryID"]);
    $countryBilling = Shopware()->Db()->fetchOne($sql);
    $sql     = 'SELECT `countryiso` FROM s_core_countries WHERE id = ' . intval($shipping["countryID"]);
    $countryShipping = Shopware()->Db()->fetchOne($sql);

    $request = new IntrumRequest();
    $request->setClientId($config->get("client_id"));
    $request->setUserID($config->get("user_id"));
    $request->setPassword($config->get("password"));
    $request->setVersion("1.00");
    $request->setRequestEmail($config->get("technical_contact"));
	
	$sql     = 'SELECT `language` FROM s_core_locales WHERE id = ' . intval($order->getLanguageIso());
    $langName = Shopware()->Db()->fetchOne($sql);	
	if (isset($langName) && $langName == 'English') {
		$request->setLanguage('en');
	} else if (isset($langName) && $langName == 'German') {
		$request->setLanguage('de');	
	} else {
		$request->setLanguage('de');	
	}

    $request->setRequestId(uniqid($billing["id"]."_"));
    $reference = $billing["id"];
    if (empty($reference)) {
        $request->setCustomerReference("guest_".$billing["id"]);
    } else {
        $request->setCustomerReference($billing["id"]);
    }
    
    $request->setFirstName((String)$billing['firstname']);
    $request->setLastName((String)$billing['lastname']);
    $request->setFirstLine(trim((String)$billing['street'].' '.$billing['streetnumber']));
    $request->setCountryCode(strtoupper((String)$countryBilling));
    $request->setPostCode((String)$billing['zipcode']);
    $request->setTown((String)$billing['city']);
    $request->setFax((String)$billing['fax']);
	
	$request->setGender(0);
	if (!empty($billing['salutation'])) {
		if (strtolower($billing['salutation']) == 'ms') {
			$request->setGender(2);	
		} else if (strtolower($billing['salutation']) == 'mr') {
			$request->setGender(1);	
		}	
	}
	
	if (!empty($user["additional"]["user"]['birthday']) && substr($user["additional"]["user"]['birthday'], 0, 4) != '0000') {
		$timestamp = strtotime($user["additional"]["user"]['birthday']);
		if ($timestamp !== false) {
			$request->setDateOfBirth(date('Y-m-d', $timestamp));	
		}
	}

    $request->setTelephonePrivate((String)$billing['phone']);
    $request->setEmail((String)$user["additional"]["user"]["email"]);

    $extraInfo["Name"] = 'ORDERCLOSED';
    $extraInfo["Value"] = 'YES';
    $request->setExtraInfo($extraInfo);

    $extraInfo["Name"] = 'ORDERAMOUNT';
    $extraInfo["Value"] = $order->getInvoiceAmount();
    $request->setExtraInfo($extraInfo);

    $extraInfo["Name"] = 'ORDERCURRENCY';
    $extraInfo["Value"] = $order->getCurrency();
    $request->setExtraInfo($extraInfo);

    $extraInfo["Name"] = 'IP';
    $extraInfo["Value"] = getClientIp();
    $request->setExtraInfo($extraInfo);

    $tmx_enable = $config->get("tmx_enable");
    $tmxorgid = $config->get("tmxorgid");
    if (isset($tmx_enable) && $tmx_enable == 'enable' && isset($tmxorgid) && $tmxorgid != '' && !empty($_SESSION["intrum_tmx"])) {
        $extraInfo["Name"] = 'DEVICE_FINGERPRINT_ID';
        $extraInfo["Value"] = $_SESSION["intrum_tmx"];
        $request->setExtraInfo($extraInfo);
    }

    /* shipping information */
    $extraInfo["Name"] = 'DELIVERY_FIRSTNAME';
    $extraInfo["Value"] = $shipping['firstname'];
    $request->setExtraInfo($extraInfo);

    $extraInfo["Name"] = 'DELIVERY_LASTNAME';
    $extraInfo["Value"] = $shipping['lastname'];
    $request->setExtraInfo($extraInfo);

    $extraInfo["Name"] = 'DELIVERY_FIRSTLINE';
    $extraInfo["Value"] = trim($shipping['street'].' '.$shipping['streetnumber']);
    $request->setExtraInfo($extraInfo);

    $extraInfo["Name"] = 'DELIVERY_HOUSENUMBER';
    $extraInfo["Value"] = '';
    $request->setExtraInfo($extraInfo);

    $extraInfo["Name"] = 'DELIVERY_COUNTRYCODE';
    $extraInfo["Value"] = $countryShipping;
    $request->setExtraInfo($extraInfo);

    $extraInfo["Name"] = 'DELIVERY_POSTCODE';
    $extraInfo["Value"] = $shipping['zipcode'];
    $request->setExtraInfo($extraInfo);

    $extraInfo["Name"] = 'DELIVERY_TOWN';
    $extraInfo["Value"] = $shipping['city'];
    $request->setExtraInfo($extraInfo);

    $extraInfo["Name"] = 'ORDERID';
    $extraInfo["Value"] = $order->getNumber();
    $request->setExtraInfo($extraInfo);

    $extraInfo["Name"] = 'PAYMENTMETHOD';
    $extraInfo["Value"] = mapPaymentMethodToSpecs($order->getPayment()->getName());
    $request->setExtraInfo($extraInfo);

    $extraInfo["Name"] = 'CONNECTIVTY_MODULE';
    $extraInfo["Value"] = 'Intrum ShopWare 5.2.X module 1.4.2';
    $request->setExtraInfo($extraInfo);	

    return $request;

}