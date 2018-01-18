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
        $this->version = '1.0.0';
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
        return (parent::install() && $this->registerHook('actionCustomerAccountAdd') && $this->registerHook('backOfficeHeader'));
    }
    
    public function uninstall()
    {
        if (!parent::uninstall() ||
            !Configuration::deleteByName('MAILKOMPLET_LIST_ID') ||
            !Configuration::deleteByName('MAILKOMPLET_API_KEY') ||
            !Configuration::deleteByName('MAILKOMPLET_BASE_CRYPT')
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
			<h2 style="color:#aad03d;">' . $this->l('Contact Mail Komplet') . '</h2>
			<div style="clear: both;"></div>
			<p>' . $this->l('Email') . ': <a href="' . $this->l('info@mail-komplet.cz') . '" style="color:#aad03d;">' . $this->l('info@mail-komplet.cz') . '</a><br>' . $this->l('Phone') . ': ' . $this->l('+420 517 070 000') . '</p>
			<p style="padding-top:20px;"><b>' . $this->l('Visit us for more info') . ': </b><br><a href="' . $this->l('http://www.mail-komplet.cz') . '" target="_blank" style="color:#aad03d;">' . $this->l('http://www.mail-komplet.cz') . '</a></p>
			</div>
			</fieldset>';
            
            // process form submit
            if (Tools::isSubmit('submit'.$this->name))
            {
                $mailkomplet_list_id = Tools::getValue('MAILKOMPLET_LIST_ID');
                $mailkomplet_api_key = Tools::getValue('MAILKOMPLET_API_KEY');
                $mailkomplet_base_crypt = Tools::getValue('MAILKOMPLET_BASE_CRYPT');
                if ((!$mailkomplet_list_id || empty($mailkomplet_list_id))
                    || (!$mailkomplet_api_key || empty($mailkomplet_api_key))
                    || (!$mailkomplet_base_crypt || empty($mailkomplet_base_crypt)))
                    $output .= $this->displayError($this->l('Invalid Configuration value'));
                else
                {
                    Configuration::updateValue('MAILKOMPLET_LIST_ID', $mailkomplet_list_id);
                    Configuration::updateValue('MAILKOMPLET_API_KEY', $mailkomplet_api_key);
                    Configuration::updateValue('MAILKOMPLET_BASE_CRYPT', $mailkomplet_base_crypt);
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
                    'name' => 'MAILKOMPLET_MODULE_PATH'
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
                ),
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

        if ($customer->newsletter)
        {
            $customer_gender = null;
            // male
            if ($customer->id_gender == 1)
                $customer_gender = true;
            // female
            elseif ($customer->id_gender == 2)
                $customer_gender = false;
                
            $data = array(
                'name' => $customer->firstname,
                'surname' => $customer->lastname,
                'email' => $customer->email,
                'sex' => $customer_gender,
                'mailingListIds' => array(0 => Configuration::get('MAILKOMPLET_LIST_ID')),
            );
            
            $this->apiCall(Configuration::get('MAILKOMPLET_API_KEY'), Configuration::get('MAILKOMPLET_BASE_CRYPT'), 'POST', 'contacts/', $data);
        }
    }
    
    public function apiCall($apiKey, $baseCrypt, $method, $url, $data = null) {
        return mailkompletApiCall($apiKey, $baseCrypt, $method, $url, $data);
    }
}
    