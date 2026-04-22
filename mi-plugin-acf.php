<?php
/**
 * Plugin Name: Mi Plugin ACF
 * Description: Muestra campos ACF mediante shortcode para WPBakery.
 * Version: 1.0
 * Author: Lucas Román y Víctor Nieves
 */

if (!defined('ABSPATH')) {
    exit;
}

function mi_plugin_acf_shortcode($atts) {
    $atts = shortcode_atts(array(
        'campo' => '',
    ), $atts, 'acf');

    if (empty($atts['campo'])) {
        return '';
    }

    if (!function_exists('get_field')) {
        return '';
    }

    $valor = get_field($atts['campo']);

    if (empty($valor)) {
        return '';
    }

    if (is_array($valor) && isset($valor['url'])) {
        return '<img src="' . esc_url($valor['url']) . '" alt="">';
    }

    if (is_string($valor)) {
        return esc_html($valor);
    }

    return '';
}

add_shortcode('acf', 'mi_plugin_acf_shortcode');