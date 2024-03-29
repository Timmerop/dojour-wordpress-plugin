<?php
	/*
	* Template Name: Dojour Events
	* Template Post Type: dojour_event
	*/
	$paged = (get_query_var ('paged')) ? get_query_var ('paged') : 1;

	$args = [
		'posts_per_page' => 15,
		'paged' => $paged,
		'post_type'  => 'dojour_event'
	];

	$the_query = new WP_Query ($args);

	/**
	 * This function will print out the
	 *
	 * @param string $pages - The total number of pages available
	 * @param integer $range
	 *
	 * @return void
	 */
	function pagination ($pages = '', $range = 1) {
		// WordPress uses this variable to store which page we are currently viewing
		global $paged;

		global $wp_query;

		// If the pages argument is empty, try to retrieve the number of pages
		// available from the query.
		if ($pages == '') {
			$pages = $wp_query -> max_num_pages;

			// If there are no pages, we'll set it to 1
			if (!$pages) {
				$pages = 1;
			}
		}

		// Only show pagination if there is more than one page
		if ($pages != 1) {
			echo '<div class="pagination">';

			// If we are more than 2 pages away from the first one
			if ($paged > 2 && $paged > $range + 1) {
				echo "<a href='" . get_pagenum_link (1) . "'>&laquo; First</a>";
			}

			if ($paged > 1) {
				echo "<a href='" . get_pagenum_link ($paged - 1) . "'>&lsaquo; Previous</a>";
			}

			echo "<span>Page " . $paged . " of " . $pages . "</span>";

			if ($paged < $pages) {
				echo "<a href=\"" . get_pagenum_link ($paged + 1) . "\">Next &rsaquo;</a>";
			}

			// If we are more than one page away from the last one
			if ($paged < $pages - 1 &&  $paged + $range - 1 < $pages) {
				echo "<a href='" . get_pagenum_link ($pages) . "'>Last &raquo;</a>";
			}

			echo "</div>";
		}
	}
?>

<?php get_header (); ?>

<div class="container dojour_event_archive_container">
	<h1 class="dojour_event_archive__title"><?php the_title (); ?></h1>
	<div class="dojour_event_archive">
		<?php while ($the_query -> have_posts ()) : $the_query -> the_post (); ?>

			<?php

				$post = sanitize_post (get_post ());

				$remote_url = get_post_meta ($post -> ID, 'event_url', true);

				$start_date =  get_post_meta ($post -> ID, 'start_date', true);
				$start_time = null;

				if ($start_date) {
					$start_dt = date_create ($start_date);
					$start_date = date_format ($start_dt, 'l, F j');
					$start_time = date_format ($start_dt, 'g:i a');
				}

				$end_date =  get_post_meta ($post -> ID, 'end_date', true);
				$end_time = null;

				if ($end_date) {
					$end_dt = date_create ($end_date);
					$end_date = date_format ($end_dt, 'l, F j');
					$end_time = date_format ($end_dt, 'g:i a');
				}

				$door_time = get_post_meta ($post -> ID, 'door_time', true);

				if ($door_time) {
					$door_time = date_create ($door_time);
					$door_time = date_format ($door_time, 'g:i a');
				}

				$event_time = '';

				if ($start_time) {
					$event_time = '<p><b>' . $start_time;

					if ($end_time && $end_time !== $start_time) {
						$event_time = $event_time . ' - ' . $end_time;
					}

					if ($door_time && $door_time !== $start_time) {
						$event_time = $event_time . ', doors open at ' . $door_time;
					}

					$event_time = $event_time . '</b></p>';
				}

				$first_showing = get_post_meta ($post -> ID, 'first_showing', true);
				$first_date = null;

				if ($first_showing) {
					$first_dt = date_create ($first_showing);
					$first_date = date_format ($first_dt, 'l, F j');
				}

				$last_showing = get_post_meta ($post -> ID, 'last_showing', true);
				$last_date = null;

				if ($last_showing) {
					$last_dt = date_create ($last_showing);
					$last_date = date_format ($last_dt, 'l, F j');
				}

				if ($event_time === '' && $first_date) {
					$event_time = '<p><b>' . $first_date;

					if ($last_date) {
						$event_time = $event_time . ' - ' . $last_date;
					}

					$event_time = $event_time . '</b></p>';
				}

				$location_title = get_post_meta ($post -> ID, 'location_title', true);
				$location_address = get_post_meta ($post -> ID, 'location_address', true);

				$tickets_available = get_post_meta ($post -> ID, 'tickets_available', true);

				$cancelled = get_post_meta ($post -> ID, 'cancelled', true);

			?>

			<article id="post-<?php the_ID(); ?>" class="dojour_event dojour_event--archive post type-post status-publish format-standard has-post-thumbnail">
				<header class="dojour_event__header entry-header">
					<?php if (has_post_thumbnail ()): ?>
					<div class="dojour_event__cover">
						<?php the_post_thumbnail ('full') ?>
					</div>
					<?php endif; ?>

					<div class="dojour_event__title">
						<a href="<?php the_permalink(); ?>"><h2 class="entry-title"><?php the_title (); ?></h2></a>
						<?php if ($cancelled != true): ?>
							<?php if ($tickets_available): ?>
								<a href="<?php echo $remote_url; ?>"><button class="tickets">Buy Tickets</button></a>
							<?php else: ?>
								<a href="<?php echo $remote_url; ?>"><button>View on Dojour</button></a>
							<?php endif; ?>
						<?php endif; ?>
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
					</div>
				</header>
				<div class="dojour_event__content entry-content">
					<div class="entry">
						<?php
							$more = '<p><a href="' . get_permalink(). '">Read more...</a></p>';
							echo force_balance_tags (html_entity_decode (wp_trim_words (htmlentities (wpautop (get_the_content())), 50, $more)));
						?>
					</div>
				</div>
			</article>
		<?php endwhile; ?>
	</div>
	<?php
		if (function_exists ('pagination')) {
			pagination ($the_query -> max_num_pages);
		}
	?>
</div>

<?php get_footer (); ?>
