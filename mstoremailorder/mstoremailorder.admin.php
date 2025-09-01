<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=tools
[END_COT_EXT]
==================== */

/**
 * MStore Email Order plugin: admin tools
 * mstoremailorder.admin.php
 * @package MStoreEmailOrder
 * @author webitproff
 * @copyright Copyright (c) 2025 webitproff | https://github.com/webitproff
 * @license BSD License
 */
defined('COT_CODE') or die('Wrong URL.');

require_once cot_incfile('mstoremailorder', 'plug', 'functions');

global $db, $db_x, $L, $sys;
cot_block(Cot::$usr['isadmin']);

$db_mstore_mailorders = $db_x . 'mstore_mailorders';
$db_mstore_mailorder_status_history = $db_x . 'mstore_mailorder_status_history';

// Обработка действия обновления статуса
$a = cot_import('a', 'G', 'ALP');
$order_id = cot_import('order_id', 'G', 'INT');
$new_status = cot_import('new_status', 'P', 'INT');
$filter_status = cot_import('filter_status', 'G', 'TXT');
$search = cot_import('search', 'G', 'TXT');

if ($a == 'update' && $order_id && isset($new_status)) {
    $order = Cot::$db->query("SELECT * FROM $db_mstore_mailorders WHERE order_id = ?", [$order_id])->fetch();
    if ($order) {
        Cot::$db->update($db_mstore_mailorders, ['order_status' => $new_status], "order_id = ?", [$order_id]);
        Cot::$db->insert($db_mstore_mailorder_status_history, [
            'order_id' => $order_id,
            'status' => $new_status,
            'change_date' => $sys['now']
        ]);
        $item = Cot::$db->query("SELECT * FROM $db_mstore WHERE msitem_id = ?", [$order['order_item_id']])->fetch();
        $status_email_body = mstoremailorder_generate_status_email($order, $item, $new_status);
        mstoremailorder_send_email($order['order_email'], $cfg['plugins']['mstoremailorder']['status_email_subject'], $status_email_body);
        cot_message($L['mstoremailorder_status_updated']);
        cot_redirect(cot_url('admin', 'm=other&p=mstoremailorder', '', true));
    }
}

// Инициализация шаблона
$t = new XTemplate(cot_tplfile('mstoremailorder.admin', 'plug'));
list($pg, $d, $durl) = cot_import_pagenav('d', Cot::$cfg['maxrowsperpage']);

// Формирование условий для SQL-запроса
$where = [];
$params = [];
if ($filter_status !== '' && $filter_status !== null) {
    $where[] = "o.order_status = ?";
    $params[] = $filter_status;
}
if ($search) {
    $where[] = "(o.order_email LIKE ? OR m.msitem_title LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$where_sql = $where ? "WHERE " . implode(' AND ', $where) : '';

// Подсчет общего количества записей
$totallines = Cot::$db->query("SELECT COUNT(*) FROM $db_mstore_mailorders AS o LEFT JOIN $db_mstore AS m ON m.msitem_id = o.order_item_id $where_sql", $params)->fetchColumn();

// Логирование для отладки
cot_message("Total lines: $totallines", 'debug');
cot_message("SQL Query: SELECT o.*, m.msitem_title, u.user_name AS seller_name FROM $db_mstore_mailorders AS o LEFT JOIN $db_mstore AS m ON m.msitem_id = o.order_item_id LEFT JOIN $db_users AS u ON u.user_id = o.order_seller_id $where_sql ORDER BY o.order_date DESC LIMIT $d, " . Cot::$cfg['maxrowsperpage'], 'debug');
cot_message("Params: " . print_r($params, true), 'debug');
cot_message("Offset (d): $d, Max rows: " . Cot::$cfg['maxrowsperpage'], 'debug');

// Выборка заказов
$orders = Cot::$db->query("
    SELECT o.*, m.msitem_title, u.user_name AS seller_name
    FROM $db_mstore_mailorders AS o
    LEFT JOIN $db_mstore AS m ON m.msitem_id = o.order_item_id
    LEFT JOIN $db_users AS u ON u.user_id = o.order_seller_id
    $where_sql
    ORDER BY o.order_date DESC
    LIMIT $d, " . Cot::$cfg['maxrowsperpage'], $params)->fetchAll();

// Логирование результатов
cot_message("Orders fetched: " . count($orders), 'debug');
if (empty($orders)) {
    cot_message("No orders found. Check if cot_mstore or cot_users have required data.", 'warning');
}

// Обработка заказов для шаблона
$i = 0;
foreach ($orders as $order) {
    $i++;
    $status_text = $L['mstoremailorder_status_new'];
    if ($order['order_status'] == 1) $status_text = $L['mstoremailorder_status_processing'];
    elseif ($order['order_status'] == 2) $status_text = $L['mstoremailorder_status_completed'];
    elseif ($order['order_status'] == 3) $status_text = $L['mstoremailorder_status_canceled'];
    elseif ($order['order_status'] == 4) $status_text = $L['mstoremailorder_status_rejected'];

    $history = Cot::$db->query("SELECT * FROM $db_mstore_mailorder_status_history WHERE order_id = ? ORDER BY change_date DESC", [$order['order_id']])->fetchAll();
    foreach ($history as $hist) {
        $hist_status_text = $L['mstoremailorder_status_new'];
        if ($hist['status'] == 1) $hist_status_text = $L['mstoremailorder_status_processing'];
        elseif ($hist['status'] == 2) $hist_status_text = $L['mstoremailorder_status_completed'];
        elseif ($hist['status'] == 3) $hist_status_text = $L['mstoremailorder_status_canceled'];
        elseif ($hist['status'] == 4) $hist_status_text = $L['mstoremailorder_status_rejected'];
        $t->assign([
            'HISTORY_STATUS_TEXT' => $hist_status_text,
            'HISTORY_DATE' => $hist['change_date'],
        ]);
        $t->parse('MAIN.ORDERS.HISTORY');
    }

    $t->assign([
        'ORDER_ID' => $order['order_id'],
        'ORDER_ITEM_TITLE' => $order['msitem_title'] ?: 'Item not found',
        'ORDER_EMAIL' => $order['order_email'],
        'ORDER_SELLER_NAME' => $order['seller_name'] ?: 'Seller not found',
        'ORDER_QUANTITY' => $order['order_quantity'],
        'ORDER_PHONE' => $order['order_phone'],
        'ORDER_COMMENT' => $order['order_comment'],
        'ORDER_DATE' => $order['order_date'],
        'ORDER_STATUS' => $order['order_status'],
        'ORDER_STATUS_TEXT' => $status_text,
        'ORDER_UPDATE_URL' => cot_url('admin', 'm=other&p=mstoremailorder&a=update&order_id=' . $order['order_id']),
        'ORDER_STATUS_0_SELECTED' => ($order['order_status'] == 0) ? 'selected' : '',
        'ORDER_STATUS_1_SELECTED' => ($order['order_status'] == 1) ? 'selected' : '',
        'ORDER_STATUS_2_SELECTED' => ($order['order_status'] == 2) ? 'selected' : '',
        'ORDER_STATUS_3_SELECTED' => ($order['order_status'] == 3) ? 'selected' : '',
        'ORDER_STATUS_4_SELECTED' => ($order['order_status'] == 4) ? 'selected' : '',
        'ORDER_ODDEVEN' => cot_build_oddeven($i),
        'ORDER_I' => $i,
    ]);
    $t->parse('MAIN.ORDERS');
}

// Передача данных фильтра в шаблон
$t->assign([
    'FILTER_STATUS_0_SELECTED' => ($filter_status === '0') ? 'selected' : '',
    'FILTER_STATUS_1_SELECTED' => ($filter_status === '1') ? 'selected' : '',
    'FILTER_STATUS_2_SELECTED' => ($filter_status === '2') ? 'selected' : '',
    'FILTER_STATUS_3_SELECTED' => ($filter_status === '3') ? 'selected' : '',
    'FILTER_STATUS_4_SELECTED' => ($filter_status === '4') ? 'selected' : '',
    'SEARCH' => $search,
]);

// Пагинация
$pagenav = cot_pagenav(
    'admin',
    'm=other&p=mstoremailorder' . ($filter_status ? "&filter_status=$filter_status" : '') . ($search ? "&search=$search" : ''),
    $d,
    $totallines,
    Cot::$cfg['maxrowsperpage'],
    'd',
    '',
    Cot::$cfg['jquery'] && Cot::$cfg['turnajax']
);

$t->assign(cot_generatePaginationTags($pagenav));
cot_display_messages($t);
$t->parse();
$pluginBody = $t->text('MAIN');
?>