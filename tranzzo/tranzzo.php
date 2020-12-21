<?php
/**
 * @name tranzzo
 * @description Модуль оплаты Tranzzo для CMS Prestashop 1.7.0.x
 * @author tranzzo.com
 * @email insatiablemen@gmail.com
 * @last_update 16.07.2020
 * @version 1.0
 */

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_'))
    exit;

class tranzzo extends PaymentModule
{
    const POS_ID = '';
    const API_KEY = '';
	const API_SECRET = '';
	const ENDPOINTS_KEY = '';
	const TYPE_PAYMENT = '';

    public function __construct()
    {
        $this->name = 'tranzzo';
        $this->tab = 'payments_gateways';
        $this->version = '1.0';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->currencies = true;
        $this->currencies_mode = 'radio';

        parent::__construct();

        $this->author = 'TRANZZO';
        $this->page = basename(__FILE__, '.php');
        $this->displayName = $this->l('tranzzo 1.0');
        $this->description = $this->getTranslator()->trans('Does this and that', array(), 'Modules.tranzzo.Admin');
        $this->description = $this->l('Accepting payments by credit card quickly and safely with tranzzo');
        $this->confirmUninstall = $this->l('Are you sure you want to delete all the settings?');

    }

    public function install()
    {
        //При установке будет создан новый статус заказа для pending
        $trStatePending = new OrderState();
        foreach (Language::getLanguages() AS $language)
        {
            $trStatePending->name[$language['id_lang']] = 'Pending payment of Tranzzo';
        }
        $trStatePending ->send_mail = 0;
        $trStatePending ->template = "tranzzo";
        $trStatePending ->invoice = 1;
        $trStatePending ->color = "#007cf9";
        $trStatePending ->unremovable = false;
        $trStatePending ->logable = 0;
        $trStatePending ->add();

        //При установке будет создан новый статус заказа для оплаты после pending
        $trStatePaid = new OrderState();
        foreach (Language::getLanguages() AS $language)
        {
            $trStatePaid->name[$language['id_lang']] = 'Paid via Tranzzo';
        }
        $trStatePaid ->send_mail = 1;
        $trStatePaid ->template = "tranzzo";
        $trStatePaid ->invoice = 1;
        $trStatePaid ->color = "#27ae60";
        $trStatePaid ->unremovable = false;
        $trStatePaid ->logable = 1;
        $trStatePaid ->paid = 1;
        $trStatePaid ->add();

        if (!parent::install()
            OR !$this->registerHook('paymentOptions')
            OR !$this->registerHook('paymentReturn')
		    OR !$this->registerHook('actionOrderStatusUpdate')
            OR !Configuration::updateValue('TRANZZO_POS_ID', '')
            OR !Configuration::updateValue('TRANZZO_API_KEY', '')           
			OR !Configuration::updateValue('TRANZZO_API_SECRET', '')
			OR !Configuration::updateValue('TRANZZO_ENDPOINTS_KEY', '')
			OR !Configuration::updateValue('TRANZZO_TYPE_PAYMENT', '')
            OR !Configuration::updateValue('TRANZZO_PAY_TEXT', 'Pay with Tranzzo')
            OR !Configuration::updateValue('TRANZZO_PENDING',$trStatePending->id)
            OR !Configuration::updateValue('TRANZZO_PAID',$trStatePaid->id)
        ) {
            return false;
        }


        return true;
    }

    public function uninstall()
    {
        return (parent::uninstall()
            AND Configuration::deleteByName('TRANZZO_POS_ID')
            AND Configuration::deleteByName('TRANZZO_API_KEY')
            AND Configuration::deleteByName('TRANZZO_API_SECRET')
			AND Configuration::deleteByName('TRANZZO_ENDPOINTS_KEY')
			AND Configuration::deleteByName('TRANZZO_TYPE_PAYMENT')
            AND Configuration::deleteByName('TRANZZO_PAY_TEXT')
            AND Configuration::deleteByName('TRANZZO_PENDING')
            AND Configuration::deleteByName('TRANZZO_PAID')
        );
    }

    public function getContent()
    {
        global $cookie;

        if (Tools::isSubmit('submitTranzzo')) {
            if ($tr_text = Tools::getValue('tranzzo_pay_text')) Configuration::updateValue('TRANZZO_PAY_TEXT', $tr_text);
            if ($tr_pos_id = Tools::getValue('tr_pos_id')) Configuration::updateValue('TRANZZO_POS_ID', $tr_pos_id);
            if ($api_key = Tools::getValue('api_key')) Configuration::updateValue('TRANZZO_API_KEY', $api_key);
            if ($api_secret = Tools::getValue('api_secret')) Configuration::updateValue('TRANZZO_API_SECRET', $api_secret);
			 if ($endpoints_key = Tools::getValue('endpoints_key')) Configuration::updateValue('TRANZZO_ENDPOINTS_KEY', $endpoints_key);
            if ($type_payment = Tools::getValue('type_payment')) Configuration::updateValue('TRANZZO_TYPE_PAYMENT', $type_payment);

        }
        $html = '<div style="width:550px">
           <p style="text-align:center;">
               <a href="https://tranzzo.com/" target="_blank">
                <img  src="' . __PS_BASE_URI__ . 'modules/tranzzo/views/img/tranzzo.png" alt="tranzzo.com" border="0" width="300px" align="center " />
               </a>
            </p>
        <form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
          <fieldset>
          <legend><img width="20px" src="' . __PS_BASE_URI__ . 'modules/tranzzo/views/img/logo.gif" />' . $this->l('Settings') . '</legend>

            
			<label>
              ' . $this->l('POS_ID TRANZZO') . '
            </label>
            <div class="margin-form">
              <input type="text" name="tr_pos_id" value="' . trim(Tools::getValue('tr_pos_id', Configuration::get('TRANZZO_POS_ID'))) . '" />
            </div>
            
			<label>
              ' . $this->l('API_KEY TRANZZO') . '
            </label>
            <div class="margin-form">
              <input type="text" name="api_key" value="' . trim(Tools::getValue('api_key', Configuration::get('TRANZZO_API_KEY'))) . '" />
            </div>'.
            
			'<label>
            ' . $this->l('API_SECRET TRANZZO') . '
             </label>		 
            <div class="margin-form">
              <input type="text" name="api_secret" value="' . trim(Tools::getValue('api_secret', Configuration::get('TRANZZO_API_SECRET'))) . '" />
            </div> 
			
			<label>
            ' . $this->l('ENDPOINTS_KEY TRANZZO') . '
             </label>
            <div class="margin-form">
              <input type="text" name="endpoints_key" value="' . trim(Tools::getValue('endpoints_key', Configuration::get('TRANZZO_ENDPOINTS_KEY'))) . '" />
            </div> 
			
			
             <div class="margin-form" style="margin-top:5px">
               <input type="text" name="tranzzo_pay_text" value="' . Configuration::get('TRANZZO_PAY_TEXT') . '">
             </div><br>
             <label>
             ' . $this->l('Preview') . '
             </label>
                  <div align="center">' . Configuration::get('TRANZZO_PAY_TEXT') . '&nbsp&nbsp
                  <img width="100px" alt="Pay via Tranzzo" title="Pay via Tranzzo" src="' . __PS_BASE_URI__
            . 'modules/tranzzo/views/img/tranzzo.png">
                    </div><br>  
            <div style="float:right;"><input type="submit" name="submitTranzzo" class="button btn btn-default pull-right" value="' . $this->l('Save') . '" /></div><div 
            class="clear"></div>
          </fieldset>
        </form>
        </div>';

        return $html;
    }

    //Возвращает новый способ оплаты
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }
        $payment_options = [
            $this->getCardPaymentOption()
        ];
        return $payment_options;
    }


    public function getCardPaymentOption()
    {
        global $cookie, $cart;

        require_once(__DIR__ . '/TranzzoApi.php');
		
		
        $total = $cart->getOrderTotal();
        $currency = $this->getCurrency((int)$cart->id_currency);
        $s_key = Configuration::get('TRANZZO_API_SECRET');

        $tranzzo_api = new TranzzoApi(trim(Configuration::get('TRANZZO_POS_ID')), trim(Configuration::get('TRANZZO_API_KEY')),trim(Configuration::get('TRANZZO_API_SECRET')), trim(Configuration::get('TRANZZO_ENDPOINTS_KEY')));	   
	    $tranzzo_api->setServerUrl($this->context->link->getModuleLink($this->name, 'validation', array()));
		$tranzzo_api->setResultUrl($this->context->link->getModuleLink($this->name, 'confirmation', array()));
		$tranzzo_api->setOrderId($cart->id);
		$tranzzo_api->setAmount(number_format(sprintf("%01.2f", $total), 1, '.', ''));
		$tranzzo_api->setCurrency($currency->iso_code);
		$tranzzo_api->setDescription("Order #".$cart->id);  
        $data = array();
		$form = array();
		//print_r($tranzzo_api);
        $response = $tranzzo_api->createPaymentHosted(0);
		//print_r($response);
		//$this->wrlog($response);
	    $tr_action = '';
		if (!empty($response['redirect_url'])) {
			$tr_action = $response['redirect_url'];
        }else{
			// ?
		}
	
        $externalOption = new PaymentOption(); 
        $externalOption->setCallToActionText($this->l(Configuration::get('TRANZZO_PAY_TEXT')))
            ->setForm($this->generateForm($tr_action))
            ->setAdditionalInformation($this->context->smarty->assign(array(
                'tr_dir'=> Tools::getHttpHost(true) . __PS_BASE_URI__ . 'modules/tranzzo/'
            ))->fetch('module:tranzzo/views/templates/front/tranzzo_info.tpl'))
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/payment.png'));

        return $externalOption;
    }



       protected function generateForm($link)
    {

        $this->context->smarty->assign([
            'link' => $link
        ]);

        return $this->context->smarty->fetch('module:tranzzo/views/templates/front/payment_form.tpl');
    }      
        public function wrlog($text){
            
            $tmpLocationFile = __DIR__ . '/log.txt';
            file_put_contents($tmpLocationFile, $text);
        }
    
}

