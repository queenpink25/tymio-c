<?php
/* Template Name: Tymio SMS Login  */
get_header();
?>

<div id="tymio-sms-login-container">

  <h2>Login with SMS</h2>

  <form id="sms-login-form" method="post" autocomplete="off">
    <label for="phone_number">Enter your phone number</label><br>
    <input type="tel" id="phone_number" name="phone_number" placeholder="+2567XXXXXXX" required><br><br>

    <button type="button" id="send-otp-btn">Send OTP</button>
  </form>

  <form id="otp-verify-form" method="post" autocomplete="off" style="display:none;">
    <label for="otp_code">Enter the OTP code</label><br>
    <input type="text" id="otp_code" name="otp_code" placeholder="123456" required><br><br>

    <button type="button" id="verify-otp-btn">Verify OTP</button>
  </form>

  <div id="sms-login-message"></div>

</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const sendOtpBtn = document.getElementById('send-otp-btn');
  const verifyOtpBtn = document.getElementById('verify-otp-btn');
  const smsLoginMessage = document.getElementById('sms-login-message');

  if (sendOtpBtn) {
    sendOtpBtn.addEventListener('click', function () {
      const phone = document.getElementById('phone_number').value;
      if (!phone) {
        alert('Please enter your phone number');
        return;
      }

      smsLoginMessage.textContent = 'Sending OTP...';

      // Simulate sending OTP (replace with real API call later)
      setTimeout(() => {
        smsLoginMessage.textContent = 'OTP sent! Check your phone.';
        document.getElementById('sms-login-form').style.display = 'none';
        document.getElementById('otp-verify-form').style.display = 'block';
      }, 2000);
    });
  }

  if (verifyOtpBtn) {
    verifyOtpBtn.addEventListener('click', function () {
      const otp = document.getElementById('otp_code').value;
      if (!otp) {
        alert('Please enter the OTP');
        return;
      }

      smsLoginMessage.textContent = 'Verifying OTP...';

      // Simulate verifying OTP (replace with real API call later)
      setTimeout(() => {
        smsLoginMessage.textContent = 'OTP verified! Redirecting...';
        // Simulate login success
        window.location.href = "<?php echo home_url('/dashboard'); ?>";
      }, 2000);
    });
  }
});
</script>



<?php get_footer(); ?>
