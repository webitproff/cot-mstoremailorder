<!-- BEGIN: MAIN -->
<h2>{PHP.L.mstoremailorder}</h2>
<form action="{PHP|cot_url('admin', 'm=other&p=mstoremailorder')}" method="get">
    <input type="hidden" name="m" value="other" />
    <input type="hidden" name="p" value="mstoremailorder" />
    <p>
        <label>{PHP.L.mstoremailorder_filter_status}</label>
        <select name="filter_status">
            <option value="">{PHP.L.All}</option>
            <option value="0" {FILTER_STATUS_0_SELECTED}>{PHP.L.mstoremailorder_status_new}</option>
            <option value="1" {FILTER_STATUS_1_SELECTED}>{PHP.L.mstoremailorder_status_processing}</option>
            <option value="2" {FILTER_STATUS_2_SELECTED}>{PHP.L.mstoremailorder_status_completed}</option>
            <option value="3" {FILTER_STATUS_3_SELECTED}>{PHP.L.mstoremailorder_status_canceled}</option>
            <option value="4" {FILTER_STATUS_4_SELECTED}>{PHP.L.mstoremailorder_status_rejected}</option>
        </select>
    </p>
    <p>
        <label>{PHP.L.mstoremailorder_filter_search}</label>
        <input type="text" name="search" value="{SEARCH}" placeholder="Email or Item Title" />
    </p>
    <button type="submit">{PHP.L.Filter}</button>
</form>
<table>
    <tr>
        <th>{PHP.L.ID}</th>
        <th>{PHP.L.Item}</th>
        <th>{PHP.L.mstoremailorder_email}</th>
        <th>{PHP.L.Seller}</th>
        <th>{PHP.L.mstoremailorder_quantity}</th>
        <th>{PHP.L.mstoremailorder_phone}</th>
        <th>{PHP.L.mstoremailorder_comment}</th>
        <th>{PHP.L.Date}</th>
        <th>{PHP.L.Status}</th>
        <th>{PHP.L.History}</th>
        <th>{PHP.L.Actions}</th>
    </tr>
    <!-- BEGIN: ORDERS -->
    <tr class="{ORDER_ODDEVEN}">
        <td>{ORDER_I}</td>
        <td>{ORDER_ITEM_TITLE}</td>
        <td>{ORDER_EMAIL}</td>
        <td>{ORDER_SELLER_NAME}</td>
        <td>{ORDER_QUANTITY}</td>
        <td>{ORDER_PHONE}</td>
        <td>{ORDER_COMMENT}</td>
        <td>{ORDER_DATE|cot_date('datetime_full', $this)}</td>
        <td>{ORDER_STATUS_TEXT}</td>
        <td>
            <!-- BEGIN: HISTORY -->
            <p>{HISTORY_STATUS_TEXT} ({HISTORY_DATE|cot_date('datetime_full', $this)})</p>
            <!-- END: HISTORY -->
        </td>
        <td>
            <form action="{ORDER_UPDATE_URL}" method="post">
                <select name="new_status">
                    <option value="0" {ORDER_STATUS_0_SELECTED}>{PHP.L.mstoremailorder_status_new}</option>
                    <option value="1" {ORDER_STATUS_1_SELECTED}>{PHP.L.mstoremailorder_status_processing}</option>
                    <option value="2" {ORDER_STATUS_2_SELECTED}>{PHP.L.mstoremailorder_status_completed}</option>
                    <option value="3" {ORDER_STATUS_3_SELECTED}>{PHP.L.mstoremailorder_status_canceled}</option>
                    <option value="4" {ORDER_STATUS_4_SELECTED}>{PHP.L.mstoremailorder_status_rejected}</option>
                </select>
                <button type="submit">{PHP.L.mstoremailorder_update_status}</button>
            </form>
        </td>
    </tr>
    <!-- END: ORDERS -->
</table>
      <!-- IF {PAGINATION} -->
<div>
    {PAGINATION}
    <p>{PREV} {CURRENTPAGE} / {TOTALPAGES} {NEXT}</p>
</div>
      <!-- ENDIF -->

<!-- END: MAIN -->