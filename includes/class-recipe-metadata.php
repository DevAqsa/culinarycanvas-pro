<?php

if (!defined('ABSPATH')) {
    exit;
}

class CulinaryCanvas_Recipe_Metadata {
    private $meta_fields = array(
        'prep_time',
        'cook_time',
        'total_time',
        'servings',
        'calories',
        'ingredients',
        'instructions',
        'difficulty_level',
        'cuisine_type'
    );

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_recipe_meta_boxes'));
        add_action('save_post_recipe', array($this, 'save_recipe_meta'));
        add_action('rest_api_init', array($this, 'register_meta_rest_api'));
    }

    public function add_recipe_meta_boxes() {
        add_meta_box(
            'recipe_details',
            __('Recipe Details', 'culinary-canvas-pro'),
            array($this, 'render_recipe_meta_box'),
            'recipe',
            'normal',
            'high'
        );
    }

    public function render_recipe_meta_box($post) {
        wp_nonce_field('recipe_meta_box', 'recipe_meta_box_nonce');

        // Get existing values
        $prep_time = get_post_meta($post->ID, '_prep_time', true);
        $cook_time = get_post_meta($post->ID, '_cook_time', true);
        $servings = get_post_meta($post->ID, '_servings', true);
        $calories = get_post_meta($post->ID, '_calories', true);
        $ingredients = get_post_meta($post->ID, '_ingredients', true);
        $instructions = get_post_meta($post->ID, '_instructions', true);
        $difficulty = get_post_meta($post->ID, '_difficulty_level', true);
        $cuisine = get_post_meta($post->ID, '_cuisine_type', true);

        ?>
        <div class="recipe-meta-box">
            <p>
                <label for="prep_time"><?php _e('Preparation Time (minutes):', 'culinary-canvas-pro'); ?></label>
                <input type="number" id="prep_time" name="prep_time" value="<?php echo esc_attr($prep_time); ?>">
            </p>

            <p>
                <label for="cook_time"><?php _e('Cooking Time (minutes):', 'culinary-canvas-pro'); ?></label>
                <input type="number" id="cook_time" name="cook_time" value="<?php echo esc_attr($cook_time); ?>">
            </p>

            <p>
                <label for="servings"><?php _e('Number of Servings:', 'culinary-canvas-pro'); ?></label>
                <input type="number" id="servings" name="servings" value="<?php echo esc_attr($servings); ?>">
            </p>

            <p>
                <label for="calories"><?php _e('Calories per Serving:', 'culinary-canvas-pro'); ?></label>
                <input type="number" id="calories" name="calories" value="<?php echo esc_attr($calories); ?>">
            </p>

            <div class="recipe-ingredients">
                <label><?php _e('Ingredients:', 'culinary-canvas-pro'); ?></label>
                <div class="ingredients-container">
                    <?php
                    $ingredients = !empty($ingredients) ? $ingredients : array('');
                    foreach ($ingredients as $index => $ingredient) {
                        ?>
                        <div class="ingredient-row">
                            <input type="text" name="ingredients[]" value="<?php echo esc_attr($ingredient); ?>" class="regular-text">
                            <button type="button" class="remove-ingredient button"><?php _e('Remove', 'culinary-canvas-pro'); ?></button>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <button type="button" class="add-ingredient button"><?php _e('Add Ingredient', 'culinary-canvas-pro'); ?></button>
            </div>

            <div class="recipe-instructions">
                <label for="instructions"><?php _e('Instructions:', 'culinary-canvas-pro'); ?></label>
                <?php
                $settings = array(
                    'textarea_name' => 'instructions',
                    'textarea_rows' => 10,
                    'media_buttons' => false,
                );
                wp_editor($instructions, 'instructions', $settings);
                ?>
            </div>

            <p>
                <label for="difficulty_level"><?php _e('Difficulty Level:', 'culinary-canvas-pro'); ?></label>
                <select id="difficulty_level" name="difficulty_level">
                    <option value=""><?php _e('Select Difficulty', 'culinary-canvas-pro'); ?></option>
                    <option value="easy" <?php selected($difficulty, 'easy'); ?>><?php _e('Easy', 'culinary-canvas-pro'); ?></option>
                    <option value="medium" <?php selected($difficulty, 'medium'); ?>><?php _e('Medium', 'culinary-canvas-pro'); ?></option>
                    <option value="hard" <?php selected($difficulty, 'hard'); ?>><?php _e('Hard', 'culinary-canvas-pro'); ?></option>
                </select>
            </p>

            <p>
                <label for="cuisine_type"><?php _e('Cuisine Type:', 'culinary-canvas-pro'); ?></label>
                <input type="text" id="cuisine_type" name="cuisine_type" value="<?php echo esc_attr($cuisine); ?>" class="regular-text">
            </p>
        </div>
        <?php
    }

    public function save_recipe_meta($post_id) {
        // Check if our nonce is set
        if (!isset($_POST['recipe_meta_box_nonce'])) {
            return;
        }

        // Verify the nonce
        if (!wp_verify_nonce($_POST['recipe_meta_box_nonce'], 'recipe_meta_box')) {
            return;
        }

        // If this is an autosave, don't do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save each meta field
        foreach ($this->meta_fields as $field) {
            if (isset($_POST[$field])) {
                $value = '';
                
                if ($field === 'ingredients') {
                    $value = array_filter($_POST[$field]); // Remove empty values
                } elseif ($field === 'instructions') {
                    $value = wp_kses_post($_POST[$field]);
                } else {
                    $value = sanitize_text_field($_POST[$field]);
                }
                
                update_post_meta($post_id, '_' . $field, $value);
            }
        }

        // Calculate and save total time
        $prep_time = isset($_POST['prep_time']) ? intval($_POST['prep_time']) : 0;
        $cook_time = isset($_POST['cook_time']) ? intval($_POST['cook_time']) : 0;
        $total_time = $prep_time + $cook_time;
        update_post_meta($post_id, '_total_time', $total_time);
    }

    public function register_meta_rest_api() {
        foreach ($this->meta_fields as $field) {
            register_rest_field('recipe',
                $field,
                array(
                    'get_callback' => function($object) use ($field) {
                        return get_post_meta($object['id'], '_' . $field, true);
                    },
                    'update_callback' => function($value, $object) use ($field) {
                        update_post_meta($object->ID, '_' . $field, $value);
                    },
                    'schema' => array(
                        'description' => __('Recipe ' . $field, 'culinary-canvas-pro'),
                        'type' => 'string',
                        'context' => array('view', 'edit')
                    )
                )
            );
        }
    }
}