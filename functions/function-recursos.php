<?php

// Registrar el Custom Post Type RECURSOS
function registrar_cpt_recursos() {
    $labels = array(
        'name' => 'Recursos',
        'singular_name' => 'Recurso',
        'add_new' => 'Agregar nuevo',
        'add_new_item' => 'Agregar nuevo recurso',
        'edit_item' => 'Editar recurso',
        'new_item' => 'Nuevo recurso',
        'view_item' => 'Ver recurso',
        'search_items' => 'Buscar recursos',
        'not_found' => 'No se encontraron recursos',
        'not_found_in_trash' => 'No hay recursos en la papelera',
        'menu_name' => 'Recursos'
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-media-document',
        'has_archive' => true,
        'rewrite' => array('slug' => 'recursos'),
        'supports' => array('title', 'editor', 'thumbnail'),
        'show_in_rest' => true
    );

    register_post_type('recurso', $args);
}
add_action('init', 'registrar_cpt_recursos');

// Agregar metabox para campos personalizados
function agregar_campos_personalizados_recurso() {
    add_meta_box(
        'campos_recurso',
        'Información del Recurso',
        'campos_personalizados_recurso_callback',
        'recurso',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'agregar_campos_personalizados_recurso');

// HTML de los campos personalizados
function campos_personalizados_recurso_callback($post) {
    $link = get_post_meta($post->ID, '_link_recurso', true);
    $categoria = get_post_meta($post->ID, '_categoria_recurso', true);
    ?>
    <p>
        <label for="link_recurso"><strong>Enlace del recurso:</strong></label><br>
        <input type="url" name="link_recurso" id="link_recurso" value="<?php echo esc_url($link); ?>" style="width:100%;">
    </p>
    <p>
        <label for="categoria_recurso"><strong>Categoría:</strong></label><br>
        <select name="categoria_recurso" id="categoria_recurso" style="width:100%;">
            <option value="">Selecciona una categoría</option>
            <option value="Herramientas" <?php selected($categoria, 'Herramientas'); ?>>Herramientas</option>
            <option value="Videos" <?php selected($categoria, 'Videos'); ?>>Videos</option>
            <option value="Websites" <?php selected($categoria, 'Websites'); ?>>Websites</option>
            <option value="Artículos/Noticias de terceros" <?php selected($categoria, 'Artículos/Noticias de terceros'); ?>>Artículos/Noticias de terceros</option>
        </select>
    </p>
    <?php
}

// Guardar los campos personalizados
function guardar_campos_personalizados_recurso($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['link_recurso'])) {
        update_post_meta($post_id, '_link_recurso', esc_url_raw($_POST['link_recurso']));
    }

    if (isset($_POST['categoria_recurso'])) {
        update_post_meta($post_id, '_categoria_recurso', sanitize_text_field($_POST['categoria_recurso']));
    }
}
add_action('save_post', 'guardar_campos_personalizados_recurso');
