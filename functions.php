<?php
// Definir versión del tema para cache busting
define('MI_TEMA_VERSION', '1.0.0');

// Activar soporte para características del tema
add_action('after_setup_theme', function() {
    add_theme_support('post-thumbnails'); // Imágenes destacadas
    add_theme_support('title-tag');       // <title> automático
    add_theme_support('custom-logo');     // Soporte para logo personalizable
    add_theme_support('html5', array(
        'search-form', 'comment-form', 'comment-list', 'gallery', 'caption'
    ));

    // Tamaños de imagen personalizados

    // Cargar traducciones
    load_theme_textdomain('mi-tema-bootstrap', get_template_directory() . '/languages');
});

// Incluir archivos modulares
require_once get_template_directory() . '/functions/function-team.php';
require_once get_template_directory() . '/functions/blog-card.php';
require_once get_template_directory() . '/functions/team-card.php';
require_once get_template_directory() . '/functions/function-recursos.php';
require_once get_template_directory() . '/functions/function-recursos-privados.php';
require_once get_template_directory() . '/functions/recurso-card.php';
require_once get_template_directory() . '/functions/recurso-mason.php';
// functions.php (del theme o child theme)
require_once get_stylesheet_directory() . '/functions/wm-user-favorites.php';
require_once get_stylesheet_directory() . '/functions/wp-favorites-preview.php';
require_once get_stylesheet_directory() . '/functions/wm-subscriptions-preview.php';
require_once get_stylesheet_directory() . '/functions/wm-user-subscriptions.php';
require_once get_stylesheet_directory() . '/functions/wm-trending-tags.php';
require_once get_stylesheet_directory() . '/functions/wm-user-participations.php';
require_once get_stylesheet_directory() . '/functions/wm-recommended-topics.php';
require_once get_template_directory() . '/functions/cpt-eventos.php';


/**
 * Customizer: CTA para landing page
 */
add_action('customize_register', function( $wp_customize ) {

    // Sección
    $wp_customize->add_section('MI_TEMA_VERSION_landing_cta_section', [
        'title'       => __('CTA Landing Page', 'MI_TEMA_VERSION'),
        'priority'    => 40,
        'description' => __('Configura el destino y comportamiento del botón CTA del landing.', 'MI_TEMA_VERSION'),
    ]);

    // --- Fuente del link: página o URL personalizada ---
    $wp_customize->add_setting('MI_TEMA_VERSION_landing_cta_source', [
        'default'           => 'page',
        'sanitize_callback' => function( $value ) {
            return in_array($value, ['page','custom'], true) ? $value : 'page';
        },
        'transport'         => 'refresh',
    ]);
    $wp_customize->add_control('MI_TEMA_VERSION_landing_cta_source', [
        'type'     => 'radio',
        'section'  => 'MI_TEMA_VERSION_landing_cta_section',
        'label'    => __('Fuente del enlace', 'MI_TEMA_VERSION'),
        'choices'  => [
            'page'   => __('Usar una página del sitio', 'MI_TEMA_VERSION'),
            'custom' => __('Usar URL personalizada', 'MI_TEMA_VERSION'),
        ],
    ]);

    // --- Selector de página existente ---
    $wp_customize->add_setting('MI_TEMA_VERSION_landing_cta_page', [
        'default'           => 0,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ]);
    $wp_customize->add_control(new WP_Customize_Control(
        $wp_customize,
        'MI_TEMA_VERSION_landing_cta_page',
        [
            'type'     => 'dropdown-pages',
            'section'  => 'MI_TEMA_VERSION_landing_cta_section',
            'label'    => __('Página destino', 'MI_TEMA_VERSION'),
            'active_callback' => function() use ( $wp_customize ) {
                return get_theme_mod('MI_TEMA_VERSION_landing_cta_source', 'page') === 'page';
            },
        ]
    ));

    // --- URL personalizada ---
    $wp_customize->add_setting('MI_TEMA_VERSION_landing_cta_custom_url', [
        'default'           => '',
        'sanitize_callback' => function( $url ) { return esc_url_raw( trim($url) ); },
        'transport'         => 'refresh',
    ]);
    $wp_customize->add_control('MI_TEMA_VERSION_landing_cta_custom_url', [
        'type'            => 'url',
        'section'         => 'MI_TEMA_VERSION_landing_cta_section',
        'label'           => __('URL personalizada', 'MI_TEMA_VERSION'),
        'description'     => __('Incluye https:// o http://', 'MI_TEMA_VERSION'),
        'input_attrs'     => ['placeholder' => 'https://...'],
        'active_callback' => function() {
            return get_theme_mod('MI_TEMA_VERSION_landing_cta_source', 'page') === 'custom';
        },
    ]);

    // --- Comportamiento de apertura (target) ---
    $wp_customize->add_setting('MI_TEMA_VERSION_landing_cta_target', [
        'default'           => '_self',
        'sanitize_callback' => function( $value ) {
            return in_array($value, ['_self','_blank'], true) ? $value : '_self';
        },
        'transport'         => 'refresh',
    ]);
    $wp_customize->add_control('MI_TEMA_VERSION_landing_cta_target', [
        'type'     => 'radio',
        'section'  => 'MI_TEMA_VERSION_landing_cta_section',
        'label'    => __('Apertura del enlace', 'MI_TEMA_VERSION'),
        'choices'  => [
            '_self'  => __('Misma pestaña', 'MI_TEMA_VERSION'),
            '_blank' => __('Nueva pestaña', 'MI_TEMA_VERSION'),
        ],
    ]);
});

/**
 * Helpers para usar en el template
 */
if ( ! function_exists('MI_TEMA_VERSION_get_landing_cta_url') ) {
    function MI_TEMA_VERSION_get_landing_cta_url() {
        $source = get_theme_mod('MI_TEMA_VERSION_landing_cta_source', 'page');

        if ( $source === 'custom' ) {
            $url = trim( (string) get_theme_mod('MI_TEMA_VERSION_landing_cta_custom_url', '') );
            if ( $url ) {
                return esc_url( $url );
            }
        } else {
            $page_id = absint( get_theme_mod('MI_TEMA_VERSION_landing_cta_page', 0) );
            if ( $page_id ) {
                $permalink = get_permalink( $page_id );
                if ( $permalink ) {
                    return esc_url( $permalink );
                }
            }
        }

        // Fallback: home
        return esc_url( home_url('/') );
    }
}

if ( ! function_exists('MI_TEMA_VERSION_get_landing_cta_target') ) {
    function MI_TEMA_VERSION_get_landing_cta_target() {
        $target = get_theme_mod('MI_TEMA_VERSION_landing_cta_target', '_self');
        return in_array($target, ['_self','_blank'], true) ? $target : '_self';
    }
}

/**
 * (Opcional) Helper para rel seguro cuando target=_blank
 */
if ( ! function_exists('MI_TEMA_VERSION_get_landing_cta_rel') ) {
    function MI_TEMA_VERSION_get_landing_cta_rel() {
        return ( MI_TEMA_VERSION_get_landing_cta_target() === '_blank' ) ? 'noopener noreferrer' : '';
    }
}



// Puedes agregar más como: functions-gallery.php, etc.

// Cargar Bootstrap y scripts personalizados
function mi_tema_scripts() {
    // Estilos
    wp_enqueue_style('bootstrap-css', get_template_directory_uri() . '/assets/css/bootstrap.min.css', array(), MI_TEMA_VERSION);
    wp_enqueue_style('main-css', get_stylesheet_uri(), array(), MI_TEMA_VERSION);

    // Scripts
    wp_enqueue_script('main-js', get_template_directory_uri() . '/assets/js/main.js', array(), MI_TEMA_VERSION, true);
}
add_action('wp_enqueue_scripts', 'mi_tema_scripts');

// Registrar menús
register_nav_menus(array(
    'menu-principal' => __('Menú Principal', 'mi-tema-bootstrap')
));

register_nav_menus(array(
    'menu-exclusivo' => __('Menú Exclusivo', 'mi-tema-bootstrap')
));

// Registrar sidebar
function mi_tema_widgets_init() {
    register_sidebar(array(
        'name'          => __('Sidebar Principal', 'mi-tema-bootstrap'),
        'id'            => 'sidebar-1',
        'before_widget' => '<div class="mb-4">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ));
}
add_action('widgets_init', 'mi_tema_widgets_init');

// Cargar navwalker para menú Bootstrap
require_once get_template_directory() . '/class-wp-bootstrap-navwalker.php';


// Agrega campo de imagen al perfil del usuario
add_action('admin_enqueue_scripts', function( $hook ){
    if ( $hook === 'profile.php' || $hook === 'user-edit.php' ) {
        wp_enqueue_media(); // necesario para wp.media
        wp_enqueue_script( 'jquery' );
    }
});

// 2) Campo en el perfil (admin)
function agregar_foto_personalizada_perfil( $user ) {
    $url = esc_attr( get_user_meta( $user->ID, 'profile_picture', true ) );
    ?>
    <h3>Imagen de perfil personalizada</h3>
    <table class="form-table">
        <tr>
            <th><label for="profile_picture">Imagen</label></th>
            <td>
                <input type="text" name="profile_picture" id="profile_picture" value="<?php echo $url; ?>" class="regular-text" />
                <button type="button" class="button" id="upload_profile_picture">Subir imagen</button>
                <p class="description">URL de la imagen o usa el botón para subir desde la galería.</p>
            </td>
        </tr>
    </table>

    <script>
    jQuery(function($){
        var frame;
        $('#upload_profile_picture').on('click', function(e){
            e.preventDefault();

            // Reusar el frame si ya existe
            if (frame) {
                frame.open();
                return;
            }

            frame = wp.media({
                title: 'Seleccionar Imagen',
                library: { type: 'image' },
                multiple: false,
                button: { text: 'Usar esta imagen' }
            });

            frame.on('select', function(){
                var attachment = frame.state().get('selection').first().toJSON();
                $('#profile_picture').val(attachment.url);
            });

            frame.open();
        });
    });
    </script>
    <?php
}
add_action('show_user_profile', 'agregar_foto_personalizada_perfil');
add_action('edit_user_profile',  'agregar_foto_personalizada_perfil');

// 3) Guardar el campo
function guardar_foto_personalizada_perfil( $user_id ) {
    if ( ! current_user_can('edit_user', $user_id) ) return;
    if ( isset($_POST['profile_picture']) ) {
        update_user_meta( $user_id, 'profile_picture', esc_url_raw( $_POST['profile_picture'] ) );
    }
}
add_action('personal_options_update', 'guardar_foto_personalizada_perfil');
add_action('edit_user_profile_update','guardar_foto_personalizada_perfil');

function mi_formato_comentario($comment, $args, $depth) {
    ?>
    <li <?php comment_class('media mb-4'); ?> id="comment-<?php comment_ID(); ?>">
        <div class="media d-flex align-items-start">
            <img class="mr-3 rounded-circle" src="<?php echo esc_url(get_avatar_url($comment, ['size' => 60])); ?>" alt="Avatar">
            <div class="media-body">
                <h5 class="mt-0 mb-1"><?php comment_author(); ?></h5>
                <small class="text-muted"><?php comment_date(); ?> a las <?php comment_time(); ?></small>
                <div class="comment-text mt-2">
                    <?php comment_text(); ?>
                </div>
                <?php
                if ($comment->comment_approved == '0') {
                    echo '<p class="text-warning">Tu comentario está esperando moderación.</p>';
                }
                ?>
                <div class="reply mt-2">
                    <?php comment_reply_link(array_merge($args, array(
                        'reply_text' => 'Responder',
                        'depth' => $depth,
                        'max_depth' => $args['max_depth']
                    ))); ?>
                </div>
            </div>
        </div>
    </li>
    <?php
}


/**
 * Avatar personalizado basado en user_meta('profile_picture') en TODO el sitio.
 * - Reemplaza la URL del avatar (get_avatar_url).
 * - Como refuerzo, reemplaza también el <img> completo (pre_get_avatar).
 * - Fallback local opcional si no hay foto personalizada.
 */

// RUTA DEL AVATAR LOCAL POR DEFECTO (opcional)
function wm_default_local_avatar_url() {
    // pon tu archivo en /wp-content/themes/tu-tema/assets/img/avatar-default.png
    return get_stylesheet_directory_uri() . '/assets/img/avatar-default.png';
}

/** Helper: obtener WP_User desde id/email/objeto comentario */
function wm_resolve_user_from_mixed( $id_or_email ) {
    if ( is_numeric( $id_or_email ) ) {
        return get_user_by( 'id', (int) $id_or_email );
    }
    if ( is_object( $id_or_email ) ) {
        // comentario
        if ( ! empty( $id_or_email->user_id ) ) {
            return get_user_by( 'id', (int) $id_or_email->user_id );
        }
        if ( ! empty( $id_or_email->comment_author_email ) ) {
            return get_user_by( 'email', $id_or_email->comment_author_email );
        }
    }
    if ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
        return get_user_by( 'email', $id_or_email );
    }
    return false;
}

/** 1) Cambiar la URL del avatar globalmente */
add_filter( 'get_avatar_url', function( $url, $id_or_email, $args ) {
    $user = wm_resolve_user_from_mixed( $id_or_email );
    if ( $user ) {
        $custom = get_user_meta( $user->ID, 'profile_picture', true );
        if ( $custom ) {
            return esc_url( $custom );
        }
        // Si NO quieres Gravatar nunca, devuelve un avatar local:
        // return wm_default_local_avatar_url();
    }
    // Si quieres desactivar Gravatar para TODOS los demás (anónimos, sin user), descomenta:
    // return wm_default_local_avatar_url();

    return $url; // deja el comportamiento por defecto (Gravatar) para quien no tenga foto
}, 10, 3 );

/** 2) Como refuerzo, reemplazar el <img …> entero (algunos plugins ignoran get_avatar_url) */
add_filter( 'pre_get_avatar', function( $avatar, $id_or_email, $args ) {
    $user = wm_resolve_user_from_mixed( $id_or_email );

    $src = '';
    if ( $user ) {
        $custom = get_user_meta( $user->ID, 'profile_picture', true );
        if ( $custom ) {
            $src = esc_url( $custom );
        }
    }
    // Si no hay user o no tiene foto personalizada, puedes decidir:
    if ( ! $src ) {
        // Opción A: mantener el avatar que venga por defecto (Gravatar/lo que sea)
        if ( $avatar ) return $avatar;

        // Opción B (sin Gravatar): usar un avatar local por defecto
        // $src = wm_default_local_avatar_url();
        // if ( ! $src ) return $avatar; // si no definiste default local, deja pasar
        // (descomenta B si quieres bloquear Gravatar totalmente)
    }

    if ( $src ) {
        $size  = isset( $args['size'] )  ? (int) $args['size'] : 96;
        $alt   = isset( $args['alt'] )   ? $args['alt']   : '';
        $class = isset( $args['class'] ) ? $args['class'] : array();
        $class = is_array( $class ) ? $class : explode( ' ', (string) $class );
        $class = array_filter( array_merge( array( 'avatar', 'avatar-' . $size ), $class ) );

        $html = sprintf(
            '<img alt="%s" src="%s" class="%s" width="%d" height="%d" loading="lazy" decoding="async" />',
            esc_attr( $alt ),
            esc_url( $src ),
            esc_attr( implode( ' ', $class ) ),
            $size,
            $size
        );
        return $html;
    }

    return $avatar;
}, 10, 3 );


add_action('init', function() {
    if (
        isset($_POST['forum_id'], $_POST['nonce']) &&
        wp_verify_nonce($_POST['nonce'], 'toggle_forum_subscription_' . $_POST['forum_id']) &&
        is_user_logged_in()
    ) {
        $forum_id = absint($_POST['forum_id']);
        $user_id = get_current_user_id();

        if ( bbp_is_user_subscribed( $user_id, $forum_id ) ) {
            bbp_remove_user_subscription( $user_id, $forum_id );
        } else {
            bbp_add_user_subscription( $user_id, $forum_id );
        }

        wp_redirect( get_permalink( $forum_id ) );
        exit;
    }
});

add_action('template_redirect', function() {
    if (
        isset($_POST['action']) &&
        $_POST['action'] === 'toggle_topic_subscription' &&
        is_user_logged_in()
    ) {
        $user_id = get_current_user_id();
        $topic_id = absint($_POST['topic_id'] ?? 0);
        $nonce = $_POST['nonce'] ?? '';

        if (
            bbp_is_topic($topic_id) &&
            wp_verify_nonce($nonce, 'toggle_topic_subscription_' . $topic_id)
        ) {
            if ( bbp_is_user_subscribed($user_id, $topic_id) ) {
                bbp_remove_user_subscription($user_id, $topic_id);
            } else {
                bbp_add_user_subscription($user_id, $topic_id);
            }
        }

        wp_redirect( get_permalink($topic_id) );
        exit;
    }
});

add_action('template_redirect', function() {
    if (
        isset($_POST['action']) &&
        $_POST['action'] === 'toggle_topic_favorite' &&
        is_user_logged_in()
    ) {
        $user_id  = get_current_user_id();
        $topic_id = absint($_POST['topic_id'] ?? 0);
        $nonce    = $_POST['nonce'] ?? '';

        if (
            bbp_is_topic($topic_id) &&
            wp_verify_nonce($nonce, 'toggle_topic_favorite_' . $topic_id)
        ) {
            if ( bbp_is_user_favorite($user_id, $topic_id) ) {
                bbp_remove_user_favorite($user_id, $topic_id);
            } else {
                bbp_add_user_favorite($user_id, $topic_id);
            }
        }

        wp_redirect( get_permalink($topic_id) );
        exit;
    }
});
