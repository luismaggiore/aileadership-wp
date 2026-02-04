<?php get_header(); ?>
<div class="container mt-5">
  <?php if ( have_posts() ) :
    while ( have_posts() ) : the_post(); ?>
      <h2><?php the_title(); ?></h2>
      <div><?php the_content(); ?></div>
    <?php endwhile;
  else : ?>
    <p>No hay contenido disponible.</p>
  <?php endif; ?>
  <?php get_sidebar(); ?>
</div>
<?php get_footer(); ?>
