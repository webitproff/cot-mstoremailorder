<?php
/**
 * MStore Email Order plugin: functions
 * Filename: mstoremailorder.functions.php
 * @package MStoreEmailOrder
 * @author webitproff
 * @copyright Copyright (c) 2025 webitproff | https://github.com/webitproff
 * @license BSD License
 */
defined('COT_CODE') or die('Wrong URL.');


/**
 * Отправка email через Cotonti PHPMailer
 *
 * @param string $to Получатель
 * @param string $subject Тема письма
 * @param string $body Тело письма (HTML поддерживается)
 * @return bool true при успешной отправке, false при ошибке
 */
function mstoremailorder_send_email($to, $subject, $body) {
    global $cfg;

    // Защита: проверка настроек плагина
    $fromemail = isset($cfg['plugins']['mstoremailorder']['email_from']) 
        ? $cfg['plugins']['mstoremailorder']['email_from'] 
        : ($cfg['adminemail'] ?? 'noreply@example.com');

    $fromname = isset($cfg['plugins']['mstoremailorder']['email_from_name']) 
        ? $cfg['plugins']['mstoremailorder']['email_from_name'] 
        : ($cfg['maintitle'] ?? 'Cotonti');

    // Используем cot_mail_custom с правильным количеством аргументов для Cotonti 0.9.26
    try {
        return cot_mail_custom(
            $to,       // кому
            $subject,  // тема
            $body,     // тело письма
            $fromemail,// от кого email
            $fromname, // от кого имя
            true,      // html
            'UTF-8'    // кодировка
        );
    } catch (Exception $e) {
        // Логирование ошибки
        cot_log("MStoreEmailOrder PHPMailer error: " . $e->getMessage(), 'err');
        return false;
    }
}

/** старая, простейшая. 
function mstoremailorder_send_email($to, $subject, $body) {
    global $cfg;
    $headers = "From: {$cfg['plugins']['mstoremailorder']['email_from']}\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    return mail($to, $subject, $body, $headers);
}
 */

function mstoremailorder_generate_order_email($order, $item, $is_seller = false) {
    global $L;
    $item_url = cot_url('mstore', "id={$order['order_item_id']}", '', true);
    $body = $is_seller ? $L['mstoremailorder_seller_email_body'] : $L['mstoremailorder_buyer_email_body'];

    $buyer_profile_url = ($order['order_user_id'] > 0) ? cot_url('users', "m=details&id={$order['order_user_id']}", '', true) : '';
    $seller_profile_url = cot_url('users', "m=details&id={$order['order_seller_id']}", '', true);

    $body = str_replace(
        ['{ITEM_TITLE}', '{ITEM_URL}', '{QUANTITY}', '{PHONE}', '{DATE}', '{BUYER_PROFILE_URL}', '{SELLER_PROFILE_URL}', '{COMMENT}'],
        [
            htmlspecialchars($item['msitem_title'] ?: 'No title'),
            $item_url,
            $order['order_quantity'],
            htmlspecialchars($order['order_phone']),
            cot_date('datetime_full', $order['order_date']),
            $buyer_profile_url,
            $seller_profile_url,
            htmlspecialchars($order['order_comment'] ?: '')
        ],
        $body
    );
    return $body;
}

function mstoremailorder_generate_status_email($order, $item, $new_status) {
    global $L;
    $item_url = cot_url('mstore', "id={$order['order_item_id']}", '', true);
    $status_text = $L['mstoremailorder_status_new'];
    if ($new_status == 1) $status_text = $L['mstoremailorder_status_processing'];
    elseif ($new_status == 2) $status_text = $L['mstoremailorder_status_completed'];
    elseif ($new_status == 3) $status_text = $L['mstoremailorder_status_canceled'];
    elseif ($new_status == 4) $status_text = $L['mstoremailorder_status_rejected'];

    $body = str_replace(
        ['{ITEM_TITLE}', '{ITEM_URL}', '{QUANTITY}', '{PHONE}', '{DATE}', '{COMMENT}', '{STATUS}'],
        [
            htmlspecialchars($item['msitem_title'] ?: 'No title'),
            $item_url,
            $order['order_quantity'],
            htmlspecialchars($order['order_phone']),
            cot_date('datetime_full', $order['order_date']),
            htmlspecialchars($order['order_comment'] ?: ''),
            $status_text
        ],
        $L['mstoremailorder_status_email_body']
    );
    return $body;
}

function mstoremailorder_validate_phone($phone) {
    return preg_match('/^[\+]?[0-9\s\-\(\)]{7,20}$/', $phone);

}
