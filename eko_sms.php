<?php
/*
* 2015 ekosshop
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
*
*  @author ekosshop <info@ekosshop.com>
*  @shop http://ekosshop.com
*  @copyright  2015 ekosshop
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*
*/

if (!defined('_PS_VERSION_'))
    exit;

class eko_sms extends Module
{
    public  $_html = '', $smsOP;
    private $_postErrors = array();

    public function __construct()
    {
        $this->name     = 'eko_sms';
        $this->tab      = 'administration';
        $this->version  = '0.1.3';
        $this->author   = 'ekosshop';

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('SMS Alert');
        $this->description = $this->l('Send SMS');
        $this->confirmUninstall = $this->l('Are you sure about removing this module?');

        $this->smsOP = array(
                            0 => array('id' => 1, 'name' => 'Direct100',    'url' => 'http://www.direct100.mobi/activacao',     'api' => '1', 'img' => 'direct100.png'),
                            1 => array('id' => 2, 'name' => 'VoipBuster',   'url' => 'https://www.voipbuster.com',              'api' => '0', 'img' => 'voip_buster.png'),
                            2 => array('id' => 3, 'name' => '12Voip',       'url' => 'https://www.12voip.com',                  'api' => '0', 'img' => 'voip_12voip.png'),
                            3 => array('id' => 4, 'name' => 'FreeVoipDeal', 'url' => 'https://www.freevoipdeal.com',            'api' => '0', 'img' => 'voip_freevoipdeal.png'),
                            4 => array('id' => 5, 'name' => 'InternetCalls','url' => 'https://www.internetcalls.com',           'api' => '0', 'img' => 'voip_internetcalls.png'),
                            5 => array('id' => 6, 'name' => 'Sip Discount', 'url' => 'https://www.sipdiscount.com',             'api' => '0', 'img' => 'voip_sipdiscount.png'),
                            6 => array('id' => 7, 'name' => 'SMSdiscount',  'url' => 'https://www.smsdiscount.com',             'api' => '0', 'img' => 'voip_smsdiscount.png'),
                            7 => array('id' => 8, 'name' => 'Voip Stunt',   'url' => 'https://www.voipstunt.com',               'api' => '0', 'img' => 'voip_stunt.png'),
                            8 => array('id' => 9, 'name' => 'Clickatell',   'url' => 'https://www.clickatell.com',              'api' => '1', 'img' => 'clickatell.png'),
                       );
    }

    public function install()
    {
        if(!(Configuration::get('EKO_SMS_OP') > 0))
            $this->create_db();

        if(!parent::install() || !$this->registerHook('postUpdateOrderStatus') || !$this->registerHook('actionEkoCttUpdate') || !$this->registerHook('displayAdminOrderRight') || !$this->registerHook('displayAdminCustomers'))
            return false;

        return true;
    }

    public function uninstall()
    {
        if(!parent::uninstall())
            return false;

        Configuration::deleteByName("EKO_SMS_OP");
        Configuration::deleteByName("EKO_SMS_USERNAME");
        Configuration::deleteByName("EKO_SMS_PASSWORD");
        Configuration::deleteByName("EKO_SMS_ADMINMOBILE");
        Configuration::deleteByName("EKO_SMS_TOKEN");
        Configuration::deleteByName("EKO_SMS_DATATOKEN");
        Configuration::deleteByName("EKO_SMS_LOWCOST");
        Configuration::deleteByName("EKO_SMS_CLEANMSG");
        Configuration::deleteByName("EKO_SMS_APIID");

        return true;
    }

    public function create_db()
    {

        Db::getInstance()->Execute
        ('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'eko_sms_send`(
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `order_id` int(10) unsigned NOT NULL,
              `id_order_state` int(10) unsigned NOT NULL,
              `id_other` int(10) unsigned NOT NULL,
              `tipo` smallint(3) NOT NULL,
              `date` varchar(12) NOT NULL,
              `hora` varchar(12) NOT NULL,              
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;'
        );

        Db::getInstance()->Execute
        ('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'eko_sms_restrict`(
              `country_id` int(11) NOT NULL,
              `id_order_status` smallint(3) NOT NULL,
              `id_ctt_status` smallint(3) NOT NULL,
              `id_other` smallint(3) NOT NULL,
              PRIMARY KEY (`country_id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;'
        );

        Db::getInstance()->Execute
        ('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'eko_sms_cmsg`(
              `id_order_state` int(10) unsigned NOT NULL,
              `id_lang` int(10) unsigned NOT NULL,
              `msg_customer` text,
               PRIMARY KEY (`id_order_state`, `id_lang`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;'
        );

        Db::getInstance()->Execute
        ('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'eko_sms_mmsg`(
              `id_order_state` int(10) unsigned NOT NULL,
              `msg_merchant` text,
               PRIMARY KEY (`id_order_state`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;'
        );

        Db::getInstance()->Execute
        ('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'eko_sms_customer`(
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `id_customer` int(10) unsigned NOT NULL,
              `id_order` int(10) unsigned NOT NULL,
              `id_other` int(10) unsigned NOT NULL,
              `smsmsg` text,
              `date` varchar(12) NOT NULL,
              `hora` varchar(12) NOT NULL,
               PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;'
        );

        return true;
    }

    private function _postValidation()
    {
        if (Tools::isSubmit('btnSubmit'))
        {
            $xAux = Tools::getValue('USERNAME');
            if (empty($xAux))
                $this->_postErrors[] = $this->l('You have to enter User Name.');

            $xAux  = Tools::getValue('PASSWORD');
            $xAux1 = Configuration::get('EKO_SMS_PASSWORD');
            if (empty($xAux) AND empty($xAux1))
                $this->_postErrors[] = $this->l('You have to enter Password.');

            $xAux = Tools::getValue('ADMINMOBILE');
            if (empty($xAux))
                $this->_postErrors[] = $this->l('You have to enter Admin Mobile Contact.');
            elseif (substr(Tools::getValue('ADMINMOBILE'),0,1) != '+')
                $this->_postErrors[] = $this->l('Mobile Contact must be enterd like (+351123456789).');
        }
    }

    public function getContent()
    {
        $this->_html = '<h2>'.$this->displayName.'</h2>';

        if(Tools::isSubmit('btnSubmit')) {
            $this->_postValidation();
            if (!count($this->_postErrors))
                $this->_postProcess();
            else
                foreach ($this->_postErrors as $err)
                    $this->_html .= $this->displayError($err);
        }
        elseif (Tools::isSubmit('updateeko_sms')) {
            $this->_html .= $this->_displayEkoShortCode();
            $this->_html .= $this->renderForm();
            return $this->_html;
        }
        elseif (Tools::isSubmit('btnSubmitSMS')) {
            $this->processSaveSMS();
        }
        elseif (Tools::isSubmit('id_order_status_eko_smsrestrict')) {
            $this->updateRestric('id_order_status');
        }
        elseif (Tools::isSubmit('id_ctt_status_eko_smsrestrict')) {
            $this->updateRestric('id_ctt_status');
        }
        elseif (Tools::isSubmit('submitBulkaddo_eko_smsrestrict')) {
            $this->saveMulti(Tools::getValue('_eko_smsrestrictBox'), 'id_order_status', 1);
        }
        elseif (Tools::isSubmit('submitBulkremoveo_eko_smsrestrict')) {
            $this->saveMulti(Tools::getValue('_eko_smsrestrictBox'), 'id_order_status', 0);
        }
        elseif (Tools::isSubmit('submitBulkaddc_eko_smsrestrict')) {
            $this->saveMulti(Tools::getValue('_eko_smsrestrictBox'), 'id_ctt_status', 1);
        }
        elseif (Tools::isSubmit('submitBulkremovec_eko_smsrestrict')) {
            $this->saveMulti(Tools::getValue('_eko_smsrestrictBox'), 'id_ctt_status', 0);
        }
        elseif (Tools::isSubmit('submitBulkadd_eko_smsrestrict')) {
            $this->saveMulti(Tools::getValue('_eko_smsrestrictBox'), 'id_order_status', 1);
            $this->saveMulti(Tools::getValue('_eko_smsrestrictBox'), 'id_ctt_status', 1);
        }
        elseif (Tools::isSubmit('submitBulkremove_eko_smsrestrict')) {
            $this->saveMulti(Tools::getValue('_eko_smsrestrictBox'), 'id_order_status', 0);
            $this->saveMulti(Tools::getValue('_eko_smsrestrictBox'), 'id_ctt_status', 0);
        } else
            $this->_html .= '<br />';

        $this->_displayEkoSMS();
        $this->_html .= $this->SMSrenderForm();
        $this->_html .= $this->renderList();
        if(Module::isInstalled("eko_ctt"))
            $this->_html .= $this->renderCTTList();
        $this->_html .= $this->renderRestricList();

        return $this->_html;
    }

    public function processSaveSMS()
    {
        $id_state = Tools::getValue('id_order_state');

        $languages = Language::getLanguages(false);
        foreach ($languages AS $lang) {
            $this->saveSMSmsgDB(true, $id_state, Tools::getValue('msg_customer_'.$lang['id_lang']), (int)$lang['id_lang']);
        }
        $this->saveSMSmsgDB(false, $id_state, Tools::getValue('msg_merchant'));
            
        return true;
    }

    private function _postProcess()
    {
        if(Tools::isSubmit('btnSubmit'))
        {
            Configuration::updateValue('EKO_SMS_OP',            Tools::getValue('OP'));
            Configuration::updateValue('EKO_SMS_USERNAME',      Tools::getValue('USERNAME'));
            $xAux = Tools::getValue('PASSWORD');
            if(!empty($xAux))
                Configuration::updateValue('EKO_SMS_PASSWORD',  Tools::getValue('PASSWORD'));
            Configuration::updateValue('EKO_SMS_ADMINMOBILE',   Tools::getValue('ADMINMOBILE'));
            Configuration::updateValue('EKO_SMS_LOWCOST',       Tools::getValue('LOWCOST'));
            Configuration::updateValue('EKO_SMS_CLEANMSG',      Tools::getValue('CLEAN'));
            Configuration::updateValue('EKO_SMS_APIID',         Tools::getValue('APIID'));
            Configuration::updateValue('EKO_SMS_TOKEN',         '');
            Configuration::updateValue('EKO_SMS_DATATOKEN',     '');
        }
        $this->_html .= $this->displayConfirmation($this->l('Settings updated'));
    }

    private function _displayEkoShortCode()
    {
        $this->_html .= '<div class="alert">
                            <div style="float:left; margin-right:15px; height:100px;">
                                <img src="../modules/eko_sms/logo_sms.png" width="86" height="86">
                            </div>
                            <p><strong>'.$this->l('You can use ShortCode (use lower case).').'</strong></p><br/>
                            <table>
                                <tr>
                                    <td>[shopname]</td><td>&nbsp;=>&nbsp;</td><td>'.$this->l('Shop Name').'</td>
                                </tr><tr>
                                    <td>[orderid]</td><td>&nbsp;=>&nbsp;</td><td>'.$this->l('Order ID').'</td>
                                </tr><tr>
                                    <td>[orderref]</td><td>&nbsp;=>&nbsp;</td><td>'.$this->l('Order Reference').'</td>
                                </tr><tr>
                                    <td>[ordertotal]</td><td>&nbsp;=>&nbsp;</td><td>'.$this->l('Order Total Paid').'</td>
                                </tr><tr>
                                    <td>[customerfname]</td><td>&nbsp;=>&nbsp;</td><td>'.$this->l('Customer First Name').'</td>
                                </tr><tr>
                                    <td>[customerlname]</td><td>&nbsp;=>&nbsp;</td><td>'.$this->l('Customer Last Name').'</td>
                                </tr><tr>
                                    <td>[carriertracking]</td><td>&nbsp;=>&nbsp;</td><td>'.$this->l('Carrier Tracking Number').'</td>
                                </tr><tr>
                                    <td>[carriername]</td><td>&nbsp;=>&nbsp;</td><td>'.$this->l('Carrier Name').'</td>
                                </tr>
                            </table>
                        </div>';
    }

    private function _displayEkoSMS()
    {
        $this->_html .= '
        <div class="alert">
            <img src="../modules/eko_sms/logo_sms.png" style="float:left; margin-right:15px;" width="86" height="86">
            <p><strong>'.$this->l('This module allows you to send SMS when :').'</strong>
            <ul>
            <li>'.$this->l('the order state changes;').'</li>
            <li>'.$this->l('the shipping state changes (if use eko_ctt module v.0.1.0 or higher);').'</li>
            </ul>
            <br/>
            '.$this->l('This module also allows you to send SMS to client from client\'s info and/or order details').'
            </p>
            <br/><p>'.$this->l('Supported Operators:').'</p>
            <p>
                <br/>';
        foreach($this->smsOP as $op)
            $this->_html .= '<a href="'.$op['url'].'" target="_blank" style="margin-right:15px;"><img src="../modules/'.$this->name.'/img/'.$op['img'].'"></a>';
        $this->_html .= '</p>
            <br/><p>
               '.$this->l('Direct100 Promo Code : ').'<strong>prestashop</strong> '.$this->l('(Free 2,50 € / R$ 12 in SMS)').'<br/>
               Direct100 PT : <a href="http://www.direct100.mobi/activacao" target="_blank">http://www.direct100.mobi/activacao</a><br/>
               Direct100 BR : <a href="http://www.direct100.mobi/br/cadastro" target="_blank">http://www.direct100.mobi/br/cadastro</a>
            </p>
        </div>';
    }

    public function SMSrenderForm()
    {
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $OPOptions = array();
        foreach($this->smsOP as $op)
            $OPOptions[] = array(
                                'id_option' => $op['id'], 
                                'name' => $op['name'] 
                                );

        $fields_form_00 = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('SMS Alert'),
                    'icon' => 'icon-mobile-phone'
                ),
                'input' => array(
                    array (
                        'type'     => 'select',
                        'label'    => $this->l('Operator'),
                        'name'     => 'OP',
                        'options'  => array(
                            'query'     => $OPOptions,
                            'id'        => 'id_option',
                            'name'      => 'name'
                        )
                    ),
                    array(
                        'type'     => 'text',
                        'label'    => $this->l('User Name'),
                        'class'    => 'fixed-width-lg',
                        'name'     => 'USERNAME',
                        'prefix'   => '<i class="icon icon-user"></i>',
                        'required' => true
                    ),
                    array(
                        'type'     => 'password',
                        'label'    => $this->l('Password'),
                        'name'     => 'PASSWORD',
                        'class'    => 'fixed-width-lg',
                        'required' => true
                    ),
                    array(
                        'type'     => 'text',
                        'label'    => $this->l('API ID'),
                        'class'    => 'fixed-width-lg',
                        'name'     => 'APIID',
                        'prefix'   => '<i class="icon icon-list-alt"></i>',
                        'desc'     => $this->l('API ID if existing'),
                        'required' => false
                    ),
                    array(
                        'type'     => 'switch',
                        'label'    => $this->l('Convert Special Chars'),
                        'name'     => 'CLEAN',
                        'class'    => 'fixed-width-lg',
                        'desc'     => $this->l('Convert Chars like Á to A'),
                        'values'  => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type'     => 'switch',
                        'label'    => $this->l('Low-Cost SMS'),
                        'name'     => 'LOWCOST',
                        'class'    => 'fixed-width-lg',
                        'desc'     => $this->l('Valid only for Direct100'),
                        'values'  => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type'     => 'text',
                        'label'    => $this->l('Admin Mobile Contact'),
                        'name'     => 'ADMINMOBILE',
                        'class'    => 'fixed-width-lg',
                        'prefix'   => '<i class="icon icon-mobile-phone"></i>',
                        'desc'     => $this->l('Mobile contact with international code (ex. +351123456789)'),
                        'required' => true
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            )
        );

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->languages = Language::getLanguages();
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form_00));
    }

    private function getConfigFieldsValues()
    {
        return array(
            'OP'          => Tools::getValue('OP',         Configuration::get('EKO_SMS_OP')),
            'USERNAME'    => Tools::getValue('USERNAME',   Configuration::get('EKO_SMS_USERNAME')),
            'PASSWORD'    => Tools::getValue('PASSWORD',   Configuration::get('EKO_SMS_PASSWORD')),
            'ADMINMOBILE' => Tools::getValue('ADMINMOBILE',Configuration::get('EKO_SMS_ADMINMOBILE')),
            'LOWCOST'     => Tools::getValue('LOWCOST',    Configuration::get('EKO_SMS_LOWCOST')),
            'CLEAN'       => Tools::getValue('CLEAN',      Configuration::get('EKO_SMS_CLEANMSG')),
            'APIID'       => Tools::getValue('APIID',      Configuration::get('EKO_SMS_APIID')),
        );
    }

    public function renderList()
    {
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        if ($result = $this->getOrderStatus((int)$default_lang))
        {
            $this->fields_list = array(
                'id_order_state' => array(
                    'title' => $this->l('ID'),
                    'width' => 'auto',
                    'type'  => 'text'
                    ),
                'name' => array(
                    'title' => $this->l('Name'),
                    'width' => 'auto',
                    'type'  => 'text',
                    'color' => 'color'
                    ),
                'msg_customer' => array(
                    'title' => $this->l('SMS -> Client'),
                    'width' => 'auto',
                    'type'  => 'text'
                    ),
                'msg_merchant' => array(
                    'title' => $this->l('SMS -> Admin'),
                    'width' => 'auto',
                    'type'  => 'text'
                    )
                );

            $helper = new HelperList();
            $helper->shopLinkType = '';
            $helper->simple_header = true;
            $helper->identifier = 'id_order_state';
            $helper->actions = array('edit');
            $helper->show_toolbar = false;
            $helper->title = '<i class="icon icon-shopping-cart"></i>&nbsp;'.$this->l('Order Status');
            $helper->table = $this->name;
            $helper->token = Tools::getAdminTokenLite('AdminModules');
            $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
            return $helper->generateList($result, $this->fields_list);
        }
    }

    public function renderCTTList()
    {
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        if ($result = $this->getCTTStatus((int)$default_lang))
        {
            $this->fields_list = array(
                'id_order_state' => array(
                    'title' => $this->l('ID'),
                    'width' => 'auto',
                    'type'  => 'text'
                    ),
                'name' => array(
                    'title' => $this->l('Name'),
                    'width' => 'auto',
                    'type'  => 'text'
                    ),
                'msg_customer' => array(
                    'title' => $this->l('SMS -> Client'),
                    'width' => 'auto',
                    'type'  => 'text'
                    ),
                'msg_merchant' => array(
                    'title' => $this->l('SMS -> Admin'),
                    'width' => 'auto',
                    'type'  => 'text'
                    )
                );

            $helper = new HelperList();
            $helper->shopLinkType = '';
            $helper->simple_header = true;
            $helper->identifier = 'id_order_state';
            $helper->actions = array('edit');
            $helper->show_toolbar = false;
            $helper->title = '<i class="icon icon-truck"></i>&nbsp;'.$this->l('CTT Status');
            $helper->table = $this->name;
            $helper->token = Tools::getAdminTokenLite('AdminModules');
            $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
            return $helper->generateList($result, $this->fields_list);
        }
    }
    
    public function renderRestricList() {
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        $this->bulk_actions = array(
            'add'     => array('text' => $this->l('Add Selected Countries'), 'icon' => 'icon-plus'),
            'remove'  => array('text' => $this->l('Remove Selected Countries'), 'icon' => 'icon-minus'),
        );

        if(Module::isInstalled("eko_ctt")) {
            $this->bulk_actions['divid1']  = array('text' => 'divider');
            $this->bulk_actions['addo']    = array('text' => $this->l('Add Selected Countries to Order Status'), 'icon' => 'icon-plus');
            $this->bulk_actions['removeo'] = array('text' => $this->l('Remove Selected Countries from Order Status'), 'icon' => 'icon-minus');
            $this->bulk_actions['divid2']  = array('text' => 'divider');
            $this->bulk_actions['addc']    = array('text' => $this->l('Add Selected Countries to CTT Status'), 'icon' => 'icon-plus');
            $this->bulk_actions['removec'] = array('text' => $this->l('Remove Selected Countries from CTT Status'), 'icon' => 'icon-minus');
        }

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = false;
        $helper->identifier = 'id_country';
        $helper->show_toolbar = false;
        $helper->no_link = true;
        $helper->bulk_actions = $this->bulk_actions;
        $helper->title = '<i class="icon icon-globe"></i>&nbsp;'.$this->l('Country Restrict');
        $helper->table = '_'.$this->name."restrict";
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        return $helper->generateList($this->getRestricFieldsData(), $this->getRestricFields());
    }

    private function getRestricFieldsData() {
        $result = array();
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        $resultC = Country::getCountries($default_lang);

        foreach($resultC as $key => $rc) {
            $os = 0;
            $cs = 0;
            $rest = $this->getSMSRestrictDB($rc['id_country']);
            if($rest) {
                $os = $rest['id_order_status'];
                $cs = $rest['id_ctt_status'];
            }
            if(Module::isInstalled("eko_ctt")) {
                $result[] = array('id_country' => $rc['id_country'], 'country_name' => $rc['name'], 'id_order_status' => $os, 'id_ctt_status' => $cs);
            } else {
                $result[] = array('id_country' => $rc['id_country'], 'country_name' => $rc['name'], 'id_order_status' => $os);
            }
        }

        return($result);
    }

    private function getRestricFields() {
        $result = array(
            'id_country' => array(
                                'title' => $this->l('ID Country'),
                                'width'  => 'auto',
                                'type' => 'text',
                                 ),
            'country_name' => array(
                                'title' => $this->l('Country'),
                                'width'  => 'auto',
                                'type' => 'text',
                                 ),
            'id_order_status' => array(
                                'title'  => $this->l('Order Status'),
                                'width'  => 'auto',
                                'type'   => 'bool',
                                'active' => 'id_order_status',
                                'ajax'   => false,
                                'icon'   => array(
                                            0 => 'disabled.gif',
                                            1 => 'enabled.gif',
                                            'default' => 'disabled.gif'
                                        ),
                                )
        );

        if(Module::isInstalled("eko_ctt"))
             $result['id_ctt_status'] = array(
                                'title'  => $this->l('CTT Status'),
                                'width'  => 'auto',
                                'type'   => 'bool',
                                'active' => 'id_ctt_status',
                                'ajax'   => false,
                                'icon'   => array(
                                            0 => 'disabled.gif',
                                            1 => 'enabled.gif',
                                            'default' => 'disabled.gif'
                                        ),
                                );

        return($result);
    }

    private function updateRestric($st) {
        $this->saveSMSRestrictDB(Tools::getValue('id_country'), $st);
    }

    public function renderForm()
    {
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        if((int)Tools::getValue('id_order_state') < 999000)
            $StatName     = $this->getOrderStatus((int)$default_lang, (int)Tools::getValue('id_order_state'));
        else
            $StatName     = $this->getCTTStatus((int)$default_lang, (int)Tools::getValue('id_order_state'));
        $fields_form_00 = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('SMS Send Alert :: ').$StatName[0]['name'],
                    'icon' => 'icon-mobile-phone'
                ),
                'input' => array(
                    array(
                        'type' => 'hidden',
                        'name' => 'id_order_state'
                    ),
                    array(
                        'type'  => 'textarea',
                        'label' => $this->l('SMS -> Client'),
                        'lang'  => true,
                        'name'  => 'msg_customer'
                    ),
                    array(
                        'type'  => 'textarea',
                        'label' => $this->l('SMS -> Admin'),
                        'lang'  => false,
                        'name'  => 'msg_merchant'
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
                'buttons' => array(
                    array(
                        'href' => AdminController::$currentIndex.'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'),
                        'title' => $this->l('Back'),
                        'icon' => 'process-icon-back'
                    )
                )
            )
        );

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        foreach (Language::getLanguages(false) as $lang)
            $helper->languages[] = array(
                'id_lang' => $lang['id_lang'],
                'iso_code' => $lang['iso_code'],
                'name' => $lang['name'],
                'is_default' => ($default_lang == $lang['id_lang'] ? 1 : 0)
            );
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmitSMS';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getSMSFieldsValues(),
        );

        return $helper->generateForm(array($fields_form_00));
    }

    private function getSMSFieldsValues()
    {
        $fields_value = array();
        $id_state = (int)Tools::getValue('id_order_state');

        foreach (Language::getLanguages(false) as $lang) {
            $ek = $this->getSMSmsgDB($id_state, (int)$lang['id_lang']);
            if(isset($ek['id_order_state']))
            {
                $fields_value['msg_customer'][(int)$lang['id_lang']] = Tools::getValue('msg_customer_'.(int)$lang['id_lang'], $ek['msg_customer']);
            } else {
                $fields_value['msg_customer'][(int)$lang['id_lang']] = Tools::getValue('msg_customer_'.(int)$lang['id_lang'], '');
            }
        }

        $ek = $this->getSMSMmsgDB($id_state);
        if(isset($ek['id_order_state'])){
            $fields_value['msg_merchant']   = Tools::getValue('msg_customer', $ek['msg_merchant']);
        } else {
            $fields_value['msg_merchant']   = Tools::getValue('msg_customer', '');
        }
        $fields_value['id_order_state'] = $id_state;

        return $fields_value;
    }

    private function getOrderStatus($default_lang, $state = 0) {
        if($state == 0) {
            $sqlE = '
                SELECT os.id_order_state, osl.name, os2.msg_customer, os3.msg_merchant, os.color
                FROM `'._DB_PREFIX_.'order_state` os
                LEFT JOIN `'._DB_PREFIX_.'order_state_lang` osl ON (os.`id_order_state` = osl.`id_order_state` AND osl.`id_lang` = '.$default_lang.')
                LEFT JOIN `'._DB_PREFIX_.'eko_sms_cmsg` os2 ON (os.`id_order_state` = os2.`id_order_state` AND os2.`id_lang` = '.$default_lang.')
                LEFT JOIN `'._DB_PREFIX_.'eko_sms_mmsg` os3 ON (os.`id_order_state` = os3.`id_order_state`)
                WHERE `deleted` = 0
                ORDER BY `name` ASC
            ';
        } else {
            $sqlE = '
                SELECT *
                FROM `'._DB_PREFIX_.'order_state_lang`
                WHERE `id_lang` = '.$default_lang.' and `id_order_state` = '.$state;
        }

        if ($result = Db::getInstance()->ExecuteS($sqlE))
            return $result;

        return false;
    }

    private function getCTTStatus($default_lang, $state = 0) {
        if($state > 999000) {
            if(Module::isInstalled("eko_ctt")) {
                $ctt        = new eko_ctt();
                $ctt_status = $ctt->getCTTStates($state);
                $result[]   = array('name' => 'EKO_CTT - '.$ctt_status['name']);
                return $result;
            }
        } else {
            if(Module::isInstalled("eko_ctt")) {
                $ctt        = new eko_ctt();
                $ctt_status = $ctt->getCTTStates();
                foreach ($ctt_status as $key=>$cttST) {
                    $DbData   = $this->getSMSmsgDB($cttST['id'], $default_lang);
                    $DbDataM  = $this->getSMSMmsgDB($cttST['id']);
                    $result[] = array('id_order_state' => $cttST['id'], 'name' => "EKO_CTT : ".$cttST['name'], 'msg_customer' => $DbData['msg_customer'], 'msg_merchant' => $DbDataM['msg_merchant'] );
                }
            }
            return $result;
        }

        return false;
    }

    private function SMSmsgExist($id_state, $lang = '') {
        $sqlE = 'SELECT count(id_order_state) as Total
                 FROM `'._DB_PREFIX_.'eko_sms_cmsg`
                 WHERE `id_order_state` = '.$id_state;

        if(!empty($lang))
            $sqlE .= ' AND `id_lang` = '.$lang;

        return(Db::getInstance()->getValue($sqlE));
    }

    private function SMSMmsgExist($id_state) {
        $sqlE = 'SELECT count(id_order_state) as Total
                 FROM `'._DB_PREFIX_.'eko_sms_mmsg`
                 WHERE `id_order_state` = '.$id_state;

        return(Db::getInstance()->getValue($sqlE));
    }

    private function is_SMSmsgSend($id_state, $id_order, $tipo = 0) {
        $sqlE = 'SELECT count(id) as Total
                 FROM `'._DB_PREFIX_.'eko_sms_send`
                 WHERE `order_id` = '.$id_order.' AND `id_order_state` = '.$id_state.' AND `tipo` = '.$tipo;

        if(Db::getInstance()->getValue($sqlE) > 0)
            return true;

        return false;
    }

    private function setSMSmsgSend($id_state, $id_order, $tipo = 0) {
        $sqlE = 'INSERT INTO `'._DB_PREFIX_.'eko_sms_send`
                    ( `id_order_state`, `order_id`, `tipo`, `date`, `hora`)
                 VALUES
                    ('.$id_state.', '.$id_order.', '.$tipo.',\''.date("Y/m/d").'\',\''.date("H:i:s").'\')';

        return(Db::getInstance()->Execute($sqlE));
    }

    private function getSMSmsgDB($id_state, $lang) {
        $sqlE = 'SELECT *
            FROM `'._DB_PREFIX_.'eko_sms_cmsg`
            WHERE `id_order_state` = '.$id_state.' AND `id_lang` = '.$lang;

        return(Db::getInstance()->getRow($sqlE));
    }

    private function getSMSMmsgDB($id_state) {
        $sqlE = 'SELECT *
            FROM `'._DB_PREFIX_.'eko_sms_mmsg`
            WHERE `id_order_state` = '.$id_state;

        return(Db::getInstance()->getRow($sqlE));
    }

    private function saveSMSmsgDB($type, $id_state, $msg, $id_lang = '') {
        $sqlE = '';
        if($type) {
            if($this->SMSmsgExist($id_state, $id_lang) > 0 ) {
                if(!empty($msg)) {
                    $sqlE = 'UPDATE `'._DB_PREFIX_.'eko_sms_cmsg`
                            SET `msg_customer` = \''.$msg.'\'
                            WHERE `id_order_state` = '.$id_state.' AND `id_lang`= '.$id_lang;
                } else {
                    $sqlE = 'DELETE FROM `'._DB_PREFIX_.'eko_sms_cmsg`
                            WHERE `id_order_state` = '.$id_state.' AND `id_lang`= '.$id_lang;

                }
            } else {
                if(!empty($msg))
                    $sqlE = 'INSERT INTO `'._DB_PREFIX_.'eko_sms_cmsg`
                                ( `id_order_state`, `id_lang`, `msg_customer`)
                            VALUES
                                ('.$id_state.', '.$id_lang.', \''.$msg.'\')';
            }
        } else {
            if($this->SMSMmsgExist($id_state) > 0 ) {
                if(!empty($msg)) {
                    $sqlE = 'UPDATE `'._DB_PREFIX_.'eko_sms_mmsg`
                            SET `msg_merchant` = \''.$msg.'\'
                            WHERE `id_order_state` = '.$id_state;
                } else {
                    $sqlE = 'DELETE FROM `'._DB_PREFIX_.'eko_sms_mmsg`
                            WHERE `id_order_state` = '.$id_state;
                }
            } else {
                if(!empty($msg))
                    $sqlE = 'INSERT INTO `'._DB_PREFIX_.'eko_sms_mmsg`
                                ( `id_order_state`, `msg_merchant`)
                            VALUES
                                ('.$id_state.', \''.$msg.'\')';
            }
        }

        if(!empty($sqlE))
            return(Db::getInstance()->Execute($sqlE));
        else
            return false;
    }

    private function getSMSRestrictDB($id_country) {
        $sqlE = 'SELECT *
            FROM `'._DB_PREFIX_.'eko_sms_restrict`
            WHERE `country_id` = '.$id_country;

        return(Db::getInstance()->getRow($sqlE));
    }

    public function saveMulti($list, $type, $value) {
        if(is_array($list))
            foreach($list as $country)
                $this->saveSMSRestrictDB($country, $type, $value);
    }

    private function saveSMSRestrictDB($id_country, $type, $value=2) {
        $rest = $this->getSMSRestrictDB($id_country);
        if($rest) {
            if($value == 2) {
                $st = 0;
                if($rest[$type] == 0)
                    $st = 1;
            } else {
                $st = $value;
            }

            $sqlE = 'UPDATE `'._DB_PREFIX_.'eko_sms_restrict`
                     SET `'.$type.'` = '.$st.' 
                     WHERE `country_id` = '.$id_country;
        } else {
            if($value == 2) {
                $st = 1;
            } else {
                $st = $value;
            }

            $sqlE = 'INSERT INTO `'._DB_PREFIX_.'eko_sms_restrict`
                        ( `country_id`, `'.$type.'`)
                     VALUES
                        ('.$id_country.', '.$st.' )';
        }

        return(Db::getInstance()->Execute($sqlE));
    }

    public function hookpostUpdateOrderStatus($params) {
        if(!$this->active OR !Configuration::get('EKO_SMS_OP') > 0)
            return;

        return $this->processSMSSend($params['newOrderStatus']->id, $params['id_order']);
    }

    public function hookactionEkoCttUpdate($params) {
        if(!$this->active OR !Configuration::get('EKO_SMS_OP') > 0)
            return;

        return $this->processSMSSend($params['id_status']['id'], $params['id_order']);
    }

    private function processSMSSend($status, $id_order) {

        if($this->SMSmsgExist($status) == 0 and $this->SMSMmsgExist($status) == 0)
            return false;

        $xAux0 = Configuration::get('EKO_SMS_OP');
        $xAux1 = Configuration::get('EKO_SMS_USERNAME');
        $xAux2 = Configuration::get('EKO_SMS_PASSWORD');
        if(empty($xAux0) or empty($xAux1) or empty($xAux2))
            return false;

        $mobile   = '';
        $order    = new Order((int)$id_order);
        $customer = new Customer($order->id_customer);

        if(!$customer->isBanned($order->id_customer) and $customer->getAddressesTotalById($order->id_customer) > 0 and !$this->is_SMSmsgSend($status, $id_order)) {
            $smsCustomer = $this->getSMSmsgDB($status, $customer->id_lang);
            if(!empty($smsCustomer['msg_customer'])) {
                $address = $customer->getAddresses($customer->id_lang);
                foreach ($address as $key=>$addr) {
                    if(!empty($addr['phone_mobile'])) {
                        $xAccept = false;
                        $xC = $this->getSMSRestrictDB($addr['id_country']);
                        if($xC) {
                            if($status > 999000 and $xC['id_ctt_status'] == 1)
                                $xAccept = true;
                            if($status < 999000 and $xC['id_order_status'] == 1)
                                $xAccept = true;
                        }
                        if($xAccept) {
                            $mobile = $this->mobileProcess($addr['phone_mobile'], $addr['id_country']);
                            break;
                        }
                    }
                }

                if(!empty($mobile)) {
                    // Process msg_customer
                    $msgSMS = $this->processMSGString($smsCustomer['msg_customer'], $id_order);
                    if($this->sendSMS($mobile, $msgSMS)) $this->setSMSmsgSend($status, $id_order);
                }
            }
        }

        $xAux = Configuration::get('EKO_SMS_ADMINMOBILE');
        if(!empty($xAux) and !$this->is_SMSmsgSend($status, $id_order, 1)) {
            $smsMerchant = $this->getSMSMmsgDB($status);
            if(!empty($smsMerchant['msg_merchant'])) {
                // Process msg_merchant
                $msgSMS = $this->processMSGString($smsMerchant['msg_merchant'], $id_order);
                if($this->sendSMS(Configuration::get('EKO_SMS_ADMINMOBILE'), $msgSMS)) $this->setSMSmsgSend($status, $id_order, 1);
            }
        }

        return false;
    }

    private function mobileProcess($mobile, $country) {
        if(substr($mobile,0,1) == "+")
            return $mobile;

        $cc  = new country($country);
        $pre = $cc->call_prefix;        

        if(substr($mobile,0,strlen($pre)) == $pre)
            return "+".$mobile;
        else
            return "+".$pre.$mobile;
    }

    private function processMSGString($msg, $id_order) {
        $order         = new Order((int)$id_order);
        $customer      = new Customer($order->id_customer);
        $carrier_order = new Carrier($order->id_carrier);
        $carrier_order->name = ($carrier_order->name == '0' ? "" : $carrier_order->name);

        $msg = str_replace('[orderid]',         $id_order, $msg);
        $msg = str_replace('[orderref]',        $order->reference, $msg);
        $msg = str_replace('[ordertotal]',      $order->total_paid, $msg);
        $msg = str_replace('[customerfname]',   $customer->firstname, $msg);
        $msg = str_replace('[customerlname]',   $customer->lastname, $msg);
        $msg = str_replace('[carriertracking]', $order->shipping_number, $msg);
        $msg = str_replace('[carriername]',     $carrier_order->name, $msg);
        $msg = str_replace('[shopname]',        Configuration::get('PS_SHOP_NAME'), $msg);

        return($msg);
    }

    private function cleanSMSmsg($msg) {
        if(Configuration::get('EKO_SMS_CLEANMSG')) {
            $accentedCharacters = array ( 'à', 'á', 'â', 'ã', 'ä', 'å', 'ç', 'è', 'é', 'ê', 'ë',
                                      'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø',
                                      'ù', 'ú', 'û', 'ü', 'ý', 'ÿ',
                                      'Š', 'Ž', 'š', 'ž', 'Ÿ', 'À', 'Á', 'Â', 'Ã', 'Ä', 'Å',
                                      'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ñ', 'Ò',
                                      'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý' );

            $replacementCharacters = array ( 'a', 'a', 'a', 'a', 'a', 'a', 'c', 'e', 'e', 'e', 'e',
                                         'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o',
                                         'u', 'u', 'u', 'u', 'y', 'y',
                                         'S', 'Z', 's', 'z', 'Y', 'A', 'A', 'A', 'A', 'A', 'A',
                                         'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'N', 'O',
                                         'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y' );

            $msg = str_replace($accentedCharacters, $replacementCharacters, $msg);
        }
        return($msg);
    }

    public function sendSMS($mobile, $msg) {
        if (function_exists('array_column')) {
            $key = array_search(Configuration::get('EKO_SMS_OP'), array_column($this->smsOP, 'id'));
        } else {
            $key = array_search(Configuration::get('EKO_SMS_OP'), $this->sms_array_column($this->smsOP, 'id'));
        }
        $op  = $this->smsOP[$key];
        $msg = $this->cleanSMSmsg($msg);
        $params = array(
                'username' => Configuration::get('EKO_SMS_USERNAME'),
                'password' => Configuration::get('EKO_SMS_PASSWORD'),
                'from'     => Configuration::get('EKO_SMS_ADMINMOBILE'),
                'to'       => $mobile,
                'text'     => $msg
                );

        if($op['api'] > 0) {
            require_once("classes/".strtolower($op['name']).".class.php");
            $className = 'api'.$op['name'];
            $xOp = new $className($params);
            return ($xOp->SMSsend());
        } else {
            $apiurl = $op['url'].'/myaccount/sendsms.php';
            $sQuery = http_build_query($params);
            $aContextData = array (
                            'method' => 'GET',
                            'header' => "Connection: close\r\n".
                            "Content-Type: application/x-www-form-urlencoded\r\n".
                            "Content-Length: ".strlen($sQuery)."\r\n",
                            'content'=> $sQuery );
            
            $sContext = stream_context_create(array('http' => $aContextData));
            $response = file_get_contents($apiurl, false, $sContext);

            if(empty($response))
                return false;

            $Xml = simplexml_load_string($response);

            if($Xml->result == 0)
                return false;
        }

        return true;
    }

    /**
     * Returns the values from a single column of the input array, identified by
     * the $columnKey.
     *
     * Optionally, you may provide an $indexKey to index the values in the returned
     * array by the values from the $indexKey column in the input array.
     *
     * @param array $input A multi-dimensional array (record set) from which to pull
     *                     a column of values.
     * @param mixed $columnKey The column of values to return. This value may be the
     *                         integer key of the column you wish to retrieve, or it
     *                         may be the string key name for an associative array.
     * @param mixed $indexKey (Optional.) The column to use as the index/keys for
     *                        the returned array. This value may be the integer key
     *                        of the column, or it may be the string key name.
     * @return array
    */
    public function sms_array_column($input = null, $columnKey = null, $indexKey = null)
    {

        $paramsInput = $input;
        $paramsColumnKey = ($columnKey !== null) ? (string) $columnKey : null;

        $paramsIndexKey = null;
        if (isset($indexKey)) {
            if (is_float($indexKey) || is_int($indexKey)) {
                $paramsIndexKey = (int) $indexKey;
            } else {
                $paramsIndexKey = (string) $indexKey;
            }
        }

        $resultArray = array();

        foreach ($paramsInput as $row) {
            $key = $value = null;
            $keySet = $valueSet = false;

            if ($paramsIndexKey !== null && array_key_exists($paramsIndexKey, $row)) {
                $keySet = true;
                $key = (string) $row[$paramsIndexKey];
            }

            if ($paramsColumnKey === null) {
                $valueSet = true;
                $value = $row;
            } elseif (is_array($row) && array_key_exists($paramsColumnKey, $row)) {
                $valueSet = true;
                $value = $row[$paramsColumnKey];
            }

            if ($valueSet) {
                if ($keySet) {
                    $resultArray[$key] = $value;
                } else {
                    $resultArray[] = $value;
                }
            }

        }

        return $resultArray;
    }

    public function hookdisplayAdminOrderRight($params) {
        if(!$this->active)
            return;

        $xAux0 = Configuration::get('EKO_SMS_OP');
        $xAux1 = Configuration::get('EKO_SMS_USERNAME');
        $xAux2 = Configuration::get('EKO_SMS_PASSWORD');
        if(empty($xAux0) or empty($xAux1) or empty($xAux2)) {
            $this->smarty->assign(array(
                                    'status'        => 2,
                                    'callfrom'      => 1,
                                    'SMSerror'      => $this->l('SMS Module not Configured!')
                                  ));
        } else {
            $order    = new Order((int)$params['id_order']);
            $customer = new Customer($order->id_customer);

            if(!$customer->isBanned($order->id_customer) and $customer->getAddressesTotalById($order->id_customer) > 0) {
                $this->smarty->assign(array(
                                'status'        => 1,
                                'callfrom'      => 1,
                                'id_customer'   => $order->id_customer,
                                'id_order'      => $params['id_order'],
                                'pathSMS'       => tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/ajax'.$this->name.'.php'
                              ));
            } else {
                $this->smarty->assign(array(
                                'status'        => 2,
                                'callfrom'      => 1,
                                'SMSerror'      => $this->l('This user can not receive SMS!')
                              ));
            }
        }
        return $this->display(__FILE__, '/sms.tpl');
    }

    public function hookdisplayAdminCustomers($params) {
        if(!$this->active)
            return;

        $xAux0 = Configuration::get('EKO_SMS_OP');
        $xAux1 = Configuration::get('EKO_SMS_USERNAME');
        $xAux2 = Configuration::get('EKO_SMS_PASSWORD');
        if(empty($xAux0) or empty($xAux1) or empty($xAux2)) {
            $this->smarty->assign(array(
                                    'status'        => 2,
                                    'callfrom'      => 2,
                                    'SMSerror'      => $this->l('SMS Module not Configured!')
                                  ));
        } else {
            $customer = new Customer($params['id_customer']);

            if(!$customer->isBanned($params['id_customer']) and $customer->getAddressesTotalById($params['id_customer']) > 0) {
                $this->smarty->assign(array(
                                'status'      => 1,
                                'callfrom'    => 2,
                                'id_customer' => $params['id_customer'],
                                'id_order'    => 0,
                                'pathSMS'     => tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/ajax'.$this->name.'.php'
                              ));
            } else {
                $this->smarty->assign(array(
                                'status'      => 2,
                                'callfrom'    => 2,
                                'SMSerror'    => $this->l('This user can not receive SMS!')
                              ));
            }
        }

        return $this->display(__FILE__, '/sms.tpl');
    }
    
    public function sendSMStoClient($id_customer, $id_order, $msg) {

        $mobile = '';
        $error  = $this->l('Error : SMS not send!');
        $status = "erro";

        $customer = new Customer($id_customer);
        $address = $customer->getAddresses($customer->id_lang);
        foreach ($address as $key=>$addr) {
            if(!empty($addr['phone_mobile'])) {
                $mobile = $this->mobileProcess($addr['phone_mobile'], $addr['id_country']);
                break;
            }
        }

        if(!empty($mobile)) {
            if($this->sendSMS($mobile, $msg)) {
                $status = "ok";
                $error  = "";

                $this->updatemanualmsg($id_customer, $id_order, $msg);
            }
        }

        return(Tools::jsonEncode(array(
                                      'status'     => $status,
                                      'error'      => $error
                                     )));
    }

    public function updatemanualmsg($id_customer, $id_order, $smsmsg) {
        Db::getInstance()->Execute('INSERT INTO `'._DB_PREFIX_.'eko_sms_customer` 
                    ( `id_customer`, `id_order`, `smsmsg`, `date`, `hora` )
                   VALUES
                    ('.$id_customer.', '.$id_order.', \''.$smsmsg.'\', \''.date("Y/m/d").'\', \''.date("H:i:s").'\')');
    }
}