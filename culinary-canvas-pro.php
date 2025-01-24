<?php
/**
 * Plugin Name: CulinaryCanvas Pro
 * Plugin URI: https://example.com/culinary-canvas-pro
 * Description: A professional recipe management plugin for food blogs with ratings and reviews
 * Version: 1.0.1
 * Author: Aqsa Mumtaz
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: culinary-canvas-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CCP_VERSION', '1.0.1');
define('CCP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CCP_PLUGIN_URL', plugin_dir_url(__FILE__));

class CulinaryCanvas_Pro {
    private static $instance = null;
    public $post_type;
    public $metadata;
    public $ratings;
    public $admin;
   

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->include_files();
        add_action('plugins_loaded', array($this, 'init'), 0);
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));


        
    }

    private function include_files() {
        $files = array(
            'class-recipe-post-type.php',
            'class-recipe-metadata.php',
            'class-recipe-ratings.php',
            'class-recipe-admin.php',
            'class-recipe-features.php',
            'class-meal-planner.php',
            'class-cost-calculator.php'
        );

        foreach ($files as $file) {
            require_once CCP_PLUGIN_DIR . 'includes/' . $file;
        }
    }

    public $features;
public $meal_planner;
public $cost_calculator;

    public function init() {
        $this->post_type = new CulinaryCanvas_Recipe_Post_Type();
    $this->metadata = new CulinaryCanvas_Recipe_Metadata();
    $this->ratings = new CulinaryCanvas_Recipe_Ratings();
    $this->features = new CulinaryCanvas_Recipe_Features();
    $this->meal_planner = new CulinaryCanvas_Meal_Planner();
    $this->cost_calculator = new CulinaryCanvas_Cost_Calculator();
        

        if (is_admin()) {
            $this->admin = new CulinaryCanvas_Recipe_Admin();
        }

        load_plugin_textdomain('culinary-canvas-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        $this->create_tables();
        
        $this->post_type = new CulinaryCanvas_Recipe_Post_Type();
        $this->post_type->register_post_type();
        $this->post_type->register_taxonomies();
        
        
        
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tables = array(
            'recipe_ratings' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}recipe_ratings (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                recipe_id bigint(20) NOT NULL,
                user_id bigint(20) NOT NULL,
                rating tinyint(1) NOT NULL,
                review text,
                date_created datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY recipe_id (recipe_id),
                KEY user_id (user_id)
            )",
            
        );

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        foreach ($tables as $table_sql) {
            dbDelta($table_sql . " $charset_collate;");
        }
    }
}

function culinary_canvas_pro() {
    return CulinaryCanvas_Pro::get_instance();
}

culinary_canvas_pro();