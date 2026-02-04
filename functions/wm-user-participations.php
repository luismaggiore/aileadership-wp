<?php
/**
 * Últimas participaciones del usuario en debates (bbPress)
 * - Función: echo wm_render_user_recent_participations([ 'user_id'=>0, 'limit'=>3, 'unique_by_topic'=>true ]);
 * - Shortcode: [wm_user_recent_participations limit="3" unique_by_topic="1"]
 */

if ( ! defined('ABSPATH') ) exit;

if ( ! function_exists('wm_render_user_recent_participations') ) {

  /**
   * Renderiza las últimas participaciones (respuestas) de un usuario.
   *
   * @param array $args {
   *   @type int  $user_id         ID del usuario (0 = actual)
   *   @type int  $limit           Máximo de items a mostrar (def 3)
   *   @type bool $unique_by_topic Si true, no repite el mismo debate (def true)
   * }
   * @return string HTML
   */
  function wm_render_user_recent_participations( $args = [] ) {

    if ( ! function_exists('bbp_get_reply_post_type') ) {
      return '<div class="notice notice-error">bbPress no está activo.</div>';
    }

    $args = wp_parse_args( $args, [
      'user_id'         => 0,
      'limit'           => 3,
      'unique_by_topic' => true,
    ]);

    $limit     = max(1, (int) $args['limit']);
    $user_id   = (int) $args['user_id'];
    if ( $user_id <= 0 ) $user_id = get_current_user_id();
    if ( $user_id <= 0 ) {
      $login = site_url('/acceso/');
      $login = add_query_arg('redirect_to', urlencode(get_permalink() ?: home_url('/')), $login);
      return '<div class="notice notice-error">Necesitas iniciar sesión.</div><p><a class="button" href="'.esc_url($login).'">Iniciar sesión</a></p>';
    }

    // Obtenemos replies recientes del usuario
    // Si pedimos unique_by_topic, traemos más para poder deduplicar.
    $fetch = $args['unique_by_topic'] ? max($limit * 5, 20) : $limit;

    $q = new WP_Query([
      'post_type'           => bbp_get_reply_post_type(),
      'post_status'         => 'publish',
      'author'              => $user_id,
      'posts_per_page'      => $fetch,
      'no_found_rows'       => true,
      'orderby'             => 'date',
      'order'               => 'DESC',
      'ignore_sticky_posts' => true,
      'suppress_filters'    => true,
      'fields'              => 'ids',
    ]);

    if ( ! $q->have_posts() ) {
      return '<div class="notice notice-info">Aún no has participado en discusiones.</div>';
    }

    $items = [];
    $seen_topics = [];

    foreach ( $q->posts as $reply_id ) {
      $topic_id = function_exists('bbp_get_reply_topic_id') ? bbp_get_reply_topic_id($reply_id) : 0;
      if ( ! $topic_id ) continue;

      if ( $args['unique_by_topic'] && isset($seen_topics[$topic_id]) ) {
        continue; // saltar respuestas posteriores del mismo topic
      }

      $seen_topics[$topic_id] = true;

      // Datos del topic
      $topic_title = get_the_title($topic_id);
      $topic_link  = function_exists('bbp_get_topic_permalink') ? bbp_get_topic_permalink($topic_id) : get_permalink($topic_id);
      $reply_raw   = get_post_field('post_content', $reply_id);
      $reply_text  = trim( wp_strip_all_tags( strip_shortcodes( $reply_raw ) ) );
      $reply_excerpt = wp_trim_words( $reply_text, 10, '…' );
      $reply_excerpt = '“' . esc_html( $reply_excerpt ) . '”';

      $replies_cnt = function_exists('bbp_get_topic_reply_count') ? (int) bbp_get_topic_reply_count($topic_id) : 0;

      $items[] = [
       'topic_id'    => $topic_id,
        'topic_link'  => $topic_link,
        'topic_title' => $topic_title,
        'reply_text'  => $reply_text, // <-- crudo
        'replies'     => $replies_cnt,
      ];

      if ( count($items) >= $limit ) break;
    }

 if ( empty($items) ) {
      return '<div class="notice notice-info">Aún no has participado en discusiones.</div>';
    }

    // === NUEVO: calcular clase de columna según cantidad de items ===
     $count = count($items);
    if ( $count === 1 ) {
        $col_class   = 'col col-lg-12';
        $excerpt_len = 30;
    } elseif ( $count === 2 ) {
        $col_class   = 'col-12 col-lg-6';
        $excerpt_len = 20;
    } else {
        $col_class   = 'col-12 col-lg-4';
        $excerpt_len = 10;
    }

    ob_start();
        echo ' <h2 class="my-area-title">Tus últimas participaciones</h2>';

    echo '<div class="wm-user-participations mb-4">';
    echo '<div class="row g-2 ">';

     foreach ( $items as $it ) {
      // Cortamos aquí según $excerpt_len
      $excerpt = wp_trim_words( $it['reply_text'], $excerpt_len, '…' );
      $excerpt = '“' . esc_html( $excerpt ) . '”';
      ?>
      
      <div class=" <?php echo esc_attr($col_class); ?>" style="align-content:start;display:block;position:relative">
            <a class="forum-wp"  href="<?php echo esc_url($it['topic_link']); ?>" style="text-decoration:none;color:#111827;">
        <div class="forum-wp-div">
        <div class="wm-up-excerpt" style="margin-bottom:10px;">
                
                  <p style="margin:0;" class="excerpt-comment">
        <?php echo $excerpt; ?>
                </p>
            
        </div>
        <div class="wm-up-meta" >
      
            <p style="color:#6b7280;margin-bottom:0px;align-self:bottom;font-size:14px">
           En "<?php echo esc_html($it['topic_title']); ?>"</p>
         
        </div>
      </div>
    </a>
    </div>
      <?php
    }
    echo '</div>';
    echo '</div>';

    return ob_get_clean();
  }
}

// Shortcode opcional
add_shortcode('wm_user_recent_participations', function( $atts = [] ){
  $atts = shortcode_atts([
    'user_id'         => '0',
    'limit'           => '3',
    'unique_by_topic' => '1',
  ], $atts, 'wm_user_recent_participations');

  $atts['user_id']         = (int) $atts['user_id'];
  $atts['limit']           = (int) $atts['limit'];
  $atts['unique_by_topic'] = ($atts['unique_by_topic'] === '1');

  return wm_render_user_recent_participations( $atts );
});
