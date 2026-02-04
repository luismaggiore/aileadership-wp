<?php get_header(); ?>

<section style="padding-top:25vh;padding-bottom:25vh;position:relative;overflow:hidden;position:relative">




    <div class="container text-center mt-5" style="z-index:2">
        <h1 class="split-animated-title">Un espacio para aprender, compartir y reflexionar sobre los desafíos y oportunidades de la inteligencia artificial.</h1>
      
    </div>


</section>

              <?php get_template_part('gradient-2');?>

<section style="position:relative;">
          <?php get_template_part('network-2');?>
<section>
<div class="container py-2">
    <div class="row">
        <div class="col-md-7">
            <div class="card p-4">
<h2>El fenómeno que nos reúne</h2>
<p>
AI Leadership Network es una red de académicos que investiga, reflexiona y colabora para entender mejor el alcance e impacto de la inteligencia artificial en empresas y organizaciones. En nuestro rol de formadores, buscamos aportar a quienes toman decisiones para que puedan distinguir entre lo posible y lo improbable, y lo que aún no está claro. Buscamos aportar claridad, comprensión técnica y pensamiento crítico en un tema nebuloso y cambiante.
.
</p>
</div>
</div>
</div>
</div>
</section>

<section>
<div class="container  py-2">
     <div class="row justify-content-end">
         
        <div class="col-md-7">
            <div class="card p-4">
<h2>Somos una comunidad de práctica y una red académica internacional.</h2>
<p>
Venimos de distintas disciplinas, pero compartimos un entendimiento común: sabemos cómo funcionan las organizaciones y también cómo funcionan los sistemas. Nuestra experiencia proviene de la docencia, la investigación y el trabajo concreto con empresas y organizaciones, desde distintas disciplinas: algunos desde la tecnología, otros desde la estrategia, la innovación o la toma de decisiones. Esa combinación nos permite observar el fenómeno de la inteligencia artificial con mayor profundidad y desde múltiples perspectivas.
</p>
</div>
</div>
</div>
</div>
</section>


<section>

<div class="container  py-2">
         <div class="row">

        <div class="col-md-7">
            <div class="card p-4">
<h2>Nuestra forma de encontrarnos y avanzar.</h2>
<p>
En nuestras aulas conversamos con cientos de profesionales y directivos. Escuchamos sus preguntas, sus decisiones e inquietudes. Ese diálogo constante nos motivó a formar esta red: para apoyarlos mejor, para pensar juntos con más profundidad y para aportar claridad en medio de un fenómeno que avanza más rápido que su comprensión. No venimos a dar certezas, sino a ofrecer herramientas, criterios y análisis que ayuden a distinguir entre lo que la IA hace bien, lo que aún no, y lo que todavía está por entenderse.
</p>
</div>
</div>
</div>
</div>
</section>

<section>
<div class="container py-2">
<div class="row justify-content-end">
<div class="col-md-7 ">
    <div class="card p-4 ">
<h2>Lo que nos caracteriza</h2>
<p>
En esta red trabajamos desde lo que vemos, lo que escuchamos y lo que nos interpela. A veces basta una conversación entre dos colegas para que, días después, surja un artículo que aborda un tema complejo. No seguimos una agenda fija, sino que vamos tras lo que creemos relevante, sabiendo que entender a fondo toma tiempo, pero que entre varias cabezas se avanza más y mejor.
</p>
</div>
</div>
</div>
</div>
</section>
	<section>
<div class="container  py-2">
         <div class="row">

        <div class="col-md-7">
            <div class="card p-4">
<h2>Creemos que, para entender esta tecnología, no basta con leer lo que concluyen otros.</h2>
<p>
Es necesario experimentar, probar herramientas, analizar resultados y discutir sobre lo que sirve y lo que no. Somos conscientes de que lo que sabemos hoy puede quedar obsoleto mañana; sin embargo, eso nos desafía y motiva, porque aprender, desaprender y cuestionar lo que sabemos es nuestra forma de aportar.
</p>
</div>
</div>
</div>
</div>	
	
	


	
</section>


   <section  style="margin: 100px 0px;align-content: center;"> 
        <div class=" container" >
 
        <div class="row mb-3 g-2 justify-content-center">
        
        <div class="col-md-12 col-lg-3" style="height: auto; align-content: center;padding-right: 20px;"> 
          
        <h2 style="font-size:300; font-variant: all-small-caps;letter-spacing: 0.12rem;font-size: 24px;line-height: 22px;" >Las personas detrás</BR> del proyecto</h2>
        <div style="height: 1px;width: 100%;  background: linear-gradient(to right, #FF9E5E, #BA42FF); "></div>
      
        </div>
 
        <?php mostrar_tarjetas_equipo_bootstrap(); ?>
   
 
    
        </div>
        </div>

    </section>

<section class="pt-5">
       <div class=" container" >

      <div class="row mb-5 g-4">

  <?php mostrar_tarjetas_bootstrap('post', 3); ?>

 
   
    </div>  
</div>


    </section>
<?php get_footer(); ?>
