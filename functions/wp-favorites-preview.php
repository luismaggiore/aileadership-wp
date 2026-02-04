<?php
/**
 * Módulo: Preview de favoritos del usuario (bbPress) para "Mi Área"
 * Uso en plantillas: echo wm_render_user_favorites_preview([
 *   'max' => 6,
 *   'cta_url' => site_url('/myfavorites/'),
 *   'orderby' => 'post__in', // 'post__in' (por defecto) | 'date' | 'title' | 'modified'
 *   'show_unfavorite' => true, // mostrar toggle nativo (recarga)
 *   'empty_text' => 'Aún no tienes favoritos.'
 * ]);
 */

if ( ! defined('ABSPATH') ) exit;

if ( ! function_exists('wm_render_user_favorites_preview') ) {

  /**
   * Renderiza un listado compacto de los últimos favoritos del usuario actual.
   * @param array $args {
   *   @type int    $max             Máximo de items a mostrar (def: 6)
   *   @type string $cta_url         URL del listado completo (def: /myfavorites/)
   *   @type string $orderby         'post__in'|'date'|'title'|'modified' (def: 'post__in')
   *   @type bool   $show_unfavorite Mostrar toggle nativo para quitar/poner favorito (def: true)
   *   @type string $empty_text      Texto cuando no hay favoritos (def: 'Aún no tienes favoritos.')
   * }
   * @return string HTML
   */
  function wm_render_user_favorites_preview( $args = [] ) {

    // Defaults
    $args = wp_parse_args( $args, [
      'max'             => 6,
      'cta_url'         => site_url('/myfavorites/'),
      'orderby'         => 'post__in',
      'show_unfavorite' => true,
      'empty_text'      => __( 'Aún no tienes favoritos.', 'tu-tema' ),
    ]);

    // Reqs mínimos
    if ( ! is_user_logged_in() ) {
      $login = site_url('/acceso/');
      $login = add_query_arg('redirect_to', urlencode( get_permalink() ?: home_url('/') ), $login);
      return '<div class="notice notice-error">'. esc_html__('Necesitas iniciar sesión.', 'tu-tema') .'</div>'
           . '<p><a class="button" href="'. esc_url($login) .'">'. esc_html__('Iniciar sesión','tu-tema') .'</a></p>';
    }
    if ( ! function_exists('bbp_is_favorites_active') || ! bbp_is_favorites_active() ) {
      return '<div class="notice notice-error">'. esc_html__('Los favoritos del foro no están activos.', 'tu-tema') .'</div>';
    }

    $user_id = get_current_user_id();
    $max     = max(1, (int) $args['max']);
    $orderby = in_array($args['orderby'], ['post__in','date','title','modified'], true) ? $args['orderby'] : 'post__in';
    $cta_url = esc_url( $args['cta_url'] );
    $show_un = (bool) $args['show_unfavorite'];

    // 1) Obtener TODOS los IDs de favoritos del usuario
    if ( function_exists('bbp_get_user_favorites_topic_ids') ) {
      $all_ids = (array) bbp_get_user_favorites_topic_ids( $user_id );
    } else {
      $all_ids = (array) ( function_exists('bbp_get_user_favorites') ? bbp_get_user_favorites( $user_id ) : [] );
    }
    $all_ids = array_values( array_filter( array_map( 'absint', $all_ids ) ) );
    $total   = count( $all_ids );

    // 2) Si no hay favoritos → mensaje
    if ( $total === 0 ) {
      return '<div class="wm-fav-preview-empty">'. esc_html( $args['empty_text'] ) .'</div>';
    }

    // 3) Preparar subset de IDs a consultar (solo los primeros $max)
    $ids_for_query = $all_ids;
    if ( $orderby === 'post__in' ) {
      // Mantener el orden natural y recortar antes de consultar
      $ids_for_query = array_slice( $all_ids, 0, $max );
    }

    // 4) Query independiente (no afecta loop de bbPress)
    $qargs = [
      'post_type'           => function_exists('bbp_get_topic_post_type') ? bbp_get_topic_post_type() : 'topic',
      'post__in'            => $ids_for_query,
      'posts_per_page'      => $max,
      'no_found_rows'       => true,
      'ignore_sticky_posts' => true,
      'orderby'             => ($orderby === 'post__in') ? 'post__in' : $orderby,
      'order'               => 'DESC',
    ];

    $q = new WP_Query( $qargs );

    ob_start();
    echo '<div class="wm-fav-preview mb-4">
     <div class="fav-loop-title">
    <h2 class="my-area-title">Tus favoritos</h2>
      <a  href="'. $cta_url .'" aria-label="'. esc_attr__('Ver todas mis favoritos', 'tu-tema') .'">
    <button class="btn-plus">
      <i class="bi bi-plus"></i> 
    </button>
    </a>
    </div>
    <ul class="fav-loop">
   


    ';

    if ( $q->have_posts() ) :
      while ( $q->have_posts() ) : $q->the_post();
        $topic_id   = get_the_ID();
        $forum_id   = function_exists('bbp_get_topic_forum_id') ? bbp_get_topic_forum_id($topic_id) : 0;
        $replies    = function_exists('bbp_get_topic_reply_count') ? (int) bbp_get_topic_reply_count($topic_id) : 0;
        $likes      = (int) get_post_meta( $topic_id, 'wm_fav_count', true );

        // Freshness
        $last_reply_id   = function_exists('bbp_get_topic_last_reply_id') ? bbp_get_topic_last_reply_id( $topic_id ) : 0;
        $last_post_id    = $last_reply_id ? $last_reply_id : $topic_id;
        $last_link       = ($last_reply_id && function_exists('bbp_get_reply_url')) ? bbp_get_reply_url( $last_reply_id ) : get_permalink( $topic_id );
        $last_author_id  = (int) get_post_field( 'post_author', $last_post_id );
        $last_author     = $last_author_id ? get_the_author_meta( 'display_name', $last_author_id ) : '';
        $last_author_url = ($last_author_id && function_exists('bbp_get_user_profile_url')) ? bbp_get_user_profile_url( $last_author_id ) : '#';
        $last_ts         = get_post_time( 'U', true, $last_post_id );
        $last_when       = human_time_diff( $last_ts, current_time('timestamp') );

        // Toggle nativo (opcional)
        $fav_toggle_html = '';
        if ( $show_un && function_exists('bbp_get_topic_favorite_link') ) {
          $fav_toggle_html = bbp_get_topic_favorite_link([
            'topic_id'   => $topic_id,
            'user_id'    => $user_id,
            'before'     => '',
            'after'      => '',
            'link_class' => 'btn-like',
            'favorite'   => '<i class="bi bi-heart"></i> <span class="like-count">'. number_format_i18n($likes) .'</span>',
            'favorited'  => '<i class="bi bi-heart-fill"></i> <span class="like-count">'. number_format_i18n($likes) .'</span>',
          ]);
        }
        ?>
        <li>
        <article class="wm--item">
          <header >
            <h3>
              <a href="<?php echo esc_url( get_permalink($topic_id) ); ?>" >
                <?php echo esc_html( get_the_title($topic_id) ); ?>
              </a>
            </h3>
            <?php if ( $show_un ) : ?>
              <div class="wm-toggle"><?php echo $fav_toggle_html; ?></div>
            <?php endif; ?>
          </header>

          <div class="wm--meta">
         <?php if ( $last_author ) : ?>
              <span>Última resp. hace<a href="<?php echo esc_url( $last_link ); ?>"> <?php echo esc_html( $last_when ); ?></a></span>
            <?php endif; ?>
          </div>
        </article>
        </li>
        <?php
      endwhile;
      wp_reset_postdata();
    endif;

    // 5) CTA "Ver todos" si hay más que el máximo
   

    echo '
    </ul>
       '; // .wm-fav-preview

     
  echo '
   </div>';

    return ob_get_clean();
  }
}
