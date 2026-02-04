<?php
/**
 * Template Name: Recuperar Clave (Frontend)
 */
if ( ! defined('ABSPATH') ) exit;

add_action('wp_head', function(){ echo '<meta name="robots" content="noindex,nofollow" />' . "\n"; });

get_header(); ?>
<main class="site-main site-main--lost" style="display:flex;justify-content:center;padding:40px 16px;padding-top:15vh">
    <section class="card" style="width:min(460px,100%);background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:28px;">
        <h1 style="margin-top:0;">Restablecer contraseña</h1>
        <p style="color:#6b7280;">Ingresa tu email y te enviaremos un enlace para restablecerla.</p>
        <?php echo do_shortcode('[wm_front_lostpassword]'); ?>
    </section>
</main>
<?php get_footer();
