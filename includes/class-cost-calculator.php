<?php
if (!defined('ABSPATH')) {
    exit;
}

class CulinaryCanvas_Cost_Calculator {
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_cost_meta_box'));
        add_action('save_post_recipe', array($this, 'save_cost_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_cost_calculator_scripts'));
        $this->register_ajax_handlers();
    }

    public function enqueue_cost_calculator_scripts($hook) {
        if ('recipe_page_cost-calculator' !== $hook) {
            return;
        }

        wp_enqueue_style('cost-calculator-style', CCP_PLUGIN_URL . 'assets/css/cost-calculator-style.css', array(), CCP_VERSION);
        wp_enqueue_script('cost-calculator', CCP_PLUGIN_URL . 'assets/js/cost-calculator.js', array('jquery'), CCP_VERSION, true);

        wp_localize_script('cost-calculator', 'costCalculatorData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cost_calculator_nonce'),
            'currency' => get_option('culinary_canvas_settings')['currency_symbol'] ?? '$'
        ));
    }

    public function add_cost_meta_box() {
        add_meta_box(
            'recipe_cost',
            __('Recipe Cost', 'culinary-canvas-pro'),
            array($this, 'render_cost_meta_box'),
            'recipe',
            'side',
            'default'
        );
    }

    public function render_cost_meta_box($post) {
        wp_nonce_field('recipe_cost_meta_box', 'recipe_cost_meta_box_nonce');

        $total_cost = get_post_meta($post->ID, '_recipe_total_cost', true);
        $cost_per_serving = get_post_meta($post->ID, '_recipe_cost_per_serving', true);
        $currency_symbol = get_option('culinary_canvas_settings')['currency_symbol'] ?? '$';
        ?>
        <div class="recipe-cost-details">
            <p>
                <strong><?php _e('Total Cost:', 'culinary-canvas-pro'); ?></strong>
                <?php echo esc_html($currency_symbol . ($total_cost ? number_format($total_cost, 2) : '0.00')); ?>
            </p>
            <p>
                <strong><?php _e('Cost per Serving:', 'culinary-canvas-pro'); ?></strong>
                <?php echo esc_html($currency_symbol . ($cost_per_serving ? number_format($cost_per_serving, 2) : '0.00')); ?>
            </p>
            <button type="button" class="button" id="update-costs">
                <?php _e('Update Costs', 'culinary-canvas-pro'); ?>
            </button>
        </div>
        <?php
    }

    public function register_ajax_handlers() {
        add_action('wp_ajax_save_ingredient_cost', array($this, 'handle_save_ingredient_cost'));
        add_action('wp_ajax_delete_ingredient_cost', array($this, 'handle_delete_ingredient_cost'));
    }

    public function handle_save_ingredient_cost() {
        check_ajax_referer('cost_calculator_nonce', 'security');

        $name = sanitize_text_field($_POST['name']);
        $unit = sanitize_text_field($_POST['unit']);
        $cost = floatval($_POST['cost']);
        $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : uniqid('ingredient_');

        $ingredient_costs = get_option('ingredient_costs', array());
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

    public function handle_delete_ingredient_cost() {
        check_ajax_referer('cost_calculator_nonce', 'security');

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

    public function ajax_calculate_recipe_cost() {
        check_ajax_referer('cost_calculator_nonce', 'nonce');
        
        $recipe_id = isset($_POST['recipe_id']) ? intval($_POST['recipe_id']) : 0;
        if (!$recipe_id) {
            wp_send_json_error('Invalid recipe ID');
            return;
        }

        $ingredients = get_post_meta($recipe_id, '_ingredients', true);
        $servings = get_post_meta($recipe_id, '_servings', true);
        $ingredient_costs = get_option('ingredient_costs', array());

        if (empty($ingredients) || !is_array($ingredients)) {
            wp_send_json_error('No ingredients found');
            return;
        }

        $total_cost = 0;
        $ingredient_breakdown = array();

        foreach ($ingredients as $ingredient) {
            // Parse ingredient text
            preg_match('/^([\d.]+)\s*(\w+)\s+(.+)$/', $ingredient, $matches);
            if (count($matches) < 4) continue;

            $quantity = floatval($matches[1]);
            $unit = $matches[2];
            $name = $matches[3];

            // Find matching ingredient
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

        // Update recipe cost meta
        update_post_meta($recipe_id, '_recipe_total_cost', $total_cost);
        update_post_meta($recipe_id, '_recipe_cost_per_serving', $cost_per_serving);

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
            ),
            'tbsp' => array(
                'ml' => 15,
                'tsp' => 3
            ),
            'tsp' => array(
                'ml' => 5,
                'tbsp' => 0.333
            ),
            'cup' => array(
                'ml' => 240,
                'tbsp' => 16
            )
        );

        if (isset($conversion_rates[$from_unit][$to_unit])) {
            return $quantity * $conversion_rates[$from_unit][$to_unit];
        }

        return $quantity;
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