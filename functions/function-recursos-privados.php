<?php

// Registrar el Custom Post Type RECURSOS PRIVADOS
function registrar_cpt_recursos_privados() {
    $labels = array(
        'name' => 'Recursos Privados',
        'singular_name' => 'Recurso Privado',
        'add_new' => 'Agregar nuevo',
        'add_new_item' => 'Agregar nuevo recurso privado',
        'edit_item' => 'Editar recurso privado',
        'new_item' => 'Nuevo recurso privado',
        'view_item' => 'Ver recurso privado',
        'search_items' => 'Buscar recursos privados',
        'not_found' => 'No se encontraron recursos privados',
        'not_found_in_trash' => 'No hay recursos privados en la papelera',
        'menu_name' => 'Recursos Privados'
    );

    $args = array(
        'labels' => $labels,
        'public' => true, // importante para permitir front-end queries, pero lo restringiremos con auth
        'exclude_from_search' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-lock',
        'has_archive' => true,
        'rewrite' => array('slug' => 'recursos-privados'),
        'supports' => array('title', 'editor', 'thumbnail'),
        'show_in_rest' => true
    );

    register_post_type('recurso_privado', $args);
}
add_action('init', 'registrar_cpt_recursos_privados');


// Agregar metabox para campos personalizados en recursos privados
function agregar_campos_personalizados_recurso_privado() {
    add_meta_box(
        'campos_recurso_privado',
        'Información del Recurso Privado',
        'campos_personalizados_recurso_privado_callback',
        'recurso_privado',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'agregar_campos_personalizados_recurso_privado');

function campos_personalizados_recurso_privado_callback($post) {
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
function guardar_campos_personalizados_recurso_privado($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['link_recurso'])) {
        update_post_meta($post_id, '_link_recurso', esc_url_raw($_POST['link_recurso']));
    }

    if (isset($_POST['categoria_recurso'])) {
        update_post_meta($post_id, '_categoria_recurso', sanitize_text_field($_POST['categoria_recurso']));
    }
}
add_action('save_post_recurso_privado', 'guardar_campos_personalizados_recurso_privado');


// Redirigir visitantes no logueados que intentan acceder a recursos privados
function restringir_acceso_recursos_privados() {
    if ( is_singular('recurso_privado') || is_post_type_archive('recurso_privado') ) {
        if ( ! is_user_logged_in() ) {
            wp_redirect( wp_login_url( get_permalink() ) );
            exit;
        }
    }
}
add_action('template_redirect', 'restringir_acceso_recursos_privados');
