<?php
/**
 * Template Name: Foro Privado (Índice)
 * Description: Índice del foro para miembros (bbPress) con UX personalizada.
 */
if ( ! defined('ABSPATH') ) exit;

// Protección temprana: sólo miembros del foro
if ( ! is_user_logged_in() || ! current_user_can('foro_miembro') ) {
    $login = site_url('/acceso/');
    $login = add_query_arg( 'redirect_to', urlencode( get_permalink() ), $login );
    wp_safe_redirect( add_query_arg('login','required', $login) );
    exit;
}

// Noindex
add_action('wp_head', function(){
    echo '<meta name="robots" content="noindex,nofollow" />' . "\n";
});

get_header(); ?>
      <?php get_template_part('gradient-2');?>

<main class="site-main site-main--foro"  style="padding-top:clamp(80px,15vh,200px);">
    <div class="container mb-4" >
        
                <h1 style="margin:0;">Bienvenido a la discusión sobre IA</h1>
                <p style="margin:6px 0 0;color:#6b7280">Explora los foros y participa en los temas.</p>
            </div>
     

        <section class="foro-index" >
                <div class="container" >
                <div class="row g-2">
                        
                <div class="col-xl-9 col-lg-8">
                 
                <div class="row g-2 " >
                   <?php bbp_get_template_part( 'loop', 'forums2' ); ?>
                </div>
                                <div class="row " >

                      <?php if ( function_exists('bbp_has_topics') ) : ?>
                <section class="foro-mis-temas" style="margin-top:24px;">
                       

                
                        <?php if ( bbp_has_topics( array( 'author' => get_current_user_id(), 'posts_per_page' => 2 ) ) ) : ?>
                            <?php bbp_get_template_part( 'loop', 'topics2' ); ?>
                        <?php else : ?>
                            <p style="color:#6b7280;">Aún no has creado temas.</p>
                        <?php endif; ?>               

                </section>
        <?php endif; ?>
           </div>
                </div>
                
                <div class="col-xl-3 col-lg-4 ">
                <?php get_template_part('forum-sidebar');?>
                </div>
                </div>   
                </div>
        </section>

  

</main>

<?php get_footer();
