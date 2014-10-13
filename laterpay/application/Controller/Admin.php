<?php

class LaterPay_Controller_Admin extends LaterPay_Controller_Abstract
{

    const ADMIN_MENU_POINTER            = 'lpwpp01';
    const POST_PRICE_BOX_POINTER        = 'lpwpp02';
    const POST_TEASER_CONTENT_POINTER   = 'lpwpp03';

    /**
     * Show plugin in administrator panel.
     *
     * @return void
     */
    public function add_to_admin_panel() {
        $plugin_page = LaterPay_Helper_View::$pluginPage;
        add_menu_page(
            __( 'LaterPay Plugin Settings', 'laterpay' ),
            'LaterPay',
            'activate_plugins',
            $plugin_page,
            array( $this, 'run' ),
            'dashicons-laterpay-logo',
            81
        );

        $page_number    = 0;
        $menu           = LaterPay_Helper_View::get_admin_menu();
        foreach ( $menu as $name => $page ) {
            $slug = ! $page_number ? $plugin_page : $page['url'];
            $page_id = add_submenu_page(
                $plugin_page,
                $page['title'] . ' | ' . __( 'LaterPay Plugin Settings', 'laterpay' ),
                $page['title'],
                'activate_plugins',
                $slug,
                array( $this, 'run_' . $name )
            );
            add_action( 'load-' . $page_id, array( $this, 'help_' . $name ) );
            $page_number++;
        }
    }

    /**
     *
     * @param string $name
     * @param mixed  $args
     *
     * @return void
     */
    public function __call( $name, $args ) {
        if ( substr( $name, 0, 4 ) == 'run_' ) {
            return $this->run( strtolower( substr( $name, 4 ) ) );
        } elseif ( substr( $name, 0, 5 ) == 'help_' ) {
            return $this->help( strtolower( substr( $name, 5 ) ) );
        }
    }

    /**
     * @see LaterPay_Controller_Abstract::load_assets()
     */
    public function load_assets() {
        parent::load_assets();

        // load LaterPay-specific CSS
        wp_register_style(
            'laterpay-backend',
            $this->config->get( 'css_url' ) . 'laterpay-backend.css',
            array(),
            $this->config->get( 'version' )
        );
        wp_register_style(
            'open-sans',
            '//fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,300,400,600&subset=latin,latin-ext'
        );
        wp_enqueue_style( 'laterpay-backend' );
        wp_enqueue_style( 'open-sans' );

        // load LaterPay-specific JS
        wp_register_script(
            'laterpay-backend',
            $this->config->get( 'js_url' ) . 'laterpay-backend.js',
            array( 'jquery' ),
            $this->config->get( 'version' ),
            true
        );
        wp_enqueue_script( 'laterpay-backend' );

    }

    /**
     * Add html5shiv to the admin_head() for Internet Explorer < 9.
     *
     * @wp-hook admin_head
     *
     * @return void
     */
    public function add_html5shiv_to_admin_head() {
        ?>
        <!--[if lt IE 9]>
        <script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->
        <?php
    }

    /**
     * Constructor for class LaterPayController, processes the tabs in the plugin backend.
     *
     * @param string $tab
     *
     * @return void
     */
    public function run( $tab = '' ) {
        $this->load_assets();

        if ( isset( $_GET['tab'] ) ) {
            $tab = $_GET['tab'];
        }

        // return default tab, if no specific tab is requested
        if ( empty( $tab ) ) {
            $tab            = 'dashboard';
            $_GET['tab']    = 'dashboard';
        }

        switch ( $tab ) {
            default:

            // render dashboard tab
            case 'dashboard':
                $dashboard_controller = new LaterPay_Controller_Admin_Dashboard( $this->config );
                $dashboard_controller->render_page();
                break;

            // render pricing tab
            case 'pricing':
                $pricing_controller = new LaterPay_Controller_Admin_Pricing( $this->config );
                $pricing_controller->render_page();
                break;

            // render appearance tab
            case 'appearance':
                $appearance_controller = new LaterPay_Controller_Admin_Appearance( $this->config );
                $appearance_controller->render_page();
                break;

            // render account tab
            case 'account':
                $account_controller = new LaterPay_Controller_Admin_Account( $this->config );
                $account_controller->render_page();
                break;
        }
    }

    /**
     * Render contextual help, depending on the current page.
     *
     * @param string $tab
     *
     * @return void
     */
    public function help( $tab = '' ) {
        switch ( $tab ) {
            case 'wp_edit_post':
            case 'wp_add_post':
                $this->render_add_edit_post_page_help();
                break;

            case 'dashboard':
                $this->render_dashboard_tab_help();
                break;

            case 'pricing':
                $this->render_pricing_tab_help();
                break;

            case 'appearance':
                $this->render_appearance_tab_help();
                break;

            case 'account':
                $this->render_account_tab_help();
                break;

            default:
                break;
        }
    }

    /**
     * Add contextual help for add / edit post page.
     *
     * @return void
     */
    protected function render_add_edit_post_page_help() {
        $screen = get_current_screen();
        $screen->add_help_tab( array(
                                   'id'      => 'laterpay_add_edit_post_page_help',
                                   'title'   => __( 'LaterPay', 'laterpay' ),
                                   'content' => __( '
                                        <p>
                                            <strong>Setting Prices</strong><br>
                                            You can set an individual price for each post.<br>
                                            Possible prices are either 0 Euro (free) or any value between 0.05 Euro (inclusive) and 149.99 Euro (inclusive).<br>
                                            If you set an individual price, category default prices you might have set for the post\'s category(s)
                                            won\'t apply anymore, unless you make the post use a category default price.
                                        </p>
                                        <p>
                                            <strong>Dynamic Pricing Options</strong><br>
                                            You can define dynamic price settings for each post to adjust prices automatically over time.<br>
                                            <br>
                                            For example, you could sell a "breaking news" post for 0.49 Euro (high interest within the first 24 hours)
                                            and automatically reduce the price to 0.05 Euro on the second day.
                                        </p>
                                        <p>
                                            <strong>Teaser</strong><br>
                                            The teaser should give your visitors a first impression of the content you want to sell.<br>
                                            You don\'t have to provide a teaser for every single post on your site:<br>
                                            by default, the LaterPay plugin uses the first 60 words of each post as teaser content.
                                            <br>
                                            Nevertheless, we highly recommend manually creating the teaser for each post, to increase your sales.
                                        </p>
                                        <p>
                                            <strong>PPU (Pay-per-Use)</strong><br>
                                            If you choose to sell your content as <strong>Pay-per-Use</strong>, a user pays the purchased content <strong>later</strong>. The purchase is added to his LaterPay invoice and he has to log in to LaterPay and pay, once his invoice has reached 5.00 EUR.<br>
                                            LaterPay <strong>recommends</strong> Pay-per-Use for all prices up to 5.00 EUR as they deliver the <strong>best purchase experience</strong> for your users.<br>
                                            PPU is possible for prices between (including) <strong>0.05 EUR</strong> and (including) <strong>5.00 EUR</strong>.
                                        </p>
                                        <p>
                                            <strong>SIS (Single Sale)</strong><br>
                                            If you sell your content as <strong>Single Sale</strong>, a user has to <strong>log in</strong> to LaterPay and <strong>pay</strong> for your content <strong>immediately</strong>.<br>
                                            Single Sales are especially suitable for higher-value content and / or content that immediately occasions costs (e. g. license fees for a video stream).<br>
                                            A Single Sales is possible between (including) <strong>1.49 EUR</strong> and (including) <strong>149.99 EUR</strong>.<br>
                                            Single Sales are currently available for <strong>individual prices</strong> and will soon be implemented for the global default price and category default prices.
                                        </p>',
                                    'laterpay'
                                   ),
                               ) );
    }

    /**
     * Add contextual help for dashboard tab.
     *
     * @return  void
     */
    protected function render_dashboard_tab_help() {
        $screen = get_current_screen();
        $screen->add_help_tab( array(
                                   'id'      => 'laterpay_dashboard_tab_help_conversion',
                                   'title'   => __( 'Conversion', 'laterpay' ),
                                   'content' => __( '
                                                    <p>
                                                        The <strong>Conversion</strong> (short for Conversion Rate) is the share of visitors of a specific post, who actually <strong>bought</strong> the post.<br>
                                                        A conversion of 100% would mean that every user who has visited a post page and has read the teaser content had bought the post with LaterPay.<br>
                                                        The conversion rate is one of the most important metrics for selling your content successfully: It indicates, if the price is perceived as adequate and if your content fits your audience\'s interests.
                                                    </p>
                                                    <p>
                                                        The metric <strong>New Customers</strong> indicates the share of your customers who bought with LaterPay for the first time in the reporting period.<br>
                                                        Please note that this is only an approximate value.
                                                    </p>',
                                                    'laterpay'
                                                ),
                               ) );
        $screen->add_help_tab( array(
                                   'id'      => 'laterpay_dashboard_tab_help_items_sold',
                                   'title'   => __( 'Items Sold', 'laterpay' ),
                                   'content' => __( '
                                                    <p>
                                                        The column <strong>Items Sold</strong> provides an overview of all your sales in the reporting period.
                                                    </p>
                                                    <p>
                                                        <strong>AVG Items Sold</strong> (short for Average Items Sold) indicates how many posts you sold on average per day in the reporting period.
                                                    </p>',
                                                    'laterpay'
                                                ),
                               ) );
        $screen->add_help_tab( array(
                                   'id'      => 'laterpay_dashboard_tab_help_gross_revenue',
                                   'title'   => __( 'Committed Revenue', 'laterpay' ),
                                   'content' => __( '
                                                    <p>
                                                        <strong>Committed Revenue</strong> is the value of all purchases, for which your users have committed themselves to pay later (or paid immediately in case of a Single Sale purchase).
                                                    </p>
                                                    <p>
                                                        <strong>AVG Revenue</strong> (short for Average Revenue) indicates the average revenue per day in the reporting period.
                                                    </p>
                                                    <p>
                                                        Please note that this <strong>is not the amount of money you will receive with your next LaterPay payout</strong>, as a user will have to pay his invoice only once it reaches 5.00 € and LaterPay will deduct a fee of 15% for each purchase that was actually paid.
                                                    </p>',
                                                    'laterpay'
                                                ),
                               ) );
    }

    /**
     * Add contextual help for pricing tab.
     *
     * @return  void
     */
    protected function render_pricing_tab_help() {
        $screen = get_current_screen();
        $screen->add_help_tab( array(
                                   'id'      => 'laterpay_pricing_tab_help_global_default_price',
                                   'title'   => __( 'Global Default Price', 'laterpay' ),
                                   'content' => __( '
                                                    <p>
                                                        The global default price is used for all posts, for which no
                                                        category default price or individual price has been set.<br>
                                                        Accordingly, setting the global default price to 0 Euro makes
                                                        all articles free, for which no category default price or
                                                        individual price has been set.
                                                    </p>',
                                                    'laterpay'
                                                ),
                               ) );
        $screen->add_help_tab( array(
                                   'id'      => 'laterpay_pricing_tab_help_category_default_price',
                                   'title'   => __( 'Category Default Prices', 'laterpay' ),
                                   'content' => __( '
                                                    <p>
                                                        A category default price is applied to all posts in a given
                                                        category that don\'t have an individual price.<br>
                                                        A category default price overwrites the global default price.<br>
                                                        If a post belongs to multiple categories, you can choose on
                                                        the add / edit post page, which category default price should
                                                        be effective.<br>
                                                        For example, if you have set a global default price of 0.15 Euro,
                                                        but a post belongs to a category with a category default price
                                                        of 0.30 Euro, that post will sell for 0.30 Euro.
                                                    </p>',
                                                    'laterpay'
                                                ),
                               ) );
        $screen->add_help_tab( array(
                                   'id'      => 'laterpay_pricing_tab_help_currency',
                                   'title'   => __( 'Currency', 'laterpay' ),
                                   'content' => __( '
                                                    <p>
                                                        Currently, the plugin only supports Euro as default currency, but
                                                        you will soon be able to choose between different currencies for your blog.<br>
                                                        Changing the standard currency will not convert the prices you
                                                        have set.
                                                        Only the currency code next to the price is changed.<br>
                                                        For example, if your global default price is 0.10 Euro and you
                                                        change the default currency to U.S. dollar, the global default
                                                        price will be 0.10 U.S. dollar.
                                                    </p>',
                                                    'laterpay'
                                                ),
                               ) );
    }

    /**
     * Add contextual help for appearance tab.
     *
     * @return  void
     */
    protected function render_appearance_tab_help() {
        $screen = get_current_screen();
        $screen->add_help_tab( array(
                                   'id'      => 'laterpay_appearance_tab_help_preview_mode',
                                   'title'   => __( 'Preview Mode', 'laterpay' ),
                                   'content' => __( '
                                                    <p>
                                                        The preview mode defines, how teaser content is shown to your
                                                        visitors.<br>
                                                        You can choose between two preview modes:
                                                    </p>
                                                    <ul>
                                                        <li>
                                                            <strong>Teaser only</strong> &ndash; This mode shows only
                                                            the teaser with an unobtrusive purchase link below.
                                                        </li>
                                                        <li>
                                                            <strong>Teaser + overlay</strong> &ndash; This mode shows
                                                            the teaser and an excerpt of the full content under a
                                                            semi-transparent overlay that briefly explains LaterPay.<br>
                                                            The plugin never loads the entire content before a user has
                                                            purchased it.
                                                        </li>
                                                    </ul>',
                                                    'laterpay'
                                                ),
                               ) );
        $screen->add_help_tab( array(
                                   'id'      => 'laterpay_appearance_tab_help_invoice_indicator',
                                   'title'   => __( 'Invoice Indicator', 'laterpay' ),
                                   'content' => __( '
                                                    <p>
                                                        The plugin provides a code snippet you can insert into your
                                                        theme that displays the user\'s current LaterPay invoice total
                                                        and provides a direct link to his LaterPay user backend.<br>
                                                        You <em>don\'t have to</em> integrate this snippet, but we
                                                        recommend it for transparency reasons.
                                                    </p>',
                                                    'laterpay'
                                                ),
                               ) );
    }

    /**
     * Add contextual help for account tab.
     *
     * @return  void
     */
    protected function render_account_tab_help() {
        $screen = get_current_screen();
        $screen->add_help_tab( array(
                                   'id'      => 'laterpay_account_tab_help_api_credentials',
                                   'title'   => __( 'API Credentials', 'laterpay' ),
                                   'content' => __( '
                                                    <p>
                                                        To access the LaterPay API, you need LaterPay API credentials,
                                                        consisting of
                                                    </p>
                                                    <ul>
                                                        <li><strong>Merchant ID</strong> (a 22-character string) and</li>
                                                        <li><strong>API Key</strong> (a 32-character string).</li>
                                                    </ul>
                                                    <p>
                                                        LaterPay runs two completely separated API environments that
                                                        need <strong>different API credentials:</strong>
                                                    </p>
                                                    <ul>
                                                        <li>
                                                            The <strong>Sandbox</strong> environment for testing and
                                                            development use.<br>
                                                            In this environment you can play around with LaterPay
                                                            without fear, as your transactions will only be simulated
                                                            and not actually be processed.<br>
                                                            LaterPay guarantees no particular service level of
                                                            availability for this environment.
                                                        </li>
                                                        <li>
                                                            The <strong>Live</strong> environment for production use.<br>
                                                            In this environment all transactions will be actually
                                                            processed and credited to your LaterPay merchant account.<br>
                                                            The LaterPay SLA for availability and response time apply.
                                                        </li>
                                                    </ul>
                                                    <p>
                                                        The LaterPay plugin comes with a set of <strong>public Sandbox
                                                        credentials</strong> to allow immediate testing use.
                                                    </p>
                                                    <p>
                                                        If you want to switch to <strong>Live mode</strong> and sell
                                                        content, you need your individual <strong>Live API credentials.
                                                        </strong><br>
                                                        Due to legal reasons, we can email you those credentials only
                                                        once we have received a <strong>signed merchant contract</strong>
                                                        including <strong>all necessary identification documents</strong>
                                                        by ground mail.
                                                    </p>',
                                                    'laterpay'
                                                ),
                               ) );
        $screen->add_help_tab( array(
                                   'id'      => 'laterpay_account_tab_help_plugin_mode',
                                   'title'   => __( 'Plugin Mode', 'laterpay' ),
                                   'content' => __( '
                                                    <p>You can run the LaterPay plugin in two modes:</p>
                                                    <ul>
                                                        <li>
                                                            <strong>Test Mode</strong> &ndash; The test mode lets you
                                                            test your plugin configuration.<br>
                                                            While providing the full plugin functionality, payments are
                                                            only simulated and not actually processed.<br>
                                                            The plugin will <em>only</em> be visible to admin users,
                                                            not to visitors.
                                                        </li>
                                                        <li>
                                                            <strong>Live Mode</strong> &ndash; In live mode, the plugin
                                                            is publicly visible and manages access to paid content.<br>
                                                            All payments are actually processed.
                                                        </li>
                                                    </ul>
                                                    <p>
                                                        Using the LaterPay plugin usually requires some adjustments on
                                                        your theme.<br>
                                                        Therefore, we recommend installing, configuring, and testing
                                                        the LaterPay plugin on a test system before activating it on
                                                        your production system.
                                                    </p>',
                                                    'laterpay'
                                                ),
                               ) );
    }

    /**
     * Add WordPress pointers to pages.
     *
     * @return void
     */
    public function modify_footer() {
        $pointers = LaterPay_Controller_Admin::get_pointers_to_be_shown();

        // don't render the partial, if there are no pointers to be shown
        if ( empty( $pointers ) ) {
            return;
        }

        $this->assign( 'pointers', $pointers );

        echo $this->get_text_view( 'backend/partials/pointer_scripts' );
    }

    /**
     * Load LaterPay stylesheet with LaterPay vector logo on all pages where the admin menu is visible.
     *
     * @return void
     */
    public function add_plugin_admin_assets() {
        wp_register_style(
            'laterpay-admin',
            $this->config->css_url . 'laterpay-admin.css',
            array(),
            $this->config->version
        );
        wp_enqueue_style( 'laterpay-admin' );

    }

    /**
     * Hint at the newly installed plugin using WordPress pointers.
     *
     * @return void
     */
    public function add_admin_pointers_script() {
        $pointers = LaterPay_Controller_Admin::get_pointers_to_be_shown();

        // don't enqueue the assets, if there are no pointers to be shown
        if ( empty( $pointers ) ) {
            return;
        }

        wp_enqueue_script( 'wp-pointer' );
        wp_enqueue_style( 'wp-pointer' );
    }

    /**
     * Return the pointers that have not been shown yet.
     *
     * @return array $pointers
     */
    public function get_pointers_to_be_shown() {
        $dismissed_pointers = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
        $pointers = array();

        if ( ! in_array( LaterPay_Controller_Admin::ADMIN_MENU_POINTER, $dismissed_pointers ) ) {
            $pointers[] = LaterPay_Controller_Admin::ADMIN_MENU_POINTER;
        }
        // add pointers to LaterPay features on add / edit post page
        if ( ! in_array( LaterPay_Controller_Admin::POST_PRICE_BOX_POINTER, $dismissed_pointers ) ) {
            $pointers[] = LaterPay_Controller_Admin::POST_PRICE_BOX_POINTER;
        }
        if ( ! in_array( LaterPay_Controller_Admin::POST_TEASER_CONTENT_POINTER, $dismissed_pointers ) ) {
            $pointers[] = LaterPay_Controller_Admin::POST_TEASER_CONTENT_POINTER;
        }

        return $pointers;
    }

    /**
     * Return all pointer constants from current class.
     *
     * @return array $pointers
     */
    public static function get_all_pointers() {
        $reflection     = new ReflectionClass( __CLASS__ );
        $class_constants = $reflection->getConstants();
        $pointers = array();

        if ( $class_constants ) {
            foreach (array_keys($class_constants) as $key_value) {
                if ( strpos( $key_value, 'POINTER') !== FALSE ) {
                    $pointers[] = $class_constants[$key_value];
                }
            }
        }

        return $pointers;
    }
}
