<?php
if (!defined('ABSPATH')) {
    exit;
}

class CulinaryCanvas_Meal_Planner {
    public function __construct() {
        add_action('init', array($this, 'register_meal_plan_post_type'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_meal_planner_scripts'));
        add_action('wp_ajax_save_meal_plan', array($this, 'save_meal_plan'));
        add_action('wp_ajax_get_recipe_details', array($this, 'get_recipe_details'));
    }

    public function register_meal_plan_post_type() {
        $args = array(
            'public' => false,
            'show_ui' => false,
            'capability_type' => 'post',
            'supports' => array('title', 'author'),
        );
        
        register_post_type('meal_plan', $args);
    }

    public function enqueue_meal_planner_scripts($hook) {
        if ('recipe_page_meal-planner' !== $hook) {
            return;
        }

        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('jquery-ui-draggable');
        wp_enqueue_script('jquery-ui-droppable');
        wp_enqueue_style('jquery-ui-css', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css');
        
        wp_enqueue_script(
            'meal-planner',
            CCP_PLUGIN_URL . 'assets/js/meal-planner.js',
            array('jquery', 'jquery-ui-datepicker', 'jquery-ui-draggable', 'jquery-ui-droppable'),
            CCP_VERSION,
            true
        );

        wp_localize_script('meal-planner', 'mealPlannerData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('meal_planner_nonce')
        ));
    }

    public function render_meal_planner_page() {
        $recipes = get_posts(array(
            'post_type' => 'recipe',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        $start_date = new DateTime('monday this week');
        ?>
        <div class="wrap">
            <h1><?php _e('Meal Planner', 'culinary-canvas-pro'); ?></h1>
            
            <div class="meal-planner-container">
                <div class="meal-planner-sidebar">
                    <h3><?php _e('Available Recipes', 'culinary-canvas-pro'); ?></h3>
                    <input type="text" id="recipe-search" placeholder="<?php esc_attr_e('Search recipes...', 'culinary-canvas-pro'); ?>">
                    
                    <div class="recipe-list">
                        <?php foreach ($recipes as $recipe) : ?>
                            <div class="recipe-item" data-recipe-id="<?php echo esc_attr($recipe->ID); ?>">
                                <?php echo esc_html($recipe->post_title); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="meal-planner-calendar">
                    <div class="calendar-navigation">
                        <button class="prev-week">&lt; <?php _e('Previous Week', 'culinary-canvas-pro'); ?></button>
                        <span class="current-week"></span>
                        <button class="next-week"><?php _e('Next Week', 'culinary-canvas-pro'); ?> &gt;</button>
                    </div>

                    <table class="meal-calendar">
                        <thead>
                            <tr>
                                <th><?php _e('Time', 'culinary-canvas-pro'); ?></th>
                                <?php
                                for ($i = 0; $i < 7; $i++) {
                                    $date = clone $start_date;
                                    $date->modify("+$i days");
                                    echo '<th data-date="' . $date->format('Y-m-d') . '">' 
                                        . $date->format('l') . '<br>' 
                                        . $date->format('M j') . '</th>';
                                }
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $meal_times = array('breakfast', 'lunch', 'dinner');
                            foreach ($meal_times as $meal) :
                            ?>
                                <tr>
                                    <td class="meal-time"><?php echo ucfirst($meal); ?></td>
                                    <?php
                                    for ($i = 0; $i < 7; $i++) {
                                        $date = clone $start_date;
                                        $date->modify("+$i days");
                                        echo '<td class="meal-slot" data-date="' . $date->format('Y-m-d') . 
                                             '" data-meal="' . $meal . '"></td>';
                                    }
                                    ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="meal-plan-actions">
                <button class="button button-primary" id="save-meal-plan">
                    <?php _e('Save Meal Plan', 'culinary-canvas-pro'); ?>
                </button>
                <button class="button" id="print-meal-plan">
                    <?php _e('Print Meal Plan', 'culinary-canvas-pro'); ?>
                </button>
                <button class="button" id="generate-shopping-list">
                    <?php _e('Generate Shopping List', 'culinary-canvas-pro'); ?>
                </button>
            </div>

            <div id="shopping-list-modal" class="modal" style="display: none;">
                <div class="modal-content">
                    <h3><?php _e('Shopping List', 'culinary-canvas-pro'); ?></h3>
                    <div id="shopping-list-content"></div>
                    <button class="button" id="print-shopping-list">
                        <?php _e('Print Shopping List', 'culinary-canvas-pro'); ?>
                    </button>
                    <button class="button modal-close">
                        <?php _e('Close', 'culinary-canvas-pro'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    public function save_meal_plan() {
        check_ajax_referer('meal_planner_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permission denied');
            return;
        }

        $meal_plan = isset($_POST['meal_plan']) ? json_decode(stripslashes($_POST['meal_plan']), true) : array();
        $user_id = get_current_user_id();
        
        // Save meal plan
        update_user_meta($user_id, 'current_meal_plan', $meal_plan);
        
        wp_send_json_success(array(
            'message' => __('Meal plan saved successfully', 'culinary-canvas-pro')
        ));
    }

    public function get_recipe_details() {
        check_ajax_referer('meal_planner_nonce', 'nonce');

        $recipe_id = isset($_POST['recipe_id']) ? intval($_POST['recipe_id']) : 0;
        
        if (!$recipe_id) {
            wp_send_json_error('Invalid recipe ID');
            return;
        }

        $recipe = get_post($recipe_id);
        $ingredients = get_post_meta($recipe_id, '_ingredients', true);
        
        wp_send_json_success(array(
            'title' => $recipe->post_title,
            'ingredients' => $ingredients
        ));
    }
}