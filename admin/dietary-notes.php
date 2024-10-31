<?php
namespace Recipe_Master;

class Dietary_Notes {   

    function dietary_notes_taxonomy_custom_fields_add() {
        ?>
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="display_name">Display Name</label>
            </th>
            <td>
                <input type="text" name="dietary_notes_display_name" id="dietary_notes_display_name" size="25" style="width:60%;"><br />
                <span class="description">The text to display if this dietary note applies to a recipe</span>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="negative_name">Negative Name</label>
            </th>
            <td>
                <input type="text" name="dietary_notes_negative_name" id="dietary_notes_negative_name" size="25" style="width:60%;"><br />
                <span class="description">The text to display if this dietary note does not apply to a recipe</span>
            </td>
        </tr>        
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="any_vs_all">Any or All?</label>
            </th>
            <td>
				<input type="radio" name="dietary_notes_any_vs_all" id="any" value="any"> Any<br />
				<input type="radio" name="dietary_notes_any_vs_all" id="all" value="all"> All<br />
                <span class="description">Include in Dietary Notes if ANY values are set, or if ALL values are set</span>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="enabled">Enabled</label>
            </th>
            <td>
				<input type="checkbox" name="dietary_notes_enabled" id="dietary_notes_enabled" value="enabled" checked> 
                <span class="description">Whether the dietary note is included when shortcodes are used</span>
            </td>
        </tr>		

        <?php
    }

    function dietary_notes_taxonomy_custom_fields_edit( $term ) {
        $display = get_term_meta( $term->term_id, 'dietary_notes_display_name', true );
        $negative = get_term_meta( $term->term_id, 'dietary_notes_negative_name', true );
		$any_vs_all = get_term_meta( $term->term_id, 'dietary_notes_any_vs_all', true );
        $enabled = get_term_meta( $term->term_id, 'dietary_notes_enabled', true );

		?>
        <tr class="form-field">

            <th scope="row" valign="top">
                <label for="display_name">Display Name</label>
            </th>
            <td>
                <input type="text" name="dietary_notes_display_name" id="dietary_notes_display_name" size="25" style="width:60%;"  value="<?php echo esc_attr($display) ?>"><br />
                <span class="description">The text to display if this dietary note applies to a recipe</span>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="negative_name">Negative Name</label>
            </th>
            <td>
                <input type="text" name="dietary_notes_negative_name" id="dietary_notes_negative_name" size="25" style="width:60%;"  value="<?php echo esc_attr($negative) ?>"><br />
                <span class="description">The text to display if this dietary note does not apply to a recipe</span>
            </td>
        </tr>                
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="any_vs_all">Any or All?</label>
            </th>
            <td>
				<input type="radio" name="dietary_notes_any_vs_all" id="any" value="any" <?php checked( $any_vs_all, 'any' ); ?>> Any<br />
				<input type="radio" name="dietary_notes_any_vs_all" id="all" value="all" <?php checked( $any_vs_all, 'all' ); ?>> All<br />
                <span class="description">Include in Dietary Notes if ANY values are set, or if ALL values are set</span>
            </td>
        </tr>		
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="enabled">Enabled</label>
            </th>
            <td>
				<input type="checkbox" name="dietary_notes_enabled" id="dietary_notes_enabled" value="enabled" <?php checked( $enabled, 'enabled' ); ?>> 
                <span class="description">Whether the dietary note is included when shortcodes are used</span>
            </td>
        </tr>	        	
        <?php
    }


    function dietary_notes_save_term_fields( $term_id ) {
		// no custom nonce - comes from wordpress form
        if(isset($_POST[ 'dietary_notes_display_name' ])){
            update_term_meta($term_id,'dietary_notes_display_name', sanitize_text_field(wp_unslash($_POST[ 'dietary_notes_display_name' ] )));
        }
        if(isset($_POST[ 'dietary_notes_negative_name' ])){
            update_term_meta($term_id,'dietary_notes_negative_name', sanitize_text_field(wp_unslash( $_POST[ 'dietary_notes_negative_name' ] )));
        }
        if(isset($_POST[ 'dietary_notes_any_vs_all' ])){
            update_term_meta($term_id,'dietary_notes_any_vs_all', sanitize_text_field(wp_unslash( $_POST[ 'dietary_notes_any_vs_all' ] )));
        }
        if(isset($_POST[ 'dietary_notes_enabled' ])){
            update_term_meta($term_id,'dietary_notes_enabled', sanitize_text_field(wp_unslash( $_POST[ 'dietary_notes_enabled' ] )));
        }
    }
	// to be run on activation only & only if none there yet
	function populate_dietary_notes(){
		$arr = get_terms(array(
			'taxonomy' => 'rcpmst_dietary_notes',
			'hide_empty' => false,
		));
		if(sizeof($arr) == 0){
			$this->insert_dietary_note('Vegetarian', 'Vegetarian','Not Vegetarian','all','enabled' );
			$this->insert_dietary_note('Vegan', 'Vegan','Not Vegan','all','enabled' );
			$this->insert_dietary_note('Nut Free', 'Nut Free','Contains Nuts','all','enabled' );
			$this->insert_dietary_note('Dairy Free', 'Dairy Free','Contains Dairy','all','enabled' );
			$this->insert_dietary_note('Gluten Free', 'Cotains No Ingredients With Gluten','Contains Gluten','all','enabled' );
			$this->insert_dietary_note('Egg Free', 'Egg Free','Contains Egg','all','enabled' );
			$this->insert_dietary_note('Soy Free', 'Soy Free','Contains Soy','all','enabled' );
			$this->insert_dietary_note('Contains Alcohol', 'Contains Alcohol','Alcohol Free','any','enabled' );
			$this->insert_dietary_note('Kosher', 'Kosher','Not Kosher','all','' );
			$this->insert_dietary_note('Not Halal', 'Not Halal','Halal','any','' );
		}
	}
	function insert_dietary_note($term, $displayName,$negativeName,$anyAll,$enabled){
		$newTerm;
        if (!term_exists($term, 'rcpmst_dietary_notes')){
            $newTerm = wp_insert_term($term,'rcpmst_dietary_notes');
            update_term_meta($newTerm['term_id'], 'dietary_notes_display_name',$displayName);
            update_term_meta($newTerm['term_id'], 'dietary_notes_negative_name',$negativeName);
            update_term_meta($newTerm['term_id'], 'dietary_notes_any_vs_all',$anyAll);
            update_term_meta($newTerm['term_id'], 'dietary_notes_enabled',$enabled);
        }
	}
}
?>