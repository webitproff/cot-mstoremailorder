<!-- BEGIN: MAIN -->
<div class="border-bottom border-secondary py-3 px-3">
  <nav aria-label="breadcrumb">
    <div class="ps-container-breadcrumb">
      <ol class="breadcrumb d-flex mb-0">{PHP.L.mstoremailorder}</ol>
    </div>
  </nav>
</div>
<div class="min-vh-50 px-2 py-4">
  <div class="px-0 m-0 row justify-content-center">
    <div class="col-12 container-3xl">
      <h2>{PHP.L.mstoremailorder}</h2>
      <form action="{FORM_URL}" method="get">
        <input type="hidden" name="e" value="mstoremailorder" />
        <input type="hidden" name="m" value="{MODE}" />
        <p>
          <label>{PHP.L.mstoremailorder_filter_status}</label>
          <select name="filter_status">
            <option value="" {FILTER_STATUS_0_SELECTED}>{PHP.L.All}</option>
            <option value="0" {FILTER_STATUS_0_SELECTED}>{PHP.L.mstoremailorder_status_new}</option>
            <option value="1" {FILTER_STATUS_1_SELECTED}>{PHP.L.mstoremailorder_status_processing}</option>
            <option value="2" {FILTER_STATUS_2_SELECTED}>{PHP.L.mstoremailorder_status_completed}</option>
            <option value="3" {FILTER_STATUS_3_SELECTED}>{PHP.L.mstoremailorder_status_canceled}</option>
            <option value="4" {FILTER_STATUS_4_SELECTED}>{PHP.L.mstoremailorder_status_rejected}</option>
          </select>
        </p>
        <p>
          <label>{PHP.L.mstoremailorder_filter_search}</label>
          <input type="text" name="search" value="{SEARCH}" placeholder="Email или название товара" />
        </p>
        <button type="submit">{PHP.L.mstoremailorder_submit}</button>
      </form>
      <!-- IF {PHP.usr.id} == 0 -->
      <p style="color: red;">{PHP.L.mstoremailorder_login_required}</p>
      <!-- ENDIF -->
      <!-- IF {MODE} == "outgoing" -->
      <h3>{PHP.L.mstoremailorder_outgoing_orders}</h3>
      <!-- IF {OUTGOING_COUNT} > 0 -->
      <table>
        <tr>
          <th>{PHP.L.mstoremailorder_id}</th>
          <th>{PHP.L.mstoremailorder_item}</th>
          <th>{PHP.L.mstoremailorder_quantity}</th>
          <th>{PHP.L.mstoremailorder_comment}</th>
          <th>{PHP.L.mstoremailorder_date}</th>
          <th>{PHP.L.mstoremailorder_status}</th>
          <th>{PHP.L.mstoremailorder_history}</th>
        </tr>
        <!-- BEGIN: OUTGOING -->
        <tr class="{ORDER_ODDEVEN}">
          <td>{ORDER_ID}</td>
          <td>{ORDER_ITEM_TITLE}</td>
          <td>{ORDER_QUANTITY}</td>
          <td>{ORDER_COMMENT}</td>
          <td>{ORDER_DATE}</td>
          <td>{ORDER_STATUS_TEXT}</td>
          <td>
            <!-- BEGIN: HISTORY -->
            <p>{HISTORY_STATUS_TEXT} ({HISTORY_DATE})</p>
            <!-- END: HISTORY -->
          </td>
        </tr>
        <!-- END: OUTGOING -->
      </table>
      <!-- ELSE -->
      <p>{PHP.L.mstoremailorder_no_outgoing_orders}</p>
      <!-- ENDIF -->
      <!-- ENDIF -->
      <!-- IF {MODE} == "incoming" -->
      <h3>{PHP.L.mstoremailorder_incoming_orders}</h3>
      <!-- IF {INCOMING_COUNT} > 0 -->
      <table>
        <tr>
          <th>{PHP.L.mstoremailorder_id}</th>
          <th>{PHP.L.mstoremailorder_item}</th>
          <th>{PHP.L.mstoremailorder_buyer}</th>
          <th>{PHP.L.mstoremailorder_phone}</th>
          <th>{PHP.L.mstoremailorder_quantity}</th>
          <th>{PHP.L.mstoremailorder_comment}</th>
          <th>{PHP.L.mstoremailorder_date}</th>
          <th>{PHP.L.mstoremailorder_status}</th>
          <th>{PHP.L.mstoremailorder_history}</th>
          <th>{PHP.L.mstoremailorder_actions}</th>
        </tr>
        <!-- BEGIN: INCOMING -->
        <tr class="{ORDER_ODDEVEN}">
          <td>{ORDER_ID}</td>
          <td>{ORDER_ITEM_TITLE}</td>
          <td>{ORDER_EMAIL}</td>
          <td>{ORDER_PHONE}</td>
          <td>{ORDER_QUANTITY}</td>
          <td>{ORDER_COMMENT}</td>
          <td>{ORDER_DATE}</td>
          <td>{ORDER_STATUS_TEXT}</td>
          <td>
            <!-- BEGIN: HISTORY -->
            <p>{HISTORY_STATUS_TEXT} ({HISTORY_DATE})</p>
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
        <!-- END: INCOMING -->
      </table>
      <!-- ELSE -->
      <p>{PHP.L.mstoremailorder_no_incoming_orders}</p>
      <!-- ENDIF -->
      <!-- ENDIF -->
	  <!-- IF {PAGINATION} -->
      <div> {PAGINATION} <p>{PREV} {CURRENTPAGE} / {TOTALPAGES} {NEXT}</p>
      </div>
	  <!-- ENDIF -->
    </div>
  </div>
</div>
<!-- END: MAIN -->