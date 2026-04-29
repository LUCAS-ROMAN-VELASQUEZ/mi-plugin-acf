<?php
/**
 * Plugin Name: Media Logos
 * Description: Asocia logos (imágenes) con dominios y URLs de medios de comunicación.
 * Version: 1.0.0
 * Author: Tu equipo
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ─────────────────────────────────────────
// 1. CREAR TABLA AL ACTIVAR EL PLUGIN
// ─────────────────────────────────────────
register_activation_hook( __FILE__, 'ml_crear_tabla' );

function ml_crear_tabla() {
    global $wpdb;
    $tabla   = $wpdb->prefix . 'media_logos';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $tabla (
        id        BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        nombre    VARCHAR(255)        NOT NULL,
        dominio   VARCHAR(255)        NOT NULL,
        logo_url  TEXT                NOT NULL,
        creado    DATETIME            DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY dominio (dominio)
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

// ─────────────────────────────────────────
// 2. MENÚ EN EL PANEL DE ADMINISTRACIÓN
// ─────────────────────────────────────────
add_action( 'admin_menu', 'ml_agregar_menu' );

function ml_agregar_menu() {
    add_menu_page(
        'Media Logos',
        'Media Logos',
        'manage_options',
        'media-logos',
        'ml_pagina_listado',
        'dashicons-format-image',
        30
    );

    add_submenu_page(
        'media-logos',
        'Añadir medio',
        'Añadir medio',
        'manage_options',
        'media-logos-nuevo',
        'ml_pagina_formulario'
    );
}

// ─────────────────────────────────────────
// 3. ENCOLAR MEDIA UPLOADER DE WP
// ─────────────────────────────────────────
add_action( 'admin_enqueue_scripts', 'ml_scripts' );

function ml_scripts( $hook ) {
    if ( ! in_array( $hook, [ 'media-logos_page_media-logos-nuevo', 'toplevel_page_media-logos' ] ) ) return;
    wp_enqueue_media();
}

// ─────────────────────────────────────────
// 4. PROCESAR FORMULARIO (guardar en BD)
// ─────────────────────────────────────────
add_action( 'admin_post_ml_guardar_medio', 'ml_guardar_medio' );

function ml_guardar_medio() {
    check_admin_referer( 'ml_guardar_medio_nonce' );

    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );

    $nombre   = sanitize_text_field( $_POST['ml_nombre'] ?? '' );
    $dominio  = sanitize_text_field( $_POST['ml_dominio'] ?? '' );
    $logo_url = esc_url_raw( $_POST['ml_logo_url'] ?? '' );
    $id       = intval( $_POST['ml_id'] ?? 0 );

    if ( ! $nombre || ! $dominio || ! $logo_url ) {
        wp_redirect( add_query_arg( [ 'page' => 'media-logos-nuevo', 'error' => '1' ], admin_url( 'admin.php' ) ) );
        exit;
    }

    // Normalizar dominio (quitar http/https/www)
    $dominio = preg_replace( '#^https?://(www\.)?#i', '', rtrim( $dominio, '/' ) );

    global $wpdb;
    $tabla = $wpdb->prefix . 'media_logos';

    if ( $id > 0 ) {
        $wpdb->update( $tabla, compact( 'nombre', 'dominio', 'logo_url' ), [ 'id' => $id ] );
    } else {
        $wpdb->insert( $tabla, compact( 'nombre', 'dominio', 'logo_url' ) );
    }

    wp_redirect( add_query_arg( [ 'page' => 'media-logos', 'guardado' => '1' ], admin_url( 'admin.php' ) ) );
    exit;
}

// ─────────────────────────────────────────
// 5. PROCESAR ELIMINACIÓN
// ─────────────────────────────────────────
add_action( 'admin_post_ml_eliminar_medio', 'ml_eliminar_medio' );

function ml_eliminar_medio() {
    check_admin_referer( 'ml_eliminar_medio_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );

    $id = intval( $_GET['id'] ?? 0 );
    if ( $id > 0 ) {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'media_logos', [ 'id' => $id ] );
    }

    wp_redirect( add_query_arg( [ 'page' => 'media-logos', 'eliminado' => '1' ], admin_url( 'admin.php' ) ) );
    exit;
}

// ─────────────────────────────────────────
// 6. PÁGINA: LISTADO DE MEDIOS
// ─────────────────────────────────────────
function ml_pagina_listado() {
    global $wpdb;
    $tabla  = $wpdb->prefix . 'media_logos';
    $medios = $wpdb->get_results( "SELECT * FROM $tabla ORDER BY nombre ASC" );
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Media Logos</h1>
        <a href="<?php echo admin_url( 'admin.php?page=media-logos-nuevo' ); ?>" class="page-title-action">Añadir medio</a>
        <hr class="wp-header-end">

        <?php if ( isset( $_GET['guardado'] ) ) : ?>
            <div class="notice notice-success is-dismissible"><p>Medio guardado correctamente.</p></div>
        <?php endif; ?>
        <?php if ( isset( $_GET['eliminado'] ) ) : ?>
            <div class="notice notice-success is-dismissible"><p>Medio eliminado.</p></div>
        <?php endif; ?>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:60px">Logo</th>
                    <th>Nombre</th>
                    <th>Dominio</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if ( $medios ) : ?>
                <?php foreach ( $medios as $medio ) : ?>
                    <tr>
                        <td><img src="<?php echo esc_url( $medio->logo_url ); ?>" style="max-height:40px;max-width:80px;object-fit:contain;"></td>
                        <td><?php echo esc_html( $medio->nombre ); ?></td>
                        <td><?php echo esc_html( $medio->dominio ); ?></td>
                        <td>
                            <a href="<?php echo admin_url( 'admin.php?page=media-logos-nuevo&id=' . $medio->id ); ?>">Editar</a>
                            &nbsp;|&nbsp;
                            <a href="<?php echo wp_nonce_url( admin_url( 'admin-post.php?action=ml_eliminar_medio&id=' . $medio->id ), 'ml_eliminar_medio_nonce' ); ?>"
                               onclick="return confirm('¿Eliminar este medio?')"
                               style="color:#b32d2e">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="4">No hay medios registrados todavía.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// ─────────────────────────────────────────
// 7. PÁGINA: FORMULARIO AÑADIR / EDITAR
// ─────────────────────────────────────────
function ml_pagina_formulario() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'media_logos';
    $medio = null;

    $id = intval( $_GET['id'] ?? 0 );
    if ( $id > 0 ) {
        $medio = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $tabla WHERE id = %d", $id ) );
    }
    ?>
    <div class="wrap">
        <h1><?php echo $medio ? 'Editar medio' : 'Añadir medio'; ?></h1>
        <?php if ( isset( $_GET['error'] ) ) : ?>
            <div class="notice notice-error"><p>Todos los campos son obligatorios.</p></div>
        <?php endif; ?>

        <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
            <?php wp_nonce_field( 'ml_guardar_medio_nonce' ); ?>
            <input type="hidden" name="action" value="ml_guardar_medio">
            <input type="hidden" name="ml_id" value="<?php echo $medio ? $medio->id : 0; ?>">

            <table class="form-table">
                <tr>
                    <th><label for="ml_nombre">Nombre del medio</label></th>
                    <td><input type="text" id="ml_nombre" name="ml_nombre" class="regular-text"
                               value="<?php echo $medio ? esc_attr( $medio->nombre ) : ''; ?>" required></td>
                </tr>
                <tr>
                    <th><label for="ml_dominio">Dominio / URL</label></th>
                    <td>
                        <input type="text" id="ml_dominio" name="ml_dominio" class="regular-text"
                               placeholder="ej: elpais.com"
                               value="<?php echo $medio ? esc_attr( $medio->dominio ) : ''; ?>" required>
                        <p class="description">Puedes poner el dominio completo o con https://. Se normalizará automáticamente.</p>
                    </td>
                </tr>
                <tr>
                    <th><label>Logo</label></th>
                    <td>
                        <div style="margin-bottom:10px">
                            <img id="ml_logo_preview"
                                 src="<?php echo $medio ? esc_url( $medio->logo_url ) : ''; ?>"
                                 style="max-height:80px;max-width:200px;object-fit:contain;display:<?php echo $medio ? 'block' : 'none'; ?>;margin-bottom:8px">
                        </div>
                        <input type="hidden" id="ml_logo_url" name="ml_logo_url"
                               value="<?php echo $medio ? esc_url( $medio->logo_url ) : ''; ?>">
                        <button type="button" class="button" id="ml_subir_logo">
                            <?php echo $medio ? 'Cambiar logo' : 'Subir logo'; ?>
                        </button>
                        <p class="description">Selecciona o sube una imagen desde la biblioteca de medios de WordPress.</p>
                    </td>
                </tr>
            </table>

            <?php submit_button( $medio ? 'Actualizar medio' : 'Guardar medio' ); ?>
        </form>
    </div>

    <script>
    jQuery(function($){
        var frame;
        $('#ml_subir_logo').on('click', function(e){
            e.preventDefault();
            if ( frame ) { frame.open(); return; }
            frame = wp.media({
                title: 'Seleccionar logo',
                button: { text: 'Usar esta imagen' },
                multiple: false,
                library: { type: 'image' }
            });
            frame.on('select', function(){
                var attachment = frame.state().get('selection').first().toJSON();
                $('#ml_logo_url').val( attachment.url );
                $('#ml_logo_preview').attr('src', attachment.url).show();
            });
            frame.open();
        });
    });
    </script>
    <?php
}

// ─────────────────────────────────────────
// 8. FUNCIÓN AUXILIAR: obtener logo por URL
//    Úsala en tus plantillas o en el campo
//    de notas de prensa.
//
//    Ejemplo: ml_get_logo('https://elpais.com/noticia-x')
//    Devuelve la URL del logo o cadena vacía.
// ─────────────────────────────────────────
function ml_get_logo( $url ) {
    global $wpdb;
    // Extraer dominio de la URL
    $parsed  = parse_url( $url );
    $dominio = isset( $parsed['host'] ) ? preg_replace( '/^www\./', '', $parsed['host'] ) : '';

    if ( ! $dominio ) return '';

    $tabla = $wpdb->prefix . 'media_logos';
    $logo  = $wpdb->get_var( $wpdb->prepare(
        "SELECT logo_url FROM $tabla WHERE dominio = %s LIMIT 1",
        $dominio
    ) );

    return $logo ?: '';
}