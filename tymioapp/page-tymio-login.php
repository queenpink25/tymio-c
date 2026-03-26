<?php
/* Template Name: Tymio Login Page */
get_header();

// Role-based redirect after login
add_filter('login_redirect', 'tymio_role_based_redirect', 10, 3);
function tymio_role_based_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('administrator', $user->roles)) {
            return site_url('/admin-dashboard');
        } elseif (in_array('mentor', $user->roles)) {
            return site_url('/mentor-dashboard');
        } elseif (in_array('student', $user->roles)) {
            return site_url('/student-dashboard');
        }
    }
    return home_url();
}

// Register translatable strings for Polylang
function tymio_register_strings() {
    pll_register_string('Welcome to Tymio', 'Welcome to Tymio', 'Tymio');
    pll_register_string('Email or Phone', 'Email or Phone', 'Tymio');
    pll_register_string('Password', 'Password', 'Tymio');
    pll_register_string('Login', 'Login', 'Tymio');
    pll_register_string('Language:', 'Language:', 'Tymio');
    pll_register_string('Login with SMS instead', 'Login with SMS instead', 'Tymio');
    pll_register_string('Continue with Google', 'Continue with Google', 'Tymio');
}
add_action('init', 'tymio_register_strings');
?>

<style>
#tymio-login-container {
  max-width: 400px;
  margin: auto;
  padding: 30px 20px;
  background: #f9f9f9;
  border-radius: 12px;
  box-shadow: 0 4px 8px rgba(0,0,0,0.05);
}
#tymio-login-container input,
#tymio-login-container select,
#tymio-login-container button,
.login-button {
  width: 100%;
  margin-bottom: 15px;
  padding: 10px;
  font-size: 1rem;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-weight: bold;
}
#tymio-login-container button {
  background-color: #0073aa;
  color: white;
}
.or-separator {
  text-align: center;
  margin: 20px 0;
  font-weight: bold;
}
.sms-login-link a {
  background-color: #0a4;
  color: white;
  padding: 10px 20px;
  display: inline-block;
  border-radius: 5px;
  text-decoration: none;
  font-weight: bold;
}
/* Google button override */
.nsl-container .nsl-button-google {
  background-color: #db4437 !important;
  color: white !important;
  border-radius: 5px !important;
  padding: 12px !important;
  font-weight: bold !important;
  width: 100% !important;
  text-align: center !important;
}
.nsl-container .nsl-button-google:hover {
  background-color: #c1351d !important;
}
</style>

<div id="tymio-login-container" role="main" aria-label="Tymio Login Form">

  <h2><?php _e('Welcome to Tymio', 'blocksy'); ?></h2>

  <!-- Standard WordPress login form -->
  <form method="post" action="<?php echo esc_url(wp_login_url(get_permalink())); ?>" autocomplete="off">
    <?php wp_nonce_field('login_form'); ?>

    <label for="user_login"><?php _e('Email or Phone', 'blocksy'); ?></label>
    <input type="text" name="log" id="user_login" placeholder="you@example.com or +2567XXXXXXX" required autocomplete="username">

    <label for="user_pass"><?php _e('Password', 'blocksy'); ?></label>
    <input type="password" name="pwd" id="user_pass" placeholder="********" required autocomplete="current-password">

    <button type="submit"><?php _e('Login', 'blocksy'); ?></button>
  </form>

  <div class="or-separator">— OR —</div>

  <!-- Continue with Google (styled) -->
  <div class="google-login-button">
    <?php
    if (class_exists('NextendSocialLogin')) {
        echo do_shortcode('[nextend_social_login provider="google" redirect="' . site_url() . '" style="button"]');
    } else {
        echo '<p style="color:red;">Nextend Social Login plugin is not active or not configured.</p>';
    }
    ?>
  </div>

  <!-- Language Selector -->
  <div class="language-select" style="margin-top: 20px;">
    <form method="get" id="language-switcher">
      <label for="language"><?php _e('Language:', 'blocksy'); ?></label>
      <select name="lang" id="language" onchange="this.form.submit()">
        <?php
        $lang_slugs = ['en' => 'English', 'lg' => 'Luganda', 'fr' => 'French', 'sw' => 'Swahili'];
        $current_lang = function_exists('pll_current_language') ? pll_current_language() : 'en';
        foreach ($lang_slugs as $slug => $name) {
          $selected = ($slug === $current_lang) ? 'selected' : '';
          echo '<option value="' . esc_attr($slug) . '" ' . $selected . '>' . esc_html($name) . '</option>';
        }
        ?>
      </select>
    </form>
  </div>

  <!-- Login with SMS -->
  <div class="sms-login-link" style="text-align:center; margin-top: 15px;">
    <a href="<?php echo site_url('/sms-login'); ?>">
      <?php _e('Login with SMS instead', 'blocksy'); ?>
    </a>
  </div>

</div>

<?php get_footer(); ?>
