<?php

namespace Recipe_Master;

/**
 * This is used to load dependencies, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Defines templates
 *
 */
class Main {
    protected $loader;

    protected $optionsClass;

    protected $options;

    public function __construct() {
        $this->load_dependencies();
        $this->optionsClass = new Options();
        $this->options = $this->optionsClass->get_options();
        $this->loader = new Loader();
        $this->define_shared_hooks();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        //loader
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/loader.php';
        //shared
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/options.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/recipe-component.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/shortcodes.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/recipe-component-type.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/checklist-type.php';
        //admin
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/admin.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/listings.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/single-edit.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/dietary-notes.php';
        //public
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/public-content.php';
    }

    private function define_shared_hooks() {
        $plugin_recipe_component_type = new Recipe_Component_Type();
        $plugin_checklist_type = new Checklist_Type();
        $shortCodes = new Shortcodes();
        $this->loader->add_action( 'init', $plugin_recipe_component_type, 'recipe_component_custom_post_type' );
        $this->loader->add_action( 'init', $plugin_recipe_component_type, 'create_recipe_type_taxonomy' );
        $this->loader->add_action( 'init', $plugin_recipe_component_type, 'create_selection_taxonomy' );
        $this->loader->add_action( 'init', $plugin_recipe_component_type, 'create_dietary_notes_taxonomy' );
        $this->loader->add_filter( 'wp_insert_post_data', $plugin_recipe_component_type, 'default_comments_on' );
        $this->loader->add_action( 'init', $plugin_checklist_type, 'checklist_custom_post_type' );
        $this->loader->add_filter( 'wp_insert_post_data', $plugin_checklist_type, 'default_comments_on' );
        $this->loader->add_action( 'init', $shortCodes, 'shortcodes_init' );
    }

    private function define_admin_hooks() {
        $plugin_admin = new Admin($this->options);
        $plugin_listings = new Listings();
        $plugin_single_edit = new Single_Edit($this->options);
        $plugin_dietary_notes = new Dietary_Notes();
        $this->loader->add_action( 'admin_menu', $this->optionsClass, 'rcpmst_add_options_link' );
        $this->loader->add_action( 'admin_init', $this->optionsClass, 'rcpmst_register_settings' );
        $this->loader->add_action( 'admin_enqueue_scripts', $this->optionsClass, 'enqueue_scripts' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        $this->loader->add_action( 'save_post', $plugin_admin, 'save_recipe_component' );
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_submenus' );
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_submenu_args' );
        $this->loader->add_action( 'admin_notices', $plugin_admin, 'show_admin_notices' );
        $this->loader->add_action(
            'pre_post_update',
            $plugin_admin,
            'pre_save_validation',
            10,
            2
        );
        $this->loader->add_action( 'rcpmst_dietary_notes_add_form_fields', $plugin_dietary_notes, 'dietary_notes_taxonomy_custom_fields_add' );
        $this->loader->add_action( 'rcpmst_dietary_notes_edit_form_fields', $plugin_dietary_notes, 'dietary_notes_taxonomy_custom_fields_edit' );
        $this->loader->add_action( 'created_rcpmst_dietary_notes', $plugin_dietary_notes, 'dietary_notes_save_term_fields' );
        $this->loader->add_action( 'edited_rcpmst_dietary_notes', $plugin_dietary_notes, 'dietary_notes_save_term_fields' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_listings, 'add_onload_js' );
        $this->loader->add_action( "manage_rcpmst_recipe_comp_posts_custom_column", $plugin_listings, "custom_columns_recipe_component" );
        $this->loader->add_filter( "manage_rcpmst_recipe_comp_posts_columns", $plugin_listings, "edit_columns_recipe_component" );
        $this->loader->add_filter( 'manage_edit-rcpmst_recipe_comp_sortable_columns', $plugin_listings, 'sortable_columns_recipe_component' );
        $this->loader->add_action( 'restrict_manage_posts', $plugin_listings, 'filter_post_type_by_taxonomy_recipe_component' );
        $this->loader->add_filter( 'parse_query', $plugin_listings, 'convert_id_to_term_in_query_recipe_component' );
        $this->loader->add_action( 'pre_get_posts', $plugin_listings, 'customise_query' );
        $this->loader->add_action(
            'quick_edit_custom_box',
            $plugin_listings,
            'ingredient_custom_edit_box',
            10,
            2
        );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_listings, 'quick_edit_javascript' );
        $this->loader->add_filter(
            'post_row_actions',
            $plugin_listings,
            'expand_quick_edit_link',
            10,
            2
        );
        $this->loader->add_filter( 'views_edit-rcpmst_recipe_comp', $plugin_listings, 'views_filter_for_recipe_components' );
        $this->loader->add_action( "admin_init", $plugin_single_edit, "init" );
        $this->loader->add_filter( "get_user_option_meta-box-order_rcpmst_recipe_comp", $plugin_single_edit, "metabox_order" );
        $this->loader->add_filter(
            'theme_page_templates',
            $this,
            'add_template_to_select',
            10,
            4
        );
    }

    private function define_public_hooks() {
        $plugin_public = new Public_Content();
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
        $this->loader->add_action( 'init', $plugin_public, 'public_shortcodes_init' );
        $this->loader->add_filter( 'single_template', $plugin_public, 'recipe_comp_template' );
        $this->loader->add_filter( 'page_template', $this, 'public_page_template' );
    }

    public function run() {
        $this->loader->run();
    }

    //add templates
    //Load template from specific page
    function public_page_template( $page_template ) {
        if ( get_page_template_slug() == 'recipe-master-public-page.php' ) {
            $page_template = WP_RCPMST__PLUGIN_DIR . '/templates/public-page.php';
        }
        if ( get_page_template_slug() == 'recipe-master-user-defined-page.php' ) {
            $page_template = WP_RCPMST__PLUGIN_DIR . '/templates/user-defined-page.php';
        }
        return $page_template;
    }

    //Add templates to page editor's dropdown
    function add_template_to_select(
        $post_templates,
        $wp_theme,
        $post,
        $post_type
    ) {
        $post_templates['recipe-master-public-page.php'] = 'Recipe Master: Public Page';
        $post_templates['recipe-master-user-defined-page.php'] = 'Recipe Master: User Defined Page';
        return $post_templates;
    }

}
