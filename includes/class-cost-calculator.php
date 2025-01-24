<?php
if (!defined('ABSPATH')) {
    exit;
}

class CulinaryCanvas_Cost_Calculator {
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_cost_meta_box'));
        add_action('save_post_recipe', array($this, 'save_cost_meta'));
        add_action('wp_ajax_calculate_recipe_cost', array($this, 'calculate_recipe_cost'));
        add_action('wp_ajax_save_ingredient_cost', array($this, 'save_ingredient_cost'));
        add_action('wp_ajax_delete_ingredient_cost', array($this, 'delete_ingredient_cost'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_cost_calculator_scripts'));
    }

    public function enqueue_cost_calculator_scripts($hook) {
        if ('recipe_page_cost-calculator' !== $hook) {
            return;
        }

        wp_enqueue_script(
            'cost-calculator',
            CCP_PLUGIN_URL . 'assets/js/cost-calculator.js',
            array('jquery'),
            CCP_VERSION,
            true
        );

        wp_localize_script('cost-calculator', 'costCalculatorData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cost_calculator_nonce'),
            'currency' => get_option('culinary_canvas_settings')['currency_symbol'] ?? '$'
        ));
    }

    public function render_cost_calculator_page() {
        $recipes = get_posts(array(
            'post_type' => 'recipe',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        $currency_symbol = get_option('culinary_canvas_settings')['currency_symbol'] ?? '$';
        $ingredient_costs = get_option('ingredient_costs', array());
        ?>
        <div class="wrap">
            <h1><?php _e('Recipe Cost Calculator', 'culinary-canvas-pro'); ?></h1>

            <div class="cost-calculator-container">
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
                                    <input type="text" id="new-ingredient-name" placeholder="<?php esc_attr_e('Ingredient name', 'culinary-canvas-pro'); ?>">
                                </td>
                                <td>
                                    <select id="new-ingredient-unit">
                                        <option value="g"><?php _e('Grams (g)', 'culinary-canvas-pro'); ?></option>
                                        <option value="kg"><?php _e('Kilograms (kg)', 'culinary-canvas-pro'); ?></option>
                                        <option value="ml"><?php _e('Milliliters (ml)', 'culinary-canvas-pro'); ?></option>
                                        <option value="l"><?php _e('Liters (l)', 'culinary-canvas-pro'); ?></option>
                                        <option value="piece"><?php _e('Piece', 'culinary-canvas-pro'); ?></option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" id="new-ingredient-cost" step="0.01" min="0" 
                                           placeholder="<?php esc_attr_e('Cost', 'culinary-canvas-pro'); ?>">
                                </td>
                                <td>
                                    <button type="button" class="button" id="add-ingredient-cost">
                                        <?php _e('Add', 'culinary-canvas-pro'); ?>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

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
                                <tr>
                                    <th colspan="3"><?php _e('Total Recipe Cost:', 'culinary-canvas-pro'); ?></th>
                                    <th id="total-recipe-cost">0.00</th>
                                </tr>
                                <tr>
                                    <th colspan="3"><?php _e('Cost per Serving:', 'culinary-canvas-pro'); ?></th>
                                    <th id="cost-per-serving">0.00</th>
                                </tr>
                            </tfoot>
                        </table>
                        <button type="button" class="button button-primary" id="save-recipe-cost">
                            <?php _e('Save Recipe Cost', 'culinary-canvas-pro'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function calculate_recipe_cost() {
        check_ajax_referer('cost_calculator_nonce', 'nonce');

        $recipe_id = isset($_POST['recipe_id']) ? intval($_POST['recipe_id']) : 0;
        if (!$recipe_id) {
            wp_send_json_error('Invalid recipe ID');
            return;
        }

        $ingredients = get_post_meta($recipe_id, '_ingredients', true);
        $servings = get_post_meta($recipe_id, '_servings', true);
        $ingredient_costs = get_option('ingredient_costs', array());

        $total_cost = 0;
        $ingredient_breakdown = array();

        foreach ($ingredients as $ingredient) {
            // Parse ingredient text to extract quantity and unit
            preg_match('/^([\d.]+)\s*(\w+)\s+(.+)$/', $ingredient, $matches);
            if (count($matches) < 4) continue;

            $quantity = floatval($matches[1]);
            $unit = $matches[2];
            $name = $matches[3];

            // Find matching ingredient in cost database
            $cost_data = $this->find_matching_ingredient($name, $ingredient_costs);
            if (!$cost_data) continue;

            // Convert units if necessary
            $converted_quantity = $this->convert_units($quantity, $unit, $cost_data['unit']);
            $item_cost = $converted_quantity * $cost_data['cost'];
            $total_cost += $item_cost;

            $ingredient_breakdown[] = array(
                'name' => $name,
                'quantity' => $quantity,
                'unit' => $unit,
                'unit_cost' => $cost_data['cost'],
                'total_cost' => $item_cost
            );
        }

        $cost_per_serving = $servings ? ($total_cost / $servings) : 0;

        wp_send_json_success(array(
            'total_cost' => $total_cost,
            'cost_per_serving' => $cost_per_serving,
            'ingredients' => $ingredient_breakdown
        ));
    }

    private function find_matching_ingredient($name, $ingredient_costs) {
        foreach ($ingredient_costs as $cost) {
            if (stripos($name, $cost['name']) !== false) {
                return $cost;
            }
        }
        return null;
    }

    private function convert_units($quantity, $from_unit, $to_unit) {
        $conversion_rates = array(
            'g' => array(
                'kg' => 0.001,
                'g' => 1
            ),
            'kg' => array(
                'g' => 1000,
                'kg' => 1
            ),
            'ml' => array(
                'l' => 0.001,
                'ml' => 1
            ),
            'l' => array(
                'ml' => 1000,
                'l' => 1
            )
        );

        if (isset($conversion_rates[$from_unit][$to_unit])) {
            return $quantity * $conversion_rates[$from_unit][$to_unit];
        }

        return $quantity;
    }

    public function save_ingredient_cost() {
        check_ajax_referer('cost_calculator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }

        $name = sanitize_text_field($_POST['name']);
        $unit = sanitize_text_field($_POST['unit']);
        $cost = floatval($_POST['cost']);

        $ingredient_costs = get_option('ingredient_costs', array());
        $id = uniqid('ingredient_');

        $ingredient_costs[$id] = array(
            'name' => $name,
            'unit' => $unit,
            'cost' => $cost
        );

        update_option('ingredient_costs', $ingredient_costs);

        wp_send_json_success(array(
            'id' => $id,
            'name' => $name,
            'unit' => $unit,
            'cost' => $cost
        ));
    }

    public function delete_ingredient_cost() {
        check_ajax_referer('cost_calculator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }

        $id = sanitize_text_field($_POST['id']);
        $ingredient_costs = get_option('ingredient_costs', array());

        if (isset($ingredient_costs[$id])) {
            unset($ingredient_costs[$id]);
            update_option('ingredient_costs', $ingredient_costs);
            wp_send_json_success();
        } else {
            wp_send_json_error('Ingredient not found');
        }
    }

    public function save_cost_meta($post_id) {
        if (!isset($_POST['recipe_cost_meta_box_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['recipe_cost_meta_box_nonce'], 'recipe_cost_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $total_cost = isset($_POST['recipe_total_cost']) ? floatval($_POST['recipe_total_cost']) : 0;
        $cost_per_serving = isset($_POST['recipe_cost_per_serving']) ? floatval($_POST['recipe_cost_per_serving']) : 0;

        update_post_meta($post_id, '_recipe_total_cost', $total_cost);
        update_post_meta($post_id, '_recipe_cost_per_serving', $cost_per_serving);
    }
}