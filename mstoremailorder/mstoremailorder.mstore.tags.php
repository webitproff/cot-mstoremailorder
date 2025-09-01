<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=mstore.tags
[END_COT_EXT]
==================== */

/**
 * MStore Email Order plugin: mstore tags
 *
 * @package MStoreEmailOrder
 * @author webitproff
 * @copyright Copyright (c) 2025 webitproff | https://github.com/webitproff
 * @license BSD License
 */
defined('COT_CODE') or die('Wrong URL.');

$t->assign('MSTORE_ORDER_LINK', cot_url('plug', "e=mstoremailorder&item_id={$item['msitem_id']}"));
?>