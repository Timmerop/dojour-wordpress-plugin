<?php
/*
* Template Name: Dojour Events
* Template Post Type: dojour_event
*/
	$posts = get_posts (['post_type'  => 'dojour_event']);
?>

<?php get_header (); ?>
<h1>Dojour Events</h1>
<?php
	while (have_posts ()) {
		the_post();
	}
?>

<?php get_footer (); ?>

