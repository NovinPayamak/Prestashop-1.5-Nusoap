 <?php
/**
 * @author DMT - Development Team
 * @copyright Copyright DMT - Development Team
 * @link www.prestatool.com
 */
require_once(dirname(__FILE__) . '/../dmtnovinsms.php');
class NovinWebService
{
    public $prefix;
    public $username;
    public $password;
    private $module;
    private $sms;
    public function __construct($prefix, $username, $password)
    {
        $this->prefix   = $prefix;
        $this->username = $username;
        $this->password = $password;
        $this->module   = new DMTNovinSMS();
        $this->connectNovin();
    }
    public function connectNovin()
    {
        $this->sms = new nusoap_client('http://www.novinpayamak.com/services/SMSBox/wsdl', 'wsdl'); 
    }
    
    public function sendOne($text, $phonenumber, $status)
    {

        try {
            
				$result = $client->call('Send', array(
													array(
															'Auth' 	=> array('number' => $this->username,'pass' => $this->password),
															'Recipients' => array('string' => array($phonenumber)),
															'Message' => array('string' => array($text)),
															'Flash' => false
														)
													)
	);
			
			
        }
        catch (soap_fault $e) {
            return $e;
        }
        
        //handle error
       if (Validate::isInt($result['Status']) AND ($result['Status']) < 0 ) 
        {
            $error = $this->handleError((int) $result['Status']);
            
            $this->module->saveLogs($status, $error);
            return $error;
        } 
        else
        {
            $this->module->saveLogs($status, $result['Status']);
            return 'sent';
            
        }
    }
    
    protected function handleError($error)
    {
        switch ($error) {
            case -11:
                return $this->module->l('Information submitted is incomplete');
                break;
            
            case -22:
                return $this->module->l('webservice for this information is invalid or acount is expaired');
                break;
            
            case -33:
                return $this->module->l('Reputation is not enough virtual ports');
                break;
            
            case -44:
                return $this->module->l('Amount is not sufficient to account');
                break;
            
            case -55:
                return $this->module->l('vTexting problem occurred when connecting to a dispatch center');
                break;
            
            case -66:
                return $this->module->l('A problem has occurred in completion');
                break;
            
            default:
                return $this->module->l('Unkonwn problem :'.$error);
                break;
                
        }
    }
} 
