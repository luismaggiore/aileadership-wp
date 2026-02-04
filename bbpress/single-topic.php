<?php

/**
 * Single Topic
 *
 * @package bbPress
 * @subpackage Theme
 */

get_header(); ?>
<?php get_template_part('gradient-2');?>
	<?php do_action( 'bbp_before_main_content' ); ?>

	<?php do_action( 'bbp_template_notices' ); ?>

	<?php if ( bbp_user_can_view_forum( array( 'forum_id' => bbp_get_topic_forum_id() ) ) ) : ?>

		<?php while ( have_posts() ) : the_post(); ?>
		
    	<div id="bbp-topic-wrapper-<?php bbp_topic_id(); ?>" class="bbp-topic-wrapper container" style="margin-top:15vh" >
           
        
     <div class="row mb-4 justify-content-between">
    <div class="col">
        <?php bbp_breadcrumb(); ?>
    </div>

    <div class="col-auto d-flex gap-3 align-items-center">
        <?php if ( is_user_logged_in() ) : ?>
            <?php
            $user_id  = get_current_user_id();
            $topic_id = bbp_get_topic_id();
            $is_subscribed = bbp_is_user_subscribed( $user_id, $topic_id );
            $nonce = wp_create_nonce( 'toggle_topic_subscription_' . $topic_id );
            ?>
            <form method="post" action="" class="toggle-topic-subscription-form">
                <input type="hidden" name="action" value="toggle_topic_subscription">
                <input type="hidden" name="topic_id" value="<?php echo esc_attr($topic_id); ?>">
                <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">
                <button type="submit" class="btn-toggle-subscription" style="background:none; border:none; cursor:pointer;">
                    <?php if ( $is_subscribed ) : ?>
                        <i class="bi bi-bell-fill " title="Dejar de seguir debate"></i>
                    <?php else : ?>
                        <i class="bi bi-bell" title="Seguir debate"></i>
                    <?php endif; ?>
                </button>
            </form>

        
           
        <?php endif; ?>

        <?php if ( is_user_logged_in() ) : ?>
    <?php
    $user_id   = get_current_user_id();
    $topic_id  = bbp_get_topic_id();
    $is_fav    = bbp_is_user_favorite( $user_id, $topic_id );
    $nonce     = wp_create_nonce( 'toggle_topic_favorite_' . $topic_id );
    ?>
    <form method="post" action="" class="toggle-topic-favorite-form" style="display:inline;">
        <input type="hidden" name="action" value="toggle_topic_favorite">
        <input type="hidden" name="topic_id" value="<?php echo esc_attr($topic_id); ?>">
        <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">
        <button type="submit" class="btn-toggle-favorite" style="background:none; border:none; cursor:pointer;">
            <?php if ( $is_fav ) : ?>
                <i class="bi bi-heart-fill text-danger" title="Quitar de favoritos"></i>
            <?php else : ?>
                <i class="bi bi-heart" title="Agregar a favoritos"></i>
            <?php endif; ?>
        </button>
    </form>
<?php endif; ?>

    </div>
</div>


	
<?php
// Datos del autor del topic
$topic_id    = bbp_get_topic_id();
$author_id   = bbp_get_topic_author_id( $topic_id );
$avatar_url  = get_avatar_url( $author_id, array( 'size' => 96 ) );
$author_name = get_the_author_meta( 'display_name', $author_id );
$created_at  = get_the_date( '', $topic_id );
$content     = bbp_get_topic_content( $topic_id );
$tags        = get_the_terms( $topic_id, 'topic-tag' );
?>

<div class="topic-header mb-5">
    
    <!-- Título del topic -->
    <h1 class="entry-title mb-3"><?php bbp_topic_title(); ?></h1>

    <!-- Barra inferior al título: avatar, tags, meta, botón -->
    <div class="d-flex flex-wrap align-items-center gap-4 mb-3">

        <!-- Avatar -->
        <div class="topic-author-avatar">
            <img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $author_name ); ?>" style="width:60px;height:60px;border-radius:50%;object-fit:cover;border:1px solid #e5e7eb;">
        </div>
    <div>
           <div class="topic-meta text-muted" style="font-size:14px;">
            Iniciado por <strong><?php echo esc_html( $author_name ); ?></strong> el <?php echo esc_html( $created_at ); ?>
        </div>
       
        <!-- Tags -->
        <?php if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) : ?>
            <div class="tag-container ">
                <?php foreach ( $tags as $tag ) : ?>
                    <a href="<?php echo esc_url( get_term_link( $tag ) ); ?>" class="tag-card" style="border: #7c4cff solid 1px; ">
                        #<?php echo esc_html( $tag->name ); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
</div>
        <!-- Meta: autor y fecha -->
      
        <!-- Botón Responder -->
     
    </div>

    <!-- Contenido del topic -->
    <div class="topic-content card" style="font-size:18px;line-height:1.6;width:100%">
        <?php echo wpautop( $content ); ?>
         <div class="ms-auto">
            <a href="#new-reply" class="btn btn-ai" style="border-radius:6px;padding:8px 16px;font-weight:500;">
                Responder
            </a>
        </div>
    </div>

      
</div>



           
                
				<div class="entry-content">

					<?php bbp_get_template_part( 'content', 'single-topic' ); ?>

				</div>
			</div><!-- #bbp-topic-wrapper-<?php bbp_topic_id(); ?> -->

		<?php endwhile; ?>

	<?php elseif ( bbp_is_forum_private( bbp_get_topic_forum_id(), false ) ) : ?>

		<?php bbp_get_template_part( 'feedback', 'no-access' ); ?>

	<?php endif; ?>

	<?php do_action( 'bbp_after_main_content' ); ?>


<?php get_footer();
