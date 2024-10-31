<?php
namespace Recipe_Master;
class Listings {

	public function __construct(  ) {
		$this->run();
	}

	public function run(){
		$this->sanitiseGetParams();
	}

	private $safeFilter;
	private $safeOrder;
	private $safeTaxonomy_rcpmst_recipe_type;
	private $safeTaxonomy_rcpmst_selection;
	
	// nonces checked in class-recipe-master-admin in pre_save_validation()
	function sanitiseGetParams(){
		//if (is_post_type_archive( 'rcpmst_recipe_comp' )) {
			//whitelist Check
			if(isset($_GET['rcpmst-filter'])){
				if(!$_GET['rcpmst-filter'] == 'rcpmst-ingredients'){
					error_log('Invalid listing filter');
					update_option('rcpmst_notifications', wp_json_encode(array('error', 'Invalid listing filter')));
					$exit = true;
				}else{
					$this->safeFilter = isset($_GET['rcpmst-filter']) ? sanitize_text_field(wp_unslash($_GET['rcpmst-filter'])) : ""; 
				}
			}			
			// string checks
			if(isset($_GET['rcpmst_recipe_type'])){
				$this->safeTaxonomy_rcpmst_recipe_type = sanitize_text_field(wp_unslash($_GET['rcpmst_recipe_type']));
			}else{
				$this->safeTaxonomy_rcpmst_recipe_type = '';
			}
			if(isset($_GET['rcpmst_selection'])){
				$this->safeTaxonomy_rcpmst_selection = sanitize_text_field(wp_unslash($_GET['rcpmst_selection']));
			}else{
				$this->safeTaxonomy_rcpmst_selection = '';
			}	
			if(isset($_GET['order'])){
				$this->safeOrder = sanitize_text_field(wp_unslash($_GET['order']));
			}					
		//}
	}

	//
	// Identify which type of listing we are in
	//
	function isRcpMstListing($checkPostType){
		if(!is_admin()) return false;
		if(!is_post_type_archive( 'rcpmst_recipe_comp' )) return false;
		if (isset($current_screen) && $checkPostType){
			return(($current_screen->id =='edit-rcpmst_recipe_comp') && ($current_screen->post_type =='rcpmst_recipe_comp'));
		}else{
			return true;
		}
	}
	function isIngredientListing($checkPostType){
		//it is an ingred listing if query param is set, or if hidden quick edit is present
    	if(isset($_POST["_quickedit_nonce_ingredient"])){
      		return true;
    	} 
		return($this->isRcpMstListing($checkPostType) && isset($this->safeFilter) && $this->safeFilter=="rcpmst-ingredients");
	}
	function isMainListing($checkPostType){
    	if(isset($_POST["_quickedit_nonce_recipe"])){
      		return true;
    	} 
		return($this->isRcpMstListing($checkPostType) && !($this->isIngredientListing($checkPostType)));
	}

	//
	// Set columns visible and populate them
	//
	//already filters by rcpmst as part of filter
	//set columns visible in admin screens
	function edit_columns_recipe_component($columns){
		if ($this->isMainListing(false)){
			$columns = array(
				"cb" => "<input type='checkbox' />",
				"title" => "Title",
				"types" => "Types",
				"selections" => "Selections",
				"amount" => "Quantity",
				"unit" => "Unit",				
			);
		}elseif ($this->isIngredientListing(false)){
			$columns = array(
				"cb" => "<input type='checkbox' />",
				"title" => "Title",
				"recipeIngredient" => "Recipe/Ingredient",				
				"price" => "Price",	
				"amount" => "Quantity",
				"unit" => "Unit",
				"supplier" => "Supplier",
				//"dietaryNotes" => "Dietary Notes",
			);		
		}
		return $columns;
	}
	//populate columns in admin screens
	function custom_columns_recipe_component($column){
		global $post;
		$custom = get_post_custom();
		switch ($column) {				
			case "recipeIngredient":
				echo esc_html($custom["rcpmst_ingredient_or_recipe_meta"][0]);
				break;
			case "types":
				echo wp_kses_post(get_the_term_list($post->ID, 'rcpmst_recipe_type', '', ', ',''));
				break;	  
			case "selections":
				echo wp_kses_post(get_the_term_list($post->ID, 'rcpmst_selection', '', ', ',''));
				break;	 
			case "price":
				echo esc_html($custom["rcpmst_price_meta"][0]);
				break;
			case "amount":
				$arr = unserialize($custom["rcpmst_amount_meta"][0]);	
				echo esc_html($arr["val"]);
				break;
			case "unit":
				$arr = unserialize($custom["rcpmst_amount_meta"][0]);
				echo esc_html($arr["unit"]);
				break;
			case "supplier":
				echo esc_html($custom["rcpmst_supplier_meta"][0]);
				break;
		}
	}

	//
	// Sorting and Filtering
	//
	//already filters by rcpmst as part of filter
	//set sortable columns
	function sortable_columns_recipe_component( $columns ) {
		$columns['recipeIngredient'] = 'Recipe/Ingredient';	
		$columns['price'] = 'Price';
		$columns['supplier'] = 'Supplier';
		return $columns;
	}	
	//filter ingredient listing screen and sorting
    function customise_query($query){
		if( $query->is_main_query() && $this->isRcpMstListing(true)){
			$orderby = $query->get( 'orderby' );
    		if ( 'Price' == $orderby ) {
				$query->set('orderby','meta_value_num');
				$query->set('meta_key','rcpmst_price_meta');
    		}
    		if ( 'Recipe/Ingredient' == $orderby ) {
				$query->set('orderby','meta_value');
				$query->set('meta_key','rcpmst_ingredient_or_recipe_meta');
    		}		
    		if ( 'Supplier' == $orderby ) {
				$query->set('orderby','meta_value');
				$query->set('meta_key','rcpmst_supplier_meta');
    		}						
			if(isset($_GET["order"])){
				$query->set('order',$this->safeOrder);
			}
			if( $query->is_main_query() && $this->isIngredientListing(true)) {
				$meta_filter = array(
						array(
						'key' => 'rcpmst_ingredient_or_recipe_meta',
						'value' => array('Ingredient','Non-Edible'),
						'compare' => 'IN'
					)
				);
				$query->set( 'meta_query', $meta_filter );
			}
			if( $query->is_main_query() && $this->isMainListing(true)) {
				$meta_filter = array(
						array(
						'key' => 'rcpmst_ingredient_or_recipe_meta',
						'value' => 'Recipe',
						'compare' => '='
					)
				);
				$query->set( 'meta_query', $meta_filter );
			}
		}
    }
	function views_filter_for_recipe_components( $views ) {
		$post_type = get_query_var('post_type');
		$author = get_current_user_id();

		unset($views['mine']);

		$new_views = array(
				'all'       => 'All',
				'publish'   => 'Published',
				'private'   => 'Private',
				'pending'   => 'Pending Review',
				'future'    => 'Scheduled',
				'draft'     => 'Draft',
				'trash'     => 'Trash'
				);

		foreach( $new_views as $view => $name ) {
			$query = array(
				'author'      => $author,
				'post_type'   => $post_type
			);
			if( $this->isIngredientListing(true)) {
				$query['meta_query'] = array(
						array(
						'key' => 'rcpmst_ingredient_or_recipe_meta',
						'value' => array('Ingredient','Non-Edible'),
						'compare' => 'IN'
					)
				);
			}
			if(  $this->isMainListing(true)) {
				$query['meta_query'] = array(
						array(
						'key' => 'rcpmst_ingredient_or_recipe_meta',
						'value' => 'Recipe',
						'compare' => '='
					)
				);
			}
			if($view == 'all') {
				$query['all_posts'] = 1;
				$class = ( get_query_var('all_posts') == 1 || get_query_var('post_status') == '' ) ? ' class="current"' : '';
				$url_query_var = 'all_posts=1';
			} else {
				$query['post_status'] = $view;
				$class = ( get_query_var('post_status') == $view ) ? ' class="current"' : '';
				$url_query_var = 'post_status='.$view;
			}
			$result = new \WP_Query($query);
			if($result->found_posts > 0) {
				$views[$view] = sprintf(
					'<a href="%s"'. $class .'>' . $name . ' <span class="count">(%d)</span></a>',
					admin_url('edit.php?'.$url_query_var.'&post_type='.$post_type),
					$result->found_posts
				);
			} else {
				unset($views[$view]);
			}
		}
		return $views;
	}	
	/* edit the admin page title for a particular custom post type & add hidden field to make sure when searching for ingredients, paging etc work */
	function add_onload_js() {
    	if( $this->isIngredientListing(true) ) {
			if ( ! wp_script_is( 'jquery', 'done' ) ) {
     			wp_enqueue_script( 'jquery' );
   			}
   			wp_add_inline_script(WP_RCPMST__PLUGIN_NAME . '-adminscripts', 'jQuery(document).ready(function(){setHeading();addIngredientField();});' );
    	}
	}

	/**
	* Display a custom taxonomy dropdown in admin
	* @author Mike Hemberger
	* @link http://thestizmedia.com/custom-post-type-filter-admin-custom-taxonomy/
	*/
	function filter_post_type_by_taxonomy_recipe_component() {
		if ($this->isMainListing(false)){
			$this->add_filter_dropdown('rcpmst_recipe_type');
			$this->add_filter_dropdown('rcpmst_selection');
		}
	}
	function add_filter_dropdown($taxonomy){
		$selected      = $this->{'safeTaxonomy_' . $taxonomy};
		$info_taxonomy = get_taxonomy($taxonomy);
		wp_dropdown_categories(array(
			'show_option_all' => sprintf( 'Show all %s', $info_taxonomy->label ),
			'taxonomy'        => $taxonomy,
			'name'            => $taxonomy,
			'orderby'         => 'name',
			'selected'        => $selected,
			'show_count'      => true,
			'hide_empty'      => true,
		));
	}
	/**
	 * Filter posts by taxonomy in admin
	 * @author  Mike Hemberger
	 * @link http://thestizmedia.com/custom-post-type-filter-admin-custom-taxonomy/
	 */
	function convert_id_to_term_in_query_recipe_component($query) {
		$q_vars    = &$query->query_vars;
		if($this->isRcpMstListing(false)){
			$this->filter_by_dropdown($q_vars,'rcpmst_recipe_type');
			$this->filter_by_dropdown($q_vars,'rcpmst_selection');
		}
	}	
	function filter_by_dropdown(&$q_vars, $taxonomy){ // note q_var passed by ref
		if(isset($q_vars[$taxonomy]) && is_numeric($q_vars[$taxonomy]) && $q_vars[$taxonomy] != 0 ) {
			$term = get_term_by('id', $q_vars[$taxonomy], $taxonomy);
			$q_vars[$taxonomy] = $term->slug;
		}
	}

	//
	// Quick Edit Functions
	//
	//quick edit - create controls
	function ingredient_custom_edit_box( $column_name, $post_type ) {
		global $post;
		if($this->isRcpMstListing(true)){
			//add hidden fields to identify listing type and act as nonce
			if($this->isIngredientListing(true)){
				$hiddenName = "_quickedit_nonce_ingredient";
			}else{
				$hiddenName = "_quickedit_nonce_recipe";
			}
			?>
            <input type="hidden" name="<?php echo esc_attr($hiddenName); ?>" id="<?php echo esc_attr($hiddenName); ?>" value=""/>
			<?php 
            if( $column_name === 'price' ){ ?>
				<fieldset class="inline-edit-col-right" id="#edit-">
					<div class="inline-edit-col">					
            			<label>
							<span class="title">Price</span>
							<span class="input-text-wrap"><input type="number" step="any" name="rcpmst_price_meta_field" id="rcpmst_price_meta_field" class="inline-edit-menu-order-input" value=""></span>
						</label>
					</div>
				</fieldset>
				<?php
			}
			if( $column_name === 'supplier' ){ ?>
				<fieldset class="inline-edit-col-right" id="#edit-">
					<div class="inline-edit-col">					
            			<label>
							<span class="title">Supplier</span>
							<span class="input-text-wrap"><input type="text" name="rcpmst_supplier_meta_field" id="rcpmst_supplier_meta_field" class="inline-edit-menu-order-input" value=""></span>
						</label>
					</div>
				</fieldset>
				<?php
			}
		}
	}
	//quick edit - customise appearance
	function quick_edit_javascript() {
    	if($this->isIngredientListing(true)){
			wp_enqueue_script( WP_RCPMST__PLUGIN_NAME . '-listingquickedit', plugin_dir_url( __FILE__ ) . 'js/admin-ingredients.js', array( 'jquery' ), WP_RCPMST__VERSION, false );
		}elseif($this->isMainListing(true)){
			wp_enqueue_script( WP_RCPMST__PLUGIN_NAME . '-listingquickedit', plugin_dir_url( __FILE__ ) . 'js/admin-recipes.js', array( 'jquery' ), WP_RCPMST__VERSION, false );			
		}
	}
	//quick edit - patch in customised appearance script
	function expand_quick_edit_link($actions,$post) {
		if($this->isRcpMstListing(true)){
			if($this->isIngredientListing(true)){
				$nonce= wp_create_nonce('_quickedit_nonce_ingredient'.$post->ID);
				$price= get_post_meta($post->ID,'rcpmst_price_meta', TRUE);
				$supplier= get_post_meta($post->ID,'rcpmst_supplier_meta', TRUE);
			}elseif($this->isMainListing(true)){
				$nonce= wp_create_nonce('_quickedit_nonce_recipe'.$post->ID);
				$price= 0;
				$supplier = '';
			}
			$actions['inline hide-if-no-js'] ='<a href="#" class="editinline" title="';
			$actions['inline hide-if-no-js'] .= esc_attr( 'Edit this item inline')  .'" ';
			$actions['inline hide-if-no-js'] .=" onclick=\"set_inline_widget_set('{$price}','{$supplier}', '{$nonce}')\">";
			$actions['inline hide-if-no-js'] .= 'Quick&nbsp;Edit';
			$actions['inline hide-if-no-js'] .='</a>';
		}	
		return $actions; 
	}
}