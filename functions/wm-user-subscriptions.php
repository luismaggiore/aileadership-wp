<?php
/**
 * Listado completo de suscripciones del usuario (bbPress), independiente del loop de bbPress.
 * - Función: echo wm_render_user_subscriptions([...]);
 * - Shortcode: [wm_user_subscriptions per_page="12" orderby="post__in" order="DESC" show_unsubscribe="1" query_var="pg_subs" include_forums="1"]
 */

if ( ! defined('ABSPATH') ) exit;

if ( ! function_exists('wm__get_user_subscribed_topic_ids') ) {
  /**
   * Robusto: obtiene IDs de TEMAS suscritos para distintas versiones de bbPress.
   */
  function wm__get_user_subscribed_topic_ids( $user_id ) {
    $ids = [];

    if ( function_exists('bbp_get_user_subscribed_topic_ids') ) {
      $ids = (array) bbp_get_user_subscribed_topic_ids( $user_id );
    } elseif ( function_exists('bbp_get_user_subscriptions_topic_ids') ) {
      $ids = (array) bbp_get_user_subscriptions_topic_ids( $user_id );
    } elseif ( function_exists('bbp_get_user_subscriptions') ) {
      $raw = (array) bbp_get_user_subscriptions( $user_id );
      foreach ( $raw as $id ) {
        if ( get_post_type( $id ) === bbp_get_topic_post_type() ) $ids[] = (int) $id;
      }
    } else {
      // Fallback duro: user meta directo (CSV o array)
      $meta = get_user_meta( $user_id, '_bbp_subscriptions', true );
      if ( is_array( $meta ) ) {
        $ids = $meta;
      } elseif ( is_string( $meta ) && strlen( $meta ) ) {
        $ids = array_map( 'intval', array_filter( array_map( 'trim', explode( ',', $meta ) ) ) );
      }
    }

    return array_values( array_filter( array_map( 'absint', $ids ) ) );
  }
}

if ( ! function_exists('wm__get_user_subscribed_forum_ids') ) {
  /**
   * IDs de FOROS suscritos (si quieres mostrarlos cuando no hay temas).
   */
  function wm__get_user_subscribed_forum_ids( $user_id ) {
    $ids = [];
    if ( function_exists('bbp_get_user_subscriptions') ) {
      $raw = (array) bbp_get_user_subscriptions( $user_id );
      foreach ( $raw as $id ) {
        if ( get_post_type( $id ) === bbp_get_forum_post_type() ) $ids[] = (int) $id;
      }
    }
    return array_values( array_filter( array_map( 'absint', $ids ) ) );
  }
}

if ( ! function_exists('wm_render_user_subscriptions') ) {

  /**
   * Renderiza TODAS las suscripciones (temas) del usuario con paginación propia.
   * Si no hay temas y include_forums=true, muestra foros suscritos.
   *
   * @param array $args {
   *   @type int    $per_page        Items por página (def: 12)
   *   @type string $orderby         'post__in'|'date'|'title'|'modified' (def: 'post__in')
   *   @type string $order           'DESC'|'ASC' (def: 'DESC')
   *   @type bool   $show_unsubscribeMostrar toggle nativo (def: true)
   *   @type string $query_var       Query var para paginación local (def: 'pg_subs')
   *   @type bool   $include_forums  Si no hay temas, listar foros suscritos (def: true)
   * }
   * @return string HTML
   */
  function wm_render_user_subscriptions( $args = [] ) {

    // Defaults
    $args = wp_parse_args( $args, [
      'per_page'        => 12,
      'orderby'         => 'post__in',
      'order'           => 'DESC',
      'show_unsubscribe'=> true,
      'query_var'       => 'pg_subs',
      'include_forums'  => true,
    ]);

    // Requisitos
    if ( ! function_exists('bbp_get_topic_post_type') ) {
      return '<div class="notice notice-error">bbPress no está activo.</div>';
    }
    if ( ! is_user_logged_in() ) {
      $login = site_url('/acceso/');
      $login = add_query_arg('redirect_to', urlencode( get_permalink() ?: home_url('/') ), $login);
      return '<div class="notice notice-error">'. esc_html__('Necesitas iniciar sesión.', 'tu-tema') .'</div>'
           . '<p><a class="button" href="'. esc_url($login) .'">'. esc_html__('Iniciar sesión','tu-tema') .'</a></p>';
    }
    if ( ! function_exists('bbp_is_subscriptions_active') || ! bbp_is_subscriptions_active() ) {
      return '<div class="notice notice-error">'. esc_html__('Las suscripciones de bbPress no están activas.', 'tu-tema') .'</div>';
    }

    $user_id  = get_current_user_id();
    $per_page = max(1, (int) $args['per_page']);
    $orderby  = in_array($args['orderby'], ['post__in','date','title','modified'], true) ? $args['orderby'] : 'post__in';
    $order    = (strtoupper($args['order']) === 'ASC') ? 'ASC' : 'DESC';
    $qv       = preg_replace('/[^a-z0-9_\-]/i', '', $args['query_var']);
    $show_un  = (bool) $args['show_unsubscribe'];
    $incl_forums = (bool) $args['include_forums'];

    // Paginación propia
    $paged = isset($_GET[$qv]) ? max(1, (int) $_GET[$qv]) : 1;

    // === Temas suscritos
    $topic_ids = wm__get_user_subscribed_topic_ids( $user_id );

    // Si hay temas → listarlos con WP_Query
    if ( ! empty( $topic_ids ) ) {
      $q = new WP_Query( [
        'post_type'           => bbp_get_topic_post_type(),
        'post__in'            => $topic_ids,
        'posts_per_page'      => $per_page,
        'paged'               => $paged,
        'no_found_rows'       => false,
        'ignore_sticky_posts' => true,
        'orderby'             => ($orderby === 'post__in') ? 'post__in' : $orderby,
        'order'               => $order,
      ] );

      ob_start();
      echo '<div class="wm-subs-list">';

      if ( $q->have_posts() ) {
        while ( $q->have_posts() ) { $q->the_post();
          $topic_id   = get_the_ID();
          $forum_id   = bbp_get_topic_forum_id( $topic_id );
          $replies    = (int) bbp_get_topic_reply_count( $topic_id );
          $likes      = (int) get_post_meta( $topic_id, 'wm_fav_count', true );

          // Freshness
          $last_reply_id   = bbp_get_topic_last_reply_id( $topic_id );
          $last_post_id    = $last_reply_id ? $last_reply_id : $topic_id;
          $last_link       = $last_reply_id ? bbp_get_reply_url( $last_reply_id ) : get_permalink( $topic_id );
          $last_author_id  = (int) get_post_field( 'post_author', $last_post_id );
          $last_author     = $last_author_id ? get_the_author_meta( 'display_name', $last_author_id ) : '';
          $last_author_url = $last_author_id ? bbp_get_user_profile_url( $last_author_id ) : '#';
          $last_ts         = get_post_time( 'U', true, $last_post_id );
          $last_when       = human_time_diff( $last_ts, current_time('timestamp') );

          // Toggle nativo de suscripción (seguro con nonce)
          $subs_toggle_html = '';
          if ( $show_un && function_exists('bbp_get_user_subscribe_link') ) {
            $subs_toggle_html = bbp_get_user_subscribe_link([
              'topic_id'    => $topic_id,
              'user_id'     => $user_id,
              'before'      => '',
              'after'       => '',
              'subscribe'   => '<i class="bi bi-bell"></i> '. esc_html__('Suscribirme','tu-tema'),
              'unsubscribe' => '<i class="bi bi-bell-slash"></i> '. esc_html__('Cancelar','tu-tema'),
              'link_class'  => 'btn btn-subscribe',
            ]);
          }
          ?>
          <article class="wm-subs-card" style="border:1px solid #e5e7eb;border-radius:12px;padding:14px;margin-bottom:12px;background:#fff;">
            <header style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
              <h3 style="margin:0;font-size:18px;line-height:1.3;">
                <a href="<?php echo esc_url( get_permalink($topic_id) ); ?>" style="text-decoration:none;color:#111827;"><?php echo esc_html( get_the_title($topic_id) ); ?></a>
              </h3>
              <?php if ( $show_un ) : ?>
                <div class="wm-subs-toggle"><?php echo $subs_toggle_html; ?></div>
              <?php endif; ?>
            </header>

            <div class="wm-subs-meta" style="display:flex;flex-wrap:wrap;gap:14px;color:#6b7280;font-size:14px;margin-top:8px;">
              <?php if ( $forum_id ) : ?>
                <span>en <a href="<?php echo esc_url( bbp_get_forum_permalink($forum_id) ); ?>" style="color:inherit;text-decoration:none;"><?php echo esc_html( bbp_get_forum_title($forum_id) ); ?></a></span>
              <?php endif; ?>
              <span><i class="bi bi-chat"></i> <?php echo number_format_i18n( $replies ); ?> <?php esc_html_e('respuestas','tu-tema'); ?></span>
              <span><i class="bi bi-heart-fill" style="color:#ef4444;"></i> <?php echo number_format_i18n( max(0,$likes) ); ?> <?php esc_html_e('me gusta','tu-tema'); ?></span>
              <?php if ( $last_author ) : ?>
                <span>Última respuesta de <a href="<?php echo esc_url( $last_author_url ); ?>" style="color:inherit;text-decoration:none;"><?php echo esc_html($last_author); ?></a> <a href="<?php echo esc_url( $last_link ); ?>">hace <?php echo esc_html( $last_when ); ?></a></span>
              <?php endif; ?>
            </div>
          </article>
          <?php
        }
        wp_reset_postdata();
      } else {
        echo '<div class="notice notice-info">No hay resultados.</div>';
      }

      // Paginación propia
      if ( $q->max_num_pages > 1 ) {
        $base = remove_query_arg( $qv );
        $links = paginate_links( [
          'base'      => add_query_arg( $qv, '%#%', $base ),
          'format'    => '',
          'current'   => $paged,
          'total'     => $q->max_num_pages,
          'type'      => 'list',
          'prev_text' => '&larr;',
          'next_text' => '&rarr;',
        ] );
        if ( $links ) {
          echo '<nav class="wm-subs-pagination" aria-label="Paginación suscripciones">'. $links .'</nav>';
        }
      }

      echo '</div>';
      return ob_get_clean();
    }

    // === Si no hay TEMAS y permitimos FOROS → mostrar foros suscritos
    if ( $incl_forums ) {
      $forum_ids = wm__get_user_subscribed_forum_ids( $user_id );

      if ( ! empty( $forum_ids ) ) {
        // Paginamos los foros suscritos también
        $total_forums = count( $forum_ids );
        $chunks = array_chunk( $forum_ids, $per_page );
        $max_pages = max(1, count($chunks));
        $paged = min( $paged, $max_pages );
        $page_forums = $chunks ? $chunks[$paged-1] : [];

        ob_start();
        echo '<div class="wm-subs-forums-list">';
        echo '<div class="notice notice-info" style="margin-bottom:10px;">'. esc_html__('Mostrando foros suscritos (no tienes temas suscritos).', 'tu-tema') .'</div>';

        foreach ( $page_forums as $fid ) {
          $title = get_the_title( $fid );
          $link  = bbp_get_forum_permalink( $fid );

          $forum_toggle = '';
          if ( $show_un && function_exists('bbp_get_user_subscribe_link') ) {
            $forum_toggle = bbp_get_user_subscribe_link([
              'forum_id'    => $fid,
              'user_id'     => $user_id,
              'before'      => '',
              'after'       => '',
              'subscribe'   => '<i class="bi bi-bell"></i> '. esc_html__('Suscribirme','tu-tema'),
              'unsubscribe' => '<i class="bi bi-bell-slash"></i> '. esc_html__('Cancelar','tu-tema'),
              'link_class'  => 'btn btn-subscribe',
            ]);
          }
          ?>
          <article class="wm-subs-forum-card" style="border:1px solid #e5e7eb;border-radius:12px;padding:14px;margin-bottom:12px;background:#fff;">
            <header style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
              <h3 style="margin:0;font-size:18px;line-height:1.3;">
                <a href="<?php echo esc_url( $link ); ?>" style="text-decoration:none;color:#111827;"><?php echo esc_html( $title ); ?></a>
              </h3>
              <?php if ( $show_un ) : ?>
                <div class="wm-subs-toggle"><?php echo $forum_toggle; ?></div>
              <?php endif; ?>
            </header>
            <div class="wm-subs-meta" style="color:#6b7280;font-size:14px;margin-top:8px;">
              <?php esc_html_e('Foro suscrito','tu-tema'); ?>
            </div>
          </article>
          <?php
        }

        // Paginación manual de foros
        if ( $max_pages > 1 ) {
          $base = remove_query_arg( $qv );
          $links = paginate_links( [
            'base'      => add_query_arg( $qv, '%#%', $base ),
            'format'    => '',
            'current'   => $paged,
            'total'     => $max_pages,
            'type'      => 'list',
            'prev_text' => '&larr;',
            'next_text' => '&rarr;',
          ] );
          if ( $links ) {
            echo '<nav class="wm-subs-pagination" aria-label="Paginación foros">'. $links .'</nav>';
          }
        }

        echo '</div>';
        return ob_get_clean();
      }
    }

    // Sin temas ni foros
    return '<div class="notice notice-info">'. esc_html__('Aún no sigues ningún tema.', 'tu-tema') .'</div>';
  }
}

// Shortcode (opcional)
add_shortcode('wm_user_subscriptions', function( $atts = [] ){
  $atts = shortcode_atts([
    'per_page'        => '12',
    'orderby'         => 'post__in',
    'order'           => 'DESC',
    'show_unsubscribe'=> '1',
    'query_var'       => 'pg_subs',
    'include_forums'  => '1',
  ], $atts, 'wm_user_subscriptions');

  $atts['per_page']         = (int) $atts['per_page'];
  $atts['show_unsubscribe'] = ($atts['show_unsubscribe'] === '1');
  $atts['include_forums']   = ($atts['include_forums'] === '1');

  return wm_render_user_subscriptions( $atts );
});
