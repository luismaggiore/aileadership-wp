<?php
/**
 * Template Name: Mi Perfil (Frontend)
 */
if ( ! defined('ABSPATH') ) exit;

// Protección temprana (antes de imprimir):
if ( ! is_user_logged_in() || ! current_user_can('foro_miembro') ) {
    $login = site_url('/acceso/');
    $login = add_query_arg( 'redirect_to', urlencode( get_permalink() ), $login );
    wp_safe_redirect( add_query_arg('login','required',$login) );
    exit;
}

add_action('wp_head', function(){
    echo '<meta name="robots" content="noindex,nofollow" />' . "\n";
});

get_header(); ?>
      <?php get_template_part('gradient-2');?>

<main>
    <section style="padding-top:clamp(80px,15vh,200px)">
        <div class="container">        
            
     

        <?php echo do_shortcode('[wm_front_profile]'); ?>
    </div>

    </section>
</main>
<?php get_footer();
