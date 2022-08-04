<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Gets it started.
 *
 * @since 0.1.0
 *
 * @link https://docs.wpvip.com/how-tos/write-custom-wp-cli-commands/
 * @link https://webdevstudios.com/2019/10/08/making-wp-cli-commands/
 *
 * @return void
 */
add_action( 'cli_init', 'maitp_cli_add_command' );
function maitp_cli_add_command() {
	WP_CLI::add_command( 'maitp', 'Mai_Trending_Posts_CLI' );
}

/**
 * Main Mai_Trending_Posts_CLI Class.
 *
 * @since 0.1.0
 */
class Mai_Trending_Posts_CLI {
	/**
	 * Updates views from Jetpack Stats.
	 *
	 * Usage: wp maitp update_views --post_type=post --posts_per_page=10 --offset=0
	 *
	 * @param array $args       Standard command args.
	 * @param array $assoc_args Keyed args like --posts_per_page and --offset.
	 *
	 * @return void
	 */
	function update_views( $args, $assoc_args ) {
		$assoc_args = wp_parse_args(
			$assoc_args,
			[
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'offset'         => 0,
			]
		);

		if ( ! class_exists( 'Jetpack' ) ) {
			WP_CLI::error( 'Jetpack is not installed or active.' );
			return;
		}

		if ( ! Jetpack::is_module_active( 'stats' ) ) {
			WP_CLI::error( 'Jetpack stats module is not active.' );
			return;
		}

		if ( ! function_exists( 'stats_get_from_restapi' ) ) {
			WP_CLI::error( 'stats_get_from_restapi function does not exist.' );
			return;
		}

		$query = new WP_Query(
			[
				'post_type'              => $assoc_args['post_type'],
				'post_status'            => $assoc_args['post_status'],
				'posts_per_page'         => $assoc_args['posts_per_page'],
				'offset'                 => $assoc_args['offset'],
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			]
		);

		$posts = [];

		if ( $query->have_posts() ) {
			// Progress bar stuff.
			$progress = \WP_CLI\Utils\make_progress_bar( 'Importing...', $query->post_count );

			while ( $query->have_posts() ) : $query->the_post();
				$post_id = get_the_ID();
				$views   = maitp_update_view_count( $post_id );
				$progress->tick();
			endwhile;

			$progress->finish();

			WP_CLI::success( 'Done, updates complete.' );
		} else {
			WP_CLI::success( 'No posts found.' );
		}

		wp_reset_postdata();
	}
}

/**
 * Instantiate the class.
 *
 * @since 0.1.0
 *
 * @return void
 */
$maitp_cli = new Mai_Trending_Posts_CLI;
