<?php

require_once ('lib/sdk/Shop.php');

use Paymenterio\Payments\Shop;
use Paymenterio\Payments\Helpers\SignatureGenerator;
use Paymenterio\Payments\Services\PaymenterioException;

class Gateway {
    private $_session;
    private $_module;
    private $_basket;
    private $_shop;

    public function __construct($module = false, $basket = false) {
        $this->_session	=& $GLOBALS['user'];
        $this->_module	= $module;
        $this->_basket =& $GLOBALS['cart']->basket;
        $this->_shop = new Shop($this->_module['shop_id'], $this->_module['api_key']);
    }  

    public function transfer() {
        $shopID = $this->_module['shop_id'];
        $currency = $GLOBALS['config']->get('config', 'default_currency');
        $total = $this->_basket['total'];
        $orderID = $this->_basket['cart_order_id'];

        $storeURL = $GLOBALS['storeURL'];
        $urls = $this->getReturnUrlsForOrder($storeURL, $shopID, $orderID);

        try {
            $paymentData = $this->_shop->createPayment(
                1,
                $orderID,
                $this->getAmountForOrder($total, $currency),
                $this->getNameForOrder($orderID),
                $urls['successUrl'],
                $urls['failUrl'],
                $urls['notifyUrl']
            );
        } catch (PaymenterioException $e) {
            exit ($e);
        }

        $transfer	= array(
            'action'	=> $paymentData->payment_link,
            'method'	=> 'post',
            'target'	=> '_self',
            'submit'	=> 'auto',
        );

        return $transfer;
    }
    
    public function repeatVariables() {
        return false;
    }
    
    public function fixedVariables() {
        return array();
    }
    

    public function form() {
        return false;
    }

    public function getAmountForOrder($total, $currency)
    {
        return array(
            "value"=>$total,
            "currencyCode"=>$currency
        );
    }

    public function getNameForOrder($orderID) {
        return "Płatność za zamówienie {$orderID}";
    }

    public function getReturnUrlsForOrder($storeURL, $shopID, $orderID)
    {
        $successURL = $storeURL . '/index.php?_a=complete';
        $pattern = $storeURL . '/index.php?_a=vieworder&cart_order_id={{order_id}}';
        $url = str_replace('{{order_id}}', $orderID, $pattern);
        $notifyPattern = $storeURL . '/index.php?_g=rm&type=gateway&cmd=call&module=Paymenterio&hash={{$hash}}';
        return array(
            'successUrl' =>  $successURL,
            'failUrl' => $url,
            'notifyUrl' => $this->buildNotifyUrl($orderID, $shopID, $notifyPattern)
        );
    }

    private function buildNotifyUrl($orderID, $shopID, $notifyPattern) {
        return str_replace('{{$hash}}', SignatureGenerator::generateSHA1Signature($orderID, $shopID), $notifyPattern);
    }
    
    
    public function process() {
        httpredir(currentPage(array('_g', 'type', 'cmd', 'module'), array('_a' => 'complete')),null,true);
        return false;
    }

    public function checkSign($data, $key, $sign){
        if(md5($data['service'].$data['orderid'].$data['amount'].$data['userdata'].$data['status'].$key) === $sign){
            return true;
        }

        if(md5($data['service'].'|'.$data['orderid'].'|'.$data['amount'].'|'.$data['userdata'].'|'.$data['status'].'|'.$key) === $sign){
            return true;
        }

        return false;
    }
    
    public function call()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            Header('HTTP/1.1 400 Bad Request');
            exit("BadRequest - The request could not be resolved, try again.");
        }

        $shopID = $this->_module['shop_id'];
        $hash = $_GET['hash'];
        $body = json_decode(file_get_contents("php://input"), true);
        $orderID = 0;
        $statusID = 0;

        if (isset($body['order']) && !empty($body['order'])) {
            $orderID = $body['order'];
        }

        if (isset($body['status']) && !empty($body['status'])) {
            $statusID = $body['status'];
        }

        $order = Order::getInstance();
        $orderData = $order->getSummary($orderID);

        if (empty($orderData)) {
            Header('HTTP/1.1 404 Not Found');
            exit("OrderNotFoundException - The order was not found or was completed successfully.");
        }

        $isSignatureValid = SignatureGenerator::verifySHA1Signature($orderID, $shopID, $hash);
        if (!$isSignatureValid) {
            Header('HTTP/1.1 400 Bad Request');
            exit("WrongSignatureException - Signature mismatch.");
        }

        $currentOrderStatus = $orderData['status'];
        $orderProcessingStatusID = 2;
        $orderCompletedStatusID = 3;
        $orderDeclinedStatusID = 4;
        $logData = array();
        $logData['gateway'] = 'Paymenterio';
        $logData['order_id'] = $orderID;
        $logData['trans_id'] = $body['transaction_hash'];
        $logData['amount'] = $orderData['total'];
        $logData['status'] = $statusID;
        $logData['customer_id'] = $orderData['customer_id'];
        $logData['extra'] = '';

        if ($currentOrderStatus != $orderProcessingStatusID && $currentOrderStatus != $orderCompletedStatusID && $currentOrderStatus != $orderDeclinedStatusID) {
            if ($statusID == 5) {
                $logData['notes'][] = 'Payment successful. <br /> Transaction Hash: ' . $body['transaction_hash'];
                $order->paymentStatus(Order::PAYMENT_SUCCESS, $orderID);
                $order->orderStatus(Order::ORDER_PROCESS, $orderID);
                $order->logTransaction($logData);
                exit('Success');
            } elseif ($statusID <= 4) {
                $logData['notes'][] = 'Payment status has been changed. Current status is: ' . $statusID;
                $order->paymentStatus(Order::PAYMENT_PENDING, $orderID);
                $order->orderStatus(Order::ORDER_PENDING, $orderID);
                $order->logTransaction($logData);
                exit('Changed');
            } else {
                $logData['notes'][] = 'Payment has been canceled. Current status is: ' . $statusID;
                $order->paymentStatus(Order::PAYMENT_CANCEL, $orderID);
                $order->orderStatus(Order::ORDER_CANCELLED, $orderID);
                $order->logTransaction($logData);
                exit('Cancelled');
            }
        }

        Header('HTTP/1.1 404 Not Found');
        exit("OrderNotFoundException - The order was not found or was completed successfully.");
    }
}