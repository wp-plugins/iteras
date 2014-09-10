<?php
/**
 * ITERAS
 *
 * @package   Iteras
 * @author    ITERAS Team <team@iteras.dk>
 * @license   GPL-2.0+
 * @link      http://www.iteras.dk
 * @copyright 2014 ITERAS ApS
 */

/**
 * @package Iteras
 * @author  ITERAS Team <team@iteras.dk>
 */
class Iteras {

  const VERSION = '0.2';

  const SETTINGS_KEY = "iteras_settings";
  const POST_META_KEY = "iteras_paywall";

  protected $plugin_slug = 'iteras';

  protected static $instance = null;

  public $settings = null;


  private function __construct() {
    // Load plugin text domain
    add_action( 'init', array( $this, 'load_settings' ) );
    add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

    // Activate plugin when new blog is added
    add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

    // Load public-facing style sheet and JavaScript.
    //add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
    add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

    add_filter('the_content', array( $this, 'potentially_paywall_content' ));
  }


  public function get_plugin_slug() {
    return $this->plugin_slug;
  }


  public static function get_instance() {
    if ( null == self::$instance ) {
      self::$instance = new self;
    }

    return self::$instance;
  }


  public static function activate( $network_wide ) {
    if ( function_exists( 'is_multisite' ) && is_multisite() ) {

      if ( $network_wide  ) {

	// Get all blog ids
	$blog_ids = self::get_blog_ids();

	foreach ( $blog_ids as $blog_id ) {

	  switch_to_blog( $blog_id );
	  self::single_activate();

	  restore_current_blog();
	}

      } else {
	self::single_activate();
      }

    } else {
      self::single_activate();
    }

  }


  public static function deactivate( $network_wide ) {

    if ( function_exists( 'is_multisite' ) && is_multisite() ) {

      if ( $network_wide ) {

	// Get all blog ids
	$blog_ids = self::get_blog_ids();

	foreach ( $blog_ids as $blog_id ) {

	  switch_to_blog( $blog_id );
	  self::single_deactivate();

	  restore_current_blog();

	}

      } else {
	self::single_deactivate();
      }

    } else {
      self::single_deactivate();
    }

  }


  public function activate_new_site( $blog_id ) {

    if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
      return;
    }

    switch_to_blog( $blog_id );
    self::single_activate();
    restore_current_blog();

  }


  public static function uninstall() {
    delete_option(self::SETTINGS_KEY);
    //delete_metadata( 'post', null, Iteras_Admin::$POST_META_KEY, null, true );
  }


  private static function get_blog_ids() {

    global $wpdb;

    // get an array of blog ids
    $sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

    return $wpdb->get_col( $sql );

  }


  private static function single_activate() {
    $settings = get_option(self::SETTINGS_KEY);

    if (!empty($settings) and version_compare(self::VERSION, $settings['version'], "gt")) {
      $old_version = $settings['version'];
      $new_version = self::VERSION;
      // do version upgrades here
    }
  }


  private static function single_deactivate() {
  }


  public function load_plugin_textdomain() {
    // Load the plugin text domain for translation.

    $domain = $this->plugin_slug;
    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

    load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
    load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );
  }


  public function load_settings() {
    $settings = get_option(self::SETTINGS_KEY);

    if (empty($settings)) {
      $settings = array(
        'profile_name' => "",
        'paywall_id' => "",
        'subscribe_url' => "",
        'user_url' => "",
        'default_access' => "",
        'version' => self::VERSION,
      );

      add_option(self::SETTINGS_KEY, $settings);
    }

    $this->settings = $settings;
  }


  public function save_settings($settings) {
    wp_cache_delete(self::SETTINGS_KEY);
    $settings['version'] = self::VERSION;
    update_option(self::SETTINGS_KEY, $settings);
    $this->settings = $settings;
  }


  public function enqueue_styles() {
    wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'assets/css/public.css', __FILE__ ), array(), self::VERSION );
  }


  public function enqueue_scripts() {
    // include the itearas javascript api
    if (WP_DEBUG)
      $url = "http://iteras.localhost:8000/media/api/iteras.js"; //"http://app-test.iteras.dk/static/api/iteras.js";
    else
      $url = "https://app.iteras.dk/static/api/iteras.js";

    wp_enqueue_script( $this->plugin_slug . '-api-script', $url );

    //wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.js', __FILE__ ), array( 'jquery' ), self::VERSION );
  }

  public function potentially_paywall_content($content) {
    global $post;

    if ( is_single() ) {
      $paywall = get_post_meta( $post->ID, self::POST_META_KEY, true );

      $extra = "";
      if (!$this->settings['subscribe_url'])
        $extra = '<!-- ITERAS paywall enabled but not configured properly  -->';
      elseif (in_array($paywall, array("user", "sub")))
        $extra = '<script>Iteras.wall({ redirect: "'.$this->settings['subscribe_url'].'", access: "'.$paywall.'" });</script>';

      $content = $extra.$content;
    }

    return $content;
  }
}