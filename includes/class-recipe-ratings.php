<?php

if (!defined('ABSPATH')) {
    exit;
}

class CulinaryCanvas_Recipe_Ratings {
    public function __construct() {
        add_action('wp_ajax_submit_recipe_rating', array($this, 'submit_recipe_rating'));
        add_action('wp_ajax_nopriv_submit_recipe_rating', array($this, 'submit_recipe_rating'));
        add_filter('the_content', array($this, 'append_rating_form'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_rating_scripts'));
    }

    public function enqueue_rating_scripts() {
        if (is_singular('recipe')) {
            wp_enqueue_style('recipe-ratings', CCP_PLUGIN_URL . 'assets/css/recipe-ratings.css');
            wp_enqueue_script('recipe-ratings', CCP_PLUGIN_URL . 'assets/js/recipe-ratings.js', array('jquery'), CCP_VERSION, true);
            
            wp_localize_script('recipe-ratings', 'recipeRatings', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('recipe_rating_nonce')
            ));
        }
    }

    public function submit_recipe_rating() {
        check_ajax_referer('recipe_rating_nonce', 'nonce');

        $recipe_id = intval($_POST['recipe_id']);
        $rating = intval($_POST['rating']);
        $review = sanitize_textarea_field($_POST['review']);
        $user_id = get_current_user_id();

        if ($rating < 1 || $rating > 5) {
            wp_send_json_error('Invalid rating value');
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'recipe_ratings';

        // Check if user has already rated this recipe
        $existing_rating = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_name WHERE recipe_id = %d AND user_id = %d",
            $recipe_id,
            $user_id
        ));

        if ($existing_rating) {
            // Update existing rating
            $wpdb->update(
                $table_name,
                array(
                    'rating' => $rating,
                    'review' => $review,
                    'date_created' => current_time('mysql')
                ),
                array(
                    'recipe_id' => $recipe_id,
                    'user_id' => $user_id
                ),
                array('%d', '%s', '%s'),
                array('%d', '%d')
            );
        } else {
            // Insert new rating
            $wpdb->insert(
                $table_name,
                array(
                    'recipe_id' => $recipe_id,
                    'user_id' => $user_id,
                    'rating' => $rating,
                    'review' => $review,
                    'date_created' => current_time('mysql')
                ),
                array('%d', '%d', '%d', '%s', '%s')
            );
        }

        // Update average rating meta
        $this->update_average_rating($recipe_id);

        wp_send_json_success(array(
            'message' => __('Rating submitted successfully', 'culinary-canvas-pro'),
            'average' => $this->get_average_rating($recipe_id)
        ));
    }

    public function get_average_rating($recipe_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'recipe_ratings';
        
        $average = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(rating) FROM $table_name WHERE recipe_id = %d",
            $recipe_id
        ));

        return round($average, 1);
    }

    public function update_average_rating($recipe_id) {
        $average = $this->get_average_rating($recipe_id);
        update_post_meta($recipe_id, '_average_rating', $average);
        
        // Update rating count
        global $wpdb;
        $table_name = $wpdb->prefix . 'recipe_ratings';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE recipe_id = %d",
            $recipe_id
        ));
        update_post_meta($recipe_id, '_rating_count', $count);
    }

    public function get_rating_html($recipe_id) {
        $average = get_post_meta($recipe_id, '_average_rating', true);
        $count = get_post_meta($recipe_id, '_rating_count', true);

        $html = '<div class="recipe-rating">';
        $html .= '<div class="stars">';
        
        for ($i = 1; $i <= 5; $i++) {
            $class = $i <= $average ? 'filled' : 'empty';
            $html .= '<span class="star ' . $class . '">★</span>';
        }
        
        $html .= '</div>';
        $html .= '<div class="rating-count">';
        $html .= sprintf(
            _n('(%d rating)', '(%d ratings)', $count, 'culinary-canvas-pro'),
            $count
        );
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function append_rating_form($content) {
        if (!is_singular('recipe')) {
            return $content;
        }

        ob_start();
        include CCP_PLUGIN_DIR . 'templates/recipe-rating-form.php';
        $rating_form = ob_get_clean();

        return $content . $rating_form;
    }

    public function get_recipe_reviews($recipe_id, $per_page = 10, $page = 1) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'recipe_ratings';
        
        $offset = ($page - 1) * $per_page;
        
        $reviews = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
            WHERE recipe_id = %d 
            ORDER BY date_created DESC 
            LIMIT %d OFFSET %d",
            $recipe_id,
            $per_page,
            $offset
        ));

        return $reviews;
    }
}