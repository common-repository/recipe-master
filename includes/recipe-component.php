<?php
namespace Recipe_Master;
class Recipe_Component {

	private $rcPost;
	private $rcId;
	private $ingredientDetails =[];
	private $ingredientStructureLookup = [];
	private $totalWeightIngredients = 0;
	private $totalAlcoholAmount = 0;
	private $productWarnings = [];
	private $productPriceWarnings = []; 
	private $dietaryNotesTerms = [];
	private $currentIngListing;
	private $ingListingRecipeLookup = [];
	//the arrays above are used to construct the ingredient listing
	//ingList in the structure should appear only for top level and compound recipes
	//it should contain all ingredients and recipes (compound and non-compound, gen and manual) for that level
	//as well as any subingredients from lower recipes where that recipe is not manual and not compounded
	private $newPageFlag = false; //used in cases where post-new.php is not enough of a check
	private $depth=0;

/* amounts are stored in selected quantity, 
are converted in gram equiv when loaded into class 
are converted back to original units for display to user */
	// constructor functions
	function populate($id = null){
		
		global $post;

		if($this->is_new_page() ){
			return;
		}

		if ($this->rcPost === NULL){
			$this->dietaryNotesTerms = $this->get_dietary_notes_terms();
			$this->rcPost = $post;
			
			if($id != null ){ //if an id is passed in use that, otherwise assume we are on a recipe component page and use post id
				$this->rcId = $id;
			}else if(is_singular('rcpmst_recipe_comp') || 'rcpmst_recipe_comp' === get_post_type( $post )){				
				$this->rcId = $post->ID;
			}else{ // no id so can't build - return
				return;
			}
			$recipeSlug = get_post_field('post_name', $this->rcId);
			//if previous save was aborted (eg duplicate name) then id may be present but slug will be empty, in which case treat as new
			if ($recipeSlug == ""){
				$this->newPageFlag = true;
				return;
			}
            $ingredients = get_post_meta($this->rcId, "rcpmst_ingredients_meta", true);
            $ingredientSlugs = $this->get_ingredient_slugs($ingredients);

			$val = 1;
			$unit = 'Grams';
			if(is_array(get_post_meta($this->rcId, "rcpmst_amount_meta", true))){
				$val = get_post_meta($this->rcId, "rcpmst_amount_meta", true)["val"];
				$unit = get_post_meta($this->rcId, "rcpmst_amount_meta", true)["unit"];
			}
			$ingListingMeta = get_post_meta($this->rcId, "rcpmst_ingredient_listing_meta", true);
			$this->populate_data_table($this->rcId, get_the_title(), $recipeSlug,
				get_post_meta($this->rcId, "rcpmst_ingredient_or_recipe_meta", true),
				$val,
				$unit,
				get_post_meta($this->rcId, "rcpmst_price_meta", true), 
				get_post_meta($this->rcId, "rcpmst_activities_meta", true), 
				wpautop(get_post_meta($this->rcId, "rcpmst_recipe_meta", true)),
				get_post_meta($this->rcId, "rcpmst_dietary_notes_meta", true)["manual_or_generated"],
				get_the_terms($this->rcId, 'rcpmst_dietary_notes'),
				get_post_meta($this->rcId, "rcpmst_allergens_meta", true)["manual_or_generated"],
				$ingListingMeta["manual_or_generated"],
				$ingListingMeta["manual_listing"],
				get_post_meta($this->rcId, "rcpmst_abv_meta", true), 
				isset($ingListingMeta["compound"]) ? $ingListingMeta["compound"] : "",
				isset($ingListingMeta["alternate_name"]) ? $ingListingMeta["alternate_name"] : "",
				//wpautop(get_post_meta($id, "rcpmst_dietary_notes_meta", true)["manual_listing"]),
			);
			$recipeScaleParent = 1;	
			$this->build_ingredient_structure($ingredients,$recipeSlug, true);
			$this->get_sub_recipe_components($ingredientSlugs);
			$this->currentIngListing = &$this->ingredientStructureLookup[0]["ingListing"];
			$this->post_update($this->ingredientStructureLookup,1,false,true); //sets data in the data structure that also requires info from main table
		}
	}

	// recurse level by level to avoid too many calls
	// start by passing in ingredientids for current product to avoid extra wp_query calls
	function get_sub_recipe_components($recipeComponentSlugs){
		$ingredientSlugs = [];
		//$recipeScale = 1;
		$traverseLower = false;
		$this->depth += 1;

		if($this->depth > get_option( 'rcpmst-plugin-settings' )['max_depth']){
			error_log("Max Depth on recursion hit - possible loop");
			return;
		}
		//bug means don't go ahead if nothing in array or returns everything
        if (count($recipeComponentSlugs) >0){
			$args = array('post_type' => 'rcpmst_recipe_comp',
				'orderby' => 'ASC',
				'posts_per_page'=>-1,
				'post_name__in' => $recipeComponentSlugs,
			);
			$posts = get_posts($args);
			foreach ( $posts as $rcpPost ) {
				//$the_query->the_post();
				$currentId = $rcpPost->ID;
				$ingredients = [];
				$rawIngredients = get_post_meta($currentId, "rcpmst_ingredients_meta", true);
				//clean ingredients array by removing any item without a slug
				foreach($rawIngredients as $i){
					if(isset($i["slug"]) && $i["slug"] != ""){
						array_push($ingredients, $i);
					}
				}
				//collect ids for next traversal
				$ingredientSlugs = array_merge($ingredientSlugs, $this->get_ingredient_slugs($ingredients));
				// will we need to go lower?
				$recipeOrIngredient = get_post_meta($currentId, "rcpmst_ingredient_or_recipe_meta", true);
				if($recipeOrIngredient == "Recipe"){
					$traverseLower = true;
				}	
				$ingListingMeta = get_post_meta($currentId, "rcpmst_ingredient_listing_meta", true);				
				$this->populate_data_table($currentId, $rcpPost->post_title, get_post_field( 'post_name', $currentId ),
					$recipeOrIngredient,
					get_post_meta($currentId, "rcpmst_amount_meta", true)["val"],
					get_post_meta($currentId, "rcpmst_amount_meta", true)["unit"],
					get_post_meta($currentId, "rcpmst_price_meta", true),
					get_post_meta($currentId, "rcpmst_activities_meta", true),
					wpautop(get_post_meta($currentId, "rcpmst_recipe_meta", true)),
					get_post_meta($currentId, "rcpmst_dietary_notes_meta", true)["manual_or_generated"],
					get_the_terms($currentId, 'rcpmst_dietary_notes'),
					get_post_meta($currentId, "rcpmst_allergens_meta", true)["manual_or_generated"],
					$ingListingMeta["manual_or_generated"],
					$ingListingMeta["manual_listing"],
					//wpautop(get_post_meta($currentId, "rcpmst_dietary_notes_meta", true)["manual_listing"]),
					get_post_meta($currentId, "rcpmst_abv_meta", true),
					isset($ingListingMeta["compound"]) ? $ingListingMeta["compound"] : "",
					isset($ingListingMeta["alternate_name"]) ? $ingListingMeta["alternate_name"] : "",
					);
				$this->build_ingredient_structure($ingredients,get_post_field( 'post_name', $rcpPost->ID ),false);
			}
		}
		
		//wp_reset_postdata();
		if ($traverseLower){
			$this->get_sub_recipe_components($ingredientSlugs);
		}
	}
    function is_id_in_ingredient_details($id){
        $ret = false;
        if(in_array($id,array_column($this->ingredientDetails, "id"),true)){
            $ret = true;
        };
        return $ret;
    }
	function populate_data_table($id, $title,$slug, $recipeOrIngredient, $purchaseAmount, $unit, $price, $activityArray, $recipe,
		$dietNotesMorG, $dietNotesM, $allergensMorG, $ingredientListingMorG, $ingredientListing, $abv, $compoundFlag, $alternateName){
		if (! $this->is_id_in_ingredient_details($id)){
			$this->ingredientDetails[] = array(
				"id" => $id,
				"title" => $title,
				"slug" => $slug,
				"recipeOrIngredient" => $recipeOrIngredient,
				"amount" => $this->normalise_amounts($purchaseAmount,$unit), //convert into g
				"unit" => $unit,
				"price" => $price,
        		"activities" => $activityArray,
				"recipe" => $recipe,
				"dietNotesMorG" => ($dietNotesMorG == "Generated" ? $dietNotesMorG : "Manual"),
				"dietNotesM" => $dietNotesM,
				"allergensMorG" => ($allergensMorG == "Generated" ? $allergensMorG : "Manual"),
				"ingredientListingMorG" => ($ingredientListingMorG == "Generated" ? $ingredientListingMorG : "Manual"),
				"ingredientListing" => $ingredientListing,
				"abv" => $abv,
				"compoundFlag" => $compoundFlag,
				"alternateName" => $alternateName,
			);	
			if ((!$price > 0) && $recipeOrIngredient != "Recipe"){
				array_push($this->productPriceWarnings, "Ingredient " . $title . " has no price. Price details may not be accurate.");
			}
		}	
	}
	function build_ingredient_structure($ingredients, $parentSlug, $addHead){
		// NOTE - items here may not be in the data table yet as subingredients are added before the main entry has been retrieved from the database, 
		// so you can't get data from the ingredientDetails table from this function or called function without care
		// where such data is needed, populate it in the postUpdate function
		if ($addHead ){
			//top level
			$arr["slug"] = $parentSlug;
            $arr["originalQuantity"] = $this->get_amount($this->get_ingredient_id_from_slug($parentSlug))["val"]; //as the head this will have been loaded already
			$arr["unit"] = $this->get_amount($this->get_ingredient_id_from_slug($parentSlug))["unit"];
			$arr["originalPercent"] = 0;
			$arr["comment"] = "";
            $arr["parentSlug"] = NULL;
			$arr["recipeScale"] = 1;
			//$arr["parentRecipeScale"] = 1;
			$arr["actualQuantity"] = $this->get_amount($this->get_ingredient_id_from_slug($parentSlug))["val"];
			$arr["actualPercent"] = 0;
			$arr["price"] = 0;
			$arr["labourDuration"] = 0;
			$arr["dietNotesG"] = [];
            $arr["subingredients"] = [];
			$arr["ingListing"] = [];
			array_push($this->ingredientStructureLookup, $arr);
		}
		$totalWeight = 0;
		if(is_array($ingredients)){
			foreach($ingredients as $ingredient):
				if($ingredient["slug"] != ""){
					$totalWeight += $this->normalise_amounts($ingredient['quantity'],$ingredient['unit']);
				}
			endforeach; 
		
			foreach($ingredients as $ingredient):
				if($ingredient["slug"] != ""){
					$arr["slug"] = $ingredient['slug'];
					$arr["originalQuantity"] = $this->normalise_amounts($ingredient['quantity'], $ingredient['unit']);
					$arr["unit"] = $ingredient['unit'];
					$arr["originalPercent"] = $this->normalise_amounts($ingredient['quantity'],$ingredient['unit']) / $totalWeight;
					$arr["comment"] = $ingredient['comment'];
					$arr["parentSlug"] = $parentSlug;
					$arr["recipeScale"] = 1;
					//$arr["parentRecipeScale"] = 1;
					$arr["actualQuantity"] = $this->normalise_amounts($ingredient['quantity'],$ingredient['unit']);
					$arr["actualPercent"] = $this->normalise_amounts($ingredient['quantity'],$ingredient['unit']) / $totalWeight;
					$arr["price"] = 0;
					$arr["labourDuration"] = 0;
					$arr["dietNotesG"] = [];
					$arr["subingredients"] = [];	
					$arr["ingListing"] = [];
					$this->push_entry_into_ingredient_structure($this->ingredientStructureLookup, $arr);
				}
			endforeach; 
		}
    }
	function push_entry_into_ingredient_structure(&$structure, $ingredArr){
		if(is_array($structure)){
			foreach($structure as &$ingredient):
				if(!empty($ingredient['slug'])){
					if($ingredient["slug"] == $ingredArr["parentSlug"]){
						array_push($ingredient["subingredients"],$ingredArr);
					}
				}			
				$this->push_entry_into_ingredient_structure($ingredient, $ingredArr);
			endforeach;
			unset($ingredient);
		}
	}
	function post_update(&$ingredientStructureLookup, $recipeScale, $excludeIngFromListing,$top){
		//returns array of ing cost and labour duration
		$totalIngCost = 0;
        $totalLabDur = 0;
		$previousIngListing;
		$excludeSubIngFromListing = false;
		foreach($ingredientStructureLookup as &$ingredient):
			$labDur = 0;
			$ingredient["actualQuantity"] = $recipeScale * $ingredient["originalQuantity"];
			//if id is not present, add it from the details table
			if(!isset($ingredient["id"]) || $ingredient["id"] ==""){
				$ingredient["id"] = $this->get_ingredient_id_from_slug($ingredient["slug"]);
			}
			if($this->get_ingredient_or_recipe($ingredient["id"])=="Ingredient"){
				if (is_numeric($this->get_price($ingredient["id"]))){
					$ingredient["price"] = $this->get_price($ingredient["id"]) / $this->get_amount($ingredient["id"])["val"] * $ingredient["actualQuantity"];
				}else{
					$ingredient["price"] = 0;
				}
				$totalIngCost +=  $ingredient["price"];
				$this->totalWeightIngredients += $ingredient["actualQuantity"];
				if(!$excludeIngFromListing){
					//add amount to current ingListing array unless parent item is manual 
					if (!isset($this->currentIngListing[$ingredient["id"]])){
						$this->currentIngListing[$ingredient["id"]] = 0;
					}
					$this->currentIngListing[$ingredient["id"]] += $ingredient["actualQuantity"];
				}

				if(is_numeric($this->get_abv($ingredient["id"]))){
					$ingredient["alcoholAmount"] = $ingredient["actualQuantity"] * ($this->get_abv($ingredient["id"]) / 100);
					$this->totalAlcoholAmount += $ingredient["actualQuantity"] * ($this->get_abv($ingredient["id"]) / 100);
				}else{
					$ingredient["alcoholAmount"] = 0;
				}

			}elseif($this->get_ingredient_or_recipe($ingredient["id"])=="Non-Edible"){
				$ingredient["price"] = $this->get_price($ingredient["id"]) / $this->get_amount($ingredient["id"])["val"] * $ingredient["actualQuantity"];
				$totalIngCost +=  $ingredient["price"];
			}elseif($this->get_ingredient_or_recipe($ingredient["id"])=="Recipe"){
				$storeRecipeScale = $recipeScale;
				$recipeScale = $recipeScale * $ingredient["originalQuantity"] / $this->get_amount($ingredient["id"])["val"];
				$ingredient["recipeScale"] = $recipeScale;
				//ingListing - if compound flag is set, or it is in pieces, add recipe to current ingListings array 
				//and set a new ingListing array
				//don't add top level item to ing array and don't add anything below a manual ing item

				if (!$top && !$excludeIngFromListing){ 
					if ($this->get_ingredient_details_by_id($ingredient["id"])["ingredientListingMorG"] == "Manual"){ 
						if (!isset($this->currentIngListing[$ingredient["id"]])){
							$this->currentIngListing[$ingredient["id"]] = 0;
						}
						$this->currentIngListing[$ingredient["id"]] += $ingredient["actualQuantity"];
						$excludeSubIngFromListing = true;
					}else if($this->get_ingredient_details_by_id($ingredient["id"])["compoundFlag"] == "Compound"  ||
						($this->get_amount($ingredient["id"])["unit"] == 'Pieces')){
						if (!isset($this->currentIngListing[$ingredient["id"]])){
							$this->currentIngListing[$ingredient["id"]] = 0;
						}
						$this->currentIngListing[$ingredient["id"]] += $ingredient["actualQuantity"];
						$previousIngListing = &$this->currentIngListing;
						$this->currentIngListing = &$ingredient["ingListing"];	
					}	
				}

				//recurse
				$subCosts = $this->post_update($ingredient["subingredients"],$recipeScale,$excludeSubIngFromListing,false);

				//sort and then reset ingListing on the way back up
				if (!empty($previousIngListing)){
					$this->currentIngListing = &$previousIngListing;
				}
				$excludeSubIngFromListing = false;

                //labour on this item is this + anything below
				$labDur = ($this->get_activities_duration($ingredient["id"]) * $recipeScale);	
				$labDur += $subCosts[1]; 
                $ingredient["labourDuration"] = $labDur;
				//but also acumulate labour so next level up will contain everything
				$totalLabDur += $labDur;

				$ingredient["price"] = $subCosts[0];
				$totalIngCost += $subCosts[0];
				$recipeScale = $storeRecipeScale;
				if ($this->get_dietary_notes($ingredient["id"])["manual_or_generated"] == "Generated"){
					$ingredient["dietNotesG"] = $this->generate_dietary_notes($ingredient);
				}
			}
		endforeach;

		$costs = [$totalIngCost, $totalLabDur];
		return $costs;
	}


	//Access functions
    function get_post_meta_debug(){
		if($this->is_new_page() ){
			return "";
		}
        return get_post_meta($this->rcPost->ID);
    }
    function get_recipe($id = null){
		if($this->is_new_page() ){
			return "";
		}
		$this->populate();
		if($id == null){
			$id = $this->rcId;
		}
        return  $this->get_ingredient_details_by_id($id)["recipe"];
    }	
    function get_title($id = null){
		if($this->is_new_page() ){
			return "";
		}		
		$this->populate();
		if($id == null){		
			$id = $this->rcId;
		}
		return  $this->get_ingredient_details_by_id($id)["title"];
    }	
    function get_slug($id = null){
		if($this->is_new_page() ){
			return "";
		}		
		$this->populate();
		if($id == null){		
			$id = $this->rcId;
		}
		return  $this->get_ingredient_details_by_id($id)["slug"];
    }	
	function get_amount($id = null){
		$this->populate();
		if($id == null){
			$id = $this->rcId;
		}
		$amount = array(
				"val" => $this->get_ingredient_details_by_id($id)["amount"], 
				"unit"=> $this->get_ingredient_details_by_id($id)["unit"], 
		);
		return  $amount;
	}	
	function get_amount_denormalised($id = null){
		if($this->is_new_page() ){
			return 0;
		}
		$this->populate();
		if($id == null){
			$id = $this->rcId;
		}
		$amount = array(
				"val" => $this->denormalise_amounts($this->get_ingredient_details_by_id($id)["amount"], $this->get_ingredient_details_by_id($id)["unit"]),
				"unit"=> $this->get_ingredient_details_by_id($id)["unit"], 
		);
		return  $amount;
	}	
	function get_price($id = null){
		if($this->is_new_page() ){
			return 0;
		}
		$this->populate();
		if($id == null){
			$id = $this->rcId;
		}
		return $this->get_ingredient_details_by_id($id)["price"];
	}	
	function get_ingredient_or_recipe($id = null){
		if($this->is_new_page() ){
			return "";
		}		
		$this->populate();
		if($id == null){
			$id = $this->rcId;
		}
		return  $this->get_ingredient_details_by_id($id)["recipeOrIngredient"];
	}
	function get_total_ingredient_weight(){
		if($this->is_new_page() ){
			return 0;
		}
		return $this->totalWeightIngredients;
	}
	function get_total_alcohol_amount(){
		if($this->is_new_page() ){
			return 0;
		}
		return $this->totalAlcoholAmount;
	}
	function get_total_abv(){
		if($this->is_new_page() ){
			return 0;
		}		
		return $this->totalAlcoholAmount / $this->totalWeightIngredients;
	}	


	function get_full_ingredient_listing($id = null){
		if($this->is_new_page() ){
			return "";
		}		
		$this->populate();
		if($id == null){
			$id = $this->rcId;
		}
		$ingredientListing = get_post_meta($id, "rcpmst_ingredient_listing_meta", true);
		return $ingredientListing;
	}
	function get_ingredient_listing($id = null, $notices = null){
		if($this->is_new_page() ){
			return "";
		}
		$this->populate();
		if($id == null){
			$id = $this->rcId;
		}
		$notices = array("all",);
		$a = $this->get_ingredient_listing_text($this->ingredientStructureLookup,true, $notices);
		return $a;
	}
	function get_ingredient_listing_text(&$ingredientStructureLookup, $top, $notices){
		$listing = "";
		foreach($ingredientStructureLookup as &$ingredient):
			$subListing = "";
			$ingredientListing = $this->get_full_ingredient_listing($ingredient["id"]);
			//ingList is not empty, so must be a recipe, either top level or compounded
			if(!empty($ingredient["ingListing"])){
				//generated recipe, either top level or compounded
				if($ingredientListing["manual_or_generated"] == "Generated"){
					// we don't include top level name - use alternate name or title
					if(!$top){
						if($ingredientListing["alternate_name"] != ""){
							$subListing .= $ingredientListing["alternate_name"] . " (";
						}else{
							$subListing .= $this->get_ingredient_details_by_id($ingredient["id"])["title"] . " (";
						}
					}	
					uasort($ingredient["ingListing"], function($a, $b) {
						if ($a < $b) {
							return 1;
						} elseif ($a > $b) {
							return -1;
						}
						return 0;
					});
					foreach($ingredient["ingListing"] as $key => $item):
						// if ingredient or a manual listing recipe, just include it
						if($this->get_ingredient_details_by_id($key)["recipeOrIngredient"] == "Ingredient" ||
							($this->get_ingredient_details_by_id($key)["recipeOrIngredient"] == "Recipe" &&
							$this->get_ingredient_details_by_id($key)["ingredientListingMorG"] == "Manual")){
							$subListing .= $this->get_m_or_g_ingredient_name($this->get_ingredient_details_by_id($key));
						}else{
							//otherwise it must be a generated listing recipe, so insert placeholder
							$subListing .= "[" . $key . "]";
						}
					endforeach;
					if(!$top){
						$subListing = rtrim($subListing,", ");
						$subListing .= "), ";
					}	
				}else{
					// manual recipe, so just use defined value
					$subListing .= $ingredientListing["manual_listing"] . ", ";
				}
				//if current item was a recipe and is not top - add to compounding list otherwise add to recipe listing
				if($this->get_ingredient_details_by_id($ingredient["id"])["recipeOrIngredient"] == "Recipe" && !$top){
					if(!array_key_exists($ingredient["id"], $this->ingListingRecipeLookup)){
						$this->ingListingRecipeLookup[$ingredient["id"]] = $subListing;
					}
				}else{
					$listing .= $subListing;
				}				
			}
			//recurse
			$listing .= $this->get_ingredient_listing_text($ingredient["subingredients"], false, $notices);	
		endforeach;
		$listing = $this->parse_ingredient_string($listing);
		$listing = rtrim($listing,", ");	
		$listing = apply_filters( 'rcpmst_ingredient_listing_filter', $listing, $notices);	
		return $listing;
	}

	function get_m_or_g_ingredient_name($ingListing){
		if($ingListing["ingredientListingMorG"] == "Manual"){
			// if empty return empty...
			if (strlen($ingListing["ingredientListing"]) == 0) return "";
			return $ingListing["ingredientListing"] . ", ";
		}else{
			return $ingListing["title"] . ", ";
		}		
	}
	function parse_ingredient_string($ingredientString){
		while (str_contains($ingredientString, "[")) {
  			$start = strpos($ingredientString, "[");
			$len = strpos($ingredientString, "]") - $start;
			$id = substr($ingredientString, $start + 1, $len - 1);
			$ingredientString = substr_replace($ingredientString, $this->ingListingRecipeLookup[$id],$start,$len + 1);
		}
		while (str_contains($ingredientString, "), )")) {
			$ingredientString = str_replace("), )", "))", $ingredientString);
		}		
		return $ingredientString;
	}
	function get_activities($id = null){
		if($this->is_new_page() ){
			return [];
		}
		$this->populate();
		if($id == null){
			$id = $this->rcId;
		}
    	return $this->get_ingredient_details_by_id($id)["activities"];
	}	
	function get_activities_duration($id = null){
		$this->populate();
		if($id == null){
			$id = $this->rcId;
		}
		$totalDur = 0;
    	$activities =  $this->get_ingredient_details_by_id($id)["activities"];
		foreach($activities as $activity):
			$totalDur += $activity["duration"];
		endforeach;
		return $totalDur;
	}
    function get_ingredient_details(){
        return $this->ingredientDetails;
    }

	function get_ingredient_structure_details(){
		return $this->ingredientStructureLookup;
	}

    function get_ingredients($id = null){
		if($this->is_new_page() ){
			return [];
		}		
		$this->populate();
		if($id == null){
			$id = $this->rcId;
		}
		$ingredients = get_post_meta($id, "rcpmst_ingredients_meta", true);
        return $ingredients;
    }	
	function get_allergens($id = null){
		if($this->is_new_page() ){
			return "";
		}
		$this->populate();
		if($id == null){
			$id = $this->rcId;
		}
		$allergens = get_post_meta($id, "rcpmst_allergens_meta", true);
		return $allergens;
	}
	function get_allergens_text($id = null){
		if($this->is_new_page() ){
			return "";
		}
		$text = "";
		$allergenArray = [];
		if($id == null){
			$id = $this->rcId;
		}		
		$allergens = $this->get_allergens($id);
		$allergensMOrG = $this->get_ingredient_details_by_id($this->rcId)["allergensMorG"];
		
		if($allergensMOrG == "Manual"){
			$text = $allergens["manual_listing"];
		}else{
			$ingListing = $this->get_ingredient_listing($this->rcId);
			preg_match_all("~(?<=<strong>).*?(?=</strong>)~", $ingListing, $matches);
			//also get b tags
			preg_match_all("~(?<=<b>).*?(?=</b>)~", $ingListing, $matches2);
			$allMatches = array_merge($matches[0], $matches2[0]);
			foreach ($allMatches as $d) {
				$d = strtolower($d);
				//break down on comma as well
				$temp = explode(",",$d);
				$temp = array_map('trim', $temp);
				$allergenArray = array_merge($allergenArray,$temp);
			}
			
			$allergenArray = array_unique($allergenArray);
			
			foreach ($allergenArray as $allergen) {
			   	// capitalise first letters, rejoin and add strong tags
				$text .= "<strong>" . ucwords($allergen) . "</strong>, ";
			}			
			$text = rtrim($text,", ");
		}
		return $text;
	}
	function get_dietary_notes($id = null){
		$this->populate();
		if($id == null){
			$id = $this->rcId;
		}
    	$dietary_notes = get_post_meta($id, "rcpmst_dietary_notes_meta", true);
		return $dietary_notes;
	}
	function get_dietary_notes_text($id = null){
		if($this->is_new_page() ){
			return "";
		}		
		$text = "";
		if($id == null){
			$id = $this->rcId;
		}		
		$notes = $this->get_dietary_notes($id);
		$dietNotesMOrG = $this->get_ingredient_details_by_id($this->rcId)["dietNotesMorG"];
		foreach($this->dietaryNotesTerms as $dietaryNote){
			if($dietNotesMOrG == "Manual"){
				if(has_term($dietaryNote->term_id, "rcpmst_dietary_notes", $this->rcId)){
					$text .= get_term_meta( $dietaryNote->term_id, "dietary_notes_display_name",true ) . "</br>";
				}else{
					$text .= get_term_meta( $dietaryNote->term_id, "dietary_notes_negative_name",true ) . "</br>";
				}
			}else{
				//get first item from structure, it will be the top level item
				if(in_array($dietaryNote->term_id,$this->ingredientStructureLookup[0]["dietNotesG"])){
					$text .= get_term_meta( $dietaryNote->term_id, "dietary_notes_display_name",true ) . "</br>";
				}else{
					$text .= get_term_meta( $dietaryNote->term_id, "dietary_notes_negative_name",true ) . "</br>";
				}
			}
		}
		return $text;
	}
	function generate_dietary_notes($recipe){ //note $recipe is a node from ingredient structure and will always be a recipe
		$terms = [];
		foreach($this->dietaryNotesTerms as $dietaryNote){
			$anyOrAll = get_term_meta( $dietaryNote->term_id, "dietary_notes_any_vs_all",true );
			if ($anyOrAll == "any"){
				$includeDietaryNote = false;
			}else{
				$includeDietaryNote = true;
			}
			foreach($recipe["subingredients"] as $ingredient){
				if(! is_null($ingredient["id"])){
					$recipeOrIngredient = $this->get_ingredient_details_by_id($ingredient["id"])["recipeOrIngredient"];
					if($recipeOrIngredient != "Non-Edible"){
						$dietNotesMOrG = $this->get_ingredient_details_by_id($ingredient["id"])["dietNotesMorG"];
						if(($dietNotesMOrG == "Generated" && in_array($dietaryNote->term_id,$ingredient["dietNotesG"]))
							|| ($dietNotesMOrG == "Manual" && has_term($dietaryNote->term_id, "rcpmst_dietary_notes", $ingredient["id"]))){
							if($anyOrAll == "any"){
								$includeDietaryNote = true;
							}
						}else{
							if($anyOrAll == "all"){
								$includeDietaryNote = false;
							}
						}
					}
				}
			}
			if($includeDietaryNote){
				//build up array of term ids which can then be added to data structure
				array_push($terms, $dietaryNote->term_id);
			}
		}

		return $terms;
	}
	function get_dietary_notes_terms(){
		$arr = get_terms(array(
			'taxonomy' => 'rcpmst_dietary_notes',
			'hide_empty' => false,
			'meta_key' => 'dietary_notes_enabled',
        	'meta_value' => 'enabled',
		));
		return $arr;
	}

	function get_supplier($id = null){
		if($this->is_new_page() ){
			return "";
		}		
		$this->populate();
		if($id == null){
			$id = $this->rcId;
		}
		return  get_post_meta($id, "rcpmst_supplier_meta", true);
	}
	function get_abv($id = null){
		if($this->is_new_page() ){
			return 0;
		}		
		$this->populate();
		if($id == null){
			$id = $this->rcId;
		}
		$abv = get_post_meta($id, "rcpmst_abv_meta", true);
		if(! is_numeric($abv)){
			$abv = 0;
		}
		return  $abv;
	}	
	function get_retail_price($id = null){
		if($this->is_new_page() ){
			return '';
		}		
		$this->populate();
		if($id == null){
			$id = $this->rcId;
		}
		$retail = get_post_meta($id, "rcpmst_retail_meta", true);
		if(! isset($retail["retailPrice"])){
			return '';
		}		
		return  $retail["retailPrice"];
	}
	function get_sku($id = null){
		if($this->is_new_page() ){
			return '';
		}		
		$this->populate();
		if($id == null){
			$id = $this->rcId;
		}
		$retail = get_post_meta($id, "rcpmst_retail_meta", true);
		if(! isset($retail["sku"])){
			return "";
		}		
		return  $retail["sku"];
	}
	function get_shelf_life($id = null){
		if($this->is_new_page() ){
			return '';
		}		
		$this->populate();
		if($id == null){
			$id = $this->rcId;
		}
		$storage = get_post_meta($id, "rcpmst_storage_meta", true);
		if(! isset($storage["shelfLife"])){
			return "";
		}
		return  $storage["shelfLife"];
	}
	function get_storage($id = null){
		if($this->is_new_page() ){
			return '';
		}		
		$this->populate();
		if($id == null){
			$id = $this->rcId;
		}
		$storage = get_post_meta($id, "rcpmst_storage_meta", true);
		if(! isset($storage["storage"])){
			return "";
		}	
		return  $storage["storage"];
	}
	function get_source($id = null){
		if($this->is_new_page() ){
			return "";
		}		
		$this->populate();
		if($id == null){
			$id = $this->rcId;
		}
		return  get_post_meta($id, "rcpmst_source_meta", true);
	}	
	function get_product_price_warnings(){
		return $this->productPriceWarnings;
	}	
	function normalise_amounts($amount, $unit){ //convert into g
		if($unit == 'Grams'){
			return $amount;
		}elseif($unit == 'Kilograms'){
			return $amount * 1000;
		}elseif($unit == 'Millilitres'){
			return $amount;
		}elseif($unit == 'Drops'){
			return $amount / get_option( 'rcpmst-plugin-settings' )['drops_per_g'];
		}elseif($unit == 'Pieces'){
// to do - raise warning ?
			return $amount;
		}
	}
	function denormalise_amounts($amount, $unit){ //convert from g
		if($unit == 'Grams'){
			return $amount;
		}elseif($unit == 'Kilograms'){
			return $amount / 1000;
		}elseif($unit == 'Millilitres'){
			return $amount;
		}elseif($unit == 'Drops'){
			return $amount * get_option( 'rcpmst-plugin-settings' )['drops_per_g'];
		}elseif($unit == 'Pieces'){
// to do - raise warning ?
			return $amount;
		}
	}


	function get_ingredient_slugs($ingredients){
        $ingredientSlugs = [];
		if(is_array($ingredients)){
			foreach($ingredients as $ingredient):
				if(array_key_exists("slug",$ingredient)){
					if (!($ingredient["slug"]=="NULL")):
						$ingredientSlugs[] = $ingredient['slug'];
					endif;
				}
			endforeach; 		
		}
		return $ingredientSlugs;
	}

	function &get_ingredient_details_by_id($id){
		foreach($this->ingredientDetails as &$ingredient):
			if($ingredient["id"] == $id){
				return $ingredient;
			}
		endforeach;
	}

	function get_ingredient_id_from_slug($slug){
		foreach($this->ingredientDetails as $ingredient):
			if($ingredient["slug"] == $slug){
				return $ingredient["id"];
			}
		endforeach;
	}
	function is_new_page(){
		global $pagenow;
		return in_array( $pagenow, array( 'post-new.php' )) || $this->newPageFlag;
	}
	
}