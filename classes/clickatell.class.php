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

class apiClickatell extends ObjectModel
{
    public $msg;
    public $UserName;
    public $Password;
    public $mobile;

    public function __construct($params) {
        $this->UserName = $params['username'];
        $this->Password = $params['password'];
        $this->msg      = htmlspecialchars($params['text']);
        $this->mobile   = str_replace('+','',$params['to']);
    }

    public function SMSsend() {
        $token  = $this->getAPIToken();
        if(!empty($token)) {
            $url    = 'https://api.clickatell.com/http/sendmsg';
            $data   = array('session_id' => $token, 'text' => $this->msg, 'to' => $this->mobile);
            $return = $this->dr_request($data, $url);
            $send   = explode(":",$return);
            if($send[0] == "ID")
                return true;
        }
        return false;
    }

    private function getAPIToken() {
        $time    = time();
        $sess_id = '';
        if($time - Configuration::get('EKO_SMS_DATATOKEN') > 800) {
            $tk  = array('user' => $this->UserName, 'password' => $this->Password, 'api_id' => Configuration::get('EKO_SMS_APIID'));
            $rtk = $this->dr_request($tk, 'https://api.clickatell.com/http/auth');
            $sess = explode(":",$rtk);
            if ($sess[0] == "OK")
                $sess_id = trim($sess[1]);

            if(empty($sess_id)) {
                return '';
            } else {
                Configuration::updateValue('EKO_SMS_TOKEN',     $sess_id);
                Configuration::updateValue('EKO_SMS_DATATOKEN', $time);
                $rtk = $sess_id;
            }
        } else {
            $rtk = Configuration::get('EKO_SMS_TOKEN');
        }
        return $rtk;
    }

    private function dr_request($data, $apiurl) {
        $sQuery = http_build_query($data);
        /* Isto é um atalho, mas o que eu sei de PHP não chega para fazer uma alteração melhor
           Deve substituir 351931234567 pelo número de telemóvel registado no clickatell de forma
           a poder aparecer o número correto do remetente e não um número +447781470020668 ou outro
        */
        $sQuery = $sQuery . '&from=351931234567&set_mobile_originated=1'
        $aContextData = array (
                            'method' => 'POST',
                            'header' => "Connection: close\r\n".
                            "Content-Type: application/x-www-form-urlencoded\r\n".
                            "Content-Length: ".strlen($sQuery)."\r\n",
                            'content'=> $sQuery );

        $sContext = stream_context_create(array('http' => $aContextData));
        $sResult = file_get_contents($apiurl, false, $sContext);
        return ($sResult);
    }
}
?>
