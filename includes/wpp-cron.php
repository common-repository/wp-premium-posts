<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

function wpp_register_cron_jobs() {
	// Make sure this event hasn't been scheduled
	if ( ! wp_next_scheduled( 'wpp_daily_checker' ) ) {
		// Schedule the event
		wp_schedule_event( time(), apply_filters( 'wpp_register_cron_jobs_checker_recurrence', 'daily' ), apply_filters( 'wpp_register_cron_jobs_checker_hook', 'wpp_daily_checker' ) );
	}
}

add_action( 'wpp_daily_checker', 'wpp_daily_checker_function' );
function wpp_daily_checker_function() {

	ignore_user_abort( true );

	if ( ! ini_get( 'safe_mode' ) ) {
		@set_time_limit( 0 );
	}

	//Get all posts that contain _wpp_featured_expiration & _wpp_post_expiration meta keys
	$args = array(
		'post_type'   => 'post',
		'post_status' => 'publish',
//		'author'      => get_current_user_id(),
		'numberposts' => - 1,

		'meta_query' => array(
			'relation' => 'OR',
			array(
				'key'     => '_wpp_featured_expiration',
				'compare' => 'EXISTS'
			),
			array(
				'key'     => '_wpp_post_expiration',
				'compare' => 'EXISTS'
			)
		)
	);

	$posts = get_posts( $args );
	wp_reset_postdata();

	foreach ( $posts as $post ) {
		$featured_expiration_days = (int) get_post_meta( $post->ID, '_wpp_featured_expiration', true );
		$post_expiration_days     = (int) get_post_meta( $post->ID, '_wpp_post_expiration', true );
		$is_featured              = get_post_meta( $post->ID, '_wpp_is_featured', true );

		//Current date
		$current_date = new DateTime();

		$post_date = new DateTime( $post->post_date );

		//Check the expiration
		if ( $post_expiration_days > 0 ) {
			$post_date->modify( '+' . $post_expiration_days . ' days' );
			if ( $current_date > $post_date ) {
				$force_delete = get_post_meta( $post->ID, '_wpp_force_delete', true );

				if ( $force_delete == 'on' ) {
					$force_delete = true;
				} else {
					$force_delete = false;

				}
				wp_delete_post( $post->ID, $force_delete );
			}
		}

		//Check the featured
		if ( ( $featured_expiration_days > 0 ) && ( $is_featured == 'yes' ) ) {
			$new_post_date = new DateTime( $post->post_date );
			$new_post_date->modify( '+' . $featured_expiration_days . ' days' );

			if ( $current_date > $new_post_date ) {
				update_post_meta( $post->ID, '_wpp_is_featured', 'no' );
				delete_post_meta( $post->ID, '_wpp_featured_expiration' );
			}
		}
	}
}

