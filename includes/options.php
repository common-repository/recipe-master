<?php
namespace Recipe_Master;
class Options {

    private $options;

    public function __construct(  ) {
        if (false === get_option('rcpmst-plugin-settings')) {
            $this->initialize();
        }
        $this->options = get_option('rcpmst-plugin-settings');
	}

    function initialize() {
        $options = [
            'labour_rate' => 15,
            'allergen_statement' => '',
			'drops_per_g' => 20,
			'currency_symbol' => '£',
            'font_size' => 13,
            'debug' => 'no',
            'max_depth' => 10,
            'checklist' => [],
			'checklist_reminder' => 'no',
        ];
        update_option('rcpmst-plugin-settings', $options);
    }

    function get_options(){
        return $this->options;
    }
	function rcpmst_add_options_link() {
		$page = add_options_page('Recipe Master Options', 'Recipe Master', 'manage_options', 'rcpmst-options', [$this,'rcpmst_options_page']);
	}
    
	public function enqueue_scripts($hook) {		
		if ( 'settings_page_rcpmst-options' != $hook ) {
        	return;
    	}
        wp_enqueue_script( WP_RCPMST__PLUGIN_NAME . '-settings', plugin_dir_url( __FILE__ ) . '../admin/js/settings.js', array( 'jquery' ), WP_RCPMST__VERSION, false );
    }
	function rcpmst_register_settings() {
		register_setting('rcpmst-options-general', 'rcpmst-plugin-settings', [$this,'validation_cb']);
        add_settings_section('rcpmst_general_section', 'General Settings',[$this,'rcpmst_general_section_cb'],'rcpmst-options-general');
        add_settings_section('rcpmst_ingredient_section', 'Ingredient Settings',[$this,'rcpmst_ingredient_section_cb'],'rcpmst-options-general');
        add_settings_field('rcpmst_labour_rate','Labour Rate',[$this,'rcpmst_labour_rate_cb'],'rcpmst-options-general','rcpmst_general_section');
		add_settings_field('rcpmst_currency_symbol','Currency Symbol',[$this,'rcpmst_currency_symbol_cb'],'rcpmst-options-general','rcpmst_general_section');
		add_settings_field('rcpmst_drops_per_g','Drops per Gram',[$this,'rcpmst_drops_per_g_cb'],'rcpmst-options-general','rcpmst_ingredient_section');
		add_settings_field('rcpmst_allergen_statement','Allergen Statement',[$this,'rcpmst_allergen_statement_cb'],'rcpmst-options-general','rcpmst_ingredient_section');
        
		register_setting('rcpmst-options-checklist', 'rcpmst-plugin-settings', [$this,'validation_cb']);
		add_settings_section('rcpmst_checklist_section', 'Daily Checklist',[$this,'rcpmst_checklist_section_cb'],'rcpmst-options-checklist');
		add_settings_field('rcpmst_checklist','Checklist',[$this,'rcpmst_checklist_cb'],'rcpmst-options-checklist','rcpmst_checklist_section');
		add_settings_field('rcpmst_checklist_reminder','Checklist Reminder',[$this,'rcpmst_checklist_reminder_cb'],'rcpmst-options-checklist','rcpmst_checklist_section');
		
		register_setting('rcpmst-options-technical', 'rcpmst-plugin-settings', [$this,'validation_cb']);
		add_settings_section('rcpmst_technical_section', 'Technical Settings',[$this,'rcpmst_technical_section_cb'],'rcpmst-options-technical');
        add_settings_section('rcpmst_debug_section', 'Debug',[$this,'rcpmst_debug_section_cb'],'rcpmst-options-technical');
        add_settings_field('rcpmst_font_size','Font Size',[$this,'rcpmst_font_size_cb'],'rcpmst-options-technical','rcpmst_technical_section');
		add_settings_field('rcpmst_debug','Debug',[$this,'rcpmst_debug_cb'],'rcpmst-options-technical','rcpmst_debug_section');
	    add_settings_field('rcpmst_max_depth','Max Depth',[$this,'rcpmst_max_depth_cb'],'rcpmst-options-technical','rcpmst_technical_section');
        
	}
	function validation_cb($input){
		$input = array_merge($this->options, $input);
		
		if(!is_numeric($input['labour_rate'])){
			$input['labour_rate'] = 10;
			add_settings_error("rcpmst_labour_rate","settings_update","Labour rate must be a number - reset to 10","error");
		}
		if(!is_numeric($input['max_depth'])){
			$input['max_depth'] = 10;
			add_settings_error("rcpmst_max_depth","settings_update","Max Depth must be a number - reset to 10","error");
		}        
		if(!is_numeric($input['drops_per_g'])){
			$input['drops_per_g'] = 20;
			add_settings_error("rcpmst_drops_per_g","settings_update","Drops per Gram must be a number - reset to 20","error");
		}
		if(!is_numeric($input['font_size'])){
			$input['font_size'] = 13;
			add_settings_error("rcpmst_font_size","settings_update","Font Size must be a number - reset to 13","error");
		}        
		$arr = array('b'=>array(), 'strong'=>array());
		$input['allergen_statement'] = wp_kses($input['allergen_statement'],$arr);		
		$input['currency_symbol'] = sanitize_text_field($input['currency_symbol']);		

        //sort and validate checklist
		$input['checklist'] = array_filter($input['checklist'], function($value){
				return $value["description"] != "";
    		},);
		array_multisort(array_column($input['checklist'], 'position'), $input['checklist']);
		$valid_strings = ['Text', 'Number', 'Checkbox'];
		foreach($input['checklist'] as &$checkItem){
			$checkItem['description'] = sanitize_text_field($checkItem['description']);	
			if(! in_array( $checkItem['type'], $valid_strings, true )){
				add_settings_error("rcpmst_checklist_type","settings_update","Invalid checklist data type found","error");
				exit; //something v wrong so...
			} 
		}

		return $input;
	}
    function rcpmst_general_section_cb (){
        ?>
        For help and documentation please check our <a href="<?php echo esc_url(WP_RCPMST__DOC_URL . 'wiki') ?>" target="new">wiki</a>
        <?php
    }
    function rcpmst_ingredient_section_cb(){
    }
    function rcpmst_technical_section_cb(){
    }
    function rcpmst_checklist_section_cb(){
    }    
    function rcpmst_debug_section_cb(){
    }
    function rcpmst_labour_rate_cb(){
        ?>
        <input id="labour_rate" name="rcpmst-plugin-settings[labour_rate]" type="number" step="any" value="<?php echo esc_attr($this->options['labour_rate']); ?>"/>
        <?php
    }
	function rcpmst_currency_symbol_cb(){
        ?>
        <input id="currency_symbol" name="rcpmst-plugin-settings[currency_symbol]" type="text" value="<?php echo esc_attr($this->options['currency_symbol']); ?>"/>
        <?php
    }
	function rcpmst_drops_per_g_cb(){
        ?>
        <input id="drops_per_g" name="rcpmst-plugin-settings[drops_per_g]" type="number" step="any" value="<?php echo esc_attr($this->options['drops_per_g']); ?>"/>
        <?php
    }	
	function rcpmst_allergen_statement_cb(){
        ?>
        <input id="allergen_statement" name="rcpmst-plugin-settings[allergen_statement]" type="text" value="<?php echo wp_kses_post($this->options['allergen_statement']); ?>"/>
        <?php
	}
	function rcpmst_font_size_cb(){
        ?>
        <input id="font_size" name="rcpmst-plugin-settings[font_size]" type="number" step="any" value="<?php echo esc_attr($this->options['font_size']); ?>"/>
        <?php
    }    
	function rcpmst_debug_cb(){
		$debug = $this->options['debug'];
		?>
		Yes <input type="radio" name="rcpmst-plugin-settings[debug]" id="yes" value="yes" <?php checked($debug,'yes'); ?>></br>
		No <input type="radio" name="rcpmst-plugin-settings[debug]" id="no" value="no" <?php checked($debug,'no'); ?>>
		<?php
	}
	function rcpmst_max_depth_cb(){
        ?>
        <input id="max_depth" name="rcpmst-plugin-settings[max_depth]" type="number" step="any" value="<?php echo esc_attr($this->options['max_depth']); ?>"/>
        <?php
    }
	function rcpmst_checklist_reminder_cb(){
		$reminder = $this->options['checklist_reminder'];
		?>
		Yes <input type="radio" name="rcpmst-plugin-settings[checklist_reminder]" id="yes" value="yes" <?php checked($reminder,'yes'); ?>></br>
		No <input type="radio" name="rcpmst-plugin-settings[checklist_reminder]" id="no" value="no" <?php checked($reminder,'no'); ?>>
		<?php
	}
	function rcpmst_checklist_cb(){
		$list = $this->options['checklist'];
		?>
		<div id="checklist_list">
			<p style="display:none">
				<label>Description</label>
				<input type="text" name="rcpmst-plugin-settings[checklist][0][description]" id="rcpmst-plugin-settings[checklist][0][description]" value="">
				<label>Data Type</label>
				<select name="rcpmst-plugin-settings[checklist][0][type]" id="rcpmst-plugin-settings[checklist][0][type]">
					<option value="Text">Text</option>
                    <option value="Number">Number</option>
                    <option value="Checkbox">Checkbox</option>
                </select>
				<label>Position</label><input type="number" id="rcpmst-plugin-settings[checklist][0][position]">
				<input type="button" id="btn_remove_checklist_0" value="-" onClick="checklistRemove(this)">
			</p>
			<?php
			$listIndex = 1;
			if($list): 
				foreach($list as $listItem):
					if (isset($listItem["description"])){
						if (!($listItem["description"]=="")):
							?>
							<p>
							<label>Description</label>
							<input type="text" name="rcpmst-plugin-settings[checklist][<?php echo esc_html($listIndex)?>][description]" id="rcpmst-plugin-settings[checklist][<?php echo esc_html($listIndex)?>][description]" value="<?php echo esc_html($listItem['description'])?>">
							<label>Data Type</label>
							<input type="text" name="rcpmst-plugin-settings[checklist][<?php echo esc_html($listIndex)?>][type]" id="rcpmst-plugin-settings[checklist][<?php echo esc_html($listIndex)?>][type]" value="<?php echo esc_html($listItem['type'])?>" size="10">
							<label>Position</label>
							<input type="number" name="rcpmst-plugin-settings[checklist][<?php echo esc_html($listIndex)?>][position]" id="rcpmst-plugin-settings[checklist][<?php echo esc_html($listIndex)?>][position]" value="<?php echo esc_html($listIndex)?>">
							<input type="button" id="btn_remove_checklist_<?php echo esc_html($listIndex)?>" value="-" onClick="checklistRemove(this)">
							<?php $listIndex++; ?>
							</p>
							<?php 
						endif;
					}
				endforeach; 
			endif;
			?>
			<p>
			<label>Description</label>
			<input type="text" name="rcpmst-plugin-settings[checklist][<?php echo esc_html($listIndex)?>][description]" id="rcpmst-plugin-settings[checklist][<?php echo esc_html($listIndex)?>][description]">
			<label>Data Type</label>
            <select name="rcpmst-plugin-settings[checklist][<?php echo esc_html($listIndex)?>][type]" id="rcpmst-plugin-settings[checklist][<?php echo esc_html($listIndex)?>][type]">
                <option value="Text">Text</option>
                <option value="Number">Number</option>
                <option value="Checkbox">Checkbox</option>
            </select>   
			<label>Position</label>
			<input type="number" name="rcpmst-plugin-settings[checklist][<?php echo esc_html($listIndex)?>][position]" id="rcpmst-plugin-settings[checklist][<?php echo esc_html($listIndex)?>][position]" value="<?php echo esc_html($listIndex)?>">				     
			<input type="button" id="btn_remove_checklist_<?php echo esc_html($listIndex)?>" value="-" onClick="checklistRemove(this)" disabled>
			</p>
		</div>
		<p id="p_add_checklist"><input type="button" id="btn_add_checklist" value="+" onClick="checklistAdd()"></p>    
		<?php
    }    	    
	function rcpmst_options_page() {
        ?>
        <div class="wrap">
            <h2>Recipe Master Options</h2>
            <form method="post" action="options.php">
            <?php 
			$tabs = array(
				'general' => 'General Settings',
				'checklist' => 'Checklist',	
				'technical' => 'Technical Settings',	
			); 
			$current_tab = isset( $_GET[ 'tab' ] ) && isset( $tabs[ $_GET[ 'tab' ] ] ) ? wp_unslash($_GET[ 'tab' ]) : array_key_first( $tabs );
			?>
			<nav class="nav-tab-wrapper">
				<?php
				foreach( $tabs as $tab => $name ){
					$current = $tab === $current_tab ? ' nav-tab-active' : '';
					$url = add_query_arg( array( 'page' => 'rcpmst-options', 'tab' => $tab ), '' );
					echo wp_kses_post("<a class=\"nav-tab{$current}\" href=\"{$url}\">{$name}</a>");
				}
				?>
			</nav>
			<?php
				settings_fields( "rcpmst-options-" . $current_tab );
				do_settings_sections( "rcpmst-options-" . $current_tab );
				submit_button();
		       	if($this->options['debug'] == "yes"){
					echo wp_kses_post((print_r($this->options,true)));
				}
			?>
			</form>
		</div>
    	<?php 
    } 
}