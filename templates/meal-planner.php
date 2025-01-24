<?php
/**
 * Meal Planner Template for CulinaryCanvas Pro
 */
if (!defined('ABSPATH')) {
    exit;
}

// Retrieve recipes
$recipes = get_posts(array(
    'post_type' => 'recipe',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC'
));

// Get current user's saved meal plan
$user_id = get_current_user_id();
$current_meal_plan = get_user_meta($user_id, 'current_meal_plan', true);
$current_meal_plan = is_array($current_meal_plan) ? $current_meal_plan : array();

// Set start date to the beginning of the current week
$start_date = new DateTime('monday this week');
?>

<div class="wrap culinary-canvas-meal-planner">
    <h1><?php _e('Meal Planner', 'culinary-canvas-pro'); ?></h1>
    
    <div class="meal-planner-container">
        <!-- Recipes Sidebar -->
        <div class="meal-planner-sidebar">
            <div class="recipes-header">
                <h3><?php _e('Available Recipes', 'culinary-canvas-pro'); ?></h3>
                <input 
                    type="text" 
                    id="recipe-search" 
                    placeholder="<?php esc_attr_e('Search recipes...', 'culinary-canvas-pro'); ?>"
                >
            </div>
            
            <div class="recipe-list scrollable">
                <?php foreach ($recipes as $recipe) : ?>
                    <div 
                        class="recipe-item draggable" 
                        data-recipe-id="<?php echo esc_attr($recipe->ID); ?>"
                    >
                        <?php echo esc_html($recipe->post_title); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Meal Planning Calendar -->
        <div class="meal-planner-calendar">
            <div class="calendar-navigation">
                <button class="btn-nav prev-week">&laquo; <?php _e('Previous Week', 'culinary-canvas-pro'); ?></button>
                <span class="current-week-display">
                    <?php 
                    echo esc_html($start_date->format('M d, Y')) . ' - ' . 
                         esc_html($start_date->modify('+6 days')->format('M d, Y'));
                    ?>
                </span>
                <button class="btn-nav next-week"><?php _e('Next Week', 'culinary-canvas-pro'); ?> &raquo;</button>
            </div>

            <table class="meal-calendar">
                <thead>
                    <tr>
                        <th class="time-column"><?php _e('Meal', 'culinary-canvas-pro'); ?></th>
                        <?php 
                        $start_date->modify('-6 days'); // Reset to original start date
                        for ($i = 0; $i < 7; $i++) {
                            $current_date = clone $start_date;
                            $current_date->modify("+$i days");
                            echo '<th data-date="' . esc_attr($current_date->format('Y-m-d')) . '">' .
                                 esc_html($current_date->format('D')) . '<br>' .
                                 esc_html($current_date->format('M d')) . 
                                 '</th>';
                        }
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $meal_types = array(
                        'breakfast' => __('Breakfast', 'culinary-canvas-pro'),
                        'lunch' => __('Lunch', 'culinary-canvas-pro'),
                        'dinner' => __('Dinner', 'culinary-canvas-pro')
                    );

                    foreach ($meal_types as $meal_key => $meal_label) :
                    ?>
                    <tr class="meal-row" data-meal-type="<?php echo esc_attr($meal_key); ?>">
                        <td class="meal-type"><?php echo esc_html($meal_label); ?></td>
                        <?php 
                        $start_date->modify('-6 days'); // Reset to original start date
                        for ($i = 0; $i < 7; $i++) {
                            $current_date = clone $start_date;
                            $current_date->modify("+$i days");
                            $date_key = $current_date->format('Y-m-d');
                            
                            // Safely check for planned recipe
                            $planned_recipe = null;
                            if (
                                isset($current_meal_plan[$date_key]) && 
                                is_array($current_meal_plan[$date_key]) && 
                                isset($current_meal_plan[$date_key][$meal_key])
                            ) {
                                $planned_recipe = $current_meal_plan[$date_key][$meal_key];
                            }
                            
                            $recipe_title = $planned_recipe ? get_the_title($planned_recipe) : '';
                            ?>
                            <td 
                                class="meal-slot droppable" 
                                data-date="<?php echo esc_attr($date_key); ?>" 
                                data-meal="<?php echo esc_attr($meal_key); ?>"
                            >
                                <div class="recipe-placeholder">
                                    <?php echo esc_html($recipe_title); ?>
                                </div>
                            </td>
                        <?php } ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="meal-plan-actions">
        <button id="save-meal-plan" class="button button-primary">
            <?php _e('Save Meal Plan', 'culinary-canvas-pro'); ?>
        </button>
        <button id="generate-shopping-list" class="button button-secondary">
            <?php _e('Generate Shopping List', 'culinary-canvas-pro'); ?>
        </button>
        <button id="print-meal-plan" class="button">
            <?php _e('Print Meal Plan', 'culinary-canvas-pro'); ?>
        </button>
    </div>

    <!-- Shopping List Modal -->
    <div id="shopping-list-modal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2><?php _e('Shopping List', 'culinary-canvas-pro'); ?></h2>
            <div id="shopping-list-content"></div>
            <div class="modal-actions">
                <button id="print-shopping-list" class="button">
                    <?php _e('Print Shopping List', 'culinary-canvas-pro'); ?>
                </button>
                <button id="close-shopping-list" class="button">
                    <?php _e('Close', 'culinary-canvas-pro'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function($) {
    $(document).ready(function() {
        // Drag and drop functionality
        $('.recipe-item').draggable({
            helper: 'clone',
            cursor: 'move'
        });

        $('.meal-slot').droppable({
            accept: '.recipe-item',
            drop: function(event, ui) {
                var recipeId = ui.draggable.data('recipe-id');
                var recipeTitle = ui.draggable.text();
                var mealSlot = $(this);

                // Update the slot with the recipe
                mealSlot.find('.recipe-placeholder').text(recipeTitle);
                mealSlot.data('recipe-id', recipeId);
            }
        });

        // Save Meal Plan
        $('#save-meal-plan').on('click', function() {
            var mealPlan = {};
            
            $('.meal-slot').each(function() {
                var date = $(this).data('date');
                var meal = $(this).data('meal');
                var recipeId = $(this).data('recipe-id');

                if (!mealPlan[date]) {
                    mealPlan[date] = {};
                }

                if (recipeId) {
                    mealPlan[date][meal] = recipeId;
                }
            });

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'save_meal_plan',
                    nonce: '<?php echo wp_create_nonce('meal_planner_nonce'); ?>',
                    meal_plan: JSON.stringify(mealPlan)
                },
                success: function(response) {
                    if (response.success) {
                        alert('Meal plan saved successfully!');
                    } else {
                        alert('Error saving meal plan.');
                    }
                }
            });
        });

        // Generate Shopping List (basic implementation)
        $('#generate-shopping-list').on('click', function() {
            var ingredients = [];
            
            $('.meal-slot').each(function() {
                var recipeId = $(this).data('recipe-id');
                if (recipeId) {
                    // AJAX call to get recipe ingredients
                    $.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        data: {
                            action: 'get_recipe_details',
                            nonce: '<?php echo wp_create_nonce('meal_planner_nonce'); ?>',
                            recipe_id: recipeId
                        },
                        success: function(response) {
                            if (response.success) {
                                ingredients = ingredients.concat(response.data.ingredients);
                                
                                // Consolidate ingredients
                                var consolidatedIngredients = {};
                                ingredients.forEach(function(ingredient) {
                                    if (consolidatedIngredients[ingredient]) {
                                        consolidatedIngredients[ingredient]++;
                                    } else {
                                        consolidatedIngredients[ingredient] = 1;
                                    }
                                });

                                // Display shopping list
                                var shoppingListHtml = '<ul>';
                                for (var ingredient in consolidatedIngredients) {
                                    shoppingListHtml += '<li>' + ingredient + '</li>';
                                }
                                shoppingListHtml += '</ul>';

                                $('#shopping-list-content').html(shoppingListHtml);
                                $('#shopping-list-modal').show();
                            }
                        }
                    });
                }
            });
        });

        // Modal close buttons
        $('.close-modal, #close-shopping-list').on('click', function() {
            $('#shopping-list-modal').hide();
        });
    });
})(jQuery);
</script>