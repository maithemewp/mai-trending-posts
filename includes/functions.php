<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Gets a limited number of trending posts.
 *
 * @since 0.1.0
 *
 * @param int  $number    The number to return.
 * @param int  $offset    The number to skip.
 * @param bool $use_cache Whether to use transients.
 *
 * @return array
 */
function maitp_get_trending( $number = 12, $offset = 0, $use_cache = true ) {
	$trending = maitp_get_all_trending( $use_cache );

	if ( ! $trending ) {
		return [];
	}

	$trending = array_slice( $input, $offset, $number );

	return $trending;
}

/**
 * Gets trending post IDs. 24 posts max, for performance.
 *
 * @since 0.1.0
 *
 * @param bool $use_cache Whether to use transients.
 *
 * @return array
 */
function maitp_get_all_trending( $use_cache = true ) {
	$post_ids  = [];
	$days      = 100;
	$transient = 'mai_trending_posts';

	if ( ! function_exists( 'stats_get_from_restapi' ) ) {
		return $post_ids;
	}

	if ( ! $use_cache || false === ( $posts_ids = get_transient( $transient ) ) ) {
		$stats = stats_get_from_restapi( [], 'top-posts?max=11&summarize=1&num=' . $days );

		if ( isset( $stats->summary ) && $stats->summary->postviews ) {
			$post_ids = array_filter( wp_list_pluck( $stats->summary->postviews, 'id' ) );

			set_transient( $transient, $post_ids, 8 * HOUR_IN_SECONDS );
		}
	}

	return $post_ids;
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
function maitp_get_views( $atts ) {
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
 * @param int|string $post_id Post ID.
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
