<?php
/**
 * Template Name: Mis favoritos (bbPress)
 * Description: Página privada con el listado completo de favoritos del usuario.
 */

if ( ! defined('ABSPATH') ) exit;

get_header();

// Redirige a login si no está autenticado
if ( ! is_user_logged_in() ) {
    $login = site_url('/acceso/');
    // redirigir a esta misma URL luego del login
    $login = add_query_arg('redirect_to', urlencode( home_url( add_query_arg( null, null ) ) ), $login);
    wp_safe_redirect( $login );
    exit;
}
?>
     <?php get_template_part('gradient');?>
<main id="primary" class="container site-main" >
  <header style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px;">
    <h1 style="margin:0;"><?php echo esc_html( get_the_title() ?: __('Mis favoritos','tu-tema') ); ?></h1>
    <nav style="font-size:14px;">
      <a href="<?php echo esc_url( site_url('/mi-area/') ); ?>">← <?php esc_html_e('Volver a Mi área','tu-tema'); ?></a>
    </nav>
  </header>

  <?php
  // Contenido de la página (opcional, editable en WP)
  while ( have_posts() ) : the_post();
    if ( get_the_content() ) {
      echo '<div class="page-intro" style="margin-bottom:16px;">';
      the_content();
      echo '</div>';
    }
  endwhile;

  // Render independiente del loop de bbPress (usa tu módulo del theme)
  if ( function_exists('wm_render_user_favorites') ) {

      echo wm_render_user_favorites([
        'per_page'        => 12,        // ajusta a gusto
        'orderby'         => 'post__in',// respeta el orden natural de favoritos
        'order'           => 'DESC',
        'show_unfavorite' => '1',       // muestra toggle nativo para quitar
        'query_var'       => 'pg_fav',  // paginación local ?pg_fav=2
      ]);

  } else {

      // Fallback por si el módulo no está cargado
      echo '<div class="notice notice-error" style="padding:12px;border-left:4px solid #DC2626;background:#FEF2F2;margin:12px 0;">'
         . esc_html__('Falta cargar el módulo de favoritos en el theme (inc/wm-user-favorites.php).','tu-tema')
         . '</div>';

      if ( shortcode_exists('wm_user_favorites') ) {
          echo do_shortcode('[wm_user_favorites per_page="12" orderby="post__in" order="DESC" show_unfavorite="1"]');
      }
  }
  ?>
</main>

<?php get_footer(); ?>
