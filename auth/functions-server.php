<?php

add_filter( 'woocommerce_ship_to_different_address_checked', '__return_false' );


function wc_ninja_remove_password_strength() {
	if ( wp_script_is( 'wc-password-strength-meter', 'enqueued' ) ) {
		wp_dequeue_script( 'wc-password-strength-meter' );
	}
}
add_action( 'wp_print_scripts', 'wc_ninja_remove_password_strength', 100 );

// Remove Breadcrumbs on Product Pages
remove_action( 'woocommerce_before_main_content','woocommerce_breadcrumb', 20, 0);

// Change Project Slug
function custom_post_name () {
return array(
'feeds' => true,
'slug' => 'free-video-lesson',
'with_front' => false,
);
}
add_filter( 'et_project_posttype_rewrite_args', 'custom_post_name' );

// Changes the redirect URL for the Return To Shop button in the cart
function wc_empty_cart_redirect_url() {
	return home_url('/shop/');
}
add_filter( 'woocommerce_return_to_shop_redirect', 'wc_empty_cart_redirect_url' );


// PV - Move woocommerce proceed to cart button
add_action( 'woocommerce_cart_actions', 'move_proceed_button' );
function move_proceed_button( $checkout ) {
	echo '<a href="' . esc_url( WC()->cart->get_checkout_url() ) . '" class="checkout-button button alt wc-forward" >' . __( 'Proceed to Checkout', 'woocommerce' ) . '</a>';
}



function wc_remove_related_products( $args ) {
	return array();
}
add_filter('woocommerce_related_products_args','wc_remove_related_products', 10); 

remove_action( 'woocommerce_product_tabs', 'woocommerce_product_reviews_tab', 30 ); 

remove_action( 'woocommerce_product_tab_panels', 'woocommerce_product_reviews_panel', 30 ); 

remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );


// ═══════════════════════════════════════════════════════════════
// FSR MEMBER SSO — shared cookie lets WP members auto-access games
// ═══════════════════════════════════════════════════════════════
define( 'FSR_SSO_SECRET', 'fsr-2026-x9mK#qLpRv8!nWzT' );
define( 'FSR_GAMES_URL',  'https://games.firststepreading.com' );

/**
 * Generate a signed SSO token for a user ID.
 */
function fsr_sso_make_token( $user_id ) {
    $expires = time() + 86400 * 365; // 1 year — stay logged in until logout
    $sig     = hash_hmac( 'sha256', $user_id . '|' . $expires, FSR_SSO_SECRET );
    return base64_encode( $user_id . '|' . $expires . '|' . $sig );
}

/**
 * Validate an SSO token. Returns user_id (int) or false.
 */
function fsr_sso_validate_token( $token ) {
    $raw = base64_decode( $token );
    if ( ! $raw ) return false;
    $parts = explode( '|', $raw );
    if ( count( $parts ) !== 3 ) return false;
    list( $uid, $expires, $sig ) = $parts;
    if ( time() > (int) $expires ) return false;
    $expected = hash_hmac( 'sha256', $uid . '|' . $expires, FSR_SSO_SECRET );
    if ( ! hash_equals( $expected, $sig ) ) return false;
    return (int) $uid;
}

/**
 * Check if a WP user has an active FSR membership or is admin.
 */
function fsr_user_is_member( $user_id ) {
    if ( user_can( $user_id, 'administrator' ) || user_can( $user_id, 'shop_manager' ) ) {
        return true;
    }
    // Check WC Memberships plugin: try specific plan first, then any active membership
    if ( function_exists( 'wc_memberships_is_user_active_member' ) ) {
        // Try the primary plan slug
        if ( wc_memberships_is_user_active_member( $user_id, 'learn-to-read-package-online-membership' ) ) {
            return true;
        }
        // Try alternate slugs in case it was renamed
        $alt_slugs = [ 'membership', 'member', 'learn-to-read', 'reading-membership',
                       'online-membership', 'full-membership', 'learn-to-read-membership' ];
        foreach ( $alt_slugs as $slug ) {
            if ( wc_memberships_is_user_active_member( $user_id, $slug ) ) {
                return true;
            }
        }
        // Try getting ALL user memberships (any active one counts)
        if ( function_exists( 'wc_memberships_get_user_memberships' ) ) {
            $memberships = wc_memberships_get_user_memberships( $user_id, [ 'status' => [ 'active', 'complimentary' ] ] );
            if ( ! empty( $memberships ) ) {
                return true;
            }
        }
    }
    // Fallback: check for any completed order with a membership product
    if ( function_exists( 'wc_get_orders' ) ) {
        $orders = wc_get_orders( [
            'customer_id' => $user_id,
            'status'      => [ 'completed', 'processing' ],
            'limit'       => 20,
        ] );
        foreach ( $orders as $order ) {
            foreach ( $order->get_items() as $item ) {
                // Known membership product IDs
                if ( in_array( $item->get_product_id(), [ 1612, 2096, 25776 ] ) ) {
                    return true;
                }
                // Any product with "membership" or "member" in its name
                $prod = $item->get_product();
                if ( $prod ) {
                    $name = strtolower( $prod->get_name() );
                    if ( strpos( $name, 'membership' ) !== false || strpos( $name, 'member' ) !== false ) {
                        return true;
                    }
                }
            }
        }
    }
    return false;
}

/**
 * On WP login: if user is a member, set SSO cookie on .firststepreading.com
 */
function fsr_sso_set_cookie( $user_login, $user ) {
    if ( ! fsr_user_is_member( $user->ID ) ) return;
    $token   = fsr_sso_make_token( $user->ID );
    $expires = time() + 86400 * 365;
    // Must call before any output — fires in wp_login action (headers still open)
    setcookie( 'fsr_member', $token, [
        'expires'  => $expires,
        'path'     => '/',
        'domain'   => '.firststepreading.com',
        'secure'   => true,
        'httponly' => false,   // JS needs to read it on games subdomain for welcome msg
        'samesite' => 'Lax',
    ] );
}
add_action( 'wp_login', 'fsr_sso_set_cookie', 10, 2 );

/**
 * On WP logout: clear the SSO cookie.
 */
function fsr_sso_clear_cookie() {
    setcookie( 'fsr_member', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'domain'   => '.firststepreading.com',
        'secure'   => true,
        'httponly' => false,
        'samesite' => 'Lax',
    ] );
}
add_action( 'wp_logout', 'fsr_sso_clear_cookie' );

// ── Allow redirect to games subdomain ───────────────────────────────────
add_filter( 'allowed_redirect_hosts', function( $hosts ) {
    $hosts[] = 'games.firststepreading.com';
    return $hosts;
} );

// ── After login, send members DIRECTLY to games hub (no intermediate page) ──
function fsr_member_login_redirect( $user ) {
    if ( is_object( $user ) && ! is_wp_error( $user ) && fsr_user_is_member( $user->ID ) ) {
        $token = fsr_sso_make_token( $user->ID );
        return 'https://games.firststepreading.com/auth/member-sso.php?t=' . urlencode( $token );
    }
    return null;
}
add_filter( 'woocommerce_login_redirect', function( $redirect, $user ) {
    $url = fsr_member_login_redirect( $user );
    return $url ? $url : $redirect;
}, 10, 2 );
add_filter( 'login_redirect', function( $redirect, $request, $user ) {
    $url = fsr_member_login_redirect( $user );
    return $url ? $url : $redirect;
}, 10, 3 );


/**
 * My Account dashboard: show Learning Hub tile for members.
 */
function fsr_myaccount_hub_tile() {
    $uid = get_current_user_id();
    if ( ! $uid || ! fsr_user_is_member( $uid ) ) return;
    $user  = wp_get_current_user();
    $name  = $user->display_name ?: $user->user_login;
    $token = fsr_sso_make_token( $uid );
    $url   = FSR_GAMES_URL . '/auth/member-sso.php?t=' . urlencode( $token );
    ?>
    <div style="margin:1.5rem 0;padding:1.8rem;background:linear-gradient(135deg,#e8f5e9,#e3f2fd);border:2px solid #86efac;border-radius:20px;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;text-align:center">
      <div style="font-size:2.5rem;margin-bottom:.4rem">&#127918;</div>
      <h3 style="font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:1.6rem;color:#1e8449;margin:0 0 .4rem">Your Learning Hub</h3>
      <p style="color:#555;font-size:.95rem;margin:0 0 1.2rem;line-height:1.5">
        Welcome back, <strong><?php echo esc_html( $name ); ?></strong>!<br>
        Your <strong>50+ videos</strong>, <strong>3 online books</strong> and <strong>25+ games</strong> are ready.
      </p>
      <a href="<?php echo esc_url( $url ); ?>"
         style="display:inline-block;background:linear-gradient(135deg,#1e8449,#27ae60);color:#fff;text-decoration:none;padding:1rem 2.5rem;border-radius:50px;font-weight:700;font-size:1.1rem;font-family:inherit;box-shadow:0 6px 20px rgba(30,132,73,.3);transition:transform .15s"
         onmouseover="this.style.transform='translateY(-3px)'"
         onmouseout="this.style.transform=''"
      >&#127918; Go to Learning Hub &rarr;</a>
      <p style="margin-top:.9rem;font-size:.8rem;color:#888">No extra login needed &mdash; you&#39;re in!</p>
    </div>
    <?php
}
add_action( 'woocommerce_account_dashboard', 'fsr_myaccount_hub_tile', 5 );




/**
 * Floating member nav widget — back button + logout.
 * Shows on every front-end page for logged-in users.
 */
function fsr_member_nav_widget() {
    if ( is_admin() ) return;
    if ( ! is_user_logged_in() ) return;
    $uid      = get_current_user_id();
    $is_member = fsr_user_is_member( $uid );
    $logout_url = wp_logout_url( home_url('/') );
    $account_url = wc_get_page_permalink('myaccount');
    $hub_url = '';
    if ( $is_member ) {
        $token   = fsr_sso_make_token( $uid );
        $hub_url = FSR_GAMES_URL . '/auth/member-sso.php?t=' . urlencode( $token );
    }
    ?>
<style>
#fsr-member-bar{position:fixed;bottom:1.1rem;right:1.1rem;z-index:99999;display:flex;flex-direction:column;align-items:flex-end;gap:.45rem;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif}
.fsr-mbar-btn{display:inline-flex;align-items:center;gap:.38rem;padding:.5rem 1.1rem;border-radius:50px;font-size:.88rem;font-weight:700;text-decoration:none;box-shadow:0 4px 16px rgba(0,0,0,.18);transition:transform .15s,box-shadow .15s;white-space:nowrap;border:none;cursor:pointer}
.fsr-mbar-btn:hover{transform:translateY(-2px);box-shadow:0 6px 22px rgba(0,0,0,.22)}
.fsr-mbar-back{background:#fff;color:#1e8449 !important;border:2px solid #86efac}
.fsr-mbar-hub{background:linear-gradient(135deg,#1e8449,#27ae60);color:#fff !important}
.fsr-mbar-account{background:linear-gradient(135deg,#3A8EF6,#6B48FF);color:#fff !important}
.fsr-mbar-logout{background:linear-gradient(135deg,#FF6B6B,#FF8C42);color:#fff !important}
@media(max-width:480px){#fsr-member-bar{bottom:.6rem;right:.6rem}.fsr-mbar-btn{font-size:.8rem;padding:.45rem .9rem}}
</style>
<div id="fsr-member-bar">
  <?php if ( $is_member && $hub_url ): ?>
  <a href="<?php echo esc_url($hub_url); ?>" class="fsr-mbar-btn fsr-mbar-hub">🎮 Learning Hub</a>
  <?php endif; ?>
  <a href="<?php echo esc_url($account_url); ?>" class="fsr-mbar-btn fsr-mbar-account">👤 My Account</a>
  <a href="#" class="fsr-mbar-btn fsr-mbar-back" onclick="history.length>1?history.back():window.location='<?php echo esc_js(home_url('/')); ?>';return false;">← Back</a>
  <a href="<?php echo esc_url($logout_url); ?>" class="fsr-mbar-btn fsr-mbar-logout">🚪 Log Out</a>
</div>
    <?php
}
add_action( 'wp_footer', 'fsr_member_nav_widget' );

/**
 * My Account: add prominent logout button below the hub tile.
 */
function fsr_myaccount_logout_btn() {
    $logout_url = wp_logout_url( home_url('/') );
    ?>
    <div style="margin:1rem 0;text-align:center">
      <a href="<?php echo esc_url($logout_url); ?>" style="display:inline-flex;align-items:center;gap:.4rem;background:linear-gradient(135deg,#FF6B6B,#FF8C42);color:#fff;text-decoration:none;padding:.75rem 2rem;border-radius:50px;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:1.05rem;box-shadow:0 4px 14px rgba(255,107,107,.3);transition:transform .15s" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">🚪 Log Out of My Account</a>
    </div>
    <?php
}
add_action( 'woocommerce_account_dashboard', 'fsr_myaccount_logout_btn', 20 );


// Google Fonts (Fredoka One, Baloo 2) removed — site uses Comic Sans everywhere

// Strip ANY Google Fonts stylesheet loaded by Divi, plugins, or other code
add_action( 'wp_print_styles', function() {
    global $wp_styles;
    if ( empty( $wp_styles->queue ) ) return;
    foreach ( $wp_styles->queue as $handle ) {
        $src = $wp_styles->registered[ $handle ]->src ?? '';
        if ( strpos( $src, 'fonts.googleapis.com' ) !== false ) {
            wp_dequeue_style( $handle );
            wp_deregister_style( $handle );
        }
    }
}, 9999 );


// ═══════════════════════════════════════════════════════════════
// PWA — manifest link, Apple meta tags, theme color
// ═══════════════════════════════════════════════════════════════
function fsr_pwa_head() {
    ?>
<link rel="manifest" href="/manifest.json">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="First Step Reading">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<link rel="apple-touch-icon" href="/icons/icon-192.png">
<meta name="theme-color" content="#01a3e7">
    <?php
}
add_action( 'wp_head', 'fsr_pwa_head', 5 );


// ═══════════════════════════════════════════════════════════════
// PWA — service worker registration + install banner + iOS hint
// ═══════════════════════════════════════════════════════════════
function fsr_pwa_footer() {
    ?>
<script>
/* ── Service Worker registration ── */
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function() {
    navigator.serviceWorker.register('/service-worker.js')
      .then(function(r){ console.log('FSR SW registered', r.scope); })
      .catch(function(e){ console.warn('FSR SW error', e); });
  });
}

/* ── Android install prompt ── */
var _fsrPrompt;
window.addEventListener('beforeinstallprompt', function(e) {
  e.preventDefault();
  _fsrPrompt = e;
  var b = document.getElementById('fsr-install-banner');
  if (b) b.style.display = 'flex';
});
window.addEventListener('appinstalled', function() {
  var b = document.getElementById('fsr-install-banner');
  if (b) b.style.display = 'none';
});
function fsrInstall() {
  if (!_fsrPrompt) return;
  _fsrPrompt.prompt();
  _fsrPrompt.userChoice.then(function() {
    _fsrPrompt = null;
    var b = document.getElementById('fsr-install-banner');
    if (b) b.style.display = 'none';
  });
}

/* ── iOS hint (show once, remembers dismissal) ── */
(function() {
  var isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent);
  var isStandalone = window.navigator.standalone === true;
  if (isIOS && !isStandalone && !localStorage.getItem('fsr-ios-dismissed')) {
    var h = document.getElementById('fsr-ios-hint');
    if (h) h.style.display = 'block';
  }
})();
function fsrDismissIOS() {
  localStorage.setItem('fsr-ios-dismissed', '1');
  var h = document.getElementById('fsr-ios-hint');
  if (h) h.style.display = 'none';
}
</script>

<!-- Android install banner (hidden until Chrome fires beforeinstallprompt) -->
<div id="fsr-install-banner" style="display:none;position:fixed;bottom:0;left:0;right:0;z-index:999998;background:linear-gradient(135deg,#1e8449,#27ae60);color:#fff;padding:.85rem 1rem;align-items:center;gap:.75rem;box-shadow:0 -4px 20px rgba(0,0,0,.22);font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif">
  <span style="font-size:1.8rem;flex-shrink:0">📚</span>
  <div style="flex:1;min-width:0">
    <div style="font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:1rem;line-height:1.2">Install First Step Reading</div>
    <div style="font-size:.78rem;opacity:.88">Add to your home screen for instant access</div>
  </div>
  <button onclick="fsrInstall()" style="background:#fff;color:#1e8449;border:none;border-radius:50px;padding:.5rem 1.1rem;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:.9rem;cursor:pointer;flex-shrink:0;font-weight:700;white-space:nowrap">Install</button>
  <button onclick="document.getElementById('fsr-install-banner').style.display='none'" style="background:none;border:none;color:rgba(255,255,255,.8);font-size:1.4rem;cursor:pointer;padding:.1rem .4rem;line-height:1;flex-shrink:0" aria-label="Dismiss">✕</button>
</div>

<!-- iPhone/iPad install hint (shown once until dismissed) -->
<div id="fsr-ios-hint" style="display:none;position:fixed;bottom:0;left:0;right:0;z-index:999997;background:#fff;border-top:3px solid #01a3e7;padding:.9rem 1.1rem 1rem;box-shadow:0 -4px 20px rgba(0,0,0,.12);font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:.9rem;text-align:center">
  <button onclick="fsrDismissIOS()" style="position:absolute;top:.5rem;right:.8rem;background:none;border:none;font-size:1.3rem;color:#aaa;cursor:pointer;line-height:1" aria-label="Close">✕</button>
  <strong style="font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;color:#1e8449;font-size:1rem;display:block;margin-bottom:.3rem">📱 Add to Your Home Screen</strong>
  On iPhone or iPad: tap <strong>Share ⎙</strong>, then <strong>Add to Home Screen</strong>.
</div>
    <?php
}
add_action( 'wp_footer', 'fsr_pwa_footer', 99 );


// ═══════════════════════════════════════════════════════════════
// CUSTOM FAVICON — red apple (overrides the default WordPress "W")
// ═══════════════════════════════════════════════════════════════
function fsr_custom_favicon() {
    $icon = get_stylesheet_directory_uri() . '/fsr-favicon.svg';
    echo '<link rel="icon" type="image/svg+xml" href="' . esc_url($icon) . '">' . "\n";
    echo '<link rel="shortcut icon" type="image/svg+xml" href="' . esc_url($icon) . '">' . "\n";
}
add_action( 'wp_head', 'fsr_custom_favicon', 2 );

// Override WordPress site_icon output so the W logo never appears
add_filter( 'get_site_icon_url', function() {
    return get_stylesheet_directory_uri() . '/fsr-favicon.svg';
}, 10, 3 );

// ── Redirect logged-in members away from /my-account/ back to games hub ─────
// This prevents the browser back button from landing them on the WP login form.
// Members can still access /my-account/orders/, /my-account/edit-account/ etc.
add_action( 'template_redirect', function() {
    if ( ! is_user_logged_in() ) return;
    if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) return;
    // Only intercept the root dashboard/login page, not sub-endpoints
    if ( function_exists( 'WC' ) && WC()->query ) {
        $endpoint = WC()->query->get_current_endpoint();
        if ( ! empty( $endpoint ) ) return;
    }
    $user_id = get_current_user_id();
    if ( ! fsr_user_is_member( $user_id ) ) return;
    // Send member straight back to games hub via fresh SSO token
    $token = fsr_sso_make_token( $user_id );
    wp_safe_redirect( 'https://games.firststepreading.com/auth/member-sso.php?t=' . urlencode( $token ) );
    exit;
} );






// ═══════════════════════════════════════════════════════════════
// MOBILE COMIC SANS — injected in wp_footer so it loads AFTER
// Divi's et-core-unified CSS and wins the cascade.
// :root body * selector beats typical Divi specificity.
// ═══════════════════════════════════════════════════════════════
function fsr_mobile_comic_sans() {
    ?>
<style id="fsr-mobile-comic-sans">
/* Comic Sans lock — mobile and desktop — overrides Divi and all plugins */
html, html *, html *::before, html *::after {
  font-family: "Comic Sans MS", "Chalkboard SE", "Comic Neue", sans-serif !important;
}
</style>
    <?php
}
add_action( 'wp_footer', 'fsr_mobile_comic_sans', 9999 );


// ═══════════════════════════════════════════════════════════════
// SELF-HEAL: restore Comic Sans to style.css if ever cleared
// ═══════════════════════════════════════════════════════════════
add_action( 'wp_loaded', function() {
    $css_file = get_stylesheet_directory() . '/style.css';
    $marker   = 'MOBILE COMIC SANS';
    if ( strpos( file_get_contents( $css_file ), $marker ) === false ) {
        $backup = get_option( 'fsr_mobile_comic_sans_css', '' );
        if ( $backup ) {
            file_put_contents( $css_file, "\n/* $marker — permanent rule, DO NOT REMOVE */\n" . $backup . "\n", FILE_APPEND );
        }
    }
} );


// ═══════════════════════════════════════════════════════════════
// EMAIL CAPTURE POPUP — 15-second delay + exit intent
// ═══════════════════════════════════════════════════════════════
function fsr_email_popup_output() {
    if ( is_user_logged_in() ) return;
    $nonce   = wp_create_nonce( "fsr_lead_nonce" );
    $ajaxurl = admin_url( "admin-ajax.php" );
    ?>
<div id="fsr-popup-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:99999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:20px;padding:2.5rem 2rem;max-width:440px;width:90%;position:relative;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.25)">
    <button onclick="fsrClosePopup()" style="position:absolute;top:.8rem;right:1rem;background:none;border:none;font-size:1.6rem;cursor:pointer;color:#999;line-height:1">&times;</button>
    <div style="font-size:3rem;margin-bottom:.5rem">&#x1F34E;</div>
    <h2 style="font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:1.7rem;color:#1A3A6B;margin:0 0 .4rem">Free Reading Starter Kit!</h2>
    <p style="color:#555;font-size:.95rem;line-height:1.6;margin:0 0 1.2rem">Get our <strong>free printable worksheets + 3 bonus tips</strong> to kickstart your child's reading journey.</p>
    <form id="fsr-popup-form" onsubmit="fsrSubmitEmail(event)">
      <input id="fsr-popup-name" type="text" placeholder="Child's first name (optional)" style="width:100%;box-sizing:border-box;padding:.75rem 1rem;border:2px solid #e5e7eb;border-radius:50px;margin-bottom:.6rem;font-size:.95rem;font-family:inherit">
      <input id="fsr-popup-email" type="email" placeholder="Your email address" required style="width:100%;box-sizing:border-box;padding:.75rem 1rem;border:2px solid #e5e7eb;border-radius:50px;margin-bottom:.9rem;font-size:.95rem;font-family:inherit">
      <button type="submit" style="width:100%;background:linear-gradient(135deg,#1e8449,#27ae60);color:#fff;border:none;border-radius:50px;padding:.85rem;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:1.1rem;cursor:pointer;box-shadow:0 6px 20px rgba(30,132,73,.35)">&#x1F4E5; Send My Free Kit!</button>
    </form>
    <div id="fsr-popup-thanks" style="display:none;color:#1e8449;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:1.2rem;padding:1rem 0">&#x2705; Check your inbox &#x2014; it's on its way!</div>
    <p style="margin-top:.8rem;font-size:.75rem;color:#9ca3af">No spam, ever. Unsubscribe anytime.</p>
  </div>
</div>
<script>
(function(){
  var shown=false;
  function showPopup(){
    if(shown||localStorage.getItem("fsrPopupDone"))return;
    shown=true;
    var el=document.getElementById("fsr-popup-overlay");
    if(el){el.style.display="flex";}
  }
  setTimeout(showPopup,15000);
  document.addEventListener("mouseleave",function(e){if(e.clientY<10)showPopup();});
  window.fsrClosePopup=function(){
    document.getElementById("fsr-popup-overlay").style.display="none";
    localStorage.setItem("fsrPopupDone","1");
  };
  window.fsrSubmitEmail=function(e){
    e.preventDefault();
    var email=document.getElementById("fsr-popup-email").value;
    var name=document.getElementById("fsr-popup-name").value;
    var fd=new FormData();
    fd.append("action","fsr_save_lead");
    fd.append("email",email);
    fd.append("name",name);
    fd.append("nonce","<?php echo $nonce; ?>");
    fetch("<?php echo $ajaxurl; ?>",{method:"POST",body:fd})
      .then(function(r){return r.json();})
      .then(function(){
        document.getElementById("fsr-popup-form").style.display="none";
        document.getElementById("fsr-popup-thanks").style.display="block";
        localStorage.setItem("fsrPopupDone","1");
        setTimeout(window.fsrClosePopup,3000);
      });
  };
})();
</script>
    <?php
}
add_action( "wp_footer", "fsr_email_popup_output", 100 );

add_action( "wp_ajax_nopriv_fsr_save_lead", "fsr_handle_lead" );
add_action( "wp_ajax_fsr_save_lead",        "fsr_handle_lead" );
function fsr_handle_lead() {
    if ( ! wp_verify_nonce( sanitize_text_field( $_POST["nonce"] ?? "" ), "fsr_lead_nonce" ) ) {
        wp_send_json_error( "bad nonce" );
    }
    global $wpdb;
    $email = sanitize_email( $_POST["email"] ?? "" );
    $name  = sanitize_text_field( $_POST["name"] ?? "" );
    if ( ! is_email( $email ) ) wp_send_json_error( "bad email" );
    $wpdb->replace(
        $wpdb->prefix . "fsr_email_leads",
        [ "email" => $email, "name" => $name, "source" => "popup", "ip" => sanitize_text_field( $_SERVER["REMOTE_ADDR"] ?? "" ) ]
    );
    wp_send_json_success();
}


// ═══════════════════════════════════════════════════════════════
// CART ABANDONMENT — capture checkout email + 1-hour reminder
// ═══════════════════════════════════════════════════════════════
add_action( "woocommerce_checkout_update_order_review", "fsr_capture_checkout_email" );
function fsr_capture_checkout_email( $posted_data ) {
    parse_str( $posted_data, $data );
    $email = sanitize_email( $data["billing_email"] ?? "" );
    if ( ! is_email( $email ) ) return;
    global $wpdb;
    $exists = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}fsr_abandoned_carts WHERE email=%s", $email
    ) );
    if ( ! $exists ) {
        $cart_total = ( WC() && WC()->cart ) ? WC()->cart->get_total( "edit" ) : "";
        $cart_data  = ( WC() && WC()->cart ) ? maybe_serialize( WC()->cart->get_cart() ) : "";
        $wpdb->insert( $wpdb->prefix . "fsr_abandoned_carts", [
            "email"      => $email,
            "cart_data"  => $cart_data,
            "total"      => $cart_total,
            "reminder_sent" => 0,
            "created_at" => current_time( "mysql" ),
        ] );
        wp_schedule_single_event( time() + 3600, "fsr_cart_abandonment_hook", [ $email ] );
    }
}

add_action( "fsr_cart_abandonment_hook", "fsr_send_abandonment_email" );
function fsr_send_abandonment_email( $email ) {
    global $wpdb;
    $row = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}fsr_abandoned_carts WHERE email=%s AND reminder_sent=0", $email
    ) );
    if ( ! $row ) return;
    $site  = get_bloginfo( "name" );
    $url   = function_exists( "wc_get_cart_url" ) ? wc_get_cart_url() : home_url( "/cart/" );
    $subj  = "You left something behind! \xF0\x9F\x8D\x8E Your reading program is waiting";
    $body  = "Hi there,\n\nWe noticed you were checking out " . $site . " but didn't complete your purchase.\n\n";
    $body .= "Your cart is saved and ready for you:\n{$url}\n\n";
    $body .= "Questions? Email us at support@firststepreading.com\n\nHappy reading!\nThe First Step Reading Team";
    wp_mail( $email, $subj, $body, [
        "From: {$site} <support@firststepreading.com>",
        "Content-Type: text/plain; charset=UTF-8",
    ] );
    $wpdb->update( $wpdb->prefix . "fsr_abandoned_carts", [ "reminder_sent" => 1 ], [ "email" => $email ] );
}

// Clear abandon record when order completes
add_action( "woocommerce_checkout_order_processed", "fsr_cart_abandon_clear" );
function fsr_cart_abandon_clear( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) return;
    global $wpdb;
    $wpdb->delete( $wpdb->prefix . "fsr_abandoned_carts", [ "email" => $order->get_billing_email() ] );
}


// ═══════════════════════════════════════════════════════════════
// SCHEMA MARKUP — Organization on home, FAQPage on FAQ page
// ═══════════════════════════════════════════════════════════════
function fsr_schema_markup() {
    if ( is_front_page() ) {
        $s = [
            "@context"        => "https://schema.org",
            "@type"           => "Organization",
            "name"            => "First Step Reading",
            "url"             => "https://www.firststepreading.com",
            "logo"            => get_stylesheet_directory_uri() . "/fsr-favicon.svg",
            "description"     => "Online phonics and reading program for children ages 2-8, step-by-step since 2010.",
            "email"           => "support@firststepreading.com",
            "foundingDate"    => "2010",
            "sameAs"          => [
                "https://www.facebook.com/firststepreading",
                "https://www.pinterest.com/firststepreading",
                "https://www.youtube.com/firststepreading",
            ],
            "aggregateRating" => [
                "@type"       => "AggregateRating",
                "ratingValue" => "4.9",
                "reviewCount" => "312",
                "bestRating"  => "5",
            ],
        ];
        echo "<script type=\"application/ld+json\">\n" . wp_json_encode( $s ) . "\n</script>\n";
    }
    if ( is_page( "frequently-asked-questions" ) || is_page( "faq" ) ) {
        $faq = [
            "@context"   => "https://schema.org",
            "@type"      => "FAQPage",
            "mainEntity" => [
                [ "@type" => "Question", "name" => "What age is First Step Reading for?",
                  "acceptedAnswer" => [ "@type" => "Answer", "text" => "Designed for children ages 2-8, from beginners who do not know their letters to early readers building fluency." ] ],
                [ "@type" => "Question", "name" => "How does the online program work?",
                  "acceptedAnswer" => [ "@type" => "Answer", "text" => "Members access 50+ video lessons, 25+ interactive reading games, and digital books on any device." ] ],
                [ "@type" => "Question", "name" => "Is there a money-back guarantee?",
                  "acceptedAnswer" => [ "@type" => "Answer", "text" => "Yes! We offer a 30-day money-back guarantee. Contact us for a full refund if not satisfied." ] ],
                [ "@type" => "Question", "name" => "Does it work for kids with dyslexia?",
                  "acceptedAnswer" => [ "@type" => "Answer", "text" => "Yes. The multi-sensory approach of watching, listening, and playing games has helped many children with dyslexia." ] ],
                [ "@type" => "Question", "name" => "Can teachers use this in the classroom?",
                  "acceptedAnswer" => [ "@type" => "Answer", "text" => "Absolutely. We have a school package with a teacher portal, class management tools, and student login codes." ] ],
            ],
        ];
        echo "<script type=\"application/ld+json\">\n" . wp_json_encode( $faq ) . "\n</script>\n";
    }
}
add_action( "wp_head", "fsr_schema_markup", 5 );


// ═══════════════════════════════════════════════════════════════
// VISITOR COUNTER — simulated live count on homepage
// ═══════════════════════════════════════════════════════════════
function fsr_visitor_counter_script() {
    if ( ! is_front_page() ) return;
    ?>
<script id="fsr-visitor-counter">
(function(){
  var base=47,v=Math.floor(Math.random()*20);
  var h=new Date().getHours();
  var boost=(h>=8&&h<=20)?Math.floor(Math.random()*18):0;
  var count=base+v+boost;
  function upd(){document.querySelectorAll(".fsr-visitor-count").forEach(function(el){el.textContent=count+" "+(count===1?"family":"families");});}
  document.addEventListener("DOMContentLoaded",upd);
  setInterval(function(){count=count+(Math.random()>.5?1:-1);if(count<32)count=32;if(count>130)count=130;upd();},45000);
})();
</script>
    <?php
}
add_action( "wp_footer", "fsr_visitor_counter_script", 50 );

// ═══════════════════════════════════════════════════════════════
// FIRSTSTEPMATH LOGO BUTTON — top bar
// ═══════════════════════════════════════════════════════════════
function fsr_fsm_logo_button() {
    $logo_url = get_stylesheet_directory_uri() . '/fsm-logo.png';
    echo '<div style="background:#fff8e1;border-bottom:3px solid #fbbf24;text-align:center;padding:8px 16px;">
        <a href="https://firststepmath.com" style="display:inline-block;text-decoration:none;transition:transform 0.15s;" onmouseover="this.style.transform=\'scale(1.04)\'" onmouseout="this.style.transform=\'scale(1)\'">
            <img src="' . esc_url($logo_url) . '" alt="FirstStepMath" style="height:80px;width:auto;display:block;margin:0 auto;">
            <div style="font-family:\'Comic Sans MS\',\'Chalkboard SE\',\'Comic Neue\',sans-serif;font-size:0.95rem;font-weight:800;color:#b45309;margin-top:2px;letter-spacing:0.5px">🎉 Try FirstStepMath FREE! →</div>
        </a>
    </div>';
}
add_action( 'wp_body_open', 'fsr_fsm_logo_button', 1 );


// ═══════════════════════════════════════════════════════════════
// TEACHER ACCOUNT CODES — auto-email on WooCommerce order complete
//   Product 28201 ($99 Teacher License)  → 1 activation code
//   Product 28202 ($499 School License)  → 20 activation codes
// ═══════════════════════════════════════════════════════════════
function fsr_send_activation_codes( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) return;

    // Don't run twice if order is re-processed
    if ( get_post_meta( $order_id, '_fsr_codes_sent', true ) ) return;

    $teacher_pid = 28201;
    $school_pid  = 28202;
    $codes_needed = 0;
    $license_type = '';

    foreach ( $order->get_items() as $item ) {
        $pid = (int) $item->get_product_id();
        if ( $pid === $teacher_pid ) {
            $codes_needed = 1;
            $license_type = 'Teacher License';
            break;
        }
        if ( $pid === $school_pid ) {
            $codes_needed = 20;
            $license_type = 'School License';
            break;
        }
    }

    if ( ! $codes_needed ) return;

    // Generate codes and insert into reading-games DB
    $chars  = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    // Use same DB the reading-games auth uses (resolved via wp-config constants)
    $db_name = defined( 'FSR_GAMES_DB' ) ? FSR_GAMES_DB : DB_NAME;
    $mysqli  = new mysqli( DB_HOST, DB_USER, DB_PASSWORD, $db_name );
    if ( $mysqli->connect_error ) {
        error_log( 'FSR codes: DB connect failed — ' . $mysqli->connect_error );
        return;
    }

    $generated = [];
    for ( $i = 0; $i < $codes_needed; $i++ ) {
        $attempts = 0;
        do {
            $parts = [];
            for ( $p = 0; $p < 3; $p++ ) {
                $seg = '';
                for ( $c = 0; $c < 4; $c++ ) $seg .= $chars[ random_int( 0, strlen( $chars ) - 1 ) ];
                $parts[] = $seg;
            }
            $code = 'FSR-' . implode( '-', $parts );
            $st = $mysqli->prepare( 'INSERT IGNORE INTO premium_codes (code) VALUES (?)' );
            $st->bind_param( 's', $code );
            $st->execute();
            $attempts++;
        } while ( $st->affected_rows === 0 && $attempts < 10 );
        if ( $st->affected_rows > 0 ) $generated[] = $code;
    }
    $mysqli->close();

    if ( ! $generated ) {
        error_log( 'FSR codes: failed to generate codes for order ' . $order_id );
        return;
    }

    // Build email
    $to      = $order->get_billing_email();
    $name    = $order->get_billing_first_name();
    $subject = '⭐ Your First Step Reading Activation Code' . ( $codes_needed > 1 ? 's' : '' );
    $activate_url = 'https://www.firststepreading.com/reading-games/auth/activate.php';

    $code_lines = '';
    foreach ( $generated as $c ) {
        $code_lines .= '    ' . $c . "\n";
    }

    if ( $codes_needed === 1 ) {
        $body = "Hi {$name},\n\n"
            . "Thank you for purchasing the First Step Reading Teacher License!\n\n"
            . "Your activation code is:\n\n"
            . $code_lines . "\n"
            . "To activate your teacher account:\n"
            . "1. Go to: {$activate_url}\n"
            . "2. Sign in (or create a free account first)\n"
            . "3. Enter your code — your account will be active for 365 days\n\n"
            . "Questions? Reply to this email.\n\n"
            . "— First Step Reading Team";
    } else {
        $body = "Hi {$name},\n\n"
            . "Thank you for purchasing the First Step Reading School License!\n\n"
            . "Below are your {$codes_needed} teacher activation codes. "
            . "Share one code with each teacher — each code activates 365 days of premium access.\n\n"
            . $code_lines . "\n"
            . "Each teacher should:\n"
            . "1. Go to: {$activate_url}\n"
            . "2. Sign in (or create a free account first)\n"
            . "3. Enter their code\n\n"
            . "Questions? Reply to this email.\n\n"
            . "— First Step Reading Team";
    }

    $headers = [ 'Content-Type: text/plain; charset=UTF-8' ];
    wp_mail( $to, $subject, $body, $headers );

    // Mark as sent so we don't send twice
    update_post_meta( $order_id, '_fsr_codes_sent', 1 );
}
add_action( 'woocommerce_order_status_completed', 'fsr_send_activation_codes', 10, 1 );
add_action( 'woocommerce_payment_complete',        'fsr_send_activation_codes', 10, 1 );


// ═══════════════════════════════════════════════════════════════
// DISCOUNT CODE — "readfree" gives 100% off teacher license ($99)
// Applied at checkout; grants free teacher access for 365 days
// ═══════════════════════════════════════════════════════════════
add_action( 'init', function() {
    // Create the coupon if it doesn't exist yet
    $existing = get_page_by_title( 'readfree', OBJECT, 'shop_coupon' );
    if ( $existing ) return; // already exists, nothing to do

    $coupon = [
        'post_title'   => 'readfree',
        'post_name'    => 'readfree',
        'post_status'  => 'publish',
        'post_type'    => 'shop_coupon',
        'post_author'  => 1,
    ];
    $coupon_id = wp_insert_post( $coupon );
    if ( ! $coupon_id || is_wp_error( $coupon_id ) ) return;

    // 100% discount on the teacher license product only
    update_post_meta( $coupon_id, 'discount_type',           'percent' );
    update_post_meta( $coupon_id, 'coupon_amount',           '100' );
    update_post_meta( $coupon_id, 'product_ids',             '28201' );
    update_post_meta( $coupon_id, 'usage_limit',             '' );   // unlimited uses
    update_post_meta( $coupon_id, 'usage_limit_per_user',    '1' );  // once per teacher
    update_post_meta( $coupon_id, 'individual_use',          'yes' );
    update_post_meta( $coupon_id, 'exclude_sale_items',      'no' );
    update_post_meta( $coupon_id, 'free_shipping',           'no' );
} );

// After a "readfree" order completes → still send the activation code
// (fsr_send_activation_codes already handles this via the same hook)
