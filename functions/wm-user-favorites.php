<?php
/**
 * User Favorites list for bbPress — theme-side, independent of loop-topics.php
 * Shortcode: [wm_user_favorites per_page="10" orderby="date|title|modified|post__in" order="DESC|ASC" show_unfavorite="1"]
 * Template tag: echo wm_render_user_favorites(['per_page'=>8,'orderby'=>'post__in']);
 */

if ( ! defined('ABSPATH') ) exit;

if ( ! function_exists('wm_render_user_favorites') ) {

    function wm_render_user_favorites( $atts = [] ) {

        // Reqs mínimos
        if ( ! is_user_logged_in() ) {
            $login = site_url('/acceso/');
            $login = add_query_arg('redirect_to', urlencode( get_permalink() ?: home_url('/') ), $login);
            return '<div class="notice notice-error">Necesitas iniciar sesión.</div><p><a class="button" href="'.esc_url($login).'">Iniciar sesión</a></p>';
        }
        if ( ! function_exists('bbp_is_favorites_active') || ! bbp_is_favorites_active() ) {
            return '<div class="notice notice-error">Los favoritos del foro no están activos.</div>';
        }

        // Atributos
        $atts = shortcode_atts([
            'per_page'        => 10,
            'orderby'         => 'date',   // date|title|modified|post__in
            'order'           => 'DESC',   // DESC|ASC
            'show_unfavorite' => '1',      // 1 = mostrar botón nativo para quitar
            'query_var'       => 'pg_fav', // query var para paginación local
        ], $atts, 'wm_user_favorites');

        $per_page = max(1, (int) $atts['per_page']);
        $orderby  = in_array($atts['orderby'], ['date','title','modified','post__in'], true) ? $atts['orderby'] : 'date';
        $order    = (strtoupper($atts['order']) === 'ASC') ? 'ASC' : 'DESC';
        $show_un  = ($atts['show_unfavorite'] === '1');
        $qv       = preg_replace('/[^a-z0-9_\-]/i', '', $atts['query_var']); // sanea el nombre del query var

        $user_id = get_current_user_id();
        $paged   = isset($_GET[$qv]) ? max(1, (int) $_GET[$qv]) : 1;

        // IDs favoritos del usuario (API moderna si existe)
        if ( function_exists('bbp_get_user_favorites_topic_ids') ) {
            $fav_ids = (array) bbp_get_user_favorites_topic_ids( $user_id );
        } else {
            $fav_ids = (array) ( function_exists('bbp_get_user_favorites') ? bbp_get_user_favorites( $user_id ) : [] );
        }
        $fav_ids = array_values( array_filter( array_map('absint', $fav_ids) ) );

        if ( empty( $fav_ids ) ) {
            return '<div class="notice notice-info">Aún no tienes publicaciones en tus favoritos.</div>';
        }

        // Query independiente (no afecta loop de bbPress)
        $args = [
            'post_type'           => function_exists('bbp_get_topic_post_type') ? bbp_get_topic_post_type() : 'topic',
            'post__in'            => $fav_ids,
            'posts_per_page'      => $per_page,
            'paged'               => $paged,
            'no_found_rows'       => false,
            'ignore_sticky_posts' => true,
        ];
        if ( $orderby === 'post__in' ) {
            $args['orderby'] = 'post__in';
        } else {
            $args['orderby'] = $orderby;
            $args['order']   = $order;
        }

        $q = new WP_Query( $args );

        ob_start();

        // Contenedor (clases simples para que puedas estilizar)
        echo '<div class="wm-fav-list">';

        if ( $q->have_posts() ) :
            while ( $q->have_posts() ) : $q->the_post();
                $topic_id   = get_the_ID();
                $forum_id   = function_exists('bbp_get_topic_forum_id') ? bbp_get_topic_forum_id($topic_id) : 0;
                $replies    = function_exists('bbp_get_topic_reply_count') ? (int) bbp_get_topic_reply_count($topic_id) : 0;
                $likes      = (int) get_post_meta( $topic_id, 'wm_fav_count', true );

                // Freshness (última actividad)
                $last_reply_id   = function_exists('bbp_get_topic_last_reply_id') ? bbp_get_topic_last_reply_id( $topic_id ) : 0;
                $last_post_id    = $last_reply_id ? $last_reply_id : $topic_id;
                $last_link       = $last_reply_id && function_exists('bbp_get_reply_url') ? bbp_get_reply_url( $last_reply_id ) : get_permalink( $topic_id );
                $last_author_id  = (int) get_post_field( 'post_author', $last_post_id );
                $last_author     = $last_author_id ? get_the_author_meta( 'display_name', $last_author_id ) : '';
                $last_author_url = $last_author_id && function_exists('bbp_get_user_profile_url') ? bbp_get_user_profile_url( $last_author_id ) : '#';
                $last_ts         = get_post_time( 'U', true, $last_post_id );
                $last_when       = human_time_diff( $last_ts, current_time('timestamp') );

                // Botón nativo (opcional)
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
                <article class="wm-fav-card" style="border:1px solid #e5e7eb;border-radius:12px;padding:14px;margin-bottom:12px;background:#fff;">
                    <header style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                        <h3 style="margin:0;font-size:18px;line-height:1.3;">
                            <a href="<?php echo esc_url( get_permalink($topic_id) ); ?>" style="text-decoration:none;color:#111827;"><?php echo esc_html( get_the_title($topic_id) ); ?></a>
                        </h3>
                        <?php if ( $show_un ) : ?>
                            <div class="wm-fav-toggle"><?php echo $fav_toggle_html; ?></div>
                        <?php endif; ?>
                    </header>

                    <div class="wm-fav-meta" style="display:flex;flex-wrap:wrap;gap:14px;color:#6b7280;font-size:14px;margin-top:8px;">
                        <?php if ( $forum_id ) : ?>
                            <span>en <a href="<?php echo esc_url( bbp_get_forum_permalink($forum_id) ); ?>" style="color:inherit;text-decoration:none;"><?php echo esc_html( bbp_get_forum_title($forum_id) ); ?></a></span>
                        <?php endif; ?>
                        <span><i class="bi bi-chat"></i> <?php echo number_format_i18n( $replies ); ?> respuestas</span>
                        <span><i class="bi bi-heart-fill" style="color:#ef4444;"></i> <?php echo number_format_i18n( max(0,$likes) ); ?> me gusta</span>
                        <?php if ( $last_author ) : ?>
                            <span>Última respuesta de <a href="<?php echo esc_url( $last_author_url ); ?>" style="color:inherit;text-decoration:none;"><?php echo esc_html($last_author); ?></a> <a href="<?php echo esc_url( $last_link ); ?>">hace <?php echo esc_html( $last_when ); ?></a></span>
                        <?php endif; ?>
                    </div>
                </article>
                <?php
            endwhile;
            wp_reset_postdata();
        else :
            echo '<div class="notice notice-info">No hay resultados.</div>';
        endif;

        echo '</div>';

        // Paginación propia con query var local (no interfiere con otras)
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
                echo '<nav class="wm-fav-pagination" aria-label="Paginación favoritos">'. $links .'</nav>';
            }
        }

        return ob_get_clean();
    }
}

// Shortcode
add_shortcode('wm_user_favorites', function( $atts = [] ){
    return wm_render_user_favorites( $atts );
});
