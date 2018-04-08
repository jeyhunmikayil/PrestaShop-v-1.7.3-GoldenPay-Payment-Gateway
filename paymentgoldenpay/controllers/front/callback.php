<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '/home/estoreaz/public_html/cantamaz/payment/private/filter/filter.php';
require_once '/home/estoreaz/public_html/cantamaz/payment/private/stub/PaymentGatewayGoldenpay.php';

class PaymentGoldenpayCallbackModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $payment_key = getFilteredParam('payment_key');
        $stub = new PaymentGatewayGoldenpay();
        $resp = $stub->getPaymentResult($payment_key);

        $customer = new Customer((int)$this->context->cart->id_customer);
        $total = $resp->amount/100;
        if ($resp->status->code == 1)
        {
        	
            $order_id = Order::getOrderByCartId((int)($resp->description));
            $order = new Order($order_id);
            
            $history = new OrderHistory();
            $history->id_order = (int)$order_id;
            $history->changeIdOrderState(2, (int)($order_id));
            $history->add(true);

            $payments = $order->getOrderPaymentCollection();
            $payments[0]->transaction_id = $payment_key;
            
			$payments[0]->update();
            
            Tools::redirectLink(__PS_BASE_URI__.'order-confirmation.php?key='.$customer->secure_key.'&id_cart='.(int)$resp->description.'&id_module='.(int)$this->module->id.'&id_order='.(int)$order_id);
        }
        else
        {
            $this->context->smarty->assign([
                'message' => 'Payment Unsuccessfully',
            ]);
        }

        $this->setTemplate('module:paymentgoldenpay/views/templates/front/payment_callback.tpl');
    }
}
