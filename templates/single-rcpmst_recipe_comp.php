<?php

namespace Recipe_Master;

if ( current_user_can( "read_recipe_components" ) ) {
    get_header();
    function sigFig(  $value, $digits  ) {
        if ( $value == "" ) {
            $value = 0;
        }
        if ( $value == 0 ) {
            $decimalPlaces = $digits - 1;
        } elseif ( $value < 0 ) {
            $decimalPlaces = $digits - floor( log10( $value * -1 ) ) - 1;
        } else {
            $decimalPlaces = $digits - floor( log10( $value ) ) - 1;
        }
        $answer = ( $decimalPlaces > 0 ? number_format( $value, $decimalPlaces ) : round( $value, (int) $decimalPlaces ) );
        return $answer;
    }

    function return_recipe_block(
        $rc,
        $ingredientStructure,
        &$ingredientsHTML,
        &$recipesHTML,
        &$costingsHTML,
        $level,
        &$recipeCosts,
        &$labourCosts,
        $unitCostText,
        $unitCostDivisor
    ) {
        $level = $level + 1;
        foreach ( $ingredientStructure as $ingredient ) {
            if ( !($ingredient["slug"] == "") ) {
                $ingredientsHTML .= "<div>";
                if ( count( $ingredient["subingredients"] ) > 0 ) {
                    // title
                    $ingredientsHTML .= "<h" . esc_html( $level ) . " id='" . esc_html( $rc->get_title( $ingredient["id"] ) ) . "'>" . esc_html( $rc->get_title( $ingredient["id"] ) ) . " - Ingredients (";
                    $ingredientsHTML .= "<a href='../../wp-admin/post.php?action=edit&post=" . esc_html( $ingredient["id"] ) . "' target='new'>Edit</a>)</h" . esc_html( $level ) . ">";
                    if ( $level > 1 ) {
                        //don't show leftovers at top level
                        $ingredientsHTML .= generate_leftovers_block( $ingredient );
                    }
                    $ingredientsHTML .= generate_ingredients_table(
                        $rc,
                        $ingredient,
                        $level,
                        $recipeCosts,
                        $labourCosts,
                        $unitCostText,
                        $unitCostDivisor
                    );
                }
                $ingredientsHTML .= "</div>";
                if ( $rc->get_ingredient_or_recipe( $ingredient["id"] ) == "Recipe" ) {
                    $title = "<h" . esc_html( $level ) . ">" . esc_html( $rc->get_title( $ingredient["id"] ) ) . " - Recipe</h" . esc_html( $level ) . ">";
                    $recipesHTML .= $title;
                    $costingsHTML .= $title;
                    //include tasks and costs
                    $recipesHTML .= '<span class="recipeMode" ';
                    if ( get_post_thumbnail_id( $ingredient["id"] ) ) {
                        $recipesHTML .= 'style="min-height:300px">';
                    } else {
                        $recipesHTML .= '>';
                    }
                    $recipesHTML .= wp_get_attachment_image(
                        get_post_thumbnail_id( $ingredient["id"] ),
                        'medium',
                        false,
                        array(
                            'align'   => 'right',
                            'float'   => 'right',
                            'display' => 'inline',
                        )
                    );
                    $recipesHTML .= wp_kses_post( $rc->get_recipe( $ingredient["id"] ) );
                    $recipesHTML .= '</span>';
                    if ( current_user_can( "edit_recipe_components" ) ) {
                        $activities = $rc->get_activities( $ingredient["id"] );
                        if ( is_array( $activities ) ) {
                            $recipeDuration = 0;
                            $labourCost = 0;
                            $costingsHTML .= generate_costings_table(
                                $rc,
                                $ingredient,
                                $recipeCosts,
                                $labourCosts,
                                $unitCostText,
                                $unitCostDivisor,
                                $activities,
                                $recipeDuration
                            );
                        }
                    }
                }
                return_recipe_block(
                    $rc,
                    $ingredient["subingredients"],
                    $ingredientsHTML,
                    $recipesHTML,
                    $costingsHTML,
                    $level,
                    $recipeCosts,
                    $labourCosts,
                    $unitCostText,
                    $unitCostDivisor
                );
            }
        }
    }

    function generate_leftovers_block(  $ingredient  ) {
        $o = "<div class='quantityblock recipeMode'>Leftovers <input type='number' step='any' id='leftovers-" . esc_html( $ingredient["id"] ) . "' style='width:100px' ";
        $o .= "onkeyup='if(event.keyCode == 13){recalculateQuantities();}'/> ";
        $o .= esc_html( $ingredient["unit"] ) . " <input type='button' onClick='recalculateQuantities()' value='Apply'/></div>";
        return $o;
    }

    function generate_ingredients_table(
        $rc,
        $ingredient,
        $level,
        &$recipeCosts,
        &$labourCosts,
        $unitCostText,
        $unitCostDivisor
    ) {
        $o = "<div>";
        $o .= "<table border='1' class='recipeTable'>";
        /* cols
        		name
        		quantity scaled
        		%
        		cost
        		ABV
        		RSF
        		quantity orig
        		% orig
        		comment 
        		subrec
        		*/
        $o .= "<colgroup><col span='3' style='visibility: visible' class='colQuantity'>";
        if ( current_user_can( "edit_recipe_components" ) ) {
            $o .= "<col span='2' style='visibility: visible' class='colPriceMode'>";
            $o .= "<col span='1' style='visibility: visible' class='colCompositionMode'>";
        }
        $o .= "<col span='3' style='visibility: collapse' class='colOriginalQuantity'>";
        $o .= "<col span='2' style='visibility: visible' class='colComment'>";
        $o .= "</colgroup>";
        $o .= "<tr><th>Name</th><th>Quantity (scaled)</th><th>Percentage (original recipe)</th>";
        if ( current_user_can( "edit_recipe_components" ) ) {
            $o .= "<th>Cost (" . esc_html( get_option( 'rcpmst-plugin-settings' )['currency_symbol'] ) . ")</th>";
            $o .= "<th>Unit Cost (" . esc_html( get_option( 'rcpmst-plugin-settings' )['currency_symbol'] ) . ")</br>" . $unitCostText . "</th>";
            $o .= "<th>Alcohol Content</th>";
        }
        $o .= "<th>Recipe Scale Factor</th><th>Quantity (original recipe)</th>";
        $o .= "<th>Percentage (Scaled)</th><th>comment</th><th>Subrecipe?</th></tr>";
        foreach ( $ingredient["subingredients"] as $subIng ) {
            $o .= "<tr>";
            $o .= "<td>" . esc_html( $rc->get_title( $subIng["id"] ) ) . "</td>";
            $o .= "<td><span class='recipeQuantity' data-parent-id='" . esc_html( $ingredient["id"] ) . "' ";
            $o .= "data-original-percent='" . esc_html( $subIng["originalPercent"] ) . "' ";
            $o .= "data-quantity='" . esc_html( $rc->denormalise_amounts( $subIng["actualQuantity"], $subIng["unit"] ) ) . "'>";
            $o .= esc_html( sigFig( $rc->denormalise_amounts( $subIng["actualQuantity"], $subIng["unit"] ), 3 ) ) . "</span> " . esc_html( $subIng["unit"] ) . "</td>";
            $o .= "<td>" . esc_html( sigFig( $subIng["originalPercent"] * 100, 3 ) ) . "%</td>";
            if ( current_user_can( "edit_recipe_components" ) ) {
                $o .= "<td>" . esc_html( sigFig( $subIng["price"], 3 ) ) . "</td>";
                $o .= "<td>" . esc_html( sigFig( $subIng["price"] / $unitCostDivisor, 3 ) ) . "</td>";
                if ( $rc->get_total_ingredient_weight() > 0 && array_key_exists( "alcoholAmount", $subIng ) ) {
                    $o .= "<td>" . esc_html( sigFig( $subIng["alcoholAmount"], 3 ) ) . " </td>";
                } else {
                    $o .= "<td> - </td>";
                }
            }
            $o .= "<td>" . esc_html( sigFig( $subIng["recipeScale"] * 100, 3 ) ) . " %</td>";
            $o .= "<td>" . esc_html( $rc->denormalise_amounts( $subIng["originalQuantity"], $subIng["unit"] ) . " " . $subIng["unit"] ) . "</td>";
            if ( $subIng["unit"] != 'Pieces' ) {
                if ( $rc->get_total_ingredient_weight() > 0 ) {
                    $o .= "<td>" . esc_html( sigFig( $subIng["actualQuantity"] / $rc->get_total_ingredient_weight() * 100, 3 ) ) . " %</td>";
                } else {
                    $o .= "<td> - </td>";
                }
            } else {
                $o .= "<td>-</td>";
            }
            $o .= "<td>" . esc_html( $subIng["comment"] ) . "</td>";
            $o .= "<td>";
            if ( $rc->get_ingredient_or_recipe( $subIng["id"] ) == "Recipe" ) {
                $o .= "<a href='#" . esc_html( $rc->get_title( $subIng["id"] ) ) . "'>Scroll to method</a>";
                $o .= "</br><a href='" . esc_url( site_url() ) . "/rcpmst_recipe_comp/" . esc_html( $rc->get_slug( $subIng["id"] ) ) . "'>Open recipe</a>";
                $recipeCosts[$rc->get_title( $subIng["id"] )] = sigFig( $subIng["price"], 3 );
            }
            $o .= "</td>";
            $o .= "</tr>";
        }
        if ( $level == 1 ) {
            //top level item
            $recipeCosts[$rc->get_title( $ingredient["id"] )] = $ingredient["price"];
            $labourCosts[$rc->get_title( $ingredient["id"] )] = $ingredient["labourDuration"] / 60 * get_option( 'rcpmst-plugin-settings' )['labour_rate'];
        }
        $o .= "</table></div>";
        return $o;
    }

    function generate_costings_table(
        $rc,
        $ingredient,
        &$recipeCosts,
        &$labourCosts,
        $unitCostText,
        $unitCostDivisor,
        $activities,
        &$recipeDuration
    ) {
        $o = "<table class='recipeTable priceMode'><tr><th>Task</th><th>Length (minutes)</th>";
        $o .= "<th>Cost (" . esc_html( get_option( 'rcpmst-plugin-settings' )['currency_symbol'] ) . ")</th><th>Unit Cost (" . esc_html( get_option( 'rcpmst-plugin-settings' )['currency_symbol'] ) . ")</br>" . $unitCostText . "</th></tr>";
        foreach ( $activities as $activity ) {
            $o .= "<tr><td>" . esc_html( $activity["activity"] ) . "</td>";
            $o .= "<td>" . esc_html( $activity["duration"] ) . "</td>";
            $recipeDuration += $activity["duration"];
            $cost = sigFig( $activity["duration"] / 60 * get_option( 'rcpmst-plugin-settings' )['labour_rate'], 3 );
            $o .= "<td>" . esc_html( sigFig( $cost, 3 ) ) . "</td>";
            $o .= "<td>" . esc_html( sigFig( $cost / $unitCostDivisor, 3 ) ) . "</td></tr>";
            //$labourCost += $cost;
        }
        $o .= "<tr><td><b>Total Labour</b></br>(recipe)</td>";
        $o .= "<td>" . esc_html( sigFig( $recipeDuration, 3 ) ) . "</td>";
        $labourCost = $recipeDuration / 60 * get_option( 'rcpmst-plugin-settings' )['labour_rate'];
        $o .= "<td>" . esc_html( sigFig( $labourCost, 3 ) ) . "</td>";
        $o .= "<td>" . esc_html( sigFig( $labourCost / $unitCostDivisor, 3 ) ) . "</td></tr>";
        $o .= "<tr><td><b>Total Labour</b></br>(scaled & inc sub-recipes)</td>";
        $o .= "<td>" . esc_html( sigFig( $ingredient["labourDuration"], 3 ) ) . "</td>";
        $labourCost = $ingredient["labourDuration"] / 60 * get_option( 'rcpmst-plugin-settings' )['labour_rate'];
        $o .= "<td>" . esc_html( sigFig( $labourCost, 3 ) ) . "</td>";
        $o .= "<td>" . esc_html( sigFig( $labourCost / $unitCostDivisor, 3 ) ) . "</td></tr>";
        $o .= "<tr><td><b>Total Ingredients</b></br>(scaled & inc sub-recipes)</td>";
        $o .= "<td></td>";
        $o .= "<td>" . esc_html( sigFig( $recipeCosts[$rc->get_title( $ingredient["id"] )], 3 ) ) . "</td>";
        $o .= "<td>" . esc_html( sigFig( $recipeCosts[$rc->get_title( $ingredient["id"] )] / $unitCostDivisor, 3 ) ) . "</td></tr>";
        $o .= "<tr><td><b>Total Cost</b></br>(scaled & inc sub-recipes)</td>";
        $o .= "<td></td>";
        $o .= "<td>" . esc_html( sigFig( $labourCost + $recipeCosts[$rc->get_title( $ingredient["id"] )], 3 ) ) . "</td>";
        $o .= "<td>" . esc_html( sigFig( ($labourCost + $recipeCosts[$rc->get_title( $ingredient["id"] )]) / $unitCostDivisor, 3 ) ) . "</td></tr>";
        $o .= "</table>";
        return $o;
    }

    function generate_meta_block() {
        $o = "<div><h4>Source</h4>";
        $o .= esc_html( get_post_meta( get_the_ID(), 'rcpmst_source', true ) );
        $o .= "</div><div><h4>ID</h4>";
        $o .= esc_html( get_the_ID() );
        $o .= "</div>";
        return $o;
    }

    function generate_quantity_block(  $rc  ) {
        $o = "<span class='recipeMode'><div class='quantityblock' >";
        $o .= "Total Amount <span id='originalRecipeTotal' data-quantity='" . esc_html( $rc->denormalise_amounts( $rc->get_amount()["val"], $rc->get_amount()["unit"] ) ) . "'> ";
        $o .= "<input type='text' step='any' id='recipeTotal' value='" . esc_html( $rc->denormalise_amounts( $rc->get_amount()["val"], $rc->get_amount()["unit"] ) ) . "' ";
        $o .= "onfocus='setOldValue(this);' onkeyup='if(event.keyCode == 13){recalculateQuantities();setOldValue(this);}' style='width:100px'/> ";
        $o .= "</span>" . esc_html( $rc->get_amount()["unit"] ) . " ";
        $o .= "</br>Adjust Quantity - ";
        $o .= "<input type='button' onClick='scale(0.5)' value='x0.5'/> ";
        $o .= "<input type='button' onClick='scale(1.5)' value='x1.5'/> ";
        $o .= "<input type='button' onClick='scale(2)' value='x2'/> ";
        $o .= "<input type='button' onClick='reset()' value='Reset'/></div></span>";
        return $o;
    }

    function generate_ingredient_listings(  $ingredientsHTML  ) {
        $o = "<span class='recipeMode priceMode compositionMode'>";
        $o .= "<a onClick='showHideOriginalCols()'>Show/Hide Calculation Values</a>";
        $o .= $ingredientsHTML;
        $o .= "</span>";
        return $o;
    }

    function generate_recipe_listings(  $recipesHTML  ) {
        $o = "<span class='recipeMode'>";
        $o .= $recipesHTML;
        $o .= "</span>";
        return $o;
    }

    function generate_costings_listings(  $costingsHTML  ) {
        $o = "<span class='priceMode'>";
        $o .= $costingsHTML;
        $o .= "</span>";
        return $o;
    }

    function generate_price_block(  $rc, $price, $unitCostText  ) {
        $o = "<span class='priceMode' id='price' data-price='" . esc_html( $price ) . "'></br>Price - " . esc_html( get_option( 'rcpmst-plugin-settings' )['currency_symbol'] );
        $o .= esc_html( sigFig( $price, 3 ) . $unitCostText );
        foreach ( $rc->get_product_price_warnings() as $warning ) {
            $o .= "</br><font color='red'>" . esc_html( $warning ) . "</font>";
        }
        $o .= "</span>";
        return $o;
    }

    function generate_composition_block(  $rc  ) {
        $o = "<span class='compositionMode'>";
        $o .= "ABV - " . esc_html( sigfig( $rc->get_total_abv() * 100, 3 ) ) . " %";
        if ( $rc->get_shelf_life() != "" ) {
            $dt = new \DateTime();
            $di = \DateInterval::createFromDateString( $rc->get_shelf_life() );
            $o .= "</br>Estimated Shelf Life ";
            if ( $di ) {
                $o .= "- " . esc_html( $rc->get_shelf_life() . " - " . $dt->add( $di )->format( 'd M y' ) );
            } else {
                $o .= "can't be calculated";
            }
        }
        $o .= "</span>";
        return $o;
    }

    function generate_test_block() {
        $o = "<span class='testMode'>";
        $o .= "<h2>Shortcodes</h2>";
        $o .= "<h3>Test Description</h3>-------------------";
        $o .= "[rcpmst_description]";
        $o .= "-------------------";
        $o .= "<h3>Test Ingredient Listing</h3>-------------------";
        $o .= "[rcpmst_ingredients title='h4' notices='true']";
        $o .= "-------------------";
        $o .= "<h3>Test Dietary Notes</h3>-------------------";
        $o .= "[rcpmst_dietary_notes title='h4']";
        $o .= "-------------------";
        $o .= "<h3>Test Allergens (with may include)</h3>-------------------";
        $o .= "[rcpmst_allergens title='h4' may-contain='true']";
        $o .= "-------------------";
        $o .= "<h3>Test Allergens (inline)</h3>-------------------";
        $o .= "[rcpmst_allergens title='span' container='span']";
        $o .= "</br>-------------------";
        $o .= "[rcpmst_storage title='span' title-seperator=' - ' may-contain='true']";
        $o .= "[rcpmst_sku title='h4' may-contain='true']";
        $o .= "[rcpmst_image]";
        $o .= "</span>";
        return $o;
    }

    $ingredientsHTML = "";
    $recipesHTML = "";
    $costingsHTML = "";
    $recipeCosts = [];
    $labourCosts = [];
    $rc = new Recipe_Component();
    $rc->populate();
    $ingredientStructure = $rc->get_ingredient_structure_details();
    $unitCostText = "";
    $unitCostDivisor = $rc->get_amount()["val"];
    if ( $rc->get_amount()["unit"] == "Pieces" ) {
        $unitCostText = " per piece";
    } else {
        $unitCostText = " per 100g";
        $unitCostDivisor = $unitCostDivisor / 100;
    }
    return_recipe_block(
        $rc,
        $ingredientStructure,
        $ingredientsHTML,
        $recipesHTML,
        $costingsHTML,
        0,
        $recipeCosts,
        $labourCosts,
        $unitCostText,
        $unitCostDivisor
    );
    $ingPrice = $recipeCosts[$rc->get_title()] / $unitCostDivisor;
    $labourPrice = $labourCosts[$rc->get_title()] / $unitCostDivisor;
    $price = $ingPrice + $labourPrice;
    $o = Public_Content::top_menu();
    while ( have_posts() ) {
        the_post();
        ?>
		<div class="content-container" onload="recalculateQuantities()">
		<div class="container"  style="padding:0 10px 0 20px;float:left;">
		<?php 
        // title
        $o .= "<h1>" . esc_html( $rc->get_title() ) . "</h1>";
        $o .= "<h3>Default quantity - " . esc_html( $rc->denormalise_amounts( $rc->get_amount()["val"], $rc->get_amount()["unit"] ) ) . ' ' . esc_html( $rc->get_amount()["unit"] ) . "</h3></br>";
        //tabs
        $o .= "<div class='tab'>";
        $o .= "<button class='tablinks' onclick='changeMode(event, 0)' id='defaultTab'>Recipe Mode</button>";
        if ( current_user_can( "edit_recipe_components" ) ) {
            $o .= "<button class='tablinks' onclick='changeMode(event, 1)'>Price Mode</button>";
            $o .= "<button class='tablinks' onclick='changeMode(event, 2)'>Composition Mode</button>";
            $o .= "<button class='tablinks' onclick='changeMode(event, 3)'>Test Features</button>";
        }
        $o .= "</div>";
        //content
        $o .= "<div class='tabcontent'>";
        if ( current_user_can( "edit_recipe_components" ) ) {
            $o .= generate_price_block( $rc, $price, $unitCostText );
            $o .= generate_composition_block( $rc );
            $o .= generate_test_block();
        }
        $o .= generate_costings_listings( $costingsHTML );
        $o .= generate_quantity_block( $rc );
        $o .= generate_ingredient_listings( $ingredientsHTML );
        $o .= generate_recipe_listings( $recipesHTML );
        $o .= "</div>";
        $o .= generate_meta_block();
        echo do_shortcode( $o );
        esc_html( comments_template() );
        ?>
		</div>
		</div>
	<?php 
    }
    esc_html( get_footer() );
} else {
    wp_redirect( esc_url( wp_login_url() . "?redirect_to=" . home_url( $wp->request ) ) );
}