<?php

namespace Recipe_Master;

// public facing shortcodes for public pages
class Public_Content {
    public static $checklistDateFormat = 'jS M Y';

    public function enqueue_styles() {
        wp_enqueue_style(
            WP_RCPMST__PLUGIN_NAME . '-publicstyles',
            plugin_dir_url( __FILE__ ) . 'css/public.css',
            array(),
            WP_RCPMST__VERSION,
            'all'
        );
        if ( is_singular( 'rcpmst_recipe_comp' ) ) {
            wp_enqueue_style(
                WP_RCPMST__PLUGIN_NAME . '-singlestyles',
                plugin_dir_url( __FILE__ ) . '../templates/css/single-rcpmst_recipe_comp.css',
                array(),
                WP_RCPMST__VERSION,
                false
            );
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script(
            WP_RCPMST__PLUGIN_NAME . '-publicscripts',
            plugin_dir_url( __FILE__ ) . 'js/public.js',
            array('jquery'),
            WP_RCPMST__VERSION,
            false
        );
        if ( is_singular( 'rcpmst_recipe_comp' ) ) {
            wp_enqueue_script(
                WP_RCPMST__PLUGIN_NAME . '-singlescripts',
                plugin_dir_url( __FILE__ ) . '../templates/js/single-rcpmst_recipe_comp.js',
                array('jquery'),
                WP_RCPMST__VERSION,
                false
            );
        }
    }

    static function top_menu() {
        $html = "";
        if ( get_option( 'rcpmst-plugin-settings' )['checklist_reminder'] == "yes" ) {
            //check if there is a checklist entry for today
            $date = new \DateTime();
            $dateString = $date->format( self::$checklistDateFormat );
            $pages = get_posts( [
                'title'     => $dateString,
                'post_type' => 'rcpmst_checklist',
            ] );
            if ( !isset( $pages[0] ) ) {
                $html .= "<div style='border:1px black solid;background-color:red'>";
                $html .= "<a href='/recipe-master-checklist/'>Reminder - Daily checklist for today is not yet complete</a>";
                $html .= "</div>";
            }
        }
        $html .= "<div style='border:1px black solid;background-color:ghostwhite'>";
        $html .= "<img src='" . esc_url( plugin_dir_url( __FILE__ ) ) . "images/RMLogo.png' width='100' height='100'>";
        $html .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='" . esc_url( get_site_url() ) . "/recipe-master-menu/'>Main Menu</a>";
        $html .= " - <a href='" . esc_url( get_site_url() ) . "/recipe-master-recipe-type-list/'>By Type</a>";
        $html .= " - <a href='" . esc_url( get_site_url() ) . "/recipe-master-selection-list/'>By Selection</a>";
        $html .= " - <a href='" . esc_url( get_site_url() ) . "/recipe-master-checklist/'>Daily Checklist</a>";
        $html .= " - <a href='" . esc_url( get_site_url() ) . "/recipe-master-user-defined/'>My Files</a>";
        $html .= " - <a href='" . esc_url( get_site_url() ) . "/wp-admin/edit.php?post_type=rcpmst_recipe_comp'>Administer</a>";
        $html .= "</div>";
        return $html;
    }

    function recipe_list_shortcode( $atts = [], $content = null, $tag = '' ) {
        // normalize attribute keys, lowercase
        $atts = array_change_key_case( (array) $atts, CASE_LOWER );
        $taxonomy = ( isset( $_GET['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) : "" );
        $value = ( isset( $_GET['value'] ) ? sanitize_text_field( wp_unslash( $_GET['value'] ) ) : "" );
        $args = array(
            'post_type'      => 'rcpmst_recipe_comp',
            'post_status'    => 'publish',
            'order'          => 'ASC',
            'posts_per_page' => -1,
            'meta_key'       => 'rcpmst_ingredient_or_recipe_meta',
            'meta_value'     => 'Recipe',
        );
        if ( $taxonomy != "" ) {
            $taxArg = array(
                'tax_query' => array(array(
                    'taxonomy' => $taxonomy,
                    'terms'    => $value,
                    'field'    => 'name',
                )),
            );
            $args = array_merge( $args, $taxArg );
        }
        $the_query = new \WP_Query($args);
        $o = self::top_menu();
        $o .= '<div class="rcpmst-recipe-list-box"><table>';
        if ( $the_query->have_posts() ) {
            while ( $the_query->have_posts() ) {
                $the_query->the_post();
                $o .= "<tr><td>";
                $o .= "<a href='../rcpmst_recipe_comp/" . esc_html( get_post_field( 'post_name' ) ) . "'>";
                $o .= esc_html( get_the_title() );
                $o .= "</a>";
                $o .= "<td><a href='../rcpmst_recipe_comp/" . esc_html( get_post_field( 'post_name' ) ) . "'>Show Recipe</td>";
                //$o .= "<td>Labels</td>";
                $o .= "</tr>";
            }
        } else {
            $o .= "no selections found";
        }
        $o .= '</table>';
        wp_reset_postdata();
        // enclosing tags
        if ( !is_null( $content ) ) {
            // $content here holds everything in between the opening and the closing tags of your shortcode. eg.g [my-shortcode]content[/my-shortcode].
            // Depending on what your shortcode supports, you will parse and append the content to your output in different ways.
            // In this example, we just secure output by executing the_content filter hook on $content.
            $o .= apply_filters( 'the_content', $content );
        }
        $o .= '</div>';
        return $o;
    }

    function menu_shortcode( $atts = [], $content = null, $tag = '' ) {
        $o = self::top_menu();
        $o .= "<div id='rcpmst-menu'>";
        //$o .= get_header();
        $o .= "<h1>Recipe Master Main Menu</h1>";
        $o .= "<h3><a href='../recipe-master-recipe-list'>All Recipes</a></h3>";
        $o .= "<h3><a href='../recipe-master-recipe-type-list'>Recipes by Recipe Type</a></h3>";
        $o .= "<h3><a href='../recipe-master-selection-list'>Recipes by Selection</a></h3>";
        $o .= "<h3><a href='../wp-admin/edit.php?post_type=rcpmst_recipe_comp'>Administer</a></h3>";
        if ( !is_null( $content ) ) {
            $o .= apply_filters( 'the_content', $content );
        }
        $o .= '</div>';
        return $o;
    }

    function taxonomy_ingredients_shortcode( $atts = [], $content = null, $tag = '' ) {
        $atts = array_change_key_case( (array) $atts, CASE_LOWER );
        $taxonomy = ( isset( $_GET['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) : "" );
        $value = ( isset( $_GET['value'] ) ? sanitize_text_field( wp_unslash( $_GET['value'] ) ) : "" );
        $args = array(
            'post_type'      => 'rcpmst_recipe_comp',
            'post_status'    => 'publish',
            'order'          => 'ASC',
            'posts_per_page' => -1,
            'tax_query'      => array(array(
                'taxonomy' => $taxonomy,
                'terms'    => $value,
                'field'    => 'name',
            )),
        );
        $the_query = new \WP_Query($args);
        $o = self::top_menu();
        if ( $the_query->have_posts() ) {
            $o .= "<div class='rcpmst_ingredient_label'>";
            $o .= "<table class='rcpmst_ingredient_label'>";
            while ( $the_query->have_posts() ) {
                $the_query->the_post();
                $o .= "<tr><td>" . esc_html( get_the_post_thumbnail() ) . "</td>";
                $o .= "<td><div class='rcpmst-title'>" . esc_html( get_the_title() ) . " - </div>";
                $o .= "<div class='rcpmst-ingredients'>[rcpmst_ingredients parabreaks='false' id='" . esc_html( get_the_ID() ) . "']</div></td></tr>";
                $o = do_shortcode( $o );
            }
            $o .= "</table></div>";
        } else {
            $o .= "no selections found";
        }
        wp_reset_postdata();
        if ( !is_null( $content ) ) {
            $o .= apply_filters( 'the_content', $content );
        }
        return $o;
    }

    function taxonomy_list_shortcode( $atts = [], $content = null, $tag = '' ) {
        $atts = array_change_key_case( (array) $atts, CASE_LOWER );
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
        $terms = get_terms( [
            'taxonomy'   => $atts['taxonomy'],
            'hide_empty' => false,
        ] );
        $o = self::top_menu();
        $o .= '<div class="rcpmst-taxonomy-list-box"><table>';
        foreach ( $terms as $term ) {
            $o .= "<tr><td>";
            $o .= "<a href='../recipe-master-recipe-list?taxonomy=" . esc_html( $atts['taxonomy'] ) . "&value=" . esc_html( $term->name ) . "'>";
            $o .= esc_html( $term->name ) . "</a></td>";
            $o .= "<td><a href='../recipe-master-recipe-list?taxonomy=" . esc_html( $atts['taxonomy'] ) . "&value=" . esc_html( $term->name ) . "'>";
            $o .= "Show Recipes" . "</a></td>";
            if ( is_plugin_active( 'label-maker/label-maker.php' ) ) {
                $o .= "<td><a href='../recipe-master-labels?taxonomy=" . esc_html( $atts['taxonomy'] ) . "&value=" . esc_html( $term->name ) . "'>";
                $o .= "Labels" . "</a></td>";
            }
            $o .= "</tr>";
        }
        if ( !is_null( $content ) ) {
            $o .= apply_filters( 'the_content', $content );
        }
        $o .= '</table></div>';
        return $o;
    }

    function user_defined_files_shortcode( $atts = [], $content = null, $tag = '' ) {
        $o = self::top_menu();
        $o .= "<div id='rcpmst-menu'>";
        $args = array(
            'post_type'      => array('page'),
            'order'          => 'ASC',
            'orderby'        => 'title',
            'posts_per_page' => -1,
            'meta_query'     => array(array(
                'key'   => '_wp_page_template',
                'value' => 'recipe-master-user-defined-page.php',
            )),
        );
        $the_query = new \WP_Query($args);
        if ( $the_query->have_posts() ) {
            while ( $the_query->have_posts() ) {
                $the_query->the_post();
                $o .= '<p><a href="' . esc_url( get_the_permalink() ) . '">';
                $o .= esc_html( get_the_title() ) . '</a></p>';
            }
            wp_reset_postdata();
        }
        if ( !is_null( $content ) ) {
            $o .= apply_filters( 'the_content', $content );
        }
        $o .= '</div>';
        return $o;
    }

    function validate_checklist( $postFields ) {
        $safePost = [];
        $valueIndex = 0;
        while ( isset( $_POST['description_' . $valueIndex] ) ) {
            $safePost['description_' . $valueIndex] = sanitize_text_field( ( isset( $_POST['description_' . $valueIndex] ) ? wp_unslash( $_POST['description_' . $valueIndex] ) : "" ) );
            $safePost['value_' . $valueIndex] = sanitize_text_field( ( isset( $_POST['value_' . $valueIndex] ) ? wp_unslash( $_POST['value_' . $valueIndex] ) : "" ) );
            $safePost['comment_' . $valueIndex] = sanitize_text_field( ( isset( $_POST['comment_' . $valueIndex] ) ? wp_unslash( $_POST['comment_' . $valueIndex] ) : "" ) );
            $valueIndex++;
        }
        return $safePost;
    }

    function checklist_shortcode( $atts = [], $content = null, $tag = '' ) {
        $o = "<div id='rcpmst-checklist'>";
        $date = new \DateTime();
        $o .= "<h2>Daily Checklist for " . esc_html( $date->format( self::$checklistDateFormat ) ) . "</h2>";
        if ( 'POST' == $_SERVER['REQUEST_METHOD'] && !empty( $_POST['action'] ) && $_POST['action'] == "new_checklist_entry" ) {
            // Do some minor form validation to make sure there is content
            $title = $date->format( self::$checklistDateFormat );
            $submittedValues = "";
            $metaValues = [];
            $valueIndex = 0;
            $nonce = sanitize_key( ( isset( $_REQUEST['_wpnonce'] ) ? wp_unslash( $_REQUEST['_wpnonce'] ) : "" ) );
            if ( !wp_verify_nonce( $nonce, 'new_checklist_entry' ) ) {
                die( 'Nonce Failed' );
            }
            $safePost = $this->validate_checklist( $_POST );
            $submittedValues .= "<table>";
            while ( !empty( $safePost['description_' . $valueIndex] ) ) {
                $submittedValues .= "<tr>";
                $submittedValues .= "<td>" . $safePost['description_' . $valueIndex] . "</td>";
                $submittedValues .= "<td>" . $safePost['value_' . $valueIndex] . "</td>";
                $submittedValues .= "<td>" . $safePost['comment_' . $valueIndex] . "</td>";
                $submittedValues .= "</tr>";
                $metaValues[$valueIndex] = [
                    'description' => $safePost['description_' . $valueIndex],
                    'value'       => $safePost['value_' . $valueIndex],
                    'comment'     => $safePost['comment_' . $valueIndex],
                ];
                $valueIndex++;
            }
            $submittedValues .= "</table>";
            $new_post = array(
                'post_title'  => $title,
                'post_status' => 'publish',
                'post_type'   => 'rcpmst_checklist',
            );
            $pid = wp_insert_post( $new_post );
            update_post_meta( $pid, "rcpmst_checklist_meta", $metaValues );
            $o .= "Thanks - the following values have been submitted</br>";
            $o .= $submittedValues;
        } else {
            $checklistItems = get_option( 'rcpmst-plugin-settings' )['checklist'];
            $count = 0;
            if ( count( $checklistItems ) > 0 ) {
                $o .= "<form id='new_post' name='new_post' method='post' action=''>";
                $o .= "<table>";
                $o .= "<tr>";
                $o .= "<tr><th>Check</th><th>Value</th><th>Comment</th></tr>";
                foreach ( $checklistItems as $item ) {
                    if ( $item["description"] != "" ) {
                        $o .= "<tr><td>" . esc_html( $item["description"] ) . "</td><td>";
                        $o .= "<input type='hidden' name='description_" . $count . "' value='" . esc_html( $item["description"] ) . "'>";
                        if ( $item["type"] == "Text" ) {
                            $o .= "<input type='text' name='value_" . $count . "' style='border: 1px solid #000;'>";
                        } else {
                            if ( $item["type"] == "Number" ) {
                                $o .= "<input type='number' step='any' name='value_" . $count . "' style='border: 1px solid #000;'>";
                            } else {
                                if ( $item["type"] == "Checkbox" ) {
                                    $o .= "<input type='checkbox' name='value_" . $count . "' style='border: 1px solid #000;'>";
                                }
                            }
                        }
                        $o .= "<td><input type='text' name='comment_" . $count . "' style='border: 1px solid #000;'></td>";
                        $o .= "</td></tr>";
                    }
                    $count++;
                }
                $o .= "</table>";
                $o .= "<input type='hidden' name='fieldcount' value='" . $count . "' />";
                $o .= "<input type='hidden' name='action' value='new_checklist_entry' />";
                $o .= wp_nonce_field( 'new_checklist_entry' );
                $o .= "<input type='submit'>";
                $o .= "</form>";
            }
        }
        if ( !is_null( $content ) ) {
            $o .= apply_filters( 'the_content', $content );
        }
        $o .= '</div>';
        //add top menu ahead of content at end so that it reflects whether a post has been saved for today or not
        return self::top_menu() . $o;
    }

    function public_shortcodes_init() {
        add_shortcode( 'rcpmst_taxonomy_list', [$this, 'taxonomy_list_shortcode'] );
        add_shortcode( 'rcpmst_taxonomy_ingredients', [$this, 'taxonomy_ingredients_shortcode'] );
        add_shortcode( 'rcpmst_recipe_list', [$this, 'recipe_list_shortcode'] );
        add_shortcode( 'rcpmst_menu', [$this, 'menu_shortcode'] );
        add_shortcode( 'rcpmst_user_defined', [$this, 'user_defined_files_shortcode'] );
        add_shortcode( 'rcpmst_checklist', [$this, 'checklist_shortcode'] );
    }

    function recipe_comp_template( $template ) {
        global $post;
        if ( 'rcpmst_recipe_comp' === $post->post_type ) {
            if ( locate_template( array('single-rcpmst_recipe_comp.php') ) == "" ) {
                return plugin_dir_path( __FILE__ ) . '../templates/single-rcpmst_recipe_comp.php';
            }
        }
    }

}
