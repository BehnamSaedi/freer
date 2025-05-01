<?php
/*
www.softiran.org
*/
//------------------ Load Configuration
include 'include/configuration.php';
//------------------ Start Smarty
include 'include/startSmarty.php';
session_regenerate_id();
$sql			= 'SELECT * FROM `plugindata` WHERE `plugindata_uniq` = "email";';
$plugindatas	= $db->fetchAll($sql);
if ($plugindatas)
foreach($plugindatas as $plugindata)
{
	$data[$plugindata[plugindata_field_name]] = $plugindata[plugindata_field_value];
}
	$query	= 'SELECT * FROM `config` WHERE `config_id` = "1" LIMIT 1';
	$conf	= $db->fetch($query);
	$smarty->assign('config', $conf);
	
	$data[title] = 'بازیابی اکانت';


if($post['post'] == 1)
{
	if(!$post['email'] AND !$post['mobile'])
	{
		$error = 'شما باید شماره موبایل یا ایمیتان را وارد کنید.<br />';
	}
	else
	{
		if(!$post['img'])
		{
			$error .="لطفا کد امنیتی را وارد کنید.<br />";
		}else
		{
			if($_SESSION['softiran_secimg'] != $post['img'])
			{
				$error .="کد امنیتی وارد شده صحیح نیست.<br />";
			}
			else
			{
				$_SESSION['softiran_secimg'] = rand();
				if($post['email'] AND !$post['mobile'])
				{
					$where = '`card_customer_email` = "'.$post['email'].'"';
				}
				elseif($post['mobile'] AND !$post['email'])
				{
					$where = '`card_customer_mobile` = "'.$post['mobile'].'"';
				}
				elseif($post['mobile'] AND $post['email'])
				{
					$where = '(`card_customer_email` = "'.$post['email'].'" AND `card_customer_mobile` = "'.$post['mobile'].'")';
				}
				
				$sql		= 'SELECT *,DECODE(card_first_field,"'.$config[databaseInfo][salt].'") as card_first_field ,DECODE(card_second_field,"'.$config[databaseInfo][salt].'") as card_second_field ,DECODE(card_third_field,"'.$config[databaseInfo][salt].'") as card_third_field FROM `card` WHERE `card_status` = "2" AND '.$where.' ORDER BY `card_id` DESC limit 150;';
				$cards		= $db->fetchAll($sql);

				if($cards)
				{
					$sms_text = $td_body = $table_body = '';

						foreach($cards as $card):
							$sql		= 'SELECT * FROM `product` WHERE `product_id` = "'.$card[card_product].'";';
							$product	= $db->fetch($sql);
							$td_body .= '<td style="text-align:center; font-family:tahoma; font-size:12px;">'.$product[product_title].'</td>';
							if($product[product_first_field_title])
							{
								$td_body .= '<td style="text-align:center; font-family:tahoma; font-size:12px;">'.$card[card_first_field].'</td>';
							}
							if($product[product_second_field_title])
							{
								$td_body .= '<td style="text-align:center; font-family:tahoma; font-size:12px;">'.$card[card_second_field].'</td>';
							}
							if($product[product_third_field_title])
							{
								$td_body .= '<td style="text-align:center; font-family:tahoma; font-size:12px;">'.$card[card_third_field].'</td>';
							}
							$td_body .= '<td style="text-align:center; font-family:tahoma; font-size:12px;">'.Convertnumber2farsi(pdate('d M Y',$card[card_payment_time])).'</td>';
							$table_body .= '<tr>'.$td_body.'</tr>';
							unset($td_body);
							if(!$post['email'] AND $card[card_customer_email])
							{
								$post['email'] = $card[card_customer_email];
							}
							$sms_text = 'نوع:' . $product[product_title] . "\r\n";
							if($product[product_first_field_title]!="")
								$sms_text .= $product[product_first_field_title] . ': ' . $card[card_first_field];
							if($card[card_second_field]!="")
								$sms_text .= "\r\n" . $product[product_second_field_title] . ': ' . $card[card_second_field];
							if($card[card_third_field]!="")
								$sms_text .=  "\r\n" . $product[product_third_field_title] . ': ' . $card[card_third_field];
							
						endforeach;
						$td_body .= '<td style="background:#CCCCCC; padding:5px 0; text-align:center; font-family:tahoma; font-size:12px; font-weight:bold;">نوع</td>';
						if($product[product_first_field_title])
						{
							$td_body .= '<td width="25%" style="background:#CCCCCC; text-align:center; font-family:tahoma; font-size:12px; font-weight:bold;">'.$product[product_first_field_title].'</td>';
						}
						if($product[product_second_field_title])
						{
							$td_body .= '<td width="25%" style="background:#CCCCCC; text-align:center; font-family:tahoma; font-size:12px; font-weight:bold;">'.$product[product_second_field_title].'</td>';
						}
						if($product[product_third_field_title])
						{
							$td_body .= '<td width="25%" style="background:#CCCCCC; text-align:center; font-family:tahoma; font-size:12px; font-weight:bold;">'.$product[product_third_field_title].'</td>';
						}
						$td_body .= '<td style="background:#CCCCCC; padding:5px 0; text-align:center; font-family:tahoma; font-size:12px; font-weight:bold;">تاریخ خرید</td>';
						$table = '<table style="margin-left:auto; margin-right:auto; width:90%;">'.
							 '<tr>'.
							 $td_body.
							 '</tr>'.
							 $table_body.
							 '</table>';
						if($post['email'])
						{
							send_mail($data[email],$data[name],$post['email'],$post['email'],$data[title],$table,$data[signature]);
							
						}
						else
						{
							$error = 'متاسفانه ایمیلی از شما برای ارسال اطلاعات اکانتهای شما یافت نشد.<br />';
						}
						if($post['mobile'])
						{
							$sql			= 'SELECT plugin_status FROM `plugin` WHERE `plugin_uniq` = "mida" LIMIT 1;';
							$plug	= $db->fetch($sql);
							if($plug[plugin_status] == '1')
							{
								$sql			= 'SELECT * FROM `plugindata` WHERE `plugindata_uniq` = "mida";';
								$plugindatas	= $db->fetchAll($sql);
								if ($plugindatas)
								foreach($plugindatas as $plugindata)
								{
									$data2[$plugindata[plugindata_field_name]] = $plugindata[plugindata_field_value];
								}
							}
							$sms_text=urlencode($sms_text);
							if($data2)
								@file_get_contents('http://sms.mida-co.ir/url.php?from='.$data2[sender_number].'&to='.$post['mobile'].'&text='.$sms_text.'&password='.$data2[password].'&username='.$data2[username]);
						}
				}
				else
				{
					$error = 'خریدی با مشخصات وارد شده یافت نشد.<br />';
				}
			}
		}
	}
		
	if($error)
	{
		$smarty->assign('error', $error);
		$smarty->assign('show', 'form');
	}
	else
	{
		$message = 'اطلاعات اکانت(هاي) خريداري شده شما به آدرس ايميل و شماره همراه ارسال شد.<br />';
		$smarty->assign('show', 'message');
		$smarty->assign('message', $message);
		$_SESSION['softiran_secimg'] = 0;
	}
}
else
{
	//-- نمایش پیغام خطا
	$smarty->assign('show', 'form');	
}

	$smarty->display('restore.tpl');
	exit;
?>