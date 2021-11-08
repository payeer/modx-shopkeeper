<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/manager/includes/config.inc.php';
include_once MODX_MANAGER_PATH . 'includes/document.parser.class.inc.php';
require_once MODX_BASE_PATH . "assets/snippets/payment/config/payeer.php";

if (isset($_POST['m_operation_id']) && isset($_POST['m_sign']))
{
	$err = false;
	$message = '';
	
	// запись логов

	$log_text = 
	"--------------------------------------------------------\n" .
	"operation id		" . $_POST['m_operation_id'] . "\n" .
	"operation ps		" . $_POST['m_operation_ps'] . "\n" .
	"operation date		" . $_POST['m_operation_date'] . "\n" .
	"operation pay date	" . $_POST['m_operation_pay_date'] . "\n" .
	"shop				" . $_POST['m_shop'] . "\n" .
	"order id			" . $_POST['m_orderid'] . "\n" .
	"amount				" . $_POST['m_amount'] . "\n" .
	"currency			" . $_POST['m_curr'] . "\n" .
	"description		" . base64_decode($_POST['m_desc']) . "\n" .
	"status				" . $_POST['m_status'] . "\n" .
	"sign				" . $_POST['m_sign'] . "\n\n";
	
	$log_file = PAYEER_LOGFILE;
	
	if (!empty($log_file))
	{
		file_put_contents($_SERVER['DOCUMENT_ROOT'] . $log_file, $log_text, FILE_APPEND);
	}
	
	// проверка цифровой подписи и ip

	$sign_hash = strtoupper(hash('sha256', implode(":", array(
		$_POST['m_operation_id'],
		$_POST['m_operation_ps'],
		$_POST['m_operation_date'],
		$_POST['m_operation_pay_date'],
		$_POST['m_shop'],
		$_POST['m_orderid'],
		$_POST['m_amount'],
		$_POST['m_curr'],
		$_POST['m_desc'],
		$_POST['m_status'],
		PAYEER_SECRET_KEY
	))));
	
	$valid_ip = true;
	$sIP = str_replace(' ', '', PAYEER_IPFILTER);
	
	if (!empty($sIP))
	{
		$arrIP = explode('.', $_SERVER['REMOTE_ADDR']);
		if (!preg_match('/(^|,)(' . $arrIP[0] . '|\*{1})(\.)' .
		'(' . $arrIP[1] . '|\*{1})(\.)' .
		'(' . $arrIP[2] . '|\*{1})(\.)' .
		'(' . $arrIP[3] . '|\*{1})($|,)/', $sIP))
		{
			$valid_ip = false;
		}
	}
	
	if (!$valid_ip)
	{
		$message .= " - ip-адрес сервера не является доверенным\n" .
		"   доверенные ip: " . $sIP . "\n" .
		"   ip текущего сервера: " . $_SERVER['REMOTE_ADDR'] . "\n";
		$err = true;
	}

	if ($_POST['m_sign'] != $sign_hash)
	{
		$message .= " - не совпадают цифровые подписи\n";
		$err = true;
	}
	
	if (!$err)
	{
		// загрузка заказа
				
		$modx = new DocumentParser;
    $mod_table = $modx->db->config['table_prefix'] . "manager_shopkeeper";
    $order_id = $_POST['m_orderid'];
    $order = $modx->db->getRow($modx->db->select("id, short_txt, content, allowed, addit, price, currency, DATE_FORMAT(date,'%d.%m.%Y %k:%i') AS date, status, email, phone, payment, tracking_num, userid", $mod_table, "id = $order_id", "", ""));

		if (!$order) 
		{
			$message .= " - заказ не существует\n";
			$err = true;
		}
		else 
		{
			switch ($_POST['m_status'])
			{
				case 'success':
					$status = 6;
					break;
					
				default:
					$message .= " - статус платежа не является success\n";
					$status = 5;
					$err = true;
					break;
			}
			
			$change_status = $modx->db->update(array('status' => $status), $mod_table, "id = $order_id");
			$modx->invokeEvent('OnSHKChangeStatus', array('order_id' => $order_id, 'status' => $status));
		}
	}
	
	if ($err)
	{
		$to = PAYEER_EMAILERR;

		if (!empty($to))
		{
			$message = "Не удалось провести платёж через систему Payeer по следующим причинам:\n\n" . $message . "\n" . $log_text;
			$headers = "From: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\n" . 
			"Content-type: text/plain; charset=utf-8 \r\n";
			mail($to, 'Ошибка оплаты', $message, $headers);
		}
		
		exit($_POST['m_orderid'] . '|error');
	}
	else
	{
		exit($_POST['m_orderid'] . '|success');
	}
}
?>