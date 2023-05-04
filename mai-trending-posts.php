<?php

/**
 * Plugin Name:     Mai Trending Posts
 * Plugin URI:      https://bizbudding.com/mai-theme/
 * Description:     Show total views and display popular or trending posts in Mai Post Grid. Uses Jetpack Stats.
 * Version:         0.3.0
 *
 * Author:          BizBudding
 * Author URI:      https://bizbudding.com
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main Mai_Trending_Posts_Plugin Class.
 *
 * @since 0.1.0
 */
final class Mai_Trending_Posts_Plugin {

	/**
	 * @var   Mai_Trending_Posts_Plugin The one true Mai_Trending_Posts_Plugin
	 * @since 0.1.0
	 */
	private static $instance;

	/**
	 * Main Mai_Trending_Posts_Plugin Instance.
	 *
	 * Insures that only one instance of Mai_Trending_Posts_Plugin exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since   0.1.0
	 * @static  var array $instance
	 * @uses    Mai_Trending_Posts_Plugin::setup_constants() Setup the constants needed.
	 * @uses    Mai_Trending_Posts_Plugin::includes() Include the required files.
	 * @uses    Mai_Trending_Posts_Plugin::hooks() Activate, deactivate, etc.
	 * @see     Mai_Trending_Posts_Plugin()
	 * @return  object | Mai_Trending_Posts_Plugin The one true Mai_Trending_Posts_Plugin
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup.
			self::$instance = new Mai_Trending_Posts_Plugin;
			// Methods.
			self::$instance->setup_constants();
			self::$instance->includes();
			self::$instance->hooks();
		}
		return self::$instance;
	}

	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since   0.1.0
	 * @access  protected
	 * @return  void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'mai-trending-posts' ), '1.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @since   0.1.0
	 * @access  protected
	 * @return  void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'mai-trending-posts' ), '1.0' );
	}

	/**
	 * Setup plugin constants.
	 *
	 * @access  private
	 * @since   0.1.0
	 * @return  void
	 */
	private function setup_constants() {

		// Plugin version.
		if ( ! defined( 'MAI_TRENDING_POSTS_PLUGIN_VERSION' ) ) {
			define( 'MAI_TRENDING_POSTS_PLUGIN_VERSION', '0.3.0' );
		}

		// Plugin Folder Path.
		if ( ! defined( 'MAI_TRENDING_POSTS_PLUGIN_PLUGIN_DIR' ) ) {
			define( 'MAI_TRENDING_POSTS_PLUGIN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

		// Plugin Includes Path.
		if ( ! defined( 'MAI_TRENDING_POSTS_PLUGIN_INCLUDES_DIR' ) ) {
			define( 'MAI_TRENDING_POSTS_PLUGIN_INCLUDES_DIR', MAI_TRENDING_POSTS_PLUGIN_PLUGIN_DIR . 'includes/' );
		}

		// Plugin Folder URL.
		if ( ! defined( 'MAI_TRENDING_POSTS_PLUGIN_PLUGIN_URL' ) ) {
			define( 'MAI_TRENDING_POSTS_PLUGIN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}

		// Plugin Root File.
		if ( ! defined( 'MAI_TRENDING_POSTS_PLUGIN_PLUGIN_FILE' ) ) {
			define( 'MAI_TRENDING_POSTS_PLUGIN_PLUGIN_FILE', __FILE__ );
		}

		// Plugin Base Name
		if ( ! defined( 'MAI_TRENDING_POSTS_PLUGIN_BASENAME' ) ) {
			define( 'MAI_TRENDING_POSTS_PLUGIN_BASENAME', dirname( plugin_basename( __FILE__ ) ) );
		}
	}

	/**
	 * Include required files.
	 *
	 * @access  private
	 * @since   0.1.0
	 * @return  void
	 */
	private function includes() {
		// Include vendor libraries.
		require_once __DIR__ . '/vendor/autoload.php';
		// Includes.
		foreach ( glob( MAI_TRENDING_POSTS_PLUGIN_INCLUDES_DIR . '*.php' ) as $file ) { include $file; }
	}

	/**
	 * Run the hooks.
	 *
	 * @since   0.1.0
	 * @return  void
	 */
	public function hooks() {
		add_action( 'plugins_loaded', [ $this, 'updater' ] );
		add_action( 'plugins_loaded', [ $this, 'run' ] );
	}

	/**
	 * Setup the updater.
	 *
	 * composer require yahnis-elsts/plugin-update-checker
	 *
	 * @since 0.1.0
	 *
	 * @uses https://github.com/YahnisElsts/plugin-update-checker/
	 *
	 * @return void
	 */
	public function updater() {
		// Bail if current user cannot manage plugins.
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		// Bail if plugin updater is not loaded.
		if ( ! class_exists( 'Puc_v4_Factory' ) ) {
			return;
		}

		// Setup the updater.
		$updater = Puc_v4_Factory::buildUpdateChecker( 'https://github.com/maithemewp/mai-trending-posts/', __FILE__, 'mai-trending-posts' );

		// Maybe set github api token.
		if ( defined( 'MAI_GITHUB_API_TOKEN' ) ) {
			$updater->setAuthentication( MAI_GITHUB_API_TOKEN );
		}

		// Add icons for Dashboard > Updates screen.
		if ( function_exists( 'mai_get_updater_icons' ) && $icons = mai_get_updater_icons() ) {
			$updater->addResultFilter(
				function ( $info ) use ( $icons ) {
					$info->icons = $icons;
					return $info;
				}
			);
		}
	}

	/**
	 * Loads plugin with checks for Jetpack and Stats module.
	 *
	 * @since 0.1.0
	 *
	 * @return
	 */
	function run() {
		add_shortcode( 'mai_views',                                               [ $this, 'add_shortcode' ] );
		add_filter( 'mai_post_grid_query_args',                                   [ $this, 'edit_query' ], 20, 2 );
		add_filter( 'acf/load_field/key=mai_grid_block_query_by',                 [ $this, 'add_trending_choice' ] );
		add_filter( 'acf/load_field/key=mai_grid_block_posts_orderby',            [ $this, 'add_views_choice' ] );
		add_filter( 'acf/load_field/key=mai_grid_block_post_taxonomies',          [ $this, 'add_show_conditional_logic' ] );
		add_filter( 'acf/load_field/key=mai_grid_block_post_taxonomies_relation', [ $this, 'add_show_conditional_logic' ] );
		// add_filter( 'acf/load_field/key=mai_grid_block_post_meta_keys',           [ $this, 'add_show_conditional_logic' ] ); // Can't use meta because that's what is used for Views query.
		// add_filter( 'acf/load_field/key=mai_grid_block_post_meta_keys_relation',  [ $this, 'add_show_conditional_logic' ] );
		add_filter( 'acf/load_field/key=mai_grid_block_posts_orderby',            [ $this, 'add_hide_conditional_logic' ] );
		add_filter( 'acf/load_field/key=mai_grid_block_posts_order',              [ $this, 'add_hide_conditional_logic' ] );

		// Ready to go.
		if ( $this->has_jetpack() && $this->has_stats() ) {
			add_action( 'wp_footer', [ $this, 'update_views' ] );

			// Add Stats to REST API Post response.
			if ( function_exists( 'register_rest_field' ) ) {
				add_action( 'rest_api_init', [ $this, 'rest_register_post_views' ] );
			}
		}
		// Missing Jetpack or Stats or using local development site.
		else {
			if ( ! $this->has_jetpack() ) {
				$notice = sprintf(
					__( 'Mai Trending Posts plugin, requires Jetpack. Please <a href="%s">install Jetpack</a> first, then activate the Stats module.', 'mai-trending-posts' ),
					'plugin-install.php?tab=search&s=jetpack&plugin-search-input=Search+Plugins'
				);
			} else {
				$notice = sprintf(
					__( 'Mai Trending Posts plugin, requires the Jetpack Stats module. View the <a href="%s">Jetpack settings</a> to enable the Stats module.', 'mai-trending-posts' ),
					'admin.php?page=jetpack'
				);
			}

			/**
			 * Displays admin notice if Jetpack is not installed or the Stats module is not active.
			 *
			 * @since 0.1.0
			 *
			 * @return void
			 */
			add_action( 'plugins_loaded', function() use ( $notice ) {
				printf( '<div class="error"><p>%s</p></div>', $notice );
			});
		}
	}

	/**
	 * Adds shortcode to display views.
	 * Bail if shouldn't run. This makes sure views do not display if Jetpack/Stats are not running.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	function add_shortcode( $atts ) {
		if ( ! $this->should_run() ) {
			return;
		}

		return maitp_get_views( $atts );
	}

	/**
	 * Modify Mai Post Grid query args.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	function edit_query( $query_args, $args ) {
		if ( isset( $args['query_by'] ) && $args['query_by'] && 'trending' === $args['query_by'] ) {
			$query_args = $this->edit_trending_query( $query_args, $args );
		}

		if ( isset( $args['orderby'] ) && $args['orderby'] && 'views' === $args['orderby'] ) {
			$query_args['meta_key'] = maitp_get_key();
			$query_args['orderby']  = 'meta_value_num';
		}

		return $query_args;
	}

	/**
	 * Modify Mai Post Grid query args for trending posts.
	 *
	 * @since 0.3.0
	 *
	 * @return array
	 */
	function edit_trending_query( $query_args, $args ) {
		// Build args for a pre-query to get post ids.
		$new_args                   = $query_args;
		$new_args['fields']         = 'ids';
		$new_args['posts_per_page'] = 500;
		$new_args['meta_key']       = maitp_get_key();
		$new_args['orderby']        = 'meta_value_num';
		unset( $new_args['offset'] );

		// Get post ids with new args. Cached if duplicate query since WP 6.1.
		$query = new WP_Query( $new_args );

		// Set var for post ids.
		$post_ids = (array) $query->posts;
		// Exclude any posts.
		$post_ids = isset( $args['exclude'] ) && $args['exclude'] ? array_diff( $post_ids, $args['exclude'] ) : $post_ids;

		if ( $post_ids ) {
			// Get matching post ids, including offset and posts per page.
			$trending  = maitp_get_all_trending( $days = 7, $query_args['post_type'], true );
			$intersect = array_intersect( $trending, $post_ids );
			$post_ids  = array_slice( $intersect, max( 0, $args['offset'] - 1 ), $args['posts_per_page'] + 1, true );

			// Set posts.
			$query_args['post__in'] = $post_ids;
			$query_args['orderby']  = 'post__in';

			// Unset existing args since we're using post__in now.
			unset( $query_args['tax_query'] );
			unset( $query_args['meta_query'] );
			unset( $query_args['date_query'] );
		}

		wp_reset_postdata();

		return $query_args;
	}

	/**
	 * Adds Trending as an "Get Entries By" choice.
	 *
	 * @since 0.1.0
	 *
	 * @param array $field The existing field array.
	 *
	 * @return array
	 */
	function add_trending_choice( $field ) {
		$field['choices'][ 'trending' ] = __( 'Trending', 'mai-trending-posts' );

		return $field;
	}

	/**
	 * Adds Views as an "Ordery By" choice.
	 *
	 * @since 0.1.0
	 *
	 * @param array $field The existing field array.
	 *
	 * @return array
	 */
	function add_views_choice( $field ) {
		$field['choices'] = array_merge( [ 'views' => __( 'Views', 'mai-trending-posts' ) ], $field['choices'] );

		return $field;
	}

	/**
	 * Adds conditional logic to show if query by is trending.
	 * This duplicates existing conditions and changes query_by from 'tax_meta' to 'trending'.
	 *
	 * @since 0.2.0
	 *
	 * @param array $field The existing field array.
	 *
	 * @return array
	 */
	function add_show_conditional_logic( $field ) {
		$conditions = [];

		foreach ( $field['conditional_logic'] as $index => $values ) {
			$condition = $values;

			if ( isset( $condition['field'] ) && 'mai_grid_block_query_by' == $condition['field'] ) {
				$condition['value']    = 'trending';
				$condition['operator'] = '==';
			}

			$conditions[] = $condition;
		};

		$field['conditional_logic'] = $conditions ? [ $field['conditional_logic'], $conditions ] : $field['conditional_logic'];

		return $field;
	}

	/**
	 * Adds conditional logic to hide if query by is trending.
	 *
	 * @since 0.1.0
	 *
	 * @param array $field The existing field array.
	 *
	 * @return array
	 */
	function add_hide_conditional_logic( $field ) {
		$field['conditional_logic'][] = [
			'field'    => 'mai_grid_block_query_by',
			'operator' => '!=',
			'value'    => 'trending',
		];

		return $field;
	}

	/**
	 * If plugin should run.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	function should_run() {
		return $this->has_jetpack() && $this->has_stats();
	}

	/**
	 * If Jetpack is active, and it's a recent enough
	 * version to include the `WPCOM_Stats` class.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	function has_jetpack() {
		static $has = null;

		if ( ! is_null( $has ) ) {
			return $has;
		}

		$has = class_exists( 'Jetpack' ) && class_exists( 'WPCOM_Stats' );

		return $has;
	}

	/**
	 * If Jetpack Stats is active.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	function has_stats() {
		static $has = null;

		if ( ! is_null( $has ) ) {
			return $has;
		}

		$has  = $this->has_jetpack() && Jetpack::is_module_active( 'stats' );

		return $has;
	}

	/**
	 * Updates view counts as post meta.
	 * Retrieve views using the WordPress.com Stats API.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function update_views() {
		if ( ! is_singular() ) {
			return;
		}

		$views = maitp_update_view_count( get_the_ID() );
		return;
	}

	/**
	 * Add views to REST API Post responses.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function rest_register_post_views() {
		register_rest_field( 'post',
			maitp_get_key(),
			[
				'get_callback' => [ $this, 'rest_get_views' ],
				'schema'       => null,
			]
		);
	}

	/**
	 * Get the Post views for the API.
	 *
	 * @since 0.1.0
	 *
	 * @param array           $object     Details of current post.
	 * @param string          $field_name Name of field.
	 * @param WP_REST_Request $request    Current request.
	 *
	 * @return int $views View count.
	 */
	public function rest_get_views( $object, $field_name, $request ) {
		return absint( get_post_meta( $object['id'], maitp_get_key(), true ) );
	}
}

/**
 * The main function for that returns Mai_Trending_Posts_Plugin
 *
 * The main function responsible for returning the one true Mai_Trending_Posts_Plugin
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $plugin = Mai_Trending_Posts_Plugin(); ?>
 *
 * @since 0.1.0
 *
 * @return object|Mai_Trending_Posts_Plugin The one true Mai_Trending_Posts_Plugin Instance.
 */
function mai_trending_posts_plugin() {
	return Mai_Trending_Posts_Plugin::instance();
}

// Get Mai_Trending_Posts_Plugin Running.
mai_trending_posts_plugin();
