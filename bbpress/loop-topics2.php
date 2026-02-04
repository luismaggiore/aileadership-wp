<?php
/**
 * Loop: Topics (custom layout + central role badges + freshness links)
 * Importante: no rehacer el query en Favoritos/Suscripciones; dejamos que bbPress lo provea.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$ellipsis_url = get_stylesheet_directory_uri() . '/assets/img/badges/ellipsis-avatar.png'; // ícono "..." para la 5ª mini

// Helpers mínimos
if ( ! function_exists('theme_forum_avatar_url') ) {
    function theme_forum_avatar_url( $user_id, $size = 96 ) {
        $meta = get_user_meta( $user_id, 'profile_picture', true );
        if ( $meta ) return esc_url( $meta );
        return esc_url( get_avatar_url( $user_id, array( 'size' => $size ) ) );
    }
}
if ( ! function_exists('theme_forum_hace') ) {
    function theme_forum_hace( $timestamp ) {
        $diff = human_time_diff( $timestamp, current_time('timestamp') );
        return sprintf( _x( 'hace %s', 'relative time', 'tu-tema' ), $diff );
    }
}

do_action( 'bbp_template_before_topics_loop' );

/**
 * ¿bbPress ya preparó el loop de topics?
 * - En Favoritos/Suscripciones (y otros contextos), bbPress arma bbpress()->topic_query
 *   con los IDs correctos. NO debemos llamarar bbp_has_topics() de nuevo.
 */
$prepared_query = ( function_exists('bbpress') && isset( bbpress()->topic_query ) && bbpress()->topic_query instanceof WP_Query );

// Si NO está preparado (p.ej. en listado normal), lo preparamos con los defaults de bbPress.
if ( ! $prepared_query ) {
    bbp_has_topics();
}

if ( bbp_topics() ) : ?>

    <h2 class="my-area-title">Tus últimos temas</h2>

    <?php while ( bbp_topics() ) : bbp_the_topic();

        $topic_id   = bbp_get_topic_id();
        $author_id  = bbp_get_topic_author_id( $topic_id );
        $author_img = theme_forum_avatar_url( $author_id, 96 );
        $title      = bbp_get_topic_title( $topic_id );

        // Descripción recortada
        $raw     = get_post_field( 'post_content', $topic_id );
        $plain   = wp_strip_all_tags( strip_shortcodes( $raw ) );
        $excerpt = wp_trim_words( $plain, 12, '...' );

        // Última respuesta (nombre → perfil, tiempo → última respuesta)
        $last_reply_id   = function_exists('bbp_get_topic_last_reply_id') ? bbp_get_topic_last_reply_id( $topic_id ) : 0;
        $last_post_id    = $last_reply_id ? $last_reply_id : $topic_id;
        $last_reply_link = $last_reply_id ? bbp_get_reply_url( $last_reply_id ) : bbp_get_topic_permalink( $topic_id );

        $last_author_id   = $last_reply_id ? (int) get_post_field( 'post_author', $last_reply_id ) : $author_id;
        if ( ! $last_author_id ) $last_author_id = $author_id;
        $last_author_name = get_the_author_meta( 'display_name', $last_author_id );
        $last_author_url  = bbp_get_user_profile_url( $last_author_id );

        $last_ts       = get_post_time( 'U', true, $last_post_id );
        $last_time_rel = theme_forum_hace( $last_ts );

        // Tags (máx. 5)
        $tags = get_the_terms( $topic_id, bbp_get_topic_tag_tax_id() );
        if ( is_wp_error( $tags ) || empty( $tags ) ) $tags = array();
        $tags = array_slice( $tags, 0, 5 );

        // Respuestas
        $replies = (int) bbp_get_topic_reply_count( $topic_id );

        // Mini-avatars de autores de respuestas (únicos, máx. 4; si >4, mostrará "…")
        $mini_authors = array();
        $reply_posts  = get_posts( array(
            'post_type'        => bbp_get_reply_post_type(),
            'post_parent'      => $topic_id,
            'posts_per_page'   => 50,
            'orderby'          => 'date',
            'order'            => 'DESC',
            'fields'           => 'ids',
            'no_found_rows'    => true,
            'suppress_filters' => true,
        ) );
        if ( $reply_posts ) {
            foreach ( $reply_posts as $rid ) {
                $uid = (int) get_post_field( 'post_author', $rid );
                if ( $uid && ! in_array( $uid, $mini_authors, true ) ) {
                    $mini_authors[] = $uid;
                    if ( count( $mini_authors ) >= 5 ) break;
                }
            }
        }
        $has_more_than_4 = ( count( $mini_authors ) > 4 );
        $mini_authors    = array_slice( $mini_authors, 0, 4 );

        // Insignia por rol centralizada (usa tu helper)
        $badge_html = function_exists('wm_forum_badge_img') ? wm_forum_badge_img( $author_id ) : '';
        ?>
        <div class="row forum-item" style="position:relative;margin:0 0 2px 0;">
            <!-- Avatar autor + insignia -->
            <div class="col-md-auto" style="position:relative;height:auto;padding:0;margin-right:20px;margin-bottom:20px">
                <img loading="lazy" class="forum-image" src="<?php echo esc_url( $author_img ); ?>"
                     alt="" style="width:88px;height:88px;border-radius:50%;object-fit:cover;border:1px solid #e5e7eb;">
                <?php echo $badge_html; ?>
            </div>

            <!-- Centro -->
            <div class="col-md mb-3" >
                <h3 style="margin:0 0 6px;">
                    <a class="forum-item-link" href="<?php bbp_topic_permalink(); ?>"><?php echo esc_html( $title ); ?></a>
                </h3>

                <p class="last-reply" >
                    <?php esc_html_e('Última respuesta de','tu-tema'); ?>
                    <a href="<?php echo esc_url( $last_author_url ); ?>"><?php echo esc_html( $last_author_name ); ?></a>
                    <?php echo ' '; ?>
                    <a href="<?php echo esc_url( $last_reply_link ); ?>"><?php echo esc_html( $last_time_rel ); ?></a>
                </p>

                <p style="margin-bottom:10px"><?php echo esc_html( $excerpt ); ?></p>

                <?php if ( ! empty( $tags ) ) : ?>
                    <div class="tag-container">
                        <?php foreach ( $tags as $t ) :
                            $link = get_term_link( $t ); if ( is_wp_error( $link ) ) $link = '#'; ?>
                            <a class="tag-card "  href="<?php echo esc_url( $link ); ?>">
                                <?php echo esc_html( $t->name ); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Derecha -->
            <div class="col-md-auto text-end" style="min-width:160px;position:relative;padding-bottom:40px">
                <div style="display:inline-flex;gap:6px;flex-wrap:wrap;margin-bottom:6px;">
                    <?php foreach ( $mini_authors as $uid ) : ?>
                        <img loading="lazy" class="mini-image" src="<?php echo esc_url( theme_forum_avatar_url( $uid, 36 ) ); ?>"
                             alt="" style="width:28px;height:28px;border-radius:50%;object-fit:cover;border:1px solid #e5e7eb;">
                    <?php endforeach; ?>

                    <?php if ( $has_more_than_4 ) : ?>
                        <img loading="lazy" class="mini-image" src="<?php echo esc_url( $ellipsis_url ); ?>"
                             alt="más" title="más participantes"
                             style="width:28px;height:28px;border-radius:50%;object-fit:cover;border:1px solid #e5e7eb;background:#f3f4f6;">
                    <?php endif; ?>
                </div>

                <div >
                    <p style="margin:0;color:#6a6e83;"><i class="bi bi-chat"></i> <span class="like-count"><?php echo number_format_i18n( $replies ); ?></p>
                </div>

              <?php if ( function_exists('bbp_is_favorites_active') && bbp_is_favorites_active() ) : ?>
  <div class="topic-likes" style="margin-top:6px;">
    <?php if ( is_user_logged_in() ) : ?>
      <?php
      // Construimos un toggle nativo pero con tu HTML (icono + contador)
      echo bbp_get_topic_favorite_link( array(
        'topic_id'   => $topic_id,
        'user_id'    => get_current_user_id(),
        'before'     => '',
        'after'      => '',
        'link_class' => 'btn-like', // así conservas tu estilo
        // Cuando NO es favorito (mostrar corazón vacío)
        'favorite'   => sprintf(
          '<i class="bi bi-heart"></i> <span class="like-count">%s</span>',
          number_format_i18n( (int) get_post_meta( $topic_id, 'wm_fav_count', true ) )
        ),
        // Cuando YA es favorito (mostrar corazón lleno)
        'favorited'  => sprintf(
          '<i class="bi bi-heart-fill"></i> <span class="like-count">%s</span>',
          number_format_i18n( (int) get_post_meta( $topic_id, 'wm_fav_count', true ) )
        ),
      ) );
      ?>
    <?php else :
      $login = add_query_arg('redirect_to', urlencode( bbp_get_topic_permalink($topic_id) ), site_url('/acceso/'));
    ?>
      <a class="btn-like" href="<?php echo esc_url($login); ?>"
         aria-label="<?php esc_attr_e('Inicia sesión para agregar a favoritos','tu-tema'); ?>">
        <i class="bi bi-heart"></i>
        <span class="like-count"><?php echo number_format_i18n( (int) get_post_meta($topic_id,'wm_fav_count',true) ); ?></span>
      </a>
    <?php endif; ?>
  </div>
<?php endif; ?>
 <p style="align-self:end;text-align:end;font-size:14px;color:#6a6e83;margin:0;position:absolute;bottom:5px;right:10px;">
                <?php echo esc_html( theme_forum_hace( get_post_time('U', true, $topic_id) ) ); ?>
            </p>
            </div>

            <!-- Tiempo del topic (creación) abajo derecha -->
           
        </div>
    <?php endwhile; ?>

    <?php // Paginación abajo
    bbp_get_template_part( 'pagination', 'topics' ); ?>

<?php else : ?>

    <div class="bbp-no-topics" style="padding:14px;border:1px solid #e5e7eb;border-radius:12px;background:#fff;">
        <?php esc_html_e( 'No hay debates que mostrar.', 'bbpress' ); ?>
    </div>

<?php endif;

do_action( 'bbp_template_after_topics_loop' );
