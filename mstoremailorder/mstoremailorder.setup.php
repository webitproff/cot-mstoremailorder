<?php
/* ====================
[BEGIN_COT_EXT]
Code=mstoremailorder
Name=MStore Email Order
Category=ecommerce
Description=Plugin for handling email-based orders in MStore module
Version=1.1.0
Date=2025-08-31
Author=webitproff
Copyright=Copyright (c) 2025 webitproff | https://github.com/webitproff
Notes=Requires MStore module
Auth_guests=R
Lock_guests=12345A
Auth_members=RW
Lock_members=345
[END_COT_EXT]

[BEGIN_COT_EXT_CONFIG]
email_from=01:string::no-reply@site.com:Email address for sending order notifications
email_subject=02:string::New Order Notification:Subject for order notification emails
status_email_subject=03:string::Order Status Update:Subject for order status update emails
use_function_log=05:radio::0:Использовать функцию логирования в standalone
[END_COT_EXT_CONFIG]
==================== */

/**
 * MStore Email Order plugin: setup
 *
 * @package MStoreEmailOrder
 * @author webitproff
 * @copyright Copyright (c) 2025 webitproff | https://github.com/webitproff
 * @license BSD License
 */
defined('COT_CODE') or die('Wrong URL.');