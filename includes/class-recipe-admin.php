<?php
// In class-recipe-admin.php

class CulinaryCanvas_Recipe_Admin {
    private $plugin_slug = 'recipe-dashboard';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, $this->plugin_slug) === false) {
            return;
        }

        wp_enqueue_style(
            'recipe-admin-style',
            plugins_url('assets/css/admin-style.css', dirname(__FILE__)),
            array(),
            CCP_VERSION
        );
    }

    public function add_admin_menu() {
        // Remove the original menu item that might be added by register_post_type
        remove_menu_page('edit.php?post_type=recipe');

        // Add our custom menu
        add_menu_page(
            __('Recipes', 'culinary-canvas-pro'),
            __('Recipes', 'culinary-canvas-pro'),
            'manage_options',
            $this->plugin_slug,
            array($this, 'render_dashboard_page'),
            'dashicons-food',
            5
        );

        // Add submenu items
        $submenus = array(
            array(
                'parent' => $this->plugin_slug,
                'page_title' => __('Recipe Dashboard', 'culinary-canvas-pro'),
                'menu_title' => __('Dashboard', 'culinary-canvas-pro'),
                'capability' => 'manage_options',
                'slug' => $this->plugin_slug,
                'callback' => array($this, 'render_dashboard_page')
            ),
            array(
                'parent' => $this->plugin_slug,
                'page_title' => __('All Recipes', 'culinary-canvas-pro'),
                'menu_title' => __('All Recipes', 'culinary-canvas-pro'),
                'capability' => 'manage_options',
                'slug' => 'edit.php?post_type=recipe'
            ),
            array(
                'parent' => $this->plugin_slug,
                'page_title' => __('Add New Recipe', 'culinary-canvas-pro'),
                'menu_title' => __('Add New', 'culinary-canvas-pro'),
                'capability' => 'manage_options',
                'slug' => 'post-new.php?post_type=recipe'
            ),
            array(
                'parent' => $this->plugin_slug,
                'page_title' => __('Categories', 'culinary-canvas-pro'),
                'menu_title' => __('Categories', 'culinary-canvas-pro'),
                'capability' => 'manage_options',
                'slug' => 'edit-tags.php?taxonomy=recipe_category&post_type=recipe'
            ),
            array(
                'parent' => $this->plugin_slug,
                'page_title' => __('Tags', 'culinary-canvas-pro'),
                'menu_title' => __('Tags', 'culinary-canvas-pro'),
                'capability' => 'manage_options',
                'slug' => 'edit-tags.php?taxonomy=recipe_tag&post_type=recipe'
            ),
            array(
                'parent' => $this->plugin_slug,
                'page_title' => __('Settings', 'culinary-canvas-pro'),
                'menu_title' => __('Settings', 'culinary-canvas-pro'),
                'capability' => 'manage_options',
                'slug' => 'recipe-settings',
                'callback' => array($this, 'render_settings_page')
            )
        );

        foreach ($submenus as $submenu) {
            add_submenu_page(
                $submenu['parent'],
                $submenu['page_title'],
                $submenu['menu_title'],
                $submenu['capability'],
                $submenu['slug'],
                isset($submenu['callback']) ? $submenu['callback'] : null
            );
        }
    }

    public function render_dashboard_page() {
        // Get stats
        $stats = $this->get_dashboard_stats();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Recipe Dashboard', 'culinary-canvas-pro'); ?></h1>
            <a href="<?php echo admin_url('post-new.php?post_type=recipe'); ?>" class="page-title-action"><?php _e('Add New Recipe', 'culinary-canvas-pro'); ?></a>
            
            <!-- Stats Cards -->
            <div class="recipe-stats-grid">
                <div class="stats-card">
                    <span class="stats-icon dashicons dashicons-media-document"></span>
                    <div class="stats-content">
                        <h2><?php echo $stats['total_recipes']; ?></h2>
                        <p><?php _e('Total Recipes', 'culinary-canvas-pro'); ?></p>
                    </div>
                </div>
                
                <div class="stats-card">
                    <span class="stats-icon dashicons dashicons-star-filled"></span>
                    <div class="stats-content">
                        <h2><?php echo $stats['average_rating']; ?></h2>
                        <p><?php _e('Average Rating', 'culinary-canvas-pro'); ?></p>
                    </div>
                </div>
                
                <div class="stats-card">
                    <span class="stats-icon dashicons dashicons-chart-bar"></span>
                    <div class="stats-content">
                        <h2><?php echo $stats['total_reviews']; ?></h2>
                        <p><?php _e('Total Reviews', 'culinary-canvas-pro'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Recent Recipes -->
            <div class="recipe-content-grid">
                <div class="recipe-section">
                    <h2><?php _e('Recent Recipes', 'culinary-canvas-pro'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Recipe', 'culinary-canvas-pro'); ?></th>
                                <th><?php _e('Author', 'culinary-canvas-pro'); ?></th>
                                <th><?php _e('Date', 'culinary-canvas-pro'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $recent_recipes = get_posts(array(
                                'post_type' => 'recipe',
                                'posts_per_page' => 5,
                                'orderby' => 'date',
                                'order' => 'DESC'
                            ));

                            foreach ($recent_recipes as $recipe) {
                                printf(
                                    '<tr>
                                        <td><a href="%s">%s</a></td>
                                        <td>%s</td>
                                        <td>%s</td>
                                    </tr>',
                                    get_edit_post_link($recipe->ID),
                                    esc_html($recipe->post_title),
                                    get_the_author_meta('display_name', $recipe->post_author),
                                    get_the_date('', $recipe->ID)
                                );
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- Quick Actions -->
                <div class="recipe-section">
                    <h2><?php _e('Quick Actions', 'culinary-canvas-pro'); ?></h2>
                    <div class="quick-actions">
                        <a href="<?php echo admin_url('post-new.php?post_type=recipe'); ?>" class="quick-action-button">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php _e('Add Recipe', 'culinary-canvas-pro'); ?>
                        </a>
                        <a href="<?php echo admin_url('edit-tags.php?taxonomy=recipe_category&post_type=recipe'); ?>" class="quick-action-button">
                            <span class="dashicons dashicons-category"></span>
                            <?php _e('Categories', 'culinary-canvas-pro'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=recipe-settings'); ?>" class="quick-action-button">
                            <span class="dashicons dashicons-admin-generic"></span>
                            <?php _e('Settings', 'culinary-canvas-pro'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function get_dashboard_stats() {
        return array(
            'total_recipes' => wp_count_posts('recipe')->publish,
            'average_rating' => $this->get_average_rating(),
            'total_reviews' => $this->get_total_reviews()
        );
    }

    private function get_average_rating() {
        global $wpdb;
        $average = $wpdb->get_var("SELECT AVG(rating) FROM {$wpdb->prefix}recipe_ratings");
        return $average ? number_format($average, 1) : '0';
    }

    private function get_total_reviews() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}recipe_ratings") ?: '0';
    }
}