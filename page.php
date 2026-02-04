<?php get_header(); ?>
 <?php get_template_part('gradient-2');?>
<div class="container mt-5">
  <?php
  if ( have_posts() ) : while ( have_posts() ) : the_post();
    the_title('<h1>', '</h1>');
    the_content();
  endwhile; endif;
  ?>
</div>
<?php get_footer(); ?>
