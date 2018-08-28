<?php
namespace app\models;
use Yii;
class Paynow{
public function paynows($amount){
$siteurl="http://delon.co.zw/canteen/frontend/web/index.php?";//substitute with your own return url	
define('ps_error', 'Error');
define('ps_ok','Ok');
define('ps_created_but_not_paid','created but not paid');
define('ps_cancelled','cancelled');
define('ps_failed','failed');
define('ps_paid','paid');
define('ps_awaiting_delivery','awaiting delivery');
define('ps_delivered','delivered');
define('ps_awaiting_redirect','awaiting redirect');
define('site_url', $siteurl);

$int_key="######################";//get from paynow.co.zw
$int_id=;//get from paynow.co.zw, it should be an intenger 
$paymentid="testID1234hs";
$url="https://www.paynow.co.zw/interface/initiatetransaction/?";
$reference=sha1(Yii::$app->user->identity->email);
$returnurl="http://delon.com/canteen/froentend/web/index.php?r=credit/index"; //substitute with your own return urls
$resulturl="http://delon.com/canteen/froentend/web/index.php?r=credit/index"; //substitute with your own return urls
$authemail="test@afrodeb.com";//This is the buyer's email address
$additionalinfo="Paying for canteen meals.";
$concat=$int_key.$int_id.$paymentid.$url.$reference.$returnurl.$resulturl.$authemail.$additionalinfo;
$concat=$concat.$int_key;
$values = array('resulturl' => $resulturl,
			'returnurl' =>  $returnurl,
			'reference' =>  $reference,
			'amount' =>  $amount,			
			'id' =>  $int_id,
			'additionalinfo' =>  $additionalinfo,
			'authemail' =>  $authemail,
			'authphone' =>  "0773553310",
			'status' =>  'Message'); //just a simple message
			
$fields_string = $this->CreateMsg($values,$int_key);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false); //need fixing
$result = curl_exec($ch);
if($result)
	{
		$msg = $this->ParseMsg($result);		
		if ($msg["status"] == ps_error){
			header("Location: $checkout_url");			
			exit;
		}
		else if ($msg["status"] == "Ok"){
			$validateHash = $this->CreateHash($msg, $int_key);
			if($validateHash != $msg["hash"]){
				$error =  "Paynow reply hashes do not match : " . $validateHash . " - " . $msg["hash"];
				echo $error;
			}
			else
			{				
				$theProcessUrl = $msg["browserurl"];
            //echo $theProcessUrl; 
            //header("Location: ".$theProcessUrl);
				Yii::$app->response->redirect($theProcessUrl);
				$orders_array = array();				
			}
		}
		else {						
			//unknown status or one you dont want to handle locally
			$error =  "Invalid status from Paynow, cannot continue.";
		}

	}
	else
	{
	   $error = curl_error($ch);
	   echo $error;
	}
	//print_r($result);
	//close connection
	curl_close($ch);

}
public function ParseMsg($msg) {
	$parts = explode("&",$msg);
	$result = array();
	foreach($parts as $i => $value) {
		$bits = explode("=", $value, 2);
		$result[$bits[0]] = urldecode($bits[1]);
	}

	return $result;
}

function CreateMsg($values, $MerchantKey){
	$fields = array();
	foreach($values as $key=>$value) {
	   $fields[$key] = urlencode($value);
	}

	$fields["hash"] = urlencode($this->CreateHash($values, $MerchantKey));

	$fields_string = $this->UrlIfy($fields);
	return $fields_string;
}

public function UrlIfy($fields) {
	$delim = "";
	$fields_string = "";
	foreach($fields as $key=>$value) {
		$fields_string .= $delim . $key . '=' . $value;
		$delim = "&";
	}

	return $fields_string;
}


 public function CreateHash($values, $MerchantKey){
	$string = "";
	foreach($values as $key=>$value) {
		if( strtoupper($key) != "HASH" ){
			$string .= $value;
		}
	}
	$string .= $MerchantKey;
	
	$hash = hash("sha512", $string);
	return strtoupper($hash);
}
	


}
?>