<?php

/**
 * Plugin Name: AnhDuong Cards
 * Plugin URI:  http://localhost
 * Description: Quản lý thẻ ưu đãi 50% — xác thực mã, đăng ký khách, đặt lịch, gia hạn và cảnh báo.
 * Version:     1.0.0
 * Author:      Kein
 * Text Domain: anhduong-cards
 */

require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use function PHPSTORM_META\type;

if (! defined('ABSPATH')) {
    exit;
}

register_activation_hook(__FILE__, 'ad_cards_activate');
register_deactivation_hook(__FILE__, 'ad_cards_deactivate');

function ad_cards_activate()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $prefix = $wpdb->prefix;

    $sql = [];

    // === BẢNG MÃ THẺ ===
    $sql[] = "CREATE TABLE {$prefix}ad_cards (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      code VARCHAR(100) NOT NULL,
      status VARCHAR(30) NOT NULL DEFAULT 'inactive', -- inactive/active/expired
      issued_to BIGINT UNSIGNED NULL,
      activated_at DATETIME NULL,
      expires_at DATETIME NULL,
      uses_remaining INT DEFAULT 5,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      UNIQUE KEY code (code)
    ) $charset_collate;";

    // === BẢNG KHÁCH HÀNG ===
    $sql[] = "CREATE TABLE {$prefix}ad_customers (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      card_code VARCHAR(100) NULL,
      full_name VARCHAR(200) NOT NULL,
      phone VARCHAR(50) NOT NULL,
      dob DATE NULL,
      gender VARCHAR(20) NULL,
      email VARCHAR(200) NULL,
      address TEXT NULL,
      service VARCHAR(200) NULL,
      note TEXT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      UNIQUE KEY phone (phone)
    ) $charset_collate;";

    // === BẢNG LỊCH KHÁM ===
    $sql[] = "CREATE TABLE {$prefix}ad_appointments (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      customer_id BIGINT UNSIGNED NOT NULL,
      card_id BIGINT UNSIGNED NULL,
      service VARCHAR(255) NOT NULL,
      appointment_at DATETIME NOT NULL,
      note TEXT NULL,
      status VARCHAR(50) DEFAULT 'pending',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id)
    ) $charset_collate;";

    // === Thực thi tạo bảng ===
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    foreach ($sql as $s) {
        dbDelta($s);
    }

    // ✅ Không tạo mã thẻ mẫu tự động nữa
}


function ad_cards_deactivate()
{
    // Không xóa dữ liệu ở deactivation; giữ DB. Nếu muốn xóa, dùng uninstall.php
}

// ===== Shortcode: [ad_card_verification] =====
add_shortcode('ad_card_verification', 'ad_render_card_verification');
function ad_render_card_verification()
{
    ob_start();
    wp_enqueue_style('ad-card-style', plugins_url('assets/css/ad-card.css', __FILE__));
    wp_enqueue_script('ad-card-js', plugins_url('assets/js/ad-card.js', __FILE__), ['jquery'], null, true);

    // Truyền biến PHP → JS
    wp_localize_script('ad-card-js', 'adCardData', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'site_url' => site_url(),
    ]);
    wp_enqueue_script('ad-verify-js', plugins_url('assets/js/ad-verify.js', __FILE__), ['jquery'], null, true);
    wp_localize_script('ad-verify-js', 'adCardAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'site_url' => site_url(),
    ]);

    $prefilled_code = sanitize_text_field($_GET['code'] ?? '');
    $status = $_GET['status'] ?? '';
    $code = $prefilled_code;
    include plugin_dir_path(__FILE__) . 'assets/templates/card-verification.php';
    return ob_get_clean();
}


// ===== Shortcode: [ad_card_register] =====
add_shortcode('ad_customer_register', 'ad_render_customer_register');
function ad_render_customer_register()
{
    global $wpdb;
    $table = $wpdb->prefix . 'ad_customers';
    $cards_table = $wpdb->prefix . 'ad_cards';
    $success = false;

    if (isset($_POST['ad_register_submit'])) {

        $card_code = sanitize_text_field($_GET['code'] ?? '');
        $phone     = sanitize_text_field($_POST['ad_phone'] ?? '');
        $email     = sanitize_email($_POST['ad_email'] ?? '');
        $name      = sanitize_text_field($_POST['ad_name'] ?? '');
        $dob       = sanitize_text_field($_POST['ad_birth'] ?? '');
        $gender    = sanitize_text_field($_POST['ad_gender'] ?? '');
        $service   = sanitize_text_field($_POST['ad_service'] ?? '');
        $address   = sanitize_text_field($_POST['ad_address'] ?? '');
        $note      = sanitize_textarea_field($_POST['ad_note'] ?? '');

        // kiểm tra thẻ tồn tại
        $card = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$cards_table} WHERE code = %s LIMIT 1", $card_code)
        );

        if (!$card) {
            echo '<pre style="color:red;">❌ Không tìm thấy mã thẻ trong hệ thống.</pre>';
            return;
        }

        // kiểm tra khách hàng đã tồn tại chưa
        $existing = $wpdb->get_row(
            $wpdb->prepare("SELECT id FROM {$table} WHERE phone = %s LIMIT 1", $phone)
        );

        // --- Kiểm tra trạng thái thẻ ---
        $now = current_time('timestamp');
        
        if (!$card) {
            echo '<p style="color:red;">❌ Không tìm thấy mã thẻ trong hệ thống.</p>';
            return;
        }

        // Nếu thẻ hết hạn
        if (!empty($card->expires_at) && strtotime($card->expires_at) < $now) {
            echo '<p style="color:red;">❌ Thẻ đã hết hạn, vui lòng gia hạn để tiếp tục.</p>';
            return;
        }

        // Nếu thẻ chưa kích hoạt thì cho phép đăng ký (kích hoạt ngay sau khi đăng ký)
        // Nếu thẻ đang active thì cho phép tiếp tục
        if ($card->status !== 'active' && $card->status !== 'inactive') {
            echo '<p style="color:red;">❌ Trạng thái thẻ không hợp lệ.</p>';
            return;
        }

        if ($existing) {
            // khách cũ → chuyển thẳng sang trang đặt lịch
            wp_redirect(home_url('/dat-lich-kham?code=' . urlencode($card_code) . '&phone=' . urlencode($phone)));
            exit;
        }

        // thêm khách hàng mới
        $wpdb->insert($table, [
            'card_code' => $card_code,
            'full_name' => $name,
            'phone'     => $phone,
            'dob'       => $dob,
            'gender'    => $gender,
            'email'     => $email,
            'service'   => $service,
            'address'   => $address,
            'note'      => $note,
        ]);

        if ($wpdb->insert_id) {
            $success = true;

            // Nếu thẻ đang inactive, kích hoạt và gia hạn thêm 1 năm
            if ($card->status === 'inactive') {
                $now = current_time('mysql');
                $expires = date('Y-m-d H:i:s', strtotime('+1 year', strtotime($now)));

                $wpdb->query(
                    $wpdb->prepare("
                UPDATE {$cards_table}
                SET 
                    status = 'active',
                    activated_at = %s,
                    expires_at = %s
                WHERE code = %s
            ", $now, $expires, $card_code)
                );
            }

            // Gửi email admin
            $admin_email = 'bladedarkkien@gmail.com';
            $subject = 'Khách hàng mới đăng ký thẻ ưu đãi';
            $message = "Một khách hàng mới đã đăng ký:\n\n"
                . "Họ tên: $name\n"
                . "SĐT: $phone\n"
                . "Email: $email\n"
                . "Giới tính: $gender\n"
                . "Ngày sinh: $dob\n"
                . "Dịch vụ: $service\n"
                . "Mã thẻ: $card_code\n"
                . "Ghi chú: $note\n";

            wp_mail($admin_email, $subject, $message);

            // ✅ Gửi xác nhận cho khách
            if (!empty($email)) {
                wp_mail(
                    $email,
                    'Bệnh viện Ánh Dương - Xác nhận đăng ký',
                    "Cảm ơn $name đã đăng ký thành công.\nChúng tôi sẽ liên hệ xác nhận lịch hẹn sớm nhất."
                );
            }

            // ✅ Chuyển hướng sang trang đặt lịch
            wp_redirect(home_url('/dat-lich-kham?code=' . urlencode($card_code) . '&phone=' . urlencode($phone)));
            exit;
        }

        // debug lỗi DB nếu có
        if ($wpdb->last_error) {
            echo '<pre style="color:red;">❌ DB Error: ' . esc_html($wpdb->last_error) . '</pre>';
        }
    }


    // Load CSS + giao diện
    wp_enqueue_style('ad-register-style', plugins_url('assets/css/ad-register.css', __FILE__));
    include plugin_dir_path(__FILE__) . 'assets/templates/card-register.php';
    return ob_get_clean();
}

// ===== Shortcode: [ad_appointment_booking] =====
add_shortcode('ad_appointment_booking', 'ad_render_appointment_booking');
function ad_render_appointment_booking()
{
    global $wpdb;

    $appointments_table = $wpdb->prefix . 'ad_appointments';
    $customers_table = $wpdb->prefix . 'ad_customers';
    $cards_table = $wpdb->prefix . 'ad_cards';
    $success = false;
    $error = '';

    $card_code = sanitize_text_field($_GET['code'] ?? '');
    $phone     = sanitize_text_field($_GET['phone'] ?? '');

    // Kiểm tra khách hàng và thẻ
    $customer = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$customers_table} WHERE phone = %s LIMIT 1", $phone));
    $card = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$cards_table} WHERE code = %s LIMIT 1", $card_code));

    if (!$customer || !$card) {
        echo '<p style="color:red;">❌ Không tìm thấy thông tin khách hàng hoặc mã thẻ.</p>';
        return;
    }

    // Xử lý khi submit form đặt lịch
    if (isset($_POST['ad_appointment_submit'])) {
        $service = sanitize_text_field($_POST['ad_service'] ?? '');
        $datetime = sanitize_text_field($_POST['ad_datetime'] ?? '');
        $note = sanitize_textarea_field($_POST['ad_note'] ?? '');

        if (empty($service) || empty($datetime)) {
            $error = 'Vui lòng chọn dịch vụ và thời gian khám.';
        } else {
            $now = current_time('timestamp');

            // Kiểm tra hạn thẻ
            if (!empty($card->expires_at) && strtotime($card->expires_at) < $now) {
                echo '<p style="color:red;">❌ Thẻ đã hết hạn, vui lòng gia hạn để tiếp tục đặt lịch.</p>';
                return;
            }

            if ($card->status !== 'active') {
                echo '<p style="color:red;">❌ Thẻ chưa được kích hoạt. Vui lòng đăng ký trước khi đặt lịch.</p>';
                return;
            }

            // Thêm lịch khám
            $wpdb->insert($appointments_table, [
                'customer_id'     => $customer->id,
                'card_id'         => $card->id,
                'service'         => $service,
                'appointment_at'  => $datetime,
                'note'            => $note,
                'status'          => 'pending',
            ]);

            if ($wpdb->insert_id) {
                $success = true;

                // Trừ lượt sử dụng
                $wpdb->query($wpdb->prepare("
                    UPDATE {$cards_table}
                    SET 
                        uses_remaining = GREATEST(uses_remaining - 1, 0),
                        status = CASE 
                                    WHEN uses_remaining - 1 <= 0 THEN 'expired' 
                                    ELSE status 
                                END
                    WHERE code = %s
                ", $card_code));

                /**
                 * ============================
                 * TẠO FILE EXCEL TẠM & GỬI MAIL
                 * ============================
                 */
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();

                // Header
                $headers = ['Họ tên', 'SĐT', 'Ngày sinh', 'Mã thẻ', 'Trạng thái thẻ', 'Ngày kích hoạt', 'Ngày hết hạn', 'Ngày hẹn khám', 'Ghi chú'];
                $sheet->fromArray([$headers], NULL, 'A1');

                // Dữ liệu khách hàng
                $sheet->fromArray([[
                    $customer->full_name,
                    $customer->phone,
                    $customer->dob,
                    $card->code,
                    $card->status,
                    $card->activated_at,
                    $card->expires_at,
                    $datetime,
                    $note
                ]], NULL, 'A2');

                // Tạo file tạm
                // Tạo file tạm có đuôi .xlsx
                $tmpFilePath = tempnam(sys_get_temp_dir(), 'thong_tin_lich_kham_') . '.xlsx';
                $writer = new Xlsx($spreadsheet);
                $writer->save($tmpFilePath);


                // Gửi mail cho admin
                $admin_emails = [
                    get_option('admin_email'),
                    'bladedarkkien@gmail.com', // Thêm email khác nếu cần
                ];

                $subject = '🗓 Khách hàng đặt lịch khám mới';
                $message = "Khách hàng: {$customer->full_name}\n"
                    . "SĐT: {$customer->phone}\n"
                    . "Email: {$customer->email}\n"
                    . "Dịch vụ: {$service}\n"
                    . "Thời gian: {$datetime}\n"
                    . "Mã thẻ: {$card_code}\n"
                    . "Ghi chú: {$note}\n";

                foreach ($admin_emails as $mail) {
                    wp_mail($mail, $subject, $message, [], [$tmpFilePath]);
                }

                // Gửi xác nhận cho khách
                if (!empty($customer->email)) {
                    wp_mail(
                        $customer->email,
                        'Bệnh viện Ánh Dương - Xác nhận lịch khám',
                        "Xin chào {$customer->full_name},\n"
                            . "Cảm ơn bạn đã đặt lịch khám dịch vụ '{$service}' vào {$datetime}.\n"
                            . "Chúng tôi sẽ liên hệ xác nhận trong thời gian sớm nhất."
                    );
                }

                // Xóa file tạm
                if (file_exists($tmpFilePath)) {
                    unlink($tmpFilePath);
                }
            } else {
                $error = 'Không thể lưu lịch khám. Vui lòng thử lại.';
            }
        }
    }

    // Hiển thị form
    wp_enqueue_style('ad-appointment-style', plugins_url('assets/css/ad-appointment.css', __FILE__));
    include plugin_dir_path(__FILE__) . 'assets/templates/appointment-booking.php';

    return ob_get_clean();
}


// ===== Shortcode: [ad_card_renewal] =====
add_shortcode('ad_card_renewal', 'ad_render_card_renewal');
function ad_render_card_renewal()
{
    global $wpdb;
    $cards_table = $wpdb->prefix . 'ad_cards';
    $customers_table = $wpdb->prefix . 'ad_customers';
    $success = false;
    $error = '';

    $card_code = sanitize_text_field($_GET['code'] ?? '');

    if (empty($card_code)) {
        wp_redirect(home_url('/'));
        exit;
    }

    // Lấy thông tin thẻ và chủ thẻ
    $card = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$cards_table} WHERE code = %s", $card_code));
    if (!$card) {
        echo '<p style="color:red;">❌ Không tìm thấy mã thẻ.</p>';
        return;
    }

    $customer = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$customers_table} WHERE card_code = %s LIMIT 1", $card_code));

    // Xử lý khi nhấn "Gia hạn thẻ"
    if (isset($_POST['ad_renew_submit'])) {
        $success = true;

        // Gửi email thông báo nội bộ (KHÔNG cập nhật DB)
        $to = 'bladedarkkien@gmail.com'; // email nhận thông báo nội bộ
        $subject = '🔁 Yêu cầu gia hạn thẻ khách hàng';
        $message = sprintf(
            "Khách hàng: %s\nSĐT: %s\nEmail: %s\nMã thẻ: %s\nNgày hết hạn: %s\n\nKhách hàng đã gửi yêu cầu GIA HẠN THẺ qua hệ thống.",
            $customer ? $customer->full_name : '(Chưa rõ)',
            $customer ? $customer->phone : '(Chưa rõ)',
            $customer ? $customer->email : '(Chưa rõ)',
            $card_code,
            $card->expires_at ?: '(Chưa có dữ liệu)'
        );

        wp_mail($to, $subject, $message);
    }

    // Load CSS + template
    wp_enqueue_style('ad-renew-style', plugins_url('assets/css/ad-renew.css', __FILE__));
    include plugin_dir_path(__FILE__) . 'assets/templates/card-renewal.php';
    return ob_get_clean();
}



/**
 * =============================
 * ẨN & CHẶN TRUY CẬP TRANG NỘI BỘ
 * =============================
 */
add_action('template_redirect', function () {
    if (!is_page()) return;

    global $post;
    $slug = $post->post_name;

    // Danh sách các trang cần ẩn/chặn
    $blocked = ['dang-ky-thong-tin-khach-hang', 'dat-lich-kham'];

    // Nếu không thuộc danh sách thì bỏ qua
    if (!in_array($slug, $blocked, true)) return;

    // Cho phép truy cập nếu:
    // - Trang "đăng ký" có ?code
    // - Trang "đặt lịch" có ?code và ?phone
    $has_access =
        ($slug === 'dang-ky-thong-tin-khach-hang' && !empty($_GET['code'])) ||
        ($slug === 'dat-lich-kham' && !empty($_GET['code']) && !empty($_GET['phone']));

    // Nếu không đủ điều kiện → quay về trang chủ
    if (!$has_access) {
        wp_redirect(home_url('/'));
        exit;
    }
});

/**
 * Ẩn các trang nội bộ khỏi danh sách / menu tự động
 */
add_filter('wp_list_pages_excludes', function ($exclude_ids) {
    $slugs = ['dang-ky-thong-tin', 'dat-lich-kham'];
    foreach ($slugs as $slug) {
        $page = get_page_by_path($slug);
        if ($page) {
            $exclude_ids[] = $page->ID;
        }
    }
    return $exclude_ids;
});

add_action('wp_ajax_ad_card_check', 'ad_ajax_card_check');
add_action('wp_ajax_nopriv_ad_card_check', 'ad_ajax_card_check');
function ad_ajax_card_check()
{
    global $wpdb;
    $cards_table = $wpdb->prefix . 'ad_cards';
    $customers_table = $wpdb->prefix . 'ad_customers';

    $code = sanitize_text_field($_POST['code'] ?? '');
    if (empty($code)) {
        wp_send_json(['status' => 'invalid']);
    }

    $card = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$cards_table} WHERE code = %s LIMIT 1", $code));
    if (!$card) {
        wp_send_json(['status' => 'invalid']);
    }

    $now = current_time('timestamp');

    // === 1️⃣ Thẻ chưa kích hoạt (chưa có activated_at, expires_at) ===
    if (empty($card->activated_at) && empty($card->expires_at)) {
        wp_send_json([
            'status' => 'inactive',
            'redirect' => home_url('/dang-ky-thong-tin-khach-hang?code=' . urlencode($code))
        ]);
    }

    // === 2️⃣ Thẻ đã hết hạn ===
    if (!empty($card->expires_at) && strtotime($card->expires_at) < $now) {
        wp_send_json([
            'status' => 'expired',
            'redirect' => home_url('/gia-han-the?code=' . urlencode($code))
        ]);
    }

    // === 3️⃣ Thẻ đang active và còn hạn ===
    if ($card->status === 'active' && strtotime($card->expires_at) >= $now) {
        // kiểm tra có khách hàng nào đã đăng ký mã này chưa
        $customer = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$customers_table} WHERE card_code = %s LIMIT 1", $code));

        if ($customer) {
            wp_send_json([
                'status' => 'valid',
                'redirect' => home_url('/dat-lich-kham?code=' . urlencode($code) . '&phone=' . urlencode($customer->phone))
            ]);
        } else {
            wp_send_json([
                'status' => 'valid',
                'redirect' => home_url('/dang-ky-thong-tin-khach-hang?code=' . urlencode($code))
            ]);
        }
    }

    // === 4️⃣ Trường hợp còn lại (phòng hờ dữ liệu sai) ===
    wp_send_json(['status' => 'invalid']);
}


// =========================
// TRANG QUẢN LÝ MÃ THẺ (Admin)
// =========================
add_action('admin_menu', function () {
    add_menu_page(
        'Quản lý thẻ ưu đãi',          // Tiêu đề trang
        'Thẻ ưu đãi',                 // Tên hiển thị trong menu
        'manage_options',             // Quyền truy cập
        'ad-card-manager',            // Slug
        'ad_render_card_manager_page', // Callback hiển thị
        'dashicons-id',               // Icon WordPress
        26                            // Vị trí menu
    );
});

function ad_render_card_manager_page()
{
    include plugin_dir_path(__FILE__) . 'admin/cards-page.php';
}

/**
 * =============================
 * IMPORT / EXPORT MÃ THẺ (ADMIN)
 * =============================
 */
add_action('admin_post_ad_export_cards', 'ad_export_cards');
add_action('admin_post_ad_import_cards', 'ad_import_cards');

/**
 * Xuất Excel danh sách thẻ
 */
function ad_export_cards()
{
    if (!current_user_can('manage_options')) {
        wp_die('Bạn không có quyền thực hiện thao tác này.');
    }

    global $wpdb;
    $table = $wpdb->prefix . 'ad_cards';
    $cards = $wpdb->get_results("SELECT * FROM {$table}");

    if (empty($cards)) {
        wp_die('Không có dữ liệu để xuất.');
    }

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $headers = ['Mã thẻ', 'Trạng thái', 'Ngày kích hoạt', 'Ngày hết hạn', 'Số lượt còn lại'];
    $sheet->fromArray([$headers], NULL, 'A1');

    $row = 2;
    foreach ($cards as $card) {
        $sheet->fromArray([
            [$card->code, $card->status, $card->activated_at, $card->expires_at, $card->uses_remaining]
        ], NULL, 'A' . $row);
        $row++;
    }

    // Xuất file tải xuống
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="danh-sach-ma-the.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

/**
 * Nhập Excel (Import)
 */
function ad_import_cards()
{
    if (!current_user_can('manage_options')) {
        wp_die('Bạn không có quyền thực hiện thao tác này.');
    }

    if (empty($_FILES['import_file']['tmp_name'])) {
        wp_redirect(admin_url('admin.php?page=ad-card-manager&import=error'));
        exit;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'ad_cards';
    $file_path = $_FILES['import_file']['tmp_name'];

    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        foreach (array_slice($rows, 1) as $r) {
            [$code, $status, $activated, $expires, $uses] = $r;
            if (empty($code)) continue;

            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE code = %s", $code));
            if ($exists) {
                $wpdb->update($table, [
                    'status' => $status ?: 'inactive',
                    'activated_at' => $activated ?: null,
                    'expires_at' => $expires ?: null,
                    'uses_remaining' => (int)($uses ?: 5),
                ], ['code' => $code]);
            } else {
                $wpdb->insert($table, [
                    'code' => $code,
                    'status' => $status ?: 'inactive',
                    'activated_at' => $activated ?: null,
                    'expires_at' => $expires ?: null,
                    'uses_remaining' => (int)($uses ?: 5),
                ]);
            }
        }

        wp_redirect(admin_url('admin.php?page=ad-card-manager&import=success'));
        exit;
    } catch (Exception $e) {
        error_log('Import Excel lỗi: ' . $e->getMessage());
        wp_redirect(admin_url('admin.php?page=ad-card-manager&import=error'));
        exit;
    }
}

// Đăng ký handler export báo cáo (luôn được load bởi plugin)
add_action('admin_post_ad_export_report', 'ad_export_report_to_excel');
function ad_export_report_to_excel()
{
    if (!current_user_can('manage_options')) {
        wp_die('Bạn không có quyền thực hiện thao tác này.');
    }

    global $wpdb;
    $appointments_table = $wpdb->prefix . 'ad_appointments';
    $customers_table    = $wpdb->prefix . 'ad_customers';
    $cards_table        = $wpdb->prefix . 'ad_cards';

    $from_date = sanitize_text_field($_GET['from_date'] ?? '');
    $to_date   = sanitize_text_field($_GET['to_date'] ?? '');

    if (empty($from_date) || empty($to_date)) {
        wp_redirect(admin_url('admin.php?page=ad-card-manager&report=missing_date'));
        exit;
    }

    // Lấy dữ liệu theo created_at của ad_appointments
    $rows = $wpdb->get_results($wpdb->prepare("
        SELECT c.full_name, c.phone, c.dob, ca.code, ca.status, ca.activated_at, ca.expires_at,
               a.appointment_at, a.note
        FROM {$appointments_table} a
        JOIN {$customers_table} c ON a.customer_id = c.id
        JOIN {$cards_table} ca ON a.card_id = ca.id
        WHERE DATE(a.created_at) BETWEEN %s AND %s
        ORDER BY a.created_at DESC
    ", $from_date, $to_date));

    if (empty($rows)) {
        // Quay lại trang admin với thông báo
        wp_redirect(admin_url('admin.php?page=ad-card-manager&report=no_data'));
        exit;
    }

    // Tạo file Excel
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $headers = ['Họ tên', 'SĐT', 'Ngày sinh', 'Mã thẻ', 'Trạng thái thẻ', 'Ngày kích hoạt', 'Ngày hết hạn', 'Ngày hẹn khám', 'Ghi chú'];
    $sheet->fromArray([$headers], NULL, 'A1');

    $row = 2;
    foreach ($rows as $r) {
        $sheet->fromArray([[
            $r->full_name,
            $r->phone,
            $r->dob,
            $r->code,
            ucfirst($r->status),
            $r->activated_at,
            $r->expires_at,
            $r->appointment_at,
            $r->note
        ]], NULL, 'A' . $row);
        $row++;
    }

    // Xuất file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="bao-cao-lich-kham-' . $from_date . '-den-' . $to_date . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
