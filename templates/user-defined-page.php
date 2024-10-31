<?php /* Template Name: recipe-master-user-defined-page */
namespace Recipe_Master;
if (current_user_can("read_recipe_components")) {
	get_header();
  while (have_posts()): 

    the_post();
    //get_header();
    ?>
    <div id="primary" class="content-area">
        <?php
        the_content();
        ?>
    </div>
    </body>
    </html>
    <?php 
    endwhile;?>
	</body>
	</html>
	<?php 
} else {
	wp_redirect(esc_url(wp_login_url() . "?redirect_to=" . home_url( $wp->request )));
}
?>



