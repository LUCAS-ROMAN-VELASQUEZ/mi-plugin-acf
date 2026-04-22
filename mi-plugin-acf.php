<?php
/**
 * Plugin Name: Mi Plugin Prensa ACF
 * Description: Crea un tipo de contenido Prensa y muestra noticias en grid con ACF.
 * Version: 1.1
 * Author: Lucas Román y Víctor Nieves
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registrar Custom Post Type: Prensa
 */
function mi_prensa_registrar_cpt() {
    $labels = array(
        'name'               => 'Prensa',
        'singular_name'      => 'Noticia de prensa',
        'menu_name'          => 'Prensa',
        'name_admin_bar'     => 'Prensa',
        'add_new'            => 'Añadir nueva',
        'add_new_item'       => 'Añadir nueva noticia',
        'new_item'           => 'Nueva noticia',
        'edit_item'          => 'Editar noticia',
        'view_item'          => 'Ver noticia',
        'all_items'          => 'Todas las noticias',
        'search_items'       => 'Buscar noticias',
        'not_found'          => 'No se encontraron noticias',
        'not_found_in_trash' => 'No se encontraron noticias en la papelera',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => false,
        'menu_icon'          => 'dashicons-megaphone',
        'supports'           => array('title'),
        'show_in_rest'       => true,
        'rewrite'            => array('slug' => 'prensa'),
    );

    register_post_type('prensa', $args);
}
add_action('init', 'mi_prensa_registrar_cpt');

/**
 * Shortcode para mostrar grid de prensa
 */
function mi_prensa_grid_shortcode($atts) {
    if (!function_exists('get_field')) {
        return '<p>ACF no está activo.</p>';
    }

    $args = array(
        'post_type'      => 'prensa',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    $query = new WP_Query($args);

    if (!$query->have_posts()) {
        return '<p>No hay noticias de prensa disponibles.</p>';
    }

    ob_start();

    echo '<div class="mi-prensa-grid">';

    while ($query->have_posts()) {
        $query->the_post();

        $titulo  = get_the_title();
        $fecha   = get_field('fecha');
        $medio   = get_field('medio');
        $resumen = get_field('resumen');
        $enlace  = get_field('enlace_externo');
        $imagen  = get_field('imagen');

        echo '<div class="mi-prensa-card">';

        if (!empty($imagen)) {
            echo '<div class="mi-prensa-card__img">';

            if (is_array($imagen) && !empty($imagen['url'])) {
                $alt = !empty($imagen['alt']) ? $imagen['alt'] : $titulo;
                echo '<img src="' . esc_url($imagen['url']) . '" alt="' . esc_attr($alt) . '">';
            } elseif (is_numeric($imagen)) {
                echo wp_get_attachment_image((int) $imagen, 'medium_large');
            } elseif (is_string($imagen) && filter_var($imagen, FILTER_VALIDATE_URL)) {
                echo '<img src="' . esc_url($imagen) . '" alt="' . esc_attr($titulo) . '">';
            }

            echo '</div>';
        }

        echo '<div class="mi-prensa-card__content">';

        if (!empty($fecha)) {
            echo '<div class="mi-prensa-card__fecha">' . esc_html($fecha) . '</div>';
        }

        echo '<h3 class="mi-prensa-card__titulo">' . esc_html($titulo) . '</h3>';

        if (!empty($resumen)) {
            echo '<div class="mi-prensa-card__resumen">' . esc_html($resumen) . '</div>';
        }

        if (!empty($medio)) {
            echo '<div class="mi-prensa-card__medio"><strong>Medio:</strong> ' . esc_html($medio) . '</div>';
        }

        if (!empty($enlace)) {
            echo '<div class="mi-prensa-card__link">';
            echo '<a href="' . esc_url($enlace) . '" target="_blank" rel="noopener noreferrer">Leer noticia</a>';
            echo '</div>';
        }

        echo '</div>';
        echo '</div>';
    }

    echo '</div>';

    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode('prensa_grid', 'mi_prensa_grid_shortcode');

/**
 * Estilos del grid
 */
function mi_prensa_grid_estilos() {
    ?>
    <style>
        .mi-prensa-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin: 40px 0;
        }

        .mi-prensa-card {
            background: #ffffff;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            height: 100%;
        }

        .mi-prensa-card__img img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            display: block;
        }

        .mi-prensa-card__content {
            padding: 20px;
        }

        .mi-prensa-card__fecha {
            font-size: 14px;
            color: #666666;
            margin-bottom: 10px;
        }

        .mi-prensa-card__titulo {
            font-size: 22px;
            line-height: 1.3;
            margin: 0 0 15px 0;
            color: #111111;
        }

        .mi-prensa-card__resumen {
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 15px;
            color: #444444;
        }

        .mi-prensa-card__medio {
            font-size: 14px;
            margin-bottom: 15px;
            color: #333333;
        }

        .mi-prensa-card__link a {
            display: inline-block;
            background: #004d82;
            color: #ffffff;
            padding: 10px 16px;
            border-radius: 6px;
            text-decoration: none;
        }

        .mi-prensa-card__link a:hover {
            opacity: 0.9;
            color: #ffffff;
        }

        @media (max-width: 1024px) {
            .mi-prensa-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 767px) {
            .mi-prensa-grid {
                grid-template-columns: 1fr;
            }

            .mi-prensa-card__img img {
                height: auto;
            }
        }
    </style>
    <?php
}
add_action('wp_head', 'mi_prensa_grid_estilos');