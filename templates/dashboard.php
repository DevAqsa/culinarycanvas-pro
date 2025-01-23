<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get stats
$stats = $this->get_dashboard_stats();
?>

<div class="wrap culinary-canvas-dashboard">
    <div class="dashboard-title">
        <h1><?php _e('Recipe Dashboard', 'culinary-canvas-pro'); ?></h1>
        <a href="<?php echo admin_url('post-new.php?post_type=recipe'); ?>" class="page-title-action">
            <?php _e('Add New Recipe', 'culinary-canvas-pro'); ?>
        </a>
    </div>

    <div class="recipe-stats-grid">
        <div class="stats-card">
            <div class="stats-icon">
                <span class="dashicons dashicons-media-document"></span>
            </div>
            <div class="stats-content">
                <h2><?php echo esc_html($stats['total_recipes']); ?></h2>
                <p><?php _e('Total Recipes', 'culinary-canvas-pro'); ?></p>
            </div>
        </div>

        <div class="stats-card">
            <div class="stats-icon">
                <span class="dashicons dashicons-star-filled"></span>
            </div>
            <div class="stats-content">
                <h2><?php echo esc_html($stats['average_rating']); ?></h2>
                <p><?php _e('Average Rating', 'culinary-canvas-pro'); ?></p>
            </div>
        </div>

        <div class="stats-card">
            <div class="stats-icon">
                <span class="dashicons dashicons-chart-bar"></span>
            </div>
            <div class="stats-content">
                <h2><?php echo esc_html($stats['total_reviews']); ?></h2>
                <p><?php _e('Total Reviews', 'culinary-canvas-pro'); ?></p>
            </div>
        </div>
    </div>

    <div class="recipe-content-grid">
        <div class="recipe-section">
            <h2><?php _e('Recent Recipes', 'culinary-canvas-pro'); ?></h2>
            <?php
            $recent_recipes = get_posts(array(
                'post_type' => 'recipe',
                'posts_per_page' => 5,
                'orderby' => 'date',
                'order' => 'DESC'
            ));

            if (!empty($recent_recipes)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Recipe', 'culinary-canvas-pro'); ?></th>
                            <th><?php _e('Author', 'culinary-canvas-pro'); ?></th>
                            <th><?php _e('Date', 'culinary-canvas-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_recipes as $recipe) : ?>
                            <tr>
                                <td>
                                    <a href="<?php echo get_edit_post_link($recipe->ID); ?>">
                                        <strong><?php echo esc_html($recipe->post_title); ?></strong>
                                    </a>
                                </td>
                                <td><?php echo get_the_author_meta('display_name', $recipe->post_author); ?></td>
                                <td><?php echo get_the_date('', $recipe->ID); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php _e('No recipes found.', 'culinary-canvas-pro'); ?></p>
            <?php endif; ?>
        </div>

        <div class="recipe-section">
            <h2><?php _e('Quick Actions', 'culinary-canvas-pro'); ?></h2>
            <div class="quick-actions">
                <a href="<?php echo admin_url('post-new.php?post_type=recipe'); ?>" class="quick-action-button">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Add New Recipe', 'culinary-canvas-pro'); ?>
                </a>
                <a href="<?php echo admin_url('edit-tags.php?taxonomy=recipe_category&post_type=recipe'); ?>" class="quick-action-button">
                    <span class="dashicons dashicons-category"></span>
                    <?php _e('Manage Categories', 'culinary-canvas-pro'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=recipe-settings'); ?>" class="quick-action-button">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php _e('Settings', 'culinary-canvas-pro'); ?>
                </a>
            </div>
        </div>
    </div>
</div>