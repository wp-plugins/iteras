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

  const VERSION = '0.4';

  const SETTINGS_KEY = "iteras_settings";
  const POST_META_KEY = "iteras_paywall";
  const DEFAULT_ARTICLE_SNIPPET_SIZE = 300;

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
    add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
    add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

    add_filter('the_content', array( $this, 'potentially_paywall_content' ));

    add_shortcode( 'iteras-signup', array( $this, 'signup_shortcode') );
    add_shortcode( 'iteras-paywall-login', array( $this, 'paywall_shortcode') );
    add_shortcode( 'iteras-selfservice', array( $this, 'selfservice_shortcode') );
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
      if ($new_version == "0.4") {
        $settings['paywall_display_type'] = 'redirect';
        $settings['paywall_box'] = '';
        $settings['paywall_snippet_size'] = self::DEFAULT_ARTICLE_SNIPPET_SIZE;
      }

      wp_cache_delete(self::SETTINGS_KEY);
      $settings['version'] = $new_version;
      update_option(self::SETTINGS_KEY, $settings);
    }
  }


  private static function single_deactivate() {
  }


  public function load_plugin_textdomain() {
    // Load the plugin text domain for translation.
    load_plugin_textdomain( $this->plugin_slug, false, plugin_basename(ITERAS_PLUGIN_PATH) . '/languages/' );
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
        'paywall_display_type' => "redirect",
        'paywall_box' => "",
        'paywall_snippet_size' => self::DEFAULT_ARTICLE_SNIPPET_SIZE,
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
    // include the iteras javascript api
    if (ITERAS_DEBUG) {
      //$url = "http://iteras.localhost:8000/media/api/iteras.js"; //"http://app-test.iteras.dk/static/api/iteras.js";
      //wp_enqueue_script( $this->plugin_slug . '-api-script-debug',  "http://iteras.localhost:8000/media/api/debug.js");
      $url = "http://aura.beta.iola.dk/media/api/iteras.js";
      wp_enqueue_script( $this->plugin_slug . '-api-script-debug',  "http://aura.beta.iola.dk/media/api/debug.js");
    }
    else
      $url = "https://app.iteras.dk/static/api/iteras.js";

    wp_enqueue_script( $this->plugin_slug . '-api-script', $url );

    wp_enqueue_script( $this->plugin_slug . '-plugin-script-truncate', plugins_url( 'assets/js/truncate.js', __FILE__ ), array( 'jquery' ), self::VERSION );
    wp_enqueue_script( $this->plugin_slug . '-plugin-script-box', plugins_url( 'assets/js/box.js', __FILE__ ), array( 'jquery' ), self::VERSION );
  }

  public function potentially_paywall_content($content) {
    global $post;

    if ( is_single() ) {
      $paywall = get_post_meta( $post->ID, self::POST_META_KEY, true );
      
      $extra = "";
      if (!$this->settings['subscribe_url'])
        $extra = '<!-- ITERAS paywall enabled but not configured properly  -->';
      elseif (in_array($paywall, array("user", "sub"))) {
        if ($this->settings['paywall_display_type'] == "samepage") {

          if ($this->settings['paywall_box']) {
            // remove the the_content filter so we don't process twice
            remove_filter( current_filter(), array( $this, __FUNCTION__) );
            $box_content = apply_filters( 'the_content', $this->settings['paywall_box'] );
          }
          else
            $box_content = "<p>" + __("ITERAS plugin improperly configured. Paywall box content is missing", $this->plugin_slug) + "</p>";
            //$box_content = '<script>document.write(Iteras.paywalliframe({ profile: "'.$this->settings['profile_name'].'", paywallid: "'.$this->settings['paywall_id'].'" }));</script>';

          $extra = sprintf(
            file_get_contents(plugin_dir_path( __FILE__ ) . 'views/box.php'),
            $this->settings['paywall_snippet_size'],
            $box_content
          );

          $extra = $extra.'<script>Iteras.wall({ unauthorized: iterasPaywallContent, access: "'.$paywall.'" });</script>';
        }
      }
      else {
        $extra = '<script>Iteras.wall({ redirect: "'.$this->settings['subscribe_url'].'", access: "'.$paywall.'" });</script>';
      }

      $content = '<div class="iteras-content-wrapper">'.$content.'</div>'.$extra;
    }

    return $content;
  }

  function combine_attributes($attrs) {
    if (!$attrs or empty($attrs))
      return "";

    $transformed = [];

    foreach ($attrs as $key => $value)
      if ($value)
        array_push($transformed, '"'.$key.'": "'.$value.'"');

    if (!empty($transformed))
      return ", ".join(", ", $transformed);
    else
      return "";
  }

  // [iteras-signup signupid="3for1"]
  function signup_shortcode($attrs) {
    return '<script>
      document.write(Iteras.signupiframe({
        "profile": "'.$this->settings['profile_name'].'"'.$this->combine_attributes($attrs).'
      }));</script>';
  }

  // [iteras-paywall-login]
  function paywall_shortcode($attrs) {
    return '<script>
      document.write(Iteras.paywalliframe({
        "profile": "'.$this->settings['profile_name'].'",
        "paywallid": "'.$this->settings['paywall_id'].'"'.$this->combine_attributes($attrs).'
      }));</script>';
  }


  // [iteras-selfservice]
  function selfservice_shortcode($attrs) {
    return '<script>
      document.write(Iteras.selfserviceiframe({
        "profile": "'.$this->settings['profile_name'].'"'.$this->combine_attributes($attrs).'
      }));</script>';
  }
}
