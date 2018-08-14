<?php
//exit();
header('Content-Type: text/html; charset=utf-8');
$_GET['popup']=1;
ini_set('memory_limit','8M');

require $_SERVER['DOCUMENT_ROOT'].'/application/core/route.php';

$folder_log = FILESROOT . "/log/".date('Y')."/".date('m')."/".date('d'); 
if(!file_exists($folder_log)) mkdir($folder_log, 0777, true);
$file_log = $folder_log.'/'."log".date("d_m_Y").".txt";

$wallets_to_update=dbSelectAll("SELECT wallet FROM ".DB_PREFIX.DB_NAME_1);
//var_dump($wallets_to_update);
if(is_array($wallets_to_update)){
    if(count($wallets_to_update)>0){
        foreach ($wallets_to_update as $wallet_to_update){
            if(strpos($wallet_to_update['wallet'],"0x")===0 && strlen($wallet_to_update['wallet'])==42)
                $wallets_arr[]=trim($wallet_to_update['wallet']);
        }
    }
}else{
    file_put_contents($file_log,"Error SELECT\n",FILE_APPEND);
    die();
}

$time_start = getmicrotime();
file_put_contents($file_log,"Start get Balance FOX and UXC at ".date("H:i:s",$time_start)."\n",FILE_APPEND);
$addresses=call_node('balance_addr',array("wallets"=>$wallets_arr));
if($addresses->type=="success"){
    $time=getmicrotime() - $time_start;
    $diff=sprintf('%02d:%02d:%02d', $time / 3600, ($time % 3600) / 60, ($time % 3600) % 60);
    file_put_contents($file_log,"Answer received in ".$diff." (".$time." ms)\n",FILE_APPEND);
}else{
    $time=getmicrotime() - $time_start;
    $diff=sprintf('%02d:%02d:%02d', $time / 3600, ($time % 3600) / 60, ($time % 3600) % 60);
    file_put_contents($file_log,"Wrong answer received in ".$diff." (".$time." ms)\n".print_r($addresses,1)."\n",FILE_APPEND);
}

if($addresses->type=="success"){
    $db_balances_wallets=array();
    $exists_wallet=dbSelectAll("SELECT * FROM ".DB_PREFIX.DB_NAME." WHERE field in('".implode("','",$wallets_arr)."')");
    foreach ($exists_wallet as $db_balances_wallet=>$value) {
        $db_balances_wallets[$value['wallet']]=$value;
    }
//    file_put_contents($file_log,"exists_wallet:\n".print_r($db_balances_wallets,1)."\nEnd exists_wallet\n",FILE_APPEND);
    foreach ($addresses->answer->wallets as $address=>$wallet){
        if($db_balances_wallets[$address]['balance']!=number_format($wallet->fox->balance,8,'.','') || $db_balances_wallets[$address]['balance_']!=$wallet->uxc->balance){
                file_put_contents($file_log, date("H:i:s").' Update wallet '.$address.' Balance FOX: '.$wallet->fox->balance
                        .' Balance UXC: '.$wallet->uxc->balance."\n",FILE_APPEND);
                dbUpdate(DB_PREFIX.DB_NAME, array("balance"=>number_format($wallet->fox->balance,8,'.',''),"balance_"=>$wallet->uxc->balance),"wallet='".$address."'");
                dbDelete(DB_PREFIX.DB_NAME_1, "wallet='".$address."'");
        }else{
            file_put_contents($file_log, date("H:i:s").' Wallet '.$address.' has old Balance FOX: '.$wallet->fox->balance
                        .' Balance UXC: '.$wallet->uxc->balance."\n",FILE_APPEND);
        }
    }
}else{
    file_put_contents($file_log,"Database didn't update\n",FILE_APPEND);
}
$time=getmicrotime() - $time_start;
$diff=sprintf('%02d:%02d:%02d', $time / 3600, ($time % 3600) / 60, ($time % 3600) % 60);
file_put_contents($file_log,"Cron completed in ".$diff." (".$time." ms)\n\n",FILE_APPEND);
?>