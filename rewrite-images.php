<?php
 
function rewrite_images( $atts ) {
    $atts['src'] = str_replace( 'dev.yourserver.com', 'yourserver.com', $atts['src'] );
    return $atts;
}
add_filter( 'wp_get_attachment_image_attributes', 'rewrite_images' );

// omit
