<?php
/**
 * Template Name: Mis suscripciones (bbPress)
 * Description: Página privada con el listado completo de suscripciones del usuario.
 */

if ( ! defined('ABSPATH') ) exit;

get_header();

// Forzar login
if ( ! is_user_logged_in() ) {
    $login = site_url('/acceso/');
    $login = add_query_arg('redirect_to', urlencode( home_url( add_query_arg( null, null ) ) ), $login);
    wp_safe_redirect( $login );
    exit;
}
?>
     <?php get_template_part('gradient');?>
<main id="primary" class="container site-main">
  <header style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px;">
    <h1 style="margin:0;"><?php echo esc_html( get_the_title() ?: __('Mis suscripciones','tu-tema') ); ?></h1>
    <nav style="font-size:14px;">
      <a href="<?php echo esc_url( site_url('/mi-area/') ); ?>">← <?php esc_html_e('Volver a Mi área','tu-tema'); ?></a>
    </nav>
  </header>

  <?php
  // Intro editable desde WP (opcional)
  while ( have_posts() ) : the_post();
    if ( get_the_content() ) {
      echo '<div class="page-intro" style="margin-bottom:16px;">';
      the_content();
      echo '</div>';
    }
  endwhile;

  // Render del listado completo (modular)
  if ( function_exists('wm_render_user_subscriptions') ) {
      echo wm_render_user_subscriptions([
        'per_page'        => 12,          // items por página
        'orderby'         => 'post__in',  // respeta orden natural de la lista del usuario
        'order'           => 'DESC',
        'show_unsubscribe'=> true,        // muestra toggle nativo para (des)uscribirse
        'query_var'       => 'pg_subs',   // paginación local ?pg_subs=2
        'include_forums'  => true,        // si no hay temas, mostrar foros suscritos
      ]);
  } else {
      echo '<div class="notice notice-error" style="padding:12px;border-left:4px solid #DC2626;background:#FEF2F2;margin:12px 0;">'
         . esc_html__('Falta cargar el módulo de suscripciones en el theme (inc/wm-user-subscriptions.php).','tu-tema')
         . '</div>';
  }
  ?>
</main>

<?php get_footer(); ?>
