<!-- BEGIN: MAIN -->
<div class="border-bottom border-secondary py-3 px-3">
    <nav aria-label="breadcrumb">
        <div class="ps-container-breadcrumb">
            <ol class="breadcrumb d-flex mb-0">{PHP.L.mstoremailorder_form_title}</ol>
        </div>
    </nav>
</div>

<div class="min-vh-50 px-2 px-md-3 py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 mx-auto">
            <div class="card mt-4 mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">{PHP.L.mstoremailorder_form_title}</h2>
                </div>
                <div class="card-body">
                    {FILE "{PHP.cfg.themes_dir}/{PHP.cfg.defaulttheme}/warnings.tpl"} 
                    <p>
                        <strong>{PHP.L.Item}:</strong> <a href="{ITEM_URL}">{ITEM_TITLE}</a>
                    </p>
                    <form action="{PHP|cot_url('plug', 'e=mstoremailorder')}" method="post" accept-charset="UTF-8" class="needs-validation" novalidate>
                        <input type="hidden" name="item_id" value="{ITEM_ID}" />
                        <input type="hidden" name="submit" value="1" />

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">{PHP.L.mstoremailorder_email}</label>
                            <input type="email" class="form-control" id="email" name="email" value="{EMAIL}" required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label fw-semibold">{PHP.L.mstoremailorder_phone}</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="{PHONE}" required placeholder="+1234567890">
                        </div>

                        <div class="mb-3">
                            <label for="quantity" class="form-label fw-semibold">{PHP.L.mstoremailorder_quantity}</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1" required>
                        </div>

                        <div class="mb-3">
                            <label for="comment" class="form-label fw-semibold">{PHP.L.mstoremailorder_comment}</label>
                            <textarea class="form-control" id="comment" name="comment" rows="5"></textarea>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">{PHP.L.mstoremailorder_submit}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- END: MAIN -->
