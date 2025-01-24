<?php
if (!defined('ABSPATH')) {
    exit;
}

$recipes = get_posts(array(
    'post_type' => 'recipe',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC'
));

$ingredient_costs = get_option('ingredient_costs', array());
$currency_symbol = get_option('culinary_canvas_settings')['currency_symbol'] ?? '$';
?>

<div class="wrap">
    <h1><?php _e('Recipe Cost Calculator', 'culinary-canvas-pro'); ?></h1>

    <div class="cost-calculator-container">
        <!-- Ingredient Costs Section -->
        <div class="ingredient-costs-section">
            <h2><?php _e('Ingredient Cost Database', 'culinary-canvas-pro'); ?></h2>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Ingredient', 'culinary-canvas-pro'); ?></th>
                        <th><?php _e('Unit', 'culinary-canvas-pro'); ?></th>
                        <th><?php _e('Cost per Unit', 'culinary-canvas-pro'); ?></th>
                        <th><?php _e('Actions', 'culinary-canvas-pro'); ?></th>
                    </tr>
                </thead>
                <tbody id="ingredient-costs-list">
                    <?php foreach ($ingredient_costs as $id => $cost) : ?>
                        <tr data-id="<?php echo esc_attr($id); ?>">
                            <td><?php echo esc_html($cost['name']); ?></td>
                            <td><?php echo esc_html($cost['unit']); ?></td>
                            <td><?php echo esc_html($currency_symbol . number_format($cost['cost'], 2)); ?></td>
                            <td>
                                <button type="button" class="button edit-ingredient">
                                    <?php _e('Edit', 'culinary-canvas-pro'); ?>
                                </button>
                                <button type="button" class="button delete-ingredient">
                                    <?php _e('Delete', 'culinary-canvas-pro'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="add-ingredient-row">
                        <td>
                            <input type="text" id="new-ingredient-name" 
                                   placeholder="<?php esc_attr_e('Ingredient name', 'culinary-canvas-pro'); ?>">
                        </td>
                        <td>
                            <select id="new-ingredient-unit">
                                <option value="g"><?php _e('Grams (g)', 'culinary-canvas-pro'); ?></option>
                                <option value="kg"><?php _e('Kilograms (kg)', 'culinary-canvas-pro'); ?></option>
                                <option value="ml"><?php _e('Milliliters (ml)', 'culinary-canvas-pro'); ?></option>
                                <option value="l"><?php _e('Liters (l)', 'culinary-canvas-pro'); ?></option>
                                <option value="piece"><?php _e('Piece', 'culinary-canvas-pro'); ?></option>
                                <option value="cup"><?php _e('Cup', 'culinary-canvas-pro'); ?></option>
                                <option value="tbsp"><?php _e('Tablespoon', 'culinary-canvas-pro'); ?></option>
                                <option value="tsp"><?php _e('Teaspoon', 'culinary-canvas-pro'); ?></option>
                            </select>
                        </td>
                        <td>
                            <div class="currency-input" data-currency="<?php echo esc_attr($currency_symbol); ?>">
                                <input type="number" id="new-ingredient-cost" step="0.01" min="0" 
                                       placeholder="<?php esc_attr_e('Cost', 'culinary-canvas-pro'); ?>">
                            </div>
                        </td>
                        <td>
                            <button type="button" class="button button-primary" id="add-ingredient-cost">
                                <?php _e('Add', 'culinary-canvas-pro'); ?>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Recipe Cost Calculation Section -->
        <div class="recipe-cost-calculation">
            <h2><?php _e('Calculate Recipe Cost', 'culinary-canvas-pro'); ?></h2>
            
            <div class="recipe-selector">
                <label for="recipe-select"><?php _e('Select Recipe:', 'culinary-canvas-pro'); ?></label>
                <select id="recipe-select">
                    <option value=""><?php _e('Choose a recipe...', 'culinary-canvas-pro'); ?></option>
                    <?php foreach ($recipes as $recipe) : ?>
                        <option value="<?php echo esc_attr($recipe->ID); ?>">
                            <?php echo esc_html($recipe->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="recipe-ingredients-cost" style="display: none;">
                <h3><?php _e('Ingredient Costs', 'culinary-canvas-pro'); ?></h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Ingredient', 'culinary-canvas-pro'); ?></th>
                            <th><?php _e('Amount', 'culinary-canvas-pro'); ?></th>
                            <th><?php _e('Unit Cost', 'culinary-canvas-pro'); ?></th>
                            <th><?php _e('Total Cost', 'culinary-canvas-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="recipe-ingredients-list"></tbody>
                    <tfoot>
                        <tr class="total-row">
                            <th colspan="3"><?php _e('Total Recipe Cost:', 'culinary-canvas-pro'); ?></th>
                            <th id="total-recipe-cost">
                                <?php echo esc_html($currency_symbol); ?>0.00
                            </th>
                        </tr>
                        <tr class="total-row">
                            <th colspan="3"><?php _e('Cost per Serving:', 'culinary-canvas-pro'); ?></th>
                            <th id="cost-per-serving">
                                <?php echo esc_html($currency_symbol); ?>0.00
                            </th>
                        </tr>
                    </tfoot>
                </table>

                <div class="cost-breakdown">
                    <h3><?php _e('Cost Breakdown', 'culinary-canvas-pro'); ?></h3>
                    <div id="cost-breakdown-content"></div>
                    <div class="cost-chart-container">
                        <canvas id="cost-breakdown-chart"></canvas>
                    </div>
                </div>

                <div class="recipe-cost-actions">
                    <button type="button" class="button button-primary" id="save-recipe-cost">
                        <?php _e('Save Recipe Cost', 'culinary-canvas-pro'); ?>
                    </button>
                    <button type="button" class="button" id="print-cost-analysis">
                        <?php _e('Print Analysis', 'culinary-canvas-pro'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>