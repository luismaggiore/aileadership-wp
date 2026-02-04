<?php get_header(); ?>
<?php get_template_part('gradient'); ?>

<section class="blog-header">
  <div class="container">
    <div class="row">

      <div class="col-md-12">
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>

          <h1 class="animated-title"><?php the_title(); ?></h1>

          <?php
          // Obtener datos del autor
          $author_id = get_the_author_meta('ID');
          $author_name = get_the_author();
          $author_desc = get_the_author_meta('description');
          $author_avatar = get_avatar_url($author_id, ['size' => 200]);
          $custom_avatar = get_the_author_meta('profile_picture');
          $avatar_url = $custom_avatar ? $custom_avatar : get_avatar_url(get_the_author_meta('ID'), ['size' => 200]);
          ?>

          <div class="author">
            <img class="author-pic" src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($author_name); ?>">
            <div class="author-name">
              <h3><?php echo esc_html($author_name); ?></h3>
              <h4><?php echo esc_html($author_desc); ?></h4>
            </div>
          </div>
      </div>

      <div class="col-md-12">
        <img class="blog-header-img" src="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'full')); ?>" alt="<?php the_title(); ?>">
      </div>

    </div>
  </div>
</section>

<div class="container post-content">
  <div style="padding:60px; border-radius:5px; background-color:#fff;">
    <?php
          // Mostrar contenido del post
          the_content();
        endwhile; endif;
    ?>
  </div>
</div>

<div class="container" style="margin-top: 40px; margin-bottom: 80px;">
  <?php
  // Mostrar comentarios si están habilitados
  if (comments_open() || get_comments_number()) :
  ?>
    <div class="comments-section">
      <?php comments_template(); ?>
    </div>
  <?php endif; ?>
</div>

<?php get_footer(); ?>
