<?php
/**
 * Override: Formulario "Responder"
 * Ruta: your-theme/bbpress/form-reply.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$topic_id   = bbp_get_topic_id();
$forum_id   = bbp_get_forum_id();
$can_reply  = is_user_logged_in() && current_user_can('foro_miembro') && bbp_current_user_can_access_create_reply_form();
$reply_to   = isset($_GET['bbp_reply_to']) ? absint($_GET['bbp_reply_to']) : 0;
$reply_data = null;

if ( $reply_to && bbp_get_reply($reply_to) ) {
    $reply_data = get_post($reply_to);
    $reply_author = get_the_author_meta('display_name', $reply_data->post_author);
    $reply_excerpt = wp_trim_words(strip_tags($reply_data->post_content), 20, '...');
}

do_action( 'bbp_template_before_content' );
?>

<?php if ( bbp_is_topic_closed( $topic_id ) || bbp_is_forum_closed( $forum_id ) ) : ?>
    <div class="notice notice-error" style="padding:10px 12px;border-left:4px solid #DC2626;background:#FEF2F2;">
        <?php esc_html_e( 'Este tema está cerrado. No se pueden añadir respuestas.', 'tu-tema' ); ?>
    </div>
<?php endif; ?>

<?php if ( $can_reply ) : ?>

    <?php do_action( 'bbp_theme_before_reply_form' ); ?>

    <form id="new-reply" name="new-reply" method="post" action="">
        <?php do_action( 'bbp_theme_before_reply_form_notices' ); ?>

        <fieldset class="bbp-form">
            <legend class="bbp-form__legend" style="font-weight:700;margin-bottom:8px;">
                <?php if ( $reply_data ) : ?>
                    <?php printf( esc_html__( 'Respondiendo a %s', 'tu-tema' ), esc_html( $reply_author ) ); ?>
                <?php else : ?>
                    <?php esc_html_e( 'Añadir respuesta', 'tu-tema' ); ?>
                <?php endif; ?>
            </legend>

            <?php if ( $reply_data ) : ?>
                <div class="notice notice-info" style="padding:8px 12px;margin-bottom:12px;border-left:4px solid #3B82F6;background:#EFF6FF;">
                    <?php echo esc_html( $reply_excerpt ); ?>
                </div>
            <?php endif; ?>

            <div class="bbp-form__row" style="margin-bottom:14px;">
                <label for="bbp_reply_content"><?php esc_html_e( 'Tu respuesta', 'tu-tema' ); ?> <span style="color:#DC2626">*</span></label>
                <?php
                $editor = array(
                    'textarea_rows' => 6,
                    'media_buttons' => false,
                    'teeny'         => true,
                    'tinymce'       => array( 'toolbar1' => 'bold,italic,link,bullist,numlist,undo,redo' ),
                );
                bbp_the_content( array_merge( $editor, array( 'context' => 'reply' ) ) );
                ?>
            </div>

            <?php if ( bbp_is_subscriptions_active() ) : ?>
                <div class="bbp-form__row" style="margin-bottom:14px;">
                    <label>
                        <input name="bbp_topic_subscription" id="bbp_topic_subscription" type="checkbox" value="1" <?php checked( bbp_is_user_subscribed( bbp_get_current_user_id(), $topic_id ) ); ?>>
                        <?php esc_html_e( 'Notificarme por email cuando haya nuevas respuestas', 'tu-tema' ); ?>
                    </label>
                </div>
            <?php endif; ?>

            <div class="bbp-submit-wrapper" style="display:flex;gap:8px;align-items:center;">
                <button type="submit" class="button button-primary" id="bbp_reply_submit" name="bbp_reply_submit" style="padding:.6rem 1rem;border-radius:8px;">
                    <?php esc_html_e( 'Publicar respuesta', 'tu-tema' ); ?>
                </button>
                <span style="color:#6b7280;font-size:13px;"><?php esc_html_e( 'Sé cordial y aporta contexto.', 'tu-tema' ); ?></span>
            </div>

            <?php bbp_reply_form_fields(); ?>

            <?php do_action( 'bbp_theme_after_reply_form_fields' ); ?>
        </fieldset>
    </form>

    <?php do_action( 'bbp_theme_after_reply_form' ); ?>

<?php elseif ( ! is_user_logged_in() ) : ?>

    <div class="notice notice-info" style="padding:10px 12px;border-left:4px solid #3B82F6;background:#EFF6FF;">
        <?php
        $login = wp_login_url( get_permalink() );
        printf(
            '%s <a class="button" href="%s" style="margin-left:8px;">%s</a>',
            esc_html__( 'Debes iniciar sesión para responder.', 'tu-tema' ),
            esc_url( $login ),
            esc_html__( 'Iniciar sesión', 'tu-tema' )
        );
        ?>
    </div>

<?php else : ?>

    <div class="notice notice-warning" style="padding:10px 12px;border-left:4px solid #F59E0B;background:#FFFBEB;">
        <?php esc_html_e( 'No tienes permisos para responder en este tema.', 'tu-tema' ); ?>
    </div>

<?php endif;

do_action( 'bbp_template_after_content' );
?>
