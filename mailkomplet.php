<?php
if (!defined('_PS_VERSION_'))
    exit;

require_once 'api_call.php';

class Mailkomplet extends Module
{
    /**
     * Module settings
     */
    public function __construct()
    {
        $this->name = 'mailkomplet';
        $this->tab = 'emailing';
        $this->version = '1.1.0';
        $this->author = 'Webkomplet, s.r.o.';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
        
        parent::__construct();
        
        $this->displayName = $this->l('Mail Komplet');
        $this->description = $this->l('This module will connect your Prestashop to your account on Mail Komplet');
        
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }
    
    public function install()
    {
        return (
            parent::install()
            && $this->registerHook('actionCustomerAccountAdd')
            && $this->registerHook('backOfficeHeader')
            && $this->registerHook('header')
        );
    }
    
    public function uninstall()
    {
        if (!parent::uninstall() ||
            !Configuration::deleteByName('MAILKOMPLET_LIST_ID') ||
            !Configuration::deleteByName('MAILKOMPLET_API_KEY') ||
            !Configuration::deleteByName('MAILKOMPLET_BASE_CRYPT') ||
            !Configuration::deleteByName('MAILKOMPLET_SHOP_ID') ||
            !Configuration::deleteByName('MAILKOMPLET_WITHOUT_CONSENT')
        )
            return false;
            
        return true;
    }
    
    /**
     * Get content of module setting page in Prestashop's admin
     * 
     * @return string
     */
    public function getContent()
    {
        $output = null;
        // cURL extension MUST be set
        if (!extension_loaded('curl'))
        {
            return $this->l('You have to allow cURL extension to use this module.');
        }
        else
        {
            // Additional info about this module
            $output .= '<fieldset style="padding: 25px;border: 2px solid #efefef; margin-bottom: 15px;">
			<div style="float: right; width: 340px; height: 205px; border: dashed 1px #666; padding: 8px; margin-left: 12px; margin-top:-15px;">
            <a href="https://www.mail-komplet.cz" target="_blank"><img src="' . $this->_path . 'images/logo-mk.png" width="322px"/></a><br/><br/>
			<div style="clear: both;"></div>
			<p><img src="' . $this->_path . 'images/obalka.png" height="12px" style="padding-right:12px"/><a href="mailto:' . $this->l('info@mail-komplet.cz') . '" style="color:#bf1f1f;">' . $this->l('info@mail-komplet.cz') . '</a><br><br>
            <img src="' . $this->_path . 'images/telefon.png" height="12px" style="padding-right:12px"/>&nbsp;<a href="tel:' . $this->l('+420 517 070 000') . '" style="color:#bf1f1f;">' . $this->l('+420 517 070 000') . '</a></p>
			<p style="padding-top:12px;"><b>' . $this->l('Visit us for more info') . ': </b><br><a href="' . $this->l('https://www.mail-komplet.cz') . '" target="_blank" style="color:#bf1f1f;">' . $this->l('https://www.mail-komplet.cz') . '</a></p>
			</div>
			</fieldset>';
            
            // process form submit
            if (Tools::isSubmit('submit'.$this->name))
            {
                $mailkomplet_list_id = Tools::getValue('MAILKOMPLET_LIST_ID');
                $mailkomplet_api_key = Tools::getValue('MAILKOMPLET_API_KEY');
                $mailkomplet_base_crypt = Tools::getValue('MAILKOMPLET_BASE_CRYPT');
                $mailkomplet_shop_id = Tools::getValue('MAILKOMPLET_SHOP_ID');
                $mailkomplet_without_consent = Tools::getValue('MAILKOMPLET_WITHOUT_CONSENT_1') == "on";
                if ((!$mailkomplet_list_id || empty($mailkomplet_list_id))
                    || (!$mailkomplet_api_key || empty($mailkomplet_api_key))
                    || (!$mailkomplet_base_crypt || empty($mailkomplet_base_crypt)))
                    $output .= $this->displayError($this->l('Invalid Configuration value'));
                else
                {
                    Configuration::updateValue('MAILKOMPLET_LIST_ID', $mailkomplet_list_id);
                    Configuration::updateValue('MAILKOMPLET_API_KEY', $mailkomplet_api_key);
                    Configuration::updateValue('MAILKOMPLET_BASE_CRYPT', $mailkomplet_base_crypt);
                    Configuration::updateValue('MAILKOMPLET_SHOP_ID', $mailkomplet_shop_id);
                    Configuration::updateValue('MAILKOMPLET_WITHOUT_CONSENT', $mailkomplet_without_consent);
                    $output .= $this->displayConfirmation($this->l('Settings updated'));
                }
            }
            return $output.$this->displayForm();
        }
    }
    
    /**
     * Displays a module setting form in Prestashop's admin. 
     * 
     * @return string
     */
    public function displayForm()
    {
        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        
        $options = array();
        // if there is api key and base crypt set 
        if (Configuration::get('MAILKOMPLET_API_KEY') && Configuration::get('MAILKOMPLET_BASE_CRYPT'))
        {
            // get mailing lists via api
            $lists = json_decode($this->apiCall(Configuration::get('MAILKOMPLET_API_KEY'), Configuration::get('MAILKOMPLET_BASE_CRYPT'), 'GET', 'mailingLists'));
            foreach ($lists->data as $list)
            {
                $options[] = array(
                    'mailing_list_id' => $list->mailingListId,
                    'mailing_list_name' => $list->name
                );
            }
        }
        
        // Init Fields form array
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('API key'),
                    'name' => 'MAILKOMPLET_API_KEY',
                    'size' => 20,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Base Crypt'),
                    'name' => 'MAILKOMPLET_BASE_CRYPT',
                    'size' => 20,
                    'required' => true
                ),
                array(
                    'type' => 'hidden',
                    'name' => 'MAILKOMPLET_STR_CONNECT',
                    
                ),
                array(
                    'type' => 'hidden',
                    'name' => 'MAILKOMPLET_STR_CONNECTING',
                ),
                array(
                    'type' => 'hidden',
                    'name' => 'MAILKOMPLET_STR_AJAX_ERROR',
                ),
                array(
                    'type' => 'checkbox',
                    'label' => $this->l('Import without consent?'),
                    'desc' => $this->l('Import contacts without consent with newsletters?'),
                    'name' => 'MAILKOMPLET_WITHOUT_CONSENT',
                    'size' => 100,
                    'required' => true,
                    'values' => array(
                        'query' => array(
                            array(
                                'check_id' => '1'
                            )
                        ),
                        'id' => 'check_id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Shop Identity'),
                    'name' => 'MAILKOMPLET_SHOP_ID',
                    'size' => 100,
                    'required' => false
                ),
                array(
                    'type' => 'hidden',
                    'name' => 'MAILKOMPLET_MODULE_PATH'
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Choose your mailing list'),
                    'desc' => $this->l('Choose a mailing list for new customers'),
                    'name' => 'MAILKOMPLET_LIST_ID',
                    'required' => true,
                    'options' => array(
                        'query' => $options,
                        'id' => 'mailing_list_id',
                        'name' => 'mailing_list_name'
                    )
                )
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            )
        );
        
        $helper = new HelperForm();
        
        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        
        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        
        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array(
            'save' =>
            array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                '&token='.Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );
        
        // Load current value
        $helper->fields_value['MAILKOMPLET_API_KEY'] = Configuration::get('MAILKOMPLET_API_KEY');
        $helper->fields_value['MAILKOMPLET_BASE_CRYPT'] = Configuration::get('MAILKOMPLET_BASE_CRYPT');
        $helper->fields_value['MAILKOMPLET_LIST_ID'] = Configuration::get('MAILKOMPLET_LIST_ID');
        $helper->fields_value['MAILKOMPLET_SHOP_ID'] = Configuration::get('MAILKOMPLET_SHOP_ID');
        $helper->fields_value['MAILKOMPLET_WITHOUT_CONSENT_1'] = Configuration::get('MAILKOMPLET_WITHOUT_CONSENT');
        $helper->fields_value['MAILKOMPLET_MODULE_PATH'] = $this->_path;
        $helper->fields_value['MAILKOMPLET_STR_CONNECT'] = $this->l('Connect');
        $helper->fields_value['MAILKOMPLET_STR_CONNECTING'] = $this->l('Connecting');
        $helper->fields_value['MAILKOMPLET_STR_AJAX_ERROR'] = $this->l('Unable to download mailing lists. Probably API key or base crypt string is wrong. Try to set it again please');
        
        return $helper->generateForm($fields_form);
    }
    
    /**
     * Hook - adds a javascript for setting this module into a HTML head
     * 
     * @return string
     */
    public function hookBackOfficeHeader()
    {
        return '<script type="text/javascript" src="' . $this->_path . 'js/mailkomplet.js"></script>';
    }
    
    /**
     * Hook - sends customer data to Mailkomplet during saving into DB
     * @param array $params
     */
    public function hookActionCustomerAccountAdd($params)
    {
        $customer = $params['newCustomer'];

        if (Configuration::get('MAILKOMPLET_WITHOUT_CONSENT') || $customer->newsletter)
        {
            $customer_gender = null;
            // male
            if ($customer->id_gender == 1)
                $customer_gender = true;
            // female
            elseif ($customer->id_gender == 2)
                $customer_gender = false;
            
            
            $custom_columns = array(
                'ps_country' => $this->context->country->name,
                'ps_shop' => $this->context->shop->name,
                'ps_consent' => $customer->newsletter
            );
                
            $data = array(
                'name' => $customer->firstname,
                'surname' => $customer->lastname,
                'email' => $customer->email,
                'sex' => $customer_gender,
                'mailingListIds' => array(0 => Configuration::get('MAILKOMPLET_LIST_ID')),
                'customColumns' => $custom_columns
            );
            
            $this->apiCall(Configuration::get('MAILKOMPLET_API_KEY'), Configuration::get('MAILKOMPLET_BASE_CRYPT'), 'POST', 'contacts/', $data);
        }
    }
    
    public function hookDisplayHeader($params)
    {
        $mk_identity = Configuration::get('MAILKOMPLET_SHOP_ID');
        if ($mk_identity == null || $mk_identity == '')
            return false;

        $cart_items = $this->context->cart->getProducts();
        $link = new Link();
        foreach ($cart_items as &$cart_item) {
            $product = new Product($cart_item['id_product'], null, $this->context->cart->id_lang);
            $cart_item['product_url'] = $link->getProductLink($product);
            
            $image = Image::getCover($cart_item['id_product']);
            $cart_item['image_url'] = $link->getImageLink($product->link_rewrite, $image['id_image'], 'home_default');
            
            $manufacturer = new Manufacturer($cart_item['id_manufacturer'], $this->context->cart->id_lang);
            $cart_item['manufacturer'] = $manufacturer->name;
        }
        
        $customer_email = null;
        if ($this->context->customer->email != '' && $this->context->customer->email != null) {
            $customer_email = $this->context->customer->email;
        }
        
        $order_id = null;
        if ($this->context->controller instanceOf OrderConfirmationController) {
            $order_id = $this->context->controller->id_order;
        }
        
        $currency_id = 5;
        $currency = new Currency($this->context->cart->id_currency);
        switch ($currency->iso_code) {
            case 'EUR': $currency_id = 7; break;
            case 'USD': $currency_id = 32; break;
            case 'GBP': $currency_id = 33; break;
        }
        
        $this->context->smarty->assign(
            array(
                'mk_identity' => $mk_identity,
                'cart_items' => $cart_items,
                'customer_email' => $customer_email,
                'order_id' => $order_id,
                'currency_id' => $currency_id
            )
        );
        
        return $this->display(__FILE__, 'mktracker.tpl');
    }
    
    public function apiCall($api_key, $base_crypt, $method, $url, $data = null)
    {
        return mailkompletApiCall($api_key, $base_crypt, $method, $url, $data);
    }
}
