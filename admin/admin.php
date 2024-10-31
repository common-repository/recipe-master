<?php
namespace Recipe_Master;

class Admin {

	private $options;
	
	public function __construct(  $options ) {
		$this->options = $options;
	}

	public function enqueue_styles() {
		wp_enqueue_style( WP_RCPMST__PLUGIN_NAME . '-adminstyles', plugin_dir_url( __FILE__ ) . 'css/admin.css', array(), WP_RCPMST__VERSION, 'all' );
	}
	public function enqueue_scripts() {
		wp_enqueue_script( WP_RCPMST__PLUGIN_NAME . '-adminscripts', plugin_dir_url( __FILE__ ) . 'js/admin.js', array( 'jquery' ), WP_RCPMST__VERSION, false );
	}

	private $safePrice;
	private $safeIngredientOrRecipe;
	private $safeSupplier;
	private $safeDietaryNotes;
	private $safeSource;
	private $safeRecipe;
	private $safeAmountVal;
	private $safeAmountUnit;
	private $safeIngredientListingMOrG;
	private $safeIngredientListingM;
	private $safeIngredientListingG;
	private $safeIngredientListingCompound;
	private $safeIngredientListingAlternateName;
	private $safeAllergensMOrG;
	private $safeAllergensM;
	private $safeAllergensG;
	private $safeDietaryNotesMOrG;
	private $safeDietaryNotesM;
	private $safeDietaryNotesG;
	private $safeIngredients;
	private $safeActivities;
	private $safeSKU;
	private $safeStorage;
	private $safeRetailPrice;
	private $safeShelfLife;
	private $safeABV;

	// all get, post and server params to be checked here
	// add & edit uses standard wordpress generated forms so no additional nonce required
	function pre_save_validation($post_id, $post_data) {
		global $wpdb ;
		$exit = false;
		$error = '';
		$post = get_post($post_id); 
		$slug = $post->post_name;
		// If this is just a revision, don't do anything.
		if (wp_is_post_revision($post_id)){
			return;
		}
		if ($post_data['post_type'] == 'rcpmst_recipe_comp') {
			if (!current_user_can("edit_recipe_components")) {
				error_log('User not permitted');
				header('Location: '.get_edit_post_link($post_id, 'redirect'));
				exit;
			}

			// sanitise first
			$sanitiseQuickEditIngredientNonce;
			if(isset($_POST['_quickedit_nonce_ingredient'])){
				$sanitiseQuickEditIngredientNonce = sanitize_key(wp_unslash($_POST["_quickedit_nonce_ingredient"]));
			}
			$sanitiseQuickEditRecipeNonce;
			if(isset($_POST['_quickedit_nonce_recipe'])){
				$sanitiseQuickEditRecipeNonce = sanitize_key(wp_unslash($_POST["_quickedit_nonce_recipe"]));
			}	
			$sanitiseIngredientOrRecipe;			
			if (isset($_POST['rcpmst_ingredient_or_recipe_meta_field'])){
				$sanitiseIngredientOrRecipe = sanitize_text_field(wp_unslash($_POST['rcpmst_ingredient_or_recipe_meta_field']));
			}
			$sanitiseAmountUnit;
			if (isset($_POST['rcpmst_amount_unit_meta_field'])){
				$sanitiseAmountUnit = sanitize_text_field(wp_unslash($_POST['rcpmst_amount_unit_meta_field']));
			}
			$sanitiseIngredientListingMOrG = "Manual"; //checkbox so default 
			if (isset($_POST['rcpmst_ingredient_listing_manual_or_generated_meta_field'])){
				$sanitiseIngredientListingMOrG = sanitize_text_field(wp_unslash($_POST['rcpmst_ingredient_listing_manual_or_generated_meta_field']));
			}
			$sanitiseAllergensMOrG = "Manual"; //checkbox so default 
			if (isset($_POST['rcpmst_allergens_manual_or_generated_meta_field'])){
				$sanitiseAllergensMOrG = sanitize_text_field(wp_unslash($_POST['rcpmst_allergens_manual_or_generated_meta_field']));
			}
			$sanitiseDietaryNotesMOrG = "Manual"; //checkbox so default 
			if (isset($_POST['rcpmst_dietary_notes_manual_or_generated_meta_field'])){
				$sanitiseDietaryNotesMOrG = sanitize_text_field(wp_unslash($_POST['rcpmst_dietary_notes_manual_or_generated_meta_field']));
			}
			$sanitiseIngredientListingCompound = "Non-Compound"; //checkbox so default 
			if (isset($_POST['rcpmst_ingredient_listing_compound_meta_field'])){
				$sanitiseIngredientListingCompound = sanitize_text_field(wp_unslash($_POST['rcpmst_ingredient_listing_compound_meta_field']));
			}	
			$sanitisePrice;											
			if (isset($_POST['rcpmst_price_meta_field'])){
				$sanitisePrice = sanitize_text_field(wp_unslash($_POST['rcpmst_price_meta_field']));
			}
			$sanitiseAmountVal;	
			if (isset($_POST['rcpmst_amount_val_meta_field'])){
				$sanitiseAmountVal = sanitize_text_field(wp_unslash($_POST['rcpmst_amount_val_meta_field']));
			}	
			$sanitiseABV;
			if (isset($_POST['rcpmst_abv_meta_field'])){
				$sanitiseABV = sanitize_text_field(wp_unslash($_POST['rcpmst_abv_meta_field']));
			}	
			if (isset($_POST['rcpmst_retail_price_meta_field'])){
				$sanitiseRetailPrice = sanitize_text_field(wp_unslash($_POST['rcpmst_retail_price_meta_field']));
			}	
			$sanitiseRetailPrice;
			if (isset($_POST['rcpmst_storage_shelf_life_meta_field'])){									
				$sanitiseShelfLife = sanitize_text_field(wp_unslash($_POST['rcpmst_storage_shelf_life_meta_field']));
			}
			$sanitiseSupplier;
			if(isset($_POST['rcpmst_supplier_meta_field'])){
				$sanitiseSupplier = sanitize_text_field(wp_unslash($_POST["rcpmst_supplier_meta_field"]));
			}
			$sanitiseSource;
			if(isset($_POST['rcpmst_source_meta_field'])){
				$sanitiseSource = sanitize_text_field(wp_unslash($_POST["rcpmst_source_meta_field"]));
			}
			$sanitiseIngredientListingAlternateName;
			if(isset($_POST['rcpmst_ingredient_listing_alternate_name_meta_field'])){
				$sanitiseIngredientListingAlternateName = sanitize_text_field(wp_unslash($_POST["rcpmst_ingredient_listing_alternate_name_meta_field"]));
			}
			$sanitiseSKU;
			if(isset($_POST['rcpmst_retail_sku_meta_field'])){
				$sanitiseSKU = sanitize_text_field(wp_unslash($_POST["rcpmst_retail_sku_meta_field"]));
			}$sanitiseStorage;
			if(isset($_POST['rcpmst_storage_storage_meta_field'])){
				$sanitiseStorage = sanitize_text_field(wp_unslash($_POST["rcpmst_storage_storage_meta_field"]));
			}
			$sanitiseRecipe;
			if(isset($_POST['rcpmst_recipe_meta_field'])){
				$sanitiseRecipe = wp_kses_post($_POST["rcpmst_recipe_meta_field"]);
			}
			$sanitiseIngredientListingM;
			if(isset($_POST['rcpmst_ingredient_listing_manual_listing_meta_field'])){
				$sanitiseIngredientListingM = wp_kses_data($_POST["rcpmst_ingredient_listing_manual_listing_meta_field"]);
			}	
			$sanitiseAllergensM;
			if(isset($_POST['rcpmst_allergens_manual_listing_meta_field'])){
				$sanitiseAllergensM = wp_kses_data($_POST["rcpmst_allergens_manual_listing_meta_field"]);
			}
			//$sanitiseAllergensG;
			//if(isset($_POST['rcpmst_allergens_generated_listing_meta_field'])){
			//	$sanitiseAllergensG = wp_kses_data($_POST["rcpmst_allergens_generated_listing_meta_field"]);
			//}		
			//arrays
			$sanitiseIngredients;
			if(isset($_POST['ingredients_item_0'])){
				$sanitiseIngredients = array();
				foreach( $_POST as $item => $value ) {
					if(! is_array( $item ) and (substr($item,0,12) == "ingredients_" )) {
						if(!($value=="NULL")){
							$sanitiseIngredients[$item] = sanitize_text_field(wp_unslash($value));
						}
					}
				}
			}			
			$sanitiseActivities;
			if(isset($_POST['activity_name_0'])){
				$sanitiseActivities = array();
				foreach( $_POST as $item => $value ) {
					if(! is_array( $item ) and (substr($item,0,9) == "activity_" )) {
						if(!($value=="")){
							$sanitiseActivities[$item] = sanitize_text_field(wp_unslash($value));
						}
					}
				}
			}
		
			//validation checks
			$title = $post_data['post_title'];
			// duplicate title check - not using caching to make sure duplication check goes to the source data
			$wresults = $wpdb->get_results( $wpdb->prepare( 
				"SELECT post_title FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'rcpmst_recipe_comp' AND post_title = %s AND ID != %d "
				, $title, $post_id )) ;
			if ( $wresults ) {
				$exit = $this->log_validation_error('This component title is already in use.');
			}

			//validate any nonce we have
			if (isset( $sanitiseQuickEditIngredientNonce )){
				if ( ! wp_verify_nonce( $sanitiseQuickEditIngredientNonce, '_quickedit_nonce_ingredient'.$post_id ) ) {
					$exit = $this->log_validation_error('quick edit ingredient nonce failed');
					die( 'quick edit ingredient nonce failed' ) ; 
				}
			}
			if (isset( $sanitiseQuickEditRecipeNonce )){
				if ( ! wp_verify_nonce( $sanitiseQuickEditRecipeNonce, '_quickedit_nonce_recipe'.$post_id ) ) {
					$exit = $this->log_validation_error('quick edit recipe nonce failed');
					die( 'quick edit recipe nonce failed' ) ; ;
				}
			} 
			//check we have a nonce (note _wpnonce is the one automatically added by post edit, so that will be checked elsewhere) Also don't check on autosave
			if ( ! (isset( $sanitiseQuickEditIngredientNonce ) || 
					isset( $sanitiseQuickEditRecipeNonce) || 
					isset($_POST["_wpnonce"]))) {
				if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
					return;
				}
				$exit = $this->log_validation_error('no nonce');
				return;
			}

			// whitelist checks
			$this->safeIngredientOrRecipe = $this->whitelist_check($exit, $sanitiseIngredientOrRecipe, ['Recipe','Ingredient','Non-Edible'],'Only Recipe, Ingredient or Non-Edible are valid values.');
			$this->safeAmountUnit = $this->whitelist_check($exit, $sanitiseAmountUnit, Single_Edit::$units,'A valid unit has not been selected.');
			$this->safeIngredientListingMOrG = $this->whitelist_check($exit,$sanitiseIngredientListingMOrG ,["Manual", "Generated"],'Must be Manual or Generated.');
			$this->safeAllergensMOrG = $this->whitelist_check($exit,$sanitiseAllergensMOrG ,["Manual", "Generated"],'Must be Manual or Generated.');
			$this->safeDietaryNotesMOrG = $this->whitelist_check($exit,$sanitiseDietaryNotesMOrG ,["Manual", "Generated"],'Must be Manual or Generated.');
			$this->safeIngredientListingCompound = $this->whitelist_check($exit,$sanitiseIngredientListingCompound ,["Compound", "Non-Compound"],'Must be Compound or Non-Compound.');

			// numeric checks
			$this->safePrice = $this->numeric_check($exit, $sanitisePrice,'Price must be a number' );
			$this->safeAmountVal = $this->numeric_check($exit, $sanitiseAmountVal,'Amount must be a number' );
			$this->safeABV = $this->numeric_check($exit, $sanitiseABV,'ABV must be a number' );
			$this->safeRetailPrice = $this->numeric_check($exit, $sanitiseRetailPrice,'Retail price must be a number' );
			
			//date interval
			$this->safeShelfLife = "";
			if(isset($sanitiseShelfLife) && $sanitiseShelfLife != ""){
				set_error_handler([$this,"date_warning_handler"], E_WARNING);
				$interval = 'P' . strtoupper($sanitiseShelfLife);
				new \DateInterval($interval);
				restore_error_handler();
				$this->safeShelfLife = strtoupper($sanitiseShelfLife);
			}	

			//strings & rich text
			$this->safeSupplier = $sanitiseSupplier;
			$this->safeSource = $sanitiseSource;
			$this->safeIngredientListingAlternateName = $sanitiseIngredientListingAlternateName;
			$this->safeSKU = $sanitiseSKU;
			$this->safeStorage = $sanitiseStorage;
			$this->safeRecipe = $sanitiseRecipe;
			$this->safeIngredientListingM = $sanitiseIngredientListingM;
			$this->safeAllergensM = $sanitiseAllergensM;
			//$this->safeAllergensG = $sanitiseAllergensG;

			//collections											
			if(isset($sanitiseIngredients)){
				$this->safeIngredients = array();
				$itemCount = 0;
				foreach( $sanitiseIngredients as $item => $value ) {
					if(substr($item,0,17) == "ingredients_item_" ) {
						//get item number
						$itemNo = substr($item,17, strlen($item) - 17);
						//skip itemNo 0 as this will always be our template row
						if ($itemNo > 0){
							$itemSlug = $sanitiseIngredients["ingredients_slug_" . $itemNo];
							if($itemSlug == "NULL" || $itemSlug == ""){
								//skip item, it is the blank row
							}elseif(!is_numeric($sanitiseIngredients["ingredients_quantity_" . $itemNo])){
								$exit = $this->log_validation_error('Ingredient excluded - quantity not numeric - ' . $itemSlug);
							}elseif(! in_array( $sanitiseIngredients["ingredients_unit_" . $itemNo] , Single_Edit::$units, true )) {
								$exit = $this->log_validation_error('A valid unit has not been selected for ' . $itemSlug);
							}elseif($itemSlug == $slug){
								//if somehow manage to add self to self, exclude it...
								$exit = $this->log_validation_error('A recipe can not have itself as a subingredient - ' . $itemSlug);
							}else{
								$ingredient = array();
								$ingredient['item'] = $value;
								$ingredient['quantity'] = $sanitiseIngredients["ingredients_quantity_" . $itemNo];
								$ingredient['unit'] = $sanitiseIngredients["ingredients_unit_" . $itemNo];
								$ingredient['comment'] = $sanitiseIngredients["ingredients_comment_" . $itemNo];						
								$ingredient['slug'] = $itemSlug;
								$this->safeIngredients[$itemCount++] = $ingredient;
							}
						}
					}
				}
			}

			if(isset($sanitiseActivities)){
				$this->safeActivities = array();
				$itemCount = 0;
				foreach( $sanitiseActivities as $item => $value ) {
					if(! is_array( $item ) and (substr($item,0,14) == "activity_name_" )) {
						if(!($value=="")){
							//get item number
							$itemNo = substr($item,14, strlen($item) - 14);
							//skip itemNo 0 as this will always be our template row
							if ($itemNo > 0){
								if(!is_numeric($sanitiseActivities["activity_duration_" . $itemNo])){
									$this->log_validation_error('Activity excluded - duration not numeric');
								}else{
									$activity = array();
									$activity['activity'] = $value;
									$activity['duration'] = $sanitiseActivities["activity_duration_" . $itemNo];
									$this->safeActivities[$itemCount++] = $activity;
								}
							}
						}
					}
				}
			}

			if($exit){
				header('Location: '.get_edit_post_link($post_id, 'redirect'));
				exit;
			}
		}
	}
	function whitelist_check(&$exit, $sanitisedString, $valid_strings, $errorString){
		if (isset($sanitisedString)){
			if (! in_array( $sanitisedString, $valid_strings, true ) ) {
				$exit = $this->log_validation_error($errorString);
			}else{
				return $sanitisedString;
			}
		}		
	}
	function numeric_check(&$exit, $sanitisedString, $errorString){
		if (isset($sanitisedString)){
			if ($sanitisedString != "" && !is_numeric($sanitisedString)){
				$exit = $this->log_validation_error($errorString);
			}else{
				return $sanitisedString;
			}
		}	
	}	
	function date_warning_handler($errno, $errstr) { 
		$this->log_validation_error('Shelf Life must be a valid date interval');
		header('Location: '.get_edit_post_link(0, 'redirect'));
		exit;
	}
	function log_validation_error($error){
		error_log($error);
		update_option('rcpmst_notifications', wp_json_encode(array('error', $error)));
		return true;
	}
	function save_recipe_component($post_id){
		global $post;

		//only on main edit, not quick edit, so find post id based on this
		if(isset($this->safeIngredientOrRecipe)){
			$post_id = $post->ID;
		}
		
		// check if there was a multisite switch before
		if ( is_multisite() && ms_is_switched() ) {
			return $post_id;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		
		if(isset($this->safeIngredientOrRecipe)){
			update_post_meta($post_id, "rcpmst_ingredient_or_recipe_meta", $this->safeIngredientOrRecipe);
		}
		if(isset($this->safePrice)){
			update_post_meta($post_id, "rcpmst_price_meta", $this->safePrice);
		}
		if(isset($this->safeSupplier)){
			update_post_meta($post_id, "rcpmst_supplier_meta", $this->safeSupplier);
		}
		if(isset($this->safeDietaryNotes)){
			update_post_meta($post_id, "rcpmst_dietary_notes_meta", $this->safeDietaryNotes);
		}
		if(isset($this->safeSource)){
			update_post_meta($post_id, "rcpmst_source_meta", $this->safeSource);
		}
		if(isset($this->safeRecipe)){
			update_post_meta($post_id, "rcpmst_recipe_meta", $this->safeRecipe);
		}
		if(isset($this->safeABV)){
			update_post_meta($post_id, "rcpmst_abv_meta", $this->safeABV);
		}
		if(isset($this->safeAmountVal)
			|| isset($this->safeAmountUnit)){
			$amount = array("val" => $this->safeAmountVal,"unit" => $this->safeAmountUnit);
			update_post_meta($post_id, "rcpmst_amount_meta", $amount); 
		}
		if(isset($this->safeRetailPrice)
			|| isset($this->safeSKU)){
			$retail = array("retailPrice" => $this->safeRetailPrice,"sku" => $this->safeSKU);
			update_post_meta($post_id, "rcpmst_retail_meta", $retail); 
		}		
		if(isset($this->safeStorage)
			|| isset($this->safeShelfLife)){
			$storage = array("shelfLife" => $this->safeShelfLife, "storage" => $this->safeStorage);
			update_post_meta($post_id, "rcpmst_storage_meta", $storage); 
		}			
		if(isset($this->safeIngredientListingMOrG) || isset($this->safeIngredientListingM) || isset($this->safeIngredientListingCompound) || isset($this->safeIngredientListingAlternateName)){
			$ingredient_listing = array(
				"manual_or_generated" => $this->safeIngredientListingMOrG,
				"manual_listing" => $this->safeIngredientListingM, 
				"compound" => $this->safeIngredientListingCompound,
				"alternate_name" => $this->safeIngredientListingAlternateName,
				);
			update_post_meta($post_id, "rcpmst_ingredient_listing_meta", $ingredient_listing);
		}
		if(isset($this->safeAllergensMOrG) || isset($this->safeAllergensM)){// || isset($this->safeAllergensG)){
			$allergens = array("manual_or_generated" => $this->safeAllergensMOrG,"manual_listing" => $this->safeAllergensM);//, "generated_listing" => $this->safeAllergensG);
			update_post_meta($post_id, "rcpmst_allergens_meta", $allergens);		
		}
		if(isset($this->safeDietaryNotesMOrG) ){
			$dietary_notes = array("manual_or_generated" => $this->safeDietaryNotesMOrG);
			update_post_meta($post_id, "rcpmst_dietary_notes_meta", $dietary_notes);
		}
		if(isset($this->safeIngredients)){
			update_post_meta($post_id, "rcpmst_ingredients_meta", $this->safeIngredients);
		}
		if(isset($this->safeActivities)){
			update_post_meta($post_id, "rcpmst_activities_meta", $this->safeActivities);
		}
	}

    function add_submenus(){
        add_submenu_page(
            'edit.php?post_type=rcpmst_recipe_comp',
            'Ingredient Listing', //page title
            'Ingredients', //menu title
            'edit_recipe_components', //capability,
            'ingredients',//menu slug
            'edit.php?post_type=rcpmst_recipe_comp'
            );
    }
    function add_submenu_args(){
        global $submenu;
        $position = $this->search_submenu( 'ingredients', 'edit.php?post_type=rcpmst_recipe_comp' );

        // make sure we modify our page
        if ( is_int($position) && $submenu['edit.php?post_type=rcpmst_recipe_comp'][$position][2] == 'ingredients' ) {
            // we will recompose the whole url, starting with parent
            $submenu['edit.php?post_type=rcpmst_recipe_comp'][$position][2] = add_query_arg( 'rcpmst-filter', 'rcpmst-ingredients', 'edit.php?post_type=rcpmst_recipe_comp' );
        }
    }
    function search_submenu( $page_slug, $parent_slug ) {
        global $submenu;

        if ( !isset( $submenu[$parent_slug] ) )
            return null;

        foreach ( $submenu[$parent_slug] as $i => $item ) {
            if ( $page_slug == $item[2] ) {
                return $i;
            }
        }

        return null;
    }
	/**
	*   Shows custom notifications on wordpress admin panel
	*/
	function show_admin_notices() {
		$notifications = get_option('rcpmst_notifications');
		
		if (!empty($notifications)) {
			$notifications = json_decode($notifications);
			#notifications[0] = (string) Type of notification: error, updated or update-nag
			#notifications[1] = (string) Message
			#notifications[2] = (boolean) is_dismissible?
			switch ($notifications[0]) {
				case 'error': # red
				case 'updated': # green
				case 'update-nag': # ?
					$class = $notifications[0];
					break;
				default:
					# Defaults to error just in case
					$class = 'error';
					break;
			}

			$is_dismissable = '';
			if (isset($notifications[2]) && $notifications[2] == true)
				$is_dismissable = 'is_dismissable';

			echo '<div class="' . esc_attr($class) . ' notice ' . esc_attr($is_dismissable) . '">';
			echo '<p>' . esc_html($notifications[1]) . '</p>';
			echo '</div>';

			# Let's reset the notification
			update_option('rcpmst_notifications', false);
		}
	}
}