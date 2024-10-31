function set_inline_widget_set(priceVal, supplierVal, nonce) {
// revert Quick Edit menu so that it refreshes properly
    inlineEditPost.revert();
    var priceInput = jQuery('#rcpmst_price_meta_field');
    var supplierInput = jQuery('#rcpmst_supplier_meta_field');
    priceInput.val(priceVal.trim());
    supplierInput.val(supplierVal.trim());
    //annoyingly approach creates multiple nonce fields, one per hidden column, so set them all to desired value
    jQuery("input[name=_quickedit_nonce_ingredient]").each(function(){
        this.value =nonce;
    })
}