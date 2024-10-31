<?php
namespace Recipe_Master;

class Activator {

	//defines the content we create when activating plugin so same lists can be used on uninstall
	public static $pageList = array(
		array(
			'page'		=>'Recipe Master Menu Page', 
			'slug'		=>'recipe-master-menu',
			'template'	=>'recipe-master-public-page.php',
			'content'	=>'[rcpmst_menu]',
		),
		array(
			'page'		=>'Recipe Master Selection List', 
			'slug'		=>'recipe-master-selection-list',
			'template'	=>'recipe-master-public-page.php',
			'content'	=>'[rcpmst_taxonomy_list taxonomy="rcpmst_selection"]',
		),
		array(
			'page'		=>'Recipe Master Recipe Type List', 
			'slug'		=>'recipe-master-recipe-type-list',
			'template'	=>'recipe-master-public-page.php',
			'content'	=>'[rcpmst_taxonomy_list taxonomy="rcpmst_recipe_type"]',
		),
		array(
			'page'		=>'Recipe Master Recipe List', 
			'slug'		=>'recipe-master-recipe-list',
			'template'	=>'recipe-master-public-page.php',
			'content'	=>'[rcpmst_recipe_list]',
		),
		array(
			'page'		=>'Recipe Master My Files', 
			'slug'		=>'recipe-master-user-defined',
			'template'	=>'recipe-master-public-page.php',
			'content'	=>'[rcpmst_user_defined]',
		),
		array(
			'page'		=>'Recipe Master Checklist', 
			'slug'		=>'recipe-master-checklist',
			'template'	=>'recipe-master-public-page.php',
			'content'	=>'[rcpmst_checklist]',
		),						
	);
	public static $roleList = array(
		array(
			'role' => 'Head Chef',
			'slug' => 'head_chef',
		),
		array(
			'role' => 'Chef',
			'slug' => 'chef',
		),		
	);
	public static $capabilityList = array( 
		array(
			'slug' => 'read_recipe_component',		
			'grantToChef' => true,
		),
		array(
			'slug' => 'read_recipe_components',		
			'grantToChef' => true,
		),
		array(
			'slug' => 'edit_recipe_component',		
			'grantToChef' => false,
		),
		array(
			'slug' => 'edit_recipe_components',		
			'grantToChef' => false,
		),
		array(
			'slug' => 'edit_others_recipe_components',		
			'grantToChef' => false,
		),
		array(
			'slug' => 'edit_recipe_components',		
			'grantToChef' => false,
		),	
		array(
			'slug' => 'edit_published_recipe_components',		
			'grantToChef' => false,
		),	
		array(
			'slug' => 'publish_recipe_components',		
			'grantToChef' => false,
		),	
		array(
			'slug' => 'delete_recipe_component',		
			'grantToChef' => false,
		),
		array(
			'slug' => 'delete_recipe_components',		
			'grantToChef' => false,
		),
		array(
			'slug' => 'delete_published_recipe_components',		
			'grantToChef' => false,
		),
		array(
			'slug' => 'delete_others_recipe_components',		
			'grantToChef' => false,
		),																								
	);	

	public static function activate() {
		// add roles
		foreach (Activator::$roleList as $role){
			add_role( $role['slug'], $role['role'], array( 'read' => true, 'level_0' => true ) );
		}
		// add capabilities
		$role = get_role( 'administrator' );
		foreach ( Activator::$capabilityList as $cap ) {
			$role->add_cap( $cap['slug'] );
		}
		$role = get_role( 'head_chef' );
		foreach ( Activator::$capabilityList as $cap ) {
			$role->add_cap( $cap['slug'] );
		}
		$role->add_cap('read');

		$role = get_role( 'chef' );
		foreach ( Activator::$capabilityList as $cap ) {
			if($cap['grantToChef']){
				$role->add_cap( $cap['slug'] );
			}
		}
		$role->add_cap('read');		
		// add pages
		foreach (Activator::$pageList as $page){
			if (null == get_page_by_path( $page['slug'] , OBJECT )){
				$my_post  = array( 'post_title'     => $page['page'],
					'post_type'      => 'page',
					'post_name'      => $page['slug'],
					'post_content'   => $page['content'],
					'post_status'    => 'publish',
					'comment_status' => 'closed',
					'page_template'       => $page['template'],
					'ping_status'    => 'closed',
					'post_author'    => 1,
					'menu_order'     => 0,
				);
				$PageID = wp_insert_post( $my_post, FALSE );
			}			
		}

        //create taxonomy so we can insert initial values to dietary notes          
        $rcType = new Recipe_Component_Type();
        $rcType->recipe_component_custom_post_type();
		$rcType->create_dietary_notes_taxonomy();
        $dietaryNotes = new Dietary_Notes();
        $dietaryNotes->populate_dietary_notes();
	}	
}