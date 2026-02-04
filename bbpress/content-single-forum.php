<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Solo acceso a usuarios con rol adecuado
if ( ! is_user_logged_in() || ! current_user_can( 'foro_miembro' ) ) : ?>
    <section class="forum-locked">
        <h1><?php esc_html_e( 'Acceso restringido', 'tu-tema' ); ?></h1>
        <p><?php esc_html_e( 'Este foro es privado. Debes iniciar sesión con una cuenta aprobada para continuar.', 'tu-tema' ); ?></p>
        <p>
            <a class="btn btn-primary" href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>">
                <?php esc_html_e( 'Iniciar sesión', 'tu-tema' ); ?>
            </a>
        </p>
    </section>
    <?php return;
endif;
?>

<?php if ( bbp_user_can_view_forum( array( 'forum_id' => bbp_get_forum_id() ) ) ) : ?>

<div class="row mb-4 justify-content-between">

<div class="col">   
<?php bbp_breadcrumb(); ?>
</div>

    <div class="col-auto forum-subscription-toggle" style="align-content: center;">
       <?php if ( function_exists( 'bbp_is_subscriptions_active' ) && bbp_is_subscriptions_active() ) : ?>
        <?php
        $user_id = get_current_user_id();
        $forum_id = bbp_get_forum_id();
        $is_subscribed = bbp_is_user_subscribed( $user_id, $forum_id );
        $nonce = wp_create_nonce( 'toggle_forum_subscription_' . $forum_id );
        ?>
        <form method="post" class="">
            <input type="hidden" name="forum_id" value="<?php echo esc_attr( $forum_id ); ?>">
            <input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>">
            <button type="submit" class="btn-toggle-subscription" style="background:none; border:none; cursor:pointer;">
                <?php if ( $is_subscribed ) : ?>
                    <i class="bi bi-bell-fill" title="Dejar de seguir foro"></i>
                <?php else : ?>
                    <i class="bi bi-bell" title="Seguir foro"></i>
                <?php endif; ?>
            </button>
        </form>
    <?php endif; ?> </div>


</div>

<header class="mb-4">
<div style="display:inline-flex;align-items:center;position:relative;width:100%">

    <h1 class="mb-2"><?php bbp_forum_title(); ?></h1>





</div>



    <?php if ( $desc = bbp_get_forum_content() ) : ?>
        <div class="forum-description mt-3 mb-3"><?php echo wpautop( esc_html( $desc ) ); ?></div>
    <?php endif; ?>

    <?php if ( bbp_is_forum_open() && bbp_current_user_can_access_create_topic_form() ) : ?>
        <div class="new-topic-div">
        <button class=" btn-new-topic">Nuevo tema<span><i class="bi bi-pencil-fill mx-2"></i></span></button>

        <div class="form-accordeon">
         <?php      bbp_get_template_part( 'form', 'topic' ); ?>   
        </div>
        </div>
    <?php endif; ?>

 

</header>

<?php do_action( 'bbp_template_notices' ); ?>

<div id="forum-<?php bbp_forum_id(); ?>" class="bbp-forum-content">

    <?php if ( bbp_has_forums() ) : ?>
        <section class="forum-children">
            <h2><?php esc_html_e( 'Subforos', 'tu-tema' ); ?></h2>
            <?php bbp_get_template_part( 'loop', 'forums' ); ?>
        </section>
    <?php endif; ?>

    <section class="forum-topics">
        <h2><?php esc_html_e( 'Temas', 'tu-tema' ); ?></h2>

        <?php if ( bbp_has_topics() ) : ?>
            <?php bbp_get_template_part( 'loop', 'topics3' ); ?>
        <?php else : ?>
            <p><?php esc_html_e( 'Aún no hay temas en este foro.', 'tu-tema' ); ?></p>
        <?php endif; ?>
    </section>



</div>

<?php else : ?>
    <?php bbp_get_template_part( 'feedback', 'no-access' ); ?>
<?php endif; ?>
