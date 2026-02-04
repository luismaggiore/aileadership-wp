<?php
// Seguridad: evitar fatales si el plugin no cargó
if ( ! function_exists( 'bbp_forum_pagination_count' ) || ! function_exists( 'bbp_forum_pagination_links' ) ) {
    return;
}

do_action( 'bbp_template_before_topics_pagination' );
?>
<div class="bbp-pagination top">
    <div class="bbp-pagination-count">
        <?php bbp_forum_pagination_count(); ?>
    </div>

    <div class="bbp-pagination-links">
        <?php bbp_forum_pagination_links(); ?>
    </div>
</div>
<?php do_action( 'bbp_template_after_topics_pagination' ); ?>
