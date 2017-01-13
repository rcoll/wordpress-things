<?php

/** This is just a slightly different way of getting a previous post than 
get_adjacent_post(), this function will grab a post in a SINGLE specific 
category. We'll go ahead and return it as a permalink since that's ultimately 
what we want anyways. */

function rdc_get_prev_url_in_category( $in_category ) {
    global $post, $wpdb;
     
    // Convert category to id if it's a slug
    if ( ! is_numeric( $in_category ) )
        $in_category = get_category_by_slug( $in_category )->term_id;
 
    // This query will grab the preceding post id in $in_category
    $query = $wpdb->prepare( "
        SELECT p.ID FROM $wpdb->posts AS p 
        INNER JOIN $wpdb->term_relationships AS tr ON p.ID = tr.object_id 
        INNER JOIN $wpdb->term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id 
        AND tt.taxonomy = 'category'
        AND tt.term_id IN (%d) 
        WHERE p.ID < %d 
        AND p.post_type = 'post'
        AND p.post_status = 'publish'
        ORDER BY p.ID DESC LIMIT 1 
    ", $in_category, $post->ID );
     
    // Formulate cache key and see if it exists
    $query_key = 'rdc_previous_post_' . md5( $query );
    $result = wp_cache_get( $query_key );
 
    // Cache key exists so lets use it
    if ( false !== $result ) {
        if ( $result )
            $prev_post = get_post( $result );
        return get_permalink( $prev_post->ID );
    }
     
    // Cache key didn't exist, lets run a new query
    $result = $wpdb->get_var( $query );
 
    // Query failed for some reason, probably this is
    // the first post in the category. Go home instead.
    if ( null === $result )
        return get_bloginfo( 'home' );
     
    // Save query result for use later
    wp_cache_set( $query_key, $result );
     
    // Return the permalink of the resulting post id
    if ( $result ) {
        $prev_post = get_post( $result );
        return get_permalink( $prev_post->ID );
    }
     
    // Something crazy happened to get here, but you 
    // never know ...
    return get_bloginfo( 'home' );  
}

// omit
