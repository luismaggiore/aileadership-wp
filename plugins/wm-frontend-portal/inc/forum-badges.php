<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Base URL donde guardas las imágenes de insignias.
 * Por defecto apunta a /assets/img/badges/ dentro de tu tema activo.
 * Puedes cambiarlo con el filtro 'wm_forum_badge_base_uri'.
 */
function wm_forum_badge_base_uri() {
    $uri = apply_filters( 'wm_forum_badge_base_uri', get_stylesheet_directory_uri() . '/assets/img/' );
    return trailingslashit( $uri );
}

/**
 * Mapa "rol => imagen".
 * Incluye roles de bbPress y WordPress + fallback '_default'.
 * Puedes editar aquí o, mejor, sobreescribir con el filtro 'wm_forum_badge_map'.
 */
function wm_forum_badge_map() {
    static $map = null;
    if ( $map !== null ) return $map;

    $base = wm_forum_badge_base_uri();

    $map = array(
        // bbPress
        'bbp_keymaster'   => $base . 'moderator.png',
        'bbp_moderator'   => $base . 'moderator.png',
        'bbp_participant' => $base . 'participant.png',
        'bbp_spectator'   => $base . 'participant.png',
        'bbp_blocked'     => $base . 'participant.png',
        // WordPress
        'administrator'   => $base . 'moderator.png',
        'editor'          => $base . 'moderator.png',
        'author'          => $base . 'moderator.png',
        'foro_miembro'    => $base . 'participant.png', // tu rol personalizado
        'subscriber'      => $base . 'participant.png',
        // fallback
        '_default'        => $base . 'participant.png',
    );

    // Permite personalizar desde tema/otro plugin
    $map = apply_filters( 'wm_forum_badge_map', $map );

    return $map;
}

/** Devuelve roles del usuario priorizando el rol de bbPress si existe */
function wm_forum_user_roles( $user_id ) {
    $roles = array();

    if ( function_exists('bbp_get_user_role') ) {
        $bbp_role = bbp_get_user_role( $user_id );
        if ( $bbp_role ) $roles[] = $bbp_role;
    }

    $u = get_user_by( 'id', $user_id );
    if ( $u && ! empty( $u->roles ) ) {
        $roles = array_merge( $roles, $u->roles );
    }

    return array_unique( $roles );
}

/** URL de la insignia para un usuario (según su primer rol mapeado) */
function wm_forum_badge_url( $user_id ) {
    $map = wm_forum_badge_map();

    foreach ( wm_forum_user_roles( $user_id ) as $role ) {
        if ( isset( $map[ $role ] ) && $map[ $role ] ) {
            return esc_url( $map[ $role ] );
        }
    }

    return esc_url( isset( $map['_default'] ) ? $map['_default'] : '' );
}

/** <img> de la insignia (lista para incrustar) */
function wm_forum_badge_img( $user_id, $args = array() ) {
    $defaults = array(
        'class' => 'insignia',
        'style' => 'position:absolute;right:-6px;bottom:-6px;width:28px;height:28px;',
        'alt'   => 'insignia',
        'loading' => 'lazy',
    );
    $args = wp_parse_args( $args, $defaults );

    $src = wm_forum_badge_url( $user_id );
    if ( ! $src ) return '';

    $class   = esc_attr( $args['class'] );
    $style   = esc_attr( $args['style'] );
    $alt     = esc_attr( $args['alt'] );
    $loading = esc_attr( $args['loading'] );

    return sprintf(
        '<img loading="%s" class="%s" src="%s" alt="%s" style="%s">',
        $loading, $class, esc_url( $src ), $alt, $style
    );
}
