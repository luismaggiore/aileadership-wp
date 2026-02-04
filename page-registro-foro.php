<?php
/**
 * Template Name: Registro Foro (Invitación)
 * Description: Página de registro por invitación para el foro privado.
 */
if ( ! defined('ABSPATH') ) exit;

// URL de dashboard (ajústala si usas otra)
$dashboard_url = site_url('/mi-area/');

// 1) Redirección TEMPRANA: antes de imprimir nada
if ( is_user_logged_in() ) {
    $current    = wp_get_current_user();
    $is_pending = (bool) get_user_meta( $current->ID, 'wm_pending_approval', true );
    $is_member  = current_user_can('foro_miembro');

    if ( $is_member && ! $is_pending ) {
        wp_safe_redirect( $dashboard_url );
        exit;
    }
}

// 2) Noindex (esto no imprime todavía; sólo “registra” el hook)
add_action('wp_head', function(){
    echo '<meta name="robots" content="noindex,nofollow" />' . "\n";
});

get_header(); ?>

<main id="primary" class="container" style="padding-top:15vh">

    <?php
    // Mensajes informativos si ya hay sesión iniciada pero no aprobada / no coincide
    if ( is_user_logged_in() ) {
        $current    = wp_get_current_user();
        $is_pending = (bool) get_user_meta( $current->ID, 'wm_pending_approval', true );

        if ( $is_pending ) {
            echo '<section class="rf-alert rf-alert--info">
                    <strong>Tu cuenta está pendiente de aprobación.</strong><br>
                    Te avisaremos por email cuando el acceso esté habilitado.
                  </section>';
        } else {
            $logout = wp_logout_url( get_permalink() );
            echo '<section class="rf-alert rf-alert--warn">
                    <strong>Estás con sesión iniciada.</strong> Si recibiste una invitación para otro correo,
                    <a href="'. esc_url($logout) .'">cierra sesión aquí</a> y vuelve a abrir el enlace de invitación.
                  </section>';
        }
    }
    ?>

    <section class="rf-card">
        <header class="rf-header">
            <h1>Registro para miembros del foro</h1>
            <p class="rf-subtitle">Usa el enlace de invitación que recibiste para completar tu registro.</p>
        </header>

        <?php echo do_shortcode('[wm_forum_signup]'); ?>

        <footer class="rf-footer">
            <p class="rf-help">¿Problemas con el enlace? Escríbenos y te enviaremos una nueva invitación.</p>
        </footer>
    </section>
</main>

<style>
/* …(tus estilos aquí; sin cambios)… */
</style>

<?php get_footer();
