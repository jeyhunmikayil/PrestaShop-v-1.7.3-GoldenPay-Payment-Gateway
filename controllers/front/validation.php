<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/*
* 2007-2015 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

/**
 * @since 1.5.0
 */

require_once('/home/estoreaz/public_html/cantamaz/modules/paymentgoldenpay/classes/PaymentGatewayGoldenpay.php');

class PaymentGoldenpayValidationModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        
        if ($this->context->cart->id_customer == 0 || $this->context->cart->id_address_delivery == 0 || $this->context->cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirectLink(__PS_BASE_URI__.'order.php?step=1');
        }

        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'paymentgoldenpay') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'validation'));
        }

        $customer = new Customer($this->context->cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirectLink(__PS_BASE_URI__.'order.php?step=1');
        }
        $customer = new Customer((int)$this->context->cart->id_customer);

        $total = $this->context->cart->getOrderTotal(true, Cart::BOTH);

        $this->module->validateOrder((int)$this->context->cart->id, Configuration::get('PS_OS_ERROR'), $total, $this->module->displayName, null, array(), null, false, $customer->secure_key);

        $cardType = 'v';
        $amount = (int) $total*100;
        $description = (int)$_REQUEST['item'];
        $lang = 'lv';

        $goldenpay = new PaymentGatewayGoldenpay();
        $goldenpay->merchantName = Tools::getValue('GOLDENPAY_MERCHANT', Configuration::get('GOLDENPAY_MERCHANT'));
        $goldenpay->authKey = Tools::getValue('GOLDENPAY_AUTHKEY', Configuration::get('GOLDENPAY_AUTHKEY'));
        $resp = $goldenpay->getPaymentKeyJSONRequest($amount, $lang, $cardType, $description);
        
        Tools::redirectLink($resp->urlRedirect);
    }

}
