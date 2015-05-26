<?php

/**
 * LaterPay bootstrap class.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Core_Bootstrap
{

    /**
     * Contains all controller instances.
     * @var array
     */
    private static $controllers = array();

    /**
     * Contains all settings for the plugin.
     *
     * @var LaterPay_Model_Config
     */
    private $config;

    /**
     * @param LaterPay_Model_Config $config
     *
     * @return LaterPay_Core_Bootstrap
     */
    public function __construct( LaterPay_Model_Config $config ) {
        $this->config = $config;

        // load the textdomain for 'plugins_loaded', 'register_activation_hook', and 'register_deactivation_hook'
        $textdomain_dir     = dirname( $this->config->get( 'plugin_base_name' ) );
        $textdomain_path    = $textdomain_dir . $this->config->get( 'text_domain_path' );
        load_plugin_textdomain(
            'laterpay',
            false,
            $textdomain_path
        );
    }

    /**
     * Internal function to create and get controllers.
     *
     * @param string $name name of the controller without prefix.
     *
     * @return bool|LaterPay_Controller_Base $controller instance of the given controller name
     */
    public static function get_controller( $name ) {
        $class = 'LaterPay_Controller_' . (string) $name;

        if ( ! class_exists( $class ) ) {
            $msg = __( '%s: <code>%s</code> not found', 'laterpay' );
            $msg = sprintf( $msg, __METHOD__, $class );
            laterpay_get_logger()->critical( $msg );

            return false;
        }

        if ( ! array_key_exists( $class, self::$controllers ) ) {
            self::$controllers[ $class ] = new $class( laterpay_get_plugin_config() );
        }

        return self::$controllers[ $class ];
    }

    /**
     * Start the plugin on plugins_loaded hook.
     *
     * @wp-hook plugins_loaded
     *
     * @return void
     */
    public function run() {
        $this->register_wordpress_hooks();

        $this->register_custom_actions();
        $this->register_cache_helper();
        $this->register_ajax_actions();

        $this->register_upgrade_checks();
        $this->register_admin_actions_step1();

        // check, if the plugin is correctly configured and working
        if ( ! LaterPay_Helper_View::plugin_is_working() ) {
            return;
        }

        $this->register_event_subscribers();

        // backend actions part 2
        if ( is_admin() ) {
            $this->register_admin_actions_step2();
        }

        $this->register_shortcodes();
        $this->register_global_actions();

        // late load event
        add_action( 'wp_loaded', array( $this, 'late_load' ), 0 );
    }

    /**
     * Internal function to register global actions for frontend and backend.
     *
     * @return void
     */
    private function register_global_actions() {
        $post_controller = self::get_controller( 'Frontend_Post' );
        /**
         * ->   add filters to override post content
         * ->   we're using these filters in Ajax requests, so they have to stay outside the is_admin() check
         * ->   the priority has to be 1 (first filter triggered)
         *      to fetch and manipulate content first and before other filters are triggered (wp_embed, wpautop, external plugins / themes, ...)
         */
        add_filter( 'the_content',                  array( $post_controller, 'modify_post_content' ), 1 );
        add_filter( 'wp_footer',                    array( $post_controller, 'modify_footer' ) );

        // prefetch get posts
        add_action( 'template_redirect',            array( $post_controller, 'buy_post' ) );
        add_action( 'template_redirect',            array( $post_controller, 'buy_time_pass' ) );
        add_action( 'template_redirect',            array( $post_controller, 'create_token' ) );

        // prefetch the post_access for loops
        add_filter( 'the_posts',                    array( $post_controller, 'prefetch_post_access' ) );
        add_filter( 'the_posts',                    'LaterPay_Helper_Post::hide_paid_posts', 1 );
        add_action( 'the_posts',                    array( $post_controller, 'hide_free_posts_with_premium_content' ) );

        // enqueue the frontend assets
        add_action( 'wp_enqueue_scripts',           array( $post_controller, 'add_frontend_stylesheets' ) );
        add_action( 'wp_enqueue_scripts',           array( $post_controller, 'add_frontend_scripts' ) );

        // add custom action to render the LaterPay invoice indicator
        $invoice_controller = self::get_controller( 'Frontend_Invoice' );
        add_action( 'wp_enqueue_scripts',           array( $invoice_controller, 'add_frontend_scripts' ) );

        // add account links action
        $account_controller = self::get_controller( 'Frontend_Account' );
        add_action( 'wp_enqueue_scripts',           array( $account_controller, 'add_frontend_scripts' ) );

        // set up unique visitors tracking
        $statistics_controller = self::get_controller( 'Frontend_Statistic' );
        add_action( 'template_redirect',            array( $statistics_controller, 'add_unique_visitors_tracking' ) );
        add_action( 'wp_footer',                    array( $statistics_controller, 'modify_footer' ) );
    }

    /**
     * Internal function to register all shortcodes.
     *
     * @return void
     */
    private function register_shortcodes() {
        $shortcode_controller = self::get_controller( 'Frontend_Shortcode' );
        // add 'free to read' shortcodes
        add_shortcode( 'laterpay_premium_download', array( $shortcode_controller, 'render_premium_download_box' ) );
        add_shortcode( 'laterpay_box_wrapper',      array( $shortcode_controller, 'render_premium_download_box_wrapper' ) );
        // add shortcode 'laterpay' as alias for shortcode 'laterpay_premium_download':
        add_shortcode( 'laterpay',                  array( $shortcode_controller, 'render_premium_download_box' ) );

        // add time passes shortcode (as alternative to action 'laterpay_time_passes')
        add_shortcode( 'laterpay_time_passes',      array( $shortcode_controller, 'render_time_passes_widget' ) );

        // add gift cards shortcodes
        add_shortcode( 'laterpay_gift_card',        array( $shortcode_controller, 'render_gift_card' ) );
        add_shortcode( 'laterpay_redeem_voucher',   array( $shortcode_controller, 'render_redeem_gift_code' ) );

        // add account links shortcode
        add_shortcode( 'laterpay_account_links',    array( $shortcode_controller, 'render_account_links' ) );
    }

    /**
     * Internal function to register the admin actions step 1.
     *
     * @return void
     */
    private function register_admin_actions_step1() {
        // add the admin panel
        $admin_controller = self::get_controller( 'Admin' );
        laterpay_event_dispatcher()->add_subscriber( $admin_controller );

        $settings_controller = self::get_controller( 'Admin_Settings' );
        laterpay_event_dispatcher()->add_subscriber( $settings_controller );
    }

    /**
     * Internal function to register the admin actions step 2 after the 'plugin_is_working' check.
     *
     * @return void
     */
    private function register_admin_actions_step2() {
        // register callbacks for adding meta_boxes
        $post_metabox_controller    = self::get_controller( 'Admin_Post_Metabox' );
        $column_controller          = self::get_controller( 'Admin_Post_Column' );
        laterpay_event_dispatcher()->add_subscriber( $post_metabox_controller );
        laterpay_event_dispatcher()->add_subscriber( $column_controller );
    }

    /**
     * Internal function to register custom actions for LaterPay.
     *
     * @return void
     */
    private function register_custom_actions() {
        // custom action to refresh the dashboard
        $dashboard_controller = self::get_controller( 'Admin_Dashboard' );
        add_action( 'laterpay_refresh_dashboard_data',  array( $dashboard_controller, 'refresh_dashboard_data' ), 10, 3 );

        // add action to delete old post views from table
        add_action( 'laterpay_delete_old_post_views',   array( $dashboard_controller, 'delete_old_post_views' ), 10, 1 );

        $post_controller = self::get_controller( 'Frontend_Post' );
        // add custom action to echo the LaterPay purchase button
        //add_action( 'laterpay_purchase_button',         array( $post_controller, 'the_purchase_button' ) ); // TODO: #612 proof of concept

        // add custom filter to check if current user has access to the post content
        add_filter( 'laterpay_check_user_access',       array( $post_controller, 'check_user_access' ), 10, 2 );

        // add custom action to echo the LaterPay time passes
        add_action( 'laterpay_time_passes',             array( $post_controller, 'the_time_passes_widget' ), 10, 4 );

        // add custom action to echo the LaterPay invoice indicator
        $invoice_controller = self::get_controller( 'Frontend_Invoice' );
        add_action( 'laterpay_invoice_indicator',       array( $invoice_controller, 'the_invoice_indicator' ) );

        // add account links action
        $account_controller = self::get_controller( 'Frontend_Account' );
        add_action( 'laterpay_account_links',           array( $account_controller, 'render_account_links' ), 10, 4 );
    }

    /**
     * Internal function to register the cache helper for {update_option_} hooks.
     *
     * @return void
     */
    private function register_cache_helper() {
        // cache helper to purge the cache on update_option()
        $cache_helper = new LaterPay_Helper_Cache();
        $options = array(
            'laterpay_global_price',
            'laterpay_global_price_revenue_model',
            'laterpay_currency',
            'laterpay_enabled_post_types',
            'laterpay_teaser_content_only',
            'laterpay_plugin_is_in_live_mode',
        );
        foreach ( $options as $option_name ) {
            add_action( 'update_option_' . $option_name, array( $cache_helper, 'purge_cache' ) );
        }
    }

    /**
     * Internal function to register all upgrade checks.
     *
     * @return void
     */
    private function register_upgrade_checks() {
        laterpay_event_dispatcher()->add_subscriber( self::get_controller( 'Install' ) );
    }

    /**
     * Internal function to register all Ajax requests.
     *
     * @return void
     */
    private function register_ajax_actions() {
        // plugin backend
        $controller = self::get_controller( 'Admin_Pricing' );
        add_action( 'wp_ajax_laterpay_pricing',                             array( $controller, 'process_ajax_requests' ) );
        add_action( 'wp_ajax_laterpay_get_category_prices',                 array( $controller, 'process_ajax_requests' ) );

        $controller = self::get_controller( 'Admin_Appearance' );
        add_action( 'wp_ajax_laterpay_appearance',                          array( $controller, 'process_ajax_requests' ) );

        $controller = self::get_controller( 'Admin_Account' );
        add_action( 'wp_ajax_laterpay_account',                             array( $controller, 'process_ajax_requests' ) );

        $controller = self::get_controller( 'Admin_Dashboard' );
        add_action( 'wp_ajax_laterpay_get_dashboard_data',                  array( $controller, 'ajax_get_dashboard_data' ) );

        // settings page
        $controller = self::get_controller( 'Admin_Settings' );
        add_action( 'wp_ajax_laterpay_backend_options',                     array( $controller, 'process_ajax_requests' ) );

        // edit post
        $controller = self::get_controller( 'Admin_Post_Metabox' );
        add_action( 'wp_ajax_laterpay_reset_post_publication_date',         array( $controller, 'reset_post_publication_date' ) );
        add_action( 'wp_ajax_laterpay_get_dynamic_pricing_data',            array( $controller, 'get_dynamic_pricing_data' ) );
        add_action( 'wp_ajax_laterpay_remove_post_dynamic_pricing',         array( $controller, 'remove_dynamic_pricing_data' ) );

        // view post
        $controller = self::get_controller( 'Frontend_Post' );
        add_action( 'wp_ajax_laterpay_post_load_purchased_content',         array( $controller, 'ajax_load_purchased_content' ) );
        add_action( 'wp_ajax_nopriv_laterpay_post_load_purchased_content',  array( $controller, 'ajax_load_purchased_content' ) );

        add_action( 'wp_ajax_laterpay_post_rate_purchased_content',         array( $controller, 'ajax_rate_purchased_content' ) );
        add_action( 'wp_ajax_nopriv_laterpay_post_rate_purchased_content',  array( $controller, 'ajax_rate_purchased_content' ) );

        add_action( 'wp_ajax_laterpay_post_rating_summary',                 array( $controller, 'ajax_load_rating_summary' ) );
        add_action( 'wp_ajax_nopriv_laterpay_post_rating_summary',          array( $controller, 'ajax_load_rating_summary' ) );

        add_action( 'wp_ajax_laterpay_redeem_voucher_code',                 array( $controller, 'ajax_redeem_voucher_code' ) );
        add_action( 'wp_ajax_nopriv_laterpay_redeem_voucher_code',          array( $controller, 'ajax_redeem_voucher_code' ) );

        // post statistics
        $controller = self::get_controller( 'Frontend_Statistic' );
        // post statistics are irrelevant, if only time pass purchases are allowed, but we still need to have the
        // option to switch the preview mode for the given post, so we only render that switch in this case
        if ( get_option( 'laterpay_only_time_pass_purchases_allowed' ) === true ) {
            add_action( 'wp_ajax_laterpay_post_statistic_render',           array( $controller, 'ajax_render_tab_without_statistics' ) );
        } else {
            add_action( 'wp_ajax_laterpay_post_statistic_render',           array( $controller, 'ajax_render_tab' ) );
        }

        add_action( 'wp_ajax_laterpay_post_statistic_visibility',           array( $controller, 'ajax_toggle_visibility' ) );
        add_action( 'wp_ajax_laterpay_post_statistic_toggle_preview',       array( $controller, 'ajax_toggle_preview' ) );
        add_action( 'wp_ajax_laterpay_post_track_views',                    array( $controller, 'ajax_track_views' ) );
        add_action( 'wp_ajax_nopriv_laterpay_post_track_views',             array( $controller, 'ajax_track_views' ) );

        // protected files within posts
        $file_helper = new LaterPay_Helper_File();
        add_action( 'wp_ajax_laterpay_load_files',                          array( $file_helper, 'load_file' ) );
        add_action( 'wp_ajax_nopriv_laterpay_load_files',                   array( $file_helper, 'load_file' ) );

        // time passes
        $controller = self::get_controller( 'Admin_TimePass' );
        add_action( 'wp_ajax_laterpay_get_time_passes_data',                array( $controller, 'ajax_get_time_passes_data' ) );

        // gift cards
        $controller = self::get_controller( 'Frontend_Shortcode' );
        add_action( 'wp_ajax_laterpay_get_gift_card_actions',               array( $controller, 'ajax_load_gift_action' ) );
        add_action( 'wp_ajax_nopriv_laterpay_get_gift_card_actions',        array( $controller, 'ajax_load_gift_action' ) );

        // premium content links
        add_action( 'wp_ajax_laterpay_get_premium_shortcode_link',          array( $controller, 'ajax_get_premium_shortcode_link' ) );
        add_action( 'wp_ajax_nopriv_laterpay_get_premium_shortcode_link',   array( $controller, 'ajax_get_premium_shortcode_link' ) );
    }

    /**
     * Late load event for other plugins to remove / add own actions to the LaterPay plugin.
     *
     * @return void
     */
    public function late_load() {
        /**
         * Late loading event for LaterPay.
         *
         * @param LaterPay_Core_Bootstrap $this
         */
        do_action( 'laterpay_and_wp_loaded', $this );
    }

    /**
     * Install callback to create custom database tables.
     *
     * @wp-hook register_activation_hook
     *
     * @return void
     */
    public function activate() {
        $install_controller = self::get_controller( 'Install' );
        $install_controller->install();

        // register the 'refresh dashboard' cron job
        wp_schedule_event( time(), 'hourly', 'laterpay_refresh_dashboard_data' );
        // register the 'delete old post views' cron job
        wp_schedule_event( time(), 'daily', 'laterpay_delete_old_post_views', array( '3 month' ) );
    }

    /**
     * Callback to deactivate the plugin.
     *
     * @wp-hook register_deactivation_hook
     *
     * @return void
     */
    public function deactivate() {
        // de-register the 'refresh dashboard' cron job
        wp_clear_scheduled_hook( 'laterpay_refresh_dashboard_data' );
        // de-register the 'delete old post views' cron job
        wp_clear_scheduled_hook( 'laterpay_delete_old_post_views', array( '3 month' ) );
    }

    /**
     * Internal function to register event subscribers.
     *
     * @return void
     */
    private function register_event_subscribers() {
        laterpay_event_dispatcher()->add_subscriber( new LaterPay_Module_Purchase() );
        laterpay_event_dispatcher()->add_subscriber( new LaterPay_Module_Appearance() );
        laterpay_event_dispatcher()->add_subscriber( new LaterPay_Module_TimePasses() );
    }

    /**
     * Internal function to register event subscribers.
     *
     * @return void
     */
    private function register_wordpress_hooks() {
        LaterPay_Hooks::get_instance()->init();
    }
}
