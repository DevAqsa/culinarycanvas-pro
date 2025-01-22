<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap culinary-canvas-dashboard">
    <h1 class="dashboard-title">
        <?php echo esc_html(get_admin_page_title()); ?>
        <a href="<?php echo admin_url('post-new.php?post_type=recipe'); ?>" class="page-title-action">
            <?php _e('Add New Recipe', 'culinary-canvas-pro'); ?>
        </a>
    </h1>

    <!-- Quick Stats Section -->
    <div class="dashboard-stats">
        <div class="stat-card primary">
            <div class="stat-icon">
                <span class="dashicons dashicons-book-alt"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo esc_html($total_recipes); ?></h3>
                <p><?php _e('Total Recipes', 'culinary-canvas-pro'); ?></p>
            </div>
        </div>

        <div class="stat-card success">
            <div class="stat-icon">
                <span class="dashicons dashicons-star-filled"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats->avg_rating, 1); ?></h3>
                <p><?php _e('Average Rating', 'culinary-canvas-pro'); ?></p>
            </div>
        </div>

        <div class="stat-card info">
            <div class="stat-icon">
                <span class="dashicons dashicons-chart-bar"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo esc_html($stats->total_ratings); ?></h3>
                <p><?php _e('Total Reviews', 'culinary-canvas-pro'); ?></p>
            </div>
        </div>

        <div class="stat-card warning">
            <div class="stat-icon">
                <span class="dashicons dashicons-visibility"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($this->get_total_recipe_views()); ?></h3>
                <p><?php _e('Total Views', 'culinary-canvas-pro'); ?></p>
            </div>
        </div>
    </div>

    <!-- Main Dashboard Content -->
    <div class="dashboard-grid">
        <!-- Recent Recipes -->
        <div class="dashboard-card">
            <div class="card-header">
                <h2><?php _e('Recent Recipes', 'culinary-canvas-pro'); ?></h2>
                <a href="<?php echo admin_url('edit.php?post_type=recipe'); ?>" class="button-link">
                    <?php _e('View All', 'culinary-canvas-pro'); ?> →
                </a>
            </div>
            <div class="card-content">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Recipe', 'culinary-canvas-pro'); ?></th>
                            <th><?php _e('Author', 'culinary-canvas-pro'); ?></th>
                            <th><?php _e('Rating', 'culinary-canvas-pro'); ?></th>
                            <th><?php _e('Views', 'culinary-canvas-pro'); ?></th>
                            <th><?php _e('Date', 'culinary-canvas-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_recipes as $recipe) : 
                            $rating = $this->get_recipe_rating($recipe->ID);
                            $views = get_post_meta($recipe->ID, '_recipe_views', true);
                        ?>
                            <tr>
                                <td>
                                    <strong>
                                        <a href="<?php echo get_edit_post_link($recipe->ID); ?>">
                                            <?php echo esc_html($recipe->post_title); ?>
                                        </a>
                                    </strong>
                                    <div class="row-actions">
                                        <span class="edit">
                                            <a href="<?php echo get_edit_post_link($recipe->ID); ?>">
                                                <?php _e('Edit', 'culinary-canvas-pro'); ?>
                                            </a> |
                                        </span>
                                        <span class="view">
                                            <a href="<?php echo get_permalink($recipe->ID); ?>">
                                                <?php _e('View', 'culinary-canvas-pro'); ?>
                                            </a>
                                        </span>
                                    </div>
                                </td>
                                <td><?php echo get_the_author_meta('display_name', $recipe->post_author); ?></td>
                                <td>
                                    <div class="recipe-rating">
                                        <?php echo $this->display_rating_stars($rating); ?>
                                        <span class="rating-number">(<?php echo $rating; ?>)</span>
                                    </div>
                                </td>
                                <td><?php echo number_format($views); ?></td>
                                <td><?php echo get_the_date('M j, Y', $recipe->ID); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Popular Categories -->
        <div class="dashboard-card">
            <div class="card-header">
                <h2><?php _e('Popular Categories', 'culinary-canvas-pro'); ?></h2>
            </div>
            <div class="card-content">
                <?php
                $categories = get_terms(array(
                    'taxonomy' => 'recipe_category',
                    'orderby' => 'count',
                    'order' => 'DESC',
                    'number' => 5
                ));

                if (!empty($categories) && !is_wp_error($categories)) :
                ?>
                    <ul class="category-stats">
                        <?php foreach ($categories as $category) : ?>
                            <li>
                                <span class="category-name">
                                    <a href="<?php echo get_term_link($category); ?>">
                                        <?php echo esc_html($category->name); ?>
                                    </a>
                                </span>
                                <span class="category-count">
                                    <?php echo sprintf(_n('%s recipe', '%s recipes', $category->count, 'culinary-canvas-pro'), number_format($category->count)); ?>
                                </span>
                                <span class="category-bar">
                                    <span class="bar-fill" style="width: <?php echo ($category->count / $total_recipes) * 100; ?>%;"></span>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p class="no-categories">
                        <?php _e('No categories found. Start by adding some recipe categories!', 'culinary-canvas-pro'); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Reviews -->
        <div class="dashboard-card">
            <div class="card-header">
                <h2><?php _e('Recent Reviews', 'culinary-canvas-pro'); ?></h2>
            </div>
            <div class="card-content">
                <?php
                $recent_reviews = $this->get_recent_reviews(5);
                if (!empty($recent_reviews)) :
                ?>
                    <ul class="review-list">
                        <?php foreach ($recent_reviews as $review) : 
                            $recipe = get_post($review->recipe_id);
                        ?>
                            <li class="review-item">
                                <div class="review-header">
                                    <div class="review-meta">
                                        <strong class="recipe-title">
                                            <a href="<?php echo get_edit_post_link($recipe->ID); ?>">
                                                <?php echo esc_html($recipe->post_title); ?>
                                            </a>
                                        </strong>
                                        <span class="review-rating">
                                            <?php echo $this->display_rating_stars($review->rating); ?>
                                        </span>
                                    </div>
                                    <span class="review-date">
                                        <?php echo human_time_diff(strtotime($review->date_created), current_time('timestamp')); ?>
                                        <?php _e('ago', 'culinary-canvas-pro'); ?>
                                    </span>
                                </div>
                                <div class="review-content">
                                    <?php echo wp_trim_words($review->review, 20); ?>
                                </div>
                                <div class="review-author">
                                    <?php 
                                    $user = get_user_by('id', $review->user_id);
                                    echo get_avatar($user->ID, 24);
                                    echo esc_html($user->display_name);
                                    ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p class="no-reviews">
                        <?php _e('No reviews yet. Reviews will appear here once users start rating your recipes.', 'culinary-canvas-pro'); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="dashboard-card">
            <div class="card-header">
                <h2><?php _e('Quick Actions', 'culinary-canvas-pro'); ?></h2>
            </div>
            <div class="card-content">
                <div class="quick-actions">
                    <a href="<?php echo admin_url('post-new.php?post_type=recipe'); ?>" class="action-button">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Add Recipe', 'culinary-canvas-pro'); ?>
                    </a>
                    <a href="<?php echo admin_url('edit-tags.php?taxonomy=recipe_category&post_type=recipe'); ?>" class="action-button">
                        <span class="dashicons dashicons-category"></span>
                        <?php _e('Manage Categories', 'culinary-canvas-pro'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=recipe-settings'); ?>" class="action-button">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php _e('Settings', 'culinary-canvas-pro'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=recipe-tools'); ?>" class="action-button">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <?php _e('Tools', 'culinary-canvas-pro'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>