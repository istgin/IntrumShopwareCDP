<?php

/**
 * Shopware 5.1.X Intrum Plugin
 */
require(__DIR__).'/api/intrum.php';
require(__DIR__).'/api/helper.php';
class Shopware_Plugins_Frontend_Intrum_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    public function afterInit()
    {
        $this->registerCustomModels();
        $this->Application()->Loader()->registerNamespace(
            'Intrum', $this->Path() . 'Components/Intrum/'
        );
        $this->Application()->Loader()->registerNamespace('Intrum', $this->Path() . 'Components/Classes/');
    }

    public function onGetIntrumLogControllerBackend()
    {
        $this->Application()->Template()->addTemplateDir(
            $this->Path() . 'Views/'
        );
        return $this->Path() . 'Controllers/Backend/IntrumLog.php';
    }

    public function uninstall()
    {
        $em       = $this->Application()->Models();
        $platform = $em->getConnection()->getDatabasePlatform();
        $platform->registerDoctrineTypeMapping('enum', 'string');
        $tool     = new \Doctrine\ORM\Tools\SchemaTool($em);
        $classes = array($em->getClassMetadata('Shopware\CustomModels\IntrumLog\IntrumLog'));
        $tool->dropSchema($classes);
        return true;

    }

    public function install()
    {

        $em         = $this->Application()->Models();
        $platform   = $em->getConnection()->getDatabasePlatform();
        $platform->registerDoctrineTypeMapping('enum', 'string');
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);


        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_IntrumLog', 'onGetIntrumLogControllerBackend');

        $this->subscribeEvent(
            'Shopware_Modules_Admin_GetPaymentMeans_DataFilter',
            'IntrumCdpStatusCall'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Checkout',
            'IntrumCdpOrderCall'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_PreDispatch_Frontend',
            'GlobalPostAction'
        );

        $this->subscribeEvent(
            'Shopware_Modules_Order_GetOrdernumber_FilterOrdernumber',
            'onSaveOrderMethodChange'
        );

        $this->subscribeEvent(
            'Shopware_Modules_Admin_Login_Start',
            'ClearIntrumSession'
        );
		
		$this->subscribeEvent(
			'Enlight_Controller_Action_PostDispatch',
			'onPostDispatchIntrum'
		);



        $sql     = 'SELECT `name` FROM s_core_paymentmeans WHERE active = 1';
        $payment = Shopware()->Db()->fetchAll($sql, Array(), PDO::FETCH_COLUMN);
        $availablePayments = Array();
        foreach($payment as $p) {
            $availablePayments[] = $p;
        }



        $classes = array(
            $em->getClassMetadata('Shopware\CustomModels\IntrumLog\IntrumLog'),
        );

        try
        {
            $schemaTool->createSchema($classes);
        }
        catch (\Doctrine\ORM\Tools\ToolsException $e)
        {
            // ignore
        }


        $parent = $this->Menu()->findOneBy(array('id' => 65));
        $item   = $this->createMenuItem(array(
            'label'  => 'Intrum',
            'class'  => 'intrumicon',
            'active' => 1,
            'parent' => $parent,
        ));

        $parent = $item;
        $this->createMenuItem(array(
            'label'      => 'Intrum Log',
            'controller' => 'IntrumLog',
            'action'     => 'Index',
            'class'      => 'sprite-cards-stack',
            'active'     => 1,
            'parent'     => $parent,
        ));

        $form = $this->Form();
        $parent = $this->Forms()->findOneBy(array('name' => 'Frontend'));
        $form->setParent($parent);
        $form->setElement('button', 'button1', array(
            'label' => '<b style="color:green;">Intrum CDP Credentials</b>',
            'value' => '',
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('select', 'plugin_mode', array(
            'label' => 'Mode',
            'store' => array(
                array('test', 'Test Mode'),
                array('live', 'Live Mode')
            ),
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('text', 'client_id', array(
            'label' => 'Client ID',
            'value' => null,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('text', 'user_id', array(
            'label' => 'User ID',
            'value' => null,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('text', 'password', array(
            'label' => 'Password',
            'value' => null,
			'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('text', 'technical_contact', array(
            'label' => 'Technical Contact (E-mail)',
            'value' => null,
			'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('text', 'minimal_amount', array(
            'label' => 'Mininmal amount for credit check',
            'value' => null
        ));
        $form->setElement('text', 'minimal_amount', array(
            'label' => 'Mininmal amount for credit check',
            'value' => null,
			'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('select', 'tmx_enable', array(
            'label' => 'Enable ThreatMetrix security check',
            'store' => array(
                array('enable', 'Enable'),
                array('disable', 'Disable')
            ),
			'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('text', 'tmxorgid', array(
            'label' => 'ThreatMetrix Org Id',
            'value' => null,
			'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('text', 'defaultpayment', array(
            'label' => 'Default payment method',
            'value' => null,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));

        $form->setElement('button', 'button2', array(
            'label' => '<b style="color:green;">Denied payment methods for statues</b><br>Active method names: '.implode($availablePayments, ", "),
            'value' => '',
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));

        $form->setElement('textarea', 'status1', array(
            'label' => 'There are serious negative indicators (status 1)',
            'value' => null,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('textarea', 'status2', array(
            'label' => 'All payment methods (status 2)',
            'value' => null,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('textarea', 'status3', array(
            'label' => 'Manual post-processing (currently not yet in use) (status 3)',
            'value' => null,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('textarea', 'status4', array(
            'label' => 'Postal address is incorrect (status 4)',
            'value' => null,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('textarea', 'status5', array(
            'label' => 'Enquiry exceeds the credit limit (the credit limit is specified in the cooperation agreement) (status 5)',
            'value' => null,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('textarea', 'status6', array(
            'label' => 'Customer specifications not met (optional) (status 6)',
            'value' => null,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));

        $form->setElement('textarea', 'status7', array(
            'label' => 'Enquiry exceeds the net credit limit (enquiry amount plus open items exceeds credit limit) (status 7)',
            'value' => null,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));

        $form->setElement('textarea', 'status8', array(
            'label' => 'Person queried is not of creditworthy age (status 8))',
            'value' => null,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));

        $form->setElement('textarea', 'status9', array(
            'label' => 'Delivery address does not match invoice address (for payment guarantee only) (status 9)',
            'value' => null,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));

        $form->setElement('textarea', 'status10', array(
            'label' => 'Household cannot be identified at this address (status 10))',
            'value' => null,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));

        $form->setElement('textarea', 'status11', array(
            'label' => 'Country is not supported (status 11)',
            'value' => null,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));

        $form->setElement('textarea', 'status12', array(
            'label' => 'Party queried is not a natural person (status 12)',
            'value' => null,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));

        $form->setElement('textarea', 'status13', array(
            'label' => 'System is in maintenance mode (status 13)',
            'value' => null,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));

        $form->setElement('textarea', 'status14', array(
            'label' => 'Address with high fraud risk (status 14)',
            'value' => null,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));

        $form->setElement('textarea', 'status15', array(
            'label' => 'Allowance is too low (status 15)',
            'value' => null,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));

        $form->setElement('textarea', 'status0', array(
            'label' => 'Fail to connect or Internal error (status Error)',
            'value' => null,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));

        $form->setElement('button', 'button3', array(
            'label' => '<b style="color:green;">Localization</b>',
            'value' => '',
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        for ($i = 1; $i < 16; $i++) {
            $form->setElement('text', 'decline_message_'.$i, array(
                'label' => 'Payment decline message (status '.$i.')',
                'value' => 'You cannot pay with selected payment method',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            ));
        }
        $form->setElement('text', 'decline_message_0', array(
            'label' => 'Payment decline message (status ERROR)',
            'value' => 'You cannot pay with selected payment method',
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));


        $sql = "ALTER TABLE `s_plugin_intrum_log`
CHANGE COLUMN `xml_request` `xml_request` TEXT CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci' NOT NULL ,
CHANGE COLUMN `xml_responce` `xml_responce` TEXT CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci' NOT NULL";
         Shopware()->Db()->exec($sql);
        return true;
    }
	
	function onPostDispatchIntrum(Enlight_Event_EventArgs $args) {
        if (!empty($_SESSION["intrum"]["message"])) {
            if ($args->getSubject()->View()->hasTemplate()){
               $args->getSubject()->View()->assign("sBasketInfo", $_SESSION["intrum"]["message"]);
            }
            $_SESSION["intrum"]["message"] = null;
        }

        /* @var $request Enlight_Controller_Request_RequestHttp */
        $request = $args->getSubject()->Request();
        $response = $args->getSubject()->Response();
        /* @var $view Enlight_View_Default */
        $view = $args->getSubject()->View();


        if (!$request->isDispatched()
            || $response->isException()
            || $request->getModuleName() != 'frontend'
            || $request->isXmlHttpRequest()
            || !$view->hasTemplate()
        ) {
            return;
        }

        if (!strstr($args->getRequest()->getActionName(), "ajax")) {
            $view->messageIntrum = "";
            if (!empty($_SESSION["intrum"]["paymentMessage"])) {
                $view->messageIntrum = $_SESSION["intrum"]["paymentMessage"];
                unset($_SESSION["intrum"]["paymentMessage"]);
            }
            $this->Application()->Template()->addTemplateDir(
                $this->Path() . 'Views/'
            );
            $view->extendsTemplate('frontend/intrum_message.tpl');
        }


        //if ($request->getControllerName() == 'checkout'/* && $request->getActionName() == 'cart'*/ && !isset($_SESSION["intrum_tmx"])) {
            $config = $this->Config();
            $tmx_enable = $config->get("tmx_enable");
            $tmxorgid = $config->get("tmxorgid");
            if (isset($tmx_enable) && $tmx_enable == 'enable' && isset($tmxorgid) && $tmxorgid != '' && !isset($_SESSION["intrum_tmx"])) {
				$_SESSION["intrum_tmx"] = session_id();
                $view->tmx_enable = $tmx_enable;
                $view->tmx_orgid = $tmxorgid;
                $view->tmx_session = $_SESSION["intrum_tmx"];
                $this->Application()->Template()->addTemplateDir(
                    $this->Path() . 'Views/'
                );
                $view->extendsTemplate('frontend/intrum_tmx.tpl');
            }

        //}
	}

    function ClearIntrumSession(Enlight_Event_EventArgs $args) {
        $_SESSION["intrum"] = null;
    }

    /**
     * @return array
     */
    public function getInfo()
    {
        return array(
            'label' => 'Intrum CDP',
            'supplier' => 'Intrum CDP',
            'description' => 'Intrum Credit design check',
            'link' => 'http://www.intrum.com',
            'author' =>  'Intrum.com',
            'copyright' =>  'Intrum.com 2015',
            'version' =>  '1.4.2'
        );
    }

    public function getUser()
    {
		try {
			$userData = Shopware()->Modules()->Admin()->sGetUserData();
			if (!empty($userData)) {
				return $userData;
			} else {
				return null;
			}
		} catch(\Exception $e) {
            return null;		
		}
       /* if (!empty(Shopware()->Session()->sOrderVariables['sUserData'])) {
            return Shopware()->Session()->sOrderVariables['sUserData'];
        } else {
            return null;
        }
       */
    }

    public function SaveLog(IntrumRequest $request, $xml_request, $xml_response, $status, $type) {

        $sql     = '
            INSERT INTO s_plugin_intrum_log (requestid, requesttype, firstname, lastname, ip, status, datecolumn, xml_request, xml_responce)
                    VALUES (?,?,?,?,?,?,?,?,?)
        ';
         Shopware()->Db()->query($sql, Array(
             $request->getRequestId(),
             $type,
             $request->getFirstName(),
             $request->getLastName(),
             $_SERVER['REMOTE_ADDR'],
             (($status != 0) ? $status : 'Error'),
             date('Y-m-d\TH:i:sP'),
             $xml_request,
             $xml_response
         ));
    }

    /**
     * Event listener method
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function IntrumCdpStatusCall(Enlight_Event_EventArgs $args)
    {
        /* @var $config Enlight_Config */
        $config = $this->Config();
		$mode = $config->get("plugin_mode");
        $minAmount = intval($config->get("minimal_amount"));
        $user = $this->getUser();		
        $methods = $args->getReturn();
        if(empty($user)) {
            return array(
                'LOCALECODE' => Shopware()->Shop()->getLocale()->getLocale(),
            );
        }
        if (empty($user) || empty($user['billingaddress']) || empty($user['shippingaddress'])) {
            return $methods;
        }
        $basket = Shopware()->Modules()->Basket()->sGetAmount();
        if ($basket == null || $minAmount > $basket['totalAmount']) {
            return $methods;
        }
        $billing = $user['billingaddress'];
        $shipping = $user['shippingaddress'];
        if (isset($_SESSION["intrum"]["status"])) {
            $sesStatus = (string)$_SESSION["intrum"]["status"];
        }
        $status = 0;
        if (!isset($sesStatus)) {
            $request = CreateShopWareShopRequest($user, $billing, $shipping, $basket['totalAmount'], $config);
            $xml = $request->createRequest();
            $intrumCommunicator = new IntrumCommunicator();
            if (isset($mode) && $mode == 'live') {
                $intrumCommunicator->setServer('live');
            } else {
                $intrumCommunicator->setServer('test');
            }
            $response = $intrumCommunicator->sendRequest($xml);
            if ($response) {
                $intrumResponse = new IntrumResponse();
                $intrumResponse->setRawResponse($response);
                $intrumResponse->processResponse();
                $status = (int)$intrumResponse->getCustomerRequestStatus();
                $statusLog = "Intrum status";
                if (!empty($_SESSION["intrum"]["mustupdate"])) {
                    $statusLog .= " ".$_SESSION["intrum"]["mustupdate"];
                } else {
                    $statusLog .= " GetPaymentMeans";
                }
                $this->saveLog($request, $xml, $response, $status, $statusLog);
                if (intval($status) > 15) {
                    $status = 0;
                }
            }
            $_SESSION["intrum"]["status"] = $status;
        } else {
            $status = $sesStatus;
        }
        if ($status > 15) {
            $status = '0';
        }
        $configString = $config->get("status".(String)$status);
        $DeniedMethods = explode(",", $configString);
        foreach($DeniedMethods as &$val) {
            $val = trim($val);
        }
        $defaultPaymentId = 0;
        $sql = 'SELECT `id` FROM s_core_paymentmeans WHERE name = ?';
        $id = Shopware()->Db()->fetchOne($sql, array($config->get("defaultpayment")));
        if ($id != null) {
            $defaultPaymentId = $id;
        }

        $return = Array();
        foreach($methods as $m) {
            if (in_array($m["name"], $DeniedMethods)) {
                if (isset($_SESSION["Shopware"]["sOrderVariables"]["sUserData"]["additional"]["user"]["paymentID"]) && isset($user["additional"]["user"]["customerId"]) && $m["id"] == $_SESSION["Shopware"]["sOrderVariables"]["sUserData"]["additional"]["user"]["paymentID"]) {
                    $_SESSION["intrum"]["paymentMessage"] = $config->get("decline_message_" . (String)$status);
                    $_SESSION["Shopware"]["sOrderVariables"]["sUserData"]["additional"]["user"]["paymentID"] = $defaultPaymentId;
                    $sql = "UPDATE s_user SET paymentID = ".intval($defaultPaymentId)." WHERE id = ".intval($user["additional"]["user"]["customerId"]);
                    Shopware()->Db()->exec($sql);
                }
                continue;
            }
            $return[] = $m;
        }
        return $return;
    }


    /**
     * Event listener method
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function OnsiteCdpStatusCall(Enlight_Event_EventArgs $args)
    {
        /* @var $config Enlight_Config */
        $config = $this->Config();
        $mode = $config->get("plugin_mode");
        $minAmount = intval($config->get("minimal_amount"));
        $user = $this->getUser();
        if(empty($user)) {
            return;
        }
        if (empty($user) || empty($user['billingaddress']) || empty($user['shippingaddress'])) {
            return;
        }
        $basket = Shopware()->Modules()->Basket()->sGetAmount();
        if ($basket == null || $minAmount > $basket['totalAmount']) {
            return;
        }
        $billing = $user['billingaddress'];
        $shipping = $user['shippingaddress'];
        if (isset($_SESSION["intrum"]["status"])) {
            $sesStatus = (string)$_SESSION["intrum"]["status"];
        }
        $status = 0;
        if (!isset($sesStatus)) {
            $request = CreateShopWareShopRequest($user, $billing, $shipping, $basket['totalAmount'], $config);
            $xml = $request->createRequest();
            $intrumCommunicator = new IntrumCommunicator();
            if (isset($mode) && $mode == 'live') {
                $intrumCommunicator->setServer('live');
            } else {
                $intrumCommunicator->setServer('test');
            }
            $response = $intrumCommunicator->sendRequest($xml);
            if ($response) {
                $intrumResponse = new IntrumResponse();
                $intrumResponse->setRawResponse($response);
                $intrumResponse->processResponse();
                $status = (int)$intrumResponse->getCustomerRequestStatus();
                $statusLog = "Intrum status";
                if (!empty($_SESSION["intrum"]["mustupdate"])) {
                    $statusLog .= " ".$_SESSION["intrum"]["mustupdate"];
                } else {
                    $statusLog .= " GetPaymentMeans";
                }
                $this->saveLog($request, $xml, $response, $status, $statusLog);
                if (intval($status) > 15) {
                    $status = 0;
                }
            }
            $_SESSION["intrum"]["status"] = $status;
        } else {
            $status = $sesStatus;
        }
        if ($status > 15) {
            $status = '0';
        }
        $configString = $config->get("status".(String)$status);
        $DeniedMethods = explode(",", $configString);
        foreach($DeniedMethods as &$val) {
            $val = trim($val);
        }
        $sql = "SELECT paymentID FROM s_user WHERE id = " . intval($user["additional"]["user"]["customerId"]);
        $paymentMethodId = Shopware()->Db()->fetchOne($sql);

        $defaultPaymentId = 0;
        $sql = 'SELECT `id` FROM s_core_paymentmeans WHERE name = ?';
        $id = Shopware()->Db()->fetchOne($sql, array($config->get("defaultpayment")));
        if ($id != null) {
            $defaultPaymentId = $id;
        }
        if (!empty($paymentMethodId) && !empty($user["additional"]["user"]["customerId"])) {
            $method = Shopware()->Modules()->Admin()->sGetPaymentMeanById($paymentMethodId);
            if (in_array($method["name"], $DeniedMethods)) {
                $_SESSION["intrum"]["paymentMessage"] = $config->get("decline_message_" . (String)$status);
                $_SESSION["Shopware"]["sPaymentID"] = $defaultPaymentId;
                $_SESSION["Shopware"]["sOrderVariables"]["sUserData"]["additional"]["user"]["paymentID"] = $defaultPaymentId;
                $sql = "UPDATE s_user SET paymentID = ".intval($defaultPaymentId)." WHERE id = " . intval($user["additional"]["user"]["customerId"]);
                Shopware()->Db()->exec($sql);
            }
        }
    }



    protected function getTrustedShopBasketConfig($checkoutController)
    {
        $amount = Shopware()->Modules()->Basket()->sGetAmount();
        $shippingCost = $checkoutController->getShippingCosts();
        $amount = $amount["totalAmount"] + $shippingCost["value"];
        if($checkoutController->View()->sAmountWithTax){
            $amount = $checkoutController->View()->sAmountWithTax;
        }
        return $amount;
    }

    public function onSaveOrderMethodChange(Enlight_Event_EventArgs $args) {

       // if ($args->getRequest()->getControllerName() == 'checkout' && $args->getRequest()->getActionName() == 'finish') {
            /* @var $config Enlight_Config */
            $config = $this->Config();
            $mode = $config->get("plugin_mode");
            $minAmount = intval($config->get("minimal_amount"));
            $user = $this->getUser();
            {
                $subject = $args->getSubject();
                $basketAmount = $subject->sAmount;
                if ($basketAmount == null || $minAmount > $basketAmount) {
                    return null;
                }
                $billing = $user['billingaddress'];
                $shipping = $user['shippingaddress'];
                $request = CreateShopWareShopRequest($user, $billing, $shipping, $basketAmount, $config);
                $xml = $request->createRequest();
                $intrumCommunicator = new IntrumCommunicator();
                if (isset($mode) && $mode == 'live') {
                    $intrumCommunicator->setServer('live');
                } else {
                    $intrumCommunicator->setServer('test');
                }
                $response = $intrumCommunicator->sendRequest($xml);
                $status = 0;
                if ($response) {
                    $intrumResponse = new IntrumResponse();
                    $intrumResponse->setRawResponse($response);
                    $intrumResponse->processResponse();
                    $status = (int)$intrumResponse->getCustomerRequestStatus();
                    $this->saveLog($request, $xml, $response, $status, "Intrum status final check");
                    if (intval($status) > 15) {
                        $status = 0;
                    }
                }
                $_SESSION["intrum"]["status"] = $status;
            }
            $defaultPaymentId = 0;
            $sql = 'SELECT `id` FROM s_core_paymentmeans WHERE name = ?';
            $id = Shopware()->Db()->fetchOne($sql, array($config->get("defaultpayment")));
            if ($id != null) {
                $defaultPaymentId = $id;
            }
            if (isset($_SESSION["intrum"]["status"])) {
                $status = intval($_SESSION["intrum"]["status"]);
                $configString = $config->get("status" . (String)$status);
                $DeniedMethods = explode(",", $configString);
                $sql = 'SELECT `name`, `description` FROM s_core_paymentmeans WHERE id = ' . intval($_SESSION["Shopware"]["sOrderVariables"]["sUserData"]["additional"]["user"]["paymentID"]);
                $name = Shopware()->Db()->fetchRow($sql);
                if (in_array($name["name"], $DeniedMethods)) {
                    $_SESSION["Shopware"]["sOrderVariables"]["sUserData"]["additional"]["user"]["paymentID"] = $defaultPaymentId;
                    $sql = "UPDATE s_user SET paymentID = ".intval($defaultPaymentId)." WHERE id = " . intval($user["additional"]["user"]["customerId"]);
                    Shopware()->Db()->exec($sql);
                    $_SESSION["intrum"]["message"] = $config->get("decline_message_" . (String)$status);
                    header("Location:" . Shopware()->Router()->assemble(array('module' => 'frontend', 'controller' => 'checkout', 'action' => 'cart')));
                    exit();
                }
            }
       // }
    }

    public function GlobalPostAction(Enlight_Event_EventArgs $args)
    {
        if (!empty($_SESSION["intrum"]["mustupdate"])) {

            unset($_SESSION["intrum"]["status"]);
            $this->OnsiteCdpStatusCall($args);
            unset($_SESSION["intrum"]["mustupdate"]);
        }

        if ((
                $args->getRequest()->getControllerName() == 'checkout' &&

                ($args->getRequest()->getActionName() == 'addAccessories'
                    || $args->getRequest()->getActionName() == 'changeQuantity'
                    || $args->getRequest()->getActionName() == 'addVoucher'
                    || $args->getRequest()->getActionName() == 'addPremium'
                    || $args->getRequest()->getActionName() == 'addArticle'
                    || $args->getRequest()->getActionName() == 'deleteArticle'
                    || $args->getRequest()->getActionName() == 'saveShipping'
                    || $args->getRequest()->getActionName() == 'ajaxAddArticle'
                    || $args->getRequest()->getActionName() == 'ajaxAddArticleCart'
                    || $args->getRequest()->getActionName() == 'ajaxDeleteArticle'
                    || $args->getRequest()->getActionName() == 'ajaxDeleteArticleCart'
                    || $args->getRequest()->getActionName() == 'saveShippingPayment'

                )
            )
            ||
            (
                $args->getRequest()->getControllerName() == 'account' &&
                ($args->getRequest()->getActionName() == 'saveBilling'
                    || $args->getRequest()->getActionName() == 'saveShipping'
                    || $args->getRequest()->getActionName() == 'savePayment'
                )
            )
        ) {
            $_SESSION["intrum"]["mustupdate"] = $args->getRequest()->getActionName();
        }
    }

    public function IntrumCdpOrderCall(Enlight_Event_EventArgs $args)
    {
        if ($args->getRequest()->getControllerName() == 'checkout' &&
            ($args->getRequest()->getActionName() == 'finish'
            )
            ) {
            /* @var $config Enlight_Config */
            $config = $this->Config();
            $mode = $config->get("plugin_mode");
            $minAmount = intval($config->get("minimal_amount"));
            $orderNumber = $args->getReturn();
            $user = $this->getUser();
            $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')
                ->findOneBy(array('number' => Shopware()->Session()->sOrderVariables['sOrderNumber']));
            if (empty($user) || empty($user['billingaddress']) || empty($user['shippingaddress']) || empty($order)) {
                return null;
            }
            $billing = $user['billingaddress'];
            $shipping = $user['shippingaddress'];
            if ($minAmount > $order->getInvoiceAmount()) {
                return null;
            }
			if (isset($_SESSION["existorder"][$order->getNumber()])) {
                return null;				
			}
            $request = CreateShopWareOrderRequest($user, $billing, $shipping, $order, $config);

            $xml = $request->createRequest();
            $intrumCommunicator = new IntrumCommunicator();
            if (isset($mode) && $mode == 'live') {
                $intrumCommunicator->setServer('live');
            } else {
                $intrumCommunicator->setServer('test');
            }
            $response = $intrumCommunicator->sendRequest($xml);

            if ($response) {
                $intrumResponse = new IntrumResponse();
                $intrumResponse->setRawResponse($response);
                $intrumResponse->processResponse();
                $status = (int)$intrumResponse->getCustomerRequestStatus();
                if (intval($status) > 15 || intval($status) < 0) {
                    $status = 0;
                }
                $this->saveLog($request, $xml, $response, $status, "Order completed");
            }
			$_SESSION["existorder"][$order->getNumber()] = true;
        }
    }
}
