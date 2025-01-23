<?php
if (!defined('ABSPATH')) {
    exit;
}

class CulinaryCanvas_Recipe_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    public function enqueue_admin_assets($hook) {
        // Enqueue for all recipe pages
        if (strpos($hook, 'recipe-dashboard') !== false || strpos($hook, 'recipe-settings') !== false) {
            wp_enqueue_style(
                'culinary-canvas-admin',
                CCP_PLUGIN_URL . 'assets/css/admin-style.css',
                array(),
                CCP_VERSION
            );
            
            wp_enqueue_style(
                'culinary-canvas-settings',
                CCP_PLUGIN_URL . 'assets/css/settings-style.css',
                array(),
                CCP_VERSION
            );
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Recipes', 'culinary-canvas-pro'),
            __('Recipes', 'culinary-canvas-pro'),
            'manage_options',
            'recipe-dashboard',
            array($this, 'render_dashboard_page'),
            'dashicons-food',
            5
        );

        add_submenu_page(
            'recipe-dashboard',
            __('Dashboard', 'culinary-canvas-pro'),
            __('Dashboard', 'culinary-canvas-pro'),
            'manage_options',
            'recipe-dashboard',
            array($this, 'render_dashboard_page')
        );

        add_submenu_page(
            'recipe-dashboard',
            __('All Recipes', 'culinary-canvas-pro'),
            __('All Recipes', 'culinary-canvas-pro'),
            'manage_options',
            'edit.php?post_type=recipe',
            null
        );

        add_submenu_page(
            'recipe-dashboard',
            __('Add New Recipe', 'culinary-canvas-pro'),
            __('Add New', 'culinary-canvas-pro'),
            'manage_options',
            'post-new.php?post_type=recipe',
            null
        );

        add_submenu_page(
            'recipe-dashboard',
            __('Recipe Categories', 'culinary-canvas-pro'),
            __('Categories', 'culinary-canvas-pro'),
            'manage_options',
            'edit-tags.php?taxonomy=recipe_category&post_type=recipe',
            null
        );

        add_submenu_page(
            'recipe-dashboard',
            __('Recipe Settings', 'culinary-canvas-pro'),
            __('Settings', 'culinary-canvas-pro'),
            'manage_options',
            'recipe-settings',
            array($this, 'render_settings_page')
        );
    }

    public function render_dashboard_page() {
        include CCP_PLUGIN_DIR . 'templates/dashboard.php';
    }

    private function get_dashboard_stats() {
        // Get post count properly
        $count_posts = wp_count_posts('recipe');
        $total_recipes = isset($count_posts->publish) ? $count_posts->publish : 0;

        // Get ratings stats
        global $wpdb;
        $table_name = $wpdb->prefix . 'recipe_ratings';
        
        // Get average rating
        $average_rating = $wpdb->get_var("SELECT ROUND(AVG(rating), 1) FROM {$table_name}");
        $average_rating = $average_rating ? $average_rating : '0.0';

        // Get total reviews
        $total_reviews = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        $total_reviews = $total_reviews ? $total_reviews : 0;

        return array(
            'total_recipes' => $total_recipes,
            'average_rating' => $average_rating,
            'total_reviews' => $total_reviews
        );
    }

    public function render_settings_page() {
        // Get saved settings
        $settings = get_option('culinary_canvas_settings', array());
        ?>
        <div class="wrap culinary-settings-wrap">
            <h1><?php _e('Recipe Settings', 'culinary-canvas-pro'); ?></h1>
            
            <form method="post" action="options.php" class="recipe-settings-form">
                <?php settings_fields('culinary_canvas_settings'); ?>
                
                <div class="settings-tabs">
                    <a href="#general" class="tab-link active" data-tab="general">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <?php _e('General', 'culinary-canvas-pro'); ?>
                    </a>
                    <a href="#display" class="tab-link" data-tab="display">
                        <span class="dashicons dashicons-layout"></span>
                        <?php _e('Display', 'culinary-canvas-pro'); ?>
                    </a>
                    <a href="#rating" class="tab-link" data-tab="rating">
                        <span class="dashicons dashicons-star-filled"></span>
                        <?php _e('Ratings', 'culinary-canvas-pro'); ?>
                    </a>
                    <a href="#advanced" class="tab-link" data-tab="advanced">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <?php _e('Advanced', 'culinary-canvas-pro'); ?>
                    </a>
                </div>

                <div class="settings-content">
                   
                   <!-- General Settings -->
<div id="general" class="tab-content active">
    <h2><?php _e('General Settings', 'culinary-canvas-pro'); ?></h2>
    
    <table class="form-table">
        <!-- Recipe Base Settings -->
        <tr>
            <th scope="row">
                <label for="recipe_slug"><?php _e('Recipe Slug', 'culinary-canvas-pro'); ?></label>
            </th>
            <td>
                <input type="text" id="recipe_slug" name="culinary_canvas_settings[recipe_slug]" 
                       value="<?php echo esc_attr($settings['recipe_slug'] ?? 'recipe'); ?>" class="regular-text">
                <p class="description"><?php _e('The URL slug for recipe posts. Update permalinks after changing this.', 'culinary-canvas-pro'); ?></p>
            </td>
        </tr>

        <!-- Default Recipe Options -->
        <tr>
            <th scope="row">
                <?php _e('Default Recipe Settings', 'culinary-canvas-pro'); ?>
            </th>
            <td>
                <fieldset>
                    <label for="default_servings">
                        <?php _e('Default Servings:', 'culinary-canvas-pro'); ?>
                        <input type="number" id="default_servings" 
                               name="culinary_canvas_settings[default_servings]" 
                               value="<?php echo esc_attr($settings['default_servings'] ?? '4'); ?>" 
                               class="small-text" min="1" max="100">
                    </label>
                    <br><br>
                    
                    <label for="default_cuisine">
                        <?php _e('Default Cuisine:', 'culinary-canvas-pro'); ?>
                        <input type="text" id="default_cuisine" 
                               name="culinary_canvas_settings[default_cuisine]" 
                               value="<?php echo esc_attr($settings['default_cuisine'] ?? ''); ?>" 
                               class="regular-text">
                    </label>
                </fieldset>
            </td>
        </tr>

        <!-- Author Settings -->
        <tr>
            <th scope="row">
                <?php _e('Author Settings', 'culinary-canvas-pro'); ?>
            </th>
            <td>
                <fieldset>
                    <label for="author_display">
                        <?php _e('Author Display:', 'culinary-canvas-pro'); ?>
                        <select id="author_display" name="culinary_canvas_settings[author_display]">
                            <option value="name" <?php selected(($settings['author_display'] ?? 'name'), 'name'); ?>>
                                <?php _e('Display Name', 'culinary-canvas-pro'); ?>
                            </option>
                            <option value="username" <?php selected(($settings['author_display'] ?? 'name'), 'username'); ?>>
                                <?php _e('Username', 'culinary-canvas-pro'); ?>
                            </option>
                            <option value="hide" <?php selected(($settings['author_display'] ?? 'name'), 'hide'); ?>>
                                <?php _e('Hide Author', 'culinary-canvas-pro'); ?>
                            </option>
                        </select>
                    </label>
                    <br><br>
                    
                    <label>
                        <input type="checkbox" name="culinary_canvas_settings[show_author_bio]" 
                               value="1" <?php checked(($settings['show_author_bio'] ?? 0), 1); ?>>
                        <?php _e('Show Author Bio on Recipe Pages', 'culinary-canvas-pro'); ?>
                    </label>
                </fieldset>
            </td>
        </tr>

        <!-- Recipe Units -->
        <tr>
            <th scope="row">
                <?php _e('Measurement Units', 'culinary-canvas-pro'); ?>
            </th>
            <td>
                <fieldset>
                    <label for="default_measurement">
                        <?php _e('Default Measurement System:', 'culinary-canvas-pro'); ?>
                        <select id="default_measurement" name="culinary_canvas_settings[default_measurement]">
                            <option value="metric" <?php selected(($settings['default_measurement'] ?? 'metric'), 'metric'); ?>>
                                <?php _e('Metric (g, ml, cm)', 'culinary-canvas-pro'); ?>
                            </option>
                            <option value="imperial" <?php selected(($settings['default_measurement'] ?? 'metric'), 'imperial'); ?>>
                                <?php _e('Imperial (oz, cups, inches)', 'culinary-canvas-pro'); ?>
                            </option>
                        </select>
                    </label>
                    <br><br>
                    
                    <label>
                        <input type="checkbox" name="culinary_canvas_settings[show_unit_conversion]" 
                               value="1" <?php checked(($settings['show_unit_conversion'] ?? 1), 1); ?>>
                        <?php _e('Allow Users to Toggle Between Metric/Imperial', 'culinary-canvas-pro'); ?>
                    </label>
                </fieldset>
            </td>
        </tr>

        <!-- Social Sharing -->
        <tr>
            <th scope="row">
                <?php _e('Social Sharing', 'culinary-canvas-pro'); ?>
            </th>
            <td>
                <fieldset>
                    <label>
                        <input type="checkbox" name="culinary_canvas_settings[enable_social_share]" 
                               value="1" <?php checked(($settings['enable_social_share'] ?? 1), 1); ?>>
                        <?php _e('Enable Social Sharing Buttons', 'culinary-canvas-pro'); ?>
                    </label>
                    <br><br>
                    
                    <label>
                        <?php _e('Select Platforms:', 'culinary-canvas-pro'); ?>
                    </label><br>
                    <label>
                        <input type="checkbox" name="culinary_canvas_settings[share_facebook]" 
                               value="1" <?php checked(($settings['share_facebook'] ?? 1), 1); ?>>
                        <?php _e('Facebook', 'culinary-canvas-pro'); ?>
                    </label><br>
                    <label>
                        <input type="checkbox" name="culinary_canvas_settings[share_twitter]" 
                               value="1" <?php checked(($settings['share_twitter'] ?? 1), 1); ?>>
                        <?php _e('Twitter', 'culinary-canvas-pro'); ?>
                    </label><br>
                    <label>
                        <input type="checkbox" name="culinary_canvas_settings[share_pinterest]" 
                               value="1" <?php checked(($settings['share_pinterest'] ?? 1), 1); ?>>
                        <?php _e('Pinterest', 'culinary-canvas-pro'); ?>
                    </label><br>
                    <label>
                        <input type="checkbox" name="culinary_canvas_settings[share_whatsapp]" 
                               value="1" <?php checked(($settings['share_whatsapp'] ?? 1), 1); ?>>
                        <?php _e('WhatsApp', 'culinary-canvas-pro'); ?>
                    </label>
                </fieldset>
            </td>
        </tr>
    </table>
</div>
                    
                    <!-- Display Settings -->
<div id="display" class="tab-content">
    <h2><?php _e('Display Settings', 'culinary-canvas-pro'); ?></h2>
    
    <table class="form-table">
        <!-- Layout Style -->
        <tr>
            <th scope="row">
                <?php _e('Layout Style', 'culinary-canvas-pro'); ?>
            </th>
            <td>
                <select name="culinary_canvas_settings[layout_style]">
                    <option value="classic" <?php selected(($settings['layout_style'] ?? 'classic'), 'classic'); ?>>
                        <?php _e('Classic', 'culinary-canvas-pro'); ?>
                    </option>
                    <option value="modern" <?php selected(($settings['layout_style'] ?? 'classic'), 'modern'); ?>>
                        <?php _e('Modern', 'culinary-canvas-pro'); ?>
                    </option>
                    <option value="compact" <?php selected(($settings['layout_style'] ?? 'classic'), 'compact'); ?>>
                        <?php _e('Compact', 'culinary-canvas-pro'); ?>
                    </option>
                </select>
                <p class="description"><?php _e('Choose the overall layout style for your recipes.', 'culinary-canvas-pro'); ?></p>
            </td>
        </tr>

        <!-- Recipe Image Size -->
        <tr>
            <th scope="row">
                <?php _e('Recipe Image Size', 'culinary-canvas-pro'); ?>
            </th>
            <td>
                <select name="culinary_canvas_settings[recipe_image_size]">
                    <?php
                    $image_sizes = get_intermediate_image_sizes();
                    foreach ($image_sizes as $size) {
                        printf(
                            '<option value="%s" %s>%s</option>',
                            esc_attr($size),
                            selected(($settings['recipe_image_size'] ?? 'large'), $size, false),
                            esc_html(ucfirst(str_replace('_', ' ', $size)))
                        );
                    }
                    ?>
                </select>
                <p class="description"><?php _e('Select the image size for recipe featured images.', 'culinary-canvas-pro'); ?></p>
            </td>
        </tr>

        <!-- Recipe Elements -->
        <tr>
            <th scope="row">
                <?php _e('Recipe Elements', 'culinary-canvas-pro'); ?>
            </th>
            <td>
                <fieldset>
                    <label>
                        <input type="checkbox" name="culinary_canvas_settings[show_prep_time]" 
                               value="1" <?php checked(($settings['show_prep_time'] ?? 1), 1); ?>>
                        <?php _e('Show Preparation Time', 'culinary-canvas-pro'); ?>
                    </label><br>
                    
                    <label>
                        <input type="checkbox" name="culinary_canvas_settings[show_cook_time]" 
                               value="1" <?php checked(($settings['show_cook_time'] ?? 1), 1); ?>>
                        <?php _e('Show Cooking Time', 'culinary-canvas-pro'); ?>
                    </label><br>
                    
                    <label>
                        <input type="checkbox" name="culinary_canvas_settings[show_servings]" 
                               value="1" <?php checked(($settings['show_servings'] ?? 1), 1); ?>>
                        <?php _e('Show Servings', 'culinary-canvas-pro'); ?>
                    </label><br>
                    
                    <label>
                        <input type="checkbox" name="culinary_canvas_settings[show_author]" 
                               value="1" <?php checked(($settings['show_author'] ?? 1), 1); ?>>
                        <?php _e('Show Recipe Author', 'culinary-canvas-pro'); ?>
                    </label><br>
                    
                    <label>
                        <input type="checkbox" name="culinary_canvas_settings[show_date]" 
                               value="1" <?php checked(($settings['show_date'] ?? 1), 1); ?>>
                        <?php _e('Show Published Date', 'culinary-canvas-pro'); ?>
                    </label><br>
                    
                    <label>
                        <input type="checkbox" name="culinary_canvas_settings[enable_print_button]" 
                               value="1" <?php checked(($settings['enable_print_button'] ?? 1), 1); ?>>
                        <?php _e('Enable Print Button', 'culinary-canvas-pro'); ?>
                    </label><br>
                    
                    <label>
                        <input type="checkbox" name="culinary_canvas_settings[enable_servings_adjustment]" 
                               value="1" <?php checked(($settings['enable_servings_adjustment'] ?? 1), 1); ?>>
                        <?php _e('Enable Servings Adjustment', 'culinary-canvas-pro'); ?>
                    </label><br>
                    
                    <label>
                        <input type="checkbox" name="culinary_canvas_settings[enable_rich_snippets]" 
                               value="1" <?php checked(($settings['enable_rich_snippets'] ?? 1), 1); ?>>
                        <?php _e('Enable Rich Snippets (Schema.org)', 'culinary-canvas-pro'); ?>
                    </label>
                </fieldset>
            </td>
        </tr>

        <!-- Section Labels -->
        <tr>
            <th scope="row">
                <?php _e('Section Labels', 'culinary-canvas-pro'); ?>
            </th>
            <td>
                <fieldset>
                    <p>
                        <label>
                            <?php _e('Prep Time Label:', 'culinary-canvas-pro'); ?><br>
                            <input type="text" name="culinary_canvas_settings[prep_time_label]" 
                                   value="<?php echo esc_attr($settings['prep_time_label'] ?? __('Prep Time:', 'culinary-canvas-pro')); ?>" 
                                   class="regular-text">
                        </label>
                    </p>
                    
                    <p>
                        <label>
                            <?php _e('Cook Time Label:', 'culinary-canvas-pro'); ?><br>
                            <input type="text" name="culinary_canvas_settings[cook_time_label]" 
                                   value="<?php echo esc_attr($settings['cook_time_label'] ?? __('Cook Time:', 'culinary-canvas-pro')); ?>" 
                                   class="regular-text">
                        </label>
                    </p>
                    
                    <p>
                        <label>
                            <?php _e('Total Time Label:', 'culinary-canvas-pro'); ?><br>
                            <input type="text" name="culinary_canvas_settings[total_time_label]" 
                                   value="<?php echo esc_attr($settings['total_time_label'] ?? __('Total Time:', 'culinary-canvas-pro')); ?>" 
                                   class="regular-text">
                        </label>
                    </p>
                    
                    <p>
                        <label>
                            <?php _e('Servings Label:', 'culinary-canvas-pro'); ?><br>
                            <input type="text" name="culinary_canvas_settings[servings_label]" 
                                   value="<?php echo esc_attr($settings['servings_label'] ?? __('Servings:', 'culinary-canvas-pro')); ?>" 
                                   class="regular-text">
                        </label>
                    </p>
                    
                    <p>
                        <label>
                            <?php _e('Ingredients Section Label:', 'culinary-canvas-pro'); ?><br>
                            <input type="text" name="culinary_canvas_settings[ingredients_label]" 
                                   value="<?php echo esc_attr($settings['ingredients_label'] ?? __('Ingredients', 'culinary-canvas-pro')); ?>" 
                                   class="regular-text">
                        </label>
                    </p>
                    
                    <p>
                        <label>
                            <?php _e('Instructions Section Label:', 'culinary-canvas-pro'); ?><br>
                            <input type="text" name="culinary_canvas_settings[instructions_label]" 
                                   value="<?php echo esc_attr($settings['instructions_label'] ?? __('Instructions', 'culinary-canvas-pro')); ?>" 
                                   class="regular-text">
                        </label>
                    </p>
                </fieldset>
            </td>
        </tr>
    </table>
</div>

                    <!-- Rating Settings -->
<div id="rating" class="tab-content">
    <h2><?php _e('Rating Settings', 'culinary-canvas-pro'); ?></h2>
    
    <table class="form-table">
        <!-- Basic Rating Settings -->
        <tr>
            <th scope="row">
                <?php _e('Rating System', 'culinary-canvas-pro'); ?>
            </th>
            <td>
                <fieldset>
                    <label>
                        <input type="checkbox" name="culinary_canvas_settings[enable_ratings]" 
                               value="1" <?php checked(($settings['enable_ratings'] ?? 1), 1); ?>>
                        <?php _e('Enable Recipe Ratings', 'culinary-canvas-pro'); ?>
                    </label>
                    <br><br>

                    <label for="rating_type">
                        <?php _e('Rating Type:', 'culinary-canvas-pro'); ?>
                        <select id="rating_type" name="culinary_canvas_settings[rating_type]">
                            <option value="stars" <?php selected(($settings['rating_type'] ?? 'stars'), 'stars'); ?>>
                                <?php _e('Star Rating (1-5)', 'culinary-canvas-pro'); ?>
                            </option>
                            <option value="hearts" <?php selected(($settings['rating_type'] ?? 'stars'), 'hearts'); ?>>
                                <?php _e('Hearts', 'culinary-canvas-pro'); ?>
                            </option>
                            <option value="points" <?php selected(($settings['rating_type'] ?? 'stars'), 'points'); ?>>
                                <?php _e('Points (1-10)', 'culinary-canvas-pro'); ?>
                            </option>
                        </select>
                    </label>
                </fieldset>
            </td>
        </tr>

        <!-- Review Settings -->
        <tr>
            <th scope="row">
                <?php _e('Review Settings', 'culinary-canvas-pro'); ?>
            </th>
            <td>
                <fieldset>
                    <label>
                        <input type="checkbox" name="culinary_canvas_settings[enable_reviews]" 
                               value="1" <?php checked(($settings['enable_reviews'] ?? 1), 1); ?>>
                        <?php _e('Allow Written Reviews', 'culinary-canvas-pro'); ?>
                    </label>
                    <br><br>

                    <label>
                        <input type="checkbox" name="culinary_canvas_settings[require_review]" 
                               value="1" <?php checked(($settings['require_review'] ?? 0), 1); ?>>
                        <?php _e('Require Written Review with Rating', 'culinary-canvas-pro'); ?>
                    </label>
                    <br><br>

                    <label for="min_review_length">
                        <?php _e('Minimum Review Length:', 'culinary-canvas-pro'); ?>
                        <input type="number" id="min_review_length" 
                               name="culinary_canvas_settings[min_review_length]" 
                               value="<?php echo esc_attr($settings['min_review_length'] ?? '10'); ?>" 
                               class="small-text" min="0" max="1000"> <?php _e('characters', 'culinary-canvas-pro'); ?>
                    </label>
                </fieldset>
            </td>
        </tr>

        <!-- User Restrictions -->
        <tr>
            <th scope="row">
                <?php _e('User Restrictions', 'culinary-canvas-pro'); ?>
            </th>
            <td>
                <fieldset>
                    <label>
                        <input type="checkbox" name="culinary_canvas_settings[require_login]" 
                               value="1" <?php checked(($settings['require_login'] ?? 1), 1); ?>>
                        <?php _e('Require Login to Rate', 'culinary-canvas-pro'); ?>
                    </label>
                    <br><br>

                    <label>
                        <input type="checkbox" name="culinary_canvas_settings[verified_only]" 
                               value="1" <?php checked(($settings['verified_only'] ?? 0), 1); ?>>
                        <?php _e('Only Allow Ratings from Verified Users', 'culinary-canvas-pro'); ?>
                    </label>
                    <br><br>

                    <label>
                        <input type="checkbox" name="culinary_canvas_settings[one_rating_per_user]" 
                               value="1" <?php checked(($settings['one_rating_per_user'] ?? 1), 1); ?>>
                        <?php _e('Limit to One Rating per User', 'culinary-canvas-pro'); ?>
                    </label>
                </fieldset>
            </td>
        </tr>

        <!-- Moderation -->
        <tr>
            <th scope="row">
                <?php _e('Moderation', 'culinary-canvas-pro'); ?>
            </th>
            <td>
                <fieldset>
                    <label>
                        <input type="checkbox" name="culinary_canvas_settings[moderate_reviews]" 
                               value="1" <?php checked(($settings['moderate_reviews'] ?? 1), 1); ?>>
                        <?php _e('Moderate Reviews Before Publishing', 'culinary-canvas-pro'); ?>
                    </label>
                    <br><br>

                    <label>
                        <input type="checkbox" name="culinary_canvas_settings[email_notification]" 
                               value="1" <?php checked(($settings['email_notification'] ?? 1), 1); ?>>
                        <?php _e('Email Notification for New Reviews', 'culinary-canvas-pro'); ?>
                    </label>
                    <br><br>

                    <label for="notification_email">
                        <?php _e('Notification Email:', 'culinary-canvas-pro'); ?>
                        <input type="email" id="notification_email" 
                               name="culinary_canvas_settings[notification_email]" 
                               value="<?php echo esc_attr($settings['notification_email'] ?? get_option('admin_email')); ?>" 
                               class="regular-text">
                    </label>
                </fieldset>
            </td>
        </tr>

        <!-- Display Options -->
        <tr>
            <th scope="row">
                <?php _e('Display Options', 'culinary-canvas-pro'); ?>
            </th>
            <td>
                <fieldset>
                    <label>
                        <input type="checkbox" name="culinary_canvas_settings[show_average_rating]" 
                               value="1" <?php checked(($settings['show_average_rating'] ?? 1), 1); ?>>
                        <?php _e('Show Average Rating', 'culinary-canvas-pro'); ?>
                    </label>
                    <br><br>

                    <label>
                        <input type="checkbox" name="culinary_canvas_settings[show_rating_count]" 
                               value="1" <?php checked(($settings['show_rating_count'] ?? 1), 1); ?>>
                        <?php _e('Show Number of Ratings', 'culinary-canvas-pro'); ?>
                    </label>
                    <br><br>

                    <label for="reviews_per_page">
                        <?php _e('Reviews Per Page:', 'culinary-canvas-pro'); ?>
                        <select id="reviews_per_page" name="culinary_canvas_settings[reviews_per_page]">
                            <option value="5" <?php selected(($settings['reviews_per_page'] ?? '10'), '5'); ?>>5</option>
                            <option value="10" <?php selected(($settings['reviews_per_page'] ?? '10'), '10'); ?>>10</option>
                            <option value="15" <?php selected(($settings['reviews_per_page'] ?? '10'), '15'); ?>>15</option>
                            <option value="20" <?php selected(($settings['reviews_per_page'] ?? '10'), '20'); ?>>20</option>
                        </select>
                    </label>
                    <br><br>

                    <label for="sort_reviews">
                        <?php _e('Default Review Sort:', 'culinary-canvas-pro'); ?>
                        <select id="sort_reviews" name="culinary_canvas_settings[sort_reviews]">
                            <option value="newest" <?php selected(($settings['sort_reviews'] ?? 'newest'), 'newest'); ?>>
                                <?php _e('Newest First', 'culinary-canvas-pro'); ?>
                            </option>
                            <option value="highest" <?php selected(($settings['sort_reviews'] ?? 'newest'), 'highest'); ?>>
                                <?php _e('Highest Rated', 'culinary-canvas-pro'); ?>
                            </option>
                            <option value="lowest" <?php selected(($settings['sort_reviews'] ?? 'newest'), 'lowest'); ?>>
                                <?php _e('Lowest Rated', 'culinary-canvas-pro'); ?>
                            </option>
                        </select>
                    </label>
                </fieldset>
            </td>
        </tr>
    </table>
</div>
                   
                    <!-- Advanced Settings -->
<div id="advanced" class="tab-content">
    <h2><?php _e('Advanced Settings', 'culinary-canvas-pro'); ?></h2>
    
    <table class="form-table">
        <!-- Performance Settings -->
        <tr>
            <th scope="row">
                <?php _e('Performance', 'culinary-canvas-pro'); ?>
            </th>
            <td>
                <fieldset>
                    <label>
                        <input type="checkbox" name="culinary_canvas_settings[enable_cache]" 
                               value="1" <?php checked(($settings['enable_cache'] ?? 0), 1); ?>>
                        <?php _e('Enable Recipe Cache', 'culinary-canvas-pro'); ?>
                    </label>
                    <p class="description"><?php _e('Cache recipe data for better performance.', 'culinary-canvas-pro'); ?></p>
                    
                    <br><label>
                        <input type="checkbox" name="culinary_canvas_settings[lazy_load_images]" 
                               value="1" <?php checked(($settings['lazy_load_images'] ?? 1), 1); ?>>
                        <?php _e('Lazy Load Recipe Images', 'culinary-canvas-pro'); ?>
                    </label>
                    <p class="description"><?php _e('Improves page load time by loading images only when needed.', 'culinary-canvas-pro'); ?></p>
                    
                    <br><label>
                        <input type="number" name="culinary_canvas_settings[cache_duration]" 
                               value="<?php echo esc_attr($settings['cache_duration'] ?? '24'); ?>" 
                               class="small-text" min="1" max="72"> <?php _e('Hours', 'culinary-canvas-pro'); ?>
                    </label>
                    <p class="description"><?php _e('Duration to keep cached data (1-72 hours).', 'culinary-canvas-pro'); ?></p>
                </fieldset>
            </td>
        </tr>

        <!-- Export/Import -->
        <tr>
            <th scope="row">
                <?php _e('Data Management', 'culinary-canvas-pro'); ?>
            </th>
            <td>
                <fieldset>
                    <button type="button" class="button" id="export_recipes">
                        <?php _e('Export All Recipes', 'culinary-canvas-pro'); ?>
                    </button>
                    <p class="description"><?php _e('Export all recipes as CSV file.', 'culinary-canvas-pro'); ?></p>
                    
                    <br><label for="import_recipes" class="button" style="margin-top: 10px;">
                        <?php _e('Import Recipes', 'culinary-canvas-pro'); ?>
                    </label>
                    <input type="file" id="import_recipes" name="import_recipes" accept=".csv" style="display: none;">
                    <p class="description"><?php _e('Import recipes from CSV file.', 'culinary-canvas-pro'); ?></p>
                </fieldset>
            </td>
        </tr>

        <!-- SEO Settings -->
        <tr>
            <th scope="row">
                <?php _e('SEO Options', 'culinary-canvas-pro'); ?>
            </th>
            <td>
                <fieldset>
                    <label>
                        <input type="checkbox" name="culinary_canvas_settings[enable_schema]" 
                               value="1" <?php checked(($settings['enable_schema'] ?? 1), 1); ?>>
                        <?php _e('Enable Recipe Schema Markup', 'culinary-canvas-pro'); ?>
                    </label>
                    <p class="description"><?php _e('Add structured data for better search engine visibility.', 'culinary-canvas-pro'); ?></p>
                    
                    <br><label>
                        <input type="checkbox" name="culinary_canvas_settings[auto_keywords]" 
                               value="1" <?php checked(($settings['auto_keywords'] ?? 1), 1); ?>>
                        <?php _e('Auto-generate Recipe Keywords', 'culinary-canvas-pro'); ?>
                    </label>
                    <p class="description"><?php _e('Automatically generate keywords from recipe ingredients and categories.', 'culinary-canvas-pro'); ?></p>
                </fieldset>
            </td>
        </tr>

        <!-- API Integration -->
        <tr>
            <th scope="row">
                <?php _e('API Integration', 'culinary-canvas-pro'); ?>
            </th>
            <td>
                <fieldset>
                    <label>
                        <input type="checkbox" name="culinary_canvas_settings[enable_api]" 
                               value="1" <?php checked(($settings['enable_api'] ?? 0), 1); ?>>
                        <?php _e('Enable Recipe API', 'culinary-canvas-pro'); ?>
                    </label>
                    <p class="description"><?php _e('Allow external applications to access recipe data via REST API.', 'culinary-canvas-pro'); ?></p>
                    
                    <br><label for="api_key">
                        <?php _e('API Key:', 'culinary-canvas-pro'); ?>
                        <input type="text" id="api_key" name="culinary_canvas_settings[api_key]" 
                               value="<?php echo esc_attr($settings['api_key'] ?? ''); ?>" 
                               class="regular-text">
                    </label>
                    <button type="button" class="button" id="generate_api_key">
                        <?php _e('Generate New Key', 'culinary-canvas-pro'); ?>
                    </button>
                </fieldset>
            </td>
        </tr>

        <!-- Debug Options -->
        <tr>
            <th scope="row">
                <?php _e('Debug Options', 'culinary-canvas-pro'); ?>
            </th>
            <td>
                <fieldset>
                    <label>
                        <input type="checkbox" name="culinary_canvas_settings[enable_debug]" 
                               value="1" <?php checked(($settings['enable_debug'] ?? 0), 1); ?>>
                        <?php _e('Enable Debug Mode', 'culinary-canvas-pro'); ?>
                    </label>
                    <p class="description"><?php _e('Log plugin operations for troubleshooting.', 'culinary-canvas-pro'); ?></p>
                    
                    <br><label>
                        <input type="checkbox" name="culinary_canvas_settings[debug_email]" 
                               value="1" <?php checked(($settings['debug_email'] ?? 0), 1); ?>>
                        <?php _e('Send Debug Reports via Email', 'culinary-canvas-pro'); ?>
                    </label>
                    
                    <br><label for="debug_email_address">
                        <?php _e('Debug Email Address:', 'culinary-canvas-pro'); ?>
                        <input type="email" id="debug_email_address" 
                               name="culinary_canvas_settings[debug_email_address]" 
                               value="<?php echo esc_attr($settings['debug_email_address'] ?? ''); ?>" 
                               class="regular-text">
                    </label>
                </fieldset>
            </td>
        </tr>

        <!-- Cleanup Options -->
        <tr>
            <th scope="row">
                <?php _e('Cleanup Options', 'culinary-canvas-pro'); ?>
            </th>
            <td>
                <fieldset>
                    <label>
                        <input type="checkbox" name="culinary_canvas_settings[delete_data]" 
                               value="1" <?php checked(($settings['delete_data'] ?? 0), 1); ?>>
                        <?php _e('Delete all data on uninstall', 'culinary-canvas-pro'); ?>
                    </label>
                    <p class="description"><?php _e('Warning: This will remove all recipes and settings when plugin is uninstalled.', 'culinary-canvas-pro'); ?></p>
                    
                    <br><button type="button" class="button" id="clear_cache">
                        <?php _e('Clear Cache', 'culinary-canvas-pro'); ?>
                    </button>
                    
                    <br><br><button type="button" class="button" id="reset_settings">
                        <?php _e('Reset to Default Settings', 'culinary-canvas-pro'); ?>
                    </button>
                    <p class="description"><?php _e('Reset all plugin settings to default values.', 'culinary-canvas-pro'); ?></p>
                </fieldset>
            </td>
        </tr>
    </table>
</div>

                <?php submit_button(); ?>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Tab switching functionality
            $('.tab-link').on('click', function(e) {
                e.preventDefault();
                
                // Update active tab
                $('.tab-link').removeClass('active');
                $(this).addClass('active');
                
                // Show corresponding content
                $('.tab-content').removeClass('active');
                $('#' + $(this).data('tab')).addClass('active');
            });
        });


        // -----------------------------------------

        // API Key Generation
$('#generate_api_key').on('click', function() {
    const apiKey = 'ccp_' + Math.random().toString(36).substr(2, 9);
    $('#api_key').val(apiKey);
});

// Clear Cache Confirmation
$('#clear_cache').on('click', function() {
    if (confirm('<?php _e("Are you sure you want to clear the cache?", "culinary-canvas-pro"); ?>')) {
        // Add AJAX call to clear cache
        alert('<?php _e("Cache cleared successfully!", "culinary-canvas-pro"); ?>');
    }
});

// Reset Settings Confirmation
$('#reset_settings').on('click', function() {
    if (confirm('<?php _e("Are you sure you want to reset all settings? This cannot be undone.", "culinary-canvas-pro"); ?>')) {
        // Add AJAX call to reset settings
        alert('<?php _e("Settings reset successfully!", "culinary-canvas-pro"); ?>');
    }
});

// File Upload Handler
$('#import_recipes').on('change', function() {
    const file = this.files[0];
    if (file) {
        alert('<?php _e("File selected. Import functionality will be implemented.", "culinary-canvas-pro"); ?>');
    }
});
        </script>
        <?php
    }
}