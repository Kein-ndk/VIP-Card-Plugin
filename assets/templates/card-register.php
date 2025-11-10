<div class="ad-container">
    <div class="ad-content">
        <h1 class="ad-title">Cảm ơn Quý khách đã sở hữu Thẻ Ưu Đãi 50%</h1>
        <p class="ad-subtitle">Vui lòng điền thông tin để chúng tôi phục vụ Quý khách chu đáo nhất.</p>

        <form id="ad-register-form" method="post" class="ad-form" novalidate>
            <?php $card_code = isset($_GET['code']) ? sanitize_text_field($_GET['code']) : ''; ?>

            <label class="ad-label">Họ và tên <span class="required-star">*</span></label>
            <input type="text" name="ad_name" class="ad-input" required>

            <label class="ad-label">Số điện thoại <span class="required-star">*</span></label>
            <input type="text" name="ad_phone" class="ad-input" required>

            <div class="ad-row">
                <div class="ad-col">
                    <label class="ad-label">Ngày sinh <span class="required-star">*</span></label>
                    <input type="date" name="ad_birth" class="ad-input" required>
                </div>
                <div class="ad-col half">
                    <label class="ad-label">Giới tính</label>
                    <div class="ad-radio-group">
                        <label><input type="radio" name="ad_gender" value="Nam" checked> Nam</label>
                        <label><input type="radio" name="ad_gender" value="Nữ"> Nữ</label>
                    </div>
                </div>
            </div>


            <label class="ad-label">Email (nếu có)</label>
            <input type="email" name="ad_email" class="ad-input">

            <label class="ad-label">Dịch vụ quan tâm</label>
            <div class="ad-radio-group">
                <label><input type="radio" name="ad_service" value="Sức khỏe" checked> Sức khoẻ</label>
                <label><input type="radio" name="ad_service" value="Sắc đẹp"> Sắc đẹp</label>
                <label><input type="radio" name="ad_service" value="Tiêm truyền"> Tiêm truyền</label>
            </div>

            <label class="ad-label">Ghi chú (nếu có)</label>
            <textarea name="ad_note" class="ad-textarea" rows="3" placeholder="Điền ghi chú của bạn tại đây..."></textarea>

            <button type="submit" name="ad_register_submit" class="ad-btn">Lưu thông tin & tiếp tục đăng ký lịch khám</button>
        </form>
    </div>

    <!-- <footer class="ad-footer">
    <img src="https://upload.wikimedia.org/wikipedia/commons/3/3a/Logo_placeholder.svg" alt="Logo">
    <p>Copyright 2025 © Bệnh viện Ánh Dương</p>
  </footer> -->
</div>

<script>
    document.getElementById("ad-register-form").addEventListener("submit", function(e) {
        let form = e.target;
        let valid = true;
        form.querySelectorAll(".ad-error").forEach(el => el.remove());

        form.querySelectorAll("[required]").forEach(input => {
            if (!input.value.trim()) {
                valid = false;
                input.insertAdjacentHTML("afterend", `<div class="ad-error">Vui lòng nhập ${input.previousElementSibling.innerText.replace('*','')}</div>`);
                input.style.borderColor = "#E53935";
            } else {
                input.style.borderColor = "#ccc";
            }
        });

        if (!valid) e.preventDefault();
    });
</script>