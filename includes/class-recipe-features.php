<?php
if (!defined('ABSPATH')) {
    exit;
}

class CulinaryCanvas_Recipe_Features {
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_features_meta_box'));
        add_action('save_post_recipe', array($this, 'save_features_meta'));
    }

    public function add_features_meta_box() {
        add_meta_box(
            'recipe_features',
            __('Recipe Features', 'culinary-canvas-pro'),
            array($this, 'render_features_meta_box'),
            'recipe',
            'side',
            'default'
        );
    }

    public function render_features_meta_box($post) {
        wp_nonce_field('recipe_features_meta_box', 'recipe_features_meta_box_nonce');

        $features = get_post_meta($post->ID, '_recipe_features', true);
        $dietary_restrictions = get_post_meta($post->ID, '_dietary_restrictions', true);
        $cooking_method = get_post_meta($post->ID, '_cooking_method', true);
        
        ?>
        <p>
            <label for="cooking_method"><?php _e('Cooking Method:', 'culinary-canvas-pro'); ?></label>
            <select id="cooking_method" name="cooking_method">
                <option value=""><?php _e('Select Method', 'culinary-canvas-pro'); ?></option>
                <option value="baking" <?php selected($cooking_method, 'baking'); ?>><?php _e('Baking', 'culinary-canvas-pro'); ?></option>
                <option value="grilling" <?php selected($cooking_method, 'grilling'); ?>><?php _e('Grilling', 'culinary-canvas-pro'); ?></option>
                <option value="stovetop" <?php selected($cooking_method, 'stovetop'); ?>><?php _e('Stovetop', 'culinary-canvas-pro'); ?></option>
                <option value="slowcooker" <?php selected($cooking_method, 'slowcooker'); ?>><?php _e('Slow Cooker', 'culinary-canvas-pro'); ?></option>
                <option value="instant_pot" <?php selected($cooking_method, 'instant_pot'); ?>><?php _e('Instant Pot', 'culinary-canvas-pro'); ?></option>
            </select>
        </p>

        <p><?php _e('Dietary Restrictions:', 'culinary-canvas-pro'); ?></p>
        <label>
            <input type="checkbox" name="dietary_restrictions[]" value="vegetarian" 
                <?php checked(is_array($dietary_restrictions) && in_array('vegetarian', $dietary_restrictions)); ?>>
            <?php _e('Vegetarian', 'culinary-canvas-pro'); ?>
        </label><br>
        <label>
            <input type="checkbox" name="dietary_restrictions[]" value="vegan"
                <?php checked(is_array($dietary_restrictions) && in_array('vegan', $dietary_restrictions)); ?>>
            <?php _e('Vegan', 'culinary-canvas-pro'); ?>
        </label><br>
        <label>
            <input type="checkbox" name="dietary_restrictions[]" value="gluten_free"
                <?php checked(is_array($dietary_restrictions) && in_array('gluten_free', $dietary_restrictions)); ?>>
            <?php _e('Gluten Free', 'culinary-canvas-pro'); ?>
        </label><br>
        <label>
            <input type="checkbox" name="dietary_restrictions[]" value="dairy_free"
                <?php checked(is_array($dietary_restrictions) && in_array('dairy_free', $dietary_restrictions)); ?>>
            <?php _e('Dairy Free', 'culinary-canvas-pro'); ?>
        </label>

        <p><?php _e('Recipe Features:', 'culinary-canvas-pro'); ?></p>
        <label>
            <input type="checkbox" name="recipe_features[]" value="quick_easy"
                <?php checked(is_array($features) && in_array('quick_easy', $features)); ?>>
            <?php _e('Quick & Easy', 'culinary-canvas-pro'); ?>
        </label><br>
        <label>
            <input type="checkbox" name="recipe_features[]" value="make_ahead"
                <?php checked(is_array($features) && in_array('make_ahead', $features)); ?>>
            <?php _e('Make Ahead', 'culinary-canvas-pro'); ?>
        </label><br>
        <label>
            <input type="checkbox" name="recipe_features[]" value="freezer_friendly"
                <?php checked(is_array($features) && in_array('freezer_friendly', $features)); ?>>
            <?php _e('Freezer Friendly', 'culinary-canvas-pro'); ?>
        </label>
        <?php
    }

    public function save_features_meta($post_id) {
        if (!isset($_POST['recipe_features_meta_box_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['recipe_features_meta_box_nonce'], 'recipe_features_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save cooking method
        if (isset($_POST['cooking_method'])) {
            update_post_meta($post_id, '_cooking_method', sanitize_text_field($_POST['cooking_method']));
        }

        // Save dietary restrictions
        $dietary_restrictions = isset($_POST['dietary_restrictions']) ? array_map('sanitize_text_field', $_POST['dietary_restrictions']) : array();
        update_post_meta($post_id, '_dietary_restrictions', $dietary_restrictions);

        // Save recipe features
        $features = isset($_POST['recipe_features']) ? array_map('sanitize_text_field', $_POST['recipe_features']) : array();
        update_post_meta($post_id, '_recipe_features', $features);
    }
}