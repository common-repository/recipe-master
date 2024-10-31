<?php
namespace Recipe_Master;

class Checklist_Type{
	public function checklist_custom_post_type() {
		register_post_type('rcpmst_checklist',
			array(
				'labels'      => array(
					'name'          => 'Daily Checklist',
					'singular_name' => 'Checklist Entry',
					'add_new'		=> 'Add New Checklist Entry',
				),
				'public'      => true,
				'has_archive' => true,
				'map_meta_cap' => true,
        		'show_in_menu' => 'edit.php?post_type=rcpmst_recipe_comp',
				'capability_type' => ['recipe_component','recipe_components'],
				'supports' => array('title','editor','comments', 'revisions', 'author'),
			)
		);
	}	  
	function default_comments_on( $data ) {
		if( $data['post_type'] == 'rcpmst_recipe_comp' ) {
			$data['comment_status'] = 'open';
		}
		return $data;
	}

}