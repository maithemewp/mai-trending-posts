<?php

/**
 * Plugin Name:     Mai Trending Posts
 * Plugin URI:      https://bizbudding.com
 * Description:     Show views total and display trending posts in Mai Post Grid. Uses Jetpack Stats.
 * Version:         0.1.0
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
			define( 'MAI_TRENDING_POSTS_PLUGIN_VERSION', '0.1.0' );
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
		// Register shortcode no matter what.
		add_shortcode( 'mai_views', [ $this, 'add_shortcode' ] );
		// Modify query no matter what. If plugin shouldn't run the query will revert to default.
		add_filter( 'mai_post_grid_query_args', [ $this, 'edit_query' ], 10, 2 );

		// Ready to go.
		if ( $this->has_jetpack() && $this->has_stats() ) {
			$key = 'mai_grid_block_posts_orderby';
			add_action( 'wp_footer',                 [ $this, 'update_views' ] );
			add_filter( "acf/load_field/key={$key}", [ $this, 'add_views_choice' ] );

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
	 * Updates view counts as post meta.
	 * Retrieve views using the WordPress.com Stats API.
	 * The `stats_get_from_restapi()` function is cached for 5 minutes, so no caching needed here.
	 *
	 * @link https://github.com/Automattic/jetpack/blob/trunk/projects/plugins/jetpack/modules/stats.php#L1636
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function update_views() {
		if ( ! is_singular() ) {
			return;
		}

		$views = 0;

		// Return early if we use a too old version of Jetpack.
		if ( ! function_exists( 'stats_get_from_restapi' ) ) {
			return $views;
		}

		// Get the data.
		$post_id = get_the_ID();
		$stats   = stats_get_from_restapi( [ 'fields' => 'views' ], sprintf( 'post/%d', $post_id ) );

		// If we have views.
		if ( isset( $stats ) && ! empty( $stats ) && isset( $stats->views ) ) {
			$views    = absint( $stats->views );
			$existing = maitp_get_view_count();

			// Only update if new value.
			if ( $views && $views !== $existing ) {

				update_post_meta( $post_id, maitp_get_key(), $views );
			}
		}

		return $views;
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
	 * Adds conditional logic to hide if query by is trending.
	 *
	 * @since 0.1.0
	 *
	 * @param array $field The existing field array.
	 *
	 * @return array
	 */
	function add_conditional_logic( $field ) {
		$field['conditional_logic'][] = [
			'field'    => 'mai_grid_block_query_by',
			'operator' => '!=',
			'value'    => 'trending',
		];

		return $field;
	}

	/**
	 * Modify Mai Post Grid query args.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	function edit_query( $query_args, $args ) {
		if ( ! isset( $args['orderby'] ) || empty( $args['orderby'] ) || 'views' !== $args['orderby'] ) {
			return $query_args;
		}

		if ( ! $this->should_run() ) {
			return $query_args;
		}

		$query_args['orderby']  = 'meta_value_num';
		$query_args['meta_key'] = maitp_get_key();

		return $query_args;
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
	 * If Jetpack is active.
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

		$has = class_exists( 'Jetpack' );

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
				'get_callback'    => [ $this, 'rest_get_views' ],
				'update_callback' => [ $this, 'rest_update_views' ],
				'schema'          => null,
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

	/**
	 * Update post views from the API.
	 *
	 * Only accepts a string.
	 *
	 * @since 0.1.0
	 *
	 * @param string $views      New post view value.
	 * @param object $object     The object from the response.
	 * @param string $field_name Name of field.
	 *
	 * @return bool|int
	 */
	public function rest_update_views( $views, $object, $field_name ) {
		if ( ! isset( $views ) || empty( $views ) ) {
			return new WP_Error( 'bad-post-view', __( 'The specified view is in an invalid format.', 'mai-trending-posts' ) );
		}

		return update_post_meta( $object->ID, maitp_get_key(), absint( $views ) );
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
