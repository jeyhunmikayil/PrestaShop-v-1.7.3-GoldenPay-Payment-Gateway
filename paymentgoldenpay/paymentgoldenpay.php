<?php
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

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PaymentGoldenpay extends PaymentModule
{
    protected $_html = '';
    protected $_postErrors = array();

    public $details;
    public $owner;
    public $address;
    public $extra_mail_vars;

    public function __construct()
    {
        $this->name = 'paymentgoldenpay';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';        
        $this->need_instance = 1;
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->author = 'Kamran Nəcəfzadə';
        $this->controllers = array('validation', 'callback');
        $this->is_eu_compatible = 1;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Payment Goldenpay');
        $this->description = $this->l('Description of Payment Goldenpay');
        $this->confirmUninstall = $this->trans('Are you sure you want to delete these details?', array(), 'Modules.paymentgoldenpay.Admin');

    }

    public function install()
    {
        if (!parent::install() || !$this->registerHook('paymentOptions') || !$this->registerHook('paymentReturn')) {
            return false;
        }
        return true;
    }

    private function _displayCheck()
    {
        return $this->display(__FILE__, './views/templates/hook/infos.tpl');
    }

    private function _postValidation()
    {
        if (Tools::isSubmit('btnSubmit')) {
            if (!Tools::getValue('GOLDENPAY_MERCHANT')) {
                $this->_postErrors[] = $this->trans('MERCHANT" field is required.', array(),'Modules.PaymentGoldenpay.Admin');
            } elseif (!Tools::getValue('GOLDENPAY_AUTHKEY')) {
                $this->_postErrors[] = $this->trans('Auth key" field is required.', array(), 'Modules.PaymentGoldenpay.Admin');
            }
        }
    }

    private function _postProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('GOLDENPAY_MERCHANT', Tools::getValue('GOLDENPAY_MERCHANT'));
            Configuration::updateValue('GOLDENPAY_AUTHKEY', Tools::getValue('GOLDENPAY_AUTHKEY'));
        }
        $this->_html .= $this->displayConfirmation($this->trans('Settings updated', array(), 'Admin.Notifications.Success'));
    }

    public function getContent()
    {
        $this->_html = '';

        if (Tools::isSubmit('btnSubmit')) {
            $this->_postValidation();
            if (!count($this->_postErrors)) {
                $this->_postProcess();
            } else {
                foreach ($this->_postErrors as $err) {
                    $this->_html .= $this->displayError($err);
                }
            }
        }

        $this->_html .= $this->_displayCheck();
        $this->_html .= $this->renderForm();

        return $this->_html;
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        //$order = $params['cart'];

        $payment_options = [
            $this->getExternalPaymentOption()
        ];

        return $payment_options;
    }

  

    public function getExternalPaymentOption()
    {
        $externalOption = new PaymentOption();
        $externalOption->setCallToActionText($this->l('Online Payment'))                       
                       ->setForm($this->generateForm())
                       ->setAdditionalInformation($this->context->smarty->fetch('module:paymentgoldenpay/views/templates/front/payment_infos.tpl'))
                       ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/visamaster.png'));

        return $externalOption;
    }

    
    protected function generateForm()
    {
        $cart = $this->context->cart;

        $this->context->smarty->assign([
            'amount' => (float)$cart->getOrderTotal(true, Cart::BOTH),
            'item' => $cart->id,
            'action' => $this->context->link->getModuleLink($this->name, 'validation', array(), true)
        ]);

        return $this->context->smarty->fetch('module:paymentgoldenpay/views/templates/front/payment_form.tpl');
    }

     public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->trans('GoldenPay Configuration', array(), 'Modules.PaymentGoldenpay.Admin'),
                    'icon' => 'icon-cog'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Merchant', array(), 'Modules.PaymentGoldenpay.Admin'),
                        'name' => 'GOLDENPAY_MERCHANT',
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Auth key', array(), 'Modules.PaymentGoldenpay.Admin'),
                        'name' => 'GOLDENPAY_AUTHKEY',
                        'required' => true
                    ),
                ),
                'submit' => array(
                    'title' => $this->trans('Save', array(), 'Admin.Actions'),
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->id = (int)Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
        );

        $this->fields_form = array();

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    {
        return array(
            'GOLDENPAY_MERCHANT' => Tools::getValue('GOLDENPAY_MERCHANT', Configuration::get('GOLDENPAY_MERCHANT')),
            'GOLDENPAY_AUTHKEY' => Tools::getValue('GOLDENPAY_AUTHKEY', Configuration::get('GOLDENPAY_AUTHKEY')),
        );
    }
   
}
