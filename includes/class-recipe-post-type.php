<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class CulinaryCanvas_Recipe_Post_Type {
    public function __construct() {
        // Register post type and taxonomies
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomies'));
        
        // Add template filter
        add_filter('single_template', array($this, 'load_recipe_template'));
    }

    public function register_post_type() {
        $labels = array(
            'name'               => _x('Recipes', 'post type general name', 'culinary-canvas-pro'),
            'singular_name'      => _x('Recipe', 'post type singular name', 'culinary-canvas-pro'),
            'menu_name'          => _x('Recipes', 'admin menu', 'culinary-canvas-pro'),
            'add_new'            => _x('Add New', 'recipe', 'culinary-canvas-pro'),
            'add_new_item'       => __('Add New Recipe', 'culinary-canvas-pro'),
            'edit_item'          => __('Edit Recipe', 'culinary-canvas-pro'),
            'new_item'           => __('New Recipe', 'culinary-canvas-pro'),
            'view_item'          => __('View Recipe', 'culinary-canvas-pro'),
            'search_items'       => __('Search Recipes', 'culinary-canvas-pro'),
            'not_found'          => __('No recipes found', 'culinary-canvas-pro'),
            'not_found_in_trash' => __('No recipes found in Trash', 'culinary-canvas-pro')
        );
    
        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => false, // Change this to false
            'query_var'           => true,
            'rewrite'             => array('slug' => 'recipe'),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'supports'            => array(
                'title',
                'editor',
                'thumbnail',
                'excerpt',
                'comments',
                'revisions'
            ),
            'show_in_rest'        => true,
            'rest_base'           => 'recipes',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        );
    
        register_post_type('recipe', $args);
    }
    public function register_taxonomies() {
        // Recipe Category Taxonomy
        $category_labels = array(
            'name'              => _x('Recipe Categories', 'taxonomy general name', 'culinary-canvas-pro'),
            'singular_name'     => _x('Recipe Category', 'taxonomy singular name', 'culinary-canvas-pro'),
            'search_items'      => __('Search Recipe Categories', 'culinary-canvas-pro'),
            'all_items'         => __('All Recipe Categories', 'culinary-canvas-pro'),
            'parent_item'       => __('Parent Recipe Category', 'culinary-canvas-pro'),
            'parent_item_colon' => __('Parent Recipe Category:', 'culinary-canvas-pro'),
            'edit_item'         => __('Edit Recipe Category', 'culinary-canvas-pro'),
            'update_item'       => __('Update Recipe Category', 'culinary-canvas-pro'),
            'add_new_item'      => __('Add New Recipe Category', 'culinary-canvas-pro'),
            'new_item_name'     => __('New Recipe Category Name', 'culinary-canvas-pro'),
            'menu_name'         => __('Categories', 'culinary-canvas-pro'),
        );

        register_taxonomy('recipe_category', 'recipe', array(
            'hierarchical'      => true,
            'labels'            => $category_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'recipe-category'),
            'show_in_rest'      => true
        ));

        // Recipe Tags Taxonomy
        $tag_labels = array(
            'name'              => _x('Recipe Tags', 'taxonomy general name', 'culinary-canvas-pro'),
            'singular_name'     => _x('Recipe Tag', 'taxonomy singular name', 'culinary-canvas-pro'),
            'search_items'      => __('Search Recipe Tags', 'culinary-canvas-pro'),
            'all_items'         => __('All Recipe Tags', 'culinary-canvas-pro'),
            'edit_item'         => __('Edit Recipe Tag', 'culinary-canvas-pro'),
            'update_item'       => __('Update Recipe Tag', 'culinary-canvas-pro'),
            'add_new_item'      => __('Add New Recipe Tag', 'culinary-canvas-pro'),
            'new_item_name'     => __('New Recipe Tag Name', 'culinary-canvas-pro'),
            'menu_name'         => __('Tags', 'culinary-canvas-pro'),
        );

        register_taxonomy('recipe_tag', 'recipe', array(
            'hierarchical'      => false,
            'labels'            => $tag_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'recipe-tag'),
            'show_in_rest'      => true
        ));
    }

    public function load_recipe_template($template) {
        if (is_singular('recipe')) {
            $custom_template = locate_template('culinary-canvas-pro/recipe-display.php');
            if ($custom_template) {
                return $custom_template;
            }
            return CCP_PLUGIN_DIR . 'templates/recipe-display.php';
        }
        return $template;
    }
}