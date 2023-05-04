<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Gets a limited number of trending posts.
 *
 * @since 0.1.0
 * @since 0.3.0 No longer used for Mai Post Grid.
 *
 * @param array $args The args.
 * [
 *   'days'      => 7,      // The number of days to check for trending. Max 30.
 *   'number'    => 12,     // The number to return.
 *   'offset'    => 0,      // The number to skip.
 *   'post_type' => 'post', // The post types to get. Either a string 'post' or array [ 'post', 'page' ].
 * ]
 * @param bool $use_cache Whether to use transients.
 *
 * @return array
 */
function maitp_get_trending( $args = [], $use_cache = true ) {
	$args = wp_parse_args( $args,
		[
			'days'      => 7,
			'number'    => 12,
			'offset'    => 0,
			'post_type' => 'post',
		]
	);
	$args['days']      = absint( $args['days'] );
	$args['number']    = absint( $args['number'] );
	$args['offset']    = absint( $args['offset'] );
	$args['post_type'] = is_array( $args['post_type'] ) ? array_map( 'sanitize_key', $args['post_type'] ) : sanitize_key( $args['post_type'] );
	$trending          = maitp_get_all_trending( $args['days'], $args['post_type'], $use_cache );

	if ( ! $trending ) {
		return [];
	}

	// Limit trending count.
	$trending = $trending ? array_slice( $trending, max( 0, $args['offset'] - 1 ), $args['number'] + 1 ) : $trending;

	return $trending;
}

/**
 * Gets 100 trending post IDs.
 * Cached for 5 minutes via JetPack.
 *
 * @since 0.1.0
 * @since 0.2.0 Converted to WPCOM_Stats package.
 *
 * @param int          $days      The number of days to check for trending. Max 30.
 * @param string|array $post_type The post types to get. Either a string 'post' or array [ 'post', 'page' ].
 * @param bool         $use_cache Whether to use transients.
 *
 * @return array
 */
function maitp_get_all_trending( $days = 7, $post_type = 'post', $use_cache = true ) {
	$post_ids     = [];
	$days         = min( (int) $days, 30 );
	$post_type    = array_map( 'strtolower', (array) $post_type );
	sort( $post_type );
	$transient    = sprintf( 'mai_trending_%s_%s', implode( '_', $post_type ), $days );

	if ( ! $use_cache || false === ( $post_ids = get_transient( $transient ) ) ) {

		$args = [
			'max'       => 11,
			'summarize' => 1,
			'num'       => $days,
		];

		$stats = maitp_convert_stats_array_to_object( ( new WPCOM_Stats() )->get_top_posts( $args ) );

		if ( $stats && ! is_wp_error( $stats ) ) {
			if ( isset( $stats->summary ) && $stats->summary->postviews ) {
				foreach ( $stats->summary->postviews as $values ) {
					// Skip if wrong post type.
					if ( ! in_array( $values->type, $post_type ) )  {
						continue;
					}

					$post_ids[] = $values->id;
				}

				$post_ids = array_values( array_filter( $post_ids ) );

				set_transient( $transient, $post_ids, 10 * MINUTE_IN_SECONDS );
			}
		}
	}

	return (array) $post_ids;
}

/**
 * Convert stats array to object after sanity checking the array is valid.
 * Taken from JP Post Views, which was taken from Jetpack.
 *
 * @access private
 * @see    https://github.com/Automattic/jetpack/blob/8a79f5e319d5da58de1b8f0bda863957b938bf21/projects/plugins/jetpack/modules/stats.php#L1522-L1538
 *
 * @since 0.3.0
 *
 * @param array $stats_array The stats array.
 *
 * @return WP_Error|Object|null
 */
function maitp_convert_stats_array_to_object( $stats_array ) {
	if ( is_wp_error( $stats_array ) ) {
		return $stats_array;
	}

	$encoded_array = wp_json_encode( $stats_array );

	if ( ! $encoded_array ) {
		return new WP_Error( 'stats_encoding_error', 'Failed to encode stats array' );
	}

	return json_decode( $encoded_array );
}

/**
 * Gets views for display.
 *
 * @since 0.1.0
 *
 * @param array $atts The shortcode atts.
 *
 * @return string
 */
function maitp_get_views( $atts = [] ) {
	// Atts.
	$atts = shortcode_atts(
		[
			'min'           => 20,      // Minimum number of views before displaying.
			'format'        => 'short', // Use short format (2k+) or show full number (2,143). Currently accepts 'short', '', or a falsey value.
			'icon'          => 'heart',
			'style'         => 'solid',
			'display'       => 'inline',
			'size'          => '0.85em',
			'margin_top'    => '0',
			'margin_right'  => '0.25em',
			'margin_bottom' => '0',
			'margin_left'   => '0',
		],
		$atts,
		'post_views'
	);

	// Sanitize.
	$atts = [
		'min'           => absint( $atts['min'] ),
		'format'        => esc_html( $atts['format'] ),
		'icon'          => sanitize_key( $atts['icon'] ),
		'style'         => sanitize_key( $atts['style'] ),
		'size'          => esc_html( $atts['size'] ),
		'margin_top'    => esc_html( $atts['margin_top'] ),
		'margin_right'  => esc_html( $atts['margin_right'] ),
		'margin_bottom' => esc_html( $atts['margin_bottom'] ),
		'margin_left'   => esc_html( $atts['margin_left'] ),
	];

	$views = maitp_get_view_count();

	if ( ! $views || $views < $atts['min'] ) {
		return;
	}

	$views = 'short' === $atts['format'] ? maitp_get_short_number( $views ) : number_format_i18n( $views );
	$icon  = $atts['icon'] ? mai_get_icon(
		[
			'icon'          => $atts['icon'],
			'style'         => $atts['style'],
			'size'          => $atts['size'],
			'margin_top'    => $atts['margin_top'],
			'margin_right'  => $atts['margin_right'],
			'margin_bottom' => $atts['margin_bottom'],
			'margin_left'   => $atts['margin_left'],
		]
	) : '';

	return sprintf( '<span class="entry-views" style="display:inline-flex;align-items:center;">%s<span class="view-count">%s</span></span>', $icon, $views );
}

/**
 * Retrieve view count for a post.
 *
 * @since 0.1.0
 *
 * @param int|string $post_id The post ID.
 *
 * @return int $views Post View.
 */
function maitp_get_view_count( $post_id = '' ) {
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}

	if ( ! $post_id ) {
		return 0;
	}

	return absint( get_post_meta( $post_id, maitp_get_key(), true ) );
}

/**
 * Updates view count for a post.
 *
 * @since 0.1.0
 * @since 0.2.0 Converted to WPCOM_Stats package.
 *
 * @param int|string $post_id The post ID.
 *
 * @return int
 */
function maitp_update_view_count( $post_id = '' ) {
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}

	if ( ! $post_id ) {
		return;
	}

	// Get the data.
	$views = 0;
	$stats = maitp_convert_stats_array_to_object( ( new WPCOM_Stats() )->get_post_views( (int) $post_id ) );

	// If we have views.
	if ( $stats && isset( $stats->views ) ) {
		$views    = absint( $stats->views );
		$existing = maitp_get_view_count();

		// Only update if new value.
		if ( $views && $views > $existing ) {
			update_post_meta( $post_id, maitp_get_key(), $views );
		}
	}

	return $views;
}

/**
 * Gets a shortened number value for number.
 *
 * @since 0.1.0
 *
 * @param int $number The number.
 *
 * @return string
 */
function maitp_get_short_number( int $number ) {
	if ( $number < 1000 ) {
		return sprintf( '%d', $number );
	}

	if ( $number < 1000000 ) {
		return sprintf( '%d%s', floor( $number / 1000 ), 'K+' );
	}

	if ( $number >= 1000000 && $number < 1000000000 ) {
		return sprintf( '%d%s', floor( $number / 1000000 ), 'M+' );
	}

	if ( $number >= 1000000000 && $number < 1000000000000 ) {
		return sprintf( '%d%s', floor( $number / 1000000000 ), 'B+' );
	}

	return sprintf('%d%s', floor( $number / 1000000000000 ), 'T+' );
};

/**
 * Gets key to be used for post meta and api endpoint.
 *
 * @since 0.1.0
 *
 * @return string
 */
function maitp_get_key() {
	return 'mai_views';
}
