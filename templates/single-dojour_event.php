<?php
/*
* Template Name: Dojour Event
* Template Post Type: dojour_event
*/

$post = get_post ();

$remote_url = get_post_meta ($post -> ID, 'remote_url', true);

$start_date =  get_post_meta ($post -> ID, 'start_date', true);
$start_time =  get_post_meta ($post -> ID, 'start_time', true);

$end_date =  get_post_meta ($post -> ID, 'end_date', true);
$end_date =  get_post_meta ($post -> ID, 'end_date', true);

$door_time = $start_date =  get_post_meta ($post -> ID, 'door_time', true);

$event_time = '';

if ($start_time) {
	$event_time = '<p><b>' . $start_time;

	if ($end_time) {
		$event_time .= ' - ' . $end_time;
	}

	if ($door_time) {
		$event_time .= ', doors open at ' . $door_time;
	}

	$event_time = '</b></p>';
}

$location_title = get_post_meta ($post -> ID, 'location_title');
$location_address = get_post_meta ($post -> ID, 'location_address');

get_header ();

?>
<div class="container">
	<article id="post-<?php the_ID(); ?>" class="dojour_event dojour_event--singular post type-post status-publish format-standard has-post-thumbnail">
		<header class="dojour_event__header entry-header">
			<?php if (has_post_thumbnail ()): ?>
			<div class="dojour_event__cover">
				<?php the_post_thumbnail ('full') ?>
			</div>
			<?php endif; ?>

			<div class="dojour_event__title">
			<?php if (!is_singular()): ?>
				<a href="<?php the_permalink(); ?>"><h2 class="entry-title"><?php the_title (); ?></h2></a>
			<?php else: ?>
				<a href="<?php echo $remote_url; ?>"><h1 class="entry-title"><?php the_title (); ?></h1></a>
			<?php endif; ?>
				<a href="<?php echo $remote_url; ?>"><button>Buy Tickets</button></a>
			</div>
			<div class="dojour_event__details entry-meta">
				<?php if ($start_date): ?>
					<p><b><?php echo $start_date; ?></b></p>
				<?php endif; ?>

				<?php echo $event_time; ?>

				<?php if ($location_title): ?>
					<p><?php echo $location_title; ?></p>
				<?php endif; ?>

				<?php if ($location_address): ?>
					<p><a href="https://maps.google.com/?q=<?php echo $location_address; ?>"><?php echo $location_address; ?></a></p>
				<?php endif; ?>
				<p><b>Wednesday, November 13th</b></p>
				<p><b>7:00am - 9:30am</b></p>
				<p>Science Museum of Minnesota</p>
				<p>120 W Kellogg Blvd, St Paul, MN 55102, USA</p>
			</div>
		</header>
		<div class="dojour_event__content entry-content">
			<div class="entry">
				<?php echo apply_filters ('the_content', $post -> post_content); ?>
			</div>
		</div>
	</article>
</div>
<?php

	get_footer ();

?>

