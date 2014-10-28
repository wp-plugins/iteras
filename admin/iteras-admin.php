<?php
/**
 * ITERAS
 *
 * @package   Iteras_Admin
 * @author    ITERAS Team <team@iteras.dk>
 * @license   GPL-2.0+
 * @link      http://www.iteras.dk
 * @copyright 2014 ITERAS ApS
 */

/**
 * @package Iteras_Admin
 * @author  ITERAS Team <team@iteras.dk>
 */
class Iteras_Admin {

  protected static $instance = null;

  protected $plugin_screen_hook_suffix = null;

  protected $plugin = null;

  public $access_levels = null;


  private function __construct() {
    $this->plugin = Iteras::get_instance();
    $this->plugin_slug = $this->plugin->get_plugin_slug();

    add_action( 'init', array( $this, 'load_settings' ) );
    add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

    // Load admin style sheet and JavaScript.
    //add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
    //add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

    // Add the options page and menu item.
    add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

    // Add an action link pointing to the options page.
    $plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . $this->plugin_slug . '.php' );
    add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

    add_action( 'load-post.php', array( $this, 'paywall_post_meta_boxes_setup' ) );
    add_action( 'load-post-new.php', array( $this, 'paywall_post_meta_boxes_setup' ) );
  }

  public function load_settings() {
    $this->access_levels = array(
      "" => __('Everybody', $this->plugin_slug),
      //"user" => __('Registered accounts', $this->plugin_slug),
      "sub" => __('Paying subscribers', $this->plugin_slug),
    );
  }

  public function load_plugin_textdomain() {
    // Load the plugin text domain for translation.
    load_plugin_textdomain( $this->plugin_slug, false, plugin_basename(ITERAS_PLUGIN_PATH) . '/languages/' );
  }


  function paywall_post_meta_boxes_setup() {
    add_action( 'add_meta_boxes', array( $this, 'paywall_add_post_meta_boxes') );
    add_action( 'save_post', array( $this, 'paywall_save_post' ), 10, 2 );
  }


  function paywall_add_post_meta_boxes() {
    add_meta_box( "iteras-paywall-box", __("ITERAS Paywall"), array( $this, "paywall_post_meta_box" ), "post", "side", "high" );
  }


  function paywall_post_meta_box( $post, $box ) {
    $paywall_type = get_post_meta($post->ID, Iteras::POST_META_KEY);
    if (empty($paywall_type))
      $paywall_type = $this->plugin->settings['default_access'];
    else
      $paywall_type = $paywall_type[0];

    $level_descriptions = array(
      "" => __('Does not restrict visitors, everyone can see the content', $this->plugin_slug),
      "user" => __('Content restricted to visitors who are in the subscriber database (but they are not required to have an active subscription)', $this->plugin_slug),
      "sub" => __('Content restricted to visitors with an active subscription', $this->plugin_slug),
    );

    $settings = $this->plugin->settings;
    $settings_url = admin_url( 'options-general.php?page=' . $this->plugin_slug );
    $domain = $this->plugin_slug;

    include_once( 'views/post-meta-box.php' );
  }


  function paywall_save_post( $post_id, $post ) {
    if ( !isset( $_POST['iteras_paywall_post_nonce'] ) || !wp_verify_nonce( $_POST['iteras_paywall_post_nonce'], "post".$post_id ) )
      return $post_id;

    $post_type = get_post_type_object( $post->post_type );

    if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
      return $post_id;

    $new_value = ( isset( $_POST['iteras-paywall'] ) ? sanitize_text_field( $_POST['iteras-paywall'] ) : null );

    if ( !in_array($new_value, array('', 'user', 'sub')))
      return $post_id;

    $old_value = get_post_meta( $post_id, Iteras::POST_META_KEY );
    if (empty($old_value))
      $old_value = null;
    else
      $old_value = $old_value[0];

    if ( $new_value !== null && $old_value === null )
      add_post_meta( $post_id, Iteras::POST_META_KEY, $new_value, true );

    elseif ( $new_value !== null && $new_value != $old_value )
      update_post_meta( $post_id, Iteras::POST_META_KEY, $new_value );

    elseif ( $new_value === null && $old_value )
      delete_post_meta( $post_id, Iteras::POST_META_KEY, $old_value );
  }


  public static function get_instance() {
    if ( null == self::$instance ) {
      self::$instance = new self;
    }

    return self::$instance;
  }


  public function enqueue_admin_styles() {
    if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
      return;
    }

    $screen = get_current_screen();
    if ( $this->plugin_screen_hook_suffix == $screen->id ) {
      wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), Iteras::VERSION );
    }
  }


  public function enqueue_admin_scripts() {
    if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
      return;
    }

    $screen = get_current_screen();
    if ( $this->plugin_screen_hook_suffix == $screen->id ) {
      wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), Iteras::VERSION );
    }
  }


  public function add_plugin_admin_menu() {
    $this->plugin_screen_hook_suffix = add_options_page(
      __( 'ITERAS configuration', $this->plugin_slug ),
      __( 'ITERAS', $this->plugin_slug ),
      'manage_options',
      $this->plugin_slug,
      array( $this, 'display_plugin_admin_page' )
    );
  }


  public function display_plugin_admin_page() {

    if ($_SERVER['REQUEST_METHOD'] == "POST") {
      $this->save_settings();
    }

    // template context
    $settings = $this->plugin->settings;
    $domain = $this->plugin_slug;

    include_once( 'views/admin.php' );

  }


  public function add_action_links( $links ) {

    return array_merge(
      array(
        'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>',
      ),
      $links
    );

  }


  private function save_settings() {
    if (!current_user_can('manage_options')) {
      wp_die('You do not have sufficient permissions to access this page.');
    }

    $settings = array(
      'profile_name' => sanitize_text_field($_POST['profile']),
      'paywall_id' => sanitize_text_field($_POST['paywall']),
      'subscribe_url' => sanitize_text_field($_POST['subscribe_url']),
      'user_url' => sanitize_text_field($_POST['user_url']),
      'default_access' => sanitize_text_field($_POST['default_access']),
    );

    $this->plugin->save_settings($settings);
  }
}
