//show or hide content based on recipe vs ingredient
function hideElements(elements){
	jQuery(elements).each(function() {
		jQuery('#' + this).css("display","none");
	});
}
function showElements(elements){
	jQuery(elements).each(function() {
		jQuery('#' + this).css("display","block");
	});
}
function isRecipe(){
	hideElements(['rcpmst_supplier_meta',
		'rcpmst_price_meta',
		'rcpmst_abv_meta']);
	showElements(['rcpmst_recipe_meta',
		'rcpmst_source_meta',
		'rcpmst_storage_meta',
		'rcpmst_retail_meta',
		'rcpmst_dietary_notes_meta',
		'rcpmst_ingredients_meta',
		'rcpmst_ingredient_listing_meta',
		'rcpmst_allergens_meta',
		'rcpmst_activities_meta',
		'ing_listing_compound']) 
}
function isNonEdible(){
	hideElements(['rcpmst_recipe_meta',
		'rcpmst_source_meta',
		'rcpmst_storage_meta',
		'rcpmst_retail_meta',		
		'rcpmst_dietary_notes_meta',
		'rcpmst_ingredients_meta',
		'rcpmst_ingredient_listing_meta',
		'rcpmst_allergens_meta',
		'rcpmst_activities_meta',
		'rcpmst_abv_meta',
		'ing_listing_compound']);
	showElements(['rcpmst_supplier_meta',
		'rcpmst_price_meta']);
}
function isIngredient(){
	hideElements(['rcpmst_recipe_meta',
		'rcpmst_source_meta',
		'rcpmst_storage_meta',
		'rcpmst_retail_meta',		
		'rcpmst_ingredients_meta',
		'rcpmst_dietary_notes_meta',
		'rcpmst_activities_meta',
		'ing_listing_compound']);
	showElements(['rcpmst_supplier_meta',
		'rcpmst_allergens_meta',
		'rcpmst_ingredient_listing_meta',
		'rcpmst_price_meta',
		'rcpmst_abv_meta']);
}
function showHideAltName(){
	if (document.getElementById('rcpmst_ingredient_or_recipe_meta_field').value == "Recipe" &&
		jQuery('#rcpmst_ingredient_listing_compound_meta_field').is(":checked")){
		jQuery('#ing_listing_alt').css("display","block");
	}else{
		jQuery('#ing_listing_alt').css("display","none");
	}
}
function manualVsGenerated(isGenerated, manualField, generatedField){
	jQuery('#' + manualField).css("display",isGenerated ? 'none' : 'block');
	jQuery('#' + generatedField).css("display",isGenerated ? 'block' : 'none');
}
function isIngredientListingGenerated(generated){
	manualVsGenerated(generated,'ingredient_listing_manual_div','ingredient_listing_generated_div');
	jQuery('#ing_listing_alt').css("display",generated ? "block":"none");
	jQuery('#ing_listing_compound').css("display",generated ? "block":"none");
}
function isAllergensGenerated(generated){
	manualVsGenerated(generated,'allergens_manual_div','allergens_generated_div');
}
function isDietaryNotesGenerated(generated){
	manualVsGenerated(generated,'dietary_notes_manual_div','dietary_notes_generated_div');
	if(document.getElementById('rcpmst_ingredient_or_recipe_meta_field').value == "Recipe"){
		jQuery('#tagsdiv-rcpmst_dietary_notes').css("display",generated ? 'none' : 'block');
	}else if(document.getElementById('rcpmst_ingredient_or_recipe_meta_field').value == "Ingredient"){
		jQuery('#tagsdiv-rcpmst_dietary_notes').css("display",'block');
	}else{ // non edible
		jQuery('#tagsdiv-rcpmst_dietary_notes').css("display",'none');
	}
}

function ingredientAdd(){
	var maxId = maxFieldNo('ingredients_slug_');
	var newSelectId = maxId + 1;
	var clonedId = maxFieldNo('ingredients_slug_', maxId);
	// the maxId is the drop down list - this is because we want to be able to add server side even if the add button was not pressed
	// first reset ids on drop down to avoid id clashes
	// so first we will clone maxId - 1 to get a new row without a drop down
	// we can then copy required values into that
	// finally resetting values on the drop down

	if(jQuery('#ingredients_quantity_' + maxId).val()==""){
		alert('Please set a quantity');
		return;
	}

	jQuery('#ingredients_slug_' + maxId).attr({"id": "ingredients_slug_" + newSelectId, "name": "ingredients_slug_" + newSelectId});
	jQuery('#ingredients_item_' + maxId).attr({"id": "ingredients_item_" + newSelectId, "name": "ingredients_item_" + newSelectId});
	jQuery('#ingredients_quantity_' + maxId).attr({"id": "ingredients_quantity_" + newSelectId, "name": "ingredients_quantity_" + newSelectId});
	jQuery('#ingredients_unit_' + maxId).attr({"id": "ingredients_unit_" + newSelectId, "name": "ingredients_unit_" + newSelectId});
	jQuery('#ingredients_comment_' + maxId).attr({"id": "ingredients_comment_" + newSelectId, "name": "ingredients_comment_" + newSelectId});
	jQuery('#btn_remove_ingredient_' + maxId).attr({"id": "btn_remove_ingredient_" + newSelectId, "name": "btn_remove_ingredient_" + newSelectId});
	
	//clone last good row
	//place after current max ingredient    
	var clone = jQuery('#ingredients_slug_' + (clonedId)).parent().clone();
	jQuery('#ingredients_slug_' + (clonedId)).parent().after(clone);
	clone.css("display","block");

	//copy required values into new fields
	clone.find('#ingredients_slug_' + clonedId).attr({"id":"ingredients_slug_" + maxId,
		"name":"ingredients_slug_" + maxId,
		"value" : jQuery('#ingredients_slug_' + newSelectId).val()
	});
	clone.find('#ingredients_item_' + clonedId).attr({"id":"ingredients_item_" + maxId,
		"name":"ingredients_item_" + maxId,
		"value" : jQuery('#ingredients_slug_' + newSelectId).find('option:selected').text()
	});
	clone.find('#ingredients_quantity_' + clonedId).attr({"id":"ingredients_quantity_" + maxId,
		"name":"ingredients_quantity_" + maxId,
		"value" : jQuery('#ingredients_quantity_' + newSelectId).val()
	});
	var newComment = jQuery('#ingredients_comment_' + newSelectId).val();
	clone.find('#ingredients_comment_' + clonedId).attr({"id":"ingredients_comment_" + maxId,
		"name":"ingredients_comment_" + maxId,
		"value" : newComment
	});
	//not refreshing display so update DOM value too
	jQuery('#ingredients_comment_' + maxId).val(newComment);
	clone.find('#ingredients_unit_' + clonedId).attr({"id":"ingredients_unit_" + maxId,
		"name":"ingredients_unit_" + maxId,
		//"value" : jQuery('#ingredients_unit_' + newSelectId).find('option:selected').text()
	});	
	jQuery('#ingredients_unit_' + maxId).val(jQuery('#ingredients_unit_' + newSelectId).find('option:selected').text());

	clone.find('#btn_remove_ingredient_' + clonedId).attr({"id":"btn_remove_ingredient_" + maxId});

	//reset values on drop down row
	jQuery('#ingredients_slug_' + newSelectId).prop('selectedIndex',0);
	jQuery('#ingredients_item_' + newSelectId).val("");
	jQuery('#ingredients_quantity_' + newSelectId).val("");
	jQuery('#ingredients_comment_' + newSelectId).val("");
	jQuery('#ingredients_unit_' + newSelectId).val("Grams");
	jQuery('#btn_remove_ingredient_' + newSelectId).prop('disabled',true);

	jQuery('#btn_add_ingredient').prop('disabled',true);
}
function ingredientRemove(target){
	target.parentNode.remove();
}
function activityAdd(target){
	var maxId = maxFieldNo('activity_name_');
	var newId = maxId + 1;
	var clonedId = maxFieldNo('activity_name_', maxId);

	jQuery('#activity_name_' + maxId).attr({"id": "activity_name_" + newId, "name": "activity_name_" + newId});
	jQuery('#activity_duration_' + maxId).attr({"id": "activity_duration_" + newId, "name": "activity_duration_" + newId});
	jQuery('#btn_remove_activity_' + maxId).attr({"id": "btn_remove_activity_" + newId, "name": "btn_remove_activity_" + newId});
	
	//clone last good row
	//place after current max ingredient    
	var clone = jQuery('#activity_name_' + (clonedId)).parent().clone();
	jQuery('#activity_name_' + (clonedId)).parent().after(clone);
	clone.css("display","block");

	//copy required values into new fields
	clone.find('#activity_name_' + clonedId).attr({"id":"activity_name_" + maxId,
		"name":"activity_name_" + maxId,
		"value" : jQuery('#activity_name_' + newId).val()
	});
	clone.find('#activity_duration_' + clonedId).attr({"id":"activity_duration_" + maxId,
		"name":"activity_duration_" + maxId,
		"value" : jQuery('#activity_duration_' + newId).val()
	});
	clone.find('#btn_remove_activity_' + clonedId).attr({"id":"btn_remove_activity_" + maxId});

	//reset values on drop down row
	jQuery('#activity_name_' + newId).val("");
	jQuery('#activity_duration_' + newId).val("");

	//jQuery('#btn_add_activity').prop('disabled',true);
}
function activityRemove(target){
	target.parentNode.remove();
}
function ingredientsItemChange(target){
	btn_add_ingredient.disabled = (target.value=="NULL");
	var id = target.id.substring(17, target.id.length);
	jQuery("#ingredients_item_" + id).val(jQuery("#ingredients_slug_" + id + " option:selected").text());
}
function copyToClipboard(checkM, targetM, targetG){
	if(jQuery('#' + checkM).css('display') == 'block'){
		tinyMCE.get(targetM).execCommand('selectAll',true,'id_text');
		tinyMCE.get(targetM).execCommand('copy',true,'id_text');
	}else if(jQuery('#' + targetG).css('display') == 'block'){
		navigator.clipboard.writeText(jQuery('#' + targetG).html()).then(function() {
			alert('Copied');
		}, function(err) {
			alert('Error - not copied');
		});		
	}
}
function maxFieldNo(fieldPrefix, exclude = null){
	var maxId = 0;
	var eachId;
	jQuery('[id^=' + fieldPrefix + ']').each(function() {
		idLen = this.id.length;
		eachId = Number(this.id.substring(fieldPrefix.length, idLen));
		if (exclude == null || exclude != eachId){
			if(eachId > maxId){
				maxId = eachId;
			}
		}
	});
	return parseInt(maxId);  
}
function extractAllergens(input){
	// only if there is strong text	
	if(!(input.match(/<strong>(.*?)<\/strong>/g)==null)){
		//get everything that is strong and remove tags
		var extractedResult = input.match(/<strong>(.*?)<\/strong>/g).map(function(val){
			return val.replace(/<\/?strong>/g,'');
		});
		//TO DO: needs extra split on 
		//capitalise first letters
		extractedResult = extractedResult.map(function(a){return a.toLowerCase().split(' ').map(word => word.charAt(0).toUpperCase() + word.substring(1)).join(' ');})
		//put bold back
		extractedResult = extractedResult.map(function(a) { return "<strong>" + a + "</strong>"; })
	}
	// join up items again
	return extractedResult.join(", ");
}
function refreshGeneratedAllergens(){
	var ingreds;
	var allergenTxt;
	//if(document.getElementById('rcpmst_ingredient_listing_manual_or_generated_meta_field').checked){
		//generated ing listing
	//	ingreds = document.getElementById('ingredient_listing_generated_div_text').innerText;
	//}else{			
		ingreds = tinymce.editors.rcpmst_ingredient_listing_manual_listing_meta_field.getContent();
	//}
	allergenTxt = extractAllergens(ingreds);
	if(allergenTxt.length == 0)allergenTxt = "&nbsp;";
	jQuery('#allergens_generated_div_text').html(allergenTxt);
	jQuery('#rcpmst_allergens_generated_listing_meta_field').val(allergenTxt);
}
function checkAmount(input) {
	if (input.value > 0) {
		// input is fine -- reset the error message
		input.setCustomValidity('');					
	} else {
		input.setCustomValidity('The amount produced/purchased must be greater than zero.');
	}
}
function checkPrice(input) {
	if (input.value < 0) {
		input.setCustomValidity('The retail price must not be negative.');
	} else {
		input.setCustomValidity('');					
	}
}
function setHeading(){
	document.getElementsByClassName("wp-heading-inline")[0].innerHTML = "Ingredients";
}
function addIngredientField(){
	jQuery("#posts-filter").append("<input type=\'hidden\' name=\'rcpmst-filter\' value=\'rcpmst-ingredients\'>");
}
;(function($){
	'use strict';
	$(document).ready(function(){
		if(jQuery('#rcpmst_ingredient_or_recipe_meta_field').length){ //if exists...
			showHideAltName();
			var recipeOrIngredientField = document.getElementById('rcpmst_ingredient_or_recipe_meta_field');
			var valueR = recipeOrIngredientField.value;
			if (valueR=="Recipe"){
				isRecipe();
			} else if (valueR == 'Ingredient'){
				isIngredient();
			}else{
				isNonEdible();
			}
			isIngredientListingGenerated(jQuery('#rcpmst_ingredient_listing_manual_or_generated_meta_field').is(':checked'));
			isAllergensGenerated(jQuery('#rcpmst_allergens_manual_or_generated_meta_field').is(':checked'));
			isDietaryNotesGenerated(jQuery('#rcpmst_dietary_notes_manual_or_generated_meta_field').is(':checked'));

			recipeOrIngredientField.addEventListener("change", (event) => {
				var value = event.target.value;
				if(value == 'Recipe') {
					isRecipe();
				} else if (value == 'Ingredient'){
					isIngredient();
				}else{
					isNonEdible();
				}
				var dietValue = jQuery('#rcpmst_dietary_notes_manual_or_generated_meta_field').is(':checked');
				isDietaryNotesGenerated(dietValue);
				showHideAltName();
			});
			jQuery('#rcpmst_ingredient_listing_manual_or_generated_meta_field').on("change",function(){
				var value = jQuery('#rcpmst_ingredient_listing_manual_or_generated_meta_field').is(':checked');
				isIngredientListingGenerated(value);
			});    
			jQuery('#rcpmst_ingredient_listing_generator').on("click",function(){
				// for ingredients, if generated copy title
				if (jQuery('#rcpmst_ingredient_or_recipe_meta_field').val() == 'Ingredient'){
					jQuery('#rcpmst_ingredient_listing_generated_listing_meta_field').val(jQuery('#title').val());
					jQuery('#ingredient_listing_generated_div_text').text(jQuery('#title').val());
				}
			});
			jQuery('#rcpmst_allergens_manual_or_generated_meta_field').on("change", function(){
				var value = jQuery('#rcpmst_allergens_manual_or_generated_meta_field').is(':checked');
				isAllergensGenerated(value);
			});
			jQuery('#rcpmst_allergens_manual_or_generated_meta_field').on("click",function(){
				refreshGeneratedAllergens();
			});			   
			jQuery('#rcpmst_allergens_generator').on("click",function(){
				refreshGeneratedAllergens();
			});
			jQuery('#rcpmst_dietary_notes_manual_or_generated_meta_field').on("change", function(){
				var value = jQuery('#rcpmst_dietary_notes_manual_or_generated_meta_field').is(':checked');
				isDietaryNotesGenerated(value);
			});
		}
	})    

})( jQuery )