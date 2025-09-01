<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=standalone
[END_COT_EXT]
==================== */

/**
 * MStore Email Order plugin: standalone
 * Filename: mstoremailorder.php
 * @package MStoreEmailOrder
 * @author webitproff
 * @copyright Copyright (c) 2025 webitproff | https://github.com/webitproff
 * @license BSD License
 */
defined('COT_CODE') or die('Wrong URL.');

require_once cot_incfile('mstoremailorder', 'plug', 'functions');

global $db, $db_x, $L, $sys, $usr, $cfg;
$db_mstore_mailorders = $db_x . 'mstore_mailorders';
$db_mstore_mailorder_status_history = $db_x . 'mstore_mailorder_status_history';
$db_mstore = $db_x . 'mstore';
$db_users = $db_x . 'users';

$pluginDir = isset($cfg['plugins_dir']) ? $cfg['plugins_dir'] . '/mstoremailorder' : __DIR__ . '/mstoremailorder';
$logFile = $pluginDir . '/mstoremailorder.log';

function mstoremailorder_log($message, $pluginDir) {
    global $cfg;
    $logPath = $pluginDir . '/mstoremailorder.log';
    if (
        isset($cfg['plugin']['mstoremailorder']['use_function_log']) &&
        $cfg['plugin']['mstoremailorder']['use_function_log'] === '1' &&
        (is_writable($logPath) || (!file_exists($logPath) && is_writable($pluginDir)))
    ) {
        file_put_contents($logPath, date('Y-m-d H:i:s') . ": $message\n", FILE_APPEND);
    } elseif (
        isset($cfg['plugin']['mstoremailorder']['use_function_log']) &&
        $cfg['plugin']['mstoremailorder']['use_function_log'] === '1'
    ) {
        error_log("mstoremailorder_log: Cannot write to $logPath. Message: $message");
    }
}

$id = cot_import('id', 'G', 'INT');
$item_id = cot_import('item_id', 'G', 'INT') ?: cot_import('item_id', 'P', 'INT');
$submit = cot_import('submit', 'P', 'TXT');
$mode = cot_import('m', 'G', 'ALP');
$order_id = cot_import('order_id', 'G', 'INT');
$new_status = cot_import('new_status', 'P', 'INT');
$filter_status = cot_import('filter_status', 'G', 'TXT');
$search = cot_import('search', 'G', 'TXT');
list($pg, $d, $durl) = cot_import_pagenav('d', Cot::$cfg['maxrowsperpage']);

mstoremailorder_log("User ID: {$usr['id']}, Mode: $mode, Pagination offset: $d", $pluginDir);

if ($new_status !== null && $order_id && $mode == 'incoming') {
    $order = Cot::$db->query("SELECT * FROM $db_mstore_mailorders WHERE order_id = ? AND order_seller_id = ?", [$order_id, $usr['id']])->fetch();
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
        cot_redirect(cot_url('plug', "e=mstoremailorder&m=incoming", '', true));
    } else {
        mstoremailorder_log("Update failed: Order not found or user is not seller for order_id=$order_id", $pluginDir);
        cot_message("Order not found or you are not the seller.", 'warning');
    }
}

if ($mode == 'incoming' || $mode == 'outgoing') {
    $t = new XTemplate(cot_tplfile('mstoremailorder.user', 'plug'));

    $where = [];
    $params = [];
    if ($filter_status !== '' && $filter_status !== null) {
        $where[] = "o.order_status = ?";
        $params[] = $filter_status;
    }

    if ($mode == 'outgoing') {
        if ($usr['id'] == 0) {
            mstoremailorder_log("No user ID available. User not logged in.", $pluginDir);
            cot_message("Please log in to view your orders.", 'error');
        } else {
            $where[] = "o.order_user_id = ?";
            $params[] = $usr['id'];
            if ($search) {
                $where[] = "(o.order_email LIKE ? OR COALESCE(m.msitem_title, '') LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            $where_sql = $where ? "WHERE " . implode(' AND ', $where) : '';

            $totallines = Cot::$db->query("SELECT COUNT(*) FROM $db_mstore_mailorders AS o LEFT JOIN $db_mstore AS m ON m.msitem_id = o.order_item_id $where_sql", $params)->fetchColumn();
            mstoremailorder_log("Outgoing - Total lines: $totallines", $pluginDir);

            $orders = Cot::$db->query("
                SELECT o.*, COALESCE(m.msitem_title, 'Item not found') AS msitem_title
                FROM $db_mstore_mailorders AS o
                LEFT JOIN $db_mstore AS m ON m.msitem_id = o.order_item_id
                $where_sql
                ORDER BY o.order_date DESC
                LIMIT $d, " . Cot::$cfg['maxrowsperpage'], $params)->fetchAll();

            mstoremailorder_log("Outgoing - Orders fetched: " . count($orders) . ", Query: " . json_encode($orders), $pluginDir);

            if (empty($orders)) {
                mstoremailorder_log("No outgoing orders found for user ID {$usr['id']}.", $pluginDir);
                cot_message("No outgoing orders found.", 'warning');
            } else {
                mstoremailorder_log("Rendering orders for user ID {$usr['id']}.", $pluginDir);
            }

            $i = 0;
            foreach ($orders as $order) {
                $i++;
                $status_text = $L['mstoremailorder_status_new'] ?? 'New';
                if ($order['order_status'] == 1) $status_text = $L['mstoremailorder_status_processing'] ?? 'Processing';
                elseif ($order['order_status'] == 2) $status_text = $L['mstoremailorder_status_completed'] ?? 'Completed';
                elseif ($order['order_status'] == 3) $status_text = $L['mstoremailorder_status_canceled'] ?? 'Canceled';
                elseif ($order['order_status'] == 4) $status_text = $L['mstoremailorder_status_rejected'] ?? 'Rejected';

                $history = Cot::$db->query("SELECT * FROM $db_mstore_mailorder_status_history WHERE order_id = ? ORDER BY change_date DESC", [$order['order_id']])->fetchAll();
                foreach ($history as $hist) {
                    $hist_status_text = $L['mstoremailorder_status_new'] ?? 'New';
                    if ($hist['status'] == 1) $hist_status_text = $L['mstoremailorder_status_processing'] ?? 'Processing';
                    elseif ($hist['status'] == 2) $hist_status_text = $L['mstoremailorder_status_completed'] ?? 'Completed';
                    elseif ($hist['status'] == 3) $hist_status_text = $L['mstoremailorder_status_canceled'] ?? 'Canceled';
                    elseif ($hist['status'] == 4) $hist_status_text = $L['mstoremailorder_status_rejected'] ?? 'Rejected';
                    $t->assign([
                        'HISTORY_STATUS_TEXT' => htmlspecialchars($hist_status_text, ENT_QUOTES, 'UTF-8'),
                        'HISTORY_DATE' => cot_date('datetime_full', $hist['change_date'] ?? $sys['now']),
                    ]);
                    $t->parse('MAIN.OUTGOING.HISTORY');
                }

                $t->assign([
                    'ORDER_ID' => $order['order_id'] ?? 0,
                    'ORDER_ITEM_TITLE' => htmlspecialchars($order['msitem_title'] ?? 'No title', ENT_QUOTES, 'UTF-8'),
                    'ORDER_QUANTITY' => $order['order_quantity'] ?? 0,
                    'ORDER_COMMENT' => htmlspecialchars($order['order_comment'] ?? '', ENT_QUOTES, 'UTF-8'),
                    'ORDER_DATE' => cot_date('datetime_full', $order['order_date'] ?? $sys['now']),
                    'ORDER_STATUS' => $order['order_status'] ?? 0,
                    'ORDER_STATUS_TEXT' => htmlspecialchars($status_text, ENT_QUOTES, 'UTF-8'),
                    'ORDER_ODDEVEN' => cot_build_oddeven($i),
                    'ORDER_I' => $i,
                ]);
                $t->parse('MAIN.OUTGOING');
            }

            $t->assign([
                'OUTGOING_COUNT' => count($orders),
                'DEBUG_ORDERS' => json_encode($orders),
            ]);
        }
    } elseif ($mode == 'incoming') {
        $where[] = "o.order_seller_id = ?";
        $params[] = $usr['id'];
        if ($search) {
            $where[] = "(o.order_email LIKE ? OR COALESCE(m.msitem_title, '') LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        $where_sql = $where ? "WHERE " . implode(' AND ', $where) : '';

        $totallines = Cot::$db->query("SELECT COUNT(*) FROM $db_mstore_mailorders AS o LEFT JOIN $db_mstore AS m ON m.msitem_id = o.order_item_id $where_sql", $params)->fetchColumn();
        mstoremailorder_log("Incoming - Total lines: $totallines", $pluginDir);

        $orders = Cot::$db->query("
            SELECT o.*, COALESCE(m.msitem_title, 'Item not found') AS msitem_title
            FROM $db_mstore_mailorders AS o
            LEFT JOIN $db_mstore AS m ON m.msitem_id = o.order_item_id
            $where_sql
            ORDER BY o.order_date DESC
            LIMIT $d, " . Cot::$cfg['maxrowsperpage'], $params)->fetchAll();

        mstoremailorder_log("Incoming - Orders fetched: " . count($orders) . ", Query: " . json_encode($orders), $pluginDir);

        if (empty($orders)) {
            mstoremailorder_log("No incoming orders found for seller ID {$usr['id']}.", $pluginDir);
            cot_message("No incoming orders found.", 'warning');
        } else {
            mstoremailorder_log("Rendering incoming orders for seller ID {$usr['id']}.", $pluginDir);
        }

        $i = 0;
        foreach ($orders as $order) {
            $i++;
            $status_text = $L['mstoremailorder_status_new'] ?? 'New';
            if ($order['order_status'] == 1) $status_text = $L['mstoremailorder_status_processing'] ?? 'Processing';
            elseif ($order['order_status'] == 2) $status_text = $L['mstoremailorder_status_completed'] ?? 'Completed';
            elseif ($order['order_status'] == 3) $status_text = $L['mstoremailorder_status_canceled'] ?? 'Canceled';
            elseif ($order['order_status'] == 4) $status_text = $L['mstoremailorder_status_rejected'] ?? 'Rejected';

            $history = Cot::$db->query("SELECT * FROM $db_mstore_mailorder_status_history WHERE order_id = ? ORDER BY change_date DESC", [$order['order_id']])->fetchAll();
            foreach ($history as $hist) {
                $hist_status_text = $L['mstoremailorder_status_new'] ?? 'New';
                if ($hist['status'] == 1) $hist_status_text = $L['mstoremailorder_status_processing'] ?? 'Processing';
                elseif ($hist['status'] == 2) $hist_status_text = $L['mstoremailorder_status_completed'] ?? 'Completed';
                elseif ($hist['status'] == 3) $hist_status_text = $L['mstoremailorder_status_canceled'] ?? 'Canceled';
                elseif ($hist['status'] == 4) $hist_status_text = $L['mstoremailorder_status_rejected'] ?? 'Rejected';
                $t->assign([
                    'HISTORY_STATUS_TEXT' => htmlspecialchars($hist_status_text, ENT_QUOTES, 'UTF-8'),
                    'HISTORY_DATE' => cot_date('datetime_full', $hist['change_date'] ?? $sys['now']),
                ]);
                $t->parse('MAIN.INCOMING.HISTORY');
            }

            $t->assign([
                'ORDER_ID' => $order['order_id'] ?? 0,
                'ORDER_ITEM_TITLE' => htmlspecialchars($order['msitem_title'] ?? 'No title', ENT_QUOTES, 'UTF-8'),
                'ORDER_QUANTITY' => $order['order_quantity'] ?? 0,
                'ORDER_EMAIL' => htmlspecialchars($order['order_email'] ?? '', ENT_QUOTES, 'UTF-8'),
                'ORDER_PHONE' => htmlspecialchars($order['order_phone'] ?? '', ENT_QUOTES, 'UTF-8'),
                'ORDER_COMMENT' => htmlspecialchars($order['order_comment'] ?? '', ENT_QUOTES, 'UTF-8'),
                'ORDER_DATE' => cot_date('datetime_full', $order['order_date'] ?? $sys['now']),
                'ORDER_STATUS' => $order['order_status'] ?? 0,
                'ORDER_STATUS_TEXT' => htmlspecialchars($status_text, ENT_QUOTES, 'UTF-8'),
                'ORDER_UPDATE_URL' => cot_url('plug', "e=mstoremailorder&m=incoming&order_id={$order['order_id']}"),
                'ORDER_STATUS_0_SELECTED' => ($order['order_status'] == 0) ? 'selected' : '',
                'ORDER_STATUS_1_SELECTED' => ($order['order_status'] == 1) ? 'selected' : '',
                'ORDER_STATUS_2_SELECTED' => ($order['order_status'] == 2) ? 'selected' : '',
                'ORDER_STATUS_3_SELECTED' => ($order['order_status'] == 3) ? 'selected' : '',
                'ORDER_STATUS_4_SELECTED' => ($order['order_status'] == 4) ? 'selected' : '',
                'ORDER_ODDEVEN' => cot_build_oddeven($i),
                'ORDER_I' => $i,
            ]);
            $t->parse('MAIN.INCOMING');
        }

        $t->assign([
            'INCOMING_COUNT' => count($orders),
        ]);
    }

    $pagenav = cot_pagenav(
        'plug',
        "e=mstoremailorder&m=$mode" . ($filter_status ? "&filter_status=$filter_status" : '') . ($search ? "&search=$search" : ''),
        $d,
        $totallines,
        Cot::$cfg['maxrowsperpage'],
        'd',
        '',
        Cot::$cfg['jquery'] && Cot::$cfg['turnajax']
    );

    $t->assign([
        'FORM_URL' => cot_url('plug', "e=mstoremailorder&m=$mode" . ($filter_status ? "&filter_status=$filter_status" : '') . ($search ? "&search=$search" : '')),
        'FILTER_STATUS_0_SELECTED' => ($filter_status === '0') ? 'selected' : '',
        'FILTER_STATUS_1_SELECTED' => ($filter_status === '1') ? 'selected' : '',
        'FILTER_STATUS_2_SELECTED' => ($filter_status === '2') ? 'selected' : '',
        'FILTER_STATUS_3_SELECTED' => ($filter_status === '3') ? 'selected' : '',
        'FILTER_STATUS_4_SELECTED' => ($filter_status === '4') ? 'selected' : '',
        'SEARCH' => htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8'),
        'MODE' => $mode,
        'PAGINATION' => $pagenav['main'],
        'PREV' => $pagenav['prev'],
        'NEXT' => $pagenav['next'],
        'CURRENTPAGE' => $pagenav['current'],
        'TOTALPAGES' => $pagenav['total'],
        'DEBUG_ORDERS' => json_encode($orders),
    ]);

    cot_display_messages($t);
} else {
    $t = new XTemplate(cot_tplfile('mstoremailorder', 'plug'));

    if ($submit && $item_id) {
        mstoremailorder_log("Processing form: item_id=$item_id, submit=$submit", $pluginDir);
        $email = cot_import('email', 'P', 'TXT');
        $phone = cot_import('phone', 'P', 'TXT');
        $quantity = cot_import('quantity', 'P', 'INT');
        $comment = cot_import('comment', 'P', 'HTM');

        if (empty($email) || !cot_check_email($email)) {
            cot_error($L['mstoremailorder_error_email']);
            mstoremailorder_log("Error: Invalid or empty email: $email", $pluginDir);
        }
        if (empty($phone)) {
            cot_error($L['mstoremailorder_error_phone']);
            mstoremailorder_log("Error: Empty phone", $pluginDir);
        }
        if (!mstoremailorder_validate_phone($phone)) {
            cot_error($L['mstoremailorder_error_phone_invalid']);
            mstoremailorder_log("Error: Invalid phone format: $phone", $pluginDir);
        }
        if ($quantity <= 0) {
            cot_error($L['mstoremailorder_error_quantity']);
            mstoremailorder_log("Error: Invalid quantity: $quantity", $pluginDir);
        }

        $item = Cot::$db->query("SELECT * FROM $db_mstore WHERE msitem_id = ?", [$item_id])->fetch();
        if (!$item) {
            cot_error('Item not found');
            mstoremailorder_log("Error: Item not found for item_id=$item_id", $pluginDir);
        }

        if (!cot_error_found()) {
            $order = [
                'order_item_id' => $item_id,
                'order_user_id' => $usr['id'] ?: 0,
                'order_seller_id' => $item['msitem_ownerid'],
                'order_quantity' => $quantity,
                'order_phone' => $phone,
                'order_email' => $email,
                'order_comment' => $comment,
                'order_date' => $sys['now'],
                'order_ip' => $usr['ip'],
                'order_status' => 0,
            ];

            try {
                $inserted = Cot::$db->insert($db_mstore_mailorders, $order);
                if ($inserted) {
                    $order_id = Cot::$db->lastInsertId();
                    Cot::$db->insert($db_mstore_mailorder_status_history, [
                        'order_id' => $order_id,
                        'status' => 0,
                        'change_date' => $sys['now']
                    ]);
                    mstoremailorder_log("Order inserted: order_id=$order_id", $pluginDir);

                    $buyer_email_body = mstoremailorder_generate_order_email($order, $item);
                    $seller_email_body = mstoremailorder_generate_order_email($order, $item, true);
                    $seller = Cot::$db->query("SELECT user_email FROM $db_users WHERE user_id = ?", [$item['msitem_ownerid']])->fetch();

                    if (!mstoremailorder_send_email($email, $cfg['plugins']['mstoremailorder']['email_subject'], $buyer_email_body)) {
                        cot_error('Failed to send email to buyer');
                        mstoremailorder_log("Error: Failed to send email to buyer: $email", $pluginDir);
                    }
                    if (!mstoremailorder_send_email($seller['user_email'], $cfg['plugins']['mstoremailorder']['email_subject'], $seller_email_body)) {
                        cot_error('Failed to send email to seller');
                        mstoremailorder_log("Error: Failed to send email to seller: {$seller['user_email']}", $pluginDir);
                    }

                    cot_message($L['mstoremailorder_success']);
                    cot_redirect(cot_url('plug', "e=mstoremailorder&m=outgoing", '', true));
                } else {
                    cot_error('Failed to insert order into database');
                    mstoremailorder_log("Error: Failed to insert order into database", $pluginDir);
                }
            } catch (Exception $e) {
                cot_error('Database error: ' . $e->getMessage());
                mstoremailorder_log("Database error: " . $e->getMessage(), $pluginDir);
            }
        }
    }

    if ($item_id) {
        $item = Cot::$db->query("SELECT * FROM $db_mstore WHERE msitem_id = ?", [$item_id])->fetch();
        if ($item) {
            $t->assign([
                'ITEM_ID' => $item_id,
                'EMAIL' => $usr['id'] ? ($usr['profile']['user_email'] ?: '') : '',
                'PHONE' => '',
                'ITEM_TITLE' => htmlspecialchars($item['msitem_title'] ?: 'No title', ENT_QUOTES, 'UTF-8'),
                'ITEM_URL' => cot_url('mstore', "id={$item_id}", '', true),
            ]);
        } else {
            cot_error('Item not found');
            mstoremailorder_log("Error: Item not found for item_id=$item_id", $pluginDir);
            $t->assign([
                'ITEM_ID' => $item_id,
                'EMAIL' => $usr['id'] ? ($usr['profile']['user_email'] ?: '') : '',
                'PHONE' => '',
                'ITEM_TITLE' => 'Item not found',
                'ITEM_URL' => '#',
            ]);
        }
        cot_display_messages($t);
    }
}

?>