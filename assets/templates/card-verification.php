<div class="ad-container">
    <div class="ad-content">
        <h1 class="ad-title">Chào mừng Quý khách đến với Phòng khám Ánh Dương!</h1>
        <p class="ad-subtitle">Vui lòng nhập mã thẻ ưu đãi để xem các gói dịch vụ giảm 50% và đặt lịch khám thuận tiện nhất.</p>

        <form method="post" class="ad-form">
            <label class="ad-label" for="ad_card_code">Nhập mã thẻ để tiếp tục<span style="color:red;">*</span></label>
            <input type="text" name="ad_card_code" id="ad_card_code"
                value="<?php echo esc_attr($prefilled_code); ?>"
                required placeholder="Nhập mã" class="ad-input"
                style="<?php
                        if ($status === 'invalid') echo 'border-color:#D93025;';
                        elseif ($status === 'expired') echo 'border-color:#FFB800;';
                        ?>">

            <?php if (in_array($status, ['invalid', 'expired'])): ?>
                <div class="ad-error-inline" style="color:<?php echo $status === 'invalid' ? '#D93025' : '#A67C00'; ?>">
                    <span class="ad-icon">!</span>
                    <span><?php echo $status === 'invalid' ? 'Mã không tồn tại' : 'Mã đã hết hạn'; ?></span>
                </div>
            <?php endif; ?>

            <button type="submit" name="ad_card_submit" class="ad-btn">Xác nhận</button>
        </form>

        <?php if ($status === 'invalid' || $status === 'expired'): ?>
            <div class="ad-alert <?php echo $status === 'invalid' ? 'ad-alert--error' : 'ad-alert--warning'; ?>">
                <p style="margin:0 0 10px;">
                    <?php echo $status === 'invalid'
                        ? 'Vui lòng kiểm tra lại mã thẻ hoặc liên hệ qua Hotline: <b>0913.891.578</b>'
                        : 'Vui lòng gia hạn thẻ hoặc liên hệ qua Hotline <b>0913.891.578</b>'; ?>
                </p>
                <div>
                    <?php if ($status === 'invalid'): ?>
                        <a href="#" style="background:#0057FF; color:#fff; padding:10px 20px; border-radius:6px; text-decoration:none;">Liên hệ với chúng tôi</a>
                    <?php else: ?>
                        <a href="#" style="background:#0057FF; color:#fff; padding:10px 20px; border-radius:6px; text-decoration:none; margin-right:10px;">Liên hệ hotline</a>
                        <a href="#" style="background:#FFB800; color:#000; padding:10px 20px; border-radius:6px; text-decoration:none;">Gia hạn thẻ</a>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($status === 'valid'): ?>
            <div class="ad-alert ad-alert--success">
                <div class="ad-alert__icon">
                    <svg viewBox="0 0 36 36" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="18" cy="18" r="16" fill="#EBFAEE" stroke="#1E7D34" stroke-width="2" />
                        <path d="M11 19.5 L16 24 L25 13" fill="none" stroke="#1E7D34" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <div class="ad-alert__text">
                    <div class="ad-alert__title">Mã hợp lệ</div>
                </div>
            </div>

            <script>
                setTimeout(() => {
                    window.location.href = "<?php echo esc_url(site_url('/dang-ky-thong-tin?code=' . $code)); ?>";
                }, 2000);
            </script>
        <?php endif; ?>



    </div>

    <!-- <footer class="ad-footer">
    <img src="https://upload.wikimedia.org/wikipedia/commons/3/3a/Logo_placeholder.svg" alt="Logo">
    <p>Copyright 2025 © Bệnh viện Ánh Dương</p>
  </footer> -->
</div>