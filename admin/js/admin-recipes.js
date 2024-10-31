function set_inline_widget_set(priceVal, supplierVal, nonce) {
// revert Quick Edit menu so that it refreshes properly
    inlineEditPost.revert();
    //annoyingly approach creates multiple nonce fields, one per hidden column, so set them all to desired value
    jQuery("input[name=_quickedit_nonce_recipe]").each(function(){
        this.value =nonce;
    })					
}