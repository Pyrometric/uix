<?php
/**
 * UIX Pages
 *
 * @package   uixv2
 * @author    David Cramer
 * @license   GPL-2.0+
 * @link
 * @copyright 2016 David Cramer
 */
namespace uixv2\ui;

/**
 * UIX Page class
 * @package uixv2\ui
 * @author  David Cramer
 */
class page extends panel implements \uixv2\data\save{

    /**
     * The type of object
     *
     * @since 2.0.0
     * @access public
     * @var      string
     */
    public $type = 'page';

    /**
     * Holds the option screen prefix
     *
     * @since 2.0.0
     * @access protected
     * @var      array
     */
    protected $plugin_screen_hook_suffix = array();


    /**
     * setup actions and hooks to add settings pate and save settings
     *
     * @since 2.0.0
     * @access protected
     */
    protected function actions() {
        // run parent actions ( keep 'admin_head' hook )
        parent::actions();
        // add settings page
        add_action( 'admin_menu', array( $this, 'add_settings_page' ), 9 );
        // save config
        add_action( 'wp_ajax_uix_' . $this->slug . '_save_config', array( $this, 'save_config') );
    }

    /**
     * Define core page styles
     *
     * @since 2.0.0
     * @access public
     */
    public function uix_styles() {
        parent::uix_styles();
        $pages_styles = array(
            'page'    =>  $this->url . 'assets/css/uix-page' . $this->debug_styles . '.css',
        );
        $this->styles( $pages_styles );
    }

    /**
     * Handles the Ajax request to save a page config
     *
     * @uses "wp_ajax_uix_save_config" hook
     *
     * @since 2.0.0
     * @access public
     */
    public function save_config(){
        // fetch _POST vars from helper method
        $data = uixv2()->request_vars( 'post' );
        if( ! empty( $data[ 'config' ] ) ){

            if( !wp_verify_nonce( $data[ 'uix_setup_' . $this->slug ], $this->type ) ){
                wp_send_json_error( esc_html__( 'Could not verify nonce', 'text-domain' ) );
            }

            $config = json_decode( stripslashes_deep( $data[ 'config' ] ), true );
            
            $this->set_data( $config );

            // allows submitted params to alter saving
            if( !empty( $data['params'] ) ){
                $this->struct = array_merge( $this->struct, $data['params'] );
            }

            $this->save_data();

            wp_send_json_success();

        }

    }

    /**
     * Save data for a page
     * @since 2.0.0
     * @access public
     */
    public function save_data(){
        
        /**
         * Filter config object
         *
         * @param array $config the config array to save
         * @param array $uix the uix config to be saved for
         */
        $data = apply_filters( 'uix_save_config-' . $this->type, $this->get_data(), $this );
        $store_key = $this->store_key();

        // save object
        update_option( $store_key, $data );
        
    }

    /**
     * get a UIX config store key
     * @since 1.0.0
     * @access public
     * @return string $store_key the defiuned option name for this UIX object
     */
    public function store_key(){

        if( !empty( $this->struct['store_key'] ) ){
            $store_key = $this->struct['store_key'];
        }else{
            $store_key = 'uix-' . $this->type . '-' . sanitize_text_field( $this->slug );
        }

        return $store_key;
    }

    /**
     * Determin if a page is to be loaded and set it active
     * @since 2.0.0
     * @access public
     */
    public function is_active(){
        if( !is_admin() ){ return false; }
        // check that the scrren object is valid to be safe.
        $screen = get_current_screen();
        if( empty( $screen ) || !is_object( $screen ) || $screen->base !== $this->plugin_screen_hook_suffix ){
            return parent::is_active();
        }
        return true;
    }

    /**
     * Enqueues specific tabs assets for the active pages
     *
     * @since 2.0.0
     * @access protected
     */
    protected function enqueue_active_assets(){

        if( !empty( $this->struct['base_color'] ) ){
        ?><style type="text/css">
            .contextual-help-tabs .active {
                border-left: 6px solid <?php echo $this->struct['base_color']; ?> !important;
            }
            <?php if( !empty( $this->struct['tabs'] ) && count( $this->struct['tabs'] ) > 1 ){ ?>
            .wrap > h1.uix-title {
                box-shadow: 0 0px 13px 12px <?php echo $this->struct['base_color']; ?>, 11px 0 0 <?php echo $this->struct['base_color']; ?> inset;
            }
            <?php }else{ ?>
            .wrap > h1.uix-title {
                box-shadow: 0 0 2px rgba(0, 2, 0, 0.1),11px 0 0 <?php echo $this->struct['base_color']; ?> inset;
            }
            <?php } ?>
            .uix-modal-wrap .uix-modal-title > h3,
            .wrap .uix-title a.page-title-action:hover{
                background: <?php echo $this->struct['base_color']; ?>;
                border-color: <?php echo $this->struct['base_color']; ?>;
            }
            .wrap .uix-title a.page-title-action:focus{
                box-shadow: 0 0 2px <?php echo $this->struct['base_color']; ?>;
                border-color: <?php echo $this->struct['base_color']; ?>;
            }

        </style>
        <?php
        }
    }

    /**
     * Add the settings page
     *
     * @since 2.0.0
     * @access public
     * @uses "admin_menu" hook
     */
    public function add_settings_page(){

        if( empty( $this->struct[ 'page_title' ] ) || empty( $this->struct['menu_title'] ) ){
            continue;
        }

        $args = array(
            'capability'    => 'manage_options',
            'icon'          =>  null,
            'position'      => null
        );
        $args = array_merge( $args, $this->struct );

        if( !empty( $page['parent'] ) ){

            $this->plugin_screen_hook_suffix = add_submenu_page(
                $args[ 'parent' ],
                $args[ 'page_title' ],
                $args[ 'menu_title' ],
                $args[ 'capability' ], 
                $this->type . '-' . $this->slug,
                array( $this, 'render' )
            );

        }else{

            $this->plugin_screen_hook_suffix = add_menu_page(
                $args[ 'page_title' ],
                $args[ 'menu_title' ],
                $args[ 'capability' ], 
                $this->type . '-' . $this->slug,
                array( $this, 'render' ),
                $args[ 'icon' ],
                $args[ 'position' ]
            );
        }

    }


    /**
     * Render the page
     *
     * @since 2.0.0
     * @access public
     */
    public function render(){

        ?>
        <div class="wrap uix-item" data-uix="<?php echo esc_attr( $this->slug ); ?>">
            <h1 class="uix-title"><?php esc_html_e( $this->struct['page_title'] , 'text-domain' ); ?>
                <?php if( !empty( $this->struct['version'] ) ){ ?><small><?php esc_html_e( $this->struct['version'], 'text-domain' ); ?></small><?php } ?>
                <?php if( !empty( $this->struct['save_button'] ) ){ ?>
                <a class="page-title-action" href="#save-object" data-save-object="true">
                    <span class="spinner uix-save-spinner"></span>
                    <?php esc_html_e( $this->struct['save_button'], 'text-domain' ); ?>
                </a>
                <?php } ?>
            </h1>
        <?php
        parent::render();
        echo '</div>';
    }
    
}