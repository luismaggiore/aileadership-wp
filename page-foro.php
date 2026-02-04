<?php

if ( ! defined('ABSPATH') ) exit;

// Protección temprana: sólo miembros del foro
if ( ! is_user_logged_in() || ! current_user_can('foro_miembro') ) {
    $login = site_url('/acceso/');
    $login = add_query_arg( 'redirect_to', urlencode( get_permalink() ), $login );
    wp_safe_redirect( add_query_arg('login','required', $login) );
    exit;
}

// Noindex
add_action('wp_head', function(){
    echo '<meta name="robots" content="noindex,nofollow" />' . "\n";
});

get_header(); ?>

<main class="site-main site-main--foro" style="padding:32px 16px;">
    <div class="container" style="max-width:1100px;margin:0 auto;">

        <header class="foro-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin:0 0 16px;">
            <div>
                <h1 style="margin:0;">Bienvenido a la discusión sobre IA</h1>
                <p style="margin:6px 0 0;color:#6b7280">Explora los foros y participa en los temas.</p>
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
                <?php if ( function_exists('bbp_get_template_part') ) : ?>
                    <?php bbp_get_template_part( 'form', 'search' ); ?>
                <?php endif; ?>
                <a class="button" href="<?php echo esc_url( site_url('/mi-area/') ); ?>" style="padding:.6rem 1rem;border:1px solid #e5e7eb;border-radius:8px;text-decoration:none;">
                    ← Mi área
                </a>
            </div>
        </header>

        <section class="foro-index" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:18px;">
            <?php
            // Índice de foros (lista de foros raíz)
            echo do_shortcode('[bbp-forum-index]');
            ?>
        </section>

        <?php if ( function_exists('bbp_has_topics') ) : ?>
        <section class="foro-mis-temas" style="margin-top:24px;">
            <h2 style="margin:0 0 10px;">Tus últimos temas</h2>
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:18px;">
                <?php if ( bbp_has_topics( array( 'author' => get_current_user_id(), 'posts_per_page' => 5 ) ) ) : ?>
                    <?php bbp_get_template_part( 'loop', 'topics' ); ?>
                <?php else : ?>
                    <p style="color:#6b7280;">Aún no has creado temas.</p>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

    </div>
</main>

<?php get_footer();
