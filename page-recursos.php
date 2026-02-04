<?php get_header(); ?>
      <?php get_template_part('gradient');?>
          <?php get_template_part('network');?>

<section class="py-5" style="margin-top:25vh;position:relative;z-index:2"> 
    <h1 class="mb-5 text-center animated-title">Recursos</h1>
    <div class=" container" >

        <div class="row mb-5 g-4">
            <?php mostrar_tarjetas_recursos(-1,"",4); ?>
        </div>  
    </div>



</section>

<?php get_footer(); ?>
