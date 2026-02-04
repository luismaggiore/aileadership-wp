<?php

// Registrar el Custom Post Type TEAM
function registrar_cpt_team() {
    $labels = array(
        'name' => 'Miembros del equipo',
        'singular_name' => 'Miembro del equipo',
        'add_new' => 'Agregar nuevo',
        'add_new_item' => 'Agregar nuevo miembro',
        'edit_item' => 'Editar miembro',
        'new_item' => 'Nuevo miembro',
        'view_item' => 'Ver miembro',
        'search_items' => 'Buscar miembros',
        'not_found' => 'No se encontraron miembros',
        'not_found_in_trash' => 'No hay miembros en la papelera',
        'menu_name' => 'Equipo'
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-groups',
        'has_archive' => true,
        'rewrite' => array('slug' => 'equipo'),
        'supports' => array('title', 'editor', 'thumbnail', 'page-attributes'),
        'show_in_rest' => false
    );

    register_post_type('team', $args);
}
add_action('init', 'registrar_cpt_team');

// Cambiar el placeholder del título
function cambiar_placeholder_titulo_team($title){
    $screen = get_current_screen();
    if ($screen->post_type == 'team') {
        $title = 'Nombre del miembro';
    }
    return $title;
}
add_filter('enter_title_here', 'cambiar_placeholder_titulo_team');

// Agregar metabox de campos personalizados
function agregar_campos_personalizados_team() {
    add_meta_box(
        'info_miembro_equipo',
        'Información del miembro',
        'campos_personalizados_team_callback',
        'team',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'agregar_campos_personalizados_team');

// HTML de los campos personalizados
function campos_personalizados_team_callback($post) {
    $cargo = get_post_meta($post->ID, '_cargo', true);
    $nacionalidad = get_post_meta($post->ID, '_nacionalidad', true);
    $linkedin = get_post_meta($post->ID, '_linkedin', true);
    ?>

    <p>
        <label for="cargo">Cargo:</label><br>
        <input type="text" name="cargo" id="cargo" value="<?php echo esc_attr($cargo); ?>" style="width:100%;">
    </p>
    <p>
        <label for="nacionalidad">Rol:</label><br>
        <input type="text" name="nacionalidad" id="nacionalidad" value="<?php echo esc_attr($nacionalidad); ?>" style="width:100%;">
    </p>
    <p>
        <label for="linkedin">Enlace a LinkedIn:</label><br>
        <input type="url" name="linkedin" id="linkedin" value="<?php echo esc_attr($linkedin); ?>" style="width:100%;">
    </p>

    <?php
}

// Guardar los campos personalizados
function guardar_campos_personalizados_team($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['cargo'])) {
        update_post_meta($post_id, '_cargo', sanitize_text_field($_POST['cargo']));
    }

    if (isset($_POST['nacionalidad'])) {
        update_post_meta($post_id, '_nacionalidad', sanitize_text_field($_POST['nacionalidad']));
    }

    if (isset($_POST['linkedin'])) {
        update_post_meta($post_id, '_linkedin', esc_url_raw($_POST['linkedin']));
    }
}
add_action('save_post', 'guardar_campos_personalizados_team');
