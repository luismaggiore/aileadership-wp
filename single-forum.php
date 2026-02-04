<?php
/**
 * Single Forum Wrapper (theme root)
 * Se encarga del layout general y delega el contenido a bbpress/content-single-forum.php
 */

if ( ! defined('ABSPATH') ) exit;

get_header(); ?>
<?php get_template_part('gradient-2');?>
<main id="primary" class="site-main site-main--forum">
    <div class="container">
    <?php
    // Avisos globales de bbPress (errores, etc.)
    do_action( 'bbp_before_main_content' );

    // Carga la plantilla de contenido del foro
    bbp_get_template_part( 'bbpress/content', 'single-forum' );

    do_action( 'bbp_after_main_content' );
    ?></div>
</main>

<?php get_footer();
