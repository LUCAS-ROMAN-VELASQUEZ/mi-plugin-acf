<?php
/**
 * Plugin Name: Mi Plugin ACF
 * Description: Muestra campos ACF mediante shortcode para WPBakery.
 * Version: 1.0
 * Author: Lucas Román y Víctor Nieves
 */

// Evita que se pueda acceder directamente al archivo por URL (seguridad)
if (!defined('ABSPATH')) {
    exit;
}

// Función que ejecuta el shortcode [acf]
function mi_plugin_acf_shortcode($atts) {

    // Define los atributos del shortcode y su valor por defecto
    // En este caso: 'campo' será el nombre del campo ACF
    $atts = shortcode_atts(array(
        'campo' => '',
    ), $atts, 'acf');

    // Si no se ha indicado el nombre del campo, no devuelve nada
    if (empty($atts['campo'])) {
        return '';
    }

    // Comprueba que la función get_field existe (es decir, que ACF está activo)
    if (!function_exists('get_field')) {
        return '';
    }

    // Obtiene el valor del campo ACF indicado
    $valor = get_field($atts['campo']);

    // Si el campo no tiene valor, no devuelve nada
    if (empty($valor)) {
        return '';
    }

    // Si el valor es un array y contiene 'url' (caso típico de imagen en ACF)
    if (is_array($valor) && isset($valor['url'])) {
        // Devuelve una etiqueta <img> con la URL de la imagen (escapada por seguridad)
        return '<img src="' . esc_url($valor['url']) . '" alt="">';
    }

    // Si el valor es un string (texto)
    if (is_string($valor)) {
        // Devuelve el texto escapado para evitar problemas de seguridad
        return esc_html($valor);
    }

    // Si no cumple ninguna condición anterior, no devuelve nada
    return '';
}

// Registra el shortcode [acf] en WordPress
add_shortcode('acf', 'mi_plugin_acf_shortcode');
