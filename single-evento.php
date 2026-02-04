<?php get_header(); ?>
<?php get_template_part('gradient'); ?>

<section class="blog-header">
  <div class="container">
    <div class="row">

      <div class="col-md-12">
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>

          <h1 class="animated-title"><?php the_title(); ?></h1>

      </div>

     

    </div>
  </div>
</section>

<div class="container post-content">
    <div class="row">
  <div class="col-md-6" style="padding:40px; border-radius:5px; background-color:#fff;">
    
          <?php
          // Obtener y formatear fecha del evento
          $fecha_evento = get_post_meta(get_the_ID(), '_fecha_evento', true);
          $fecha_formateada = $fecha_evento ? date_i18n('j \d\e F Y', strtotime($fecha_evento)) : '';
          ?>

          <?php if ($fecha_formateada): ?>
            <div class="evento-fecha" style="margin-top:15px;margin-bottom:15px">
              <h4 style="color:#666;"><?php echo esc_html($fecha_formateada); ?></h4>
            </div>
          <?php endif; ?>
    <?php the_content(); ?>
    <?php endwhile; endif; ?>
  </div>

   <div class="col-md-6">
        <img class="blog-header-img" src="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'full')); ?>" alt="<?php the_title(); ?>">
      </div>
</div></div>

<div class="container" style="margin-top: 40px; margin-bottom: 80px;">
  <?php if (comments_open() || get_comments_number()) : ?>
    <div class="comments-section">
      <?php comments_template(); ?>
    </div>
  <?php endif; ?>
</div>

<?php get_footer(); ?>
