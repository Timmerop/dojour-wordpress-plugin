<?php
/*
* Template Name: Dojour Event
* Template Post Type: dojour_event
*/

$post = get_post ();

$remote_url = get_post_meta ($post -> ID, 'remote_url');

$start_date =  get_post_meta ($post -> ID, 'start_date');
$start_time =  get_post_meta ($post -> ID, 'start_time');

$end_date =  get_post_meta ($post -> ID, 'end_date');
$end_date =  get_post_meta ($post -> ID, 'end_date');

$door_time = $start_date =  get_post_meta ($post -> ID, 'door_time');

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

<article class="dojour_event">
	<header class="dojour_event__header">
		<?php if (has_post_thumbnail ()): ?>
		<div class="dojour_event__cover">
			<?php the_post_thumbnail ('full') ?>
		</div>
		<?php endif; ?>

		<div class="dojour_event__title">
			<h2><?php the_title (); ?></h2>
			<a href="<?php echo $remote_url; ?>"><button>Buy Tickets</button>
		</div>
		<div class="dojour_event__details">
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

		</div>
	</header>
	<div class="dojour_event__content">
		<?php the_content (); ?>
	</div>
</article>

<?php

get_sidebar	();

get_footer ();

?>

