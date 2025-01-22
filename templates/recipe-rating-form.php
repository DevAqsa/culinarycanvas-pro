<?php
/**
 * Template for the recipe rating form
 */

// Only show form if user is logged in
if (!is_user_logged_in()) {
    printf(
        '<p class="login-to-rate">%s <a href="%s">%s</a></p>',
        __('Please', 'culinary-canvas-pro'),
        esc_url(wp_login_url(get_permalink())),
        __('login to rate this recipe', 'culinary-canvas-pro')
    );
    return;
}

$recipe_id = get_the_ID();
$user_id = get_current_user_id();

// Check if user has already rated
global $wpdb;
$table_name = $wpdb->prefix . 'recipe_ratings';
$existing_rating = $wpdb->get_row($wpdb->prepare(
    "SELECT rating, review FROM $table_name WHERE recipe_id = %d AND user_id = %d",
    $recipe_id,
    $user_id
));

?>

<div class="recipe-rating-form" id="recipe-rating-form">
    <h3><?php _e('Rate this Recipe', 'culinary-canvas-pro'); ?></h3>
    
    <form id="submit-recipe-rating" method="post">
        <div class="rating-stars">
            <div class="stars-input">
                <?php for ($i = 5; $i >= 1; $i--) : ?>
                    <input type="radio" 
                           name="rating" 
                           id="star<?php echo $i; ?>" 
                           value="<?php echo $i; ?>"
                           <?php checked($existing_rating ? $existing_rating->rating : 0, $i); ?>>
                    <label for="star<?php echo $i; ?>">★</label>
                <?php endfor; ?>
            </div>
            <div class="rating-text"></div>
        </div>

        <div class="review-input">
            <label for="review"><?php _e('Your Review', 'culinary-canvas-pro'); ?></label>
            <textarea name="review" id="review" rows="5"><?php 
                echo $existing_rating ? esc_textarea($existing_rating->review) : ''; 
            ?></textarea>
        </div>

        <input type="hidden" name="recipe_id" value="<?php echo esc_attr($recipe_id); ?>">
        <?php wp_nonce_field('recipe_rating_nonce', 'rating_nonce'); ?>

        <button type="submit" class="submit-rating">
            <?php echo $existing_rating 
                ? __('Update Rating', 'culinary-canvas-pro') 
                : __('Submit Rating', 'culinary-canvas-pro'); 
            ?>
        </button>
    </form>
</div>