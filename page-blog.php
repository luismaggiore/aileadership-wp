<?php get_header(); ?>
      <?php get_template_part('gradient-2');?>

  <div class=" container" style="height: 100%;align-content: center;margin-top:25vh">
    <div class="row">
      <div class="col-12 text-left">
    <h1  >Blog</h1>
    <p style="color:#333;margin-top: 20px ;max-width:700px; margin-bottom:40px;font-size:22px;line-height:32px;font-weight:300">Una comunidad académica que reflexiona, analiza y conversa sobre el impacto real de la inteligencia artificial en las organizaciones.</p>
              
   
  </div>
</div>
  </div>



<section class="pb-5"> 
    <div class=" container" >

        <div class="row mb-5 g-4">
            
  <?php mostrar_tarjetas_bootstrap('post',0); ?>
        </div>  
    </div>



</section>

<?php get_footer(); ?>
