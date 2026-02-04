<?php get_header(); ?>
      <?php get_template_part('gradient-2');?>
 <div class=" container" style="height: 100%;align-content: center;margin-top:25vh;position:relative;z-index:2">
    <div class="row">
      <div class="col-12 text-left">
    <h1  >Recursos</h1>
    <p style="color:#333;margin-top: 20px ;max-width:700px; margin-bottom:40px;font-size:22px;line-height:32px;font-weight:300">Herramientas, enlaces de interés, videos y noticias.</p>
   
   
    <form method="get" style="margin-top: 20px; margin-bottom: 40px;">
  <div class="form-group">
    <select id="categoria" name="categoria" class="form-control" onchange="filtrarRecursosPorCategoria(this)" style="max-width:300px">
      <option value="">Todos</option>
      <option value="Herramientas" <?php selected($_GET['categoria'] ?? '', 'Herramientas'); ?>>Herramientas</option>
      <option value="Videos" <?php selected($_GET['categoria'] ?? '', 'Videos'); ?>>Videos</option>
      <option value="Websites" <?php selected($_GET['categoria'] ?? '', 'Websites'); ?>>Websites</option>
      <option value="Artículos/Noticias de terceros" <?php selected($_GET['categoria'] ?? '', 'Artículos/Noticias de terceros'); ?>>Artículos/Noticias de terceros</option>
    </select>
  </div>
</form>          
   
  </div>
</div>
  </div>

<section class="pb-5" style="position:relative;z-index:2"> 
    <div class=" container" >

        <div class="masonry">
<?php
$categoria = isset($_GET['categoria']) ? sanitize_text_field($_GET['categoria']) : '';
mostrar_recursos(-1, $categoria, 4);
?>        </div>  
    </div>



</section>
<script>
  function filtrarRecursosPorCategoria(select) {
    const categoria = select.value;
    const url = new URL(window.location.href);
    if (categoria) {
      url.searchParams.set('categoria', categoria);
    } else {
      url.searchParams.delete('categoria');
    }
    window.location.href = url.toString();
  }
</script>

<?php get_footer(); ?>
