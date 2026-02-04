<?php
/**
 * WM Trending Topic Tags (bbPress)
 * Calcula las etiquetas más usadas en temas recientes.
 *
 * Uso (en plantillas):
 *   $tags = wm_get_trending_topic_tags([
 *     'days'       => 14,   // ventana temporal
 *     'limit'      => 10,   // máximo a devolver
 *     'forum_id'   => 0,    // opcional: filtrar por un foro
 *     'scan_limit' => 300,  // cuántos temas recientes escanear como máx.
 *     'taxonomy'   => '',   // por defecto toma la de bbPress (topic-tag)
 *     'min_count'  => 1,    // umbral mínimo de repeticiones
 *     'cache_ttl'  => 300,  // segundos de caché (5 min)
 *   ]);
 *
 * Render opcional (chips):
 *   echo wm_render_trending_topic_tags([ 'days'=>14, 'limit'=>8 ]);
 */

if ( ! defined('ABSPATH') ) exit;

if ( ! function_exists('wm_get_trending_topic_tags') ) {

  function wm_get_trending_topic_tags( $args = [] ) {

    if ( ! function_exists('bbp_get_topic_post_type') ) {
      return []; // bbPress no activo
    }

    $defaults = [
      'days'       => 14,
      'limit'      => 10,
      'forum_id'   => 0,
      'scan_limit' => 300,
      'taxonomy'   => '',
      'min_count'  => 1,
      'cache_ttl'  => 60, // 1 min
    ];
    $args = wp_parse_args( $args, $defaults );

    $days       = max(1, (int) $args['days']);
    $limit      = max(1, (int) $args['limit']);
    $forum_id   = absint( $args['forum_id'] );
    $scan_limit = max(10, (int) $args['scan_limit']);
    $taxonomy   = $args['taxonomy'] ?: ( function_exists('bbp_get_topic_tag_tax_id') ? bbp_get_topic_tag_tax_id() : 'topic-tag' );
    $min_count  = max(1, (int) $args['min_count']);
    $cache_ttl  = max(0, (int) $args['cache_ttl']);

    // Cache clave (no incluye usuario; TTL corto)
    $ckey = 'wm_trending_tags_' . md5( serialize([ $days, $limit, $forum_id, $scan_limit, $taxonomy, $min_count ]) );
    if ( $cache_ttl > 0 ) {
      $cached = get_transient( $ckey );
      if ( $cached !== false ) return $cached;
    }

    // Fecha de corte
    $cut = current_time('timestamp') - ( $days * DAY_IN_SECONDS );
    $cut_mysql = gmdate( 'Y-m-d H:i:s', $cut );

    // Query: temas recientes
    $qargs = [
      'post_type'           => bbp_get_topic_post_type(),
      'post_status'         => 'publish',
      'posts_per_page'      => $scan_limit,
      'orderby'             => 'date',
      'order'               => 'DESC',
      'date_query'          => [
        [
          'after'     => $cut_mysql,
          'inclusive' => true,
          'column'    => 'post_date_gmt',
        ]
      ],
      'fields'              => 'ids',
      'no_found_rows'       => true,
      'ignore_sticky_posts' => true,
      'suppress_filters'    => true,
    ];

    // (Opcional) limitar por foro
    if ( $forum_id ) {
      $qargs['post_parent'] = $forum_id;
    }

    $ids = get_posts( $qargs );
    if ( empty( $ids ) ) {
      return []; // no hay temas en la ventana
    }

    // Contabilizar etiquetas
    $counts = []; // term_id => count
    foreach ( $ids as $tid ) {
      $terms = wp_get_object_terms( $tid, $taxonomy, [ 'fields' => 'ids' ] );
      if ( is_wp_error($terms) || empty($terms) ) continue;
      foreach ( $terms as $term_id ) {
        $term_id = (int) $term_id;
        if ( $term_id <= 0 ) continue;
        if ( ! isset($counts[$term_id]) ) $counts[$term_id] = 0;
        $counts[$term_id]++;
      }
    }

    if ( empty( $counts ) ) {
      // Fallback: si no hubo tags en la ventana, devolver globales por count
      $popular = get_terms( [
        'taxonomy'   => $taxonomy,
        'orderby'    => 'count',
        'order'      => 'DESC',
        'number'     => $limit,
        'hide_empty' => true,
      ] );
      $out = [];
      if ( ! is_wp_error($popular) ) {
        foreach ( $popular as $t ) {
          $out[] = [
            'term_id' => (int) $t->term_id,
            'name'    => $t->name,
            'slug'    => $t->slug,
            'count'   => (int) $t->count, // global
            'link'    => get_term_link( $t ),
          ];
          if ( count($out) >= $limit ) break;
        }
      }
      if ( $cache_ttl > 0 ) set_transient( $ckey, $out, $cache_ttl );
      return $out;
    }

    // Filtrar por min_count y ordenar por repeticiones recientes
    foreach ( $counts as $k => $v ) {
      if ( $v < $min_count ) unset( $counts[$k] );
    }
    if ( empty($counts) ) return [];

    arsort( $counts ); // desc por #apariciones en la ventana
    $top_ids = array_slice( array_keys($counts), 0, $limit, true );

    // Armar salida enriquecida
    $out = [];
    foreach ( $top_ids as $term_id ) {
      $term = get_term( $term_id, $taxonomy );
      if ( ! $term || is_wp_error($term) ) continue;
      $out[] = [
        'term_id' => (int) $term->term_id,
        'name'    => $term->name,
        'slug'    => $term->slug,
        'count'   => (int) $counts[$term_id], // ocurrencias en ventana reciente
        'link'    => get_term_link( $term ),
      ];
    }

    if ( $cache_ttl > 0 ) set_transient( $ckey, $out, $cache_ttl );
    return $out;
  }
}

if ( ! function_exists('wm_render_trending_topic_tags') ) {
  /**
   * Render básico (chips) de los tags en tendencia.
   */
  function wm_render_trending_topic_tags( $args = [] ) {
    $tags = wm_get_trending_topic_tags( $args );
    if ( empty($tags) ) return '';

    ob_start();
    echo ' <h2 class="my-area-title">Lo más discutido</h2>';
    echo '<div class="tag-container mb-4">';
    foreach ( $tags as $t ) {
      $label = esc_html( $t['name'] );
      $cnt   = number_format_i18n( (int) $t['count'] );
      $url   = esc_url( $t['link'] );
      echo '<a href="'. $url .'" class="tag-card">';
      echo '<span>#'. $label .'</span><span style="opacity:.7;margin-left:5px">'. $cnt .'</span>';
      echo '</a>';
    }
    echo '</div>';
    return ob_get_clean();
  }
}
