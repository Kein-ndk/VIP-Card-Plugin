<div class="ad-renew-container">
  <?php if ($success): ?>
    <div class="ad-renew-success">
      <h2>✅ Yêu cầu đã được gửi!</h2>
      <p>Thẻ của Quý khách sẽ được kích hoạt lại và có hiệu lực thêm 12 tháng kể sau khi yêu cầu hoàn tất.</p>
      <a href="<?php echo home_url('/'); ?>" class="btn-back">Về trang chủ</a>
    </div>
  <?php else: ?>
    <h2>Thẻ của Quý khách đã hết hạn</h2>
    <p>Vui lòng chọn <strong>“Gia hạn thẻ”</strong> hoặc liên hệ Hotline: <b>0913.891.578</b> để được hỗ trợ.</p>

    <div class="ad-renew-cardinfo">
      <p><strong>Mã thẻ:</strong> <?php echo esc_html($card->code); ?></p>
      <p><strong>Ngày hết hạn:</strong> <?php echo esc_html($card->expires_at ?: '—'); ?></p>
      <p><strong>Chủ thẻ:</strong> <?php echo esc_html($customer->full_name ?? 'Không xác định'); ?></p>
    </div>

    <form method="post">
      <button type="submit" name="ad_renew_submit" class="btn-renew">Gia hạn thẻ</button>
    </form>
  <?php endif; ?>
</div>
