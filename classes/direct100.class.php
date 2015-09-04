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

class apiDirect100 extends ObjectModel
{
    public $msg;
    public $UserName;
    public $Password;
    public $mobile;

    public function __construct($params) {
        $this->UserName = $params['username'];
        $this->Password = $params['password'];
        $this->msg      = $params['text'];
        $this->mobile   = str_replace('+','',$params['to']);
    }

    public function SMSsend() {
        $token  = $this->getAPIToken();
        if(!empty($token)) {
            $url    = 'https://direct100.inesting.com/API/'.$token.'/V2/Sms';
            $type   = 'SMS';
            if(Configuration::get('EKO_SMS_LOWCOST'))
                $type = 'Low_Cost';
            $data   = array('Username' => $this->UserName, 'Message' => $this->msg, 'Telephones' => array($this->mobile), 'Type' => $type);
            $return = $this->dr_request($data, $url);
            if(!isset($return->Error))
                return true;
        }
        return false;
    }

    private function getAPIToken() {
        $time = time();
        if($time - Configuration::get('EKO_SMS_DATATOKEN') > 580) {
            $tk  = array('Username' => $this->UserName, 'Password' => $this->Password, 'Expires' => 10);
            $rtk = $this->dr_request($tk, 'https://direct100.inesting.com/API/V2/Auth');
            if(!isset($rtk->Token)) {
                return '';
            } else {
                Configuration::updateValue('EKO_SMS_TOKEN',     $rtk->Token);
                Configuration::updateValue('EKO_SMS_DATATOKEN', $time);
                $rtk = $rtk->Token;
            }
        } else {
            $rtk = Configuration::get('EKO_SMS_TOKEN');
        }
        return $rtk;
    }

    private function dr_request($data, $apiurl) {
        $sQuery = json_encode($data);
        $aContextData = array (
                            'method' => 'POST',
                            'header' => "Connection: close\r\n".
                            "Content-Type: application/json\r\n".
                            "Content-Length: ".strlen($sQuery)."\r\n",
                            'content'=> $sQuery );

        $sContext = stream_context_create(array('http' => $aContextData));
        $sResult = file_get_contents($apiurl, false, $sContext);
        return json_decode($sResult);
    }
}

?>