<?php

/** This is just a collection of WP-CLI functions that I've kept in my toolkit to 
help take care or random tasks */

if ( defined( 'WP_CLI' ) && WP_CLI ) {

	class RDC_Refactor_Command extends WP_CLI_Command {
		
		// Just a little test method to make sure we are working properly 
		function hello( $args ) {
			// No name was entered
			if ( ! count( $args ) )
				WP_CLI::error( 'You didn\'t enter your name!' );
			
			// More than one name was entered
			if ( count( $args ) > 1 )
				WP_CLI::error( 'I can only say hi to one person and you entered ' . count( $args ) . ' names!' );
			
			// Sanitize the name and say hello
			$name = esc_attr( $args[0] );
			WP_CLI::success( "Hello, $name!" );
		}
		
		/** Delete all users of the subscriber role (use -f to force removal even if
		the user has posts published in WordPress */
		function rmsubscribers( $args ) {
			$all_users = get_users( array( 'role' => 'subscriber' ) );
			
			foreach ( $all_users as $single_user ) {
				$users_posts = get_posts( array( 'author' => $single_user->ID ) );
				
				$_force = ( '-f' === $args[0] ) ? true : false;
				
				if ( ! $users_posts || $_force ) {
					if ( wp_delete_user( $single_user->ID ) )
						WP_CLI::line( 'User ' . $single_user->ID . ' deleted' );
					else
						WP_CLI::line( 'Failed to delete user ' . $single_user->ID );
				}
			}
		}
		
		// Assign all media gallery images to posts randomly (if you have enough memory)
		function rndfeatimgs( $args ) {
			
			// Get all posts
			$posts = new WP_Query( array( 
				'posts_per_page' => -1, 
				'post_type' => 'post', 
			));
			
			$post_ids = array();
			
			// Turn them into an array of post IDs
			while ( $posts->have_posts() ) {
				$posts->the_post();
				array_push( $post_ids, get_the_ID() );      
			}
			
			wp_reset_postdata();
			
			// Get all attachments
			$attachments = new WP_Query( array( 
				'posts_per_page' => -1, 
				'post_type' => 'attachment', 
				'post_status' => 'inherit', 
			));
			
			$attachment_ids = array();
			
			// Turn them into an array of attachment IDs
			while ( $attachments->have_posts() ) {
				$attachments->the_post();
				array_push( $attachment_ids, get_the_ID() );
			}
			
			wp_reset_postdata();
			
			// Now for the magic - loop through each post and randomly assign one of the attachments
			foreach ( $post_ids as $post_id ) {
				$attachment_id = rand( 0, count( $attachment_ids ) - 1 );
				
				WP_CLI::line( 'post:' . $post_id . ' attachment:' . $attachment_ids[$attachment_id] );
				
				if ( update_post_meta( $post_id, '_thumbnail_id', $attachment_ids[$attachment_id] ) )
					WP_CLI::line( 'SUCCESS' );
				else
					WP_CLI::line( 'FAILED' );
			}
			
			WP_CLI::success( 'script complete' );
		}
		
	}
	
	WP_CLI::add_command( 'refactor', 'RDC_Refactor_Command' );
	
}

// omit