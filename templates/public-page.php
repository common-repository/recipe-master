<?php /* Template Name: recipe-master-public-page */
namespace Recipe_Master;
if (current_user_can("read_recipe_components")) {
	get_header();
  ?>
  <div id="primary" class="content-area">
    <?php
    $current_page = get_queried_object();
    $content = apply_filters( 'the_content', $current_page->post_content );
    if(str_contains($current_page->post_content, "rcpmst_checklist")){
      //custom kses for checklist
      $tags = array(
        'h2' => array(),
        'table' => array(),
        'tr' => array(),
        'td' => array(),
        'th' => array(),
        'br' => array(),
        'form' => array(
          'id' => array(),
          'name' => array(),
          'method' => array(),
          'action' => array(),
        ),
        'input' => array(
          'id' => array(),
          'name' => array(),
          'type' => array(),
          'step' => array(),
          'style' => array(),
          'value' => array(),
        ),
      );
      $tags = array_merge(wp_kses_allowed_html('post'), $tags);
      echo wp_kses($content, $tags);
    }else{
      echo wp_kses_post($content);
    }
    ?>
  </div>
	</body>
	</html>
	<?php 
} else {
	wp_redirect(esc_url(wp_login_url() . "?redirect_to=" . home_url( $wp->request )));
}
?>