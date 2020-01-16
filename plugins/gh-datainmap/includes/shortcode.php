<?php

add_shortcode( 'datainmap', 'gh_dim_shortcode' );
function gh_dim_shortcode($atts, $content = null) {
    $settings = get_option('gh-datainmap-settings');
    // Override settings from attributes
    $args = shortcode_atts( [
            'types' => null,
            'zoom' => $settings['zoom'],
            'min_zoom' => $settings['minZoom'],
            'max_zoom' => $settings['maxZoom'],
            'center_x' => $settings['center_x'],
            'center_y' => $settings['center_y'],
            'projection' => $settings['projection'],
            'layers' => null,
            'single_cluster' => 0,
            'single_cluster_distance' => 75,
            'enable_search' => 1,
            'enable_feature_dialog' => 1,
        ], $atts );
    $settings['zoom'] = (int)$args['zoom'];
    $settings['minZoom'] = (int)$args['min_zoom'];
    $settings['maxZoom'] = (int)$args['max_zoom'];
    $settings['center_x'] = $args['center_x'];
    $settings['center_y'] = $args['center_y'];
    $settings['projection'] = $args['projection'];
    $settings['single_cluster'] = $args['single_cluster'] == 1 ? true : false;
    $settings['single_cluster_distance'] = (int)$args['single_cluster_distance'];
    $settings['enable_search'] = $args['enable_search'] == 1 ? true : false;
    $settings['enable_feature_dialog'] = $args['enable_feature_dialog'] == 1 ? true : false;

    $layers = get_posts([
        'post_type' => 'gh-dim-layers',
        'include' => explode(',', $args['layers']),
        'orderby' => 'post__in',
    ]);
    $map_layers = array_map(function($post) {
        return [
            'title' => get_the_title( $post->ID ),
            'type' => get_post_meta($post->ID, 'gh_dim_layer_type', true),
            'url' => get_post_meta($post->ID, 'gh_dim_layer_url', true),
            'name' => get_post_meta($post->ID, 'gh_dim_layer_name', true),
            'opacity' => (float)get_post_meta($post->ID, 'gh_dim_layer_opacity', true),
            'matrixset' => get_post_meta($post->ID, 'gh_dim_layer_maxtrixset', true),
            'kml_ignore_style' => (bool)get_post_meta($post->ID, 'gh_dim_kml_ignore_style', true),
        ];
    }, $layers);

    $terms = [];
    if(strlen($args['types']) > 0) {
        $term_taxonomy_ids = explode(',', $args['types']);
        $terms = get_terms([
            'taxonomy' => 'gh-dim-location-types',
            'term_taxonomy_id' => $term_taxonomy_ids,
            'hide_empty' => false,
        ]);
    }

    $location_layers = array_map(function($term) {
        $locations = get_posts([
            'post_type' => 'gh-dim-locations',
            'posts_per_page' => -1,
            'tax_query' => [
                [
                    'taxonomy' => 'gh-dim-location-types',
                    'field' => 'term_id',
                    'terms' => $term->term_id,
                ]
            ]
        ]);
        $features = array_map(function($post) use($term) {
            $location = get_post_meta( $post->ID, 'gh_dim_location', true);
            $location_type = get_post_meta( $post->ID, 'gh_dim_location_type', true);
            return [
                'location_type' => $location_type,
                'location' => json_decode($location),
                'feature_id' => $post->ID,
                'title' => get_the_title( $post ),
                'term' => $term->slug,
            ];
        }, $locations);
        $icon = null;
        $icon_media_id = get_term_meta( $term->term_id, 'category-image-id', true );
        if($icon_media_id) {
            $icon = wp_get_attachment_image_src($icon_media_id, 'full')[0];
        }
        return [
            'name' => $term->name,
            'term_id' => $term->term_id,
            'slug' => $term->slug,
            'description' => $term->description,
            'icon' => $icon,
            'features' => $features,
            'cluster' => get_term_meta( $term->term_id, 'cluster', true ),
            'cluster_distance' => get_term_meta( $term->term_id, 'cluster_distance', true ),
        ];
    }, $terms);

    $pro4j = gh_dim_parse_pro4j($settings['projections']);
    unset($settings['projections']);

    $el_id = uniqid('gh-datainmap-');
    $settings['element'] = $el_id;

    if($settings['include_default_style']) {
        wp_enqueue_style( 'gh-dim-style' );
    }

    wp_enqueue_script( 'gh-dim-datainmap' );
    $security = wp_create_nonce('gh-dim-datainmap');
    wp_localize_script( 'gh-dim-datainmap', 'GHDataInMap', [
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'fetchFeatureUrl' => admin_url( 'admin-ajax.php' ) . '?action=gh_dim_get_location_info&security=' . $security . '&location_id=',
        'security' => $security,
        'settings' => $settings,
        'location_layers' => $location_layers,
        'map_layers' => $map_layers,
        'pro4j' => $pro4j,
    ] );
    $output = '<div id="'.$el_id.'">'.__('Loading...', 'gh-datainmap').'</div>';
    return $output;
}