<div class="appointment-container">
  <h2 class="appointment-title">Chọn ngày, giờ và dịch vụ Quý khách mong muốn.</h2>
  <p class="appointment-subtitle">
    Các dịch vụ trong danh mục ưu đãi 50% hoặc 2 gói khám được hiển thị bên dưới.
  </p>

  <div class="appointment-packages">
    <div class="package-card">
      <div class="package-text">
        <strong>Thông tin thêm về các gói dịch vụ</strong>
      </div>
      <button type="button" class="package-btn" data-pdf="giam50.pdf">Xem danh sách dịch vụ giảm 50%</button>
      <button type="button" class="package-btn" data-pdf="toandien.pdf">Gói khám Toàn Diện - Tầm Soát Ung Thư giảm 50%</button>
      <button type="button" class="package-btn" data-pdf="phieukhamtongquat.pdf">Gói khám Tổng Quát - Tầm Soát Ung Thư giảm 50%</button>
    </div>

    <!-- Modal PDF Viewer -->
    <div class="ad-modal" id="ad-pdf-modal">
      <div class="ad-modal-inner">
        <div class="ad-modal-content">
          <span class="ad-modal-close">&times;</span>
          <iframe id="ad-pdf-frame" src="" frameborder="0"></iframe>
        </div>
      </div>
    </div>

  </div>

  <?php if (!empty($error)): ?>
    <div class="ad-alert ad-alert--error"><?php echo esc_html($error); ?></div>
  <?php endif; ?>

  <div class="appointment-form-wrapper">
    <div class="customer-info">
      <h3>Thông tin cá nhân</h3>
      <p><strong>Họ và tên:</strong> <?php echo esc_html($customer->full_name); ?></p>
      <p><strong>Số điện thoại:</strong> <?php echo esc_html($customer->phone); ?></p>
      <p><strong>Ngày sinh:</strong> <?php echo esc_html($customer->dob ?: '-'); ?></p>
      <p><strong>Giới tính:</strong> <?php echo esc_html($customer->gender ?: '-'); ?></p>
      <p><strong>Email:</strong> <?php echo esc_html($customer->email ?: '-'); ?></p>
      <p><strong>Dịch vụ quan tâm:</strong> <?php echo esc_html($customer->service ?: '-'); ?></p>

      <label>Ghi chú:</label>
      <textarea name="ad_note" form="bookingForm" placeholder="Tôi mong muốn tham khảo về gói sức khoẻ"><?php echo esc_textarea($_POST['ad_note'] ?? ''); ?></textarea>
    </div>

    <?php if ($success): ?>
      <!-- Popup success -->
      <div id="ad-appointment-overlay" class="ad-overlay show">
        <div class="ad-modal-success">
          <div class="ad-modal-body">
            <div class="ad-modal-icon">✔️</div>
            <h3>Đăng ký lịch khám thành công</h3>
            <p>Xin cảm ơn bạn đã đăng ký lịch khám. Chúng tôi rất hân hạnh được phục vụ.</p>
          </div>
          <div class="ad-modal-actions">
            <button id="ad-modal-success-close" class="ad-btn ad-btn-primary">Đóng</button>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <form id="bookingForm" method="post" class="booking-form" novalidate>
      <label>Chọn ngày khám <span style="color: red;">*</span></label>
      <input type="date" name="ad_datetime" required value="<?php echo esc_attr($_POST['ad_datetime'] ?? ''); ?>" />

      <label>Chọn giờ khám <span style="color: red;">*</span></label>
      <select name="ad_time" required>
        <option value="">-- Chọn khung giờ --</option>
        <option value="07:30" <?php selected($_POST['ad_time'] ?? '', '07:30'); ?>>Sáng sớm (7:30 – 9:00)</option>
        <option value="09:00" <?php selected($_POST['ad_time'] ?? '', '09:00'); ?>>Buổi sáng (9:00 – 11:00)</option>
        <option value="13:30" <?php selected($_POST['ad_time'] ?? '', '13:30'); ?>>Buổi chiều (13:30 – 15:00)</option>
      </select>

      <label>Chọn dịch vụ <span style="color: red;">*</span></label>
      <select name="ad_service" required>
        <option value="">-- Chọn dịch vụ --</option>
        <option value="Gói Toàn Diện - Tầm Soát Ung Thư" <?php selected($_POST['ad_service'] ?? '', 'Gói Toàn Diện - Tầm Soát Ung Thư'); ?>>Gói Khám - Toàn Diện - Tầm Soát Ung Thư Giảm 50%</option>
        <option value="Gói Tổng Quát - Tầm Soát Ung Thư" <?php selected($_POST['ad_service'] ?? '', 'Gói Tổng Quát - Tầm Soát Ung Thư'); ?>>Gói Khám - Tổng Quát - Tầm Soát Ung Thư Giảm 50%</option>
        <option value="Dịch Vụ Giảm 50%" <?php selected($_POST['ad_service'] ?? '', 'Dịch Vụ Giảm 50%'); ?>>Dịch Vụ Giảm 50%</option>
      </select>

      <button type="submit" name="ad_appointment_submit" class="btn-submit">Xác nhận đặt lịch khám</button>
    </form>
  </div>
</div>

<?php if ($success): ?>
  <script>
    const overlay = document.getElementById('ad-appointment-overlay');
    const closeBtn = document.getElementById('ad-modal-success-close');
    const redirectUrl = "<?php echo esc_url(home_url('/')); ?>"; // về trang chủ

    function closeAndRedirect() {
      overlay.classList.remove('show');
      setTimeout(() => {
        window.location.href = redirectUrl;
      }, 300); // thêm 0.3s cho cảm giác mượt
    }

    closeBtn?.addEventListener('click', closeAndRedirect);
    overlay?.addEventListener('click', e => {
      if (e.target === overlay) closeAndRedirect(); // click ra ngoài modal
    });
  </script>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const modal = document.getElementById('ad-pdf-modal');
  const iframe = document.getElementById('ad-pdf-frame');
  const closeBtn = document.querySelector('.ad-modal-close');

  // Di chuyển modal ra body
  if (modal && modal.parentNode !== document.body) {
    document.body.appendChild(modal);
  }

  // Khi click nút xem PDF
  document.querySelectorAll('.package-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const pdfFile = this.dataset.pdf;
      iframe.src = `${window.location.origin}/wp-content/uploads/2025/11/${pdfFile}`;
      modal.style.display = 'flex'; // hiển thị modal
      document.body.style.overflow = 'hidden';
    });
  });

  // Đóng khi click nút X
  closeBtn.addEventListener('click', () => {
    modal.style.display = 'none';
    iframe.src = '';
    document.body.style.overflow = '';
  });

  // Đóng khi click ra ngoài
  modal.addEventListener('click', (e) => {
    if (e.target === modal) {
      modal.style.display = 'none';
      iframe.src = '';
      document.body.style.overflow = '';
    }
  });
});
document.addEventListener('DOMContentLoaded', function() {
  const modal = document.getElementById('ad-pdf-modal');
  if (modal && modal.parentNode !== document.body) {
    document.body.appendChild(modal);
  }
});
</script>

