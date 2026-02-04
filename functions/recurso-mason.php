<?php
function mostrar_recursos($cantidad = -1, $filtrar_tipo = '', $mostrar_contenido = true, $post_type = 'recurso') {
    $meta_query = array();

    // Si se especifica un tipo de recurso, agregamos un filtro
    if (!empty($filtrar_tipo)) {
        $meta_query[] = array(
            'key'     => '_categoria_recurso',
            'value'   => $filtrar_tipo,
            'compare' => '='
        );
    }

    $args = array(
        'post_type'      => $post_type, // Aquí se usa el post_type dinámico
        'posts_per_page' => $cantidad,
        'post_status'    => 'publish',
        'meta_query'     => $meta_query
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            $titulo        = get_the_title();
            $contenido_raw = get_the_content();
            $contenido     = apply_filters('the_content', $contenido_raw);
            $enlace        = get_post_meta(get_the_ID(), '_link_recurso', true);
            $tipo          = get_post_meta(get_the_ID(), '_categoria_recurso', true); 
            $imagen        = get_the_post_thumbnail_url(get_the_ID(), 'medium');

            // Imagen por tipo (si no hay imagen destacada)
            if (!$imagen) {
                switch ($tipo) {
                    case 'Herramientas':
                        $imagen = get_template_directory_uri() . '/assets/img/01.jpg';
                        break;
                    case 'Videos':
                        $imagen = get_template_directory_uri() . '/assets/img/02.jpg';
                        break;
                    case 'Websites':
                        $imagen = get_template_directory_uri() . '/assets/img/03.jpg';
                        break;
                    case 'Artículos/Noticias de terceros':
                        $imagen = get_template_directory_uri() . '/assets/img/04.jpg';
                        break;
                    default:
                        $imagen = get_template_directory_uri() . '/assets/img/default-placeholder.jpg';
                        break;
                }
            }

            echo '<div class="mason-card" style="height: 100%;position: relative;">';
            echo '  <div class="ai-engine" style="height: 100%;padding-bottom: 60px;">';
            echo '    <h4 style="font-size:16px;font-weight:400;color:gray;text-align:end">' . esc_html($tipo) . '</h4>';
            echo '    <a class="resource-link" href="' . esc_url($enlace) . '" target="_blank">';
            echo '      <div class="ai-header">';
            echo '        <img class="resource-image" src="' . esc_url($imagen) . '" loading="lazy">';
            echo '        <h3 class="resource-title" style="width:100%;margin-top:10px">' . esc_html($titulo) . '</h3>';
            echo '      </div>';
            echo '    </a>'; 

            if ($mostrar_contenido) {
                echo '    <p>' . $contenido . '</p>';
            }

            echo '    <a href="' . esc_url($enlace) . '" class="resource-link-a">Ver más<span class="line-ai-link"></span></a>';
            echo '  </div>';
            echo '</div>';
        }

        wp_reset_postdata();
    } else {
        echo '<p>No hay recursos disponibles para mostrar.</p>';
    }
}
?>
