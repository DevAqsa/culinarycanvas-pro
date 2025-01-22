<?php
/**
 * Template for displaying recipe posts
 */

get_header();

while (have_posts()) :
    the_post();
    $recipe_id = get_the_ID();
    
    // Get recipe metadata
    $prep_time = get_post_meta($recipe_id, '_prep_time', true);
    $cook_time = get_post_meta($recipe_id, '_cook_time', true);
    $total_time = get_post_meta($recipe_id, '_total_time', true);
    $servings = get_post_meta($recipe_id, '_servings', true);
    $calories = get_post_meta($recipe_id, '_calories', true);
    $ingredients = get_post_meta($recipe_id, '_ingredients', true);
    $instructions = get_post_meta($recipe_id, '_instructions', true);
    $difficulty = get_post_meta($recipe_id, '_difficulty_level', true);
    $cuisine = get_post_meta($recipe_id, '_cuisine_type', true);
    ?>

    <div class="recipe-container">
        <article id="post-<?php the_ID(); ?>" <?php post_class('recipe-single'); ?>>
            <header class="recipe-header">
                <h1 class="recipe-title"><?php the_title(); ?></h1>
                
                <?php
                // Display rating
                $ratings = new CulinaryCanvas_Recipe_Ratings();
                echo $ratings->get_rating_html($recipe_id);
                ?>

                <div class="recipe-meta">
                    <div class="recipe-times">
                        <?php if ($prep_time) : ?>
                            <span class="prep-time">
                                <i class="icon-clock"></i>
                                <?php printf(__('Prep Time: %d mins', 'culinary-canvas-pro'), $prep_time); ?>
                            </span>
                        <?php endif; ?>

                        <?php if ($cook_time) : ?>
                            <span class="cook-time">
                                <i class="icon-fire"></i>
                                <?php printf(__('Cook Time: %d mins', 'culinary-canvas-pro'), $cook_time); ?>
                            </span>
                        <?php endif; ?>

                        <?php if ($total_time) : ?>
                            <span class="total-time">
                                <i class="icon-time"></i>
                                <?php printf(__('Total Time: %d mins', 'culinary-canvas-pro'), $total_time); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="recipe-details">
                        <?php if ($servings) : ?>
                            <span class="servings">
                                <i class="icon-users"></i>
                                <?php printf(__('Servings: %d', 'culinary-canvas-pro'), $servings); ?>
                            </span>
                        <?php endif; ?>

                        <?php if ($calories) : ?>
                            <span class="calories">
                                <i class="icon-fire"></i>
                                <?php printf(__('Calories: %d per serving', 'culinary-canvas-pro'), $calories); ?>
                            </span>
                        <?php endif; ?>

                        <?php if ($difficulty) : ?>
                            <span class="difficulty">
                                <i class="icon-gauge"></i>
                                <?php printf(__('Difficulty: %s', 'culinary-canvas-pro'), ucfirst($difficulty)); ?>
                            </span>
                        <?php endif; ?>

                        <?php if ($cuisine) : ?>
                            <span class="cuisine">
                                <i class="icon-globe"></i>
                                <?php printf(__('Cuisine: %s', 'culinary-canvas-pro'), $cuisine); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <?php if (has_post_thumbnail()) : ?>
                <div class="recipe-featured-image">
                    <?php the_post_thumbnail('large'); ?>
                </div>
            <?php endif; ?>

            <div class="recipe-content">
                <div class="recipe-description">
                    <?php the_content(); ?>
                </div>

                <div class="recipe-main">
                    <div class="recipe-ingredients">
                        <h2><?php _e('Ingredients', 'culinary-canvas-pro'); ?></h2>
                        <?php if (!empty($ingredients) && is_array($ingredients)) : ?>
                            <ul class="ingredients-list">
                                <?php foreach ($ingredients as $ingredient) : ?>
                                    <li><?php echo esc_html($ingredient); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>

                    <div class="recipe-instructions">
                        <h2><?php _e('Instructions', 'culinary-canvas-pro'); ?></h2>
                        <div class="instructions-content">
                            <?php echo wp_kses_post($instructions); ?>
                        </div>
                    </div>
                </div>

                <div class="recipe-notes">
                    <?php
                    $notes = get_post_meta($recipe_id, '_recipe_notes', true);
                    if (!empty($notes)) :
                    ?>
                        <h3><?php _e('Recipe Notes', 'culinary-canvas-pro'); ?></h3>
                        <div class="notes-content">
                            <?php echo wp_kses_post($notes); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="recipe-tags">
                    <?php
                    $categories = get_the_terms($recipe_id, 'recipe_category');
                    $tags = get_the_terms($recipe_id, 'recipe_tag');
                    ?>

                    <?php if ($categories) : ?>
                        <div class="recipe-categories">
                            <span class="label"><?php _e('Categories:', 'culinary-canvas-pro'); ?></span>
                            <?php foreach ($categories as $category) : ?>
                                <a href="<?php echo get_term_link($category); ?>"><?php echo esc_html($category->name); ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($tags) : ?>
                        <div class="recipe-tags">
                            <span class="label"><?php _e('Tags:', 'culinary-canvas-pro'); ?></span>
                            <?php foreach ($tags as $tag) : ?>
                                <a href="<?php echo get_term_link($tag); ?>"><?php echo esc_html($tag->name); ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </article>

        <?php
        // Display reviews section
        $reviews = $ratings->get_recipe_reviews($recipe_id);
        if (!empty($reviews)) :
        ?>
            <div class="recipe-reviews">
                <h3><?php _e('Reviews', 'culinary-canvas-pro'); ?></h3>
                <div class="reviews-list">
                    <?php foreach ($reviews as $review) : ?>
                        <div class="review-item">
                            <div class="review-rating">
                                <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    $class = $i <= $review->rating ? 'filled' : 'empty';
                                    echo '<span class="star ' . $class . '">★</span>';
                                }
                                ?>
                            </div>
                            <div class="review-content">
                                <?php echo wp_kses_post($review->review); ?>
                            </div>
                            <div class="review-meta">
                                <?php
                                $user = get_user_by('id', $review->user_id);
                                $date = date_i18n(get_option('date_format'), strtotime($review->date_created));
                                printf(
                                    __('By %s on %s', 'culinary-canvas-pro'),
                                    esc_html($user ? $user->display_name : __('Anonymous', 'culinary-canvas-pro')),
                                    esc_html($date)
                                );
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

<?php
endwhile;

get_footer();