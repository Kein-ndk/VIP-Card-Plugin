<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

if (!defined('ABSPATH')) exit;

global $wpdb;
$table = $wpdb->prefix . 'ad_cards';

// =============================
// XỬ LÝ THÊM / SỬA / XÓA / GIA HẠN
// =============================
$action = $_GET['action'] ?? '';
$edit_id = intval($_GET['edit'] ?? 0);

// Xóa
if (isset($_GET['delete'])) {
    $wpdb->delete($table, ['id' => intval($_GET['delete'])]);
    echo '<div class="notice notice-success"><p>✅ Đã xóa mã thẻ thành công.</p></div>';
}

// Gia hạn +1 năm
if (isset($_GET['renew'])) {
    $id = intval($_GET['renew']);
    $new_expire = date('Y-m-d H:i:s', strtotime('+1 year', current_time('timestamp')));
    $wpdb->update($table, ['expires_at' => $new_expire], ['id' => $id]);
    echo '<div class="notice notice-success"><p>🎉 Đã gia hạn thêm 1 năm (đến ' . esc_html($new_expire) . ').</p></div>';
}

// Lưu khi thêm/sửa
if (isset($_POST['ad_save_card'])) {
    $code   = sanitize_text_field($_POST['code'] ?? '');
    $status = sanitize_text_field($_POST['status'] ?? 'inactive');
    $uses   = intval($_POST['uses_remaining'] ?? 5);
    $expires = sanitize_text_field($_POST['expires_at'] ?? '');

    if ($edit_id) {
        $wpdb->update($table, [
            'code' => $code,
            'status' => $status,
            'uses_remaining' => $uses,
            'expires_at' => $expires ?: null
        ], ['id' => $edit_id]);
        echo '<div class="notice notice-success"><p>✅ Cập nhật mã thẻ thành công.</p></div>';
    } else {
        $wpdb->insert($table, [
            'code' => $code,
            'status' => $status,
            'uses_remaining' => $uses,
            'expires_at' => $expires ?: null,
            'created_at' => current_time('mysql')
        ]);
        echo '<div class="notice notice-success"><p>✅ Đã thêm mã thẻ mới.</p></div>';
    }
}

// =============================
// FORM THÊM / SỬA
// =============================
if ($action === 'add' || $edit_id) {
    $card = $edit_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $edit_id)) : null;
?>
    <div class="wrap">
        <h1><?php echo $edit_id ? '✏️ Sửa mã thẻ' : '➕ Thêm mã thẻ mới'; ?></h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="code">Mã thẻ</label></th>
                    <td><input type="text" name="code" id="code" required value="<?php echo esc_attr($card->code ?? ''); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="status">Trạng thái</label></th>
                    <td>
                        <select name="status" id="status">
                            <option value="inactive" <?php selected($card->status ?? '', 'inactive'); ?>>Chưa kích hoạt</option>
                            <option value="active" <?php selected($card->status ?? '', 'active'); ?>>Đang hoạt động</option>
                            <option value="expired" <?php selected($card->status ?? '', 'expired'); ?>>Hết hạn</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="uses_remaining">Lượt còn lại</label></th>
                    <td><input type="number" name="uses_remaining" id="uses_remaining" min="0" value="<?php echo esc_attr($card->uses_remaining ?? 5); ?>" class="small-text"></td>
                </tr>
                <tr>
                    <th><label for="expires_at">Ngày hết hạn</label></th>
                    <td>
                        <input type="datetime-local" name="expires_at" id="expires_at"
                            value="<?php echo !empty($card->expires_at) ? esc_attr(date('Y-m-d\TH:i', strtotime($card->expires_at))) : ''; ?>">
                        <?php if ($edit_id): ?>
                            <a href="?page=ad-card-manager&renew=<?php echo $edit_id; ?>"
                                class="button button-secondary"
                                onclick="return confirm('Xác nhận gia hạn thêm 1 năm cho thẻ này?');">➕ Gia hạn +1 năm</a>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <?php submit_button($edit_id ? 'Cập nhật' : 'Thêm mới', 'primary', 'ad_save_card'); ?>
            <a href="<?php echo admin_url('admin.php?page=ad-card-manager'); ?>" class="button">← Quay lại</a>
        </form>
    </div>
<?php
    return;
}

$status_filter = $_GET['status'] ?? '';
$search = $_GET['s'] ?? '';

// ====== Lọc dữ liệu ======
$where = 'WHERE 1=1';
if ($status_filter) {
    $where .= $wpdb->prepare(' AND status = %s', $status_filter);
}
if ($search) {
    $like = '%' . $wpdb->esc_like($search) . '%';
    $where .= $wpdb->prepare(' AND code LIKE %s', $like);
}

$cards = $wpdb->get_results("SELECT * FROM $table $where ORDER BY created_at DESC LIMIT 200");

?>
<div class="wrap">
    <h1 class="wp-heading-inline">📇 Quản lý mã thẻ ưu đãi</h1>
    <a href="?page=ad-card-manager&action=add" class="page-title-action">Thêm mới</a>

    <!-- Bộ lọc và Import/Export -->
    <!-- Bộ lọc và Import/Export -->
    <div style="margin-top:20px; display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
        <form method="get" style="display:flex; flex-wrap:wrap; gap:8px;">
            <input type="hidden" name="page" value="ad-card-manager">

            <select name="status">
                <option value="">-- Lọc theo trạng thái --</option>
                <option value="inactive" <?php selected($status_filter, 'inactive'); ?>>Chưa kích hoạt</option>
                <option value="active" <?php selected($status_filter, 'active'); ?>>Đang hoạt động</option>
                <option value="expired" <?php selected($status_filter, 'expired'); ?>>Hết hạn</option>
            </select>

            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Tìm theo mã thẻ" style="min-width:200px;">
            <button class="button">Lọc</button>
            <a href="?page=ad-card-manager" class="button">Xóa lọc</a>
        </form>

        <!-- Export -->
        <a href="<?php echo admin_url('admin-post.php?action=ad_export_cards'); ?>"
            class="button button-primary">📤 Xuất Excel</a>

        <!-- Import -->
        <form method="post" enctype="multipart/form-data"
            action="<?php echo admin_url('admin-post.php?action=ad_import_cards'); ?>"
            style="display:inline-flex; align-items:center; gap:8px;">
            <input type="file" name="import_file" accept=".xlsx,.xls" required>
            <button type="submit" class="button">📥 Nhập Excel</button>
        </form>
        <!-- Xuất báo cáo -->
        <form method="get" action="<?php echo admin_url('admin-post.php'); ?>" style="display:flex; align-items:center; gap:8px; margin-top:15px;">
            <input type="hidden" name="action" value="ad_export_report">
            <label for="from_date"><strong>Từ ngày:</strong></label>
            <input type="date" name="from_date" required>
            <label for="to_date"><strong>Đến ngày:</strong></label>
            <input type="date" name="to_date" required>
            <button type="submit" class="button button-primary">📊 Xuất báo cáo</button>
        </form>
        <?php if (isset($_GET['report']) && $_GET['report'] === 'no_data'): ?>
            <div class="notice notice-warning" style="margin-top:10px;">
                <p>⚠️ Không có dữ liệu trong khoảng thời gian này.</p>
            </div>
        <?php endif; ?>


    </div>


    <!-- Bảng danh sách -->
    <table class="widefat striped" style="margin-top:20px;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Mã thẻ</th>
                <th>Trạng thái</th>
                <th>Ngày kích hoạt</th>
                <th>Ngày hết hạn</th>
                <th>Lượt còn lại</th>
                <th>Ngày tạo</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($cards): ?>
                <?php foreach ($cards as $c): ?>
                    <tr>
                        <td><?php echo esc_html($c->id); ?></td>
                        <td><strong><?php echo esc_html($c->code); ?></strong></td>
                        <td>
                            <?php
                            $color = match ($c->status) {
                                'active'   => 'green',
                                'inactive' => 'gray',
                                'expired'  => 'red',
                                default    => '#000',
                            };
                            ?>
                            <span style="color:<?php echo $color; ?>; font-weight:bold;">
                                <?php echo esc_html(ucfirst($c->status)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($c->activated_at ?: '-'); ?></td>
                        <td><?php echo esc_html($c->expires_at ?: '-'); ?></td>
                        <td><?php echo esc_html($c->uses_remaining); ?></td>
                        <td><?php echo esc_html($c->created_at); ?></td>
                        <td>
                            <a href="?page=ad-card-manager&edit=<?php echo $c->id; ?>" class="button">Sửa</a>
                            <a href="?page=ad-card-manager&delete=<?php echo $c->id; ?>"
                                onclick="return confirm('Xác nhận xóa mã này?');"
                                class="button button-danger">Xóa</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align:center;">Không có dữ liệu.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
/**
 * =============================
 * XUẤT FILE EXCEL
 * =============================
 */
add_action('admin_post_ad_export_cards', 'ad_export_cards_to_excel');
function ad_export_cards_to_excel()
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

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Header
    $headers = ['Mã thẻ', 'Trạng thái', 'Ngày kích hoạt', 'Ngày hết hạn', 'Số lượt còn lại'];
    $sheet->fromArray([$headers], NULL, 'A1');

    // Data
    $row = 2;
    foreach ($cards as $card) {
        $sheet->fromArray([
            [$card->code, $card->status, $card->activated_at, $card->expires_at, $card->uses_remaining]
        ], NULL, 'A' . $row);
        $row++;
    }

    // Xuất file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="danh-sach-ma-the.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

/**
 * =============================
 * NHẬP FILE EXCEL
 * =============================
 */
add_action('admin_post_ad_import_cards', 'ad_import_cards_from_excel');
function ad_import_cards_from_excel()
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
        $spreadsheet = IOFactory::load($file_path);
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
