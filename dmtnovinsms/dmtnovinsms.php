 <?php
/**
 * @author DMT - Development Team
 * @copyright Copyright DMT - Development Team
 * @link www.prestatool.com
 */
if (!defined('_PS_VERSION_'))
    exit;
class DMTNovinSMS extends Module
{
    private $prefix;
    public function __construct()
    {
        $this->name          = 'dmtnovinsms';
        $this->tab           = 'others';
        $this->version       = '1.0';
        $this->author        = 'DMT - Development Team';
        $this->need_instance = 0;
        
        parent::__construct();
        
        $this->displayName      = $this->l('novin sms');
        $this->description      = $this->l('novin sms  web service');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
        $this->prefix           = 'DMTNOS';
    }
    
    public function install()
    {
        if (!$this->installConfig())
            return false;
        
        if (!$this->installDatabase())
            return false;
        
        if (!parent::install() OR !$this->registerHook('actionValidateOrder') OR !$this->registerHook('actionOrderStatusPostUpdate') OR !$this->registerHook('updateQuantity') OR !$this->registerHook('actionCustomerAccountAdd'))
            return false;
        
    }
    
    public function uninstall()
    {
        if (!$this->uninstallConfig())
            return false;
        
        if (!$this->unistallDatabase())
            return false;
        
        if (!parent::uninstall())
            return false;
    }
    
    protected function installConfig()
    {
        if (!Configuration::updateValue($this->prefix . 'USERNAME', '0') OR 
            !Configuration::updateValue($this->prefix . 'PASSWORD', '0') OR 
            !Configuration::updateValue($this->prefix . 'ADMINPHONE', '0') OR 
            !Configuration::updateValue($this->prefix . 'NEWORDERA', '1') OR 
            !Configuration::updateValue($this->prefix . 'NEWORDERC', '0') OR 
            !Configuration::updateValue($this->prefix . 'UPDATEORDERC', '1') OR 
            !Configuration::updateValue($this->prefix . 'NEWCUSTOMERA', '1') OR 
            !Configuration::updateValue($this->prefix . 'NEWCUSTOMERC', '0') OR 
            !Configuration::updateValue($this->prefix . 'NEWORDERFC' ,'0') OR 
            !Configuration::updateValue($this->prefix . 'NEWORDERFA' ,'0') ) 
            return false;
        
        return true;
    }
    
    protected function uninstallConfig()
    {
        if (!Configuration::deleteByName($this->prefix . 'USERNAME') OR 
            !Configuration::deleteByName($this->prefix . 'PASSWORD') OR 
            !Configuration::deleteByName($this->prefix, 'ADMINPHONE') OR 
            !Configuration::deleteByName($this->prefix . 'NEWORDERA') OR 
            !Configuration::deleteByName($this->prefix . 'NEWORDERC') OR 
            !Configuration::deleteByName($this->prefix . 'UPDATEORDERC') OR 
            !Configuration::deleteByName($this->prefix . 'NEWCUSTOMERA') OR 
            !Configuration::deleteByName($this->prefix . 'NEWCUSTOMERC') OR 
            !Configuration::deleteByName($this->prefix . 'NEWORDERFC' ,'0') OR 
            !Configuration::deleteByName($this->prefix . 'NEWORDERFA' ,'0')) 
            return false;
        
        return true;
    }
    
    protected function installDatabase()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . $this->name . '`( 
                `id_' . $this->name . '` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, 
                `status` VARCHAR(256) NOT NULL , 
                 `description` TEXT NOT NULL
                 )ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';
        return Db::getInstance()->execute($sql);
    }
    
    protected function unistallDatabase()
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . $this->name . '`';
        return Db::getInstance()->execute($sql);
    }
    
    public function getContent()
    {
        $keys   = array(
            'USERNAME',
            'PASSWORD',
            'ADMINPHONE',
            'NEWORDERA',
            'NEWORDERC',
            'UPDATEORDERC',
            'NEWCUSTOMERA',
            'NEWCUSTOMERC',
            'NEWORDERFC' ,
            'NEWORDERFA'
        );
        $output = '<h2>' . $this->displayName . '</h2>';
        //update settings
        if (Tools::isSubmit('updateSettings'))
         {
            foreach ($keys as $key) {
                if (!isset($_POST[$key]))
                    $_POST[$key] = 0;
            }
            
            foreach ($_POST as $key => $value) {
                if (!Configuration::updateValue($this->prefix . $key, $value)) {
                    $errors[] = Tools::displayError($this->l('Settings is not updated'));
                    break;
                }
            }
            if (isset($errors) && count($errors))
                $output .= $this->displayError(implode('<br />', $errors));
            else
                $output .= $this->displayConfirmation($this->l('Settings updated'));
        }
        elseif (Tools::isSubmit('SendSMS'))
        {
            $phones = Tools::getValue('reciver');
            if(strlen($phones)<11)
            $errors[] = Tools::displayError($this->l('insert valid phone number'));
            else
            {
                $text = Tools::getValue('text');
                require_once(_PS_MODULE_DIR_ . $this->name . '/classes/novinwebservice.php');
                $SMSNovin = new NovinWebService($this->prefix, Configuration::get($this->prefix . 'USERNAME'), Configuration::get($this->prefix . 'PASSWORD'));
                $result=$SMSNovin->sendOne($text,$phones,'private');
                if($result !='sent')
                $errors[]=Tools::displayError($result);
                
            }
            if (isset($errors) && count($errors))
                $output .= $this->displayError(implode('<br />', $errors));
                else
                $output .= $this->displayConfirmation($this->l('sent'));
        }
        elseif(Tools::isSubmit('SendCustomers'))
        {
           $text = Tools::getValue('textc');
           if (Tools::isEmpty($text))
           $errors[]=Tools::displayError($this->l('text is empty'));
           $phones=$this->getPhoneMobiles();
           
           require_once(_PS_MODULE_DIR_ . $this->name . '/classes/novinwebservice.php');
                $SMSNovin = new NovinWebService($this->prefix, Configuration::get($this->prefix . 'USERNAME'), Configuration::get($this->prefix . 'PASSWORD'));
                 $result=$SMSNovin->sendOne($text,$phones,'sendToCustomers');
                if($result !='sent')
                $errors[]=Tools::displayError($result);
                if (isset($errors) && count($errors))
                $output .= $this->displayError(implode('<br />', $errors));
                else
                $output .= $this->displayConfirmation($this->l('sent to customers'));
        }
        return $output . $this->displayForm();
        
    }
    
    protected function displayForm()
    {
        $phone_mobiles=$this->getPhoneMobiles(true);
        $return = '<fieldset>
            <legend><img src="' . $this->_path . 'views/img/settings.gif" alt="" title="" /> ' . $this->l('Settings') . '</legend>
            <form name="config" action="' . $_SERVER['REQUEST_URI'] . '" method="post">
                <label>' . $this->l('Username:') . '</label>
                <div class="margin-form"><input type="text" name="USERNAME" value="' . Configuration::get($this->prefix . 'USERNAME') . '" /><br />' . $this->l('ex: 3000XXXXXXX') . '</div>
                <br />
                <label>' . $this->l('Password:') . '</label>
                <div class="margin-form"><input type="text" name="PASSWORD" value="' . Configuration::get($this->prefix . 'PASSWORD') . '" /></div>
                <label>' . $this->l('Amin phone:') . '</label>
                <div class="margin-form"><input type="text" name="ADMINPHONE" value="' . Configuration::get($this->prefix . 'ADMINPHONE') . '" style="margin-bottom:10px;" /><br />' . $this->l('ex: 09123456789') . '</div>
                <br />
                <label>' . $this->l('Alerts on new order:') . '</label>
                <div class="margin-form"><span style="color:#000000; font-size:12px; margin-bottom:6px"><input type="checkbox" value="1" name="NEWORDERA" ' . (Configuration::get($this->prefix . 'NEWORDERA') == '1' ? 'checked' : '') . ' onclick="if(this.checked){document.config.alert_free_order.disabled=false;}else{document.config.alert_free_order.disabled=true;}"/>&nbsp;' . $this->l('admin') . '</span>
                <span style="color:#000000; font-size:12px; margin-bottom:6px"><input type="checkbox" value="1" name="NEWORDERC"  ' . (Configuration::get($this->prefix . 'NEWORDERC') == '1' ? 'checked="checked"' : '') . ' onclick="if(this.checked){document.config.alert_free_order.disabled=false;}else{document.config.alert_free_order.disabled=true;}"/>&nbsp;' . $this->l('customer') . '</span><div>' . $this->l('Send SMS if a new order is made') . '</div></div>
        
                <label>' . $this->l('Alerts on new free order:') . '</label>
                <div class="margin-form"><span style="color:#000000; font-size:12px; margin-bottom:6px"><input type="checkbox" value="1" name="NEWORDERFA" ' . (Configuration::get($this->prefix . 'NEWORDERFA') == '1' ? 'checked' : '') . ' onclick="if(this.checked){document.config.alert_free_order.disabled=false;}else{document.config.alert_free_order.disabled=true;}"/>&nbsp;' . $this->l('admin') . '</span>
                <span style="color:#000000; font-size:12px; margin-bottom:6px"><input type="checkbox" value="1" name="NEWORDERFC"  ' . (Configuration::get($this->prefix . 'NEWORDERFC') == '1' ? 'checked="checked"' : '') . ' onclick="if(this.checked){document.config.alert_free_order.disabled=false;}else{document.config.alert_free_order.disabled=true;}"/>&nbsp;' . $this->l('customer') . '</span><div>' . $this->l('Send SMS if a new order is free made') . '</div></div>';
        
        
        $return .= '<label>' . $this->l('Alerts on update order status:') . '</label>
                <div class="margin-form"><div style="color:#000000; font-size:12px; margin-bottom:6px"><input type="checkbox" value="1" name="UPDATEORDERC" ' . (Configuration::get($this->prefix . 'UPDATEORDERC') == '1' ? 'checked' : '') . ' onclick="if(this.checked){document.config.alert_update_free_order.disabled=false;}else{document.config.alert_update_free_order.disabled=true;}"/>&nbsp;' . $this->l('Yes') . '</div>' . $this->l('Send SMS to customer if an order status is changed') . '</div>
                <label>' . $this->l('Alerts on create new account:') . '</label>
                <div class="margin-form"><span style="color:#000000; font-size:12px; margin-bottom:6px"><input type="checkbox" value="1" name="NEWCUSTOMERA" ' . (Configuration::get($this->prefix . 'NEWCUSTOMERA') == '1' ? 'checked' : '') . ' onclick="if(this.checked){document.config.alert_free_order.disabled=false;}else{document.config.alert_free_order.disabled=true;}"/>&nbsp;' . $this->l('admin') . '</span>
                <span style="color:#000000; font-size:12px; margin-bottom:6px"><input type="checkbox" value="1" name="NEWCUSTOMERC" ' . (Configuration::get($this->prefix . 'NEWCUSTOMERC') == '1' ? 'checked' : '') . ' onclick="if(this.checked){document.config.alert_free_order.disabled=false;}else{document.config.alert_free_order.disabled=true;}"/>&nbsp;' . $this->l('customer') . '</span><div>' . $this->l('Send SMS if a new customer  is add to site') . '</div></div>
                
                <br />
                <div class="margin-form"><input class="button" name="updateSettings" value="' . $this->l('Update settings') . '" type="submit" /></div>
            </form>
        </fieldset>
        <br />
        <fieldset>
        <legend><img src="' . $this->_path . 'views/img/settings.gif" alt="" title="" /> ' . $this->l('Send sms') . '</legend>
            <form name="config" action="' . $_SERVER['REQUEST_URI'] . '" method="post">
            <label>' . $this->l('sent to:') . '</label>
                <div class="margin-form"><textarea name="reciver" cols="10" rows="5" ></textarea><br />' . $this->l('ex:09123456789') . '<br />'
                .$this->l('for send sms more than on number please saperate numbers with ; ex:09123456789;09101234567').'</div>
                <br />
                <label>' . $this->l('Text message:') . '</label>
                <div class="margin-form"><textarea  name="text"  ></textarea> </div>
                <br />
                <div class="margin-form"><input class="button" name="SendSMS" value="' . $this->l('Send sms') . '" type="submit" /></div>
            </form>
             </fieldset>
             <br />
             <fieldset>
        <legend><img src="' . $this->_path . 'views/img/settings.gif" alt="" title="" /> ' . $this->l('Send sms for customers') . '</legend>
            <form name="config" action="' . $_SERVER['REQUEST_URI'] . '" method="post">
               <div class="margin-form">  <label>' . $phone_mobiles.' '. $this->l('phone mobile(s) from customers is exist').
                '</div><br />
                <label>' . $this->l('Text message') . '</label>
                <div class="margin-form"><textarea  name="textc"  ></textarea> </div>
                <br />
                <div class="margin-form"><input class="button" name="SendCustomers" value="' . $this->l('Send sms') . '" type="submit" /></div>
            </form>
             </fieldset>
             <br />
             <fieldset style="text-align:center">
			<a href="http://www.prestatool.com" target="_blank">Designed By <span style="color:blue">DMT - Development Team</span></a>
			<a href="http://www.novinpayamak.com" target="_blank">Copy Right <span style="color:green">Novin Payamak</span></a>
            </fieldset><br />';
        return $return;
    }
    
    public function hookActionCustomerAccountAdd($params)
    {
        if (Configuration::get($this->prefix . 'USERNAME') == '0' OR Configuration::get($this->prefix . 'PASSWORD') == '0' OR Configuration::get($this->prefix . 'NEWCUSTOMERA') == 0 AND Configuration::get($this->prefix . 'NEWCUSTOMERC') == 0)
            return true;
        
        if (Configuration::get($this->prefix . 'NEWCUSTOMERA') != 0 AND Configuration::get($this->prefix . 'ADMINPHONE') == 0)
            return true;
        require_once(_PS_MODULE_DIR_ . $this->name . '/classes/novinwebservice.php');
        $SMSNovin = new NovinWebService($this->prefix, Configuration::get('DMTNOSUSERNAME'), Configuration::get($this->prefix . 'PASSWORD'));
        
        
        if (is_object($params['newCustomer']))
            $newCustomer = get_object_vars($params['newCustomer']);
        
        $firstName = $newCustomer['firstname'];
        $lastName  = $newCustomer['lastname'];
        
        
        $vars = array(
            '{firstname}' => $firstName,
            '{lastname}' => $lastName
        );
        if (Configuration::get($this->prefix . 'NEWCUSTOMERA') != 0) {
            if (!$text = $this->getText($vars, 'newacount', 'admin'))
                die('not file ');
            $SMSNovin->sendOne($text, Configuration::get($this->prefix . 'ADMINPHONE'), 'newAccount');
        }
        if(Configuration::get($this->prefix.'NEWCUSTOMERC')!=0 )
        {
            $id_address      = Address::getFirstCustomerAddressId($newCustomer['id']);
            $customerAddress = new AddressCore($id_address);
            $phone           = $customerAddress->phone_mobile;
            
            if (!$phone OR strlen($phone) < 10)
                return true;
            if (!$text = $this->getText($vars, 'newacount', 'customer'))
                die('not file ');
            
            $SMSNovin->sendOne($text, $phone, 'neworder');
        }
        
        return true;
        
    }
    
    public function hookActionValidateOrder($params)
    {
        if (Configuration::get($this->prefix . 'USERNAME') == '0' OR Configuration::get($this->prefix . 'PASSWORD') == '0' OR (Configuration::get($this->prefix . 'NEWORDERA') == 0 AND Configuration::get($this->prefix . 'NEWORDERC') == 0))
            return true;
        
        if (Configuration::get($this->prefix . 'NEWCUSTOMERA') != 0 AND Configuration::get($this->prefix . 'ADMINPHONE') == 0)
            return true;
        
        require_once(_PS_MODULE_DIR_ . $this->name . '/classes/novinwebservice.php');
        $SMSNovin = new NovinWebService($this->prefix, Configuration::get($this->prefix . 'USERNAME'), Configuration::get($this->prefix . 'PASSWORD'));
        
        
        $order    = $params['order'];
        $customer = $params['customer'];
        $currency = $params['currency'];
        $address  = new Address(intval($order->id_address_invoice));
        
        
        $vars = array(
            '{firstname}' => ($customer->firstname),
            '{lastname}' => ($customer->lastname),
            '{order_name}' => sprintf("%06d", $order->id),
            '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
            '{payment}' => $order->payment,
            '{total_paid}' => $order->total_paid,
            '{currency}' => $currency->name
        );
        
        if (Configuration::get($this->prefix . 'NEWORDERA') != 0 )
         {
            if($order->total_paid == 0 AND Configuration::get($this->prefix . 'NEWORDERFA') == 0 )
                return true;
                
            if (!$text = $this->getText($vars, 'neworder', 'admin'))
                die('not file ');
                
            $SMSNovin->sendOne($text, Configuration::get($this->prefix . 'ADMINPHONE'), 'neworder');
        }
        
        if (Configuration::get($this->prefix . 'NEWORDERC') != 0) 
        {
            if($order->total_paid == 0 AND Configuration::get($this->prefix . 'NEWORDERFC') == 0 )
                return true;
                
            $id_address      = Address::getFirstCustomerAddressId($customer->id);
            $customerAddress = new AddressCore($id_address);
            $phone           = $customerAddress->phone_mobile;
            
            if (!$phone OR strlen($phone) < 10)
                return true;
            if (!$text = $this->getText($vars, 'neworder', 'customer'))
                die('not file ');
            
            $SMSNovin->sendOne($text, $phone, 'neworder');
        }
            return true;
    }
    
    public function hookActionOrderStatusPostUpdate($params)
    {
        
        if (Configuration::get($this->prefix . 'USERNAME') == '0' OR Configuration::get($this->prefix . 'PASSWORD') == '0' OR (Configuration::get($this->prefix . 'NEWCUSTOMERA') == 0 AND Configuration::get($this->prefix . 'NEWCUSTOMERC') == 0))
            return true;
        
        if (Configuration::get($this->prefix . 'UPDATEORDERC') == 0)
            return true;
        
        require_once(_PS_MODULE_DIR_ . $this->name . '/classes/novinwebservice.php');
        $SMSNovin = new NovinWebService($this->prefix, Configuration::get('DMTNOSUSERNAME'), Configuration::get($this->prefix . 'PASSWORD'));
        
        $order      = new Order((int) ($params['id_order']));
        $orderstate = $params['newOrderStatus'];
        
        $customer = new Customer((int) $order->id_customer);
        $id_address = Address::getFirstCustomerAddressId($customer->id);
        $address  = new Address((int)($id_address));
        if ($address->phone_mobile == null)
            return true;
            
         if(strtolower($orderstate->name) == strtolower('Awaiting cheque payment') OR strtolower($orderstate->name) == strtolower('Awaiting bank wire payment'))
            return true;
        $vars = array(
            '{firstname}' => ($customer->firstname),
            '{lastname}' => ($customer->lastname),
            '{order_name}' => sprintf("%06d", $order->id),
            '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
            '{order_state}' => $orderstate->name
        );
        
        if (!$text = $this->getText($vars, 'updateorder', 'customer'))
            die('not file ');
        
        $SMSNovin->sendOne($text, $address->phone_mobile, 'updateorder');
        
        return true;
    }
    
    /** load text message
     * @param array vars varible to set in tamplate
     * @param string folder exists template
     * @param string folder exists txtfile nam
     * @return text message
     */
    private function getText($vars, $folder, $txtfile)
    {
        $language = Language::getIsoById((int) (Configuration::get('PS_LANG_DEFAULT')));
        $file     = dirname(__FILE__) . '/views/smstemp/' . $language . '/' . $folder . '/' . $txtfile . '.txt';
        
        if (!file_exists($file))
            return false;
        
        $tpl      = file($file);
        $template = str_replace(array_keys($vars), array_values($vars), $tpl);
        return (implode("", $template));
        
    }
    
    /** save logs to database 
     * @param string status
     * @param string logs form webservice
     * @return boolean 
     */
    public function saveLogs($status, $description)
    {
        $fields = array(
            'status' => pSQL($status),
            'description' => pSQL($description)
        );
        return Db::getInstance()->insert($this->name, $fields);
    }
   
     /** get phone mobile from customers
     * @return strings of phonemobiles
     */
    protected function getPhoneMobiles($getTotal=false)
    {
        //get mobiles
        if(!$getTotal)
        {
            $sql = new DbQueryCore();
            $sql->select('phone_mobile');
            $sql->from('address');
            $query = $sql->build();
        
            $results=Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
            if ($results and count($results)>0)
                foreach ($results as $result)
                {
                    if(!Tools::isEmpty($result['phone_mobile']))
                    $mobiles.=$result['phone_mobile'].';';
                }
            else
                return false;
                
                return trim($mobiles,';');
        }
        else
        {
            $sql= new DbQueryCore();
            $sql->select('count(*)');
            $sql->from('address');
            $sql->where('phone_mobile !=""');
            $query= $sql->build();
            return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
        }
        
    }
    
} 