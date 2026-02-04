<?php
/**
 * Plugin Name: WM Frontend Portal
 * Description: Login y portal 100% frontend para miembros del foro.
 * Version: 1.0.1
 * Author: Tu Nombre
 */

if ( ! defined('ABSPATH') ) exit;

define('WM_DASH_SLUG', 'mi-area');          // dashboard frontend
define('WM_LOGIN_SLUG', 'acceso');          // página de login
define('WM_LOST_SLUG',  'recuperar-clave'); // página "olvidé mi contraseña"

/** Helpers */
function wm_site_path( $slug ) { return site_url( '/'.$slug.'/' ); }
function wm_is_admin_like()    { return current_user_can('manage_options'); }

/** 1) Ocultar Admin Bar a no-admin */
add_filter('show_admin_bar', function($show){
    if ( is_user_logged_in() && ! wm_is_admin_like() ) return false;
    return $show;
});

/** 2) Bloquear /wp-admin/ a no-admin (permitir AJAX) */
add_action('admin_init', function(){
    if ( ! is_user_logged_in() ) return;
    if ( wm_is_admin_like() ) return;
    if ( defined('DOING_AJAX') && DOING_AJAX ) return;
    wp_safe_redirect( wm_site_path(WM_DASH_SLUG) );
    exit;
});

/** 3) Redirección post-login según rol */
add_filter('login_redirect', function($redirect_to, $request, $user){
    if ( is_wp_error($user) || ! $user instanceof WP_User ) return $redirect_to;
    if ( in_array('foro_miembro', (array)$user->roles, true) ) {
        return wm_site_path( WM_DASH_SLUG );
    }
    return $redirect_to;
}, 10, 3);

/** 4) Reemplazar URLs estándar por tus páginas */
add_filter('login_url', function($login_url, $redirect, $force_reauth){
    $url = wm_site_path(WM_LOGIN_SLUG);
    if ( ! empty($redirect) ) $url = add_query_arg('redirect_to', urlencode($redirect), $url);
    return $url;
}, 10, 3);

add_filter('lostpassword_url', function($lost_url, $redirect){
    $url = wm_site_path(WM_LOST_SLUG);
    if ( ! empty($redirect) ) $url = add_query_arg('redirect_to', urlencode($redirect), $url);
    return $url;
}, 10, 2);

add_filter('logout_redirect', function($to, $req){
    return home_url('/');
}, 10, 2);

/** 5) Shortcode: Login frontend */
/** 5) Shortcode: Login frontend con Bootstrap */
add_shortcode('wm_front_login', function($atts = []) {
    $error = '';
    if ( isset($_GET['login']) && $_GET['login'] === 'failed' ) {
        $error = __('Usuario o contraseña incorrectos.', 'tu-tema');
    } elseif ( isset($_GET['login']) && $_GET['login'] === 'required' ) {
        $error = __('Debes iniciar sesión para continuar.', 'tu-tema');
    }

    $redirect_to = isset($_GET['redirect_to']) ? esc_url_raw($_GET['redirect_to']) : wm_site_path(WM_DASH_SLUG);

    ob_start(); ?>
    
                    <div class="card-body">

                        <?php if ( $error ) : ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo esc_html($error); ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                            <?php wp_nonce_field('wm_front_login'); ?>
                            <input type="hidden" name="action" value="wm_front_login">
                            <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">

                            <div class="mb-3">
                                <label class="form-label"><?php esc_html_e('Email o usuario','tu-tema'); ?></label>
                                <input type="text" name="log" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><?php esc_html_e('Contraseña','tu-tema'); ?></label>
                                <input type="password" name="pwd" class="form-control" required>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" name="rememberme" value="1" id="rememberme">
                                <label class="form-check-label" for="rememberme"><?php esc_html_e('Recordarme','tu-tema'); ?></label>
                            </div>

                            <div class="d-grid">
                                <button class="btn btn-ai" type="submit"><?php esc_html_e('Iniciar sesión','tu-tema'); ?></button>
                            </div>

                            <div class="mt-3 text-center">
                                <a href="<?php echo esc_url( wm_site_path(WM_LOST_SLUG) ); ?>"><?php esc_html_e('¿Olvidaste tu contraseña?','tu-tema'); ?></a>
                            </div>
                        </form>
                    </div>
         
    <?php
    return ob_get_clean();
});


/** 6) Handler POST del login (redirige ANTES de imprimir nada) */
add_action('admin_post_nopriv_wm_front_login', 'wm_front_login_handler');
add_action('admin_post_wm_front_login',        'wm_front_login_handler');
function wm_front_login_handler() {
    if ( ! isset($_POST['_wpnonce']) || ! wp_verify_nonce($_POST['_wpnonce'], 'wm_front_login') ) {
        wp_safe_redirect( add_query_arg('login', 'failed', wp_get_referer() ?: wm_site_path(WM_LOGIN_SLUG) ) );
        exit;
    }
    $creds = array(
        'user_login'    => sanitize_text_field( $_POST['log'] ?? '' ),
        'user_password' => (string)($_POST['pwd'] ?? ''),
        'remember'      => ! empty($_POST['rememberme']),
    );
    $user  = wp_signon( $creds, false );

    $redirect = isset($_POST['redirect_to']) ? esc_url_raw($_POST['redirect_to']) : wm_site_path(WM_DASH_SLUG);

    if ( is_wp_error($user) ) {
        wp_safe_redirect( add_query_arg('login', 'failed', wp_get_referer() ?: wm_site_path(WM_LOGIN_SLUG) ) );
        exit;
    }
    // Si es foro_miembro → dashboard, si no, respeta redirect
    if ( in_array('foro_miembro', (array)$user->roles, true) ) $redirect = wm_site_path(WM_DASH_SLUG);

    wp_safe_redirect( $redirect );
    exit;
}

/** 7) Shortcode: Olvidé mi contraseña (envío del email) */
/** Shortcode: Formulario de "Olvidé mi contraseña" con Bootstrap */
add_shortcode('wm_front_lostpassword', function($atts = []) {
    $msg = '';
    if ( isset($_GET['reset']) && $_GET['reset'] === 'sent' ) {
        $msg = __('Si el email existe, recibirás un enlace para restablecer tu contraseña.', 'tu-tema');
    }

    ob_start(); ?>
    
                    <div class="card-body">

                        <?php if ( $msg ) : ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo esc_html($msg); ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                            <?php wp_nonce_field('wm_front_lostpass'); ?>
                            <input type="hidden" name="action" value="wm_front_lostpass">

                            <div class="mb-3">
                                <label for="user_login" class="form-label"><?php esc_html_e('Tu email','tu-tema'); ?></label>
                                <input type="email" name="user_login" id="user_login" class="form-control" required>
                            </div>

                            <div class="d-grid mb-3">
                                <button class="btn btn-ai" type="submit"><?php esc_html_e('Enviar enlace de restablecimiento','tu-tema'); ?></button>
                            </div>

                            <div class="text-center">
                                <a href="<?php echo esc_url( wm_site_path(WM_LOGIN_SLUG) ); ?>">&larr; <?php esc_html_e('Volver a iniciar sesión','tu-tema'); ?></a>
                            </div>
                        </form>
                    </div>
           
    <?php
    return ob_get_clean();
});


add_action('admin_post_nopriv_wm_front_lostpass', 'wm_front_lostpass_handler');
function wm_front_lostpass_handler(){
    if ( ! isset($_POST['_wpnonce']) || ! wp_verify_nonce($_POST['_wpnonce'], 'wm_front_lostpass') ) {
        wp_safe_redirect( wm_site_path(WM_LOST_SLUG) );
        exit;
    }
    $login = sanitize_text_field( $_POST['user_login'] ?? '' );
    retrieve_password( $login ); // envía el correo estándar de reset
    wp_safe_redirect( add_query_arg('reset','sent', wm_site_path(WM_LOST_SLUG) ) );
    exit;
}

// =====================
// PERFIL FRONTEND
// =====================

// (Opcional pero recomendado) permitir subir archivos a foro_miembro
add_action('init', function(){
    $role = get_role('foro_miembro');
    if ( $role && ! $role->has_cap('upload_files') ) {
        $role->add_cap('upload_files', true);
    }
});

// Encolar media y jQuery SÓLO en /mi-perfil
add_action('wp_enqueue_scripts', function(){
    if ( is_page('mi-perfil') ) {
        wp_enqueue_media();
        wp_enqueue_script('jquery');
        $css = '.wm-prof input,.wm-prof textarea{width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px}
        .wm-prof label{font-weight:600;margin:0 0 6px;display:block}
        .wm-prof .row{margin-bottom:14px}
        .wm-prof .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px}
        .wm-prof .avatar{}
        .wm-prof .button{padding:.6rem 1rem;border-radius:8px}
        .wm-prof .button-primary{background:#2563eb;border-color:#2563eb;color:#fff}
        .wm-prof .notice{padding:10px 12px;border-left:4px solid; margin:0 0 14px}
        .wm-prof .notice-success{background:#ECFDF5;border-color:#10B981}
        .wm-prof .notice-error{background:#FEF2F2;border-color:#DC2626}';
        wp_add_inline_style( 'wp-block-library', $css );
    }
});

// Shortcode del formulario de perfil
add_shortcode('wm_front_profile', function($atts = []){
    if ( ! is_user_logged_in() || ! current_user_can('foro_miembro') ) {
        $login = site_url('/acceso/');
        $login = add_query_arg('redirect_to', urlencode(get_permalink()), $login);
        return '<div class="wm-prof"><div class="notice notice-error">Necesitas iniciar sesión.</div><p><a class="button" href="'.esc_url($login).'">Ir a iniciar sesión</a></p></div>';
    }

    $u = wp_get_current_user();
    $uid = $u->ID;

    // Metas
    $profile_picture = esc_url( get_user_meta($uid, 'profile_picture', true) );
    $profile_title   = esc_html( get_user_meta($uid, 'profile_title', true) );
    $bio             = get_user_meta($uid, 'description', true ); // estándar WP

    // Mensajes
    $ok   = isset($_GET['updated']) ? sanitize_text_field($_GET['updated']) : '';
    $err  = isset($_GET['error'])   ? sanitize_text_field($_GET['error'])   : '';
    $msg_ok = '';
    if ( $ok === '1' ) $msg_ok = 'Perfil actualizado.';
    if ( $ok === 'pwd' ) $msg_ok = 'Tu contraseña fue cambiada. Vuelve a iniciar sesión.';

    ob_start(); ?>
    <div class="wm-prof">
        <?php if($msg_ok): ?><div class="notice notice-success"><p><?php echo esc_html($msg_ok); ?></p></div><?php endif; ?>
        <?php if($err): ?><div class="notice notice-error"><p><?php echo esc_html($err); ?></p></div><?php endif; ?>

        <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
            <?php wp_nonce_field('wm_profile_update'); ?>

            <div class="row mb-3 g-5 align-items-center">
                <div class="col-auto" style="position: relative;">
                    <?php
                    $fallback = get_avatar_url( $uid, ['size'=>96] );
                    $src = $profile_picture ?: $fallback;
                    ?>
                    <img class="avatar" id="profile_avatar_preview" style="border-radius: 50%;width:96px;height:96px;object-fit:cover;border:1px solid #e5e7eb;" src="<?php echo esc_url($src); ?>" alt="Avatar">
                    <?php if ( current_user_can('upload_files') ): ?>
                        <p style="margin-top:8px; position: absolute;top:50px;left:90px;">
                            <button type="button" class="btn btn-ai" style="border-radius:50%" id="btn_upload_profile">
                                <i class="bi bi-camera"></i>
                            </button>
                        </p>
                    <?php endif; ?>
                </div>
                <div class="col">
                    <h1 style="margin:0;">Mi Perfil</h1>
                    <p style="color:#6b7280;">Actualiza tu información visible en el foro.</p>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <input type="text" style="display:none;" class="form-control" id="profile_picture" name="profile_picture" value="<?php echo $profile_picture; ?>" placeholder="https://...">
                </div>
                <div class="col-md-6">
                    <input type="hidden" name="action" value="wm_front_profile_update">
                </div>
                <div class="col-md-6">
                    <input type="hidden" name="user_id" value="<?php echo esc_attr($uid); ?>">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre a mostrar</label>
                    <input class="form-control" type="text" name="display_name" value="<?php echo esc_attr($u->display_name); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Cargo / Credencial</label>
                    <input class="form-control" type="text" name="profile_title" value="<?php echo esc_attr($profile_title); ?>" placeholder="Ej: CTO, PhD en Ciencias, etc.">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-12">
                    <label class="form-label">Biografía (breve)</label>
                    <textarea name="profile_bio" class="form-control" rows="5" placeholder="Cuéntanos en 2-3 líneas"><?php echo esc_textarea($bio); ?></textarea>
                </div>
            </div>

            <p><button class="btn btn-ai" type="submit" name="do" value="save_profile">Guardar perfil</button></p>

            <hr style="margin:20px 0;">

            <h3>Cambiar contraseña</h3>
            <div class="row mb-3">
                <div class="col-12">
                    <label class="form-label">Contraseña actual</label>
                    <input class="form-control" type="password" name="current_pass" autocomplete="current-password">
                    <label class="form-label">Nueva contraseña</label>
                    <input class="form-control" type="password" name="new_pass" autocomplete="new-password" minlength="8">
                    <label class="form-label">Repite la nueva contraseña</label>
                    <input class="form-control" type="password" name="new_pass2" autocomplete="new-password" minlength="8">
                </div>
            </div>
            <p><button class="btn btn-ai" type="submit" name="do" value="change_pwd">Actualizar contraseña</button></p>
        </form>
    </div>

    <?php if ( current_user_can('upload_files') ): ?>
    <script>
    jQuery(function($){
        var frame;
        $('#btn_upload_profile').on('click', function(e){
            e.preventDefault();
            if (frame) { frame.open(); return; }
            frame = wp.media({
                title: 'Seleccionar imagen de perfil',
                library: { type: 'image' },
                multiple: false,
                button: { text: 'Usar esta imagen' }
            });
            frame.on('select', function(){
                var att = frame.state().get('selection').first().toJSON();
                $('#profile_picture').val(att.url);
                $('#profile_avatar_preview').attr('src', att.url);
            });
            frame.open();
        });
    });
    </script>
    <?php endif; ?>

    <?php
    return ob_get_clean();
});

add_action('admin_post_wm_front_profile_update', 'wm_front_profile_update_handler');
add_action('admin_post_nopriv_wm_front_profile_update', function(){
    $login = site_url('/acceso/');
    wp_safe_redirect( add_query_arg('login','required', $login) );
    exit;
});

function wm_front_profile_update_handler(){
    if ( ! is_user_logged_in() || ! current_user_can('foro_miembro') ) {
        wp_safe_redirect( site_url('/') );
        exit;
    }
    if ( ! isset($_POST['_wpnonce']) || ! wp_verify_nonce($_POST['_wpnonce'], 'wm_profile_update') ) {
        wp_safe_redirect( add_query_arg('error','Solicitud inválida', wp_get_referer() ?: site_url('/mi-perfil/') ) );
        exit;
    }

    $uid = get_current_user_id();
    if ( intval($_POST['user_id'] ?? 0) !== $uid ) {
        wp_safe_redirect( add_query_arg('error','Usuario inválido', wp_get_referer() ?: site_url('/mi-perfil/') ) );
        exit;
    }

    $do = sanitize_text_field( $_POST['do'] ?? 'save_profile' );

    if ( $do === 'change_pwd' ) {
        $current = (string)($_POST['current_pass'] ?? '');
        $new     = (string)($_POST['new_pass'] ?? '');
        $new2    = (string)($_POST['new_pass2'] ?? '');

        if ( strlen($new) < 8 ) {
            wp_safe_redirect( add_query_arg('error','La nueva contraseña debe tener al menos 8 caracteres', wp_get_referer() ?: site_url('/mi-perfil/') ) );
            exit;
        }
        if ( $new !== $new2 ) {
            wp_safe_redirect( add_query_arg('error','Las contraseñas no coinciden', wp_get_referer() ?: site_url('/mi-perfil/') ) );
            exit;
        }

        $user = get_user_by('id', $uid);
        if ( ! wp_check_password( $current, $user->data->user_pass, $uid ) ) {
            wp_safe_redirect( add_query_arg('error','La contraseña actual no es correcta', wp_get_referer() ?: site_url('/mi-perfil/') ) );
            exit;
        }

        wp_set_password( $new, $uid );
        wp_safe_redirect( add_query_arg('updated','pwd', site_url('/acceso/') ) );
        exit;
    }

    // Guardar perfil
    $display_name    = sanitize_text_field( $_POST['display_name'] ?? '' );
    $profile_title   = sanitize_text_field( $_POST['profile_title'] ?? '' );
    $profile_picture = esc_url_raw( $_POST['profile_picture'] ?? '' );

    $allowed = [
        'a' => ['href'=>[], 'title'=>[], 'target'=>[]],
        'br'=>[], 'em'=>[], 'strong'=>[], 'code'=>[]
    ];
    $bio = wp_kses( (string)($_POST['profile_bio'] ?? ''), $allowed );

    if ( $display_name ) {
        wp_update_user( ['ID'=>$uid, 'display_name'=>$display_name] );
    }
    update_user_meta( $uid, 'profile_title',   $profile_title );
    update_user_meta( $uid, 'profile_picture', $profile_picture );
    update_user_meta( $uid, 'description',     $bio );

    wp_safe_redirect( add_query_arg('updated','1', wp_get_referer() ?: site_url('/mi-perfil/') ) );
    exit;
}

/** === Helpers/Badges === */
$__wm_portal_dir = plugin_dir_path( __FILE__ );
require_once $__wm_portal_dir . 'inc/forum-badges.php';

/** === Favoritos (likes) AJAX — opcional (no se usa con el toggle nativo actual) === */
add_action('wp_ajax_wm_toggle_favorite', 'wm_toggle_favorite_handler');
add_action('wp_ajax_nopriv_wm_toggle_favorite', function () {
    wp_send_json_error(['error' => 'auth']);
});
function wm_toggle_favorite_handler() {
    check_ajax_referer('wm_toggle_favorite', 'nonce');

    if ( ! is_user_logged_in() ) {
        wp_send_json_error(['error' => 'auth']);
    }
    if ( ! function_exists('bbp_is_favorites_active') || ! bbp_is_favorites_active() ) {
        wp_send_json_error(['error' => 'favorites_disabled']);
    }

    $topic_id = isset($_POST['topic_id']) ? absint($_POST['topic_id']) : 0;
    if ( ! $topic_id || get_post_type($topic_id) !== bbp_get_topic_post_type() ) {
        wp_send_json_error(['error' => 'bad_topic']);
    }

    $user_id = get_current_user_id();
    $was_fav = function_exists('bbp_is_user_favorite') ? bbp_is_user_favorite($user_id, $topic_id) : false;

    if ( $was_fav ) {
        bbp_remove_user_favorite( $user_id, $topic_id );
        $favorited = false;
    } else {
        bbp_add_user_favorite( $user_id, $topic_id );
        $favorited = true;
    }

    $count = (int) get_post_meta($topic_id, 'wm_fav_count', true);
    if ( $favorited && ! $was_fav ) $count++;
    if ( ! $favorited && $was_fav && $count > 0 ) $count--;
    update_post_meta($topic_id, 'wm_fav_count', max(0, $count));

    wp_send_json_success([
        'favorited' => $favorited,
        'count'     => (int) get_post_meta($topic_id, 'wm_fav_count', true),
    ]);
}

/** === Contador de favoritos por hooks nativos (toggle nativo) === */
if ( ! function_exists('wm_fav_safe_int') ) {
    function wm_fav_safe_int( $v ) { return max(0, (int) $v); }
}

add_action( 'bbp_add_user_favorite', function( $user_id, $topic_id ) {
    if ( ! $topic_id || get_post_type($topic_id) !== bbp_get_topic_post_type() ) return;
    $count = wm_fav_safe_int( get_post_meta( $topic_id, 'wm_fav_count', true ) );
    update_post_meta( $topic_id, 'wm_fav_count', $count + 1 );
}, 10, 2 );

add_action( 'bbp_remove_user_favorite', function( $user_id, $topic_id ) {
    if ( ! $topic_id || get_post_type($topic_id) !== bbp_get_topic_post_type() ) return;
    $count = wm_fav_safe_int( get_post_meta( $topic_id, 'wm_fav_count', true ) );
    update_post_meta( $topic_id, 'wm_fav_count', $count > 0 ? $count - 1 : 0 );
}, 10, 2 );

/** === Herramienta de recuento (opcional) === */
add_action('admin_menu', function(){
    add_management_page(
        __('Recontar likes del foro','tu-tema'),
        __('Recontar likes foro','tu-tema'),
        'manage_options',
        'wm-recount-forum-likes',
        'wm_recount_forum_likes_page'
    );
});

function wm_recount_forum_likes_page() {
    if ( ! current_user_can('manage_options') ) return;

    $did = false;
    if ( isset($_POST['wm_recount_do']) && check_admin_referer('wm_recount_do') ) {
        $did = true;
        wm_recount_forum_likes_execute();
        echo '<div class="updated notice"><p>'. esc_html__('Recuento completado.', 'tu-tema') .'</p></div>';
    }

    echo '<div class="wrap"><h1>'. esc_html__('Recontar likes del foro (favoritos bbPress)', 'tu-tema') .'</h1>';
    echo '<p>'. esc_html__('Esto recorre los usuarios y recalcula cuántas veces fue marcado favorito cada debate (topic), actualizando el meta wm_fav_count.', 'tu-tema') .'</p>';
    echo '<form method="post">';
    wp_nonce_field('wm_recount_do');
    submit_button( $did ? __('Volver a ejecutar','tu-tema') : __('Ejecutar recuento ahora','tu-tema'), 'primary', 'wm_recount_do' );
    echo '</form></div>';
}

function wm_recount_forum_likes_execute() {
    $counts = array();

    $paged = 1;
    $per   = 500;
    do {
        $users = get_users( array(
            'fields' => array( 'ID' ),
            'number' => $per,
            'paged'  => $paged,
        ) );

        foreach ( $users as $u ) {
            $uid = (int) $u->ID;

            if ( function_exists('bbp_get_user_favorites_topic_ids') ) {
                $fav_ids = (array) bbp_get_user_favorites_topic_ids( $uid );
            } else {
                $fav_ids = (array) ( function_exists('bbp_get_user_favorites') ? bbp_get_user_favorites( $uid ) : array() );
            }
            if ( empty( $fav_ids ) ) continue;

            foreach ( $fav_ids as $tid ) {
                $tid = (int) $tid;
                if ( ! $tid || get_post_type($tid) !== bbp_get_topic_post_type() ) continue;
                if ( ! isset($counts[$tid]) ) $counts[$tid] = 0;
                $counts[$tid]++;
            }
        }

        $paged++;
    } while ( count($users) === $per );

    foreach ( $counts as $tid => $c ) {
        $c = wm_fav_safe_int( $c );
        $current = wm_fav_safe_int( get_post_meta( $tid, 'wm_fav_count', true ) );
        if ( $current !== $c ) {
            update_post_meta( $tid, 'wm_fav_count', $c );
        }
    }

    // Opcional: poner a 0 los que no quedaron en $counts
    $topics = get_posts( array(
        'post_type'      => bbp_get_topic_post_type(),
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ) );
    foreach ( $topics as $tid ) {
        if ( isset($counts[$tid]) ) continue;
        $current = wm_fav_safe_int( get_post_meta( $tid, 'wm_fav_count', true ) );
        if ( $current !== 0 ) {
            update_post_meta( $tid, 'wm_fav_count', 0 );
        }
    }
}
