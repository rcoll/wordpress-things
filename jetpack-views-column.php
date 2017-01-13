<?php

// Add a Views columns to insert jetpack postviews data into
function rdc_add_views_column( $cols ) {
        $cols['pageviews'] = 'Views';
 
        return $cols;
}
add_filter( 'manage_edit-post_columns', 'rdc_add_views_column' );
 
// Grab and display the postviews data from Jetpack for each post
function rdc_add_views_colurdc_data( $colname ) {
    global $post;
 
    // Make sure we're inserting into the correct column
    if ( 'pageviews' !== $colname )
        return false;
 
    // Make sure jetpack and stats are available
    if ( ! ( class_exists( 'Jetpack' ) && Jetpack::is_module_active( 'stats' ) ) ) {
        echo 'Error 101';
        return false;
    }
 
    // Make sure stats_get_csv is available
    if ( ! function_exists( 'stats_get_csv' ) ) {
        echo 'Error 102';
        return false;
    }
 
    // Get the post data from Jetpack
    $postviews = stats_get_csv( 'postviews', "post_id={$post->ID}" );
 
    // We have a problem if there was no data returned
    if ( ! $postviews ) {
        echo 'Error 103';
        return false;
    }
 
    // Print Jetpack post views
    echo '<strong>' . number_format( $postviews[0]['views'] ) . '</strong>';
}
add_action( 'manage_posts_custom_column', 'rdc_add_views_colurdc_data' );

// omit