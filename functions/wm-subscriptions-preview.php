<?php
/**
 * Módulo: Preview de suscripciones del usuario (bbPress) para "Mi Área"
 * - Shortcode: [wm_user_subscriptions_preview max="6" cta_url="/mysubscriptions/" orderby="post__in" show_unsubscribe="1" include_forums="1"]
 * - Función: echo wm_render_user_subscriptions_preview([...]);
 */
if ( ! defined('ABSPATH') ) exit;

if ( ! function_exists('wm_render_user_subscriptions_preview') ) {

  function wm_render_user_subscriptions_preview( $args = [] ) {

    // Defaults
    $args = wp_parse_args( $args, [
      'max'               => 6,
      'cta_url'           => site_url('/mysubscriptions/'),
      'orderby'           => 'post__in',   // 'post__in' | 'date' | 'title' | 'modified'
      'show_unsubscribe'  => true,
      'empty_text'        => __( 'Aún no sigues ningún tema.', 'tu-tema' ),
      'include_forums'    => true,         // si no hay temas, mostrar foros suscritos
    ]);

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

    $user_id = get_current_user_id();
    $max     = max(1, (int) $args['max']);
    $orderby = in_array($args['orderby'], ['post__in','date','title','modified'], true) ? $args['orderby'] : 'post__in';
    $cta_url = esc_url( $args['cta_url'] );
    $show_un = (bool) $args['show_unsubscribe'];
    $incl_forums = (bool) $args['include_forums'];

    // === 1) Intentar TEMAS suscritos
   // === Obtener IDs de TEMAS suscritos (robusto a distintas versiones)
$topic_ids = [];

// 1) bbPress moderno (nombres usados en versiones recientes)
if ( function_exists('bbp_get_user_subscribed_topic_ids') ) {
    $topic_ids = (array) bbp_get_user_subscribed_topic_ids( $user_id );

// 2) Otro alias frecuente en algunos builds
} elseif ( function_exists('bbp_get_user_subscriptions_topic_ids') ) {
    $topic_ids = (array) bbp_get_user_subscriptions_topic_ids( $user_id );

// 3) Lista mixta (foros+temas): filtramos solo TEMAS
} elseif ( function_exists('bbp_get_user_subscriptions') ) {
    $raw = (array) bbp_get_user_subscriptions( $user_id );
    foreach ( $raw as $id ) {
        if ( get_post_type( $id ) === bbp_get_topic_post_type() ) {
            $topic_ids[] = (int) $id;
        }
    }

// 4) Fallback duro: user meta directo (bbPress usa _bbp_subscriptions para temas)
} else {
    $meta = get_user_meta( $user_id, '_bbp_subscriptions', true );
    // Puede venir como array o como CSV
    if ( is_array( $meta ) ) {
        $topic_ids = $meta;
    } elseif ( is_string( $meta ) && strlen( $meta ) ) {
        $topic_ids = array_map( 'intval', array_filter( array_map( 'trim', explode( ',', $meta ) ) ) );
    }
}

$topic_ids = array_values( array_filter( array_map( 'absint', $topic_ids ) ) );

    // === Si hay TEMAS, mostramos el preview de TEMAS
    if ( ! empty( $topic_ids ) ) {
      $ids_for_query = ($orderby === 'post__in') ? array_slice( $topic_ids, 0, $max ) : $topic_ids;

      $q = new WP_Query( [
        'post_type'           => bbp_get_topic_post_type(),
        'post__in'            => $ids_for_query,
        'posts_per_page'      => $max,
        'no_found_rows'       => true,
        'ignore_sticky_posts' => true,
        'orderby'             => ($orderby === 'post__in') ? 'post__in' : $orderby,
        'order'               => 'DESC',
      ] );

      ob_start();
      echo '<div class="wm-subs-preview mb-4">
      <div class="fav-loop-title">
    <h2 class="my-area-title">Tus subscripciones</h2>
      <a  href="'. $cta_url .'" aria-label="'. esc_attr__('Ver todas mis subscripciones', 'tu-tema') .'">
    <button class="btn-plus">
      <i class="bi bi-plus"></i> 
    </button>
    </a>
    </div>
       <ul class="fav-loop">
    ';

      while ( $q->have_posts() ) { $q->the_post();
        $topic_id   = get_the_ID();
        $forum_id   = bbp_get_topic_forum_id( $topic_id );
        $replies    = (int) bbp_get_topic_reply_count( $topic_id );
        $likes      = (int) get_post_meta( $topic_id, 'wm_fav_count', true );

        $last_reply_id   = bbp_get_topic_last_reply_id( $topic_id );
        $last_post_id    = $last_reply_id ? $last_reply_id : $topic_id;
        $last_link       = $last_reply_id ? bbp_get_reply_url( $last_reply_id ) : get_permalink( $topic_id );
        $last_author_id  = (int) get_post_field( 'post_author', $last_post_id );
        $last_author     = $last_author_id ? get_the_author_meta( 'display_name', $last_author_id ) : '';
        $last_author_url = $last_author_id ? bbp_get_user_profile_url( $last_author_id ) : '#';
        $last_ts         = get_post_time( 'U', true, $last_post_id );
        $last_when       = human_time_diff( $last_ts, current_time('timestamp') );

        $subs_toggle_html = '';
        if ( $show_un && function_exists('bbp_get_user_subscribe_link') ) {
          $subs_toggle_html = bbp_get_user_subscribe_link([
            'topic_id'    => $topic_id,
            'user_id'     => $user_id,
            'before'      => '',
            'after'       => '',
            'subscribe'   => '<i class="bi bi-bell"></i> '. esc_html__('','tu-tema'),
            'unsubscribe' => '<i class="bi bi-bell-slash"></i> '. esc_html__('','tu-tema'),
            'link_class'  => 'btn btn-subscribe',
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
              <div class="wm-toggle"><?php echo $subs_toggle_html; ?></div>
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
      }
      wp_reset_postdata();
      echo '</ul>';
      if ( count($topic_ids) > $max && ! empty($cta_url) ) {
        echo '<div style="text-align:right;width:100%">'
           . '<a class="btn-fav btn" href="'. $cta_url .'">'. esc_html__('Ver todos', 'tu-tema') .'</a></div>';
      }

      echo '</div>';
      return ob_get_clean();
    }

    // === 2) Si NO hay TEMAS y se permiten FOROS → mostrar FOROS suscritos
    if ( $incl_forums ) {
      $forum_ids = [];
      if ( function_exists('bbp_get_user_subscriptions') ) {
        $raw = (array) bbp_get_user_subscriptions( $user_id );
        foreach ( $raw as $id ) {
          if ( get_post_type( $id ) === bbp_get_forum_post_type() ) $forum_ids[] = (int) $id;
        }
      }
      $forum_ids = array_values( array_filter( array_map('absint', $forum_ids ) ) );

      if ( ! empty( $forum_ids ) ) {
        $forum_ids = array_slice( $forum_ids, 0, $max );

        ob_start();
        echo '<div class="wm-subs-preview">';
        echo '<div class="notice notice-info" style="margin-bottom:10px;">'. esc_html__('Mostrando foros suscritos (no tienes temas suscritos).', 'tu-tema') .'</div>';

        foreach ( $forum_ids as $fid ) {
          $title = get_the_title( $fid );
          $link  = bbp_get_forum_permalink( $fid );

          // Toggle nativo de foro (si disponible)
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
          <article class="wm-subs-forum-item" style="border:1px solid #e5e7eb;border-radius:12px;padding:12px;margin-bottom:10px;background:#fff;">
            <header style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
              <h4 style="margin:0;font-size:16px;line-height:1.35;">
                <a href="<?php echo esc_url( $link ); ?>" style="text-decoration:none;color:#111827;"><?php echo esc_html( $title ); ?></a>
              </h4>
              <?php if ( $show_un ) : ?>
                <div class="wm-subs-toggle"><?php echo $forum_toggle; ?></div>
              <?php endif; ?>
            </header>
            <div class="wm-subs-meta" style="color:#6b7280;font-size:13px;margin-top:6px;">
              <?php esc_html_e('Foro suscrito', 'tu-tema'); ?>
            </div>
          </article>
          <?php
        }

        if ( count($forum_ids) > $max && ! empty($cta_url) ) {
          echo '<div class="wm-subs-cta" style="text-align:right;margin-top:8px;">'
             . '<a class="button" href="'. $cta_url .'">'. esc_html__('Ver todos', 'tu-tema') .'</a></div>';
        }

        echo '</div>';
        return ob_get_clean();
      }
    }

    // === 3) Sin temas ni foros
    return '<div class="wm-subs-preview-empty">'. esc_html( $args['empty_text'] ) .'</div>';
  }
}

// Shortcode
add_shortcode('wm_user_subscriptions_preview', function( $atts = [] ){
  $atts = shortcode_atts([
    'max'               => '6',
    'cta_url'           => site_url('/mysubscriptions/'),
    'orderby'           => 'post__in',
    'show_unsubscribe'  => '1',
    'empty_text'        => 'Aún no sigues ningún tema.',
    'include_forums'    => '1',
  ], $atts, 'wm_user_subscriptions_preview');

  $atts['max'] = (int) $atts['max'];
  $atts['show_unsubscribe'] = ($atts['show_unsubscribe'] === '1');
  $atts['include_forums']   = ($atts['include_forums'] === '1');

  return wm_render_user_subscriptions_preview( $atts );
});
