<?php
namespace Recipe_Master;
class Single_Edit {

	private $pagenow;
	private $options;
	private $pathToHelp = WP_RCPMST__DOC_URL . "/wiki/";
	private $unitFieldAllowed = array(
	   "select" => array(
		   "name" => array(),
    	   "id" => array()
		),
   		"option" => array(
        	"value" => array(),
        	"selected" => array()
    	),
	);

	public static $units = ['Grams','Kilograms','Millilitres','Pieces','Drops'];

	public function __construct(  $options ) {
		global $pagenow;
		$this->options = $options;
		$this->pagenow = $pagenow;
	}

	public $recipeComponent;
	function init(){
		global $post;
		add_meta_box("rcpmst_ingredient_or_recipe_meta", "Ingredient Or Recipe", [$this,"ingredient_or_recipe_meta_cb"], "rcpmst_recipe_comp", "normal", "high");
		add_meta_box("rcpmst_recipe_meta", "Recipe", [$this,"recipe_meta_cb"], "rcpmst_recipe_comp", "normal", "low");
		// amount is 2 value array - val for quantity and units for unit of measure
		add_meta_box("rcpmst_amount_meta", "Purchased or Produced Amount", [$this,"amount_meta_cb"], "rcpmst_recipe_comp", "normal", "low");
		add_meta_box("rcpmst_price_meta", "Purchase Price or Cost", [$this,"price_meta_cb"], "rcpmst_recipe_comp", "normal", "low");
		add_meta_box("rcpmst_retail_meta", "Sale Price and SKU", [$this,"retail_meta_cb"], "rcpmst_recipe_comp", "normal", "low");
		add_meta_box("rcpmst_storage_meta", "Storage Instructions and Shelf Life", [$this,"storage_meta_cb"], "rcpmst_recipe_comp", "normal", "low");
		// ingredientListing is array of Calculate? (y/n) Listing (text field) GeneratedListing (text field)
		add_meta_box("rcpmst_ingredient_listing_meta", "Ingredient Listing", [$this,"ingredient_listing_meta_cb"], "rcpmst_recipe_comp", "normal", "high");
		// ingredients is array of SubRecipes (array of recipe components)
		add_meta_box("rcpmst_ingredients_meta", "Ingredients", [$this,"ingredients_meta_cb"], "rcpmst_recipe_comp", "normal", "low");
		add_meta_box("rcpmst_allergens_meta", "Allergens", [$this,"allergens_meta_cb"], "rcpmst_recipe_comp", "normal", "low");
		add_meta_box("rcpmst_dietary_notes_meta", "Dietary Notes", [$this,"dietary_notes_meta_cb"], "rcpmst_recipe_comp", "normal", "low");
		add_meta_box("rcpmst_abv_meta", "ABV (%)", [$this,"abv_meta_cb"], "rcpmst_recipe_comp", "normal", "low");
		add_meta_box("rcpmst_supplier_meta", "Supplier", [$this,"supplier_meta_cb"], "rcpmst_recipe_comp", "side", "low");
		add_meta_box("rcpmst_source_meta", "Source", [$this,"source_meta_cb"], "rcpmst_recipe_comp", "side", "low");
		add_meta_box("rcpmst_activities_meta", "Activities", [$this,"activities_meta_cb"], "rcpmst_recipe_comp", "normal", "low");
		if ($this->options['debug']=="yes"){
			add_meta_box("rcpmst_debug_meta", "Debug", [$this,"debug_meta_cb"], "rcpmst_recipe_comp", "normal", "low");
		}
       	$this->recipeComponent = new Recipe_Component();

		//checklist meta
		add_meta_box("rcpmst_checklists_meta", "Checklist", [$this,"checklist_meta_cb"], "rcpmst_checklist", "normal", "high");
	}

	function metabox_order( $order ) {
		return array(
			'normal' => join( 
				",", 
				array(       // vvv  Arrange here as you desire
					'rcpmst_ingredient_or_recipe_meta',
					'rcpmst_amount_meta',
					'rcpmst_ingredients_meta',
					'rcpmst_ingredient_listing_meta',
					'rcpmst_activities_meta',
					'rcpmst_recipe_meta',
					'rcpmst_price_meta',
					'rcpmst_retail_meta',
					'rcpmst_allergens_meta',
					'rcpmst_dietary_notes_meta',
					'rcpmst_abv_meta',
					'rcpmst_storage_meta',
					'rcpmst_debug_meta',
				)
			),
		);
	}

	function ingredient_listing_meta_cb(){
    	$ingredientListing = $this->recipeComponent->get_full_ingredient_listing();
		if (!(is_array($ingredientListing))){
			$ingredientListing = array("manual_or_generated" => "Manual","manual_listing" => "","generated_listing"=>"");
		}
		?>
		<div id="rcpmst_ingredient_listing_manual_or_generated_meta_field_div">
		<table width="100%">
		<tr><td>
		<label>Generate?</label>
		<input type="checkbox" <?php 
		if($this->pagenow == 'post-new.php') {
			echo 'checked';
		}else{
			checked( $ingredientListing["manual_or_generated"], 'Generated');
		} ?> name="rcpmst_ingredient_listing_manual_or_generated_meta_field" id="rcpmst_ingredient_listing_manual_or_generated_meta_field" value="Generated">
		</td>
		<td align="right">
		<button type="button" id="rcpmst_ingredient_listing_copy" style="background-size: 25px 25px;background-image: url( '<?php echo esc_url(plugin_dir_url( __FILE__ ) . 'images/copy.svg')?>');height:30px;width:30px" 
		onClick="copyToClipboard('ingredient_listing_manual_div','rcpmst_ingredient_listing_manual_listing_meta_field','ingredient_listing_generated_div_text')"></button>
		<button type="button" style="background-image: url( '<?php echo esc_url(plugin_dir_url( __FILE__ ) . 'images/help.svg')?>');height:30px;width:30px" 
		onClick="window.open('<?php echo esc_url($this->pathToHelp . "ingredient-listings") ?>','_blank')"></button>
		</td></tr>
		<tbody id="ing_listing_compound">
		<tr><td>
		<label>Compound</label>
		<input type="checkbox" <?php checked( isset($ingredientListing["compound"]) ? esc_html($ingredientListing["compound"]) : '', 'Compound' ); ?> name="rcpmst_ingredient_listing_compound_meta_field" id="rcpmst_ingredient_listing_compound_meta_field" value="Compound" onClick="showHideAltName();">
		</td></tr>
		</tbody><tbody id="ing_listing_alt">
		<tr><td>
		<label>Alternate Name</label>
		<input name="rcpmst_ingredient_listing_alternate_name_meta_field" id="rcpmst_ingredient_listing_alternate_name_meta_field" value="<?php echo isset($ingredientListing["alternate_name"]) ? esc_html($ingredientListing["alternate_name"]) : ""; ?>">
		</td></tr>
		</tbody>
		</table>
		</div>
		<div id="ingredient_listing_manual_div">
			<label>Manual Entry</label>
			<?php
			$settings = array( 
				'teeny' => false, 
				'media_buttons' => false,
				'textarea_rows' => 3,
				'tinymce' => array(
					'toolbar1' => 'bold',
					'toolbar2'=>false
				),
			);
			wp_editor(wp_kses_post($ingredientListing["manual_listing"]), 'rcpmst_ingredient_listing_manual_listing_meta_field', $settings );
			?>
		</div>
		<div id="ingredient_listing_generated_div">
			<label>Generated Values (updated on save)</label>
			<div id="ingredient_listing_generated_div_text" style="padding:2px;border: 1px solid black">
				<?php echo wp_kses_post($this->recipeComponent->get_ingredient_listing()); ?>
			</div>
		</div>
		<?php
	}
	function activities_meta_cb(){
		$activities = $this->recipeComponent->get_activities();
		?>
		<div id="activity_list">
			<p style="display:none">
				<label>Activity</label>
				<input type="text" name="activity_name_0" id="activity_name_0" value="">
				<label>Duration</label>
				<input type="text" name="activity_duration_0" id="activity_duration_0" value="" size="6">
				<input type="button" id="btn_remove_activity_0" value="-" onClick="activityRemove(this)">
			</p>
			<?php
			$activityIndex = 1;
			if($activities): 
				foreach($activities as $activity):
					if (isset($activity["activity"])){
						if (!($activity["activity"]=="NULL")):
							?>
							<p>
							<label>Activity</label>
							<input type="text" name="activity_name_<?php echo esc_html($activityIndex)?>" id="activity_name_<?php echo esc_html($activityIndex)?>" value="<?php echo esc_html($activity['activity'])?>">
							<label>Duration</label>
							<input type="text" name="activity_duration_<?php echo esc_html($activityIndex)?>" id="activity_duration_<?php echo esc_html($activityIndex)?>" value="<?php echo esc_html($activity['duration'])?>" size="6">
							<input type="button" id="btn_remove_activity_<?php echo esc_html($activityIndex)?>" value="-" onClick="activityRemove(this)">
							<?php $activityIndex++; ?>
							</p>
							<?php 
						endif;
					}
				endforeach; 
			endif;
			?>
			<p>
			<label>Activity</label>
			<input type="text" name="activity_name_<?php echo esc_html($activityIndex)?>" id="activity_name_<?php echo esc_html($activityIndex)?>">
			<label>Duration</label>
			<input type="text" name="activity_duration_<?php echo esc_html($activityIndex)?>" id="activity_duration_<?php echo esc_html($activityIndex)?>" size="6">
			<input type="button" id="btn_remove_activity_<?php echo esc_html($activityIndex)?>" value="-" onClick="activityRemove(this)" disabled>
			</p>
		</div>
		<p id="p_add_activity"><input type="button" id="btn_add_activity" value="+" onClick="activityAdd()"></p>    
		<?php
	}
	function ingredients_meta_cb(){
		$ingredients = $this->recipeComponent->get_ingredients();
        
		$recipeComponentArgs = array(
			'post_type' => 'rcpmst_recipe_comp',
			'post_status' => 'publish',
			'orderby' => 'title',
			'order' => 'ASC',
			'numberposts' => -1
		);
		$recipeComps = get_posts($recipeComponentArgs);
		//$ingredientsUsed = array();
		if($recipeComps): 
			?>
			<div id="ingredient_list">
				<!--hidden controls used for adding only when adding the first item - subsequent items copy from last good rows -->
				<p style="display:none">
					<label>Ingredient</label>
					<input type="hidden" name="ingredients_slug_0" id="ingredients_slug_0" value="">
					<input type="text" name="ingredients_item_0" readonly id="ingredients_item_0" value="">
					<label>Quantity</label>
					<input type="number" step="any" name="ingredients_quantity_0" id="ingredients_quantity_0" value=""  max="999999">
					
					<?php echo wp_kses($this->generate_unit_field('ingredients_unit_0',''),$this->unitFieldAllowed) ;?>
					<label>Comment</label>
					<input type="text" name="ingredients_comment_0" id="ingredients_comment_0" value="">
					<input type="button" id="btn_remove_ingredient_0" value="-" onClick="ingredientRemove(this)">
				</p>			
				<?php   //let us start by putting in all the loaded recipe components 
				$ingredientIndex = 1;
				if(is_array($ingredients)){
					foreach($ingredients as $ingredient):
						if (!($ingredient["slug"]=="NULL")):
							?>
							<p>
							<label>Ingredient</label>
							<input type="hidden" name="ingredients_slug_<?php echo esc_html($ingredientIndex)?>" id="ingredients_slug_<?php echo esc_html($ingredientIndex)?>" value="<?php echo esc_html($ingredient['slug'])?>">
							<input type="text" name="ingredients_item_<?php echo esc_html($ingredientIndex)?>" readonly id="ingredients_item_<?php echo esc_html($ingredientIndex)?>" value="<?php echo esc_html($ingredient['item'])?>">
							<label>Quantity</label>
							<input type="number" step="any" name="ingredients_quantity_<?php echo esc_html($ingredientIndex)?>" id="ingredients_quantity_<?php echo esc_html($ingredientIndex)?>" value="<?php echo esc_html($ingredient['quantity'])?>" max="999999">
							<?php echo wp_kses($this->generate_unit_field('ingredients_unit_' . $ingredientIndex,$ingredient['unit']),$this->unitFieldAllowed) ;?>
							<label>Comment</label>
							<input type="text" name="ingredients_comment_<?php echo esc_html($ingredientIndex)?>" id="ingredients_comment_<?php echo esc_html($ingredientIndex)?>" value="<?php echo esc_html($ingredient['comment'])?>">
							<input type="button" id="btn_remove_ingredient_<?php echo esc_html($ingredientIndex)?>" value="-" onClick="ingredientRemove(this)">
							<?php $ingredientIndex++; ?>
							</p>
							<?php 
						endif;
					endforeach; 
				}
				// then finally the blank row
				?>
				<p>
				<label>Ingredient</label>
				<input type="hidden" name="ingredients_item_<?php echo esc_html($ingredientIndex)?>" id="ingredients_item_<?php echo esc_html($ingredientIndex)?>">
				<select name="ingredients_slug_<?php echo esc_html($ingredientIndex)?>" id="ingredients_slug_<?php echo esc_html($ingredientIndex)?>" onChange="ingredientsItemChange(this)" style="width:180px">
					<option value="NULL">Please choose an ingredient or recipe</option>
					<?php 
					foreach($recipeComps as $recipeComp): 
						//note post_name is the slug
						if (($recipeComp->post_title != $this->recipeComponent->get_title()) ):?>
							<option value="<?php echo esc_html($recipeComp->post_name); ?>"><?php echo esc_html($recipeComp->post_title); ?></option>
						<?php endif;
					endforeach; ?>
				</select>
				<label>Quantity</label> <input type="number" step="any" id="ingredients_quantity_<?php echo esc_html($ingredientIndex)?>" name="ingredients_quantity_<?php echo esc_html($ingredientIndex)?>"  max="999999">
				<?php echo wp_kses($this->generate_unit_field('ingredients_unit_' . $ingredientIndex,''),$this->unitFieldAllowed) ;?>
				<label>Comment</label> <input type="text" id="ingredients_comment_<?php echo esc_html($ingredientIndex)?>" name="ingredients_comment_<?php echo esc_html($ingredientIndex)?>">
				<input type="button" id="btn_remove_ingredient_<?php echo esc_html($ingredientIndex)?>" value="-" onClick="ingredientRemove(this)" disabled>
				</p>
			</div>
			<p id="p_add_ingredient"><input type="button" id="btn_add_ingredient" value="+" disabled="true" onClick="ingredientAdd()"></p>    
			<?php
		else:
			?>
			<p>There are no recipe Components yet.</p>
			<?php
		endif;
	}

	function recipe_meta_cb(){
		$recipe = $this->recipeComponent->get_recipe();
		$settings = array( 
			'teeny' => false, 
			'media_buttons' => false,
			'textarea_rows' => 10,
		);
		wp_editor( wp_kses_post($recipe), 'rcpmst_recipe_meta_field', $settings );
	}

	function debug_meta_cb(){
        global $post;
        $debug = "WP Post->" . print_r($post,true);
        $debug .= "<br/>Meta->" . print_r($this->recipeComponent->get_post_meta_debug(),true);
		$debug .= "<br/>Post->" . esc_html(print_r($_POST,true)); //not checking nonce as just displaying for debug purposes
		$debug .= "<br/>Ingedients-> (note amounts are gram equiv, while units are stored values)<br/>";
		$arr = $this->recipeComponent->get_ingredient_details();
		$count = 1;
		$debug .= "<table border='1'>";
		foreach($arr as $arrRow): 
			//headers
			if($count ==1){
				$debug .= "<tr>";
				foreach($arrRow as $paramName => $arrItem): 
					$debug .= "<td>" . $paramName . "</td>";
				endforeach;
				$debug .= "</tr>";				
			}
			
			$debug .= "<tr>";
			foreach($arrRow as $arrItem): 
				$debug .= "<td>" . print_r($arrItem,true) . "</td>";
			endforeach;
			$debug .= "</tr>";
			$count ++;
		endforeach;
		$debug .= "</table>";

		$debug .= "<br/>Ingredient Structure-><br/>";
		$debug .= print_r($this->recipeComponent->get_ingredient_structure_details(),true);

		$debug .= "<br/>Options<br/>";
		$debug .= print_r(get_option( 'rcpmst-plugin-settings' ),true);
		$debug .= "<br/>Ing Listing<br/>";
		$debug .= $this->recipeComponent->get_ingredient_listing();
		?>
		<div id="debug_div">
        <?php
			if ($this->options['debug']=="yes"){
				echo wp_kses_post($debug);
			}
			?>
		</div>
		<?php
	}


	function allergens_meta_cb(){
		$allergens = $this->recipeComponent->get_allergens();
		if (!(is_array($allergens))){
			$allergens = array("manual_or_generated" => "Manual","manual_listing" => "","generated_listing"=>"");
		}
		?>
		<div id="rcpmst_allergens_manual_or_generated_meta_field_div">
			<table width="100%"><tr>
			<td>
			<label>Auto Generate?</label>
			<input type="checkbox" <?php 
				if($this->pagenow == 'post-new.php') {
					echo 'checked';
				}else{
					checked( $allergens["manual_or_generated"], 'Generated');
				} ?> name="rcpmst_allergens_manual_or_generated_meta_field" id="rcpmst_allergens_manual_or_generated_meta_field" value="Generated">
			</td>
			<td align="right">
			<button type="button" id="rcpmst_allergens_copy" style="background-size: 25px 25px;background-image: url( '<?php echo esc_url(plugin_dir_url( __FILE__ ) . 'images/copy.svg')?>');height:30px;width:30px" 
			onClick="copyToClipboard('allergens_manual_div','rcpmst_allergens_manual_listing_meta_field','allergens_generated_div_text')" alt="Copy"></button>
			<button type="button" style="background-image: url( '<?php echo esc_url(plugin_dir_url( __FILE__ ) . 'images/help.svg')?>');height:30px;width:30px" 
			onClick="window.open('<?php echo esc_url($this->pathToHelp . "allergens") ?>','_blank')"></button>
			</td></tr></table>
		</div>
		<div id="allergens_manual_div">
			<label>Manual Entry</label>
			<?php
			$settings = array( 
				'teeny' => false, 
				'media_buttons' => false,
				'textarea_rows' => 3,
				'tinymce' => array(
					'toolbar1' => 'bold',
					'toolbar2'=>false
				),
			);
			wp_editor( wp_kses_post($allergens["manual_listing"]), 'rcpmst_allergens_manual_listing_meta_field', $settings );
			?>
		</div>
		<div id="allergens_generated_div">
			<label>Generated Values (updated on save)</label>
			<div id="allergens_generated_div_text" style="padding:2px;border: 1px solid black;" >
				<?php echo wp_kses_post($this->recipeComponent->get_allergens_text()); ?>
			</div>
            <input type="hidden" name="rcpmst_allergens_generated_listing_meta_field" id="rcpmst_allergens_generated_listing_meta_field" value="<?php
				echo wp_kses_post($this->recipeComponent->get_allergens_text());
				?>">
		</div>
		<?php
	}

	function dietary_notes_meta_cb(){
		$dietary_notes = $this->recipeComponent->get_dietary_notes();
		if (!(is_array($dietary_notes))){
			$dietary_notes = array("manual_or_generated" => "Manual","manual_listing" => "","generated_listing"=>"");
		}
		?>
		<div id="rcpmst_dietary_notes_manual_or_generated_meta_field_div">
			<table width="100%"><tr>
			<td>
			<label>Generate?</label>
			<input type="checkbox" <?php 
				if($this->pagenow == 'post-new.php') {
					echo 'checked';
				}else{
					checked( $dietary_notes["manual_or_generated"], 'Generated');
				} ?> name="rcpmst_dietary_notes_manual_or_generated_meta_field" id="rcpmst_dietary_notes_manual_or_generated_meta_field" value="Generated">
			</td>
			<td align="right">
			<button type="button" id="rcpmst_dietary_notes_copy" style="background-size: 25px 25px;background-image: url( '<?php echo esc_url(plugin_dir_url( __FILE__ ) . 'images/copy.svg')?>');height:30px;width:30px" 
			onClick="copyToClipboard('null','','dietary_notes_generated_div_text')" alt="Copy"></button>
			<button type="button" style="background-image: url( '<?php echo esc_url(plugin_dir_url( __FILE__ ) . 'images/help.svg')?>');height:30px;width:30px" 
			onClick="window.open('<?php echo esc_url($this->pathToHelp . "dietary-notes") ?>','_blank')"></button>
			</td></tr></table>
		</div>
		<div id="dietary_notes_manual_div">
			<label>Manual Entry</label>
			<p>Use Dietary Notes taxonomy in right hand panel to set values.</p>
		</div>
		<div id="dietary_notes_generated_div">
			<label>Generated Values (updated on save)</label>
		</div>
		<div id="dietary_notes_generated_div_text" style="padding:2px;border: 1px solid black">
			<?php echo wp_kses_post($this->recipeComponent->get_dietary_notes_text()); ?>
		</div>
		<?php

	}

	function ingredient_or_recipe_meta_cb() {
		$ingredientRecipe = $this->recipeComponent->get_ingredient_or_recipe();
		?>
		<label>Ingredient Or Recipe:</label>
		<select name="rcpmst_ingredient_or_recipe_meta_field" id="rcpmst_ingredient_or_recipe_meta_field">
			<option value="Recipe" <?php selected( $ingredientRecipe, 'Recipe' ); ?>>Recipe</option>
			<option value="Ingredient" <?php selected( $ingredientRecipe, 'Ingredient' ); ?>>Ingredient</option>
			<option value="Non-Edible" <?php selected( $ingredientRecipe, 'Non-Edible' ); ?>>Non-Edible</option>
		</select>
		<?php
	}
	function amount_meta_cb() {
		$amount = $this->recipeComponent->get_amount_denormalised();
		if (!(is_array($amount))){
			$amount = array("val" => "","unit" => "Grams");
		}
		?>
		<table><tr><td>
		<label>Amount:</label></td><td><label>Unit:</label></td></tr>
		<tr><td>
		<input type="number" step="any" name="rcpmst_amount_val_meta_field" id="rcpmst_amount_val_meta_field" value="<?php 
			if($amount['val']>0){ 
				echo esc_html($amount['val']); 
			}else{
				echo 1;
			}; ?>" oninput="checkAmount(this)" />
		</td><td>
		<?php echo wp_kses($this->generate_unit_field('rcpmst_amount_unit_meta_field', $amount['unit']), $this->unitFieldAllowed); ?>
		</td></tr></table>
		<?php
	}
	function retail_meta_cb() {
		$retail = array("retailPrice" => $this->recipeComponent->get_retail_price(),"sku" => $this->recipeComponent->get_sku());
		?>
		<table><tr><td>
		<label>Retail Price:</label></td><td>
		<input type="number" step="any" name="rcpmst_retail_price_meta_field" id="rcpmst_retail_price_meta_field" value="<?php 
			if($retail['retailPrice']>0){ 
				echo esc_html($retail['retailPrice']); 
			}else{
				echo 0;
			}; ?>" oninput="checkPrice(this)" />
		</td></tr>
		<tr><td>
		<label>SKU:</label></td><td>
		<input type="text"name="rcpmst_retail_sku_meta_field" id="rcpmst_retail_sku_meta_field" value="<?php echo esc_html($retail['sku']);?>"/>
		</td></tr></table>
		<?php
	}

	function storage_meta_cb() {
		$storage = array("shelfLife" => $this->recipeComponent->get_shelf_life(),"storage" => $this->recipeComponent->get_storage());
		?>
		<table><tr><td>
		<label>Shelf Life:</label></td><td>
		<input type="text" name="rcpmst_storage_shelf_life_meta_field" id="rcpmst_storage_shelf_life_meta_field" value="<?php echo esc_html($storage['shelfLife']);?>" oninput="checkPrice(this)" />
		</td></tr>
		<tr><td>
		<label>Storage Instructions:</label></td><td>
		<input type="text"name="rcpmst_storage_storage_meta_field" id="rcpmst_storage_storage_meta_field" value="<?php echo esc_html($storage['storage']);?>"/>
		</td></tr></table>
		<?php
	}
	function generate_unit_field($name, $selectedValue){
		$field = '<select name="' . $name . '" id="' . $name . '">';
		$field .= $this->generate_unit_field_option($selectedValue, 'Grams');
		$field .= $this->generate_unit_field_option($selectedValue, 'Kilograms');
		$field .= $this->generate_unit_field_option($selectedValue, 'Millilitres');
		$field .= $this->generate_unit_field_option($selectedValue, 'Pieces');
		$field .= $this->generate_unit_field_option($selectedValue, 'Drops');
		$field .= '</select>';
		return $field;
	}
	function generate_unit_field_option($selectedValue, $unit){
		return '<option value="' . $unit . '" ' . selected( $selectedValue, $unit, false ) . '>' . $unit . '</option>';
	}
	function price_meta_cb() {
		$price = $this->recipeComponent->get_price();
		?>
		<label>Price</label>
		<?php 
		if ($this->recipeComponent->get_ingredient_or_recipe() != "Recipe"){
			?>
			<input name="rcpmst_price_meta_field" value="<?php
		}
		echo esc_html($price);
		if ($this->recipeComponent->get_ingredient_or_recipe() != "Recipe"){
			?>">
			<?php
		}
	}
	function supplier_meta_cb() {
		$supplier = $this->recipeComponent->get_supplier();?>
		<label>Supplier:</label>
		<input name="rcpmst_supplier_meta_field" value="<?php echo esc_html($supplier); ?>" />
		<?php
	}
	function source_meta_cb() {
		$source = $this->recipeComponent->get_source();?>
		<label>Source:</label>
		<input name="rcpmst_source_meta_field" value="<?php echo esc_html($source); ?>" />
		<?php
	}
	function abv_meta_cb() {
		$abv = $this->recipeComponent->get_abv();?>
		<label>ABV:</label>
		<input type="number" step="any" name="rcpmst_abv_meta_field" value="<?php echo esc_html($abv); ?>" />%
		<?php
	}
	function checklist_meta_cb() {
		?>
		<label>Checklist:</label>
		
		<?php
		global $post;
		$results = "";
		$id = $post->ID;
		$rcpmst_checklist_meta_field = get_post_meta($id, "rcpmst_checklist_meta", true);
		$results .= "<table border='1'>";
		foreach($rcpmst_checklist_meta_field as $item){
			$results .= "<tr>";
			$results .= "<td>" . esc_html($item["description"]) . "</td>";
			$results .= "<td>" . esc_html($item["value"]) . "</td>";
			$results .= "<td>" . esc_html($item["comment"]) . "</td>";
			$results .= "</tr>";
		}
		$results .= "</table>";
		echo wp_kses_post($results);
	}
}