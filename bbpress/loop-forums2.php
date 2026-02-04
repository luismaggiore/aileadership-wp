<?php
/**
 * Loop: Forums (custom cards)
 * Ruta: your-theme/bbpress/loop-forums.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$ellipsis_url = get_stylesheet_directory_uri() . '/assets/img/badges/ellipsis-avatar.png'; // ícono "..." para 5º mini

// Helpers mínimos (si no existen ya por tu tema)
if ( ! function_exists('theme_forum_avatar_url') ) {
    function theme_forum_avatar_url( $user_id, $size = 96 ) {
        $meta = $user_id ? get_user_meta( $user_id, 'profile_picture', true ) : '';
        if ( $meta ) return esc_url( $meta );
        return $user_id ? esc_url( get_avatar_url( $user_id, array( 'size' => $size ) ) ) : esc_url( get_stylesheet_directory_uri() . '/assets/img/badges/default.png' );
    }
}
if ( ! function_exists('theme_forum_hace') ) {
    function theme_forum_hace( $timestamp ) {
        $timestamp = $timestamp ? (int) $timestamp : current_time('timestamp');
        $diff = human_time_diff( $timestamp, current_time('timestamp') );
        return sprintf( _x( 'hace %s', 'relative time', 'tu-tema' ), $diff );
    }
}

do_action( 'bbp_template_before_forums_loop' );

if ( bbp_has_forums() ) :

    while ( bbp_forums() ) : bbp_the_forum();

        $forum_id = bbp_get_forum_id();

        // Última actividad del foro (post puede ser topic o reply)
        $last_active_id = function_exists('bbp_get_forum_last_active_id') ? bbp_get_forum_last_active_id( $forum_id ) : 0;
        $last_post_id   = $last_active_id ? $last_active_id : $forum_id;
        $last_type      = get_post_type( $last_post_id );

        if ( $last_type === bbp_get_reply_post_type() ) {
            $last_link = bbp_get_reply_url( $last_post_id );
        } elseif ( $last_type === bbp_get_topic_post_type() ) {
            $last_link = bbp_get_topic_permalink( $last_post_id );
        } else {
            $last_link = bbp_get_forum_permalink( $forum_id );
        }

        $last_author_id   = (int) get_post_field( 'post_author', $last_post_id );
        if ( ! $last_author_id ) $last_author_id = (int) get_post_field( 'post_author', $forum_id );
        $last_author_name = $last_author_id ? get_the_author_meta( 'display_name', $last_author_id ) : __( 'Alguien', 'tu-tema' );
        $last_author_url  = $last_author_id ? bbp_get_user_profile_url( $last_author_id ) : '#';

        $last_ts       = get_post_time( 'U', true, $last_post_id );
        if ( ! $last_ts ) $last_ts = get_post_time( 'U', true, $forum_id );
        $last_time_rel = theme_forum_hace( $last_ts );

        // Descripción del foro recortada
        $raw     = get_post_field( 'post_content', $forum_id );
        $plain   = wp_strip_all_tags( strip_shortcodes( $raw ) );
        $excerpt = wp_trim_words( $plain, 26, '...' );

        // Subforos (chips) máx. 5
        $subforums = get_posts( array(
            'post_type'        => bbp_get_forum_post_type(),
            'post_parent'      => $forum_id,
            'posts_per_page'   => 5,
            'orderby'          => 'menu_order title',
            'order'            => 'ASC',
            'fields'           => 'ids',
            'no_found_rows'    => true,
            'suppress_filters' => true,
        ) );

        // Contadores
        $topics  = (int) bbp_get_forum_topic_count( $forum_id, true, true );
        $replies = (int) bbp_get_forum_reply_count( $forum_id, true, true );

        // Imagen principal = avatar de quien tuvo la última actividad (o default)
        $cover_img  = theme_forum_avatar_url( $last_author_id, 96 );
        $badge_html = function_exists('wm_forum_badge_img') ? wm_forum_badge_img( $last_author_id ) : '';

        // Mini-avatars: autores únicos de actividad reciente (hasta 4 + "...")
        $mini_authors = array();
        $recent_topics = get_posts( array(
            'post_type'        => bbp_get_topic_post_type(),
            'post_parent'      => $forum_id,
            'posts_per_page'   => 20,
            'orderby'          => 'date',
            'order'            => 'DESC',
            'fields'           => 'ids',
            'no_found_rows'    => true,
            'suppress_filters' => true,
        ) );
        if ( $recent_topics ) {
            foreach ( $recent_topics as $tid ) {
                $lp  = function_exists('bbp_get_topic_last_reply_id') ? bbp_get_topic_last_reply_id( $tid ) : 0;
                $pid = $lp ? $lp : $tid;
                $uid = (int) get_post_field( 'post_author', $pid );
                if ( $uid && ! in_array( $uid, $mini_authors, true ) ) {
                    $mini_authors[] = $uid;
                    if ( count( $mini_authors ) >= 5 ) break; // capturamos 5 para saber si hay más de 4
                }
            }
        }
        $has_more_than_4 = ( count( $mini_authors ) > 4 );
        $mini_authors    = array_slice( $mini_authors, 0, 4 );
        ?>

        <div class="col-xl-4 " style="position:relative;align-items:flex-start;">
             <a class="forum-wp"  href="<?php bbp_forum_permalink(); ?>" style="text-decoration:none;color:#111827;">
        <div class="forum-wp-div">
                        <!-- Imagen + insignia -->
         

            <!-- Centro: título, meta, excerpt, subforos -->
            <div class="col" style="margin-bottom:20px">
                 
                <h3 style="margin:0 0 6px;font-weight:500">
                  <?php bbp_forum_title(); ?>
                </h3>
   </div>
            

                <?php if ( ! empty( $excerpt ) ) : ?>
                    <p style="margin:0 0 10px;color:#4b5563;"><?php echo esc_html( $excerpt ); ?></p>
                <?php endif; ?>

                <?php if ( ! empty( $subforums ) ) : ?>
                    <div class="tag-container row" style="gap:8px;margin-top:6px;">
                        <?php foreach ( $subforums as $sf_id ) : ?>
                            <a class="tag-card col-auto" href="<?php echo esc_url( get_permalink( $sf_id ) ); ?>" style="display:inline-block;background:#eef2ff;color:#3730a3;border-radius:999px;padding:4px 10px;font-size:12px;text-decoration:none;">
                                <?php echo esc_html( get_the_title( $sf_id ) ); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
         

            <!-- Derecha: mini participantes + contadores -->
            <div class="col-md-auto" style="min-width:180px;">
                <div style="display:inline-flex;gap:6px;flex-wrap:wrap;margin-bottom:6px;">
                    <?php foreach ( $mini_authors as $uid ) : ?>
                        <img loading="lazy" class="mini-image" src="<?php echo esc_url( theme_forum_avatar_url( $uid, 36 ) ); ?>" alt="" style="width:28px;height:28px;border-radius:50%;object-fit:cover;border:1px solid #e5e7eb;">
                    <?php endforeach; ?>

                    <?php if ( $has_more_than_4 ) : ?>
                        <img loading="lazy" class="mini-image" src="<?php echo esc_url( $ellipsis_url ); ?>" alt="más" title="más participantes" style="width:28px;height:28px;border-radius:50%;object-fit:cover;border:1px solid #e5e7eb;background:#f3f4f6;">
                    <?php endif; ?>
                </div>

                <div style="color:#6a6e83;">
                    <p style="margin:0;"><i class="bi bi-chat-left-text"></i> <?php echo number_format_i18n( $topics ); ?> <?php esc_html_e('Temas','tu-tema'); ?></p>
                    <p style="margin:4px 0 0;"><i class="bi bi-chat"></i> <?php echo number_format_i18n( $replies ); ?> <?php esc_html_e('Respuestas','tu-tema'); ?></p>
                </div>
            </div>

            <!-- Tiempo absoluto abajo derecha -->
            <p style="position:absolute;bottom:2px;right:-8px;text-align:end;font-size:14px;padding-right:20px;color:#6a6e83;margin:0;">
                <?php echo esc_html( theme_forum_hace( get_post_time('U', true, $forum_id) ) ); ?>
            </p>
        </div>
        </a>
     </div>
        <?php
    endwhile;

else : ?>

    <div class="bbp-no-forums" style="padding:14px;border:1px solid #e5e7eb;border-radius:12px;background:#fff;">
        <?php esc_html_e( 'No hay foros para mostrar.', 'tu-tema' ); ?>
    </div>

<?php
endif;

do_action( 'bbp_template_after_forums_loop' );
