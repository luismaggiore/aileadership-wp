<?php
/**
 * Template Name: Acceso (Login Frontend)
 */
if ( ! defined('ABSPATH') ) exit;

// Redirección temprana si ya está dentro
if ( is_user_logged_in() ) {
    // Si es miembro aprobado, al dashboard
    $user = wp_get_current_user();
    $is_pending = (bool) get_user_meta( $user->ID, 'wm_pending_approval', true );
    if ( in_array('foro_miembro', (array)$user->roles, true) && ! $is_pending ) {
        wp_safe_redirect( site_url('/mi-area/') );
        exit;
    }
}

add_action('wp_head', function(){
    echo '<meta name="robots" content="noindex,nofollow" />' . "\n";
});

get_header(); ?>

    <div class="container text-center" style="padding-top:15vh">
    <section class="card text-start" style="width:min(420px,100%);background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:28px;">
        <h1 style="margin-top:0;">Iniciar sesión</h1>
        <?php echo do_shortcode('[wm_front_login]'); ?>
    </section>
    </div>
<?php get_footer();
