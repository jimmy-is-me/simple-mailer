<?php
defined( 'ABSPATH' ) || exit;

class Simple_Mailer_Settings {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_post_simple_mailer_save', array( $this, 'save_settings' ) );
        add_action( 'admin_post_simple_mailer_test', array( $this, 'send_test_email' ) );
        add_action( 'admin_post_simple_mailer_delete_log', array( $this, 'delete_log' ) );
        add_action( 'admin_post_simple_mailer_clear_logs', array( $this, 'clear_logs' ) );
    }

    public function add_menu() {
        add_menu_page(
            'Simple Mailer',
            'Simple Mailer',
            'manage_options',
            'simple-mailer',
            array( $this, 'render_settings' ),
            'dashicons-email-alt',
            80
        );
        add_submenu_page(
            'simple-mailer',
            '設定',
            '設定',
            'manage_options',
            'simple-mailer',
            array( $this, 'render_settings' )
        );
        add_submenu_page(
            'simple-mailer',
            '電子郵件紀錄',
            '電子郵件紀錄',
            'manage_options',
            'simple-mailer-logs',
            array( $this, 'render_logs' )
        );
    }

    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'simple-mailer' ) === false ) return;
        wp_enqueue_style( 'simple-mailer-admin', SIMPLE_MAILER_URL . 'assets/admin.css', array(), SIMPLE_MAILER_VERSION );
    }

    public function save_settings() {
        check_admin_referer( 'simple_mailer_save' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( '權限不足' );

        $fields = array(
            'type', 'from_name', 'from_email',
            'resend_api_key', 'brevo_api_key',
            'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_secure',
        );
        $data = array();
        foreach ( $fields as $f ) {
            $data[ $f ] = isset( $_POST[ $f ] ) ? sanitize_text_field( wp_unslash( $_POST[ $f ] ) ) : '';
        }
        update_option( 'simple_mailer_settings', $data );
        wp_redirect( admin_url( 'admin.php?page=simple-mailer&saved=1' ) );
        exit;
    }

    public function send_test_email() {
        check_admin_referer( 'simple_mailer_test' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( '權限不足' );
        $to = sanitize_email( $_POST['test_email'] ?? get_option( 'admin_email' ) );
        $result = wp_mail( $to, 'Simple Mailer 測試郵件', '這是一封來自 Simple Mailer 的測試郵件。' );
        $status = $result ? 'test_ok' : 'test_fail';
        wp_redirect( admin_url( 'admin.php?page=simple-mailer&' . $status . '=1' ) );
        exit;
    }

    public function delete_log() {
        check_admin_referer( 'simple_mailer_delete_log' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( '權限不足' );
        Simple_Mailer_Logger::delete_log( absint( $_POST['log_id'] ?? 0 ) );
        wp_redirect( admin_url( 'admin.php?page=simple-mailer-logs' ) );
        exit;
    }

    public function clear_logs() {
        check_admin_referer( 'simple_mailer_clear_logs' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( '權限不足' );
        Simple_Mailer_Logger::delete_all();
        wp_redirect( admin_url( 'admin.php?page=simple-mailer-logs' ) );
        exit;
    }

    /* ── 設定頁面 HTML ── */
    public function render_settings() {
        $options = get_option( 'simple_mailer_settings', array() );
        $type    = $options['type'] ?? 'resend';
        ?>
        <div class="wrap sm-wrap">
            <h1>Simple Mailer 設定</h1>

            <?php if ( isset( $_GET['saved'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p>✅ 設定已儲存</p></div>
            <?php endif; ?>
            <?php if ( isset( $_GET['test_ok'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p>✅ 測試郵件寄送成功</p></div>
            <?php endif; ?>
            <?php if ( isset( $_GET['test_fail'] ) ) : ?>
                <div class="notice notice-error is-dismissible"><p>❌ 測試郵件寄送失敗，請檢查設定</p></div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'simple_mailer_save' ); ?>
                <input type="hidden" name="action" value="simple_mailer_save">

                <div class="sm-card">
                    <h2>傳輸方式</h2>
                    <table class="form-table">
                        <tr>
                            <th>類型</th>
                            <td>
                                <select name="type" id="sm_type" onchange="smToggle()">
                                    <option value="resend"  <?php selected( $type, 'resend'  ); ?>>Resend API</option>
                                    <option value="brevo"   <?php selected( $type, 'brevo'   ); ?>>Brevo API</option>
                                    <option value="smtp"    <?php selected( $type, 'smtp'    ); ?>>SMTP</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>寄件人名稱</th>
                            <td><input type="text" name="from_name" value="<?php echo esc_attr( $options['from_name'] ?? get_bloginfo('name') ); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th>寄件人信箱</th>
                            <td><input type="email" name="from_email" value="<?php echo esc_attr( $options['from_email'] ?? get_option('admin_email') ); ?>" class="regular-text"></td>
                        </tr>
                    </table>
                </div>

                <!-- Resend -->
                <div class="sm-card sm-section" id="sm_resend" style="display:none">
                    <h2>Resend API 設定</h2>
                    <p>請先在 <a href="https://resend.com" target="_blank">resend.com</a> 建立帳號，並在下方輸入 API 金鑰。</p>
                    <table class="form-table">
                        <tr>
                            <th>API 金鑰</th>
                            <td><input type="password" name="resend_api_key" value="<?php echo esc_attr( $options['resend_api_key'] ?? '' ); ?>" class="regular-text" autocomplete="new-password"></td>
                        </tr>
                    </table>
                </div>

                <!-- Brevo -->
                <div class="sm-card sm-section" id="sm_brevo" style="display:none">
                    <h2>Brevo API 設定</h2>
                    <p>請先在 <a href="https://brevo.com" target="_blank">brevo.com</a> 建立帳號，並在下方輸入 API 金鑰。</p>
                    <table class="form-table">
                        <tr>
                            <th>API 金鑰</th>
                            <td><input type="password" name="brevo_api_key" value="<?php echo esc_attr( $options['brevo_api_key'] ?? '' ); ?>" class="regular-text" autocomplete="new-password"></td>
                        </tr>
                    </table>
                </div>

                <!-- SMTP -->
                <div class="sm-card sm-section" id="sm_smtp" style="display:none">
                    <h2>SMTP 設定</h2>
                    <table class="form-table">
                        <tr>
                            <th>主機</th>
                            <td><input type="text" name="smtp_host" value="<?php echo esc_attr( $options['smtp_host'] ?? '' ); ?>" class="regular-text" placeholder="smtp.example.com"></td>
                        </tr>
                        <tr>
                            <th>埠號</th>
                            <td><input type="number" name="smtp_port" value="<?php echo esc_attr( $options['smtp_port'] ?? '587' ); ?>" class="small-text"></td>
                        </tr>
                        <tr>
                            <th>加密方式</th>
                            <td>
                                <select name="smtp_secure">
                                    <option value="tls"  <?php selected( $options['smtp_secure'] ?? 'tls', 'tls' ); ?>>TLS</option>
                                    <option value="ssl"  <?php selected( $options['smtp_secure'] ?? 'tls', 'ssl' ); ?>>SSL</option>
                                    <option value=""     <?php selected( $options['smtp_secure'] ?? 'tls', '' ); ?>>無</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>帳號</th>
                            <td><input type="text" name="smtp_user" value="<?php echo esc_attr( $options['smtp_user'] ?? '' ); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th>密碼</th>
                            <td><input type="password" name="smtp_pass" value="<?php echo esc_attr( $options['smtp_pass'] ?? '' ); ?>" class="regular-text" autocomplete="new-password"></td>
                        </tr>
                    </table>
                </div>

                <?php submit_button( '儲存設定' ); ?>
            </form>

            <!-- 測試寄信 -->
            <div class="sm-card">
                <h2>測試寄信</h2>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex;gap:10px;align-items:center">
                    <?php wp_nonce_field( 'simple_mailer_test' ); ?>
                    <input type="hidden" name="action" value="simple_mailer_test">
                    <input type="email" name="test_email" value="<?php echo esc_attr( get_option('admin_email') ); ?>" class="regular-text" placeholder="收件信箱">
                    <?php submit_button( '發送測試郵件', 'secondary', 'submit', false ); ?>
                </form>
            </div>
        </div>

        <script>
        function smToggle() {
            var type = document.getElementById('sm_type').value;
            document.querySelectorAll('.sm-section').forEach(function(el){ el.style.display = 'none'; });
            var target = document.getElementById('sm_' + type);
            if (target) target.style.display = 'block';
        }
        smToggle();
        </script>
        <?php
    }

    /* ── 郵件紀錄頁面 HTML ── */
    public function render_logs() {
        $per_page = 20;
        $current  = max( 1, absint( $_GET['paged'] ?? 1 ) );
        $offset   = ( $current - 1 ) * $per_page;
        $logs     = Simple_Mailer_Logger::get_logs( $per_page, $offset );
        $total    = Simple_Mailer_Logger::count();
        $pages    = ceil( $total / $per_page );
        ?>
        <div class="wrap sm-wrap">
            <h1 style="display:flex;justify-content:space-between;align-items:center">
                電子郵件紀錄
                <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" style="margin:0">
                    <?php wp_nonce_field( 'simple_mailer_clear_logs' ); ?>
                    <input type="hidden" name="action" value="simple_mailer_clear_logs">
                    <button class="button button-secondary" onclick="return confirm('確定清除所有紀錄？')">清除所有紀錄</button>
                </form>
            </h1>

            <div class="sm-card" style="padding:0">
                <table class="wp-list-table widefat fixed striped sm-logs-table">
                    <thead>
                        <tr>
                            <th style="width:35%">主旨</th>
                            <th style="width:25%">收件人</th>
                            <th style="width:18%">寄送時間</th>
                            <th style="width:10%">狀態</th>
                            <th style="width:12%">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ( empty( $logs ) ) : ?>
                        <tr><td colspan="5" style="text-align:center;padding:30px;color:#999">尚無郵件紀錄</td></tr>
                    <?php else : ?>
                        <?php foreach ( $logs as $log ) : ?>
                        <tr>
                            <td><?php echo esc_html( $log->subject ); ?></td>
                            <td><?php echo esc_html( $log->sent_to ); ?></td>
                            <td><?php echo esc_html( $log->created_at ); ?></td>
                            <td>
                                <?php if ( $log->status === 'success' ) : ?>
                                    <span class="sm-badge sm-badge-success">成功</span>
                                <?php else : ?>
                                    <span class="sm-badge sm-badge-fail" title="<?php echo esc_attr( $log->message ); ?>">失敗</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" style="display:inline">
                                    <?php wp_nonce_field( 'simple_mailer_delete_log' ); ?>
                                    <input type="hidden" name="action" value="simple_mailer_delete_log">
                                    <input type="hidden" name="log_id" value="<?php echo absint( $log->id ); ?>">
                                    <button class="button button-link-delete" onclick="return confirm('確定刪除？')">刪除</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ( $pages > 1 ) : ?>
            <div class="tablenav-pages" style="margin-top:15px">
                <?php
                echo paginate_links( array(
                    'base'    => add_query_arg( 'paged', '%#%' ),
                    'format'  => '',
                    'current' => $current,
                    'total'   => $pages,
                ) );
                ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
