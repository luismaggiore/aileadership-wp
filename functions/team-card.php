<?php
function mostrar_tarjetas_equipo_bootstrap($cantidad = -1) {
    $args = array(
        'post_type' => 'team',
        'posts_per_page' => $cantidad,
        'post_status' => 'publish',
        'orderby' => 'menu_order',
        'order' => 'ASC',
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {

        while ($query->have_posts()) {
            $query->the_post();

            $nombre = get_the_title();
            $cargo = get_post_meta(get_the_ID(), '_cargo', true);
            $linkedin = get_post_meta(get_the_ID(), '_linkedin', true);
            $imagen = get_the_post_thumbnail_url(get_the_ID(), 'medium');

            echo '<div class="col-md-4 col-lg-3 mb-4">';
            echo '  <div class="profile text-center">';
            if ($imagen) {
                echo '    <img class="profile-pic" src="' . esc_url($imagen) . '" alt="' . esc_attr($nombre) . '">';
            } else {
                echo '    <img class="profile-pic" src="' . esc_url(get_template_directory_uri() . "/assets/img/placeholder.jpg") . '" alt="Sin foto">';
            }
            echo '    <h3>' . esc_html($nombre) . '</h3>';
            echo '    <h4>' . esc_html($cargo) . '</h4>';

            if (!empty($linkedin)) {
                echo '    <a class="profile-link" href="' . esc_url($linkedin) . '" target="_blank" rel="noopener">';
                echo '      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 409.6 619.6">';
                echo '        <g data-name="10.Linkedin">';
                echo '          <path class="linkedin-1" d="M409.6,409.6v-150.02c0-73.73-15.87-130.05-101.89-130.05-41.47,0-69.12,22.53-80.38,44.03h-1.02v-37.38h-81.41v273.41h84.99v-135.68c0-35.84,6.66-70.14,50.69-70.14s44.03,40.45,44.03,72.19v133.12h84.99v.51Z"/>';
                echo '          <path class="linkedin-1" d="M6.66,136.19h84.99v273.41H6.66V136.19Z"/>';
                echo '          <path class="linkedin-1" d="M49.15,0C22.02,0,0,22.02,0,49.15s22.02,49.66,49.15,49.66,49.15-22.53,49.15-49.66S76.29,0,49.15,0Z"/>';
                echo '        </g>';
                echo '      </svg>';
                echo '    </a>';
            }

            echo '  </div>';
            echo '</div>';
        }

         wp_reset_postdata();
    } else {
        echo '<p>No hay miembros del equipo para mostrar.</p>';
    }
}
?>
