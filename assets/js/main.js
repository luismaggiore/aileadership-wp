

 document.addEventListener("DOMContentLoaded", (event) => {
  
  window.addEventListener('scroll', () => {
  document.querySelector('nav')
    .classList.toggle('glass-nav', window.scrollY > 30);
});


gsap.registerPlugin(DrawSVGPlugin,ScrambleTextPlugin,ScrollTrigger,ScrollSmoother,SplitText,TextPlugin);


if (document.querySelector(".split-animated-title")){
document.fonts.ready.then(() =>{
let mySplitText = new SplitText(".split-animated-title", {type:"words,chars"});
let chars = mySplitText.words; //an array of all the divs that wrap each character

gsap.from(chars, {
  duration: 0.8,
  autoAlpha: 0,
  y: -20,
  stagger:{
    amount: 0.2,
    from: "random"
  }
});
})
}

if (document.querySelector(".acordeon-tab")){
const tabs = document.querySelectorAll(".acordeon-tab");

  const defaultGrow = 0.0001
  const activeGrow = 1;

// Establece el primer tab como activo al iniciar
tabs[0].classList.add("active");
gsap.set(tabs[0], { flexGrow: activeGrow });
gsap.set(tabs[0].querySelector('.acordeon-content'), { opacity: 1, y: 0 });

tabs.forEach(tab => {
  tab.addEventListener("mouseenter", () => {
    // Si este tab ya está activo, no hagas nada
    if (tab.classList.contains("active")) return;

    tabs.forEach(t => {
      if (t === tab) {
        t.classList.add("active");

        gsap.to(t, { flexGrow: activeGrow, duration: 0.4, ease: "power2.inOut" });
        gsap.fromTo(
          t.querySelector('.acordeon-content'),
          { opacity: 0, y: 20 },
          { opacity: 1, y: 0, duration: 0.5, delay: 0.2 }
        );
      } else {
        t.classList.remove("active");

        gsap.to(t, { flexGrow: defaultGrow,duration: 0.4, ease: "power2.inOut" });
        gsap.to(
          t.querySelector('.acordeon-content'),
          { opacity: 0, y: 20, duration: 0.3 }
        );
      }
    });
  });
});
}

if (document.querySelector(".blog-link")){
  const blogLink = document.querySelectorAll(".blog-link");
const blogOverlay = document.querySelectorAll(".blog-overlay");
const blogPics = document.querySelectorAll(".blog-pic");
const blogButton = document.querySelectorAll(".blog-link-a");
const blogLine = document.querySelectorAll(".line-blog-link");

  blogLink.forEach((button,index) =>{
    button.addEventListener("mouseenter", () => {
    gsap.to(blogPics[index], {scale: 1.1, duration: 0.3, ease: "power2.inOut"});
    gsap.fromTo(blogOverlay[index], {opacity: 0}, {opacity: 1, duration: 0.3, ease: "power2.inOut"});  
        gsap.fromTo(blogLine[index], {width: "70px"}, {width: "100%", duration: 0.05, ease: "power2.in"});  

    });
    button.addEventListener("mouseleave", () => {
    gsap.to(blogPics[index], {scale: 1, duration: 0.3, ease: "power2.inOut"});
    gsap.fromTo(blogOverlay[index], {opacity: 1}, {opacity: 0, duration: 0.3, ease: "power2.inOut"});  
    gsap.fromTo(blogLine[index], {width: "100%"}, {width: "70px", duration: 0.05, ease: "power2.in"});  


    });

  })

    blogButton.forEach((button,index) =>{
    button.addEventListener("mouseenter", () => {
    gsap.to(blogPics[index], {scale: 1.1, duration: 0.3, ease: "power2.inOut"});
    gsap.fromTo(blogOverlay[index], {opacity: 0}, {opacity: 1, duration: 0.3, ease: "power2.inOut"});  
    gsap.fromTo(blogLine[index], {width: "70px"}, {width: "100%", duration: 0.05, ease: "power2.in"});  

    });
    button.addEventListener("mouseleave", () => {
    gsap.to(blogPics[index], {scale: 1, duration: 0.3, ease: "power2.inOut"});
    gsap.fromTo(blogOverlay[index], {opacity: 1}, {opacity: 0, duration: 0.3, ease: "power2.inOut"});  
        gsap.fromTo(blogLine[index], {width: "100%"}, {width: "70px", duration: 0.05, ease: "power2.in"});  
  
    });

  })

  gsap.from(".blog-post",{
    y:20,
    autoAlpha:0,
    ease:"sine.inOut",
    stagger:0.1 

  })


}



    // Seleccionar el h1 y obtener el texto limpio

if (document.querySelector(".animated-title")){
  gsap.fromTo(".animated-title", 
    { y: -50, autoAlpha: 0 }, 
    {  y: 0, autoAlpha: 1, duration: 1, ease: "power2.inOut" } 
  )
}

if (document.querySelector(".gradient-svg")){
    // Respetar accesibilidad
  if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const nodes = document.querySelectorAll('.gradient-node');

    // Utilidades
    const rnd = gsap.utils.random;
    const dur = () => rnd(0.5, 1);          // duración suave
    const dx  = () => rnd(-511, 511, 1);     // rango sutil X
    const dy  = () => rnd(-1, 1, 1);     // rango sutil Y
    const rot = () => rnd(-8, 8, 0.1);     // rotación leve

    nodes.forEach(el => {
      // Asegura que la rotación sea sobre su propio centro
      gsap.set(el, { transformBox: "fill-box", transformOrigin: "50% 50%" });

      // Arranca en una posición muy leve aleatoria para evitar sincronía perfecta
      gsap.set(el, { x: dx(), y: dy(), rotation: rot() });

      // Bucle recursivo: siempre va hacia un nuevo destino dentro del rango
      const wander = () => {
        gsap.to(el, {
          x: dx(),
          y: dy(),
          rotation: rot(),
          ease: "sine.inOut",
          duration: dur(),
          onComplete: wander
        });
      };
      wander();
    });
  }

}


if (document.querySelector(".network-graphic")){

  const circles = Array.from(document.querySelectorAll("circle"));
  const lines = Array.from(document.querySelectorAll("line"));
  const polygons = Array.from(document.querySelectorAll("polygon"));

  // Mapa de puntos
  const points = {};
  circles.forEach(circle => {
    points[circle.id] = {
      el: circle,
      original: {
        cx: parseFloat(circle.getAttribute("cx")),
        cy: parseFloat(circle.getAttribute("cy")),
        r: parseFloat(circle.getAttribute("r"))
      }
    };
   

  });

  // Animar cada círculo
  Object.values(points).forEach(p => {
    const animate = () => {
      gsap.to(p.el, {
        duration: 1 + Math.random() * 2,
        repeat: 0,
        ease:"power2.inOut",
        yoyo: true,
        attr: {
          cx: p.original.cx + (Math.random() * 40 - 15),
          cy: p.original.cy + (Math.random() * 40 - 15),
          r: p.original.r + (Math.random() * 1   )
        },
        onUpdate: updateConnections,
        onComplete: animate
      });
    };
    animate();
  });

  // Actualiza líneas y polígonos dinámicamente
  function updateConnections() {
    // Actualizar líneas
    lines.forEach(line => {
      const fromId = line.dataset.from;
      const toId = line.dataset.to;

      const from = points[fromId]?.el;
      const to = points[toId]?.el;

      if (from && to) {
        line.setAttribute("x1", from.getAttribute("cx"));
        line.setAttribute("y1", from.getAttribute("cy"));
        line.setAttribute("x2", to.getAttribute("cx"));
        line.setAttribute("y2", to.getAttribute("cy"));
      }
    });

  
  }

  gsap.from(".line-appear", {delay:0.5,duration:2,stagger: 0.005,ease:"power2.inOut", drawSVG: 0});
gsap.from("circle", {duration:0.5,stagger: 0.001,ease:"power2.inOut",scale:0.1,transformOrigin:"center"});

}

if (document.querySelector(".left-reveal")){
gsap.from(".left-reveal", {
  duration:1,
  stagger: 0.2,
  ease:"power2.inOut",
  x:-20,
  autoAlpha:0,
  scrollTrigger: {
  trigger: ".left-reveal",
  start: "top 80%",
  toggleActions: "play none none none"
 }
});
}

if (document.querySelector(".split-animated")){
document.fonts.ready.then(() =>{
let mySplitText = new SplitText(".split-animated", {type:"words,chars"});
let chars = mySplitText.words; //an array of all the divs that wrap each character

gsap.from(chars, {
  duration: 0.8,
  autoAlpha: 0,
  y: -20,
  stagger:{
    amount: 0.2,
    from: "random"
  },
  scrollTrigger: {
  trigger: ".split-animated",
  start: "top 80%",
  toggleActions: "play none none none"
 }
});
})
}

if (document.querySelector(".ai-engine")){

    gsap.from(".ai-engine",{
    y:20,
    autoAlpha:0,
    ease:"sine.inOut",
    stagger:0.1 

  })

  let images = document.querySelectorAll('.resource-link');
  let line = document.querySelectorAll('.line-ai-link');
  let title = document.querySelectorAll('.resource-title');
  let resourceButton = document.querySelectorAll('.resource-link-a');
  let avatar = document.querySelectorAll('.resource-image');
  images.forEach((image, index) => {
    image.addEventListener('mouseenter', () => {
    gsap.fromTo(line[index], {width: "70px"}, {width: "0%", duration: 0.03, ease: "power2.in"});  
    gsap.to(avatar[index], {scale: 1.2, duration: 0.3, ease: "power2.inOut"});
    gsap.to(resourceButton[index], {color:"#c54bca", duration: 0.3, ease: "power2.inOut"});
    gsap.to(title[index], {x:10, duration: 0.3, ease: "power2.inOut"});
    });

    image.addEventListener('mouseleave', () => {
      gsap.to(avatar[index], {scale: 1, duration: 0.3, ease: "power2.inOut"});
      gsap.fromTo(line[index], {width: "0%"}, {width: "70px", duration: 0.03, ease: "power2.in"});  
      gsap.to(resourceButton[index], {color:"#333", duration: 0.3, ease: "power2.inOut"});
          gsap.to(title[index], {x:0, duration: 0.3, ease: "power2.inOut"});

    });
  });

    resourceButton.forEach((button,index) =>{
    button.addEventListener("mouseenter", () => {
    gsap.to(button, {color:"#c54bca", duration: 0.3, ease: "power2.inOut"});     
    gsap.fromTo(line[index], {width: "70px"}, {width: "0%", duration: 0.03, ease: "power2.in"});  
    gsap.to(avatar[index], {scale: 1.2, duration: 0.3, ease: "power2.inOut"});
        gsap.to(title[index], {x:10, duration: 0.3, ease: "power2.inOut"});


    });
    button.addEventListener("mouseleave", () => {
        gsap.to(button, {color:"#333", duration: 0.3, ease: "power2.inOut"});     

        gsap.fromTo(line[index], {width: "0%"}, {width: "70px", duration: 0.03, ease: "power2.in"});  
        gsap.to(avatar[index], {scale: 1, duration: 0.3, ease: "power2.inOut"}); 
            gsap.to(title[index], {x:0, duration: 0.3, ease: "power2.inOut"});

  
    });

  })








}


if(document.querySelector(".tab-sm")){

  const buttons = document.querySelectorAll(".tab-button")
  const tabs = document.querySelectorAll(".tab-sm")

  buttons.forEach((button,index)=>{

    button.addEventListener("click",function(){
    buttons.forEach(button=>button.classList.remove("active"))  
    this.classList.add("active") 
    tabs.forEach(tab=>tab.classList.remove("active"))
    tabs[index].classList.add("active")  

    })



  })







}


if(document.querySelector(".form-accordeon")){

  let button = document.querySelector(".btn-new-topic");
  let accordeon = document.querySelector(".form-accordeon");
  
  button.addEventListener("click",()=>{
    console.log("toggle")
    accordeon.classList.toggle("active")

  })



}


   });
  // Aplicar efecto scramble
