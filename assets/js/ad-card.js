jQuery(document).ready(function ($) {
  const form = $('.ad-form');
  const input = $('#ad_card_code');
  const alertBox = $('.ad-alert');
  const inlineError = $('.ad-error-inline');

  form.on('submit', function (e) {
    e.preventDefault();
    const code = input.val().trim();
    if (!code) return;

    // Reset alert
    $('.ad-alert, .ad-error-inline').remove();

    $.ajax({
      url: adCardAjax.ajax_url,
      type: 'POST',
      dataType: 'json',
      data: {
        action: 'ad_card_check',
        code: code
      },
      beforeSend: function () {
        form.append('<div class="ad-error-inline" style="color:#777;">Đang kiểm tra...</div>');
      },
      success: function (res) {
        $('.ad-error-inline').remove();
        
        if (res.redirect) {
          window.location.href = res.redirect;
          return;
        }

        if (res.status === 'invalid') {
          input.css('border-color', '#D93025');
          form.append(`
            <div class="ad-error-inline" style="color:#D93025">
              <span class="ad-icon">!</span><span>Mã không tồn tại</span>
            </div>
            <div class="ad-alert ad-alert--error">
              <p>Vui lòng kiểm tra lại mã thẻ hoặc liên hệ qua Hotline: <b>0913.891.578</b></p>
              <div><a href="#" style="background:#0057FF;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;">Liên hệ với chúng tôi</a></div>
            </div>
          `);
        }

        else if (res.status === 'expired') {
          input.css('border-color', '#FFB800');
          form.append(`
            <div class="ad-error-inline" style="color:#A67C00">
              <span class="ad-icon">!</span><span>Mã đã hết hạn</span>
            </div>
            <div class="ad-alert ad-alert--warning">
              <p>Vui lòng gia hạn thẻ hoặc liên hệ qua Hotline <b>0913.891.578</b></p>
              <div>
                <a href="#" style="background:#0057FF;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;margin-right:10px;">Liên hệ hotline</a>
                <a href="${adCardAjax.site_url}/gia-han-the?code=${code}" style="background:#FFB800;color:#000;padding:10px 20px;border-radius:6px;text-decoration:none;">Gia hạn thẻ</a>
              </div>
            </div>
          `);
        }

        else if (res.status === 'valid') {
          input.css('border-color', '#1E7D34');
          form.append(`
            <div class="ad-alert ad-alert--success">
              <div class="ad-alert__icon">
                <svg viewBox="0 0 36 36" xmlns="http://www.w3.org/2000/svg">
                  <circle cx="18" cy="18" r="16" fill="#EBFAEE" stroke="#1E7D34" stroke-width="2" />
                  <path d="M11 19.5 L16 24 L25 13" fill="none" stroke="#1E7D34" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </div>
              <div class="ad-alert__text"><div class="ad-alert__title">Mã hợp lệ</div></div>
            </div>
          `);
          setTimeout(() => {
            window.location.href = `${adCardAjax.site_url}/dang-ky-thong-tin?code=${code}`;
          }, 2000);
        }
      },
      error: function () {
        alert('Không thể kết nối đến máy chủ.');
      }
    });
  });
});
