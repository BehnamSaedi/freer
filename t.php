<?php
include_once('include/libs/nusoap.php');
		@session_start();
		$client = new nusoap_client('https://pna.shaparak.ir/ref-payment2/jax/merchantService?wsdl',true);
		$result = $client->call('MerchantLogin', array('param' => array('Password' => "tr4@8mh", 'UserName' => "011382173")));
	var_export($result);
		
		