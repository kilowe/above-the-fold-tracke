<?php

namespace AboveTheFold;

class AdminDashboard
{
        const PAGE_SLUG = 'atf-tracker';
        const PER_PAGE = 20;

        public static function init()
        {
                add_action('admin_menu', [self::class, 'add_admin_menu']);
                add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_assets']);
        }

        public static function add_admin_menu()
        {
                add_menu_page(
                        __('Above the Fold Tracker', 'atf-tracker'),
                        __('Above the Fold', 'atf-tracker'),
                        'manage_options',
                        self::PAGE_SLUG,
                        [self::class, 'render_admin_page'],
                        'dashicons-visibility',
                        80
                );
        }

        public static function enqueue_admin_assets($hook)
        {
                if ($hook !== 'toplevel_page_' . self::PAGE_SLUG) return;

                wp_enqueue_style(
                        'atf-admin-styles',
                        plugin_dir_url(__DIR__) . 'assets/css/admin.css',
                        [],
                        '1.0'
                );

                wp_enqueue_script(
                        'atf-admin-script',
                        plugin_dir_url(__DIR__) . 'assets/js/admin.js',
                        ['jquery'],
                        '1.0',
                        true
                );
        }

        public static function render_admin_page()
        {
                if (!current_user_can('manage_options')) {
                        wp_die(__('You do not have sufficient permissions to access this page.'));
                }

                $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
                $offset = ($current_page - 1) * self::PER_PAGE;

                $data = Database::get_recent_data(self::PER_PAGE, $offset);
                $total_items = Database::get_total_count();
                $total_pages = ceil($total_items / self::PER_PAGE);
?>
                <div class="wrap atf-admin">
                        <h1><?php esc_html_e('Above the Fold Tracker', 'atf-tracker'); ?></h1>

                        <div class="atf-stats">
                                <div class="stat-card">
                                        <h3><?php echo number_format($total_items); ?></h3>
                                        <p><?php esc_html_e('Total Views', 'atf-tracker'); ?></p>
                                </div>
                                <div class="stat-card">
                                        <h3><?php echo count($data); ?></h3>
                                        <p><?php esc_html_e('Showing Records', 'atf-tracker'); ?></p>
                                </div>
                                <div class="stat-card">
                                        <h3><?php echo self::PER_PAGE; ?></h3>
                                        <p><?php esc_html_e('Per Page', 'atf-tracker'); ?></p>
                                </div>
                        </div>

                        <table class="wp-list-table widefat fixed striped">
                                <thead>
                                        <tr>
                                                <th><?php esc_html_e('Date', 'atf-tracker'); ?></th>
                                                <th><?php esc_html_e('Screen Size', 'atf-tracker'); ?></th>
                                                <th><?php esc_html_e('Visible Links', 'atf-tracker'); ?></th>
                                                <th><?php esc_html_e('Actions', 'atf-tracker'); ?></th>
                                        </tr>
                                </thead>
                                <tbody>
                                        <?php if (empty($data)) : ?>
                                                <tr>
                                                        <td colspan="4"><?php esc_html_e('No data available yet.', 'atf-tracker'); ?></td>
                                                </tr>
                                        <?php else : ?>
                                                <?php foreach ($data as $row) : ?>
                                                        <tr>
                                                                <td><?php echo date_i18n('Y-m-d H:i:s', strtotime($row['created_at'])); ?></td>
                                                                <td><?php echo esc_html($row['screen']); ?></td>
                                                                <td>
                                                                        <ul>
                                                                                <?php foreach ($row['links'] as $link) : ?>
                                                                                        <li>
                                                                                                <a href="<?php echo esc_url($link['url']); ?>" target="_blank">
                                                                                                        <?php echo esc_html($link['text'] ?: __('No text', 'atf-tracker')); ?>
                                                                                                </a>
                                                                                        </li>
                                                                                <?php endforeach; ?>
                                                                        </ul>
                                                                </td>
                                                                <td>
                                                                        <button class="button view-details" data-id="<?php echo $row['id']; ?>">
                                                                                <?php esc_html_e('Details', 'atf-tracker'); ?>
                                                                        </button>
                                                                </td>
                                                        </tr>
                                                <?php endforeach; ?>
                                        <?php endif; ?>
                                </tbody>
                        </table>

                        <div class="tablenav bottom">
                                <div class="tablenav-pages">
                                        <?php
                                        echo paginate_links([
                                                'base' => add_query_arg('paged', '%#%'),
                                                'format' => '',
                                                'prev_text' => __('&laquo; Previous'),
                                                'next_text' => __('Next &raquo;'),
                                                'total' => $total_pages,
                                                'current' => $current_page
                                        ]);
                                        ?>
                                </div>
                        </div>

                        <div id="atf-details-modal" class="atf-modal" style="display:none;">
                                <div class="atf-modal-content">
                                        <span class="atf-close">&times;</span>
                                        <h2><?php esc_html_e('View Details', 'atf-tracker'); ?></h2>
                                        <div id="atf-modal-body"></div>
                                </div>
                        </div>
                </div>
        <?php
        }

        public static function get_details()
        {
                check_ajax_referer('atf_admin_nonce', 'security');

                if (!isset($_POST['id']) || !current_user_can('manage_options')) {
                        wp_send_json_error(__('Invalid request.', 'atf-tracker'));
                }

                $id = intval(isset($_POST['id']));
                $data = Database::get_single_entry($id);

                if (!$data) {
                        wp_send_json_error(__('Record not found.', 'atf-tracker'));
                }

                ob_start();
        ?>
                <div class="atf-details">
                        <p><strong><?php esc_html_e('Date:', 'atf-tracker'); ?></strong>
                                <?php echo date_i18n('Y-m-d H:i:s', strtotime($data['created_at'])); ?>
                        </p>

                        <p><strong><?php esc_html_e('Screen Size:', 'atf-tracker'); ?></strong>
                                <?php echo esc_html($data['screen']); ?>
                        </p>

                        <h3><?php esc_html_e('Visible Links:', 'atf-tracker'); ?></h3>
                        <ul>
                                <?php foreach ($data['links'] as $index => $link) : ?>
                                        <li>
                                                <strong>#<?php echo $index + 1; ?>:</strong>
                                                <a href="<?php echo esc_url($link['url']); ?>" target="_blank">
                                                        <?php echo esc_html($link['text'] ?: __('No text', 'atf-tracker')); ?>
                                                </a>
                                                <small>(<?php echo esc_url($link['url']); ?>)</small>
                                        </li>
                                <?php endforeach; ?>
                        </ul>
                </div>
<?php
                wp_send_json_success(ob_get_clean());
        }

        public static function add_test_shortcode()
        {
                add_shortcode('atf_test', function ($atts) {
                        if (!current_user_can('manage_options')) {
                                return '<p>Permission denied</p>';
                        }

                        return '
            <div class="atf-test-container">
                <button id="atf-test-btn" class="button button-primary">
                    Run ATF Test
                </button>
                <div id="atf-test-result" style="margin-top:20px; padding:10px; border:1px solid #ddd;"></div>
            </div>
            <script>
                document.getElementById("atf-test-btn").addEventListener("click", () => {
                    const testLinks = [
                        {url: "https://test.com", text: "Test Link 1"},
                        {url: "https://example.com", text: "Example Link"}
                    ];
                    
                    const data = {
                        action: "atf_track",
                        nonce: "' . wp_create_nonce('atf_tracking_nonce') . '",
                        screen: "1920x1080",
                        links: JSON.stringify(testLinks)
                    };
                    
                    fetch("' . admin_url('admin-ajax.php') . '", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: new URLSearchParams(data)
                    })
                    .then(response => response.json())
                    .then(result => {
                        const output = result.success 
                            ? `Success: data was successfully recorded (ID: ${result.data.inserted}).` 
                            : `An error occurred while processing the request: ${result.data}`;
                        document.getElementById("atf-test-result").innerHTML = output;
                    });
                });
            </script>
        ';
                });
        }
}
