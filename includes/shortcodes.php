<?php
namespace Recipe_Master;
class Shortcodes {

    private $options;

	// shortcode helpers
	function get_post_id_shortcode($atts = []){
		//return id if id att passed in
		//or id for post if name passed in
		//or id for post with current page name if nothing passed in
		if ($atts["id"]>0){
			return absint($atts['id']);
		}
		if ($atts["name"] != ""){
			return absint($this->get_post_id_by_title($atts['name']));
		}
		return absint($this->get_post_id_by_title(get_the_title()));
	}
	// Function to get WordPress post ID given the post title
	function get_post_id_by_title( string $title  ): int {
		$posts = get_posts(
			array(
				'post_type' => 'rcpmst_recipe_comp',
				'title' => $title,
				'numberposts' => 1,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				'orderby'                => 'post_date ID',
				'order'                  => 'ASC',
				'fields'                 => 'ids'
			)
		);
		return empty( $posts ) ? false : $posts[0];
	}
	function prepare_atts(&$atts = []){
		$atts = array_change_key_case( (array) $atts, CASE_LOWER ); 
		$atts = shortcode_atts(array(
			'title' => 'false',
			'title-separator' => '',
			'name' => '',
			'id' => 0,
			'may-contain' => 'false',
			'notices' => 'false',	
			'container' => 'div',
		), $atts);
	}
	function get_recipe_component_for_shortcode($atts){
		$postID = $this->get_post_id_shortcode($atts);
		$rc = new Recipe_Component();
		$rc->populate($postID);
		return $rc;
	}
	function process_shortcode(&$atts, &$content, $divName, $title, $data, $shortcodeName){
		$o = '<' . $atts['container'] . ' class="' . $divName . '">'; 
		if ($atts['title']!='false'){
			$o .= '<' . $atts['title'].'>' . $title . $atts['title-separator'] . '</'.$atts['title'].'>';
		}
		$o .= $data;
		if ( ! is_null( $content ) ) { 
			// $content here holds everything in between the opening and the closing tags of your shortcode. eg.g [my-shortcode]content[/my-shortcode]. 
			// Depending on what your shortcode supports, you will parse and append the content to your output in different ways. 
			// In this example, we just secure output by executing the_content filter hook on $content. 
			$o .= apply_filters( 'the_content', $content ); 
		}
		if ($atts['notices']!='false'){
			//$o .= $notices;
		}	
		if ($atts['may-contain']!='false'){
			$o .= '<' . $atts['container'] . ' id="may-contain">';
			$o .= get_option( 'rcpmst_settings' )['allergen_contain'];
			$o .= '</' . $atts['container'] . '>';
		}					 
		$o .= '</' . $atts['container'] . '>'; 
		return wp_kses_post($o); 		
	}
	function description_shortcode( $atts = [], $content = null, $tag = '' ) { 
		$this->prepare_atts($atts);
		$data = get_post_field('post_content', $this->get_post_id_shortcode($atts) );
		return $this->process_shortcode($atts, $content, 'rcpmst-description-box', 'Description', $data, $tag);
	}
	function sku_shortcode( $atts = [], $content = null, $tag = '' ) { 
		$this->prepare_atts($atts);
		$data = $this->get_recipe_component_for_shortcode($atts)->get_sku();
		return $this->process_shortcode($atts, $content, 'rcpmst-sku-box', 'SKU', $data, $tag);
	}
	function storage_shortcode( $atts = [], $content = null, $tag = '' ) { 
		$this->prepare_atts($atts);
		$data = $this->get_recipe_component_for_shortcode($atts)->get_storage();
		return $this->process_shortcode($atts, $content, 'rcpmst-storage-box', 'Storage Instructions', $data, $tag);
	}		
	function ingredients_shortcode( $atts = [], $content = null, $tag = '' ) { 
		$this->prepare_atts($atts);
		$data = $this->get_recipe_component_for_shortcode($atts)->get_ingredient_listing();
		return $this->process_shortcode($atts, $content, 'rcpmst-ingredients-box', 'Ingredients', $data, $tag);
  	} 
	function dietary_notes_shortcode( $atts = [], $content = null, $tag = '' ) { 
		$this->prepare_atts($atts);
		$data = $this->get_recipe_component_for_shortcode($atts)->get_dietary_notes_text();
		return $this->process_shortcode($atts, $content, 'rcpmst-dietary-notes-box', 'Dietary Notes', $data, $tag);
	}
	function allergens_shortcode( $atts = [], $content = null, $tag = '' ) { 
		$this->prepare_atts($atts);
		$data = $this->get_recipe_component_for_shortcode($atts)->get_allergens_text();
		return $this->process_shortcode($atts, $content, 'rcpmst-allergens-box', 'Allergens', $data, $tag);		
	}
	function title_shortcode( $atts = [], $content = null, $tag = '' ) { 
		$this->prepare_atts($atts);
		$data = $this->get_recipe_component_for_shortcode($atts)->get_title();
		return $this->process_shortcode($atts, $content, 'rcpmst-title-box', 'Title', $data, $tag);		
	}
	function image_shortcode( $atts = [], $content = null, $tag = '' ) { 
		$this->prepare_atts($atts);
		global $post;
		$o = '';
		if (has_post_thumbnail( $atts["id"] ) ): 
			$image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'single-post-thumbnail' ); 
			$o = '<div class="rcpmst-image-box">'; 
  			$o .= '<img src="' . esc_url($image[0]) . '"/>';
			$o .= '</div>';
		endif;
		return $o;
	}		
	function shortcodes_init() { 
		add_shortcode( 'rcpmst_ingredients', [$this,'ingredients_shortcode'] ); 
		add_shortcode( 'rcpmst_description', [$this,'description_shortcode' ]); 
		add_shortcode( 'rcpmst_allergens', [$this,'allergens_shortcode' ]); 
		add_shortcode( 'rcpmst_dietary_notes', [$this,'dietary_notes_shortcode'] ); 
		add_shortcode( 'rcpmst_storage', [$this,'storage_shortcode'] ); 
		add_shortcode( 'rcpmst_sku', [$this,'sku_shortcode'] ); 
		add_shortcode( 'rcpmst_title', [$this,'title_shortcode'] ); 
		add_shortcode( 'rcpmst_image', [$this,'image_shortcode'] ); 
	}    
}

