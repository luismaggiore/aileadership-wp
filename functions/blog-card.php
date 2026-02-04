<?php
function mostrar_tarjetas_bootstrap($tipo = 'post', $cantidad = 3) {
    $args = array(
        'post_type' => $tipo,
        'posts_per_page' => $cantidad,
        'post_status' => 'publish'
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {

        while ($query->have_posts()) {
            $query->the_post();

            $imagen = get_the_post_thumbnail_url(get_the_ID(), 'medium');
            $titulo = get_the_title();
            $enlace = get_permalink();

            echo '<div class="col-md-4 mb-4">';
            echo '  <div class="blog-post">';
            echo '    <a href="' . esc_url($enlace) . '" class="blog-link">';
            echo '      <div class="blog-pic-container">';
            if ($imagen) {
                echo '        <img class="blog-pic" src="' . esc_url($imagen) . '" alt="' . esc_attr($titulo) . '">';
            } else {
                echo '        <img class="blog-pic" src="' . esc_url(get_template_directory_uri() . "/assets/img/placeholder.jpg") . '" alt="Sin imagen">';
            }
            echo '        <div class="blog-overlay"></div>';
            echo '      </div>';
            echo '    </a>';
            echo '    <h3>' . esc_html($titulo) . '</h3>';
            echo '    <a href="' . esc_url($enlace) . '" class="blog-link-a">Leer más<span class="line-blog-link"></span></a>';
            echo '  </div>';
            echo '</div>';
        }

        wp_reset_postdata();
    } else {
        echo '<p>No hay contenido para mostrar.</p>';
    }
}
?>
