<?php
/**
 * Loop single reply personalizado
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$reply_id     = bbp_get_reply_id();
$author_id    = bbp_get_reply_author_id( $reply_id );
$reply_to_id  = bbp_get_reply_to( $reply_id );
$is_topic_lead = ( $reply_id === bbp_get_topic_id() );

// Datos del autor
$display      = get_the_author_meta( 'display_name', $author_id );
$title        = get_user_meta( $author_id, 'profile_title', true );
$pic          = get_user_meta( $author_id, 'profile_picture', true );
$avatar_url   = $pic ? esc_url( $pic ) : esc_url( get_avatar_url( $author_id, array( 'size' => 96 ) ) );

$classes = array( 'bbp-reply', 'reply' );
if ( bbp_is_reply_anonymous( $reply_id ) ) {
    $classes[] = 'anonymous';
}

if ( $is_topic_lead ) {
    $classes[] = 'reply-topic-lead';
}
?>

<?php
$reply_id     = bbp_get_reply_id();
$topic_id     = bbp_get_topic_id();
?>

<li id="post-<?php bbp_reply_id(); ?>" <?php post_class( $classes ); ?>>

<?php if ( $reply_id === $topic_id ) : ?>

    
<?php else : ?>    
<article>
    <div class="container card reply-topic-item">
    <div class="row gy-2 gx-4" >

    <div class="col-auto">
        <div class="bbp-author-avatar" style="margin-bottom:8px;">
            <img src="<?php echo $avatar_url; ?>" alt="<?php echo esc_attr( $display ); ?>" style="width:88px;height:88px;border-radius:50%;object-fit:cover;border:1px solid #e5e7eb;">
        </div>
        <div class="bbp-author-name" style="font-weight:600;"><?php echo esc_html( $display ); ?></div>
        <?php if ( ! empty( $title ) ) : ?>
            <div class="bbp-author-title" style="color:#6b7280;font-size:13px;line-height:1.3;margin-top:2px;">
                <?php echo esc_html( $title ); ?>
            </div>
        <?php endif; ?>
    

        </div>

        <div class="col-md">
            <div class="row mb-4 text-end">
             <?php bbp_reply_admin_links(); ?>
            </div>
        

    <div>
     

        <div  >
            <?php if ( $reply_to_id && $reply_to_id != $reply_id && bbp_is_reply( $reply_to_id ) ) :
                $reply_to_author = get_the_author_meta( 'display_name', bbp_get_reply_author_id( $reply_to_id ) );
                $reply_to_excerpt = wp_trim_words( bbp_get_reply_content( $reply_to_id ), 12, '…' );

                $topic_permalink = bbp_get_topic_permalink( bbp_get_reply_topic_id( $reply_to_id ) );
                $reply_permalink = $topic_permalink . '#post-' . $reply_to_id;
            ?>
                <div class="bbp-reply-context" style="background:#f9fafb; border-left:#ba42ffa1 4px solid;padding:8px 12px;margin-bottom:10px;font-size:13px;color:#374151;">
                    <strong>En respuesta a <a href="<?php echo esc_url( $reply_permalink ); ?>" class="scroll-to-reply" data-target="post-<?php echo esc_attr( $reply_to_id ); ?>"><?php echo esc_html( $reply_to_author ); ?></a>:</strong>
                    <em><?php echo esc_html( $reply_to_excerpt ); ?></em>
                </div>
            <?php endif; ?>
               <div class="reply-content">    <?php bbp_reply_content(); ?></div>

        </div>

    </div>

        <?php if ( bbp_is_user_keymaster() ) : ?>
            <footer class="bbp-reply-ip" >
       <p style="color:gray">
                    <?php echo esc_html( bbp_get_reply_post_date() ); ?>
        </p>

            </footer>
        <?php endif; ?>

        </div>
    </div>
    </div>
</article>




<?php endif; ?>
</li>

<?php
// Solo insertar el script una vez
if ( ! did_action( 'custom_reply_scroll_script' ) ) :
    do_action( 'custom_reply_scroll_script' );
    add_action( 'wp_footer', function() { ?>
        <script>
        document.addEventListener("DOMContentLoaded", function () {
            const links = document.querySelectorAll('.scroll-to-reply');
            links.forEach(link => {
                link.addEventListener('click', function (e) {
                    const targetId = this.getAttribute('data-target');
                    const targetEl = document.getElementById(targetId);
                    if (targetEl) {
                        e.preventDefault();
                        const offset = 100;
                        const targetTop = targetEl.getBoundingClientRect().top + window.scrollY - offset;
                        window.scrollTo({ top: targetTop, behavior: 'smooth' });
                        history.replaceState(null, null, `#${targetId}`);
                    }
                });
            });
        });
        </script>
    <?php }, 99 );
endif;
?>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const urlParams = new URLSearchParams(window.location.search);
    const replyTo = urlParams.get("bbp_reply_to");

    if (replyTo) {
        const replyForm = document.getElementById("new-reply");
        if (replyForm) {
            setTimeout(function () {
                const offset = 120;
                const y = replyForm.getBoundingClientRect().top + window.scrollY - offset;
                window.scrollTo({ top: y, behavior: "smooth" });
            }, 300); // Espera leve para que se renderice el formulario correctamente
        }
    }
});
</script>