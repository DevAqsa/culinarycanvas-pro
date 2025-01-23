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
        // Only load on our plugin's pages
        if (strpos($hook, 'recipe-dashboard') === false) {
            return;
        }

        // Enqueue admin styles
        wp_enqueue_style(
            'culinary-canvas-admin',
            CCP_PLUGIN_URL . 'assets/css/admin-style.css',
            array(),
            CCP_VERSION
        );
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

    public function render_settings_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Recipe Settings', 'culinary-canvas-pro') . '</h1>';
        echo '<p>' . __('Settings page content will go here.', 'culinary-canvas-pro') . '</p>';
        echo '</div>';
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
}