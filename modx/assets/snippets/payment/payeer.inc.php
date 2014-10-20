<?php

require_once MODX_BASE_PATH."assets/snippets/payment/config/payeer.php";

switch ($_REQUEST['action'])
{
    case 'fail':
		$dbprefix = $modx->db->config['table_prefix'];
		
		$mod_table = $dbprefix."manager_shopkeeper";
		
		$m_orderid = $_REQUEST['m_orderid'];
		
		$change_status = $modx->db->update(array('status' => 5), $mod_table, "id = $m_orderid");
		
		$modx->invokeEvent('OnSHKChangeStatus',array('order_id'=>$m_orderid,'status'=>5));
		
		$output = '<br/> <h1>Your order was cancelled.</h1>';
		
        return $output;
		
        break;
		
    case 'success':
		$output = '<br/><h1>Your order is successfully paid!</h1>';
		
        return $output;
		
        break;
		
    default:
		$m_shop = PAYEER_MERCHANT_ID;
		
		$dbprefix = $modx->db->config['table_prefix'];
		
		$mod_table = $dbprefix . "manager_shopkeeper";
		
		$m_orderid = $modx->db->getValue($modx->db->select("id", $mod_table, "", "id desc limit 1", ""));
		
		$amount = $modx->db->getValue($modx->db->select("price", $mod_table, "id = $m_orderid", "", ""));
		
		$m_amount = str_replace (',', '.', $amount); 

		$change_status = $modx->db->update(array('status' => 2), $mod_table, "id = $m_orderid");
		
		$modx->invokeEvent('OnSHKChangeStatus',array('order_id'=>$m_orderid, 'status' => 2));

		$m_desc = base64_encode('Payment order No. ' . $m_orderid);
		
		$m_curr = PAYEER_CURRENCY_CODE;
		
		$m_key = PAYEER_SECRET_KEY;

		$arHash = array(
			$m_shop,
			$m_orderid,
			$m_amount,
			$m_curr,
			$m_desc,
			$m_key
		);
		$sign = strtoupper(hash('sha256', implode(':', $arHash)));
		
		// проверка принадлежности ip списку доверенных ip
		$list_ip_str = str_replace(' ', '', PAYEER_IPFILTER);
		
		if (!empty($list_ip_str)) 
		{
			$list_ip = explode(',', $list_ip_str);
			$this_ip = $_SERVER['REMOTE_ADDR'];
			$this_ip_field = explode('.', $this_ip);
			$list_ip_field = array();
			$i = 0;
			$valid_ip = FALSE;
			foreach ($list_ip as $ip)
			{
				$ip_field[$i] = explode('.', $ip);
				if ((($this_ip_field[0] ==  $ip_field[$i][0]) or ($ip_field[$i][0] == '*')) and
					(($this_ip_field[1] ==  $ip_field[$i][1]) or ($ip_field[$i][1] == '*')) and
					(($this_ip_field[2] ==  $ip_field[$i][2]) or ($ip_field[$i][2] == '*')) and
					(($this_ip_field[3] ==  $ip_field[$i][3]) or ($ip_field[$i][3] == '*')))
					{
						$valid_ip = TRUE;
						break;
					}
				$i++;
			}
		}
		else
		{
			$valid_ip = TRUE;
		}
		
		if ($valid_ip)
		{
			$output  = '
			<br/><img src="https://payeer.com/bitrix/templates/difiz/images/logo.png">
			<p>Payeer® Merchant allows you to accept international payments by a lot of payment methods around the world!</p><br/>
			<form action="' . PAYEER_MERCHANT_URL . '" method="get">
			<input type="hidden" name="m_shop" value="' . $m_shop . '">
			<input type="hidden" name="m_orderid" value="' . $m_orderid . '">
			<input type="hidden" name="m_amount" value="' . $m_amount . '">
			<input type="hidden" name="m_curr" value="' . $m_curr . '">
			<input type="hidden" name="m_desc" value="' . $m_desc . '">
			<input type="hidden" name="m_sign" value="' . $sign . '">
			<input type="submit" name="m_process" value="To pay via Payeer">';
		}
		else
		{
			$log_text = 
				"--------------------------------------------------------\n".
				"shop				" . $m_shop . "\n".
				"order id			" . $m_orderid . "\n".
				"amount				" . $m_amount . "\n".
				"currency			" . $m_curr . "\n".
				"description		" . base64_decode($m_desc) . "\n".
				"sign				" . $sign . "\n\n";

			$to = PAYEER_EMAILERR;
			$subject = "Error payment";
			$message = "Failed to make the payment through the system Payeer for the following reasons:\n\n";
			$message .= " - the ip address of the server is not trusted\n";
			$message .= "   trusted ip: " . PAYEER_IPFILTER . "\n";
			$message .= "   ip of the current server: " . $_SERVER['REMOTE_ADDR'] . "\n";
			$message .= "\n" . $log_text;
			$headers = "From: no-reply@" . $_SERVER['HTTP_SERVER'] . "\r\nContent-type: text/plain; charset=utf-8 \r\n";
			mail($to, $subject, $message, $headers);

			$dbprefix = $modx->db->config['table_prefix'];
		
			$mod_table = $dbprefix . "manager_shopkeeper";
			
			$m_orderid = $modx->db->getValue($modx->db->select("id", $mod_table, "", "id desc limit 1", ""));
			
			$change_status = $modx->db->update(array('status' => 5), $mod_table, "id = $m_orderid");
			
			$modx->invokeEvent('OnSHKChangeStatus',array('order_id' => $m_orderid, 'status'=>5));
			
			$output  = '<br/> <h1>Your order was cancelled. A letter sent in support</h1>';
		}
		
		return $output;
    break;
}
?>