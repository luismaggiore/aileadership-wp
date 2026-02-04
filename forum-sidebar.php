<div  class="sidebar" >
                <div class="buscador mb-4">
                  <h2 class="my-area-title">Buscador</h2>
               <?php if ( function_exists('bbp_get_template_part') ) : ?>
                    <?php bbp_get_template_part( 'form', 'search' ); ?>
                <?php endif; ?>
      </div>

                               <?php 
echo wm_render_user_favorites_preview([
  'max' => 3,
  'cta_url' => site_url('/myfavorites/'),
  'orderby' => 'post__in',
  'show_unfavorite' => true,
  'empty_text' => 'Aún no tienes favoritos.'
]);
            ?>
        <?php
echo wm_render_user_subscriptions_preview([
  'max' => 3,
  'cta_url' => site_url('/mysubscriptions/'),
  'orderby' => 'post__in',
  'show_unsubscribe' => true,
  'include_forums' => true
]);


        ?>
                
<?php
echo wm_render_trending_topic_tags([
  'days'  => 61,
  'limit' => 8,
]);

?>


   
<?php

echo wm_render_recommended_topics_for_user([
  'limit' => 3,
  'days'  => 90,     // considera topics de los últimos 90 días
]);

?>
</div>