<?php
/**
 * CPT: Próximos Eventos
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Registrar el Custom Post Type "evento"
function registrar_cpt_eventos() {
    $labels = array(
        'name'               => 'Próximos Eventos',
        'singular_name'      => 'Evento',
        'add_new'            => 'Agregar nuevo',
        'add_new_item'       => 'Agregar nuevo evento',
        'edit_item'          => 'Editar evento',
        'new_item'           => 'Nuevo evento',
        'view_item'          => 'Ver evento',
        'search_items'       => 'Buscar eventos',
        'not_found'          => 'No se encontraron eventos',
        'not_found_in_trash' => 'No hay eventos en la papelera',
        'menu_name'          => 'Eventos'
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_icon'          => 'dashicons-calendar-alt',
        'has_archive'        => true,
        'rewrite'            => array( 'slug' => 'eventos' ),
        'supports'           => array( 'title', 'editor', 'thumbnail' ),
        'show_in_rest'       => true,
    );

    register_post_type( 'evento', $args );
}
add_action( 'init', 'registrar_cpt_eventos' );

// Agregar metabox de fecha del evento
function agregar_metabox_fecha_evento() {
    add_meta_box(
        'fecha_evento',
        'Fecha del Evento',
        'campo_fecha_evento_callback',
        'evento',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'agregar_metabox_fecha_evento' );

// Mostrar el campo tipo fecha
function campo_fecha_evento_callback( $post ) {
    $fecha = get_post_meta( $post->ID, '_fecha_evento', true );
    ?>
    <label for="fecha_evento_input">Selecciona la fecha:</label><br>
    <input type="date" id="fecha_evento_input" name="fecha_evento_input" value="<?php echo esc_attr( $fecha ); ?>" style="width:100%;max-width:300px;">
    <?php
}

// Guardar la fecha al guardar el post
function guardar_fecha_evento( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    if ( isset( $_POST['fecha_evento_input'] ) ) {
        update_post_meta( $post_id, '_fecha_evento', sanitize_text_field( $_POST['fecha_evento_input'] ) );
    }
}
add_action( 'save_post_evento', 'guardar_fecha_evento' );
