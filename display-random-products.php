<?php
/*
Plugin Name: Display Random Products
Description: It displays user a random type of products every time whenever page is refreshed.
Version: 1.0
Author: Tanmay Patil
*/

// Define a custom endpoint to retrieve random products
add_action('rest_api_init', function () {
    register_rest_route('wc/v3', '/products/random', array(
        'methods' => 'GET',
        'callback' => 'get_random_products',
    ));
});

function get_random_products($request) {
    global $wpdb;

    $product_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish' ORDER BY RAND() LIMIT 5");
    // Query to fetch _wc_points_max_discount meta value
    $max_discount_query = $wpdb->prepare("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wc_points_max_discount' AND post_id = %d", $product_ids);
  
    $results = array();

    foreach ($product_ids as $product_id) {
        $product = wc_get_product($product_id);
        $product_data = $product->get_data();
        $product_images = $product->get_gallery_image_ids();
        $product_categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'names'));

        $product_type = !empty($product_data['type']) ? $product_data['type'] : 'simple';
        $max_discount = $wpdb->get_var($max_discount_query);

        $results[] = array(
            'id' => $product_data['id'],
            'name' => $product_data['name'],
            'slug' => $product_data['slug'],
            'permalink' => get_permalink($product_id),
            'date_created' => $product_data['date_created'],
            'type' => $product_type,
            'status' => $product_data['status'],
            'description' => $product_data['description'],
            'short_description' => $product_data['short_description'],
            'sku' => $product_data['sku'],
            'price' => $product_data['regular_price'],
            'images' => array_map(function($image_id) {
                return wp_get_attachment_url($image_id);
            }, $product_images),
            'categories' => $product_categories,
            'dimensions' => array(
                'length' => $product_data['length'],
                'width' => $product_data['width'],
                'height' => $product_data['height'],
            ),
            'weight' => $product_data['weight'],
            'wc_points_max_discount' => $max_discount,
        );
    }

    return rest_ensure_response($results);
}
