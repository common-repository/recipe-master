<?php

namespace Recipe_Master;

/*
 * Plugin Name: RecipeMaster
 * Plugin URI: https://www.coastalcocoa.co.uk/recipe-master
 * Description: Recipe management and website integration for chocolatiers and bakers
 * Version: 1.7.8
 * Requires at least: 5.3
 * Requires PHP: 5.6
 * Author: Coastal Cocoa
 * Author URI: https://www.coastalcocoa.co.uk
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: recipe-master
 * Domain Path: /public/lang
 * 
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
    // Exit if accessed directly
}
if ( !defined( 'WP_RCPMST__PLUGIN_DIR' ) ) {
    define( 'WP_RCPMST__PLUGIN_DIR', dirname( __FILE__ ) );
    define( 'WP_RCPMST__PLUGIN_NAME', plugin_basename( WP_RCPMST__PLUGIN_DIR ) );
    define( 'WP_RCPMST__PLUGIN_URL', plugins_url( WP_RCPMST__PLUGIN_NAME ) );
    define( 'WP_RCPMST__DOC_URL', 'https://jamesb520.sg-host.com/' );
}
define( 'WP_RCPMST__VERSION', '1.7.8' );
if ( function_exists( '\\Recipe_Master\\rm_fs' ) ) {
    rm_fs()->set_basename( false, __FILE__ );
} else {
    // DO NOT REMOVE THIS IF, IT IS ESSENTIAL FOR THE `function_exists` CALL ABOVE TO PROPERLY WORK.
    if ( !function_exists( '\\Recipe_Master\\rm_fs' ) ) {
        // Create a helper function for easy SDK access.
        function rm_fs() {
            global $rm_fs;
            if ( !isset( $rm_fs ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/freemius/start.php';
                $rm_fs = fs_dynamic_init( array(
                    'id'             => '15777',
                    'slug'           => 'recipe-master',
                    'type'           => 'plugin',
                    'public_key'     => 'pk_8f78f89332ba063a5921401cad731',
                    'is_premium'     => false,
                    'has_addons'     => false,
                    'has_paid_plans' => true,
                    'trial'          => array(
                        'days'               => 30,
                        'is_require_payment' => false,
                    ),
                    'menu'           => array(
                        'slug'    => 'rcpmst-options',
                        'support' => false,
                        'parent'  => array(
                            'slug' => 'options-general.php',
                        ),
                    ),
                    'is_live'        => true,
                ) );
            }
            return $rm_fs;
        }

        // Init Freemius.
        rm_fs();
        // Signal that SDK was initiated.
        do_action( 'rm_fs_loaded' );
    }
    // initialise plugin, require core classes
    // If this file is called directly, abort.
    if ( !defined( 'WPINC' ) ) {
        die;
    }
    function activate_recipe_master() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/activator.php';
        Activator::activate();
    }

    function deactivate_recipe_master() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/deactivator.php';
        Deactivator::deactivate();
    }

    register_activation_hook( __FILE__, __NAMESPACE__ . '\\activate_recipe_master' );
    register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\deactivate_recipe_master' );
    function uninstall() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/activator.php';
        foreach ( Activator::$roleList as $role ) {
            remove_role( $role['slug'] );
        }
        global $wp_roles;
        foreach ( Activator::$capabilityList as $cap ) {
            foreach ( array_keys( $wp_roles->roles ) as $role ) {
                $wp_roles->remove_cap( $role, $cap['slug'] );
            }
        }
        foreach ( Activator::$pageList as $page ) {
            delete_page( $page['page'] );
        }
    }

    function delete_page(  $title  ) {
        $query = new \WP_Query(array(
            'post_type'              => 'page',
            'title'                  => $title,
            'posts_per_page'         => 1,
            'no_found_rows'          => true,
            'ignore_sticky_posts'    => true,
            'update_post_term_cache' => false,
            'update_post_meta_cache' => false,
        ));
        if ( !empty( $query->post ) ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                wp_delete_post( get_the_ID(), $bypass_trash = true );
            }
        }
    }

    rm_fs()->add_action( 'after_uninstall', __NAMESPACE__ . '\\uninstall' );
    /**
     * Begins execution of the plugin.
     *
     * Since everything within the plugin is registered via hooks,
     * then kicking off the plugin from this point in the file does
     * not affect the page life cycle.
     *
     * @since    1.0.0
     */
    require_once plugin_dir_path( __FILE__ ) . 'includes/main.php';
    function run_recipe_master() {
        $plugin = new Main();
        $plugin->run();
    }

    run_recipe_master();
}