<?php
/* Template Name: Blog (con paginación) */
get_header();
get_template_part('gradient-2');

// Paginación actual (funciona tanto en páginas estáticas como con pretty links)
$paged = get_query_var('paged') ? (int) get_query_var('paged') : 1;
if ( $paged < 1 && get_query_var('page') ) {
  $paged = (int) get_query_var('page');
}

// Posts por página (usa el ajuste global de WordPress)
$ppp = (int) get_option('posts_per_page');

// Query principal del blog
$q = new WP_Query([
  'post_type'      => 'post',
  'post_status'    => 'publish',
  'posts_per_page' => $ppp,
  'paged'          => $paged,
  'orderby'        => 'date',
  'order'          => 'DESC',
  'no_found_rows'  => false, // necesario para paginate_links
]);

// Precalcular paginación (arriba y abajo)
$links = '';
if ( $q->max_num_pages > 1 ) {
  $big   = 999999999;
  $links = paginate_links([
    'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
    'format'    => '?paged=%#%', // compat con enlaces simples; con pretty permalinks se ajusta solo
    'current'   => max(1, $paged),
    'total'     => $q->max_num_pages,
    'type'      => 'list',
    'prev_text' => '« Anteriores',
    'next_text' => 'Siguientes »',
  ]);
}
?>

<div class="container" style="height: 100%;align-content: center;margin-top:25vh">
  <div class="row">
    <div class="col-12 text-left">
      <h1>Blog</h1>
      <p style="color:#333;margin-top: 20px ;max-width:700px; margin-bottom:40px;font-size:22px;line-height:32px;font-weight:300">
        Una comunidad académica que reflexiona, analiza y conversa sobre el impacto real de la inteligencia artificial en las organizaciones.
      </p>
    </div>
  </div>
</div>

<section class="pb-5">
  <div class="container">

    <?php if ( $links ) : ?>
      <nav class="mt-3 mb-4 nav-pagination" aria-label="Paginación del blog">
        <?php echo $links; ?>
      </nav>
    <?php endif; ?>

    <div class="row mb-5 g-4">

      <?php if ( $q->have_posts() ) : ?>
        <?php while ( $q->have_posts() ) : $q->the_post(); ?>
          <?php
            $imagen = get_the_post_thumbnail_url(get_the_ID(), 'medium');
            $titulo = get_the_title();
            $enlace = get_permalink();
          ?>
          <div class="col-md-4 mb-4">
            <div class="blog-post">
              <a href="<?php echo esc_url($enlace); ?>" class="blog-link">
                <div class="blog-pic-container">
                  <?php if ( $imagen ) : ?>
                    <img class="blog-pic" src="<?php echo esc_url($imagen); ?>" alt="<?php echo esc_attr($titulo); ?>">
                  <?php else : ?>
                    <img class="blog-pic" src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/placeholder.jpg' ); ?>" alt="Sin imagen">
                  <?php endif; ?>
                  <div class="blog-overlay"></div>
                </div>
              </a>
              <h3><?php echo esc_html($titulo); ?></h3>
              <a href="<?php echo esc_url($enlace); ?>" class="blog-link-a">Leer más<span class="line-blog-link"></span></a>
            </div>
          </div>
        <?php endwhile; wp_reset_postdata(); ?>
      <?php else : ?>
        <div class="col-12">
          <p>No hay contenido para mostrar.</p>
        </div>
      <?php endif; ?>

    </div>

    <?php if ( $links ) : ?>
      <nav class="mt-3 nav-pagination" aria-label="Paginación del blog">
        <?php echo $links; ?>
      </nav>
    <?php endif; ?>

  </div>
</section>

<?php get_footer(); ?>
