<?php
require_once MODX_BASE_PATH . "assets/snippets/payment/config/payeer.php";

switch ($_GET['m_status'])
{
    case 'fail':
		$output = '<br/> <h1>Ваш заказ был отменен.</h1>';
        return $output;
        break;
		
    case 'success':
		$output = '<br/><h1>Ваш заказ успешно оплачен!</h1>';
        return $output;
        break;
		
    default:
		$m_shop = PAYEER_MERCHANT_ID;
		$dbprefix = $modx->db->config['table_prefix'];
		$mod_table = $dbprefix . "manager_shopkeeper";
		$m_orderid = $modx->db->getValue($modx->db->select("id", $mod_table, "", "id desc limit 1", ""));
		
		if (!$m_orderid)
		{
			$m_orderid = 1;
		}
		
		$amount = $modx->db->getValue($modx->db->select("price", $mod_table, "id = $m_orderid", "", ""));
		$m_amount = number_format($amount, 2, '.', '');
		$change_status = $modx->db->update(array('status' => 2), $mod_table, "id = $m_orderid");
		$modx->invokeEvent('OnSHKChangeStatus', array('order_id'=>$m_orderid, 'status' => 2));
		$m_desc = base64_encode(PAYEER_ORDER_DESC);
		$m_curr = PAYEER_CURRENCY_CODE == 'RUR' ? 'RUB' : PAYEER_CURRENCY_CODE;
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
		
		$output  = '
		<br/><img src="https://payeer.com/bitrix/templates/difiz/images/logo.png">
		<p>Payeer® Merchant позволяет принимать платежи всеми возможными способами по всему миру!</p>
		<form action="' . PAYEER_MERCHANT_URL . '" method="get">
		<input type="hidden" name="m_shop" value="' . $m_shop . '">
		<input type="hidden" name="m_orderid" value="' . $m_orderid . '">
		<input type="hidden" name="m_amount" value="' . $m_amount . '">
		<input type="hidden" name="m_curr" value="' . $m_curr . '">
		<input type="hidden" name="m_desc" value="' . $m_desc . '">
		<input type="hidden" name="m_sign" value="' . $sign . '">
		<input type="submit" name="m_process" value="Оплатить"><br/><br/>';
		
		return $output;
    break;
}
?>