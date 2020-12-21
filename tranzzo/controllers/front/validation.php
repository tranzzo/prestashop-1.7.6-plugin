<?php
/**
* 2007-2020 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2020 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class TranzzoValidationModuleFrontController extends ModuleFrontController
{
    /**
     * This class should be use by your Instant Payment
     * Notification system to validate the order remotely
     */
    public function postProcess()
    {
        /*
         * If the module is not active anymore, no need to process anything.
         */
        if ($this->module->active == false) {
            die;
        }
		
    $this->wrlog($_POST);
	            //new
            //serialize_precision for json_encode
            if (version_compare(phpversion(), '7.1', '>=')) {
                ini_set('serialize_precision', -1);
            }
            //new
            $data = $_POST['data'];
            $signature = $_POST['signature'];
            if (empty($data) && empty($signature)){
				die('LOL! Bad Request!!!');
			}		
include(dirname(__FILE__) . '/../../TranzzoApi.php');

$tranzzo_api = new TranzzoApi(Configuration::get('TRANZZO_POS_ID'), Configuration::get('TRANZZO_API_KEY'), Configuration::get('TRANZZO_API_SECRET'), Configuration::get('TRANZZO_ENDPOINTS_KEY'));
$this->wrlog('start');
	$data_response = TranzzoApi::parseDataResponse($data);
	$method_response = $data_response[TranzzoApi::P_REQ_METHOD];
	$this->wrlog($method_response);
    $this->wrlog($data_response);
		if ($method_response == TranzzoApi::P_METHOD_AUTH || $method_response == TranzzoApi::P_METHOD_PURCHASE) {
                $cart_id = (int)$data_response[TranzzoApi::P_RES_PROV_ORDER];
                $tranzzo_cart_id = (int)$data_response[TranzzoApi::P_RES_ORDER];
				$this->wrlog('check_response_get_'.$cart_id);
	
            } else {
                $cart_id = (int)$data_response[TranzzoApi::P_RES_ORDER];
                $this->wrlog('check_response'.$cart_id);
            }
	$this->wrlog('new Cart');
	$cart = new Cart((int) $cart_id);
	$customer = new Customer((int) $cart->id_customer);
	$module_name = $this->module->displayName;
    $currency_id = (int) Context::getContext()->currency->id;
	$this->wrlog('new Cart end');
$this->wrlog($cart);$this->wrlog('display Cart end');
        
                $order_id = Order::getOrderByCartId((int) $cart_id);
				$order = new Order($order_id);
				$this->wrlog($order);
	if($tranzzo_api->validateSignature($data, $signature)){
		$status = $data_response[TranzzoApi::P_RES_STATUS];
		$code = $data_response[TranzzoApi::P_RES_RESP_CODE];
		$message = $this->module->l($data_response[TranzzoApi::P_RES_RESP_DESC]);
		 if($status == TranzzoApi::P_TRZ_ST_PENDING){		 
//return $this->module->validateOrder($cart_id,Configuration::get('TRANZZO_PENDING'), $cart->getOrderTotal(), $module_name,$message, array(), $currency_id, false, $customer->secure_key); 

		 }elseif($status == TranzzoApi::P_TRZ_ST_SUCCESS){
			 $amount_payment = TranzzoApi::amountToDouble($data_response[TranzzoApi::P_RES_AMOUNT]);
			 $order_total = number_format(sprintf("%01.2f",$cart->getOrderTotal()), 1, '.', '');
			 if ($code == 1000 && $amount_payment >= $amount_order){
				 $this->wrlog('Pay ok check_response');
if(isset($order->current_state)){
                $history = new OrderHistory();
                $history->id_order = (int)$order->id;
             return $history->changeIdOrderState(Configuration::get('TRANZZO_PAID'), (int)($order->id));
            }
				 
return $this->module->validateOrder($cart_id, Configuration::get('TRANZZO_PAID'), $amount_payment,$module_name,$message,array('transaction_id'=>$data_response[TranzzoApi::P_RES_TRSACT_ID],'payment_id'=>$data_response[TranzzoApi::P_RES_PAYMENT_ID]), $currency_id, false, $customer->secure_key);
	 
			 }elseif($code == 1002 && $amount_payment >= $amount_order){
				 $this->wrlog('pay auth check_response');
if(isset($order->current_state)){
                $history = new OrderHistory();
                $history->id_order = (int)$order->id;
             return $history->changeIdOrderState(Configuration::get('TRANZZO_PAID'), (int)($order->id));
            }				 
return  $this->module->validateOrder($cart_id, Configuration::get('TRANZZO_PAID'), $amount_payment,$module_name, $message,array('transaction_id'=>$data_response[TranzzoApi::P_RES_TRSACT_ID],'payment_id'=>$data_response[TranzzoApi::P_RES_PAYMENT_ID]), $currency_id, false, $customer->secure_key);
	 
				 
			 }elseif($method_response == TranzzoApi::U_METHOD_VOID){
if(isset($order->current_state)){
                $history = new OrderHistory();
                $history->id_order = (int)$order->id;
             return $history->changeIdOrderState(_PS_OS_PAYMENT_, (int)($order->id));
            }				 
return  $this->module->validateOrder($cart_id,_PS_OS_PAYMENT_, $amount_payment,$module_name, $message,array('transaction_id'=>$data_response[TranzzoApi::P_RES_TRSACT_ID],'payment_id'=>$data_response[TranzzoApi::P_RES_PAYMENT_ID]), $currency_id, false, $customer->secure_key);				 
				 
			 }elseif($method_response == TranzzoApi::U_METHOD_CAPTURE){
if(isset($order->current_state)){
                $history = new OrderHistory();
                $history->id_order = (int)$order->id;
             return $history->changeIdOrderState(Configuration::get('TRANZZO_PENDING'), (int)($order->id));
            }				 
return  $this->module->validateOrder($cart_id,Configuration::get('TRANZZO_PENDING'), $amount_payment,$module_name, $message,array('transaction_id'=>$data_response[TranzzoApi::P_RES_TRSACT_ID],'payment_id'=>$data_response[TranzzoApi::P_RES_PAYMENT_ID]), $currency_id, false, $customer->secure_key);				 
				 
			 }elseif($method_response == TranzzoApi::U_METHOD_REFUND){
if(isset($order->current_state)){
                $history = new OrderHistory();
                $history->id_order = (int)$order->id;
             return $history->changeIdOrderState(_PS_OS_PAYMENT_, (int)($order->id));
            }				 
return  $this->module->validateOrder($cart_id,_PS_OS_PAYMENT_, $amount_payment,$module_name, $message,array('transaction_id'=>$data_response[TranzzoApi::P_RES_TRSACT_ID],'payment_id'=>$data_response[TranzzoApi::P_RES_PAYMENT_ID]), $currency_id, false, $customer->secure_key);
					 
			 }
			 
			 
		 }
		 
		
		
		
	}else{
		$this->wrlog('validateSignature FALSE');
	}				

    }

    protected function isValidOrder()
    {
        /*
         * Add your checks right there
         */
        return true;
    }
	
	    public function wrlog($content)
        {
            return true;
			$file = 'modules/tranzzo/log.txt';
            $doc = fopen($file, 'a');
            if($doc){
                file_put_contents($file, PHP_EOL . '====================' . date("H:i:s") . '=====================', FILE_APPEND);
                if (is_array($content)) {
                    foreach ($content as $k => $v) {
                        if (is_array($v)) {
                            wrlog($v);
                        } else {
                            file_put_contents($file, PHP_EOL . $k . '=>' . $v, FILE_APPEND);
                        }
                    }
                }elseif(is_object($content)){
                    foreach (get_object_vars($content) as $k => $v) {
                        if (is_object($v)) {
                            wrlog($v);
                        } else {
                            file_put_contents($file, PHP_EOL . $k . '=>' . $v, FILE_APPEND);
                        }
                    }
                } else {
                    file_put_contents($file, PHP_EOL . $content, FILE_APPEND);
                }
                fclose($doc);
            }
        }
	
	
}
