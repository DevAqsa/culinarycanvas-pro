<?php
/**
 * Plugin Name: CulinaryCanvas Pro
 * Plugin URI: https://example.com/culinary-canvas-pro
 * Description: A professional recipe management plugin for food blogs with ratings and reviews
 * Version: 1.0.0
 * Author:Aqsa Mumtaz
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

// Main Plugin Class
class CulinaryCanvas_Pro {
    private static $instance = null;
    private $post_type;
    private $metadata;
    private $ratings;
    private $admin;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Initialize components
        add_action('plugins_loaded', array($this, 'init'));

        // Register activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        // Initialize admin
        if (is_admin()) {
            $this->admin = new CulinaryCanvas_Recipe_Admin();
        }

        // Initialize other components
        $this->post_type = new CulinaryCanvas_Recipe_Post_Type();
        $this->metadata = new CulinaryCanvas_Recipe_Metadata();
        $this->ratings = new CulinaryCanvas_Recipe_Ratings();

        // Load text domain
        load_plugin_textdomain('culinary-canvas-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        // Create database tables
        $this->create_tables();
        
        // Clear permalinks
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}recipe_ratings (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            recipe_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            rating tinyint(1) NOT NULL,
            review text,
            date_created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY recipe_id (recipe_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Initialize the plugin
function CulinaryCanvas_Pro_Init() {
    return CulinaryCanvas_Pro::get_instance();
}

add_action('plugins_loaded', 'CulinaryCanvas_Pro_Init');