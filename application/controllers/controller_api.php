<?
class Controller_VIMApi extends Controller{
    
    
    private function access() {
        $access_key = '';
        if(isset($_POST['access_key'])){
            $access_key = $_POST['access_key'];
            unset($_POST['access_key']);
        }

        if (
            isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'
        ) {
            $this->folder_log = FILESROOT . '/log/s/'.date('Y')."/".date('m')."/".date('d'); 
            if(!file_exists($this->folder_log)) mkdir($this->folder_log, 0777, true);
            $this->log_file_auth = $this->folder_log . "/log_" . date('d_m_Y') . ".txt";
            file_put_contents($this->log_file_auth, 
                "s".
                " | IP: " . $_SERVER['REMOTE_ADDR'] . 
                " | Time: " . $this->time_str . 
                " | URI: " . REQUEST_URI . 
                " | Data: " . print_r($_POST,1) .PHP_EOL
            ,FILE_APPEND);
            return true;
        } elseif (''!=$access_key && isset($this->access_token[$access_key])) {
            $this->client = $this->access_token[$access_key];
            $this->client_token = $access_key;
            $this->folder_log = FILESROOT . "/log/".$this->client['name'].'/'.date('Y')."/".date('m')."/".date('d').(isset($_POST['id_si'])&&is_numeric($_POST['id_si'])?'/'.$_POST['id_si']:''); 
            if(!file_exists($this->folder_log)) mkdir($this->folder_log, 0777, true);
            
            $this->log_file_auth = $this->folder_log . "/log" . date('d_m_Y') . ".txt";
            
            file_put_contents($this->log_file_auth, 
                "Client: " . $this->client['name'] . 
                " | IP: " . $_SERVER['REMOTE_ADDR'] . 
                " | Time: " . $this->time_str . 
                " | URI: " . REQUEST_URI . 
                " | Data: " . print_r($_POST,1) .PHP_EOL
            , FILE_APPEND);
            
            if (isset($this->client['ip'])&&$_SERVER['REMOTE_ADDR'] && !in_array($_SERVER['REMOTE_ADDR'],$this->client['ip'])) {
                $this->answerMsg=$this->answer='Access denied for this IP.';
                file_put_contents($this->log_file_auth, "Client: " . $this->client['name'] .' | Error IP.'.PHP_EOL, FILE_APPEND);
                return false;
            }elseif (isset($this->client['method'],$this->urlExp[3]) && !in_array($this->urlExp[3],$this->client['method'])) {
                $this->answerMsg=$this->answer='Forbidden Method "'.$this->urlExp[3].'".';
                file_put_contents($this->log_file_auth, "Client: " . $this->client['name'] .' | Forbidden Method "'.$this->urlExp[3].'".'.PHP_EOL, FILE_APPEND);
                return false;
            }
            
            return true;
        } else {
            $this->folder_log = FILESROOT . "/log/not_identity/".date('Y')."/".date('m')."/".date('d'); 
            if(!file_exists($this->folder_log)) mkdir($this->folder_log, 0777, true);
            $this->log_file_auth = $this->folder_log . "/log" . date('d_m_Y') . ".txt";
        
            $this->answerMsg=$this->answer='Signature verification failed';
            file_put_contents($this->log_file_auth, 
                "Error Verification" . 
                " | IP: " . $_SERVER['REMOTE_ADDR'] . 
                " | Time: " . $this->time_str . 
                " | URI: " . REQUEST_URI . 
                " | Data: " . print_r($_POST,1) .PHP_EOL
            , FILE_APPEND);
            return false;
        }
    }
    
    public function __construct(){
        $this->model = new Model();
        $this->view = new View();
        $this->time = time();
        $this->time_str = strftime('%k:%M:%S', $this->time);
        $this->view->time_str = strftime('%d.%m.%Y %k:%M', $this->time);
        
        if (preg_match("/%3C|%3E|%27|%22|\(\)/",$_SERVER['REQUEST_URI'])) {
            $this->answerMsg='Invalid Request';return;
        }
        
        $this->url=parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        $this->urlExp=explode('/',$this->url)+array(null,null,null,null,null,null);
        
        if(!$this->access()) return;
        
        

        if(empty($this->urlExp[2])||!in_array($this->urlExp[2],array('json','xml','html','text'))){
            $this->answerMsg='wrong answer';return;
        }

        $this->tr = $this->urlExp[2];

        $access = $this->access();
        
        if(!$access){
            return;
        }elseif(empty($this->urlExp[3])){
            $this->answerMsg='wrong type';
            unset($this->tr); return;
        }elseif(!method_exists('Controller_api', $this->urlExp[3])){
            $this->answerMsg='method "'.$this->urlExp[3].'" not found';
            unset($this->tr); return;
        }

        $this->limit=!empty($_GET['limit'])&&is_numeric($_GET['limit'])?$_GET['limit']:10;

        $method = $this->urlExp[3];
        $this->$method($this->urlExp[4],$this->urlExp[5]);
        $this->out();
    }
    public function __destruct(){
        if(!$this->out) $this->out();
    }
    public function out(){
        $this->out=true;
        header('Content-Type: text/plain; charset=utf-8');
        if(isset($this->tr)&&'xml'==$this->tr){
            header ("Content-Type:text/xml;");
            $xml = '<?xml version="1.0" encoding="utf-8"?>
<dataset xmlns="http://www.google.com/schemas/sitemap/0.84"
                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                xsi:schemaLocation="http://www.google.com/schemas/sitemap/0.84 http://www.google.com/schemas/sitemap/0.84/sitemap.xsd">';

            $xml .= "<type>".$this->answerType."</type>";
            if(isset($this->answerLength)&&$this->answerLength>0)
                $xml .= "<length>".$this->answerLength."</length>";
            if(isset($this->answerMsg)&&$this->answerMsg!='')
                $xml .= "<msg>".$this->answerMsg."</msg>";


            if($this->answerType=='success'&&count($this->answer)>0&&$this->answerLength!=null){
                $xml .= "<answer>";
                if(is_array($this->answer)){ 
                    foreach ($this->answer as $key_i=>$item){
                        $xml .= '<item id="'.$key_i.'">';
                        if(count($item)>0){
                            if(is_array($item)){ 
                                foreach ($item as $key=>$row){
                                    if(is_array($row)&&count($row)>0){
                                        $xml .= "<".$key.">";
                                        foreach ($row as $key_row=>$row_row){
                                            if(is_numeric($key_row)) {
                                                $key_row = $key.'_'.$key_row;
                                            }
                                            $xml .= "<".$key_row.">".$row_row."</".$key_row.">";
                                        }
                                        $xml .=  "</".$key.">";
                                    }else{
                                        $xml .= "<".$key.">".$row."</".$key.">";
                                    }
                                }
                            }else{
                                $xml .= $item;
                            }
                        }
                        $xml .=  "</item>";
                    }
                }else{
                    $xml .= "<item>".$this->answer."</item>";
                }
                $xml .= "</answer>";
            }
            elseif($this->answerType=='success'&&count($this->answer)>0){
                $xml .= "<answer>";
                foreach ($this->answer as $key_i=>$val){
                    $xml .= '<'.$key_i.'>'.$val."</".$key_i.">";
                }
                $xml .= "</answer>";
            }

            $xml .= '</dataset>';
            $xml = iconv("UTF-8", "UTF-8//IGNORE",$xml);
            echo $xml;
        }
        elseif(isset($this->tr)&&'html'==$this->tr){
            header('Content-Type: text/html; charset=utf-8');
            $html = '';
            if(isset($this->answer)&&!is_array($this->answer))
                echo $this->answer;
            elseif(isset($this->answer)){ 
                echo '<p>Error<p>'; 
                print_pre($this->answer);
            }
        }
        elseif(isset($this->tr)&&'json'==$this->tr){
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array(
                'answer'=>isset($this->answer)?$this->answer:null,
                'length'=>isset($this->answerLength)?$this->answerLength:null,
                /*'log'=>isset($this->answerLog)?$this->answerLog:null,
                'debug'=>isset($this->answerDebug)?$this->answerDebug:null,
                'js'=>isset($this->answerJs)?$this->answerJs:null,*/
                'type'=>isset($this->answerType)?$this->answerType:null,
                'msg'=>isset($this->answerMsg)?$this->answerMsg:null
                ));
        }
        elseif(isset($this->tr)&&'text'==$this->tr){
            echo '<pre>';
            echo 'answerType:';
            var_dump(isset($this->answerType)?$this->answerType:null);
            echo '<br>';
            echo 'answerMsg:';
            var_dump(isset($this->answerMsg)?$this->answerMsg:null);
            echo '<br>';
            echo 'answerDebug:';
            var_dump(isset($this->answerDebug)?$this->answerDebug:null);
            echo '<br>';
            echo 'answer:';
            print_r(isset($this->answer)?$this->answer:null);
            echo '<br>';
            echo 'answerLog:';
            var_dump(isset($this->answerLog)?$this->answerLog:null);
            echo '<br>';
            echo 'answerLength:';
            var_dump(isset($this->answerLength)?$this->answerLength:null);
            echo '</pre>';
        }
        else{
            header('Status: 404 Not Found');
            header('HTTP/1.1 404 Not Found');
            echo $this->answerMsg;
        }
    }
    
    protected function blockchain_send(){
        if(is_array($_POST)) extract(TrimArray($_POST));
        
        require_once(homeRoot . '/vendor/autoload.php');

        $Blockchain = new \Blockchain\Blockchain($api_code);
        $Blockchain->setServiceUrl('http://localhost:3000');
        if(is_null($wallet_guid) || is_null($wallet_pass)) {
            echo "Please enter a wallet GUID and password in the source file.<br/>";
            exit;
        }
        $Blockchain->Wallet->credentials($wallet_guid, $wallet_pass);

        try {
            
            $answer=array();
            if(1==$TestMode){
                $answer=array("TestMode"=>1);
            }else{
                $answer=$Blockchain->Wallet->send($address, $sum, null,"0.00001");
            }

            file_put_contents(FILESROOT . "/log/".date("d_m_Y").".txt", "Time: " . strftime('%k:%M:%S', time()) ." | Blockchain_OPERATION: \n".print_r($answer,1)."\n",FILE_APPEND);
            $this->answer=$answer;
            $this->answerType='success';
        } catch (\Blockchain\Exception\ApiError $e) {
            file_put_contents(FILESROOT . "/log/".date("d_m_Y").".txt", "Time: " . strftime('%k:%M:%S', time()) ." | Blockchain_OPERATION: \n".$e->getMessage()."\n",FILE_APPEND);
            $this->answerType='error';
            $this->answerMsg='Payment not send';
            
        }
    }
    
    protected function get_rate(){
        if(is_array($_POST)) extract(TrimArray($_POST));
        require_once(homeRoot . '/vendor/autoload.php');

        $api_code = null;

        $Blockchain = new \Blockchain\Blockchain($api_code);
//        $Blockchain->setServiceUrl('http://localhost:3000');
        $rates = $Blockchain->Rates->get();
        if(is_array($rates)){
            $this->answer=$rates["EUR"];
            $this->answerType='success';
        }else{
            $this->answerType='error';
            $this->answerType='Rates not available';
        }
    }

    protected function save_request() {
        $data = TrimArray($_POST);
        if (isset($data['id_request']) && $this->model->UpdateRequest($data,$data['id_request'])) {
            $this->answer = '';
            $this->answerType = 'success';
        }elseif (isset($data['id']) && $this->model->UpdateRequest($data,$data['id'],'id')) {
            $this->answer = '';
            $this->answerType = 'success';
        } else {
            $this->answer = '';
            $this->answerType = 'error';
            $this->answerMsg = 'Request Not Found';
        }
    }
   
    protected function save_data() {
        $data = TrimArray($_POST);
        if (isset($data['id']) && $this->model->UpdateUser($data,$data['id'])) {
            $this->answer = '';
            $this->answerType = 'success';
        } else {
            $this->answer = '';
            $this->answerType = 'error';
            $this->answerMsg = 'User Not Found';
        }
    }
    
    protected function save_logs(){ 
        if(is_array($_POST)) extract(TrimArray($_POST));
        
        if( 
            isset($_FILES['log_files'],$id_terminal) && is_numeric($id_terminal) && !empty($id_terminal)
        ){
            if($this->answer = $this->model->saveZipLogFiles(
                $_FILES['log_files'],
                $id_terminal
            )){
                $this->answerType='success';
            }else {
                $this->answerType='error';
                $this->answerMsg='Cant move files';
            }
        }else{
            $this->answerType='error';
            $this->answerMsg='Wrong data';
        }
    }
  
    protected function login(){
        if(is_array($_POST)) extract(TrimArray($_POST));
        if($id_user=$this->model->get_user_app($login,$password)){
            $token = $this->model->gen_user_token($id_user,$id_terminal);
            if($this->model->update_user_token($id_user,$token,$client_ip,$browser,$browser_info)){
                $this->answer=$token;
                $this->answerType='success';
            }else{
                $this->answerType='error';
                $this->answerMsg='Error update user token';
            }
        }else{
            $this->answerType='error';
            $this->answerMsg='Invalid user';
        }
    }

    protected function reg(){
        if(is_array($_POST)) extract(TrimArray($_POST));
        if(strlen($login)>0 and strlen($password)>0){
            if(!$id_user=$this->model->check_user_app($login)){
                if(strcmp($password, $repassword)==0){
                    if($id_user=$this->model->create_user_app($login,$password)){
                        $token = $this->model->gen_user_token($id_user,$id_terminal);
                        if($this->model->update_user_token($id_user,$token,$client_ip,$browser,$browser_info)){
//                            require_once(LIBROOT.'/ethereum.php');
//                            $eth = new Ethereum('127.0.0.1', 8545);
                            $passphrase=md5($token.time());
                            if($new_wallet=call_node($method,array("passphrase"=>$passphrase))){
                                if($id_wallet=$this->model->add_new_wallet($new_wallet->answer->wallet,$passphrase,$token)){
                                    $this->answer=$token;
                                    $this->answerType='success';
                                }else{
                                    $this->answerType = 'error';
                                    $this->answerMsg = 'Wallet not save';
                                }
                            }else{
                                $this->answerType = 'error';
                                $this->answerMsg = 'Wallet not create';
                            }
                        }else{
                            $this->answerType='error';
                            $this->answerMsg='Error update user token';
                        }
                    }else{
                        $this->answerType='error';
                        $this->answerMsg='Error create user';
                    }
                }else{
                    $this->answerType='error';
                    $this->answerMsg='Password is different';
                }
            }else{
                $this->answerType='error';
                $this->answerMsg='User exists';
            }
        }else{
            $this->answerType='error';
            $this->answerMsg='Login or password is empty';
        }
    }
    
    protected function usertoken(){
        if(is_array($_POST)) extract(TrimArray($_POST));
        if($id_user=$this->model->get_user_by_token($token)){
            $this->answer=$id_user;
            $this->answerType='success';
        }else{
            $this->answerType='error';
            $this->answerMsg='Invalid user';
        }
    }
    
    protected function userchangelog(){
        if(is_array($_POST)) extract(TrimArray($_POST));
        if($id_user=$this->model->get_user_by_token($token)){
            if(isset($login) && $login){
                if(!$isset_user_id=$this->model->get_user_by_login($login)){
                    $upd_user=array(
                        "email"=>$login,
                        "id_user"=>$id_user
                    );
                    $upd=$this->model->update_user($upd_user);
                    if($upd){
                        $this->answerType = 'success';
                        $this->answerMsg = $login;
                    }else{
                        $this->answerType = 'error';
                        $this->answerMsg = 'Wrong update';
                    }
                }else{
                    if($id_user==$isset_user_id){
                        $this->answerType = 'error';
                        $this->answerMsg = 'Login is the same with the previous login';
                    }else{
                        $this->answerType = 'error';
                        $this->answerMsg = 'Login already exists';
                    }
                }
            }else{
                $this->answerType = 'error';
                $this->answerMsg = 'Wrong login';
            }
        }
    }
    
    protected function userchangepass(){
        if(is_array($_POST)) extract(TrimArray($_POST));
        if($id_user=$this->model->get_user_by_token($token)){
            if(isset($password) && $password){
                    $upd_user=array(
                        "password"=>$password,
                        "id_user"=>$id_user
                    );
                    $upd=$this->model->update_user($upd_user);
                    if($upd){
                        $this->answerType = 'success';
                        $this->answerMsg = 'Password updated';
                    }else{
                        $this->answerType = 'error';
                        $this->answerMsg = 'Wrong update';
                    }
                
            }else{
                $this->answerType = 'error';
                $this->answerMsg = 'Wrong login';
            }
        }
    }
    
    protected function accnew(){
        if(is_array($_POST)) extract(TrimArray($_POST));
        $passphrase=md5($token.time());
        if($new_wallet=call_node($method,array("pwd"=>$passphrase))){
            if($id_wallet=$this->model->add_new_wallet($new_wallet->answer->wallet,$passphrase,$token)){
                $this->answer=$new_wallet->answer->wallet;
                $this->answerType = 'success';
                $this->answerMsg = '';
            }else{
                $this->answerType = 'error';
                $this->answerMsg = 'Wallet not save';
            }
        }else{
            $this->answerType = 'error';
            $this->answerMsg = 'Wallet not create';
        }
    }
    

    protected function newwaltosell(){
        if(is_array($_POST)) extract(TrimArray($_POST));
//        require_once(LIBROOT.'/ethereum.php');
//        $eth = new Ethereum('127.0.0.1', 8545);
        
        $return_acc=array();
        $passphrase=md5(time());
        if($new_wallet=call_node($method,array("pwd"=>$passphrase))){
            if($id_wallet=$this->model->add_new_wallet_for_sell($new_wallet->answer->wallet,$passphrase,$id_operation)){
                $this->answer=$new_wallet->answer->wallet;
                $this->answerType = 'success';
                $this->answerMsg = '';
            }else{
                $this->answerType = 'error';
                $this->answerMsg = 'Wallet not save';
            }
        }else{
            $this->answerType = 'error';
            $this->answerMsg = 'Wallet not create';
        }
    }
    
    protected function newvaltobuyprod(){
        if(is_array($_POST)) extract(TrimArray($_POST));
//        require_once(LIBROOT.'/ethereum.php');
//        $eth = new Ethereum('127.0.0.1', 8545);
        
        $return_acc=array();
        $passphrase=md5(time());
        if($new_wallet=call_node('accnew',array("passphrase"=>$passphrase))){
            if($id_wallet=$this->model->add_new_wallet_for_buy_product($new_wallet->answer->wallet,$passphrase,$id_order)){
                $this->answer=$new_wallet->answer->wallet;
                $this->answerType = 'success';
                $this->answerMsg = '';
            }else{
                $this->answerType = 'error';
                $this->answerMsg = 'Wallet do not save';
            }
        }else{
            $this->answerType = 'error';
            $this->answerMsg = 'Wallet do not create';
        }
    }
    
    protected function wallets(){
        if(is_array($_POST)) extract(TrimArray($_POST));
        $arr = array();
//        require_once(LIBROOT.'/ethereum.php');
//        $eth = new Ethereum('127.0.0.1', 8545);
        
        $return_acc=array();
//        $accounts=$eth->eth_accounts();
        $accounts=$this->model->get_wallet_address_by_user($token);
        
//        $arr_wall=array();
//        foreach ($accounts as $account){
//            $arr_wall[]=$account["wallet"];
//        }
        if(count($accounts)>0){
            foreach ($accounts as $account){ //$addr->answer->addr_res->{$account["wallet"]}->balance
                $return_acc[$account["wallet"]]=array("wallet"=>$account["wallet"],"balance"=>$account['balance'],"alias"=>$account["alias"],"name"=>"FOX","type"=>"fox");
            }
            $this->answer=$return_acc;
            $this->answerType = 'success';
//            $this->answerMsg = $accounts;
        }else{
            $this->answerType = 'error';
            $this->answerMsg = "No wallets for current token";
        }
    }
    
    protected function uxcwall(){
        if(is_array($_POST)) extract(TrimArray($_POST));
        $arr = array();
        $return_acc=array();
        $accounts=$this->model->get_wallet_address_by_user($token);
        foreach ($accounts as $account){
            $return_acc[$account["wallet"]]=array("wallet"=>$account["wallet"],"balance"=>$account['balance_uxc'],"alias"=>$account["alias"],"name"=>"UXC","type"=>"uxc");
        }
        $this->answer=$return_acc;
        $this->answerType = 'success';
        $this->answerMsg = '';
    }
    
    protected function wallbalance(){
        if(is_array($_POST)) extract(TrimArray($_POST));
        $arr = array();
//        require_once(LIBROOT.'/ethereum.php');
//
//        $eth = new Ethereum('127.0.0.1', 8545);
        if(isset($wallet)&&$wallet){
//            $balance=$eth->eth_getBalance($wallet,'latest',1);
            $this->answer=$balance;
            $this->answerType = 'success';
            $this->answerMsg = '';
        }else{
            $this->answerType = 'error';
            $this->answerMsg = 'Missing wallet';
        }
    }
    
    protected function uxcwallbalance(){
        if(is_array($_POST)) extract(TrimArray($_POST));
        if(isset($wallet)&&$wallet){
            $balance=$this->model->get_uxc_wallet_balance($wallet);
            $this->answer=array("balances"=>array("uxc"=>$balance));
            $this->answerType = 'success';
            $this->answerMsg = '';
        }else{
            $this->answerType = 'error';
            $this->answerMsg = 'Missing wallet';
        }
    }
    
    protected function txlist(){
//        ini_set('precision',30);
        if(is_array($_POST)) extract(TrimArray($_POST));
        $arr = array();
//        require_once(LIBROOT.'/ethereum.php');
//        $eth = new Ethereum('127.0.0.1', 8545);
        $return_txn=array();
//        $accounts=$eth->eth_accounts();
        $accounts=$this->model->get_wallet_address_by_user($token);
//        $block=6775;
        $wallets=array();
        foreach ($accounts as $account){
            $wallets[]=$account['wallet'];
        }
        $transactions=$this->model->get_transactions("'".implode("','",$wallets)."'",$page,$limit);
        foreach ($transactions['res'] as $transaction) {
            $return_txn[]=array("hash"=>$transaction['hash'],"from"=>$transaction['wallet_from'],"to"=>$transaction['wallet_to'],"amount"=>$transaction['amount'],"type"=>$transaction['type'],"date"=>time_notify($transaction['time_send']),"fee"=>$transaction['fee'],"fee_blockchain"=>$transaction['fee_blockchain'],"currency"=>"FOX","status"=>$transaction['status']);
        }
        
        $this->answer=array("transactions"=>$return_txn,"transactions_count"=>$transactions['cnt']);
        $this->answerType = 'success';
        $this->answerMsg = '';
    }
    
    protected function txlistuxc(){
        if(is_array($_POST)) extract(TrimArray($_POST));
        $arr = array();
        $return_txn=array();
        $accounts=$this->model->get_wallet_address_by_user($token);
        $wallets=array();
        foreach ($accounts as $account){
            $wallets[]=$account['wallet'];
        }
        $transactions=$this->model->get_uxc_transactions("'".implode("','",$wallets)."'",$page,$limit);
        foreach ($transactions['res'] as $transaction) {
            $return_txn[]=array("hash"=>$transaction['hash'],"from"=>$transaction['wallet_from'],"to"=>$transaction['wallet_to'],"amount"=>$transaction['amount'],"type"=>$transaction['type'],"date"=>time_notify($transaction['time_send']),"fee"=>$transaction['fee'],"fee_blockchain"=>$transaction['fee_blockchain'],"currency"=>"UXC","status"=>$transaction['status']);
        }
        
        $this->answer=array("transactions"=>$return_txn,"transactions_count"=>$transactions['cnt']);
        $this->answerType = 'success';
        $this->answerMsg = '';
    }
    
    protected function txlistalluxc(){
        if(is_array($_POST)) extract(TrimArray($_POST));
        $arr = array();
        $return_txn=array();
        
        $transactions=$this->model->get_uxc_transactions("all",$page,$limit);
        foreach ($transactions['res'] as $transaction) {
            $return_txn[]=array("hash"=>$transaction['hash'],"from"=>$transaction['wallet_from'],"to"=>$transaction['wallet_to'],"amount"=>$transaction['amount'],"type"=>$transaction['type'],"date"=>time_notify($transaction['time_send']),"fee"=>$transaction['fee'],"fee_blockchain"=>$transaction['fee_blockchain'],"currency"=>"UXC","status"=>$transaction['status']);
        }
        
        $this->answer=array("transactions"=>$return_txn,"transactions_count"=>$transactions['cnt']);
        $this->answerType = 'success';
        $this->answerMsg = '';
    }
    
    protected function walltx(){
        if(is_array($_POST)) extract(TrimArray($_POST));
        $arr = array();
        $return_txn=array();
        if($wallet!=''){
            $wallets=array($wallet);
            $transactions=$this->model->get_transactions("'".implode("','",$wallets)."'",$page,$limit);
            if(!$transactions['cnt'])$transactions['cnt']=0;
            foreach ($transactions['res'] as $transaction) {
//                $type_transaction=(strcmp($wallet, $transaction['wallet_from'])==0?"out":"in");
                $return_txn[]=array("hash"=>$transaction['hash'],"from"=>$transaction['wallet_from'],"to"=>$transaction['wallet_to'],"amount"=>(strcmp($account['wallet'], $transaction['wallet_from'])==0?"":"").$transaction['amount'],"type"=>$transaction['type'],"date"=>time_notify($transaction['time_send']),"fee"=>$transaction['fee'],"fee_blockchain"=>$transaction['fee_blockchain'],"currency"=>"FOX","status"=>$transaction['status']);
            }
            $this->answer=array("transactions"=>$return_txn,"transactions_count"=>$transactions['cnt']);
            $this->answerType = 'success';
            $this->answerMsg = "";
        }else{
            $this->answerType = 'error';
            $this->answerMsg = 'Missing wallet';
        }
    }
    
    protected function createtx(){
        ini_set('precision',30);
        if(is_array($_POST)) extract(TrimArray($_POST));
        $arr = array();
//        require_once(LIBROOT.'/ethereum.php');
//        $eth = new Ethereum('127.0.0.1', 8545);
        $return_txn="";
        if(isset($from)&&$from){
            if(isset($to) && $to){
                if(isset($amount) && $amount>0){
//                    $gas="0x".convert_dechex(200000);
                    $amount=floatval($amount);
                    try{
//                        $num=convert_dechex($eth->toWei($amount));
                        $value="0x".$num;
                        $arr_tx=array(
                            "from"=>$from,
                            "to"=>$to,
                            "amount"=>$amount
                        );
//                        $transaction=new Ethereum_Transaction($from, $to, $gas, 0, $value, $data);
                        $this->folder_log = FILESROOT . '/log/'.date('Y')."/".date('m')."/".date('d'); 
                        if(!file_exists($this->folder_log)) mkdir($this->folder_log, 0777, true);
                        $this->log_file = $this->folder_log . "/log_transaction_" . date('d_m_Y') . ".txt";
                        file_put_contents($this->log_file,
                            "Transaction".
                            " | IP: " . $_SERVER['REMOTE_ADDR'] . 
                            " | Time: " . $this->time_str . 
                            " | Data: " . print_r($arr_tx,1) .PHP_EOL
                        ,FILE_APPEND);
                        $arr_tx["pass"]=$this->model->get_passphrase($from);
                        $new_txn=call_node($method,$arr_tx);
                        if(isset($new_txn->answer->txhash) && $new_txn->answer->txhash!=''){
                            $tx_arr=array(
                                "hash"=>$new_txn->answer->txhash,
                                "wallet_from"=>$from,
                                "wallet_to"=>$to,
                                "amount"=>$amount,
                                "fee"=>$new_txn->answer->fee,
                                "fee_blockchain"=>$new_txn->answer->fee_blockchain,
                                "time_send"=>$this->time,
                                "status"=>1,
                            );
                            $tx_id=$this->model->save_transaction($tx_arr);
                            $add_wallets=array($from,$to);
                            $add_to_upd=$this->model->add_wallet_to_update($add_wallets);
                            

                            $wallet_to_info=dbSel(DB_PREFIX."wall","wallet='".$to."'","id_wallet,project,id_operation,id_order");
                            if($wallet_to_info["project"]=="1"){
                                $param_for_sell=array(
                                    "id_operation"=>$wallet_to_info["id"],
                                    "wallet"=>$to,
                                    "tx_hash"=>$new_txn->answer->txhash,
                                    "sum"=>$amount,
                                    "time_payment"=>$this->time,
                                    "status_request"=>1,
                                );

                                
                                $this->folder_log = FILESROOT . '/log/s/'.date('Y')."/".date('m')."/".date('d'); 
                                if(!file_exists($this->folder_log)) mkdir($this->folder_log, 0777, true);
                                $this->log_file_adbtc = $this->folder_log . "/log" . date('d_m_Y') . ".txt";
                                file_put_contents($this->log_file_adbtc, 
                                    "WEB".
                                    " | IP: " . $_SERVER['REMOTE_ADDR'] . 
                                    " | Time: " . $this->time_str . 
                                    " | URI: " . REQUEST_URI . 
                                    " | Data: " . print_r($_POST,1) .
                                    " | Answer: " . print_r($result,1) . PHP_EOL
                                ,FILE_APPEND);
                            }elseif($wallet_to_info["project"]=="2"){
                                $param_for_sell=array(
                                    "id_order"=>$wallet_to_info["id"],
    //                                "wallet"=>$to,
                                    "tx_hash"=>$new_txn->answer->txhash,
                                    "total"=>$amount,
                                    "time_payment"=>$this->time,
                                    "status"=>2,
                                );

                                $this->folder_log = FILESROOT . '/log/'.date('Y')."/".date('m')."/".date('d'); 
                                if(!file_exists($this->folder_log)) mkdir($this->folder_log, 0777, true);
                                $this->log_file_adbtc = $this->folder_log . "/log" . date('d_m_Y') . ".txt";
                                file_put_contents($this->log_file_adbtc, 
                                    "WEB".
                                    " | IP: " . $_SERVER['REMOTE_ADDR'] . 
                                    " | Time: " . $this->time_str . 
                                    " | URI: " . REQUEST_URI . 
                                    " | Data: " . print_r($_POST,1) .
                                    " | Answer: " . print_r($result,1) . PHP_EOL
                                ,FILE_APPEND);
                            }

                            if(isset($new_txn->answer->txhash) && $new_txn->answer->txhash!=''){
                                $this->answer=array("txhash"=>$new_txn->answer->txhash);
                                $this->answerType = 'success';
                                $this->answerMsg = 'Send ok';
                            }else{
                                $this->answerType = 'error';
                                $this->answerMsg = 'Wrong send';
                            }
                        }else{
                            $this->answerType = $new_txn->type;
                            $this->answerMsg = $new_txn->msg;
                        }
                    }
                    catch (RPCException $e){
                        $this->answerType = 'error';
                        $this->answerMsg = $e->getMessage();
                    }
                }else{
                    $this->answerType = 'error';
                    $this->answerMsg = 'Wrong amount to send';
                }
            }else{
                $this->answerType = 'error';
                $this->answerMsg = 'Missing wallet To';
            }
        }else{
            $this->answerType = 'error';
            $this->answerMsg = 'Missing wallet From';
        }
    }
    
    protected function createtxfox(){
        if(is_array($_POST)) extract(TrimArray($_POST));
        $return_txn="";
        if(isset($email)&&$email){
            if(isset($from)&&$from){
                if(isset($to) && $to){
                    if(isset($amount) && $amount>0){
                        $amount=floatval($amount);
                        try{
                            $value="0x".$num;
                            $arr_tx=array(
                                "from"=>$from,
                                "to"=>$to,
                                "amount"=>$amount
                            );
                            $tx_arr=array(
                                "wallet_from"=>$from,
                                "wallet_to"=>$to,
                                "amount"=>$amount,
                                "time_send_request"=>$this->time,
                                "confirm_code"=>md5($from.$to.$amount.$token.time()),
                                "ip"=>$ip,
                            );
                            //Transaction request added at '.$this->view->time_str.'
                            $tx_id=$this->model->save_transaction($tx_arr);
                            if(in_array($language, array("en","ru","cz"))){
                                if($tx_id){
                                    $url_confirm=SITE.($language!="en"?$language.'/':'')."/?tx=".base64_encode($tx_id);
                                    $data_sendmail = array(
                                        'admin'=>0,
                                        'mail_to'=>$email,
                                        'title'=>'Transaction request confirmation',
                                        'message'=>'Hi,<br>
<br>
A new transaction request added to your Unifox account.<br>
<br>

Transaction details:<br>
<br>
Sender wallet: <b>'.$from.'</b><br>
<br>
Receiver wallet: <b>'.$to.'</b><br>
<br>
Amount: <b>'.$amount.' FOX</b><br>
<br>
Fee: 1 FOX + Blockchain fee (~0.000756 FOX)<br>
<br>
As a security precaution, the transaction cannot be send until it is confirmed.<br>
<br>
If you are SURE this is correct, please confirm the transaction by clicking the link below.<br>
<br>
The transaction only needs to be confirmed one time (unless you remove it and add it back later).<br>
<br>
CONFIRM TRANSACTION: <a href="'.$url_confirm.'" rel="noreferrer" target="_blank">'.$url_confirm.'</a><br>
<br>
Review the request very carefully before confirming. We recommend you first double-check the transaction detail.<br>
<br>
Thanks for choosing Unifox<br>
The Unifox Team<br>
<br>

The IP recorded for this action was: '.$ip.'<br>
<br>'
                                    );
                                    if($ans=SendTransactionEmail($data_sendmail)){
                                        $this->answer = array("tx_id"=>$tx_id);
                                        $this->answerType = 'success';
                                        $this->answerMsg = 'Transaction request send to email';
                                    }else{
    //                                    $this->answer = $ans;
                                        $this->answerType = 'error';
                                        $this->answerMsg = 'Error send email';
                                    }
                                }else{
                                    $this->answerType = 'error';
                                    $this->answerMsg = 'Error save transaction';
                                }
                            }else{
                                $this->answerType = 'error';
                                $this->answerMsg = 'Wrong language';
                            }
                        }
                        catch (RPCException $e){
                            $this->answerType = 'error';
                            $this->answerMsg = $e->getMessage();
                        }
                    }else{
                        $this->answerType = 'error';
                        $this->answerMsg = 'Wrong amount to send';
                    }
                }else{
                    $this->answerType = 'error';
                    $this->answerMsg = 'Missing wallet To';
                }
            }else{
                $this->answerType = 'error';
                $this->answerMsg = 'Missing wallet From';
            }
        }else{
            $this->answerType = 'error';
            $this->answerMsg = 'Missing email';
        }
    }
    
    protected function foxconfirm(){
        if(is_array($_POST)) extract(TrimArray($_POST));
        $return_txn="";
        if(isset($code)&&$code){
            $transaction=$this->model->get_transaction_by_code($code);
            $id_tx=$transaction["id_tx"];
            $from=$transaction["wallet_from"];
            $to=$transaction["wallet_to"];
            $amount=$transaction["amount"];
            if($transaction["status"]==0){
                if(isset($from)&&$from){
                    if(isset($to) && $to){
                        if(isset($amount) && $amount>0){
                            $amount=floatval($amount);
                            try{
    //                            $value="0x".$num;
                                $arr_tx=array(
                                    "from"=>$from,
                                    "to"=>$to,
                                    "amount"=>$amount
                                );
                                $this->folder_log = FILESROOT . '/log/'.date('Y')."/".date('m')."/".date('d'); 
                                if(!file_exists($this->folder_log)) mkdir($this->folder_log, 0777, true);
                                $this->log_file = $this->folder_log . "/log_transaction_" . date('d_m_Y') . ".txt";
                                file_put_contents($this->log_file,
                                    "Transaction".
                                    " | IP: " . $_SERVER['REMOTE_ADDR'] . 
                                    " | Time: " . $this->time_str . 
                                    " | Data: " . print_r($arr_tx,1) .PHP_EOL
                                ,FILE_APPEND);
                                $new_txn=call_node($method,$arr_tx);
                                file_put_contents($this->log_file,
                                    "Call node answer".
                                    print_r($new_txn,1) .PHP_EOL
                                ,FILE_APPEND);
                                if(isset($new_txn->answer->txhash) && $new_txn->answer->txhash!=''){
                                    $tx_arr=array(
                                        "id_tx"=>$id_tx,
                                        "hash"=>$new_txn->answer->txhash,
        //                                "wallet_from"=>$from,
        //                                "wallet_to"=>$to,
        //                                "amount"=>$amount,
                                        "fee"=>$new_txn->answer->fee,
                                        "fee_blockchain"=>$new_txn->answer->fee_blockchain,
                                        "time_send"=>$this->time,
                                        "status"=>1,
                                    );
                                    $tx_id=$this->model->update_transaction($tx_arr);
                                    $add_wallets=array($from,$to);
                                    $add_to_upd=$this->model->add_wallet_to_update($add_wallets);
                                    $this->answer=array("txhash"=>$new_txn->answer->txhash);
                                    $this->answerType = 'success';
                                    $this->answerMsg = 'Send ok';
                                }else{
                                    $this->answerType = $new_txn->type;
                                    $this->answerMsg = $new_txn->msg;
                                }
                            }
                            catch (RPCException $e){
                                $this->answerType = 'error';
                                $this->answerMsg = $e->getMessage();
                            }
                        }else{
                            $this->answerType = 'error';
                            $this->answerMsg = 'Wrong amount to send';
                        }
                    }else{
                        $this->answerType = 'error';
                        $this->answerMsg = 'Missing wallet To';
                    }
                }else{
                    $this->answerType = 'error';
                    $this->answerMsg = 'Missing wallet From';
                }
            }else{
                $this->answerType = 'error';
                $this->answerMsg = 'Transaction already send';
            }
        }else{
            $this->answerType = 'error';
            $this->answerMsg = 'Missing code';
        }
    }
    
    protected function txcreateuxc(){
        if(is_array($_POST)) extract(TrimArray($_POST));
        $return_txn="";
        if(isset($email)&&$email){
            if(isset($from)&&$from){
                if(isset($to) && $to){
                    if(isset($amount) && $amount>0){
                        $amount=floatval($amount);
                        try{
//                            $value="0x".$num;
                            $arr_tx=array(
                                "from"=>$from,
                                "to"=>$to,
                                "amount"=>$amount
                            );
                            $tx_arr=array(
                                "wallet_from"=>$from,
                                "wallet_to"=>$to,
                                "amount"=>$amount,
                                "time_send_request"=>$this->time,
                                "confirm_code"=>md5($from.$to.$amount.$token.time()),
                                "ip"=>$ip,
                            );
                            $tx_id=$this->model->save_uxc_transaction($tx_arr);
                            if(in_array($language, array("en","ru","cz"))){
                                if($tx_id){
                                    $url_confirm=SITE.($language!="en"?$language.'/':'')."?tx=".base64_encode($tx_id);
                                    $data_sendmail = array(
                                        'admin'=>0,
                                        'mail_to'=>$email,
                                        'title'=>'Transaction request confirmation',
                                        'message'=>'Hi,<br>
<br>
A new transaction request added to your Unifox account.<br>
<br>
Transaction details:<br>
<br>
Sender wallet: <b>'.$from.'</b><br>
<br>
Receiver wallet: <b>'.$to.'</b><br>
<br>
Amount: <b>'.$amount.' UXC</b><br>
<br>
<br>
As a security precaution, the transaction cannot be send until it is confirmed.<br>
<br>
If you are SURE this is correct, please confirm the transaction by clicking the link below.<br>
<br>
The transaction only needs to be confirmed one time (unless you remove it and add it back later).<br>
<br>
CONFIRM TRANSACTION: <a href="'.$url_confirm.'" rel="noreferrer" target="_blank">'.$url_confirm.'</a><br>
<br>
Review the request very carefully before confirming. We recommend you first double-check the transaction detail.<br>
<br>
Thanks for choosing Unicash<br>
The Unicash Team<br>
<br>

The IP recorded for this action was: '.$ip.'<br>
<br>'
                                    );
                                    if($ans=SendTransactionEmail($data_sendmail)){
                                        $this->answer = array("tx_id"=>$tx_id);
                                        $this->answerType = 'success';
                                        $this->answerMsg = 'Transaction request send to email';
                                    }else{
    //                                    $this->answer = $ans;
                                        $this->answerType = 'error';
                                        $this->answerMsg = 'Error send email';
                                    }
                                }else{
                                    $this->answerType = 'error';
                                    $this->answerMsg = 'Error save transaction';
                                }
                            }else{
                                $this->answerType = 'error';
                                $this->answerMsg = 'Wrong language';
                            }
                        }
                        catch (RPCException $e){
                            $this->answerType = 'error';
                            $this->answerMsg = $e->getMessage();
                        }
                    }else{
                        $this->answerType = 'error';
                        $this->answerMsg = 'Wrong amount to send';
                    }
                }else{
                    $this->answerType = 'error';
                    $this->answerMsg = 'Missing wallet To';
                }
            }else{
                $this->answerType = 'error';
                $this->answerMsg = 'Missing wallet From';
            }
        }else{
            $this->answerType = 'error';
            $this->answerMsg = 'Missing email';
        }
    }
    
    protected function uxcconfirm(){
        if(is_array($_POST)) extract(TrimArray($_POST));
        $return_txn="";
        if(isset($code)&&$code){
            $transaction=$this->model->get_uxc_transaction_by_code($code);
            $id_tx=$transaction["id_tx"];
            $from=$transaction["wallet_from"];
            $to=$transaction["wallet_to"];
            $amount=$transaction["amount"];
            if($transaction["status"]==0){
                if(isset($from)&&$from){
                    if(isset($to) && $to){
                        if(isset($amount) && $amount>=1){
                            $amount=floatval($amount);
                            try{
    //                            $value="0x".$num;
                                $arr_tx=array(
                                    "from"=>$from,
                                    "to"=>$to,
                                    "amount"=>$amount
                                );
                                $this->folder_log = FILESROOT . '/log/'.date('Y')."/".date('m')."/".date('d'); 
                                if(!file_exists($this->folder_log)) mkdir($this->folder_log, 0777, true);
                                $this->log_file = $this->folder_log . "/log_transaction_" . date('d_m_Y') . ".txt";
                                file_put_contents($this->log_file,
                                    "Transaction".
                                    " | IP: " . $_SERVER['REMOTE_ADDR'] . 
                                    " | Time: " . $this->time_str . 
                                    " | Data: " . print_r($arr_tx,1) .PHP_EOL
                                ,FILE_APPEND);
                                $arr_tx["pass"]=$this->model->get_passphrase($from);
                                $new_txn=call_node($method,$arr_tx);
                                file_put_contents($this->log_file,
                                    "Call node answer".
                                    print_r($new_txn,1) .PHP_EOL
                                ,FILE_APPEND);
                                if(isset($new_txn->answer->txhash) && $new_txn->answer->txhash!=''){
                                    $tx_arr=array(
                                        "id_tx"=>$id_tx,
                                        "hash"=>$new_txn->answer->txhash,
        //                                "wallet_from"=>$from,
        //                                "wallet_to"=>$to,
        //                                "amount"=>$amount,
                                        "fee"=>$new_txn->answer->fee,
                                        "fee_blockchain"=>$new_txn->answer->fee_blockchain,
                                        "time_send"=>$this->time,
                                        "status"=>1,
                                    );
                                    $tx_id=$this->model->update_uxc_transaction($tx_arr);
                                    $add_wallets=array($from,$to);
                                    $add_to_upd=$this->model->add_wallet_to_update($add_wallets);
                                
                                    $this->answer=array("txhash"=>$new_txn->answer->txhash);
                                    $this->answerType = 'success';
                                    $this->answerMsg = 'Send ok';
                                }else{
                                    $this->answer = $new_txn->answer;
                                    $this->answerType = $new_txn->type;
                                    $this->answerMsg = $new_txn->msg;
                                }
                            }
                            catch (RPCException $e){
                                $this->answerType = 'error';
                                $this->answerMsg = $e->getMessage();
                            }
                        }else{
                            $this->answerType = 'error';
                            $this->answerMsg = 'Wrong amount to send';
                        }
                    }else{
                        $this->answerType = 'error';
                        $this->answerMsg = 'Missing wallet To';
                    }
                }else{
                    $this->answerType = 'error';
                    $this->answerMsg = 'Missing wallet From';
                }
            }else{
                $this->answerType = 'error';
                $this->answerMsg = 'Transaction already send';
            }
        }else{
            $this->answerType = 'error';
            $this->answerMsg = 'Missing code';
        }
    }
    
    protected function send_token(){
//        ini_set('precision',30);
        if(is_array($_POST)) extract(TrimArray($_POST));
        $arr = array();
//        require_once(LIBROOT.'/ethereum.php');
        
//        $eth = new Ethereum('127.0.0.1', 8545);
        $return_txn="";
        if(isset($from)&&$from){
            if(isset($to) && $to){
                if(isset($amount) && $amount>=0){
                    $gas="0x".convert_dechex(200000);
                    $amount=floatval($amount);
                    try{
//                        $num=convert_dechex($eth->toWei($amount));
//                        $num=convert_dechex($amount);
//                        $value="0x".$num;
                        $param_curl=array(
                            "from"=>$from,
                            "to"=>$to,
                            "amount"=>$amount
                        );
                        $tx_answer=json_decode($new_txn);
                        
                        
    //                        $transaction=new Ethereum_Transaction($from, $to, $gas, 0, $value, $data);

                            $this->folder_log = FILESROOT . '/log/'.date('Y')."/".date('m')."/".date('d'); 
                            if(!file_exists($this->folder_log)) mkdir($this->folder_log, 0777, true);
                            $this->log_file = $this->folder_log . "/log_transaction_" . date('d_m_Y') . ".txt";
                            file_put_contents($this->log_file,
                                "Transaction".
                                " | IP: " . $_SERVER['REMOTE_ADDR'] . 
                                " | Time: " . $this->time_str . 
                                " | Data: " . $from.'/'.$to.'/'.$amount .PHP_EOL
                            ,FILE_APPEND);
                        if($tx_answer->type=="success"){
                            $this->answer=$tx_answer->answer;
                            $this->answerType = 'success';
                            $this->answerMsg = 'Send ok';
                        }
                    }
                    catch (RPCException $e){
                        $this->answerType = 'error';
                        $this->answerMsg = $e->getMessage();
                    }
                }else{
                    $this->answerType = 'error';
                    $this->answerMsg = 'Wrong amount to send';
                }
            }else{
                $this->answerType = 'error';
                $this->answerMsg = 'Missing wallet To';
            }
        }else{
            $this->answerType = 'error';
            $this->answerMsg = 'Missing wallet From';
        }
    }
    
    protected function anotherbuyfox(){
        if(is_array($_POST)) extract(TrimArray($_POST));
//        $this->answer=$_POST;
        if($id_user=$this->model->get_user_by_token($token)){
            if(isset($currency) && in_array(strtolower($currency), $this->model->allow_crypto)){
                if(isset($wallet_fox) && $wallet_fox!=''){
                    if(isset($amount) && $amount>0){
                        if(isset($amount_fox) && $amount_fox>0){
                            if(isset($rate) && $rate>0){
                                if(isset($id_terminal) && $id_terminal>0){
                                    $status_terminal = dbSel('site', 'num="'.$id.'"', 'status');
                                    if(isset($status_terminal['status'])&& $status_terminal['status']==1) {
                                        $TestMode=0;
                                    }else{
                                        $TestMode=1;
                                    }
                                }
                                $data = TrimArray($_POST);
                                $currency=strtoupper($currency);
                    //            $blockchain_param=array();
                    //            $blockchain_param['sum']=$sum;
                    //            $blockchain_param['currency']=$currency;

                                if($currency=="BTC"){
                                    $data_request=array();
                                    $data_request['id_user'] = $id_user;
                                    $data_request['time_add'] = $this->time;
                                    $data_request['wallet_fox'] = $wallet_fox;
                                    $data_request['currency']=$currency;
                                    $data_request['amount']=$amount;
                                    $data_request['amount_fox']=$amount_fox;
                                    $data_request['rate']=$rate;

                                    $id_request = $this->model->CreateBuyFoxRequest($data_request);
                                 
                                    $parameters = 'xpub=' .$my_xpub. '&callback=' .urlencode($my_callback_url). '&key=' .$my_api_key;
                                    $response = file_get_contents($root_url . '?' . $parameters);
                                    $object = json_decode($response);

                                    $new_wallet="";
                                    if($object->address && $object->address!=""){
                                        $new_wallet=$object->address;
                                        $qrdata=strtolower("bitcoin").":".$new_wallet."?amount=".$amount."&message=request_".$id_request;
                                        $data_update=array(
                                            "qrdata"=>$qrdata,
                                            "generate_wallet"=>$new_wallet,
                                            "status"=>1,
                                        );
                                        $this->model->UpdateBuyFoxRequest($data_update,$id_request);
                                    }else{
                                        $this->answerType="error";
                                        $this->answerMsg="Didn't create wallet";
                                        return;
                                    }
                                }else{
                                    $this->answerType="error";
                                    $this->answerMsg="Wrong currency";
                                    return;
                                }


                                require LIBROOT . "/qrcode/qrlib.php";
                                $dir_code="/paycode/".date("Y")."/".date("m")."/".date("d")."/".$id_terminal;
                                $tempDir=FILESROOT.$dir_code;

                                if(!is_dir($tempDir)) mkdir ($tempDir, 0777, true);
                                $name_qr="sell_".date("YmdHis").'.png';
                                QRcode::png($qrdata, $tempDir."/".$name_qr, QR_ECLEVEL_M, 6, 3);


                                $data_sendmail = array(
                                    'who'=>'admin',
                                    'emailAddress'=>$email,
                                    'title'=>'Create request '.$id_request.' for Buy FOX by '.$currency,
                                    'message'=>"<p>User want buy $amount $currency".(isset($sum_eur)?"($sum_eur EUR)":"")." on web $id_terminal</p>"
                                );
                                senderInvoices($data_sendmail);

                                if(isset($currency)){
                                    $data['currency']=$currency;
                                }
                                if(isset($sum_eur)){
                                    $data['sum_eur']=$sum_eur;
                                }
                                $data['qr_url']="/files".$dir_code."/".$name_qr;
                                $this->answerType='success';
                                $this->answer=array(
                                    "id_request"=>$id_request,
                                    "qr_url"=>DOMAIN_FULL.$data['qr_url'],
                    //                    "qr_file_url"=>$tempDir."/".$name_qr,
                    //                    "qr_domain"=>DOMAIN_FULL,
                                    "qrdata"=>$qrdata,
                                    "currency"=>$currency,
                                    "sum"=>$amount,
                                    "wallet"=>$new_wallet,
                                    "time_add"=>$this->time
                                );
                            }
                        }
                    }
                }
            }else{
                $this->answerType='error';
                $this->answerMsg="Wrong input parameters";
            }
        }
    }
    
    protected function foxbank(){
        if(is_array($_POST)) extract(TrimArray($_POST));
//        $this->answer=$_POST;
        if($id_user=$this->model->get_user_by_token($token)){
            if(isset($currency) && in_array(strtolower($currency), array("eur"))){
                if(isset($wallet_fox) && $wallet_fox!=''){
                    if(isset($amount) && $amount>0){
                        if(isset($amount_fox) && $amount_fox>0){
                            if(isset($rate) && $rate>0){
                                if(isset($id_terminal) && $id_terminal>0){
                                    $status_terminal = dbSel('site', 'number="'.$id_terminal.'"', 'status');
                                    if(isset($status_terminal['status'])&& $status_terminal['status']==1) {
                                        $TestMode=0;
                                    }else{
                                        $TestMode=1;
                                    }
                                }
                                $data = TrimArray($_POST);
                                $currency=strtoupper($currency);
                    //            $blockchain_param=array();
                    //            $blockchain_param['sum']=$sum;
                    //            $blockchain_param['currency']=$currency;
                                $vs="";
                                if($currency=="EUR"){
                                    $data_request=array();
                                    $data_request['id_user'] = $id_user;
                                    $data_request['time_add'] = $this->time;
                                    $data_request['wallet_fox'] = $wallet_fox;
                                    $data_request['currency']=$currency;
                                    $data_request['amount']=$amount;
                                    $data_request['amount_fox']=$amount_fox;
                                    $data_request['rate']=$rate;

                                    $id_request = $this->model->CreateBuyFoxRequest($data_request);
                                    $vs="2018".str_pad($id_request, 5, '0', STR_PAD_LEFT);
                                    $data_update=array(
                                        "qrdata"=>$vs,
                                        "status"=>1,
                                    );
                                    $this->model->UpdateBuyFoxRequest($data_update,$id_request);
                                }else{
                                    $this->answerType="error";
                                    $this->answerMsg="Wrong currency";
                                    return;
                                }
                                $data_sendmail = array(
                                    'who'=>'admin',
                                    'emailAddress'=>$email,
                                    'title'=>'Create request '.$id_request.' for Buy FOX by '.$currency,
                                    'message'=>"<p>User want buy $amount $currency".(isset($sum_eur)?"($sum_eur EUR)":"")." on web $id_terminal</p>"
                                );
                                senderInvoices($data_sendmail);
                                $this->answerType='success';
                                $this->answer=array(
                                    "id_request"=>$id_request,
                                    "vs"=>$vs,
                                    "currency"=>$currency,
                                    "sum"=>$amount,
                                    "time_add"=>$this->time
                                );
                            }
                        }
                    }
                }
            }else{
                $this->answerType='error';
                $this->answerMsg="Wrong input parameters";
            }
        }
    }
    
    protected function listrequests(){
        if(is_array($_POST)) extract(TrimArray($_POST));
        if($id_user=$this->model->get_user_by_token($token)){
            $requests = $this->model->GetBuyFoxRequests($id_user,$page,$limit);

            $this->answerType='success';
            $this->answer=array("requests"=>$requests['res'],"requests_count"=>$requests['cnt']);
        }else{
            $this->answerType='error';
            $this->answerMsg="Wrong input parameters";
        }
    }
    
    protected function foxrequestconfirm(){
        if(is_array($_POST)) extract(TrimArray($_POST));
        if($id_request>0){
            if($id_user=$this->model->get_user_by_token($token)){
                if($request = $this->model->GetBuyFoxRequestById($id_request)){
                    if($request['status']!=2 && $request['status']==1){
                        $to=$request["wallet_fox"];
                        $amount=$request["amount_fox"];
                        $time_payment_receive=$request["time_payment_receive"];
                        if(isset($from)&&$from){
                            if(isset($to) && $to){
                                if(isset($amount) && $amount>0){
                                    $amount=floatval($amount);
                                    try{
                                        $arr_tx=array(
                                            "from"=>$from,
                                            "to"=>$to,
                                            "amount"=>$amount
                                        );
                                        $this->folder_log = FILESROOT . '/log/'.date('Y')."/".date('m')."/".date('d'); 
                                        if(!file_exists($this->folder_log)) mkdir($this->folder_log, 0777, true);
                                        $this->log_file = $this->folder_log . "/log_transaction_" . date('d_m_Y') . ".txt";
                                        file_put_contents($this->log_file,
                                            "Transaction".
                                            " | IP: " . $_SERVER['REMOTE_ADDR'] . 
                                            " | Time: " . $this->time_str . 
                                            " | Data: " . print_r($arr_tx,1) .PHP_EOL
                                        ,FILE_APPEND);
                                        $arr_tx["pass"]=$this->model->get_passphrase($from);
                                        $new_txn=call_node($method,$arr_tx);
                                        file_put_contents($this->log_file,
                                            "Call node answer".
                                            print_r($new_txn,1) .PHP_EOL
                                        ,FILE_APPEND);
                                        if(isset($new_txn->answer->txhash) && $new_txn->answer->txhash!=''){
                                            $tx_arr=array(
                                                "wallet_from"=>$from,
                                                "wallet_to"=>$to,
                                                "amount"=>$amount,
                                                "time_send_request"=>$this->time,
                                                "confirm_code"=>md5($from.$to.$amount.$token.time()),
                                                "ip"=>$ip,
                                                "hash"=>$new_txn->answer->txhash,
                                                "fee"=>$new_txn->answer->fee,
                                                "fee_blockchain"=>$new_txn->answer->fee_blockchain,
                                                "time_send"=>$this->time,
                                                "id_request"=>$id_request,
                                                "status"=>1,
                                            );
                                            $tx_id=$this->model->save_transaction($tx_arr);
                                            $data_update=array(
                                                "time_payment_receive"=>($time_payment_receive==''?$this->time:""),
                                                "time_fox_send"=>$this->time,
                                                "status"=>2,
                                            );
                                            $this->model->UpdateBuyFoxRequest($data_update,$id_request);
                                            $add_wallets=array($from,$to);
                                            $add_to_upd=$this->model->add_wallet_to_update($add_wallets);
                                            $this->answer=array("txhash"=>$new_txn->answer->txhash);
                                            $this->answerType = 'success';
                                            $this->answerMsg = 'Send ok';
                                        }else{
                                            $this->answerType = $new_txn->type;
                                            $this->answerMsg = $new_txn->msg;
                                        }
                                    }
                                    catch (RPCException $e){
                                        $this->answerType = 'error';
                                        $this->answerMsg = $e->getMessage();
                                    }
                                }else{
                                    $this->answerType = 'error';
                                    $this->answerMsg = 'Wrong amount to send';
                                }
                            }else{
                                $this->answerType = 'error';
                                $this->answerMsg = 'Missing wallet To';
                            }
                        }else{
                            $this->answerType = 'error';
                            $this->answerMsg = 'Missing wallet From';
                        }
                    }else{
                        $this->answerType = 'error';
                        $this->answerMsg = 'FOX already send';
                    }
                }else{
                    $this->answerType='error';
                    $this->answerMsg="Request not found";
                }
            }else{
                $this->answerType='error';
                $this->answerMsg="Wrong input parameters";
            }
        }else{
            $this->answerType='error';
            $this->answerMsg="Wrong input parameters";
        }
    }
    
    protected function anotherbuyuxc(){
        if(is_array($_POST)) extract(TrimArray($_POST));
//        $this->answer=$_POST;
        if($id_user=$this->model->get_user_by_token($token)){
            if(isset($currency) && in_array(strtolower($currency), $this->model->allow_crypto)){
                if(isset($wallet_uxc) && $wallet_uxc!=''){
                    if(isset($amount) && $amount>0){
                        if(isset($amount_uxc) && $amount_uxc>0){
                            if(isset($rate) && $rate>0){
                                if(isset($id_terminal) && $id_terminal>0){
                                    $status_terminal = dbSel('site', 'number="'.$id_terminal.'"', 'status');
                                    if(isset($status_terminal['status'])&& $status_terminal['status']==1) {
                                        $TestMode=0;
                                    }else{
                                        $TestMode=1;
                                    }
                                }
                                $data = TrimArray($_POST);
                                $currency=strtoupper($currency);
                    //            $blockchain_param=array();
                    //            $blockchain_param['sum']=$sum;
                    //            $blockchain_param['currency']=$currency;

                                if($currency=="BTC"){
                                    $data_request=array();
                                    $data_request['id_user'] = $id_user;
                                    $data_request['time_add'] = $this->time;
                                    $data_request['wallet_uxc'] = $wallet_uxc;
                                    $data_request['currency']=$currency;
                                    $data_request['amount']=$amount;
                                    $data_request['amount_uxc']=$amount_uxc;
                                    $data_request['rate']=$rate;

                                    $id_request = $this->model->CreateBuyUxcRequest($data_request);
                                    $parameters = 'xpub=' .$my_xpub. '&callback=' .urlencode($my_callback_url). '&key=' .$my_api_key;
                                    $response = file_get_contents($root_url . '?' . $parameters);
                                    $object = json_decode($response);
                    //                echo 'Send Payment To : ' . $object->address;

                                    $new_wallet="";
                                    if($object->address && $object->address!=""){
                                        $new_wallet=$object->address;
                                        $qrdata=strtolower("bitcoin").":".$new_wallet."?amount=".$amount."&message=request_".$id_request;
                                        $data_update=array(
                                            "qrdata"=>$qrdata,
                                            "generate_wallet"=>$new_wallet,
                                            "status"=>1,
                                        );
                                        $this->model->UpdateBuyUxcRequest($data_update,$id_request);
                                    }else{
                                        $this->answerType="error";
                                        $this->answerMsg="Didn't create wallet";
                                        return;
                                    }
                                }else{
                                    $this->answerType="error";
                                    $this->answerMsg="Wrong currency";
                                    return;
                                }


                                require LIBROOT . "/qrcode/qrlib.php";
                                $dir_code="/paycode/".date("Y")."/".date("m")."/".date("d")."/".$id_terminal;
                                $tempDir=FILESROOT.$dir_code;

                                if(!is_dir($tempDir)) mkdir ($tempDir, 0777, true);
                                $name_qr="sell_".date("YmdHis").'.png';
                                QRcode::png($qrdata, $tempDir."/".$name_qr, QR_ECLEVEL_M, 6, 3);

                                $data_sendmail = array(
                                    'who'=>'admin',
                                    'emailAddress'=>$email,
                                    'title'=>'Create request '.$id_request.' for Buy UXC by '.$currency,
                                    'message'=>"<p>User want buy $amount $currency".(isset($sum_eur)?"($sum_eur EUR)":"")." on web $id_terminal</p>"
                                );
                                senderInvoices($data_sendmail);

                                if(isset($currency)){
                                    $data['currency']=$currency;
                                }
                                if(isset($sum_eur)){
                                    $data['sum_eur']=$sum_eur;
                                }
                                $data['qr_url']="/files".$dir_code."/".$name_qr;

                                $this->answerType='success';
                                $this->answer=array(
                                    "id_request"=>$id_request,
                                    "qr_url"=>DOMAIN_FULL.$data['qr_url'],
                                    "qrdata"=>$qrdata,
                                    "currency"=>$currency,
                                    "sum"=>$amount,
                                    "wallet"=>$new_wallet,
                                    "time_add"=>$this->time
                                );
                            }
                        }
                    }
                }
            }else{
                $this->answerType='error';
                $this->answerMsg="Wrong input parameters";
            }
        }
    }
    
    protected function uxcbank(){
        if(is_array($_POST)) extract(TrimArray($_POST));
//        $this->answer=$_POST;
        if($id_user=$this->model->get_user_by_token($token)){
            if(isset($currency) && in_array(strtolower($currency), array("eur"))){
                if(isset($wallet_uxc) && $wallet_uxc!=''){
                    if(isset($amount) && $amount>0){
                        if(isset($amount_uxc) && $amount_uxc>0){
                            if(isset($rate) && $rate>0){
                                if(isset($id_terminal) && $id_terminal>0){
                                    $status_terminal = dbSel('site', 'number="'.$id_terminal.'"', 'status');
                                    if(isset($status_terminal['status'])&& $status_terminal['status']==1) {
                                        $TestMode=0;
                                    }else{
                                        $TestMode=1;
                                    }
                                }
                                $data = TrimArray($_POST);
                                $currency=strtoupper($currency);
                                $vs="";
                                if($currency=="EUR"){
                                    $data_request=array();
                                    $data_request['id_user'] = $id_user;
                                    $data_request['time_add'] = $this->time;
                                    $data_request['wallet_uxc'] = $wallet_uxc;
                                    $data_request['currency']=$currency;
                                    $data_request['amount']=$amount;
                                    $data_request['amount_uxc']=$amount_uxc;
                                    $data_request['rate']=$rate;

                                    $id_request = $this->model->CreateBuyUxcRequest($data_request);
                                    $vs="2018".str_pad($id_request, 5, '0', STR_PAD_LEFT);
                                    $data_update=array(
                                        "qrdata"=>$vs,
                                        "status"=>1,
                                    );
                                    $this->model->UpdateBuyUxcRequest($data_update,$id_request);
                                }else{
                                    $this->answerType="error";
                                    $this->answerMsg="Wrong currency";
                                    return;
                                }
                                $data_sendmail = array(
                                    'who'=>'admin',
                                    'emailAddress'=>$email,
                                    'title'=>'Create request '.$id_request.' for Buy UXC by '.$currency,
                                    'message'=>"<p>User want buy $amount $currency".(isset($sum_eur)?"($sum_eur EUR)":"")." on web $id_terminal</p>"
                                );
                                senderInvoices($data_sendmail);
                                $this->answerType='success';
                                $this->answer=array(
                                    "id_request"=>$id_request,
                                    "vs"=>$vs,
                                    "currency"=>$currency,
                                    "sum"=>$amount,
                                    "time_add"=>$this->time
                                );
                            }
                        }
                    }
                }
            }else{
                $this->answerType='error';
                $this->answerMsg="Wrong input parameters";
            }
        }
    }
    
    protected function listuxcrequest(){
        if(is_array($_POST)) extract(TrimArray($_POST));
        if($id_user=$this->model->get_user_by_token($token)){
            $requests = $this->model->GetBuyUxcRequests($id_user,$page,$limit);

            $this->answerType='success';
            $this->answer=array("requests"=>$requests['res'],"requests_count"=>$requests['cnt']);
        }else{
            $this->answerType='error';
            $this->answerMsg="Wrong input parameters";
        }
    }
    
    protected function uxcrequestconfirm(){
        if(is_array($_POST)) extract(TrimArray($_POST));
        if($id_request>0){
            if($id_user=$this->model->get_user_by_token($token)){
                if($request = $this->model->GetBuyUxcRequestById($id_request)){
                    if($request['status']!=2 && $request['status']==1){
                        $to=$request["wallet_uxc"];
                        $amount=$request["amount_uxc"];
                        $time_payment_receive=$request["time_payment_receive"];
                        if(isset($from)&&$from){
                            if(isset($to) && $to){
                                if(isset($amount) && $amount>0){
                                    $amount=floatval($amount);
//                                    try{
                                        $arr_tx=array(
                                            "from"=>$from,
                                            "to"=>$to,
                                            "amount"=>$amount
                                        );
                                        $this->folder_log = FILESROOT . '/log/'.date('Y')."/".date('m')."/".date('d'); 
                                        if(!file_exists($this->folder_log)) mkdir($this->folder_log, 0777, true);
                                        $this->log_file = $this->folder_log . "/log_transaction_" . date('d_m_Y') . ".txt";
                                        file_put_contents($this->log_file,
                                            "Transaction".
                                            " | IP: " . $_SERVER['REMOTE_ADDR'] . 
                                            " | Time: " . $this->time_str . 
                                            " | Data: " . print_r($arr_tx,1) .PHP_EOL
                                        ,FILE_APPEND);
                                        $arr_tx["pass"]=$this->model->get_passphrase($from);
                                        $new_txn=call_node($method,$arr_tx);
                                        file_put_contents($this->log_file,
                                            "Call node answer".
                                            print_r($new_txn,1) .PHP_EOL
                                        ,FILE_APPEND);
                                        if(isset($new_txn->answer->txhash) && $new_txn->answer->txhash!=''){
                                            $tx_arr=array(
                                                "wallet_from"=>$from,
                                                "wallet_to"=>$to,
                                                "amount"=>$amount,
                                                "time_send_request"=>$this->time,
                                                "confirm_code"=>md5($from.$to.$amount.$token.time()),
                                                "ip"=>$ip,
                                                "hash"=>$new_txn->answer->txhash,
                                                "fee"=>$new_txn->answer->fee,
                                                "fee_blockchain"=>$new_txn->answer->fee_blockchain,
                                                "time_send"=>$this->time,
                                                "id_request"=>$id_request,
                                                "status"=>1,
                                            );
                                            $tx_id=$this->model->save_uxc_transaction($tx_arr);
                                            $data_update=array(
                                                "time_payment_receive"=>($time_payment_receive==''?$this->time:""),
                                                "time_uxc_send"=>$this->time,
                                                "status"=>2,
                                            );
                                            $this->model->UpdateBuyUxcRequest($data_update,$id_request);
                                            $add_wallets=array($from,$to);
                                            $add_to_upd=$this->model->add_wallet_to_update($add_wallets);
                                            $this->answer=array("txhash"=>$new_txn->answer->txhash);
                                            $this->answerType = 'success';
                                            $this->answerMsg = 'Send ok';
                                        }else{
                                            $this->answerType = $new_txn->type;
                                            $this->answerMsg = $new_txn->msg;
                                        }
//                                    }
//                                    catch (RPCException $e){
//                                        $this->answerType = 'error';
//                                        $this->answerMsg = $e->getMessage();
//                                    }
                                }else{
                                    $this->answerType = 'error';
                                    $this->answerMsg = 'Wrong amount to send';
                                }
                            }else{
                                $this->answerType = 'error';
                                $this->answerMsg = 'Missing wallet To';
                            }
                        }else{
                            $this->answerType = 'error';
                            $this->answerMsg = 'Missing wallet From';
                        }
                    }else{
                        $this->answerType = 'error';
                        $this->answerMsg = 'FOX already send';
                    }
                }else{
                    $this->answerType='error';
                    $this->answerMsg="Request not found";
                }
            }else{
                $this->answerType='error';
                $this->answerMsg="Wrong input parameters";
            }
        }else{
            $this->answerType='error';
            $this->answerMsg="Wrong input parameters";
        }
    }
    
    protected function uxcbuybybtc(){
        if(is_array($_POST)) extract(TrimArray($_POST));
//        $this->answer=$_POST;
        if($sum>0 && $wallet!=""){
            if(isset($id_terminal) && $id_terminal>0){
                $status_terminal = dbSel('site', 'number="'.$id_terminal.'"', 'status');
                if(isset($status_terminal['status'])&& $status_terminal['status']==1) {
                    $TestMode=0;
                }else{
                    $TestMode=1;
                }
            }
            $data = TrimArray($_POST);
            $data['time_add'] = $this->time;
            $data['type_request'] ="sell";
            
            $blockchain_param=array();
            $blockchain_param['sum']=$sum;
            $blockchain_param['currency']=$currency;
            
            if(strtoupper($currency)=="UXC"){
                $parameters = 'xpub=' .$my_xpub. '&callback=' .urlencode($my_callback_url). '&key=' .$my_api_key. '&gap_limit=10000';
                $response = file_get_contents($root_url . '?' . $parameters);
                $object = json_decode($response);
//                echo 'Send Payment To : ' . $object->address;
                
                $new_wallet="";
                if($object->address && $object->address!=""){
                    $new_wallet=$object->address;
                    $qrdata=strtolower("bitcoin").":".$new_wallet."?amount=".$sum."&message=operation_".$id_terminal_operation;
                }else{
                    $this->answerType="error";
                    $this->answerMsg="Didn't create wallet";
                    return;
                }
            }else{
                $this->answerType="error";
                $this->answerMsg="Wrong currency";
                return;
            }
            
            
            require LIBROOT . "/qrcode/qrlib.php";
            $dir_code="/paycode/".date("Y")."/".date("m")."/".date("d")."/".$id_terminal;
            $tempDir=FILESROOT.$dir_code;

            if(!is_dir($tempDir)) mkdir ($tempDir, 0777, true);
            $name_qr="sell_".date("YmdHis").'.png';
            QRcode::png($qrdata, $tempDir."/".$name_qr, QR_ECLEVEL_M, 6, 3);

            $blockchain_param['TestMode']=$TestMode;

                
            $data_sendmail = array(
                'who'=>'admin',
                'emailAddress'=>$email,
                'title'=>'Create request for Buy UXC by BTC',
                'message'=>"<p>User want buy $sum $currency".(isset($sum_eur)?"($sum_eur EUR)":"")." on web $id_terminal</p>"
            );
            senderInvoices($data_sendmail);
                
                if(isset($currency)){
                    $data['currency']=$currency;
                }
                if(isset($sum_eur)){
                    $data['sum_eur']=$sum_eur;
                }
                $data['qr_url']="/files".$dir_code."/".$name_qr;
                $id=0;
                if($process=="test"){
                    $process=array("status_request"=>2);
                }
                $this->answerType='success';
                $this->answer=array(
                    "id_request"=>$id,
                    "qr_url"=>DOMAIN_FULL.$data['qr_url'],
//                    "qr_file_url"=>$tempDir."/".$name_qr,
//                    "qr_domain"=>DOMAIN_FULL,
                    "qrdata"=>$qrdata,
                    "currency"=>$currency,
                    "sum"=>$sum,
                    "wallet"=>$new_wallet
                );
        }
    }
    
    protected function rates(){
        if(is_array($_POST)) extract(TrimArray($_POST));
        $rates=$this->model->get_rates($currency);
        $this->answer=$rates[0]["rate"];
        $this->answerType='success';
    }
    
    protected function allrate(){
        $rates=$this->model->get_rates();
        $this->answer=$rates;
        $this->answerType='success';
    }
    
    protected function giftcard(){
        ini_set('precision',30);
        if(is_array($_POST)) extract(TrimArray($_POST));
        if($giftcard_code!="" && $wallet!=""){
            if(isset($id_terminal) && $id_terminal>0){
                $status_terminal = dbSel('site', 'number="'.$id_terminal.'"', 'status');
                if(isset($status_terminal['status'])&& $status_terminal['status']==1) {
                    $TestMode=0;
                }else{
                    $TestMode=1;
                }
            }
            $data = TrimArray($_POST);
            $data['time_add'] = $this->time;
//            $data['type_request'] ="sell";
            
            
//            $blockchain_param=array();
//            $blockchain_param['sum']=$sum;
//            $blockchain_param['currency']=$currency;
            
            if(strtoupper($currency)=="FOX"){
                if($giftcard=$this->model->get_giftcard_by_number($giftcard_code)){
                    if($giftcard['status']==0){
                        if(isset($giftcard['amount'])&& $giftcard['amount']>0){
//                            require_once(LIBROOT.'/ethereum.php');
//                            $eth = new Ethereum('127.0.0.1', 8545);
                            $return_txn="";
                            $to=$wallet;
                            $amount=floatval($giftcard['amount']);
                            try{

                                $arr_tx=array(
                                    "from"=>$from,
                                    "to"=>$to,
                                    "amount"=>$amount
                                );
                                $this->folder_log = FILESROOT . '/log/'.date('Y')."/".date('m')."/".date('d'); 
                                if(!file_exists($this->folder_log)) mkdir($this->folder_log, 0777, true);
                                $this->log_file = $this->folder_log . "/log_transaction_" . date('d_m_Y') . ".txt";
                                file_put_contents($this->log_file,
                                    "Transaction Gift Card".
                                    " | IP: " . $_SERVER['REMOTE_ADDR'] . 
                                    " | Time: " . $this->time_str . 
                                    " | Data: " . print_r($arr_tx,1) .PHP_EOL
                                ,FILE_APPEND);
                                $arr_tx["pass"]=$this->model->get_passphrase($from);
                                $new_txn=call_node($method,$arr_tx);
                                file_put_contents($this->log_file,
                                    "Call node answer".
                                    print_r($new_txn,1) .PHP_EOL
                                ,FILE_APPEND);
                                if(isset($new_txn->answer->txhash) && $new_txn->answer->txhash!=''){
                                    $tx_arr=array(
                                        "hash"=>$new_txn->answer->txhash,
                                        "wallet_from"=>$from,
                                        "wallet_to"=>$to,
                                        "amount"=>$amount,
                                        "fee"=>$new_txn->answer->fee,
                                        "fee_blockchain"=>$new_txn->answer->fee_blockchain,
                                        "time_send"=>$this->time,
                                        "status"=>1
                                    );
                                    $giftcard_param=array(
                                        "id_giftcard"=>$giftcard['id'],
                                        "hash"=>$new_txn->answer->txhash,
                                        "wallet"=>$to,
                                        "time_accepted"=>$this->time,
                                        "status"=>1
                                    );
                                    $tx_id=$this->model->save_transaction($tx_arr);
                                    $add_wallets=array($from,$to);
                                    $add_to_upd=$this->model->add_wallet_to_update($add_wallets);
                                    if($this->model->update_giftcard($giftcard_param)){
                                        $this->answerType='success';
                                        $this->answer=array(
                                            "status"=>1,
                                            "status_str"=>"Accepted",
                                            "currency"=>$currency,
                                            "amount"=>15,
                                            "wallet"=>$wallet
                                        );
                                    }else{
                                        $this->answerType="error";
                                        $this->answerMsg="error save giftcard";
                                    }
                                }else{
                                    $this->answerType=$new_txn->type;
                                    $this->answerMsg=$new_txn->msg;
                                }
                            }
                            catch (RPCException $e){
                                $this->answerType = 'error';
                                $this->answerMsg = $e->getMessage();
                            }
                        }else{
                            $this->answerType="error";
                            $this->answerMsg="Wrong card code";
                        }
                    }elseif($giftcard['status']==1){
                        $this->answerType='success';
                        $this->answer=array(
                            "status"=>2,
                            "status_str"=>"Used"
                        );
                    }else{
                        $this->answerType='success';
                        $this->answer=array(
                            "status"=>3,
                            "status_str"=>"Not active"
                        );
                    }
                }else{
                    $this->answerType="error";
                    $this->answerMsg="Wrong card code";
                }
            }else{
                $this->answerType="error";
                $this->answerMsg="Wrong currency";
            }
        }
    }
    
    protected function publicgiftcard(){
        ini_set('precision',30);
        if(is_array($_POST)) extract(TrimArray($_POST));
        if($giftcard_code!="" && $wallet!=""){
            if(isset($id_terminal) && $id_terminal>0){
                $status_terminal = dbSel('site', 'number="'.$id_terminal.'"', 'status');
                if(isset($status_terminal['status'])&& $status_terminal['status']==1) {
                    $TestMode=0;
                }else{
                    $TestMode=1;
                }
            }
            $data = TrimArray($_POST);
            $data['time_add'] = $this->time;
            
            if(strtoupper($currency)=="FOX"){
                if($id_user=$this->model->get_user_by_token($token)){
                    if($giftcard_code=="G1FX3"){
                        if("0"===$this->model->get_is_used_public_giftcard($id_user)){
                            if(!$this->model->get_is_limit_public_giftcard()){
                                    $return_txn="";
                                    $to=$wallet;
                                    $amount=floatval(20);
                                    try{
                                        $arr_tx=array(
                                            "from"=>$from,
                                            "to"=>$to,
                                            "amount"=>$amount
                                        );

                                        $this->folder_log = FILESROOT . '/log/'.date('Y')."/".date('m')."/".date('d'); 
                                        if(!file_exists($this->folder_log)) mkdir($this->folder_log, 0777, true);
                                        $this->log_file = $this->folder_log . "/log_transaction_" . date('d_m_Y') . ".txt";
                                        file_put_contents($this->log_file,
                                            "Transaction public Gift Card".
                                            " | IP: " . $_SERVER['REMOTE_ADDR'] . 
                                            " | Time: " . $this->time_str . 
                                            " | Data: " . print_r($arr_tx,1) .PHP_EOL
                                        ,FILE_APPEND);
                                        $arr_tx["pass"]=$this->model->get_passphrase($from);
                                        $new_txn=call_node($method,$arr_tx);
                                        file_put_contents($this->log_file,
                                            "Call node answer".
                                            print_r($new_txn,1) .PHP_EOL
                                        ,FILE_APPEND);
                                        if(isset($new_txn->answer->txhash) && $new_txn->answer->txhash!=''){
                                            $tx_arr=array(
                                                "hash"=>$new_txn->answer->txhash,
                                                "wallet_from"=>$from,
                                                "wallet_to"=>$to,
                                                "amount"=>$amount,
                                                "fee"=>$new_txn->answer->fee,
                                                "fee_blockchain"=>$new_txn->answer->fee_blockchain,
                                                "time_send"=>$this->time,
                                                "status"=>1
                                            );
                                            $giftcard_param=array(
                                                "number"=>$giftcard_code,
                                                "amount"=>$amount,
                                                "currency"=>$currency,
                                                "id_user"=>$id_user,
                                                "hash"=>$new_txn->answer->txhash,
                                                "wallet"=>$to,
                                                "time_accepted"=>$this->time,
                                                "status"=>1
                                            );
                                            $tx_id=$this->model->save_transaction($tx_arr);
                                            $add_wallets=array($from,$to);
                                            $add_to_upd=$this->model->add_wallet_to_update($add_wallets);
                                            if($this->model->add_use_giftcard($giftcard_param)){
                                                $this->answerType='success';
                                                $this->answer=array(
                                                    "status"=>1,
                                                    "status_str"=>"Accepted",
                                                    "currency"=>$currency,
                                                    "amount"=>$amount,
                                                    "wallet"=>$wallet
                                                );
                                            }else{
                                                $this->answerType="error";
                                                $this->answerMsg="error save giftcard";
                                            }
                                        }else{
                                            $this->answerType=$new_txn->type;
                                            $this->answerMsg=$new_txn->msg;
                                        }
                                    }
                                    catch (RPCException $e){
                                        $this->answerType = 'error';
                                        $this->answerMsg = $e->getMessage();
                                    }
                                }else{
                                    $this->answerType="error";
                                    $this->answerMsg="Limit used card";
                                }
                            }else{
                                $this->answerType='success';
                                $this->answer=array(
                                    "status"=>2,
                                    "status_str"=>"Used"
                                );
                            }
                    }else{
                        $this->answerType="error";
                        $this->answerMsg="Wrong card code";
                    }
                }else{
                    $this->answerType="error";
                    $this->answerMsg="Wrong user";
                }
            }else{
                $this->answerType="error";
                $this->answerMsg="Wrong currency";
            }
        }
    }
    
    protected function txreferer(){
        if(is_array($_POST)) extract(TrimArray($_POST));
        $accounts=json_decode($accounts);
        if($accounts!="" && is_array($accounts)){
            $data = TrimArray($_POST);
//            require_once(LIBROOT.'/ethereum.php');
//            $eth = new Ethereum('127.0.0.1', 8545);
            $return_txn=array();
            $page=$limit=0;
            
            $referals=$this->model->get_wallet_address_referal($accounts);
            $wallets=array();
            foreach ($referals as $acc=>$referal){
                foreach ($referal as $wallets_ref){
                    $wallets[]=$wallets_ref['wallet'];
                }
                $transactions=$this->model->get_transactions_buy("'".implode("','",$wallets)."'",$page,$limit);
                $return_txn[$acc]["total_sum"]=0;
                $return_txn[$acc]["total_cnt"]=0;
                $return_txn[$acc]["currency"]="FOX";
                foreach ($transactions['res'] as $transaction) {
                    $return_txn[$acc]["transactions"][$transaction['wallet_to']][]=array("hash"=>$transaction['hash'],"from"=>$transaction['wallet_from'],"to"=>$transaction['wallet_to'],"amount"=>(strcmp($wallets_ref['wallet'], $transaction['wallet_from'])==0?"":"").$transaction['amount'],"date"=>time_notify($transaction['time_send']),"fee"=>$transaction['fee'],"fee_blockchain"=>$transaction['fee_blockchain'],"currency"=>"FOX");
                    $return_txn[$acc]["total_sum"]+=$transaction['amount'];
                    $return_txn[$acc]["total_cnt"]++;
                }
                $wallets=array();
            }

            $this->answer=array("accounts"=>$return_txn);
            $this->answerType = 'success';
            $this->answerMsg = '';
        }
    }
    
    protected function CheckAccount(){
        if(is_array($_POST)) extract(TrimArray($_POST));
        if($currency=="UXC"){
            if(strlen($wallet)==42){
                $this->answerType = 'success';
                $this->answerMsg = '';
            }else{
                $this->answerType = 'error';
                $this->answerMsg = '';
            }
        }
    }
                   
}
?>