<?php
/*
Plugin Name: WP Dummy Content
Description: Comprehensive dummy content for your WordPress site! Just activate and run from wp-cli to populate your database with many kinds of dummy content.
Author: Rich Collier
Author URI: https://rich.collier.blog
Plugin URI: https://rich.collier.blog
Version: 1.0
*/

// Only load if this is a wp-cli request
if ( defined( 'WP_CLI' ) && WP_CLI ) {

	/**
	 * CLI command to add dummy content to a WordPress site
	 *
	 * @package wp-cli
	 * @since 1.0
	 * @see https://github.com/wp-cli/wp-cli
	 */
	class WP_Dummy_Content_Command extends WP_CLI_Command {
		
		/**
		 * Holder var for dummy text paragraphs
		 *
		 * @access private
		 */
		private $paragraphs = array();

		/**
		 * Holder var for dummy text words
		 *
		 * @access private
		 */
		private $words = array();

		/**
		 * Generate dummy content in a variety of flavors
		 *
		 * ## OPTIONS
		 *
		 * [--users=<int>]
		 * : Number of users to create. Roles will be randomly assigned and exclude administrator role.
		 * ---
		 * default: 0
		 *
		 * [--categories=<int>]
		 * : Number of categories to create. Sorry, category hierarchy is flat at this time.
		 * ---
		 * default: 0
		 *
		 * [--tags=<int>]
		 * : Number of tags to create.
		 * ---
		 * default: 0
		 *
		 * [--posts=<int>]
		 * : Number of posts to create. Posts will randomly import and set a thumbnail, as well as gallery shortcodes.
		 * ---
		 * default: 0
		 *
		 * [--pages=<int>]
		 * : Number of pages to create.
		 * ---
		 * default: 0
		 *
		 * [--comments=<int>]
		 * : Number of comments to create. Comments will be randomly assigned to posts.
		 * ---
		 * default: 0
		 *
		 * [--themes=<int>]
		 * : Number of themes to install.
		 * ---
		 * default: 0
		 *
		 * [--plugins=<int>]
		 * : Number of plugins to install.
		 * ---
		 * default: 0
		 *
		 * ## EXAMPLES
		 *
		 *      wp wdc generate --users=10 --categories=100 --tags=200 --posts=50 --pages=10 --comments=150 --themes=5 --plugins=5
		 *
		 * @subcommand generate
		 * @synopsis [--users=<int>] [--categories=<int>] [--tags=<int>] [--posts=<int>] [--pages=<int>] [--comments=<int>] [--themes=<int>] [--plugins=<int>]
		 */
		function generate( $args, $assoc_args ) {
			// Parse in default arguments
			$args = wp_parse_args( $assoc_args, array(
				'users' => 0,
				'categories' => 0,
				'tags' => 0,
				'posts' => 0,
				'pages' => 0,
				'comments' => 0,
				'themes' => 0,
				'plugins' => 0,
			));

			$db_size_before = $this->get_db_size();

			// Install random themes from WordPress.org
			if ( count( $args['themes'] ) ) {
				$this->install_themes( $args['themes'] );
			}

			// Install random plugins from WordPress.org
			if ( count( $args['plugins'] ) ) {
				$this->install_plugins( $args['plugins'] );
			}

			// Run methods for each content type, passing in the number of objects to create
			if ( count( $args['users'] ) ) {
				$this->create_users( $args['users'] );
			}

			if ( count( $args['categories'] ) ) {
				$this->create_categories( $args['categories'] );
			}

			if ( count( $args['tags'] ) ) {
				$this->create_tags( $args['tags'] );
			}

			if ( count( $args['posts'] ) ) {
				$this->create_posts( $args['posts'] );
			}

			if ( count( $args['pages'] ) ) {
				$this->create_pages( $args['pages'] );
			}

			if ( count( $args['comments'] ) ) {
				$this->create_comments( $args['comments'] );
			}

			$db_size_after = $this->get_db_size();
			$db_size_delta = $db_size_after - $db_size_before;

			WP_CLI::line( sprintf( 'Added %dMB of data. Total DB size is now %dMB.', $db_size_delta, $db_size_after ) );

			WP_CLI::success( 'All finished!' );
		}

		/**
		 * Install random themes from wordpress.org
		 *
		 * @uses wp_remote_get()
		 * @uses WP_CLI::line()
		 *
		 * @param int $count The number of themes to install
		 *
		 * @return null
		 */
		private function install_themes( $count ) {
			// Get the first page
			$feed = wp_remote_get( 'https://wordpress.org/themes/feed/' );
			$feedbody = $feed['body'];

			for ( $i = 2; $i <= ceil( $count / 9 ); $i++ ) {
				$feed = wp_remote_get( 'https://wordpress.org/themes/feed/?paged=' . absint( $i ) );
				$feedbody .= $feed['body'];
			}

			// Match all theme links and create a nice sanitary array
			preg_match_all( '/\<link\>https:\/\/wordpress.org\/themes\/(.*?)\/\<\/link\>/i', $feedbody, $matches );
			$matches = $matches[1];
			$matches = array_unique( $matches );
			unset( $matches[ array_search( '', $matches ) ] );
			$matches = array_values( $matches );

			// Holder for theme slugs
			$install = array();

			// Where are themes installed?
			$themesroot = trailingslashit( ABSPATH ) . 'wp-content/themes/';
			
			// Loop through and create an array of random theme slugs
			for ( $i = 0; $i < $count; $i++ ) {
				$match_i = rand( 0, count( $matches ) - 1 );
				$slug = $matches[ $match_i ];
				$install[] = $slug;
				unset( $matches[ array_search( $slug, $matches ) ] );
				$matches = array_values( $matches );
			}

			// Loop through each selected theme slug and install the theme
			foreach ( $install as $slug ) {
				// Skip if the theme is already installed
				if ( file_exists( trailingslashit( $themesroot ) . $slug ) ) {
					continue;
				}

				// Array of commands to run to install the theme
				$commands = array( 
					sprintf( 'wget -O /tmp/%s.zip https://downloads.wordpress.org/theme/%s.zip', $slug, $slug ),
					sprintf( 'unzip /tmp/%s.zip -d %s', $slug, $themesroot ),
					sprintf( 'rm /tmp/%s.zip', $slug ),
				);

				// Run the command and tell user what we're doing
				foreach ( $commands as $command ) {
					WP_CLI::line( sprintf( 'Executing: %s', $command ) );
					shell_exec( $command );
				}
			}
		}

		/**
		 * Install random plugins from wordpress.org
		 *
		 * @uses wp_remote_get()
		 * @uses WP_CLI::line()
		 *
		 * @param int $count The number of themes to install
		 *
		 * @return null
		 */
		private function install_plugins( $count ) {
			// Get the first page
			$feed = wp_remote_get( 'https://wordpress.org/plugins/browse/popular/' );
			$feedbody = $feed['body'];

			for ( $i = 2; $i <= ceil( $count / 30 ); $i++ ) {
				$url = sprintf( 'https://wordpress.org/plugins/browse/popular/page/%d/', absint( $i ) );
				$feed = wp_remote_get( $url );
				WP_CLI::line( sprintf( 'Fetched %s', $url ) );
				$feedbody .= $feed['body'];
			}

			// Match all theme links and create a nice sanitary array
			preg_match_all( '/<a href=\"https:\/\/wordpress.org\/plugins\/(.*?)\/\" class=\"plugin-icon\"/i', $feedbody, $matches );

			$matches = $matches[1];
			$matches = array_unique( $matches );
			unset( $matches[ array_search( '', $matches ) ] );
			$matches = array_values( $matches );

			// Holder for theme slugs
			$install = array();

			// Where are themes installed?
			$pluginsroot = trailingslashit( ABSPATH ) . 'wp-content/plugins/';

			// Loop through and create an array of random plugin slugs
			for ( $i = 0; $i < $count; $i++ ) {
				$match_i = rand( 0, count( $matches ) - 1 );
				$slug = $matches[ $match_i ];
				$install[] = $slug;
				unset( $matches[ array_search( $slug, $matches ) ] );
				$matches = array_values( $matches );
			}

			// Loop through each selected theme slug and install the theme
			foreach ( $install as $slug ) {
				// Skip if the theme is already installed
				if ( file_exists( trailingslashit( $pluginsroot ) . trailingslashit( $slug ) ) ) {
					continue;
				}

				// Array of commands to run to install the theme
				$commands = array( 
					sprintf( 'wget -O /tmp/%s.zip https://downloads.wordpress.org/plugin/%s.zip', $slug, $slug ),
					sprintf( 'unzip /tmp/%s.zip -d %s', $slug, $pluginsroot ),
					sprintf( 'rm /tmp/%s.zip', $slug ),
				);

				// Run the command and tell user what we're doing
				foreach ( $commands as $command ) {
					WP_CLI::line( sprintf( 'Executing: %s', $command ) );
					shell_exec( $command );
				}

				// Attempt to activate the plugin
				$current = get_option( 'active_plugins' );
				$plugin = plugin_basename( sprintf( '%s%s/%s.php', $pluginsroot, $slug, $slug ) );
				if ( ! in_array( $plugin, $current ) ) {
					$current[] = $plugin;
					sort( $current );
					do_action( 'activate_plugin', trim( $plugin ) );
					update_option( 'active_plugins', $current );
					do_action( 'activate_' . trim( $plugin ) );
					do_action( 'activated_plugin', trim( $plugin ) );
				}
			}
		}

		/**
		 * Insert random comments
		 * 
		 * @param int $count The number of comment objects to insert
		 *
		 * @uses get_user_by()
		 * @uses wp_insert_comment()
		 * @uses is_wp_error()
		 * @uses WP_CLI::line()
		 * @uses WP_CLI::warning()
		 * @uses $this->get_random_author_id()
		 * @uses $this->get_random_post_id()
		 * @uses $this->get_random_comment_content()
		 * @uses $this->get_random_ip_address()
		 * @uses $this->get_random_post_date()		 
		 *
		 * @return null
		 */
		private function create_comments( $count ) {
			// Loop and insert a number of comments
			for ( $i = 0; $i < $count; $i++ ) {
				// Get a random user object
				$comment_author = get_user_by( 'id', $this->get_random_author_id() );

				// Formulate the comment object
				$comment = array(
					'comment_post_ID' => $this->get_random_post_id(),
					'comment_content' => $this->get_random_comment_content(),
					'comment_author_IP' => $this->get_random_ip_address(),
					'comment_date' => $this->get_random_post_date(),
					'comment_author' => $comment_author->user_login,
					'comment_author_email' => $comment_author->user_email,
				);

				// Insert the comment
				$comment_id = wp_insert_comment( $comment );

				// Output a response message based on success
				if ( ! is_wp_error( $comment_id ) ) {
					WP_CLI::line( sprintf( 'Created comment %d by %s for post %d', $comment_id, $comment['comment_author'], $comment['comment_post_ID'] ) );
				} else {
					WP_CLI::warning( sprintf( 'Could not create comment on post %d', $comment['comment_post_ID'] ) );
				}

				// Free some memory
				if ( 0 === $i % 10 ) {
					WP_CLI::line( 'Stopping the insanity' );
					$this->stop_the_insanity();
				}
			}
		}

		/**
		 * Insert random posts
		 * 
		 * @param int $count The number of post objects to insert
		 *
		 * @uses wp_insert_post()
		 * @uses is_wp_error()
		 * @uses wp_set_post_tags()
		 * @uses WP_CLI::line()
		 * @uses WP_CLI::warning()
		 * @uses $this->get_random_post_title()
		 * @uses $this->get_random_author_id()
		 * @uses $this->get_random_post_date()
		 * @uses $this->get_random_post_content()
		 * @uses $this->get_random_post_excerpt()
		 * @uses $this->get_random_post_status()
		 * @uses $this->get_random_categories()
		 * @uses $this->get_random_tags()
		 * @uses $this->get_random_metadata()
		 * @uses $this->get_and_set_random_post_thumbnail()
		 *
		 * @return null
		 */
		private function create_posts( $count ) {
			// Loop and insert a number of posts
			for ( $i = 0; $i < $count; $i++ ) {
				// Create the post object
				$post = array(
					'post_type' => 'post',
					'post_title' => $this->get_random_post_title(),
					'post_author' => $this->get_random_author_id(),
					'post_date' => $this->get_random_post_date(),
					'post_content' => $this->get_random_post_content(),
					'post_excerpt' => $this->get_random_post_excerpt(),
					'post_status' => $this->get_random_post_status(),
					'post_category' => $this->get_random_categories(),
					'meta_input' => $this->get_random_metadata(),
				);

				// Insert the post object
				$post_id = wp_insert_post( $post );

				// Bail early if the post wasn't created
				if ( is_wp_error( $post_id ) ) {
					WP_CLI::warning( sprintf( 'Failed to create post %d', $i ) );

					continue;
				}

				// Set random post tags
				wp_set_post_tags( $post_id, $this->get_random_tags() );

				// Set a few thumbnails to a random number of posts
				if ( rand( 1, 10 ) > 7 ) {
					$attachment_id = $this->get_and_set_random_post_thumbnail( $post_id );
					
					if ( $attachment_id ) {
						WP_CLI::line( sprintf( 'Created and imported attachment %d', $attachment_id ) );
					} else {
						WP_CLI::warning( sprintf( 'Failed to create or import attachment for post %d', $post_id ) );
					}
				}

				// Respond with success
				WP_CLI::line( sprintf( 'Created post %d - %s', $post_id, $post['post_title'] ) );

				// Free some memory
				if ( 0 === $i % 10 ) {
					WP_CLI::line( 'Stopping the insanity' );
					$this->stop_the_insanity();
				}
			}
		}

		/**
		 * Insert random pages
		 * 
		 * @param int $count The number of page objects to insert
		 *
		 * @uses wp_insert_post()
		 * @uses is_wp_error()
		 * @uses WP_CLI::line()
		 * @uses WP_CLI::warning()
		 * @uses $this->get_random_post_title()
		 * @uses $this->get_random_author_id()
		 * @uses $this->get_random_post_date()
		 * @uses $this->get_random_post_content()
		 *
		 * @return null
		 */
		private function create_pages( $count ) {
			// Loop and create a number of pages
			for ( $i = 0; $i < $count; $i++ ) {
				// Create the page object
				$post = array(
					'post_type' => 'page',
					'post_status' => 'publish',
					'post_title' => $this->get_random_post_title(),
					'post_author' => $this->get_random_author_id(),
					'post_date' => $this->get_random_post_date(),
					'post_content' => $this->get_random_post_content( false ),
				);

				// Insert the page object
				$page_id = wp_insert_post( $post );

				// Bail early if the post wasn't created
				if ( is_wp_error( $post_id ) ) {
					WP_CLI::warning( sprintf( 'Failed to create post %d', $i ) );

					continue;
				}

				// Respond with success
				WP_CLI::line( sprintf( 'Created page %d - %s', $page_id, $post['post_title'] ) );

				// Free some memory
				if ( 0 === $i % 10 ) {
					WP_CLI::line( 'Stopping the insanity' );
					$this->stop_the_insanity();
				}
			}
		}

		/**
		 * Insert random categories
		 * 
		 * @param int $count The number of categories to insert
		 *
		 * @uses $this->get_words()
		 * @uses wp_create_category()
		 * @uses is_wp_error()
		 * @uses WP_CLI::line()
		 * @uses WP_CLI::warning()
		 *
		 * @return null
		 */
		private function create_categories( $count ) {
			// Prime the word array
			$words = $this->get_words();

			// Loop to create categories
			for ( $i = 0; $i < $count; $i++ ) {
				// Generate a two-word category name
				$cat_name = sprintf( '%s %s', 
					ucfirst( $words[ rand( 0, count( $words ) - 1 ) ] ),
					ucfirst( $words[ rand( 0, count( $words ) - 1 ) ] )
				);

				// Attempt to insert the category
				$cat_id = wp_create_category( $cat_name );

				// Bail early if the category wasn't created
				if ( is_wp_error( $cat_id ) ) {
					WP_CLI::warning( sprintf( 'Failed to create category %s', $cat_name ) );

					continue;
				}

				// Respond with success
				WP_CLI::line( sprintf( 'Created category %d - %s', $cat_id, $cat_name ) );

				// Free some memory
				if ( 0 === $i % 10 ) {
					WP_CLI::line( 'Stopping the insanity' );
					$this->stop_the_insanity();
				}
			}
		}

		/**
		 * Insert random tags
		 *
		 * @param int $count The number of tags to insert
		 *
		 * @uses $this->get_words()
		 * @uses wp_insert_term()
		 * @uses WP_CLI::warning()
		 * @uses WP_CLI::line()
		 *
		 * @return null
		 */
		public function create_tags( $count ) {
			// Prime the word array
			$words = $this->get_words();

			// Loop to create tags
			for ( $i = 0; $i < $count; $i++ ) {
				// Create a tag name from concatenated words
				$tag_name = sprintf( '%s%s%s', 
					$words[ rand( 0, count( $words ) - 1 ) ],
					$words[ rand( 0, count( $words ) - 1 ) ],
					$words[ rand( 0, count( $words ) - 1 ) ]
				);

				// Attempt to insert the tag
				$tag = wp_insert_term( $tag_name, 'post_tag', array() );

				// Bail early if there was an error
				if ( is_wp_error( $tag ) ) {
					WP_CLI::warning( sprintf( 'Error creating %s', $tag_name ) );

					continue;
				}

				// Respond with success
				WP_CLI::line( sprintf( 'Created tag %d - %s', $tag['term_id'], $tag_name ) );

				// Free some memory
				if ( 0 === $i % 10 ) {
					WP_CLI::line( 'Stopping the insanity' );
					$this->stop_the_insanity();
				}
			}
		}

		/**
		 * Create a number of users
		 *
		 * @param int $count The number of users to create
		 *
		 * @uses wp_remote_get()
		 * @uses username_exists()
		 * @uses wp_insert_user()
		 * @uses wp_generate_password()
		 * @uses is_wp_error()
		 * @uses update_user_meta()
		 * @uses WP_CLI::line()
		 * @uses WP_CLI::warning()
		 *
		 * @return null
		 */
		private function create_users( $count ) {
			// Get random user information from randomuser.me API
			$randomusers = wp_remote_get( 'https://randomuser.me/api/?results=' . absint( $count ) );

			// Decode the response and get the results
			$randomusers = json_decode( $randomusers['body'] );
			$randomusers = $randomusers->results;

			// Loop iteration count
			$i = 0;

			// Loop and create the users
			foreach ( $randomusers as $randomuser ) {
				// Check if the username already exists
				$user_id = username_exists( $randomuser->login->username );
				
				if ( ! $user_id ) {
					// Create the user
					$user_id = wp_insert_user( array(
						'user_login' => $randomuser->login->username,
						'user_pass' => wp_generate_password( 18, true ),
						'user_nicename' => ucfirst( $randomuser->login->username ),
						'user_url' => sprintf( 'http://%s%s.tld', $randomuser->name->first, $randomuser->name->last ),
						'user_email' => $randomuser->email,
						'display_name' => sprintf( '%s %s', ucfirst( $randomuser->name->first ), ucfirst( $randomuser->name->last ) ),
						'nickname' => $randomuser->name->first,
						'first_name' => ucfirst( $randomuser->name->first ),
						'last_name' => ucfirst( $randomuser->name->last ),
						'role' => $this->get_random_role(),
					));

					// Bail early if there was an error creating the user
					if ( is_wp_error( $user_id ) ) {
						WP_CLI::warning( sprintf( 'Error creating user %s', $randomuser->login->username ) );

						continue;
					}

					// Store the original user object, for use later perhaps
					update_user_meta( $user_id, 'original_user_object', $randomuser );
					
					// Add some random metadata to the user
					foreach ( $this->get_random_metadata() as $k => $v ) {
						update_user_meta( $user_id, $k, $v );

						WP_CLI::line( sprintf( 'Adding user meta for %s - %s = %s', $user_id, $k, $v ) );
					}

					// Respond with success
					WP_CLI::line( sprintf( 'Created user %d - %s %s %s', $user_id, $randomuser->login->username, $randomuser->name->first, $randomuser->name->last ) );
				} else {
					// Respond with an error
					WP_CLI::warning( sprintf( 'Cannot create user %s - username already exists', $randomuser->login->username ) );
				}

				// Free some memory
				if ( 0 === $i % 10 ) {
					WP_CLI::line( 'Stopping the insanity' );
					$this->stop_the_insanity();
				}

				// Increment loop iterator
				$i++;
			}
		}

		/**
		 * Get a random role for use in creating users
		 *
		 * @return string The name of a WordPress role
		 */
		private function get_random_role() {
			$roles = array( 'subscriber', 'contributor', 'author', 'editor' );

			return $roles[ rand( 0, count( $roles ) - 1 ) ];
		}

		/**
		 * Get a random valid user ID
		 * 
		 * @uses get_users()
		 * @uses absint()
		 *
		 * @return int Random user ID
		 */
		private function get_random_author_id() {
			$users = get_users( array( 'fields' => 'ids', 'who' => 'authors' ) );

			return absint( $users[ rand( 0, count( $users ) - 1 ) ] );
		}

		/**
		 * Get a random post title
		 *
		 * @var int $min_words Minimum number of words in the title
		 * @var int $max_words Maximum number of words in the title
		 *
		 * @uses $this->get_words()
		 *
		 * @return string A random string of capitalized words
		 */
		private function get_random_post_title( $min_words = 5, $max_words = 20 ) {
			// Prime the word array
			$words = $this->get_words();

			// Holder for the title words
			$title = array();

			// Loop through a random number of iterations between min and max
			for ( $i = 1; $i < rand( $min_words, $max_words ); $i++ ) {
				// Pick out a random word from the words array
				$title[] = ucfirst( $words[ rand( 0, count( $words ) - 1 ) ] );
			}

			// Return the results as a string
			return implode( ' ', $title );
		}

		/**
		 * Get a random filename for a JPG image
		 *
		 * @uses $this->get_words()
		 *
		 * @return string Random JPG file name
		 */
		private function get_random_jpeg_name() {
			// Prime the word array
			$words = $this->get_words();

			// Holder for the filename
			$filename = array();

			// Loop through a random number of iterations between 2 and 4
			for ( $i = 1; $i < rand( 2, 4 ); $i++ ) {
				$filename[] = $words[ rand( 0, count( $words ) - 1 ) ];
			}

			// Return string filename
			return implode( '-', $filename ) . '.jpg';
		}

		/**
		 * Get a random valid post date
		 *
		 * @param string $max_age A strtotime() readable time for max age
		 * 
		 * @return string Random valid post date
		 */
		private function get_random_post_date( $max_age = '10 years ago' ) {
			// Get the min and max times
			$oldest = date( 'U', strtotime( $max_age ) );
			$newest = date( 'U', strtotime( 'now' ) );

			// Return a random formatted date between min and max age
			return date( 'Y-m-d H:i:s', rand( $oldest, $newest ) );
		}

		/**
		 * Get a random number of paragraphs for use as post content. May or may not
		 * also include gallery shortcodes.
		 *
		 * @param bool $include_galleries True to include gallery shortcodes. False to omit.
		 *
		 * @uses $this->get_words()
		 * @uses $this->paragraphs
		 * @uses $this->get_random_gallery_shortcode()
		 *
		 * @return string Random post content
		 */ 
		private function get_random_post_content( $include_galleries = true, $min_para = 3, $max_para = 20 ) {
			// Prime the word and paragraph arrays
			$words = $this->get_words();
			$paragraphs = $this->paragraphs;

			// Holder for all paragraphs
			$content = array();

			// Make a random number of iterations
			for ( $i = 1; $i < rand( $min_para, $max_para ); $i++ ) {
				// Add a random paragraph
				$content[] = $paragraphs[ rand( 0, count( $paragraphs ) - 1 ) ];
			}

			// Include a random gallery shortcode if requested
			if ( $include_galleries ) {
				if ( rand( 1, 10 ) > 7 ) {
					$content[] = $this->get_random_gallery_shortcode();
				}
			}

			// Return the content as a string
			return implode( "\n\n", $content );
		}

		/**
		 * Get random content for a comment
		 *
		 * @uses $this->get_words()
		 * @uses $this-paragraphs
		 *
		 * @return string Random text for a comment content
		 */
		private function get_random_comment_content() {
			// Prime words and paragraphs arrays
			$words = $this->get_words();
			$paragraphs = $this->paragraphs;

			// Return a random paragraph for the content
			return $paragraphs[ rand( 0, count( $paragraphs ) - 1 ) ];
		}

		/**
		 * Get a random IP address - may not be valid
		 *
		 * @return string A random IP address
		 */
		private function get_random_ip_address() {
			// Holder for all four octets
			$octets = array();

			// Add a random octet
			for ( $i = 1; $i <= 4; $i++ ) {
				$octets[] = rand( 1, 255 );
			}

			// Return as string
			return implode( '.', $octets );
		}

		/**
		 * Create a random post excerpt
		 *
		 * @uses $this->get_words()
		 * @uses $this->paragraphs
		 *
		 * @return string A random paragraph for an excerpt
		 */
		private function get_random_post_excerpt() {
			// Prime the words and paragraphs array
			$words = $this->get_words();
			$paragraphs = $this->paragraphs;

			// Return a string
			return $paragraphs[ rand( 0, count( $paragraphs ) - 1 ) ];
		}

		/**
		 * Get an array of valid category ids
		 *
		 * @param int $min_cats Minimum number of categories to return
		 * @param int $max_cats Maximum number of categories to return
		 *
		 * @uses get_terms()
		 *
		 * @return array An array of random category IDs
		 */
		private function get_random_categories( $min_cats = 1, $max_cats = 5 ) {
			// Get all categories
			$categories = get_terms( 'category', array( 'hide_empty' => false, 'fields' => 'ids' ) );

			// Cap the maximum number of categories at the maximum number that exist
			$max_cats = ( $max_cats > count( $categories ) ) ? count( $categories ) : $max_cats;

			// Holder for our IDs
			$cat_ids = array();

			// Loop through a random number of times
			for ( $i = 0; $i < rand( $min_cats, $max_cats ); $i++ ) {
				// Add the category ID to our array
				$cat_ids[] = $categories[ rand( 0, count( $categories ) - 1 ) ];
			}

			// Unique and rebase our categories
			$cat_ids = array_unique( $cat_ids );
			$cat_ids = array_values( $cat_ids );

			// Send the results back
			return $cat_ids;
		}

		/**
		 * Get an array of valid tag IDs
		 *
		 * @param int $min_tags Minimum number of tags to retrieve
		 * @param int $max_tags Maximum number of tags to retrieve
		 *
		 * @uses get_terms()
		 *
		 * @return array Array of random tag IDs
		 */
		private function get_random_tags( $min_tags = 1, $max_tags = 20 ) {
			// Get all tags
			$tags = get_terms( 'post_tag', array( 'hide_empty' => false, 'fields' => 'ids' ) );

			// Cap the maximum number of tags at the maximum number that exist
			$max_tags = ( $max_tags > count( $tags ) ) ? count( $tags ) : $max_tags;

			// Holder for our tags
			$tag_ids = array();

			// Loop randomly and get random tags
			for ( $i = 0; $i < rand( $min_tags, $max_tags ); $i++ ) {
				$tag_ids[] = $tags[ rand( 0, count( $tags ) - 1 ) ];
			}

			// Return the array
			return $tag_ids;
		}

		/**
		 * Get an array of random metadata key-value pairs
		 *
		 * @param int $min_pairs Minimum number of pairs to return
		 * @param int $max_pairs Maximum number of pairs to return
		 *
		 * @uses $this->get_words()
		 *
		 * @return array Random key-value pairs
		 */
		private function get_random_metadata( $min_pairs = 1, $max_pairs = 10 ) {
			// Prime the words array
			$words = $this->get_words();

			// Holder for our key-value pairs
			$meta = array();

			// Make a random number of iterations and generate key-value pairs
			for ( $i = 1; $i < rand( $min_pairs, $max_pairs ); $i++ ) {
				$key = $words[ rand( 0, count( $words ) -1 ) ];
				$value = $words[ rand( 0, count( $words ) - 1 ) ];

				$meta[ $key ] = $value;
			}

			// Return the pairs
			return $meta;
		}

		/**
		 * Get a random post status, draft or publish
		 * 
		 * @param int $percent_drafts Percentage of drafts to return
		 * 
		 * @return string Returns draft or publish
		 */
		private function get_random_post_status( $percent_drafts = 10 ) {
			// Get a random integer between 1 and 100
			$rand = rand( 1, 100 );

			// Return the result of the random number
			if ( $rand < $percent_drafts ) {
				return 'draft';
			} else {
				return 'publish';
			}
		}

		/**
		 * Get a random valid post ID
		 *
		 * @uses get_posts()
		 *
		 * @return int A single random post ID
		 */
		private function get_random_post_id() {
			// Get all post IDs
			$post_ids = get_posts( array( 'posts_per_page' => -1, 'fields' => 'ids' ) );

			// Return a random ID
			return $post_ids[ array_rand( $post_ids ) ];
		}

		/**
		 * Get a huge array of random words from the Bacon Ipsum API
		 *
		 * @uses wp_remote_get()
		 * @uses $this->words
		 * @uses $this->paragraphs
		 *
		 * @return array Array of lowercase words made of strictly alpha characters
		 */
		private function get_words() {
			// If we have no words this is the first time this function has been called
			if ( ! count( $this->words ) ) {
				// Get paragraphs from the Bacon Ipsum API and decode into paragraphs
				$paras = wp_remote_get( 'https://baconipsum.com/api/?type=meat-and-filler&paras=100' );
				$paras = json_decode( $paras['body'] );

				// Store the paragraphs for later use
				$this->paragraphs = $paras;
					
				// Loop through paragraphs from API
				foreach ( $paras as $paragraph ) {
					// Remove non-alpha characters
					$paragraph = preg_replace( '/[^A-Za-z ]/', '', $paragraph );
					
					// Lowercase everything
					$paragraph = strtolower( $paragraph );
					
					// Replace multiple spaces with a single space
					$paragraph = preg_replace( '!\s+!', ' ', $paragraph );
					
					// Trim leading and trailing whitespace
					$paragraph = trim( $paragraph );

					// Add all words to the words array
					$this->words = array_merge( $this->words, explode( ' ', $paragraph ) );
				}

				// Create a nice unique rebased array of words
				$this->words = array_unique( $this->words );
				$this->words = array_values( $this->words );
			}

			// Return the word array
			return $this->words;
		}

		/**
		 * Formulate a random gallery shortcode from valid images
		 *
		 * @uses get_posts()
		 *
		 * @return string Random gallery shortcode
		 */
		private function get_random_gallery_shortcode() {
			// Get all attachment IDs
			$post_ids = get_posts( array( 'post_type' => 'attachment', 'fields' => 'ids', 'posts_per_page' => -1 ) );
			
			// Holder for our media ids
			$media_ids = array();

			// Minimum number of images in the gallery
			$min = 3;

			// Maximum number of images in the gallery
			$max = ( count( $post_ids ) > 10  ) ? 10 : count( $post_ids );

			// Bail if we don't have enough images
			if ( ! ( count( $post_ids ) && $max > $min ) ) {
				return false;
			}

			// Add media ids at random
			for ( $i = 1; $i < rand( $min, $max ); $i++ ) {
				$idx = rand( 0, count( $post_ids ) );
				$media_ids[] = $post_ids[ rand( 0, count( $post_ids ) - 1 ) ];
			}

			// Unique and rebased array
			$media_ids = array_unique( $media_ids );
			$media_ids = array_values( $media_ids );

			// Return a formatted shortcode
			return sprintf( '[gallery ids="%s"]', implode( ',', $media_ids ) );
		}

		/**
		 * Fetch a random image from the unsplash.it API and assign it as a post ID
		 *
		 * @param int $post_id The post ID to assign the image to
		 *
		 * @uses WP_CLI::line()
		 * @uses has_post_thumbnail()
		 * @uses wp_upload_dir()
		 * @uses sanitize_file_name()
		 * @uses $this->get_random_jpeg_name()
		 * @uses wp_mkdir_p()
		 * @uses trailingslashit()
		 * @uses wp_check_filetype()
		 * @uses $this->get_random_post_title()
		 * @uses $this->get_random_post_excerpt()
		 * @uses wp_insert_attachment()
		 * @uses wp_generate_attachment_metadata()
		 * @uses wp_update_attachment_metadata()
		 * @uses set_post_thumbnail()
		 *
		 * @return mixed False on failure, integer attachment id on success
		 */
		private function get_and_set_random_post_thumbnail( $post_id ) {
			// Get all existing media IDs
			$media_ids = get_posts( array( 'posts_per_page' => -1, 'post_type' => 'attachment', 'fields' => 'ids' ) );

			// If we already have 300 images, go ahead and use an existing one
			if ( count( $media_ids > 300 ) ) {
				// Pick at random
				$media_id = $media_ids[ rand( 0, count( $media_ids ) - 1 ) ];

				// Assign to the post
				set_post_thumbnail( $post_id, $media_id );

				// Report status
				WP_CLI::line( sprintf( 'Skipped downloading image, instead assigning %s to %s', $media_id, $post_id ) );

				// Bail early
				return $media_id;
			}

			// Sleep so we're not fetching images too fast from the API
			WP_CLI::line( 'Sleeping for 5 seconds so we don\'t hit API limits' );
			sleep( 5 );

			// Bail if we already have a thumbnail
			if ( has_post_thumbnail( $post_id ) ) {
				return false;
			}

			// Get the upload directory, file contents, and filename
			$upload_dir = wp_upload_dir();
			$image_contents = file_get_contents( 'https://unsplash.it/1920/1028/?random' );
			$filename = sanitize_file_name( basename( $this->get_random_jpeg_name() ) );

			// Create the uploads directory if needed and get the full file path
			if ( wp_mkdir_p( $upload_dir['path'] ) ) {
				$file = trailingslashit( $upload_dir['path'] ) . $filename;
			} else {
				$file = trailingslashit( $upload_dir['basedir'] ) . $filename;
			}

			// Write the file to disk
			file_put_contents( $file, $image_contents );

			// Get the mime information
			$mimetype = wp_check_filetype( $filename, null );

			// Create the attachment object
			$attachment = array( 
				'post_mime_type' => $mimetype['type'],
				'post_title' => $this->get_random_post_title(),
				'post_content' => $this->get_random_post_excerpt(),
				'post_status' => 'inherit',
			);

			// Attempt to insert the attachment
			$attach_id = wp_insert_attachment( $attachment, $file, $post_id );

			// Dependency for attachment metadata functions
			require_once( ABSPATH . 'wp-admin/includes/image.php' );

			// Generate and store the attachment meta
			$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
			wp_update_attachment_metadata( $attach_id, $attach_data );

			// Set the post thumbnail and return the status
			if ( set_post_thumbnail( $post_id, $attach_id ) ) {
				return $attach_id;
			} else {
				return false;
			}
		}

		/**
		 * Get the MySQL database size in MB
		 *
		 * @global $wpdb
		 * @uses $wpdb->get_results
		 * @uses DB_NAME
		 *
		 * @return float Database size in MB
		 */
		private function get_db_size() {
			global $wpdb;

			// Get db size of all dbs
			$sql = "SELECT table_schema 'dbname', Round(Sum(data_length + index_length) / 1024 / 1024, 1) 'dbsize' FROM information_schema.tables GROUP BY table_schema;";

			// Do the query
			$results = $wpdb->get_results( $sql );

			// Loop through results and get the one we want
			foreach ( $results as $result ) {
				if ( DB_NAME === $result->dbname ) {
					// Return db size
					return floatval( $result->dbsize );
				}
			}

			// Failure
			return false;
		}

		/**
		 * Free memory during loops
		 *
		 * @param int $delay Time in seconds to sleep
		 *
		 * @global $wpdb WordPress database object
		 * @global $wp_object_cache WordPress object cache object
		 *
		 * @return void
		 */
		protected function stop_the_insanity( $delay = 0 ) {
			global $wpdb, $wp_object_cache;
			
			// Free database queries
			$wpdb->queries = array();
			
			// Return if not using object cache
			if ( ! is_object( $wp_object_cache ) )
				return;
			
			// Free object cache data
			$wp_object_cache->group_ops = array();
			$wp_object_cache->stats = array();
			$wp_object_cache->memcache_debug = array();
			$wp_object_cache->cache = array();
			
			// Unclear what this does, but works on wpcom
			if ( is_callable( $wp_object_cache, '__remoteset' ) )
				$wp_object_cache->__remoteset();
			// Sleep if required
			if ( $delay ) {
				sleep( $delay );
			}
		}

	}

	WP_CLI::add_command( 'wdc', 'WP_Dummy_Content_Command' );
}
