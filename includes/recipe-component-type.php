<?php
namespace Recipe_Master;

class Recipe_Component_Type{
	public function recipe_component_custom_post_type() {
		register_post_type('rcpmst_recipe_comp',
			array(
				'labels'      => array(
					'name'          => 'Recipes',
					'singular_name' => 'Recipe Component',
					'add_new'		=> 'Add New Recipe Component',
				),
				'public'      => true,
				'has_archive' => true,
				'map_meta_cap' => true,
        		'menu_icon' => 'dashicons-food',
				'capability_type' => ['recipe_component','recipe_components'],
				'supports' => array('title','editor','thumbnail','comments', 'revisions', 'author'),
			)
		);
	}
	function create_recipe_type_taxonomy() {
		register_taxonomy('rcpmst_recipe_type','rcpmst_recipe_comp',array(
			'hierarchical' => false,
			'labels' => array(
				'name' =>  'Recipe Types', 'taxonomy general name' ,
				'singular_name' =>  'Recipe Type', 'taxonomy singular name' ,
				'menu_name' =>  'Recipe Types' ,
				'all_items' =>  'All Recipe Types' ,
				'edit_item' =>  'Edit Recipe Types' , 
				'update_item' =>  'Update Recipe Types' ,
				'add_new_item' =>  'Add Recipe Type' ,
				'new_item_name' =>  'New Recipe Type' ,
				'capabilities' => array( 'edit_recipe_components' ),
			),
			'show_ui' => true,
			'show_in_rest' => true,
			'show_admin_column' => true,
			'capabilities'      => array(
        		'manage_terms'  => 'edit_recipe_components',
        		'edit_terms'    => 'edit_recipe_components',
        		'delete_terms'  => 'edit_recipe_components',
        		'assign_terms'  => 'edit_recipe_components',
    		),			
		));
	}

	function create_selection_taxonomy() {
		register_taxonomy('rcpmst_selection','rcpmst_recipe_comp',array(
			'hierarchical' => false,
			'labels' => array(
				'name' =>  'Selections', 'taxonomy general name' ,
				'singular_name' =>  'Selection', 'taxonomy singular name' ,
				'menu_name' =>  'Selections' ,
				'all_items' =>  'All Selections' ,
				'edit_item' =>  'Edit Selections' , 
				'update_item' => 'Update Selections' ,
				'add_new_item' =>  'Add Selection' ,
				'new_item_name' =>  'New Selection' ,
			),
			'show_ui' => true,
			'show_in_rest' => true,
			'show_admin_column' => true,
			'capabilities'      => array(
        		'manage_terms'  => 'edit_recipe_components',
        		'edit_terms'    => 'edit_recipe_components',
        		'delete_terms'  => 'edit_recipe_components',
        		'assign_terms'  => 'edit_recipe_components',
    		),
		));
	}
	function create_dietary_notes_taxonomy() {
		register_taxonomy('rcpmst_dietary_notes','rcpmst_recipe_comp',array(
			'hierarchical' => false,
			'labels' => array(
				'name' =>  'Dietary Notes', 'taxonomy general name' ,
				'singular_name' =>  'Dietary Note', 'taxonomy singular name' ,
				'menu_name' =>  'Dietary Notes' ,
				'all_items' =>  'All Dietary Notes' ,
				'edit_item' =>  'Edit Dietary Notes' , 
				'update_item' =>  'Update Dietary Notes' ,
				'add_new_item' =>  'Add Dietary Note' ,
				'new_item_name' =>  'New Dietary Note' ,
			),
			'show_ui' => true,
			'show_in_rest' => true,
			'show_admin_column' => true,
			'capabilities'      => array(
        		'manage_terms'  => 'edit_recipe_components',
        		'edit_terms'    => 'edit_recipe_components',
        		'delete_terms'  => 'edit_recipe_components',
        		'assign_terms'  => 'edit_recipe_components',
    		),	            
		));
	}    

	function default_comments_on( $data ) {
		if( $data['post_type'] == 'rcpmst_recipe_comp' ) {
			$data['comment_status'] = 'open';
		}
		return $data;
	}
}