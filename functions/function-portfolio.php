<?php
 
 function registrar_cpt_portfolio() {
    $labels = array(
        'name' => 'Portafolio',
        'singular_name' => 'Proyecto',
        'add_new' => 'Agregar nuevo',
        'add_new_item' => 'Agregar nuevo proyecto',
        'edit_item' => 'Editar proyecto',
        'new_item' => 'Nuevo proyecto',
        'view_item' => 'Ver proyecto',
        'search_items' => 'Buscar proyectos',
        'not_found' => 'No se encontraron proyectos',
        'not_found_in_trash' => 'No hay proyectos en la papelera',
        'menu_name' => 'Portafolio'
        
    );
    
     $args = array( 
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-excerpt-view',
        'has_archive' => true,
        'rewrite' => array('slug' => 'portafolio'),
        'supports' => array('title', 'editor', 'thumbnail'),
        'show_in_rest' => false
    );

    register_post_type('portfolio', $args);
    register_taxonomy_for_object_type('category', 'portfolio');
    register_taxonomy_for_object_type('post_tag', 'portfolio');
 }

add_action('init', 'registrar_cpt_portfolio');

function agregar_galeria_personalizada_portfolio() {
    add_meta_box(
        'galeria_personalizada',
        'Galería del Proyecto',
        'render_galeria_personalizada_callback',
        'portfolio',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'agregar_galeria_personalizada_portfolio');

function render_galeria_personalizada_callback($post) {
    $cliente = get_post_meta($post->ID, '_cliente', true);
    $fecha = get_post_meta($post->ID, '_fecha', true);
    $galeria = get_post_meta($post->ID, '_galeria_portafolio', true);
    $galeria = is_array($galeria) ? $galeria : [];

    
    wp_nonce_field('guardar_galeria_portafolio', 'galeria_portafolio_nonce');

    echo '<button type="button" class="button agregar-imagen-galeria">Agregar imágenes</button>';
    echo '<ul id="lista-galeria" style="margin-top:15px;">';

    foreach ($galeria as $index => $item) {
        echo '<li style="margin-bottom:20px;border:1px solid #ccc;padding:10px;">';
        echo '<img src="' . esc_url($item['url']) . '" style="max-width:150px;"><br>';

        echo '<input type="hidden" name="galeria_portafolio[' . $index . '][url]" value="' . esc_url($item['url']) . '">';

        foreach (['titulo', 'subtitulo', 'descripcion'] as $campo) {
            $key = 'mostrar_' . $campo;
            $checked = !empty($item[$key]) ? 'checked' : '';
            echo '<label><input type="checkbox" name="galeria_portafolio[' . $index . '][' . $key . ']" ' . $checked . '> Mostrar ' . ucfirst($campo) . '</label><br>';
        }

        $layout = isset($item['layout']) ? $item['layout'] : '';
        echo '<label>Layout: ';
        echo '<select name="galeria_portafolio[' . $index . '][layout]">';
        foreach (['layout-1', 'layout-2', 'layout-3', 'layout-4'] as $option) {
            $selected = ($layout === $option) ? 'selected' : '';
            echo '<option value="' . $option . '" ' . $selected . '>' . ucfirst($option) . '</option>';
        }
        echo '</select></label>';

        echo '</li>';
    }

    echo '</ul>';

    ?>
     <p>
        <label for="cliente">Cliente:</label><br>
        <input type="text" name="cliente" id="cliente" value="<?php echo esc_attr($cliente); ?>" style="width:100%;">
    </p>
    <p>
        <label for="fecha">Fecha:</label><br>
        <input type="text" name="fecha" id="fecha" value="<?php echo esc_attr($fecha); ?>" style="width:100%;">
    </p>
 
    <script>
    jQuery(document).ready(function($){
        $('.agregar-imagen-galeria').on('click', function(e){
            e.preventDefault();
            var frame = wp.media({
                title: 'Seleccionar imágenes',
                multiple: true,
                library: { type: 'image' },
                button: { text: 'Usar estas imágenes' }
            });

            frame.on('select', function(){
                var images = frame.state().get('selection').toJSON();
                var lista = $('#lista-galeria');
                var count = lista.children().length;

                images.forEach(function(img, i){
                    var index = count + i;
                    var html = `
                        <li style="margin-bottom:20px;border:1px solid #ccc;padding:10px;">
                            <img src="${img.url}" style="max-width:150px;"><br>
                            <input type="hidden" name="galeria_portafolio[${index}][url]" value="${img.url}">
                            <label><input type="checkbox" name="galeria_portafolio[${index}][mostrar_titulo]" checked> Mostrar Título</label><br>
                            <label><input type="checkbox" name="galeria_portafolio[${index}][mostrar_subtitulo]"> Mostrar Subtítulo</label><br>
                            <label><input type="checkbox" name="galeria_portafolio[${index}][mostrar_descripcion]"> Mostrar Descripción</label><br>
                            <label>Layout:
                                <select name="galeria_portafolio[${index}][layout]">
                                    <option value="layout-1">Layout-1</option>
                                    <option value="layout-2">Layout-2</option>
                                    <option value="layout-3">Layout-3</option>
                                    <option value="layout-4">Layout-4</option>
                                </select>
                            </label>
                        </li>`;
                    lista.append(html);
                });
            });

            frame.open();
        });
    });
    </script>
    <?php
}

function guardar_galeria_personalizada_portfolio($post_id) {
    if (!isset($_POST['galeria_portafolio_nonce']) || !wp_verify_nonce($_POST['galeria_portafolio_nonce'], 'guardar_galeria_portafolio')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
       if (isset($_POST['cliente'])) {
        update_post_meta($post_id, '_cliente', sanitize_text_field($_POST['cliente']));
    }

    if (isset($_POST['fecha'])) {
        update_post_meta($post_id, '_fecha', sanitize_text_field($_POST['fecha']));
    }


    $galeria = $_POST['galeria_portafolio'] ?? [];
    $limpia = [];

    foreach ($galeria as $item) {
        if (empty($item['url'])) continue;

        $limpia[] = [
            'url' => esc_url_raw($item['url']),
            'mostrar_titulo' => isset($item['mostrar_titulo']),
            'mostrar_subtitulo' => isset($item['mostrar_subtitulo']),
            'mostrar_descripcion' => isset($item['mostrar_descripcion']),
            'layout' => sanitize_text_field($item['layout'] ?? 'layout-1')
        ];
    }

    update_post_meta($post_id, '_galeria_portafolio', $limpia);
}
add_action('save_post', 'guardar_galeria_personalizada_portfolio');

/* 
====================================
= CÓMO USAR LOS CAMPOS PERSONALIZADOS DE LA GALERÍA =
====================================

En tu plantilla (por ejemplo, single-portfolio.php), puedes acceder a la galería con:

    $galeria = get_post_meta(get_the_ID(), '_galeria_portafolio', true);

Esto te devuelve un array. Cada elemento del array es una imagen con la siguiente estructura:

    [
        'url' => (string) URL de la imagen,
        'mostrar_titulo' => (bool) true o false,
        'mostrar_subtitulo' => (bool) true o false,
        'mostrar_descripcion' => (bool) true o false,
        'layout' => (string) layout-1 | layout-2 | layout-3 | layout-4
    ]

Puedes hacer un loop así:

    <?php
    $galeria = get_post_meta(get_the_ID(), '_galeria_portafolio', true);
    if (!empty($galeria)) :
        foreach ($galeria as $item) :
            $url = esc_url($item['url']);
            $layout = esc_attr($item['layout']);
            $show_title = !empty($item['mostrar_titulo']);
            $show_subtitle = !empty($item['mostrar_subtitulo']);
            $show_description = !empty($item['mostrar_descripcion']);
    ?>
        <div class="card <?php echo $layout; ?>">
            <img src="<?php echo $url; ?>" class="card-img-top">
            <div class="card-body">
                <?php if ($show_title): ?>
                    <h5 class="card-title">Aquí va el título</h5>
                <?php endif; ?>

                <?php if ($show_subtitle): ?>
                    <h6 class="card-subtitle mb-2 text-muted">Aquí va el subtítulo</h6>
                <?php endif; ?>

                <?php if ($show_description): ?>
                    <p class="card-text">Aquí va la descripción</p>
                <?php endif; ?>
            </div>
        </div>
    <?php
        endforeach;
    endif;
    ?>

Puedes personalizar los estilos o clases según el valor de `$layout`.

Ejemplo:
- layout-1: Imagen grande con texto abajo
- layout-2: Imagen izquierda, texto derecha
- layout-3: Tarjeta con fondo oscuro
- layout-4: Estilo horizontal

Puedes usar estas clases para aplicar diseño desde tu CSS:
    .layout-1, .layout-2, .layout-3, .layout-4

O también usar una función switch

<?php
switch ($layout) {
    case 'layout-1':
        // Imagen arriba, texto abajo
        echo '<div class="card layout-1">';
        echo '<img src="' . $url . '">';
        echo '<div class="text">Contenido abajo</div>';
        echo '</div>';
        break;

    case 'layout-2':
        // Imagen a la izquierda, texto a la derecha
        echo '<div class="row layout-2">';
        echo '<div class="col-md-6"><img src="' . $url . '"></div>';
        echo '<div class="col-md-6">Texto</div>';
        echo '</div>';
        break;

    case 'layout-3':
        // Fondo oscuro, texto blanco
        echo '<div class="card bg-dark text-white layout-3">';
        echo '<img src="' . $url . '" class="card-img">';
        echo '<div class="card-img-overlay">Texto sobre la imagen</div>';
        echo '</div>';
        break;

    case 'layout-4':
        // Otro diseño especial
        echo '<div class="custom-layout-4">';
        echo '<img src="' . $url . '"><div>Otro diseño</div>';
        echo '</div>';
        break;
}
?>

====================================
*/
