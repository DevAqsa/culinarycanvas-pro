<?php
/**
 * Plugin Name: CulinaryCanvas Pro
 * Plugin URI: https://example.com/culinary-canvas-pro
 * Description: A professional recipe management plugin for food blogs with ratings and reviews
 * Version: 1.0.0
 * Author: Aqsa Mumtaz
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: culinary-canvas-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CCP_VERSION', '1.0.0');
define('CCP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CCP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once CCP_PLUGIN_DIR . 'includes/class-recipe-post-type.php';
require_once CCP_PLUGIN_DIR . 'includes/class-recipe-metadata.php';
require_once CCP_PLUGIN_DIR . 'includes/class-recipe-ratings.php';
require_once CCP_PLUGIN_DIR . 'includes/class-recipe-admin.php';

// Initialize Admin
function init_recipe_admin() {
    new CulinaryCanvas_Recipe_Admin();
}
add_action('plugins_loaded', 'init_recipe_admin');

// Initialize Post Type
function init_recipe_post_type() {
    new CulinaryCanvas_Recipe_Post_Type();
}
add_action('init', 'init_recipe_post_type');