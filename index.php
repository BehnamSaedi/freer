<?php
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);
/*
  Virtual Freer
  http://freer.ir/virtual

  Copyright (c) 2011 Mohammad Hossein Beyram, freer.ir

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License v3 (http://www.gnu.org/licenses/gpl-3.0.html)
  as published by the Free Software Foundation.
*/
//------------------ Load Configuration
include 'include/configuration.php';
//------------------ Start Smarty
include 'include/startSmarty.php';

	if($post['action'] != '' || $get['action'] != '') 
	{
		if($post['action'] == '')
		{
			$data[action] 	= $get['action'];
			$data[card]		= $get['card']; 
			$data[qty]		= $get['qty']; 
			$data[gateway]	= $get['gateway'];
			$data[email]	= $get['email'];
			$data[mobile]	= $get['mobile'];
			
/**/		$data[user]		= $get['user'];
/**/		$data[pass]		= $get['pass'];
			$data[update]	= $get['update'];
			$data[copun]	= $get['copun'];
			$data[amount]	= $get['amount'];
			$noJavaScript 	= 1;
		} else {
			$data[action] 	= $post['action'];
			$data[card]		= $post['card'];
			$data[qty]		= $post['qty'];
			$data[gateway]	= $post['gateway'];
			$data[email]	= $post['email'];
			$data[mobile]	= $post['mobile'];
			
/**/		$data[user]		= $post['user'];
/**/		$data[pass]		= $post['pass'];
			$data[update] 	= $post['update'];
			$data[copun]	= $post['copun'];
			$data[amount]	= $post['amount'];
			$noJavaScript 	= 0;
		}
	}
	if ($data[action] == "check")
	{
		if (!$data[card])
			$error	.= '<span style="color: #ff7800;">محصولی انتخاب نکرده‌اید.‌<br />';
		if (!$data[qty])
			$error	.= '<span style="color: #ff7800;">تعداد کارت درخواستی مشخص نشده است.‌<br />';
		if (!$data[copun])
			$error	.= '<span style="color: #ff7800;">کد تخفیفی وارد نکرده‌اید.‌<br />';
		if ($data[card] AND $data[qty] AND $data[copun])
		{
			$query	= 'SELECT * FROM `copun` WHERE `copun_code` = "'.$data[copun].'" AND `copun_status` = "1" LIMIT 1';
			$copun	= $db->fetch($query);
			if(!$copun)
				$error .= '<span style="color: #ff7800;">کد تخفیف نامعتبر است.<br />';
			else
			{
				
				if($copun[copun_user_count] != '-1' &&  $copun[copun_user_count] == '0')
				{
					$error .= '<span style="color: #ff7800;">تخفیف مورد نظر تمام شده است.<br />';	
				}
				$time_from = mktime(0,0,0,date("n"),date("j"),date("Y"));
				if($copun[copun_form_time] && $copun[copun_form_time] > $time_from )
				{
					$error .= '<span style="color: #ff7800;">زمان استفاده این تخفیف نرسیده است.<br />';	
				}
				if($copun[copun_to_time] && $copun[copun_to_time] <= $time_from   )
				{
					$error .= '<span style="color: #ff7800;">زمان استفاده این تخفیف گذشته است.<br />';	
				}
				
				if(!$error)
				{
					$amount	= $db->retrieve('product_price','product','product_id',$data[card])*$data[qty];
					$query	= 'SELECT * FROM `copun_pro` WHERE `copun_id` = "'.$copun[copun_id].'" AND `copun_pro_proid` = "'.$data[card].'" LIMIT 1';
					$copun_pro	= $db->fetch($query);
					if($copun_pro[copun_pro_amount] != '' && $copun_pro[copun_pro_type] != '')
					{
						if($copun_pro[copun_pro_amount] != '0')
						{
							if($copun_pro[copun_pro_type] == 1)
								$discount	= $copun_pro[copun_pro_amount];
							else
								$discount	= ceil($copun_pro[copun_pro_amount]*($amount/100));
						}else
						{
							$error = '<span style="color: #ff7800;">محصول انتخابی شما شامل این کد تخفیف نمی باشد ';
							echo $error.'__2';
							exit;
						}
								
					}else
					{
						if($copun[copun_type1] == 1)
							$discount	= $copun[copun_amount1];
						else
							$discount	= ceil($copun[copun_amount1]*($amount/100));
					}
				}
			}
		}
		if ($error)
			echo $error.'__2';
		else
			echo Convertnumber2farsi($discount).'__'.Convertnumber2farsi($amount-$discount);
		exit;
	}
	elseif ($data[action] == "payit")
	{
		if (!$data[card])
			$error	.= '<span style="color: #ff0000;
float:center;left:10px;bottom:550px;position:fixed;z-index:1000;">محصولی انتخاب نکرده‌اید.‌<br />';
		if (!$data[qty])
			$error	.= '<span style="color: #ff7800;">تعداد کارت درخواستی مشخص نشده است.‌<br />';
		if ($data[card] AND $data[qty])
		{
			$count_query	= 'SELECT COUNT(*) FROM `card` WHERE `card_product` = "'.$data[card].'" AND (`card_res_time` < "'.($now-(60*$config[card][reserveExpire])).'" OR `card_res_time` = "" OR `card_res_time` IS NULL) AND `card_status` = "1" AND `card_show` = "1"';
			$count_card		= $db->fetch($count_query);
			$total_card		= $count_card['COUNT(*)'];
			if ($total_card < $data[qty])
				if ($total_card != 0)
					$error .= 'متاسفانه تعداد کارت درخواستی شما در حال حاضر موجود نمی‌باشد٬ شما الان می‌توانید حداکثر '.Convertnumber2farsi($total_card).' کارت از این نوع سفارش دهید.<br />';
				else
					$error .= 'متاسفانه کارت درخواستی شما در حال حاضر موجود نمی‌باشد.‌<br />';
		}
		if (!$data[gateway])
			$error	.= '<span style="color: #ff7800;">دروازه پرداخت را مشخص نکرده اید.‌<br />';
		
		$input_validate	= $db->retrieve('config_input_validate','config','config_id',1);
		if ($input_validate)
		{
		$normal	= $db->retrieve('normal_buy','product','product_id',$data[card]);
		if( $normal != '1')
		{
/**/		if (!$data[user] OR !$data[pass])
				$error	.= '<span style="color: #ff0000;
float:center;left:10px;bottom:525px;position:fixed;z-index:1000;">لطفا نام کاربری و پسورد را وارد نمایید.<br />';
			if ( $data[user] == "admin" )
				$error	.= 'این نام کاربری قابل قبول نمیباشد<br />';
			elseif($data[card]!=null && $data[update] == null )
			{ 
			
				$sql	= 'SELECT * FROM `plugin` WHERE `plugin_uniq` = "ibsng" AND `plugin_status` = "1";';
				$plugins	= $db->fetchAll($sql);
				if($plugins)
					foreach($plugins as $plugin)
					{
						require_once('plugins/'.$plugin[plugin_uniq].'.php');
						
						$sql			= 'SELECT * FROM `plugindata` WHERE `plugindata_uniq` = "'.$plugin[plugin_uniq].'";';
						$plugindatas	= $db->fetchAll($sql);
						if ($plugindatas)
							foreach($plugindatas as $plugindata)
							{
								$data1[$plugindata[plugindata_field_name]] = $plugindata[plugindata_field_value];
							}
						$sql = 'SELECT `product_third_field_title` FROM `product` WHERE `product_id` = "'.$data[card].'";';
						$product	= $db->fetch($sql);
						require_once('plugins/ibsng.php');
						$ibs = new IBSng($data1[username], $data1[password], $data1[url]); 
						$check = $ibs->userExist($data[user]);
						
						if($check !== false)
						{
							$error	.= '<h2><span style="color: #cd0270;
float:center;left:10px;bottom:565px;position:fixed;z-index:1000;">نام کاربری انتخابی موجود هست اگر قصد تمدید اکانت را دارید تیک تمدید را انتخاب کنید.<br /></h2>';
						}
						unset($data1);
						unset($product);
					}
			}
			}
			
			if (!$data[email])
				$error	.= '<span style="color: #ff0000;
float:center;left:10px;bottom:500px;position:fixed;z-index:1000;">برای استفاده از پشتیبانی سایت ایمیل یا شماره همراه خود را وارد کنید.‌<br />';
			if ($data[email] AND filter_var($data[email], FILTER_VALIDATE_EMAIL)== false)
				$error .= 'ایمیل وارد شده نامعتبر است.<br />';
		}
		$flag = false;
		$discount = 0;
		if ($data[copun])
		{
			$query	= 'SELECT * FROM `copun` WHERE `copun_code` = "'.$data[copun].'" AND `copun_status` = "1" LIMIT 1';
			$copun	= $db->fetch($query);
			if(!$copun)
				$error .= '<span style="color: #ff7800;">کد تخفیف نامعتبر است.<br />';
			else
			{
				$flag = true;
				//
				$query	= 'SELECT * FROM `copun` WHERE `copun_code` = "'.$data[copun].'" AND `copun_status` = "1" LIMIT 1';
				$copun	= $db->fetch($query);
				if(!$copun)
					$error .= '<span style="color: #ff7800;">کد تخفیف نامعتبر است.<br />';
				else
				{
					
					if($copun[copun_user_count] != '-1' &&  $copun[copun_user_count] == '0')
					{
						$error .= '<span style="color: #ff7800;">تخفیف مورد نظر تمام شده است.<br />';	
					}
					
					$time_from = mktime(0,0,0,date("n"),date("j"),date("Y"));
					if($copun[copun_form_time] && $copun[copun_form_time] > $time_from )
					{
						$error .= '<span style="color: #ff7800;">زمان استفاده این تخفیف نرسیده است.<br />';	
					}
					if($copun[copun_to_time] && $copun[copun_to_time] <= $time_from   )
					{
						$error .= '<span style="color: #ff7800;">زمان استفاده این تخفیف گذشته است.<br />';	
					}
					
					if(!$error)
					{
						$amount	= $db->retrieve('product_price','product','product_id',$data[card])*$data[qty];
						$query	= 'SELECT * FROM `copun_pro` WHERE `copun_id` = "'.$copun[copun_id].'" AND `copun_pro_proid` = "'.$data[card].'" LIMIT 1';
						$copun_pro	= $db->fetch($query);
						if($copun[copun_user_count] != '-1')
						{
							$db->execute('UPDATE `copun` SET `copun_user_count` = `copun_user_count` - 1 WHERE `copun_id` = '.$copun[copun_id].';');
						}
						if($copun_pro[copun_pro_amount] != '' && $copun_pro[copun_pro_type] != '')
						{
							if($copun_pro[copun_pro_type] == 1)
								$discount	= $copun_pro[copun_pro_amount];
							else
								$discount	= ceil($copun_pro[copun_pro_amount]*($amount/100));
									
						}else
						{
							if($copun[copun_type1] == 1)
								$discount	= $copun[copun_amount1];
							else
								$discount	= ceil($copun[copun_amount1]*($amount/100));
						}
					}
				}
					//
			}
		}
		if($error)
			echo $error.'__2';
		else
		{
			$insert[payment_user]		= $request[PHPSESSID];
			$insert[payment_email]		= $data[email];
			$insert[payment_mobile]		= $data[mobile];
			$insert[payment_amount]		= ($db->retrieve('product_price','product','product_id',$data[card])*$data[qty])-$discount;
			$insert[payment_gateway]	= $data[gateway];
			$insert[payment_time]		= $now;
			$insert[payment_ip]			= $server[REMOTE_ADDR];
			$insert[qty]		    	= $data[qty];
			if($flag)
			{
				$insert[copun_code]		    	=$data[copun];
			}
			$sql 						= $db->queryInsert('payment', $insert);
			$db->execute($sql);
			$payment_id 				= mysql_insert_id();
			
			$randlen					= 9-strlen($payment_id);
			$update[payment_rand]		= $payment_id.get_rand_id($randlen);
			$sql = $db->queryUpdate('payment', $update, 'WHERE `payment_id` = "'.$payment_id.'" LIMIT 1;');
			$db->execute($sql);
			$random						= $update[payment_rand];
			unset($update);
			$normal	= $db->retrieve('normal_buy','product','product_id',$data[card]);
		if( $normal != '1')
		{
			$sql 		= "INSERT INTO `card` SET `card_product` = '$data[card]', `card_first_field` = ENCODE('$data[user]','".$config[databaseInfo][salt]."'), `card_second_field` = ENCODE('$data[pass]','".$config[databaseInfo][salt]."'), `card_third_field` = ENCODE('','".$config[databaseInfo][salt]."'), `card_time` = '$now', `card_status` = '1', `card_show` = '1',`card_payment_id` = $payment_id";
			$db->execute($sql);
			}else
			{
			$update[card_customer_email]	= $data[email];
			$update[card_customer_mobile]	= $data[mobile];
			$update[card_res_user]			= $request[PHPSESSID];
			$update[card_res_time]			= $now;
			$update[card_payment_id]		= $payment_id;
			$sql = $db->queryUpdate('card', $update, 'WHERE `card_product` = "'.$data[card].'" AND (`card_res_time` < "'.($now-(60*$config[card][reserveExpire])).'" OR `card_res_time` = "" OR `card_res_time` IS NULL) AND `card_status` = "1" AND `card_show` = "1" LIMIT '.$data[qty].';');
			$db->execute($sql);
			}
			echo 'gateway.php?random='.$random.'__1';
		}
		exit;
	}
else if($data[action] == "amount") {
		if ($data[amount] < 1000)
			$error	.= 'مبلغ وارد شده باید بیشتر از 1000 ریال باشد.‌<br />';
		if ($data[amount] > 100000000)
			$error	.= 'مبلغ وارد شده باید کمتر از 100000000 ریال باشد.‌<br />';
		if (!$data[gateway])
			$error	.= 'دروازه پرداخت را مشخص نکرده اید.‌<br />';
		$input_validate	= $db->retrieve('config_input_validate','config','config_id',1);
		if ($input_validate)
		{
			if (!$data[email] AND !$data[mobile])
				$error	.= 'برای استفاده از پشتیبانی سایت ایمیل یا شماره همراه خود را وارد کنید.‌<br />';
			if ($data[email] AND filter_var($data[email], FILTER_VALIDATE_EMAIL)== false)
				$error .= 'ایمیل وارد شده نامعتبر است.<br />';
			if ($data[mobile] AND !eregi("^09([0-9]{9})$", $data[mobile]))
				$error .= "شماره همراه نامعتبر است.<br />";
		}
		if($error)
		{
			if($noJavaScript == 0) {
				echo $error.'__2';
			} else if($noJavaScript == 1) {
				$smarty->display('error.tpl');
				?>
				<div style="position: fixed; z-index: 1001; left: 0px; width: 100%; margin: 0px; opacity: 0.75; top: 0px;" id="showMessage" class="error"><div style="width: 90%; margin: 1em auto; padding: 0.5em;"><ul style="font-weight: bold; margin-left: 0px; padding-left: 0px;"><li style="list-style: none outside none; font-size: 21px; line-height: 2; text-align: center;"><?=$error?></li></ul></div></div>
				<?
			}
		}
		else
		{
			$data[card] = 0;
			$data[qty] = 1;
			$insert[payment_user]		= $_COOKIE[PHPSESSID];
			$insert[payment_email]		= $data[email];
			$insert[payment_mobile]		= $data[mobile];
			$insert[payment_amount]		= $data[amount];
			$insert[payment_gateway]	= $data[gateway];
			$insert[payment_time]		= $now;
			$insert[payment_ip]			= $server[REMOTE_ADDR];
			$sql 						= $db->queryInsert('payment', $insert);
			$db->execute($sql);
			$payment_id 				= mysql_insert_id();
			$randlen					= 9-strlen($payment_id);
			$update[payment_rand]		= $payment_id.get_rand_id($randlen);
			$sql = $db->queryUpdate('payment', $update, 'WHERE `payment_id` = "'.$payment_id.'" LIMIT 1;');
			$db->execute($sql);
			$random						= $update[payment_rand];
			unset($update);
			
			$update[card_customer_email]	= $data[email];
			$update[card_customer_mobile]	= $data[mobile];
			$update[card_res_user]			= $_COOKIE[PHPSESSID];
			$update[card_res_time]			= $now;
			$update[card_payment_id]		= $payment_id;
			$sql = $db->queryUpdate('card', $update, 'WHERE `card_product` = "'.$data[card].'" AND (`card_res_time` < "'.($now-(60*$config[card][reserveExpire])).'" OR `card_res_time` = "" OR `card_res_time` IS NULL) AND `card_status` = "1" AND `card_show` = "1" LIMIT '.$data[qty].';');
			$db->execute($sql);
			if($noJavaScript == 0) {
				echo 'gateway.php?random='.$random.'__1';
			} else if($noJavaScript == 1) {
				$request[random] = $random;
				require 'gateway.php';
			}
		}
		exit;
	}
	$query		= 'SELECT * FROM `category` WHERE `category_parent_id` = "0" ORDER BY `category_order`';
	$categories	= $db->fetchAll($query);
	if ($categories)
		foreach ($categories as $key => $category)
		{
			if ($categories[$key][category_image])
				$categories[$key][category_image] = $config[MainInfo][url].$config[MainInfo][upload][image].'resized/category_'.$category[category_image];
			$query		= 'SELECT * FROM `product` WHERE `product_category` = "'.$category[category_id].'" ORDER BY `product_id` ASC';
			$categories[$key][products]	= $db->fetchAll($query);
			if ($categories[$key][products])
				foreach ($categories[$key][products] as $product_key => $product)
				{
					$count_query	= 'SELECT COUNT(*) FROM `card` WHERE `card_product` = "'.$product[product_id].'" AND (`card_res_time` < "'.($now-(60*$config[card][reserveExpire])).'" OR `card_res_time` = "" OR `card_res_time` IS NULL) AND `card_status` = "1" AND `card_show` = "1"';
					$count_card		= $db->fetch($count_query);
					$total_card		= $count_card['COUNT(*)'];
					$categories[$key][products][$product_key][counter] = $total_card;
				}
		}

	$query				= 'SELECT * FROM `plugin` WHERE `plugin_type` = "payment" AND `plugin_status` = "1"';
	$payment_methods	= $db->fetchAll($query);

	for ($i=0;$i<768;$i=$i+32)	{
		$banks_logo 	.= '<li style="background-position: -'.$i.'px 0px;"></li>';
	}


	//-- نمایش صفحه
	$query	= 'SELECT * FROM `config` WHERE `config_id` = "1" LIMIT 1';
	$config	= $db->fetch($query);
	$smarty->assign('config', $config);
	$smarty->assign('categories', $categories);
	$smarty->assign('products', $products);
	$smarty->assign('payment_methods', $payment_methods);
	$smarty->assign('banks_logo', $banks_logo);
	$smarty->display('index.tpl');
	exit;