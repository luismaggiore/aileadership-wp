<?php get_header(); ?>
      <?php get_template_part('gradient');?>

<section class="blog-archive py-5" style="margin-top:25vh"> 
    <div class="container">
        <h1 class="mb-5"><?php the_archive_title(); ?></h1>

        <div class="row">
            <?php if (have_posts()) : ?>
                <?php while (have_posts()) : the_post(); ?>
                    <div class="col-md-4 mb-4">
                        <div class="blog-post">
                            <a class="blog-link" href="<?php the_permalink(); ?>">
                                <div class="blog-pic-container">
                                    <?php if (has_post_thumbnail()) : ?>
                                        <img class="blog-pic" src="<?php the_post_thumbnail_url('medium'); ?>" alt="<?php the_title_attribute(); ?>">
                                    <?php else : ?>
                                        <img class="blog-pic" src="<?php echo get_template_directory_uri(); ?>/assets/img/placeholder.jpg" alt="Placeholder">
                                    <?php endif; ?>
                                    <div class="blog-overlay"></div>
                                </div>
                            </a>
                            <h3><?php the_title(); ?></h3>
                            <a class="blog-link-a" href="<?php the_permalink(); ?>">
                                Leer más <span class="line-blog-link"></span>
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else : ?>
                <p>No se encontraron entradas.</p>
            <?php endif; ?>
        </div>

        <div class="pagination">
            <?php the_posts_pagination(); ?>
        </div>
    </div>
</section>

<?php get_footer(); ?>
