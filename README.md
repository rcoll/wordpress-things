WordPress Things!
================

A collection of random pieces of WordPress code

== get-previous-url-in-category.php ==
This file contains a function for grabbing the previous post in a single category. Similar 
to WordPress' built-in function get_adjacent_post() but different in that this function only 
requires a single category to be passed and will look for the previous post that matches 
that single category.

== jetpack-views-column.php ==
Here is a function for displaying how many views each post has received in the posts 
column. The data is sourced from Jetpack.

== rewrite-images.php ==
A function that filters the_post_thumbnail() and get_the_post_thumbnail() and replaces the 
source domain. Can be helpful for development and staging servers to prevent having to sync 
large amounts of content images. Your development or staging server will instead serve out 
content with the image sources on the production domain.

== wpcli-refactor.php ==
This is a wp-cli command which contains several functions I have found to be useful in the 
systems world. See inline documentation for more information.
