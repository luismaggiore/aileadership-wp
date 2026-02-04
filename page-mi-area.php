<?php
/**
 * Template Name: Dashboard Foro Miembro
 */
if ( ! defined('ABSPATH') ) exit;

// Redirección temprana si no es miembro
if ( ! is_user_logged_in() || ! current_user_can('foro_miembro') ) {
    $login = site_url('/acceso/');
    $login = add_query_arg( 'redirect_to', urlencode( get_permalink() ), $login );
    wp_safe_redirect( add_query_arg('login','required', $login) );
    exit;
}

add_action('wp_head', function(){ echo '<meta name="robots" content="noindex,nofollow" />' . "\n"; });

get_header();
$user = wp_get_current_user();
?>
      <?php get_template_part('gradient-2');?>

<main  style="padding-top:clamp(80px,15vh,200px);">
        <header style="margin:0 ">
                <div class="container-lg" style="margin:0 auto;">

            <div class="row justify-content-between">
                <div class="col">             
            <h1>Hola, <?php echo esc_html( $user->display_name ); ?> </h1>
            <p style="color:#6b7280;margin:6px 0 0;">Bienvenido a tu panel de miembro.</p>
                </div>
                 <div class="col-md-auto">
                      <a class="card-btn" href="<?php echo esc_url( site_url('/mi-perfil/') ); ?>">
                    <i class="bi bi-palette-fill" style="margin-right:5px"></i>Editar Perfil
                
            </a>
              <a class="card-btn" href="<?php echo esc_url( wp_logout_url( home_url('/') ) ); ?>">
                    <i class="bi bi-box-arrow-right" style="margin-right:5px"></i>Log out
                
            </a>
        </div>


              </div>  
        </div>
        </header>

    
<div class="container-lg mt-5">
   <div class="row g-2">        <?php if ( function_exists('bbp_has_topics') ) : ?>
            <div class="col-xl-9 col-lg-8">
            <div class="row g-1 mb-2">
                <div class="col-6">
                  <a class="forum-wp"  href="<?php echo esc_url( site_url('/foro-privado/') ); ?>" style="text-decoration:none;">
                    <div class="forum-wp-div">
           
                <h3 style="margin:0 0 8px;"> Foro</h3>
                <p style="margin:0;color:#6b7280;">Entrar al foro y ver los temas más recientes.</p>  </div>
            </a>
            </div>

             <div class="col-6">
           <a class="forum-wp"  href="<?php echo esc_url( site_url('/contenido-exclusivo/') ); ?>" style="text-decoration:none;" >
                 <div class="forum-wp-div">
                <h3 style="margin:0 0 8px;"> Contenido exclusivo</h3>
                <p style="margin:0;color:#6b7280;">Ver el contenido reservado para los miembros de la comunidad.</p>
           
                     </div> 
                   </a>
                </div> 
                
            </div>
            
              
                  

            


            <?php
echo wm_render_user_recent_participations([
  'limit' => 3,            // cuántas participaciones mostrar
  'unique_by_topic' => true // no repetir el mismo debate
]);

            ?>
            <?php if ( bbp_has_topics( array( 'author' => get_current_user_id(), 'posts_per_page' => 3 ) ) ) : ?>
                <?php bbp_get_template_part( 'loop', 'topics2' ); ?>
            <?php else : ?>
                <p style="color:#6b7280;">Aún no has creado temas. ¡Anímate a iniciar uno en el foro!</p>
            <?php endif; ?>
          
        <?php endif; ?>
       </div> 

                <div class="col-xl-3 col-lg-4 ">
              <?php get_template_part('forum-sidebar');?>
</div>

 </div>
</div>
</main>
<?php get_footer();
