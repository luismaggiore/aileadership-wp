<?php
/**
 * Override: Formulario "Nuevo tema"
 * Ruta: your-theme/bbpress/form-topic.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$can_create = is_user_logged_in() && current_user_can('foro_miembro') && bbp_current_user_can_access_create_topic_form();
$forum_id   = bbp_get_forum_id();

do_action( 'bbp_template_before_content' );

if ( bbp_is_forum_closed( $forum_id ) ) : ?>
    <div class="notice notice-error" style="padding:10px 12px;border-left:4px solid #DC2626;background:#FEF2F2;">
        <?php esc_html_e( 'Este foro está cerrado. No se pueden crear nuevos temas.', 'tu-tema' ); ?>
    </div>
<?php endif; ?>

<?php if ( $can_create ) : ?>

    <?php do_action( 'bbp_theme_before_topic_form' ); ?>

    <form id="new-topic" name="new-topic" method="post" action="">
        <?php do_action( 'bbp_theme_before_topic_form_notices' ); ?>

        <fieldset class="bbp-form">
            

            <?php do_action( 'bbp_theme_before_topic_form_fields' ); ?>

            <div class="bbp-form__row" style="margin-bottom:14px;">
                <label for="bbp_topic_title"><?php esc_html_e( 'Título', 'tu-tema' ); ?> <span style="color:#DC2626">*</span></label>
                <input type="text" id="bbp_topic_title" name="bbp_topic_title" value="<?php bbp_form_topic_title(); ?>" maxlength="100" placeholder="<?php esc_attr_e('Escribe un título claro…','tu-tema'); ?>" required style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;">
            </div>

            <div class="bbp-form__row" style="margin-bottom:14px;">
                <label for="bbp_topic_content"><?php esc_html_e( 'Contenido', 'tu-tema' ); ?> <span style="color:#DC2626">*</span></label>
                <?php
                $editor = array(
                    'textarea_rows' => 8,
                    'media_buttons' => false,
                    'teeny'         => true,
                    'tinymce'       => array( 'toolbar1' => 'bold,italic,link,bullist,numlist,undo,redo' ),
                );
                // IMPORTANTE: context correcto
                bbp_the_content( array_merge( $editor, array( 'context' => 'topic' ) ) );
                ?>
            </div>

            <?php
            // Etiquetas (sólo si están habilitadas y el usuario puede asignarlas)
            if ( function_exists('bbp_allow_topic_tags') && bbp_allow_topic_tags() && current_user_can('assign_topic_tags') ) : ?>
                <div class="bbp-form__row" style="margin-bottom:14px;">
                    <label for="bbp_topic_tags"><?php esc_html_e( 'Etiquetas (opcional)', 'tu-tema' ); ?></label>
                    <input type="text" id="bbp_topic_tags" name="bbp_topic_tags" value="<?php bbp_form_topic_tags(); ?>" placeholder="<?php esc_attr_e('separa con comas: ui, seguridad, caching…','tu-tema'); ?>" style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
            <?php endif; ?>

            <?php if ( ! bbp_is_single_forum() ) : ?>
                <div class="bbp-form__row" style="margin-bottom:14px;">
                    <label for="bbp_forum_id"><?php esc_html_e( 'Foro', 'tu-tema' ); ?> <span style="color:#DC2626">*</span></label>
                    <?php
                    bbp_dropdown( array(
                        'show_none' => __( 'Selecciona un foro…', 'tu-tema' ),
                        'selected'  => bbp_get_form_forum(),
                        'select_id' => 'bbp_forum_id',
                    ) );
                    ?>
                </div>
            <?php endif; ?>

            <?php if ( bbp_is_subscriptions_active() ) : ?>
                <div class="bbp-form__row" style="margin-bottom:14px;">
                    <label>
                        <input type="checkbox" name="bbp_topic_subscribe" id="bbp_topic_subscribe" value="1" <?php checked( bbp_is_user_subscribed_to_forum( bbp_get_current_user_id(), $forum_id ) ); ?>>
                        <?php esc_html_e( 'Suscribirme a las respuestas por email', 'tu-tema' ); ?>
                    </label>
                </div>
            <?php endif; ?>

            <?php do_action( 'bbp_theme_before_topic_form_submit_wrapper' ); ?>

            <div class="bbp-submit-wrapper" style="display:flex;gap:8px;align-items:center;">
                <button type="submit" class="btn btn-ai" id="bbp_topic_submit" name="bbp_topic_submit" style="padding:.6rem 1rem;border-radius:8px;"><?php esc_html_e( 'Publicar tema', 'tu-tema' ); ?></button>
            </div>

            <?php bbp_topic_form_fields(); // campos ocultos + nonce ?>

            <?php do_action( 'bbp_theme_after_topic_form_fields' ); ?>
        </fieldset>
    </form>

    <?php do_action( 'bbp_theme_after_topic_form' ); ?>

<?php elseif ( ! is_user_logged_in() ) : ?>

    <div class="notice notice-info" style="padding:10px 12px;border-left:4px solid #3B82F6;background:#EFF6FF;">
        <?php
        $login = wp_login_url( get_permalink() );
        printf(
            '%s <a class="button" href="%s" style="margin-left:8px;">%s</a>',
            esc_html__( 'Debes iniciar sesión para crear un tema.', 'tu-tema' ),
            esc_url( $login ),
            esc_html__( 'Iniciar sesión', 'tu-tema' )
        );
        ?>
    </div>

<?php else : ?>

    <div class="notice notice-warning" style="padding:10px 12px;border-left:4px solid #F59E0B;background:#FFFBEB;">
        <?php esc_html_e( 'No tienes permisos para crear temas en este foro.', 'tu-tema' ); ?>
    </div>

<?php endif;

do_action( 'bbp_template_after_content' );

