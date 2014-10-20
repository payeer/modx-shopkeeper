<?php

    include_once $_SERVER['DOCUMENT_ROOT'].'/manager/includes/config.inc.php';
    include_once MODX_MANAGER_PATH.'includes/document.parser.class.inc.php';
    require_once MODX_BASE_PATH."assets/snippets/payment/config/payeer.php";

	if (isset($_POST['m_operation_id']) && isset($_POST['m_sign']))
	{
		$m_key = PAYEER_SECRET_KEY;
		
		$arHash = array($_POST['m_operation_id'],
				$_POST['m_operation_ps'],
				$_POST['m_operation_date'],
				$_POST['m_operation_pay_date'],
				$_POST['m_shop'],
				$_POST['m_orderid'],
				$_POST['m_amount'],
				$_POST['m_curr'],
				$_POST['m_desc'],
				$_POST['m_status'],
				$m_key);
				
		$sign_hash = strtoupper(hash('sha256', implode(':', $arHash)));
		
		$log_text = 
			"--------------------------------------------------------\n".
			"operation id		".$_POST["m_operation_id"]."\n".
			"operation ps		".$_POST["m_operation_ps"]."\n".
			"operation date		".$_POST["m_operation_date"]."\n".
			"operation pay date	".$_POST["m_operation_pay_date"]."\n".
			"shop				".$_POST["m_shop"]."\n".
			"order id			".$_POST["m_orderid"]."\n".
			"amount				".$_POST["m_amount"]."\n".
			"currency			".$_POST["m_curr"]."\n".
			"description		".base64_decode($_POST["m_desc"])."\n".
			"status				".$_POST["m_status"]."\n".
			"sign				".$_POST["m_sign"]."\n\n";
						
		if (PAYEER_LOGFILE != '')
		{	
			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/payeer_orders.log', $log_text, FILE_APPEND);
		}

		if ($_POST['m_sign'] == $sign_hash && $_POST['m_status'] == 'success')
		{
			$modx = new DocumentParser;
			
			$mod_table = $modx->db->config['table_prefix'] . "manager_shopkeeper";
			
			$m_orderid = $_POST['m_orderid'];
			
			$change_status = $modx->db->update(array('status' => 6), $mod_table, "id = $m_orderid");
			
			$modx->invokeEvent('OnSHKChangeStatus',array('order_id' => $m_orderid, 'status' => 6));
			
			echo $_POST['m_orderid'].'|success';
			
			exit;
		}
		
		$to = PAYEER_EMAILERR;
		$subject = "Error payment";
		$message = "Failed to make the payment through the system Payeer for the following reasons:\n\n";
		if ($_POST["m_sign"] != $sign_hash)
		{
			$message.=" - Do not match the digital signature\n";
		}
		if ($_POST['m_status'] != "success")
		{
			$message.=" - The payment status is not success\n";
		}
		$message .= "\n" . $log_text;
		$headers = "From: no-reply@" . $_SERVER['HTTP_SERVER']."\r\nContent-type: text/plain; charset=utf-8 \r\n";
		mail($to, $subject, $message, $headers);
				
		echo $_POST['m_orderid'] . '|error';
	}

?>
