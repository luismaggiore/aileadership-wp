<?php
/**
 * Plugin Name: Foro Miembro + Invitaciones + Aprobación (+ CSV, Cola, Reporte)
 * Description: Rol "foro_miembro", invitaciones con token, importación CSV, cola de envíos con reintentos y reporte de errores para foro privado (bbPress).
 * Version: 1.3.0
 * Author: Tu Nombre
 */

if ( ! defined('ABSPATH') ) exit;

/* -------------------------------------------------------
   Constantes / helpers
------------------------------------------------------- */
define( 'WM_INVITES_OPTION', 'wm_forum_invites' );      // almacén de invitaciones
define( 'WM_QUEUE_OPTION',   'wm_invite_queue' );       // cola de envíos
define( 'WM_CRON_HOOK',      'wm_process_invite_queue' );
define( 'WM_LOG_MAX',        5 );                       // mantener últimos N logs por invitación

function wm_arr_get( $arr, $key, $default = '' ) {
    return ( is_array($arr) && isset($arr[$key]) ) ? $arr[$key] : $default;
}

/* -------------------------------------------------------
   1) Rol foro_miembro + sync bbPress
------------------------------------------------------- */
function wm_foromiembro_register_role() {
    $caps = array(
        'read'          => true,
        'foro_miembro'  => true,
        'edit_posts'    => false,
        'upload_files'  => false,
    );

    if ( ! get_role('foro_miembro') ) {
        add_role( 'foro_miembro', __('Miembro del Foro','tu-tema'), $caps );
    } else {
        $role = get_role('foro_miembro');
        foreach ( $caps as $cap => $grant ) {
            if ( ! $role->has_cap( $cap ) ) $role->add_cap( $cap, $grant );
        }
    }

    $admin = get_role('administrator');
    if ( $admin && ! $admin->has_cap('foro_miembro') ) $admin->add_cap('foro_miembro', true);
}
add_action('init', 'wm_foromiembro_register_role');

function wm_foromiembro_set_bbp_role( $user_id ) {
    if ( function_exists('bbp_set_user_role') && function_exists('bbp_get_participant_role') ) {
        bbp_set_user_role( $user_id, bbp_get_participant_role() );
    }
}
function wm_foromiembro_maybe_sync_bbp_role( $user_id ) {
    $user = get_userdata( $user_id );
    if ( $user && in_array( 'foro_miembro', (array) $user->roles, true ) ) {
        wm_foromiembro_set_bbp_role( $user_id );
        if ( method_exists( $user, 'add_cap' ) ) $user->add_cap( 'foro_miembro', true );
    }
}
add_action( 'set_user_role', function( $user_id ){ wm_foromiembro_maybe_sync_bbp_role( $user_id ); }, 10 );
add_action( 'user_register', function( $user_id ){ wm_foromiembro_maybe_sync_bbp_role( $user_id ); } );
add_action( 'wp_login', function( $user_login, $user ){ wm_foromiembro_maybe_sync_bbp_role( $user->ID ); }, 10, 2 );

/* -------------------------------------------------------
   2) Invitaciones (almacén + helpers)
------------------------------------------------------- */
function wm_invites_get() {
    $invites = get_option( WM_INVITES_OPTION, array() );
    return is_array($invites) ? $invites : array();
}
function wm_invites_save( $invites ) {
    update_option( WM_INVITES_OPTION, $invites, false );
}
function wm_invites_token() {
    if ( function_exists('random_bytes') ) return bin2hex( random_bytes(16) );
    return bin2hex( openssl_random_pseudo_bytes(16) ?: wp_generate_password(16, false, false) );
}
function wm_invite_url( $token ) {
    // Cambia '/registro-foro/' por el slug real de tu página con el shortcode [wm_forum_signup]
    return add_query_arg( 'token', $token, site_url('/registro-foro/') );
}
function wm_invites_create( $email = '', $args = array() ) {
    $defaults = array(
        'max'     => 1,
        'notes'   => '',
        'expires' => 0,
        'name'    => '',
    );
    $args = wp_parse_args( $args, $defaults );

    $invites = wm_invites_get();
    $token   = wm_invites_token();

    $invites[$token] = array(
        'created'=> time(),
        'used'   => 0,
        'max'    => intval($args['max']),
        'notes'  => sanitize_text_field( $args['notes'] ),
        'email'  => $email ? sanitize_email($email) : '',
        'name'   => $args['name'] ? sanitize_text_field($args['name']) : '',
        'expires'=> intval($args['expires']),
        // estado de envío
        'send'   => array(
            'attempts'   => 0,
            'last_status'=> '',   // success|fail|queued
            'last_error' => '',
            'last_ts'    => 0,
            'history'    => array(), // últimos N eventos
        ),
    );
    wm_invites_save($invites);

    return array( $token, $invites[$token] );
}

/* -------------------------------------------------------
   3) Email de invitación (personalizado con nombre)
      + captura de errores de wp_mail
------------------------------------------------------- */
$GLOBALS['wm_last_mail_error'] = '';

add_action('wp_mail_failed', function( $wp_error ){
    // Captura el último error global de wp_mail
    $msg = is_wp_error($wp_error) ? $wp_error->get_error_message() : 'wp_mail_failed';
    $GLOBALS['wm_last_mail_error'] = $msg;
});

function wm_send_invite_email( $email, $token, $data = array() ) {
    $GLOBALS['wm_last_mail_error'] = '';

    $invite_link = wm_invite_url( $token );
    $site_name   = wp_specialchars_decode( get_bloginfo('name'), ENT_QUOTES );

    $name  = trim( wm_arr_get($data, 'name', '') );
    $hello = $name ? sprintf( __('Hola %s,','tu-tema'), esc_html($name) ) : __('Hola,','tu-tema');

    $expires_txt = '';
    $exp = intval( wm_arr_get($data, 'expires', 0) );
    if ( $exp && $exp > time() ) {
        $expires_txt = sprintf(
            __('(válida hasta %s)','tu-tema'),
            date_i18n( get_option('date_format').' '.get_option('time_format'), $exp )
        );
    }

    // Asunto y cuerpo personalizables (incluye nombre)
    $subject = sprintf( __('Invitación a %s','tu-tema'), $site_name );
    $subject = apply_filters( 'wm_invite_email_subject', $subject, $email, $token, $data );

    // Texto pedido por ti:
    // "Hola José Ignacio, has sido cordialmente invitado a ser parte del foro de AI Leadership Network"
    $org_name = $site_name; // o un nombre fijo de tu red
    $intro    = sprintf(
        __('Has sido cordialmente invitado/a a ser parte del foro de %s.','tu-tema'),
        esc_html( $org_name )
    );

    $body = '
        <p>'.$hello.'</p>
        <p>'.$intro.'</p>
        <p><a href="'.esc_url($invite_link).'" style="display:inline-block;padding:10px 16px;background:#2271b1;color:#fff;text-decoration:none;border-radius:4px;">'.
            __('Completar registro','tu-tema').'</a></p>
        <p><small>'.$expires_txt.'</small></p>
        <hr>
        <p>'.sprintf( __('Si el botón no funciona, usa este enlace: %s','tu-tema'), '<br><a href="'.esc_url($invite_link).'">'.esc_html($invite_link).'</a>' ).'</p>
    ';
    $body = apply_filters( 'wm_invite_email_body', $body, $email, $token, $data );

    $headers = array('Content-Type: text/html; charset=UTF-8');
    $headers = apply_filters( 'wm_invite_email_headers', $headers, $email, $token, $data );

    $ok = wp_mail( $email, $subject, $body, $headers );

    $error_msg = $ok ? '' : ( $GLOBALS['wm_last_mail_error'] ?: __('Fallo desconocido en wp_mail','tu-tema') );

    // Log
    wm_log_send_result( $token, $ok ? 'success' : 'fail', $error_msg, $email, $subject );

    return $ok;
}

function wm_log_send_result( $token, $status, $error = '', $email = '', $subject = '' ) {
    $invites = wm_invites_get();
    if ( empty($invites[$token]) ) return;

    $send = wm_arr_get( $invites[$token], 'send', array() );
    $send['attempts']    = intval( wm_arr_get($send,'attempts',0) ) + 1;
    $send['last_status'] = $status;
    $send['last_error']  = $error;
    $send['last_ts']     = time();

    $history = wm_arr_get($send,'history',array());
    $history[] = array(
        'ts'     => $send['last_ts'],
        'status' => $status,
        'error'  => $error,
        'to'     => $email,
        'subj'   => $subject,
    );
    // Limitar historial
    if ( count($history) > WM_LOG_MAX ) $history = array_slice($history, -WM_LOG_MAX);

    $send['history'] = $history;
    $invites[$token]['send'] = $send;

    wm_invites_save( $invites );
}

/* -------------------------------------------------------
   4) Cola de envíos (WP-Cron) + backoff progresivo
------------------------------------------------------- */
function wm_queue_get() {
    $q = get_option( WM_QUEUE_OPTION, array() );
    return is_array($q) ? $q : array();
}
function wm_queue_save( $q ) {
    update_option( WM_QUEUE_OPTION, $q, false );
}
function wm_queue_add( $token ) {
    $inv = wm_invites_get();
    if ( empty($inv[$token]) ) return false;

    $q   = wm_queue_get();
    if ( isset($q[$token]) ) return true; // ya en cola

    $q[$token] = array(
        'token'     => $token,
        'next_try'  => time(),  // ahora
        'attempts'  => 0,
    );
    wm_queue_save( $q );

    // marcar invitación como "queued"
    $inv[$token]['send']['last_status'] = 'queued';
    $inv[$token]['send']['last_error']  = '';
    $inv[$token]['send']['last_ts']     = time();
    wm_invites_save( $inv );

    wm_queue_schedule();
    return true;
}
function wm_queue_remove( $token ) {
    $q = wm_queue_get();
    if ( isset($q[$token]) ) {
        unset($q[$token]);
        wm_queue_save($q);
    }
}
function wm_queue_schedule() {
    if ( ! wp_next_scheduled( WM_CRON_HOOK ) ) {
        wp_schedule_single_event( time() + 10, WM_CRON_HOOK ); // en ~10s
    }
}
add_action( WM_CRON_HOOK, 'wm_queue_process' );

function wm_queue_process() {
    $q = wm_queue_get();
    if ( empty($q) ) return;

    $batch_size = apply_filters( 'wm_invite_batch_size', 30 ); // tamaño por tanda
    $sent_this_run = 0;

    $invites = wm_invites_get();

    foreach ( $q as $token => $item ) {
        if ( $sent_this_run >= $batch_size ) break;

        $now = time();
        if ( intval($item['next_try']) > $now ) continue; // aún no toca

        $data = wm_arr_get( $invites, $token, array() );
        $email = wm_arr_get( $data, 'email', '' );
        if ( ! $email ) {
            // Si no hay email, no se puede enviar; quitar de cola
            wm_log_send_result( $token, 'fail', __('Invitación sin email','tu-tema') );
            wm_queue_remove( $token );
            continue;
        }

        $ok = wm_send_invite_email( $email, $token, $data );

        if ( $ok ) {
            wm_queue_remove( $token );
        } else {
            // backoff progresivo: 5m, 15m, 60m, 3h, 24h
            $item['attempts'] = intval($item['attempts']) + 1;
            $delays = array( 5*MINUTE_IN_SECONDS, 15*MINUTE_IN_SECONDS, HOUR_IN_SECONDS, 3*HOUR_IN_SECONDS, DAY_IN_SECONDS );
            $idx = min( $item['attempts'] - 1, count($delays)-1 );
            $item['next_try'] = $now + $delays[$idx];
            $q[$token] = $item;
            wm_queue_save( $q );
        }

        $sent_this_run++;
    }

    // Si quedan elementos en cola, re-programar
    $q = wm_queue_get();
    if ( ! empty($q) ) {
        wm_queue_schedule();
    }
}

/* -------------------------------------------------------
   5) Admin: Invitaciones + Importar CSV + Reporte
------------------------------------------------------- */
add_action('admin_menu', function(){
    add_users_page(
        __('Invitaciones Foro','tu-tema'),
        __('Invitaciones Foro','tu-tema'),
        'manage_options',
        'wm-invitaciones-foro',
        'wm_render_invites_page'
    );
    add_users_page(
        __('Importar Invitaciones Foro','tu-tema'),
        'Importar Invitaciones Foro',
        'manage_options',
        'wm-importar-invitaciones',
        'wm_render_import_page'
    );
    add_users_page(
        __('Reporte de Envíos Invitaciones','tu-tema'),
        'Reporte Envíos Invitaciones',
        'manage_options',
        'wm-reporte-envios',
        'wm_render_report_page'
    );
});

/* ---- Página: Invitaciones manuales ---- */
function wm_render_invites_page() {
    if ( ! current_user_can('manage_options') ) return;

    $invites = wm_invites_get();

    // Crear
    if ( isset($_POST['wm_create_invite']) && check_admin_referer('wm_create_invite') ) {
        $email  = ! empty($_POST['wm_invite_email']) ? sanitize_email($_POST['wm_invite_email']) : '';
        $name   = ! empty($_POST['wm_invite_name']) ? sanitize_text_field($_POST['wm_invite_name']) : '';
        $days   = isset($_POST['wm_invite_days']) ? max(0, intval($_POST['wm_invite_days'])) : 0;
        $exp    = $days ? ( time() + DAY_IN_SECONDS * $days ) : 0;

        list($token, $data) = wm_invites_create( $email, array(
            'notes'   => sanitize_text_field( $_POST['wm_invite_note'] ?? '' ),
            'expires' => $exp,
            'name'    => $name,
        ) );

        // Encolar envío si hay email y se marcó
        if ( $email && ! empty($_POST['wm_enqueue_send']) ) {
            wm_queue_add( $token );
        }

        echo '<div class="notice notice-success"><p>'.esc_html__('Invitación creada.','tu-tema').'</p></div>';
        $invites = wm_invites_get();
    }

    // Borrar
    if ( isset($_POST['wm_delete_invite'], $_POST['token']) && check_admin_referer('wm_delete_invite_'.$_POST['token']) ) {
        $t = sanitize_text_field($_POST['token']);
        unset($invites[$t]);
        wm_invites_save($invites);
        // quitar también de cola si estaba
        wm_queue_remove( $t );
        echo '<div class="notice notice-success"><p>'.esc_html__('Invitación eliminada.','tu-tema').'</p></div>';
    }

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Invitaciones Foro','tu-tema'); ?></h1>

        <h2><?php esc_html_e('Crear invitación manual','tu-tema'); ?></h2>
        <form method="post" style="margin:1em 0;">
            <?php wp_nonce_field('wm_create_invite'); ?>
            <input type="email" name="wm_invite_email" placeholder="<?php esc_attr_e('Email (opcional)','tu-tema'); ?>" style="width:240px;">
            <input type="text" name="wm_invite_name" placeholder="<?php esc_attr_e('Nombre (opcional)','tu-tema'); ?>" style="width:200px;">
            <input type="number" min="0" name="wm_invite_days" placeholder="<?php esc_attr_e('Vence en días (0 = sin venc.)','tu-tema'); ?>" style="width:180px;">
            <input type="text" name="wm_invite_note" placeholder="<?php esc_attr_e('Nota interna (opcional)','tu-tema'); ?>" style="width:220px;">
            <label style="margin-left:12px;">
                <input type="checkbox" name="wm_enqueue_send" value="1" checked>
                <?php esc_html_e('Enviar por cola automáticamente','tu-tema'); ?>
            </label>
            <button class="button button-primary" name="wm_create_invite" value="1"><?php esc_html_e('Crear','tu-tema'); ?></button>
        </form>

        <hr>

        <h2><?php esc_html_e('Listado de invitaciones','tu-tema'); ?></h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Link / Token','tu-tema'); ?></th>
                    <th><?php esc_html_e('Email','tu-tema'); ?></th>
                    <th><?php esc_html_e('Nombre','tu-tema'); ?></th>
                    <th><?php esc_html_e('Usos','tu-tema'); ?></th>
                    <th><?php esc_html_e('Vence','tu-tema'); ?></th>
                    <th><?php esc_html_e('Estado envío','tu-tema'); ?></th>
                    <th><?php esc_html_e('Nota','tu-tema'); ?></th>
                    <th><?php esc_html_e('Acciones','tu-tema'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php $invites = wm_invites_get();
            if ( empty($invites) ) : ?>
                <tr><td colspan="8"><?php esc_html_e('Aún no hay invitaciones.','tu-tema'); ?></td></tr>
            <?php else : foreach ( $invites as $token => $d ) :
                $url = wm_invite_url($token);
                $send = wm_arr_get($d,'send',array());
                $status = wm_arr_get($send,'last_status','');
                $label  = $status ? $status : '—';
                ?>
                <tr>
                    <td><code style="word-break:break-all;"><?php echo esc_html( $url ); ?></code><br><small><?php echo esc_html( $token ); ?></small></td>
                    <td><?php echo esc_html( wm_arr_get($d,'email','') ); ?></td>
                    <td><?php echo esc_html( wm_arr_get($d,'name','') ); ?></td>
                    <td><?php echo intval($d['used']).' / '.intval($d['max']); ?></td>
                    <td><?php echo !empty($d['expires']) ? esc_html( date_i18n( get_option('date_format').' '.get_option('time_format'), intval($d['expires']) ) ) : '—'; ?></td>
                    <td><?php echo esc_html( ucfirst($label) ); ?></td>
                    <td><?php echo esc_html( wm_arr_get($d,'notes','') ); ?></td>
                    <td>
                        <?php if ( wm_arr_get($d,'email','') ) : ?>
                            <form method="post" style="display:inline;" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                                <?php wp_nonce_field('wm_retry_'.$token); ?>
                                <input type="hidden" name="action" value="wm_retry_send">
                                <input type="hidden" name="token" value="<?php echo esc_attr($token); ?>">
                                <button class="button" title="<?php esc_attr_e('Reintentar por cola','tu-tema'); ?>">⟲ <?php esc_html_e('Reintentar','tu-tema'); ?></button>
                            </form>
                        <?php endif; ?>
                        <form method="post" style="display:inline;">
                            <?php wp_nonce_field('wm_delete_invite_'.$token); ?>
                            <input type="hidden" name="token" value="<?php echo esc_attr($token); ?>">
                            <button class="button button-link-delete" name="wm_delete_invite" value="1" onclick="return confirm('¿Eliminar esta invitación?');">
                                <?php esc_html_e('Eliminar','tu-tema'); ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/* ---- Acción admin: reintentar envío (enqueue) ---- */
add_action( 'admin_post_wm_retry_send', function(){
    if ( ! current_user_can('manage_options') ) wp_die('No perms');
    $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
    check_admin_referer('wm_retry_'.$token);
    if ( $token ) wm_queue_add( $token );
    wp_safe_redirect( admin_url('users.php?page=wm-reporte-envios&ret=1') );
    exit;
});

/* ---- Página: Importar CSV (usa cola para envíos) ---- */
function wm_render_import_page() {
    if ( ! current_user_can('manage_options') ) return; ?>

    <div class="wrap">
        <h1><?php esc_html_e('Importar Invitaciones (CSV)','tu-tema'); ?></h1>
        <p><?php esc_html_e('CSV con columnas: email, name (opcional). Cada fila genera una invitación y se encola el envío.','tu-tema'); ?></p>

        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('wm_import_csv'); ?>
            <p><input type="file" name="wm_csv" accept=".csv" required></p>
            <p>
                <label><?php esc_html_e('Vence en (días, 0 = sin vencimiento):','tu-tema'); ?></label>
                <input type="number" name="wm_exp_days" min="0" value="7" style="width:120px;">
            </p>
            <p>
                <label><?php esc_html_e('Nota interna (opcional, se aplica a todas):','tu-tema'); ?></label>
                <input type="text" name="wm_note" style="width:320px;">
            </p>
            <p><button class="button button-primary" name="wm_do_import" value="1"><?php esc_html_e('Importar y Encolar Envíos','tu-tema'); ?></button></p>
        </form>

        <?php
        if ( isset($_POST['wm_do_import']) && check_admin_referer('wm_import_csv') ) {
            if ( empty($_FILES['wm_csv']['tmp_name']) || ! is_uploaded_file($_FILES['wm_csv']['tmp_name']) ) {
                echo '<div class="notice notice-error"><p>'.esc_html__('No se recibió un archivo válido.','tu-tema').'</p></div>';
                return;
            }

            $days     = isset($_POST['wm_exp_days']) ? max(0, intval($_POST['wm_exp_days'])) : 0;
            $exp      = $days ? ( time() + DAY_IN_SECONDS * $days ) : 0;
            $note_all = sanitize_text_field( $_POST['wm_note'] ?? '' );

            $file = fopen( $_FILES['wm_csv']['tmp_name'], 'r' );
            if ( ! $file ) {
                echo '<div class="notice notice-error"><p>'.esc_html__('No se pudo abrir el CSV.','tu-tema').'</p></div>';
                return;
            }

            // Detectar separador
            $first = fgets($file); rewind($file);
            $delimiter = ( substr_count($first, ';') > substr_count($first, ',') ) ? ';' : ',';

            // Encabezado
            $header = fgetcsv($file, 0, $delimiter);
            if ( $header && is_array($header) ) {
                $lower = array_map( 'strtolower', $header );
                if ( ! in_array('email', $lower, true) ) {
                    // No tenía cabecera real, re-leer desde inicio
                    rewind($file);
                }
            }

            $row=0; $created=0; $queued=0; $skipped=0; $errors=array();

            while ( ($data = fgetcsv($file, 0, $delimiter)) !== false ) {
                $row++;
                $email = isset($data[0]) ? sanitize_email(trim($data[0])) : '';
                $name  = isset($data[1]) ? sanitize_text_field(trim($data[1])) : '';

                if ( ! is_email($email) ) { $skipped++; $errors[] = sprintf(__('Fila %d: email inválido "%s"','tu-tema'), $row, $email); continue; }
                if ( email_exists($email) ) { $skipped++; $errors[] = sprintf(__('Fila %d: el email ya tiene cuenta en el sitio','tu-tema'), $row); continue; }

                list($token, $idata) = wm_invites_create( $email, array(
                    'notes'   => $note_all,
                    'expires' => $exp,
                    'name'    => $name,
                ) );
                $created++;

                if ( wm_queue_add( $token ) ) $queued++;
            }
            fclose($file);

            echo '<div class="notice notice-success"><p>'.
                sprintf( esc_html__('Importación: %1$d invitaciones creadas, %2$d encoladas, %3$d omitidas.','tu-tema'), $created, $queued, $skipped ).
            '</p></div>';

            if ( ! empty($errors) ) {
                echo '<div class="notice notice-warning"><ul>';
                foreach ( $errors as $e ) echo '<li>'.esc_html($e).'</li>';
                echo '</ul></div>';
            }
        }
        ?>
    </div>
    <?php
}

/* ---- Página: Reporte de envíos / reintentos ---- */
function wm_render_report_page() {
    if ( ! current_user_can('manage_options') ) return;

    $invites = wm_invites_get();

    // Construir lista de fallidos
    $failed = array();
    foreach ( $invites as $token => $d ) {
        $send = wm_arr_get($d,'send',array());
        if ( wm_arr_get($send,'last_status','') === 'fail' ) {
            $failed[$token] = $d;
        }
    }

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Reporte de Envíos de Invitaciones','tu-tema'); ?></h1>

        <p><?php esc_html_e('Aquí verás el estado del último intento de envío por invitación. Puedes reintentar los fallidos; los correos se envían por cola con reintentos automáticos y backoff.','tu-tema'); ?></p>

        <h2><?php esc_html_e('Fallidos recientes','tu-tema'); ?></h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Email','tu-tema'); ?></th>
                    <th><?php esc_html_e('Nombre','tu-tema'); ?></th>
                    <th><?php esc_html_e('Último error','tu-tema'); ?></th>
                    <th><?php esc_html_e('Intentos','tu-tema'); ?></th>
                    <th><?php esc_html_e('Último intento','tu-tema'); ?></th>
                    <th><?php esc_html_e('Acciones','tu-tema'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if ( empty($failed) ) : ?>
                <tr><td colspan="6"><?php esc_html_e('No hay fallidos.','tu-tema'); ?></td></tr>
            <?php else : foreach ( $failed as $token => $d ) :
                $send = wm_arr_get($d,'send',array()); ?>
                <tr>
                    <td><?php echo esc_html( wm_arr_get($d,'email','') ); ?></td>
                    <td><?php echo esc_html( wm_arr_get($d,'name','') ); ?></td>
                    <td><?php echo esc_html( wm_arr_get($send,'last_error','') ); ?></td>
                    <td><?php echo intval( wm_arr_get($send,'attempts',0) ); ?></td>
                    <td><?php echo wm_arr_get($send,'last_ts',0) ? esc_html( date_i18n( get_option('date_format').' '.get_option('time_format'), intval($send['last_ts']) ) ) : '—'; ?></td>
                    <td>
                        <form method="post" style="display:inline;" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                            <?php wp_nonce_field('wm_retry_'.$token); ?>
                            <input type="hidden" name="action" value="wm_retry_send">
                            <input type="hidden" name="token" value="<?php echo esc_attr($token); ?>">
                            <button class="button"><?php esc_html_e('Reintentar (cola)','tu-tema'); ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>

        <h2 style="margin-top:28px;"><?php esc_html_e('Historial (últimos eventos por invitación)','tu-tema'); ?></h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Email','tu-tema'); ?></th>
                    <th><?php esc_html_e('Nombre','tu-tema'); ?></th>
                    <th><?php esc_html_e('Historial','tu-tema'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if ( empty($invites) ) : ?>
                <tr><td colspan="3"><?php esc_html_e('Sin datos.','tu-tema'); ?></td></tr>
            <?php else : foreach ( $invites as $token => $d ) :
                $history = wm_arr_get( wm_arr_get($d,'send',array()), 'history', array() ); ?>
                <tr>
                    <td><?php echo esc_html( wm_arr_get($d,'email','') ); ?></td>
                    <td><?php echo esc_html( wm_arr_get($d,'name','') ); ?></td>
                    <td>
                        <?php if ( empty($history) ) { echo '—'; }
                        else {
                            echo '<ul style="margin:0;">';
                            foreach ( $history as $h ) {
                                printf(
                                    '<li>%s — %s %s</li>',
                                    esc_html( date_i18n( get_option('date_format').' '.get_option('time_format'), intval(wm_arr_get($h,'ts',0)) ) ),
                                    esc_html( strtoupper(wm_arr_get($h,'status','')) ),
                                    $h['error'] ? ' · <em>'.esc_html($h['error']).'</em>' : ''
                                );
                            }
                            echo '</ul>';
                        } ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/* -------------------------------------------------------
   6) Shortcode de registro: [wm_forum_signup]
   - Valida token, expiración y email si está fijado.
------------------------------------------------------- */
add_shortcode('wm_forum_signup', function( $atts = array(), $content = '' ){
    $invites = wm_invites_get();
    $token   = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

    if ( empty($token) || empty($invites[$token]) || intval($invites[$token]['used']) >= intval($invites[$token]['max']) ) {
        ob_start(); ?>
        <div class="wm-invite-invalid">
            <h2><?php esc_html_e('Invitación inválida o ya utilizada','tu-tema'); ?></h2>
            <p><?php esc_html_e('Solicita un nuevo enlace al administrador.','tu-tema'); ?></p>
        </div>
        <?php return ob_get_clean();
    }

    $inv = $invites[$token];
    if ( ! empty($inv['expires']) && time() > intval($inv['expires']) ) {
        ob_start(); ?>
        <div class="wm-invite-invalid">
            <h2><?php esc_html_e('La invitación ha expirado','tu-tema'); ?></h2>
            <p><?php esc_html_e('Solicita un nuevo enlace al administrador.','tu-tema'); ?></p>
        </div>
        <?php return ob_get_clean();
    }

    $errors = new WP_Error();
    $ok_msg = '';

    if ( isset($_POST['wm_signup_submit']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'wm_signup_'.$token) ) {

        $email    = sanitize_email( $_POST['email'] ?? '' );
        $nombre   = sanitize_text_field( $_POST['nombre'] ?? '' );
        $pass     = $_POST['pass']  ?? '';
        $pass2    = $_POST['pass2'] ?? '';

        if ( ! is_email($email) ) $errors->add('email', __('Email inválido','tu-tema'));
        if ( email_exists($email) ) $errors->add('email_exists', __('Este email ya está registrado','tu-tema'));
        if ( empty($nombre) ) $errors->add('nombre', __('Ingresa tu nombre','tu-tema'));
        if ( strlen($pass) < 8 ) $errors->add('pass', __('La contraseña debe tener al menos 8 caracteres','tu-tema'));
        if ( $pass !== $pass2 ) $errors->add('pass2', __('Las contraseñas no coinciden','tu-tema'));

        if ( ! empty($inv['email']) && strtolower($email) !== strtolower($inv['email']) ) {
            $errors->add('invite_email_mismatch', __('El email no coincide con la invitación recibida.','tu-tema'));
        }
        if ( ! empty($_POST['website']) ) $errors->add('bot', __('Detección de bot','tu-tema'));

        if ( empty($errors->errors) ) {
            $login_base = sanitize_user( current( explode('@', $email) ), true );
            if ( empty($login_base) ) $login_base = 'usuario';
            $user_login = $login_base; $i = 1;
            while ( username_exists($user_login) ) { $user_login = $login_base . $i; $i++; }

            $user_id = wp_insert_user( array(
                'user_login'   => $user_login,
                'user_email'   => $email,
                'display_name' => $nombre,
                'first_name'   => $nombre,
                'user_pass'    => $pass,
                'role'         => 'subscriber',
            ));

            if ( is_wp_error($user_id) ) {
                $errors->add('create', $user_id->get_error_message());
            } else {
                update_user_meta( $user_id, 'wm_pending_approval', 1 );
                update_user_meta( $user_id, 'wm_invite_token', $token );

                $invites[$token]['used'] = intval($invites[$token]['used']) + 1;
                wm_invites_save($invites);

                $admin_email = get_option('admin_email');
                wp_mail(
                    $admin_email,
                    sprintf( __('Nueva solicitud de acceso al foro: %s','tu-tema'), $nombre ),
                    sprintf( __('Se ha registrado %s (%s) y está pendiente de aprobación. Ve a Usuarios para aprobarlo.','tu-tema'), $nombre, $email )
                );

                $ok_msg = __('¡Gracias! Tu registro fue recibido. Te avisaremos cuando tu cuenta sea aprobada.','tu-tema');
            }
        }
    }

    ob_start(); ?>
    <div class="wm-signup-wrapper">
        <h2><?php esc_html_e('Registro para miembros del foro','tu-tema'); ?></h2>

        <?php if ( $ok_msg ) : ?>
            <div class="notice notice-success"><p><?php echo esc_html($ok_msg); ?></p></div>
            <?php return ob_get_clean();
        endif; ?>

        <?php if ( ! empty($errors->errors) ) : ?>
            <div class="notice notice-error">
                <ul>
                    <?php foreach ( $errors->get_error_messages() as $msg ) : ?>
                        <li><?php echo esc_html($msg); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" class="wm-signup-form" novalidate>
            <?php wp_nonce_field( 'wm_signup_'.$token ); ?>
            <input type="hidden" name="token" value="<?php echo esc_attr($token); ?>">
            <p style="display:none;"><label>Website</label><input type="text" name="website" value=""></p>

            <p>
                <label><?php esc_html_e('Nombre','tu-tema'); ?></label>
                <input type="text" name="nombre" value="<?php echo isset($_POST['nombre']) ? esc_attr($_POST['nombre']) : esc_attr( wm_arr_get($inv,'name','') ); ?>" required>
            </p>
            <p>
                <label><?php esc_html_e('Email','tu-tema'); ?></label>
                <input type="email" name="email" value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : esc_attr( wm_arr_get($inv,'email','') ); ?>" required <?php echo !empty($inv['email']) ? 'readonly' : ''; ?>>
                <?php if ( ! empty($inv['email']) ) : ?><small><?php esc_html_e('Este email fue el invitado.','tu-tema'); ?></small><?php endif; ?>
            </p>
            <p>
                <label><?php esc_html_e('Contraseña','tu-tema'); ?></label>
                <input type="password" name="pass" required minlength="8">
            </p>
            <p>
                <label><?php esc_html_e('Repite la contraseña','tu-tema'); ?></label>
                <input type="password" name="pass2" required minlength="8">
            </p>
            <p><button class="button button-primary" name="wm_signup_submit" value="1"><?php esc_html_e('Enviar solicitud','tu-tema'); ?></button></p>
        </form>
        <p class="wm-signup-help"><?php esc_html_e('Tu cuenta quedará pendiente de aprobación por un administrador.','tu-tema'); ?></p>
    </div>
    <?php return ob_get_clean();
});

/* -------------------------------------------------------
   7) Bloquear login si el usuario está pendiente
   8) Aprobación rápida en Usuarios
------------------------------------------------------- */
add_filter('authenticate', function( $user, $username, $password ){
    if ( $user instanceof WP_User ) {
        if ( get_user_meta( $user->ID, 'wm_pending_approval', true ) ) {
            return new WP_Error( 'wm_pending', __('Tu cuenta está pendiente de aprobación por el administrador.','tu-tema') );
        }
    }
    return $user;
}, 30, 3);

function wm_aprobar_usuario_para_foro( $user_id ) {
    $u = new WP_User( $user_id );
    $u->set_role( 'foro_miembro' );
    wm_foromiembro_set_bbp_role( $user_id );
    $u->add_cap( 'foro_miembro', true );
    delete_user_meta( $user_id, 'wm_pending_approval' );
}

add_filter( 'user_row_actions', function( $actions, $user ){
    if ( current_user_can('promote_users') && ! in_array( 'foro_miembro', (array) $user->roles, true ) ) {
        $url = wp_nonce_url( add_query_arg( array(
            'wm_aprobar_foro' => 1,
            'user_id'         => $user->ID,
        ), admin_url('users.php') ), 'wm_aprobar_foro_'.$user->ID );
        $actions['wm_aprobar_foro'] = '<a href="'.esc_url( $url ).'">'.esc_html__('Aprobar para foro','tu-tema').'</a>';
    }
    return $actions;
}, 10, 2 );

add_action( 'admin_init', function(){
    if ( isset($_GET['wm_aprobar_foro'], $_GET['user_id']) && current_user_can('promote_users') ) {
        $user_id = absint( $_GET['user_id'] );
        check_admin_referer( 'wm_aprobar_foro_'.$user_id );
        wm_aprobar_usuario_para_foro( $user_id );
        wp_safe_redirect( add_query_arg( array( 'updated' => 1 ), admin_url('users.php') ) );
        exit;
    }
});
