<?php
/**
 * Recomendados para ti (bbPress): sugiere hasta N topics por similitud de tags,
 * usando como señales los favoritos del usuario y sus participaciones (replies).
 *
 * - Función (datos + render básico):
 *     echo wm_render_recommended_topics_for_user([
 *       'user_id' => 0,    // 0 = actual
 *       'limit'   => 3,    // máximo a mostrar
 *       'days'    => 90,   // ventana de frescura para candidatos (0 = sin filtro)
 *       'cache_ttl' => 300 // cache per-user en segundos (0 = sin cache)
 *     ]);
 *
 * - Shortcode:
 *     [wm_recommended_topics limit="3" days="90"]
 */

if ( ! defined('ABSPATH') ) exit;

/** Helper robusto: IDs de topics favoritos del usuario */
if ( ! function_exists('wm__get_user_favorite_topic_ids') ) {
  function wm__get_user_favorite_topic_ids( $user_id ) {
    $ids = [];
    if ( function_exists('bbp_get_user_favorites_topic_ids') ) {
      $ids = (array) bbp_get_user_favorites_topic_ids( $user_id );
    } elseif ( function_exists('bbp_get_user_favorites') ) {
      $ids = (array) bbp_get_user_favorites( $user_id );
    } else {
      // fallback meta crudo (bbPress guarda CSV en _bbp_favorites)
      $meta = get_user_meta( $user_id, '_bbp_favorites', true );
      if ( is_array($meta) ) $ids = $meta;
      elseif ( is_string($meta) && strlen($meta) ) {
        $ids = array_map('intval', array_filter(array_map('trim', explode(',', $meta))));
      }
    }
    return array_values( array_filter( array_map('absint', $ids) ) );
  }
}

/** Helper: IDs de topics en los que el usuario participó (autor de replies) */
if ( ! function_exists('wm__get_user_participated_topic_ids') ) {
  function wm__get_user_participated_topic_ids( $user_id, $scan = 200 ) {
    if ( ! function_exists('bbp_get_reply_post_type') ) return [];
    $q = new WP_Query([
      'post_type'           => bbp_get_reply_post_type(),
      'post_status'         => 'publish',
      'author'              => (int) $user_id,
      'posts_per_page'      => max(20, (int)$scan),
      'no_found_rows'       => true,
      'orderby'             => 'date',
      'order'               => 'DESC',
      'fields'              => 'ids',
      'suppress_filters'    => true,
    ]);
    if ( empty($q->posts) ) return [];
    $topic_ids = [];
    foreach ( $q->posts as $reply_id ) {
      $tid = function_exists('bbp_get_reply_topic_id') ? bbp_get_reply_topic_id($reply_id) : 0;
      if ( $tid ) $topic_ids[] = (int) $tid;
    }
    return array_values( array_unique( array_filter( array_map('absint', $topic_ids) ) ) );
  }
}

/** Core: calcula recomendaciones (devuelve array de items) */
if ( ! function_exists('wm_get_recommended_topics_for_user') ) {
  /**
   * @param array $args {
   *   @type int $user_id   Usuario (0 = actual)
   *   @type int $limit     Máximo de resultados (def 3)
   *   @type int $days      Ventana de frescura de candidatos (0 = sin filtro)
   *   @type int $cache_ttl Cache per-user en segundos (def 300)
   * }
   * @return array[] Cada item: ['topic_id','title','permalink','score','tags'=>array of WP_Term, 'replies','date_ts']
   */
  function wm_get_recommended_topics_for_user( $args = [] ) {

    if ( ! function_exists('bbp_get_topic_post_type') ) return [];

    $args = wp_parse_args($args, [
      'user_id'   => 0,
      'limit'     => 3,
      'days'      => 90,
      'cache_ttl' => 300,
    ]);

    $limit     = max(1, (int) $args['limit']);
    $user_id   = (int) $args['user_id'];
    if ( $user_id <= 0 ) $user_id = get_current_user_id();
    if ( $user_id <= 0 ) return [];

    $taxonomy = function_exists('bbp_get_topic_tag_tax_id') ? bbp_get_topic_tag_tax_id() : 'topic-tag';

    // Cache per-user
    $ckey = 'wm_rec_topics_' . $user_id . '_' . md5( serialize([$limit, (int)$args['days']]) );
    if ( $args['cache_ttl'] > 0 ) {
      $cached = get_transient( $ckey );
      if ( $cached !== false ) return $cached;
    }

    // 1) Señales: favoritos + participaciones
    $fav_ids   = wm__get_user_favorite_topic_ids( $user_id );
    $part_ids  = wm__get_user_participated_topic_ids( $user_id, 200 );

    // Excluir: donde ya dio like o participó
    $exclude_ids = array_values( array_unique( array_merge( $fav_ids, $part_ids ) ) );

    // Tag weights: sumamos 2 por cada favorito, 1 por cada participación
    $tag_weights = []; // term_id => peso
    $seed_topic_ids = array_unique( array_merge( $fav_ids, $part_ids ) );
    if ( empty($seed_topic_ids) ) {
      if ( $args['cache_ttl'] > 0 ) set_transient($ckey, [], $args['cache_ttl']);
      return [];
    }

    foreach ( $fav_ids as $tid ) {
      $terms = wp_get_object_terms( $tid, $taxonomy, ['fields'=>'ids'] );
      if ( is_wp_error($terms) ) continue;
      foreach ( $terms as $term_id ) {
        $tag_weights[$term_id] = ($tag_weights[$term_id] ?? 0) + 2;
      }
    }
    foreach ( $part_ids as $tid ) {
      $terms = wp_get_object_terms( $tid, $taxonomy, ['fields'=>'ids'] );
      if ( is_wp_error($terms) ) continue;
      foreach ( $terms as $term_id ) {
        $tag_weights[$term_id] = ($tag_weights[$term_id] ?? 0) + 1;
      }
    }

    if ( empty($tag_weights) ) {
      if ( $args['cache_ttl'] > 0 ) set_transient($ckey, [], $args['cache_ttl']);
      return [];
    }

    // 2) Recall amplio: buscar topics con alguno de esos tags (OR)
    $tag_ids = array_keys($tag_weights);

    $date_query = [];
    if ( (int)$args['days'] > 0 ) {
      $cut = current_time('timestamp') - ( (int)$args['days'] * DAY_IN_SECONDS );
      $date_query[] = [
        'after'     => gmdate('Y-m-d H:i:s', $cut),
        'inclusive' => true,
        'column'    => 'post_date_gmt',
      ];
    }

    $candidates = new WP_Query([
      'post_type'           => bbp_get_topic_post_type(),
      'post_status'         => 'publish',
      'posts_per_page'      => 40, // traemos un pool razonable
      'fields'              => 'ids',
      'no_found_rows'       => true,
      'orderby'             => 'date',
      'order'               => 'DESC',
      'ignore_sticky_posts' => true,
      'post__not_in'        => $exclude_ids,
      'tax_query'           => [[
        'taxonomy' => $taxonomy,
        'field'    => 'term_id',
        'terms'    => $tag_ids,
        'operator' => 'IN',
      ]],
      'date_query'          => $date_query,
      'suppress_filters'    => true,
    ]);

    if ( empty($candidates->posts) ) {
      if ( $args['cache_ttl'] > 0 ) set_transient($ckey, [], $args['cache_ttl']);
      return [];
    }

    // 3) Scoring simple en PHP: suma de pesos de tags coincidentes + pequeño bonus por frescura
    $scored = []; // topic_id => score
    foreach ( $candidates->posts as $tid ) {
      $terms = wp_get_object_terms( $tid, $taxonomy, ['fields'=>'ids'] );
      if ( is_wp_error($terms) || empty($terms) ) continue;

      $score = 0;
      foreach ( $terms as $term_id ) {
        if ( isset($tag_weights[$term_id]) ) {
          $score += $tag_weights[$term_id];
        }
      }

      // bonus frescura (suave): últimos 7 días +1, últimos 2 días +2
      $ts = get_post_time('U', true, $tid);
      $age = current_time('timestamp') - $ts;
      if ( $age <= 2 * DAY_IN_SECONDS )      $score += 2;
      elseif ( $age <= 7 * DAY_IN_SECONDS )  $score += 1;

      if ( $score > 0 ) $scored[$tid] = $score;
    }

    if ( empty($scored) ) {
      if ( $args['cache_ttl'] > 0 ) set_transient($ckey, [], $args['cache_ttl']);
      return [];
    }

    // Ordenar por score desc, y en empate por fecha desc
    uasort($scored, function($a, $b){ return ($a === $b) ? 0 : ( $a > $b ? -1 : 1 ); });

    // 4) Armar salida enriquecida (máx. limit)
    $out = [];
    foreach ( array_keys($scored) as $tid ) {
      $title = get_the_title($tid);
      if ( ! $title ) continue;
      $terms = wp_get_object_terms( $tid, $taxonomy );
      $out[] = [
        'topic_id'  => $tid,
        'title'     => $title,
        'permalink' => get_permalink($tid),
        'score'     => (int) $scored[$tid],
        'tags'      => is_wp_error($terms) ? [] : $terms,
        'replies'   => function_exists('bbp_get_topic_reply_count') ? (int) bbp_get_topic_reply_count($tid) : 0,
        'date_ts'   => get_post_time('U', true, $tid),
      ];
      if ( count($out) >= $limit ) break;
    }

    if ( $args['cache_ttl'] > 0 ) set_transient($ckey, $out, $args['cache_ttl']);
    return $out;
  }
}

/** Render básico (cards compactas) */
if ( ! function_exists('wm_render_recommended_topics_for_user') ) {
  function wm_render_recommended_topics_for_user( $args = [] ) {
    $items = wm_get_recommended_topics_for_user( $args );
    if ( empty($items) ) return '<div class="notice notice-info">No hay recomendaciones por ahora.</div>';

    ob_start();
    echo '<div class="wm-recommended-topics ">
          <h2 class="my-area-title">Recomendado para ti</h2>
          <ul class="fav-loop">
    ';
    foreach ( $items as $it ) {
      $tags_html = '';
      if ( ! empty($it['tags']) ) {
        $chips = [];
        foreach ( $it['tags'] as $t ) {
          $chips[] = '<a href="'. esc_url( get_term_link($t) ) .'" class="tag-card " >'. esc_html($t->name) .'</a>';
        }
        $tags_html = '<div class="tag-container">'. implode('', $chips) .'</div>';
      }
      ?>
      <li>
      <article>
        <header style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
          <h3>
            <a href="<?php echo esc_url($it['permalink']); ?>" >
              <?php echo esc_html( $it['title'] ); ?>
            </a>
          </h3>
          <span style="color:#6b7280;font-size:13px;">
            <i class="bi bi-chat"></i> <?php echo number_format_i18n( (int)$it['replies'] ); ?>
          </span>
        </header>
        <?php echo $tags_html; ?>
      </article>
      </li>
      <?php
    }
    echo '
    </ul>
    </div>';
    return ob_get_clean();
  }
}

/** Shortcode (opcional) */
add_shortcode('wm_recommended_topics', function( $atts = [] ){
  $atts = shortcode_atts([
    'limit'     => '3',
    'days'      => '90',
    'cache_ttl' => '300',
    'user_id'   => '0',
  ], $atts, 'wm_recommended_topics');

  $atts['limit']     = (int) $atts['limit'];
  $atts['days']      = (int) $atts['days'];
  $atts['cache_ttl'] = (int) $atts['cache_ttl'];
  $atts['user_id']   = (int) $atts['user_id'];

  return wm_render_recommended_topics_for_user( $atts );
});
