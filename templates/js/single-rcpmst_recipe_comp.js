function setOldValue(target){
    target.setAttribute('oldvalue', target.value);
}
function scale(factor){
    document.getElementById("recipeTotal").value = document.getElementById("recipeTotal").value * factor;	
    recalculateQuantities();
}
function recalculateQuantities(){
    var field = document.getElementById("recipeTotal");
    
    var sign = field.value.substring(0,1);
    if (!(sign == "-" || sign == "+")){
        sign = "";
    }
    var percent = field.value.substring(field.value.length - 1,field.value.length);
    if (percent != "%"){
        percent = "";
    }
    var amount;
    amount = field.value.substring(sign.length, field.value.length - percent.length);
    if (isNaN(amount)){
        alert("The value entered must be numeric, with only + or - before it and % after it");
        field.value = field.getAttribute('oldvalue');
        return;
    }
    if (percent == "%"){
        if(sign == "+"){
            field.value = Number(field.getAttribute("oldvalue")) + (Number(field.getAttribute("oldvalue")) * Number(amount) / 100);
        }else if(sign == "-"){
            field.value = Number(field.getAttribute("oldvalue")) - (Number(field.getAttribute("oldvalue")) * Number(amount) / 100);
        }else{
            field.value = Number(field.getAttribute("oldvalue")) * Number(amount) / 100;
        }
    }else{
        if(sign == "+"){
            field.value = Number(field.getAttribute("oldvalue")) + Number(amount);
        }else if(sign == "-"){
            field.value = Number(field.getAttribute("oldvalue")) - Number(amount);
        }
    }
    collection = document.getElementsByClassName("recipeQuantity");
    var ratio = field.value / document.getElementById("originalRecipeTotal").getAttribute("data-quantity");
    for (let i = 0; i < collection.length; i++) {
        var leftovers = document.getElementById("leftovers-" + collection[i].getAttribute("data-parent-id"));
        var leftoverAdjustment = 0;
        if (!(leftovers===null) && Number(leftovers.value) > 0){
            leftoverAdjustment = leftovers.value * collection[i].getAttribute("data-original-percent");
            //if parent recipe has subrecipes add a warning about leftovers not cascading.
        }
        collection[i].innerText = parseFloat(((collection[i].getAttribute("data-quantity") * ratio)-leftoverAdjustment).toPrecision(3));
    }
}
function reset(){
    document.getElementById("recipeTotal").value = document.getElementById("originalRecipeTotal").getAttribute("data-quantity");
    recalculateQuantities();
}
function showHideOriginalCols(){
    collection = document.getElementsByClassName("colOriginalQuantity");
    for (let i = 0; i < collection.length; i++) {
        if(collection[i].style.visibility == 'visible'){
            collection[i].style.visibility = 'collapse';
        }else{
            collection[i].style.visibility = 'visible';
        }
    }
}

function changeMode(evt,mode){
    //reset all amounts
    collection = document.getElementsByClassName("recipeQuantity");
    for (let i = 0; i < collection.length; i++) {
        var leftovers = document.getElementById("leftovers-" + collection[i].getAttribute("data-parent-id"));
        if (!(leftovers===null)){
    leftovers.value = 0;
        }
    }
    reset();

    //hide all
    collection = document.getElementsByClassName("colPriceMode");
    for (let i = 0; i < collection.length; i++) {
        collection[i].style.visibility = 'collapse';
    }
    collection = document.getElementsByClassName("colCompositionMode");
    for (let i = 0; i < collection.length; i++) {
        collection[i].style.visibility = 'collapse';
    }		
    collection = document.getElementsByClassName("priceMode");
    for (let i = 0; i < collection.length; i++) {
        collection[i].style.display = 'none';
    }
    collection = document.getElementsByClassName("recipeMode");
    for (let i = 0; i < collection.length; i++) {
        collection[i].style.display = 'none';
    }	
    collection = document.getElementsByClassName("compositionMode");
    for (let i = 0; i < collection.length; i++) {
        collection[i].style.display = 'none';
    }	
    collection = document.getElementsByClassName("testMode");
    for (let i = 0; i < collection.length; i++) {
        collection[i].style.display = 'none';
    }				
    //show necessary
    if(mode==0){ // recipe mode
        collection = document.getElementsByClassName("recipeMode");
        for (let i = 0; i < collection.length; i++) {
            collection[i].style.display = 'block';
        }
    }	
    if(mode==1){ //price mode
        collection = document.getElementsByClassName("colPriceMode");
        for (let i = 0; i < collection.length; i++) {
            collection[i].style.visibility = 'visible';
        }
        collection = document.getElementsByClassName("priceMode");
        for (let i = 0; i < collection.length; i++) {
            collection[i].style.display = 'block';
        }
    }
    if(mode==2){ //composition mode
        collection = document.getElementsByClassName("colCompositionMode");
        for (let i = 0; i < collection.length; i++) {
            collection[i].style.visibility = 'visible';
        }
        collection = document.getElementsByClassName("compositionMode");
        for (let i = 0; i < collection.length; i++) {
            collection[i].style.display = 'block';
        }	
    }		
    if(mode==3){
        collection = document.getElementsByClassName("testMode");
        for (let i = 0; i < collection.length; i++) {
            collection[i].style.display = 'block';
        }
    }				

    // Get all elements with class="tablinks" and remove the class "active"
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }

    // Show the current tab, and add an "active" class to the button that opened the tab
    evt.currentTarget.className += " active";
}
;(function($){
	'use strict';
	$(document).ready(function(){
        document.getElementById("defaultTab").click();
	})    

})( jQuery )        