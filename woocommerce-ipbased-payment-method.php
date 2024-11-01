<?php
/**
 * Plugin Name: Woocommerce IP Based Payment Method
 * Version: 1.1.0
 * Plugin URI: https://wordpress.org/plugins/woo-ip-based-payment-method/
 * Description: Adds ability to make available payment method according IP address.
 * Author: Kudosta
 * Author URI: https://www.kudosta.com/
 * Requires at least: 4.0
 * Tested up to: 5.1.1
 * WC requires at least: 2.6
 * WC tested up to: 3.6.1
 * Text Domain: woocommerce-ipbased-payment-method
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

 /**
 * Check woocommerce is activated.
 */
if ( ! function_exists( 'is_woocommerce_activated' ) ) {
    function is_woocommerce_activated() {
        if ( class_exists( 'woocommerce' ) ) { return true; } else { return false; }
    }
}

class WC_Settings_Ipbased_Payment_Method {

    public static function init() {
        add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::kudos_ipbased_payment_method', 50 );
        add_action( 'woocommerce_settings_tabs_ipbased_payment_method', __CLASS__ . '::kudos_settings_tab' );
        add_action( 'woocommerce_update_options_ipbased_payment_method', __CLASS__ . '::kudos_update_settings' );
        add_filter('woocommerce_available_payment_gateways',__CLASS__.'::kudos_filter_gateways',1);

    }

    /**
     * Add a new settings tab to the WooCommerce settings tabs array.
     *
     * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
     * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
     */
    public static function kudos_ipbased_payment_method( $settings_tabs ) {
        $settings_tabs['ipbased_payment_method'] = __( 'IP Based Payment Method', 'woocommerce-ipbased-payment-method' );
        return $settings_tabs;
    }


    /**
     * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
     *
     * @uses woocommerce_admin_fields()
     * @uses self::get_settings()
     */
    public static function kudos_settings_tab() {
        woocommerce_admin_fields( self::kudos_get_settings() );
    }

    /**
     * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
     *
     * @uses woocommerce_update_options()
     * @uses self::get_settings()
     */
    public static function kudos_update_settings() {
        woocommerce_update_options( self::kudos_get_settings() );
    }

    /**
     * Return available payment methods
    */
    public static function kudos_available_payment_methods() {

        $gateways = WC()->payment_gateways();
        $_available_gateways = array(''=> '--Select--');

        if( $gateways ) {
            foreach ( $gateways->payment_gateways as $gateway ) {
                if ( $gateway->settings['enabled'] == 'yes' ) {
                    $_available_gateways[ $gateway->id ] = $gateway->title;
                }
            }
        }

        return $_available_gateways;
    }
    /**
     * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
     *
     * @return array Array of settings for @see woocommerce_admin_fields() function.
     */
    public static function kudos_get_settings() {
        $settings = array(
            'section_title' => array(
                'name'     => __( 'IP Based Payment Method', 'woocommerce-ipbased-payment-method' ),
                'type'     => 'title',
                'desc'     => '',
                'id'       => 'wc_settings_ipbased_payment_method_section_title'
            ),
            'enabled' => array(
                'name' => __( 'Enable', 'woocommerce-settings-tab-demo' ),
                'type' => 'checkbox',
                'desc' => __( 'Enable IP based payment method', 'woocommerce-ipbased-payment-method' ),
                'id'   => 'wc_settings_ipbased_payment_method_enable'
            ),
            'ip' => array(
                'name' => __( 'IP Address', 'woocommerce-settings-tab-demo' ),
                'type' => 'text',
                'desc' => __( 'Add comma separated ip addresses', 'woocommerce-ipbased-payment-method' ),
                'id'   => 'wc_settings_ipbased_payment_method_ip'
            ),
            'payment_methods' => array(
                'name' => __( 'Payment Method', 'woocommerce-ipbased-payment-method' ),
                'type' => 'select',
                'options' => self::kudos_available_payment_methods(),
                'desc' => __( 'Choose payment method', 'woocommerce-ipbased-payment-method' ),
                'id'   => 'wc_settings_ipbased_payment_method_payment_methods'
            ),
            'section_end' => array(
                 'type' => 'sectionend',
                 'id' => 'wc_settings_ipbased_payment_method_section_end'
            )
        );
        return apply_filters( 'wc_settings_ipbased_payment_method_settings', $settings );
    }

    /*
    * Return payment methods according IP address.
    */
    public static function kudos_filter_gateways($gateways){
        global $woocommerce;
        $ipaddress = '';
        if(isset($_SERVER['REMOTE_ADDR']))
        {
           $ipaddress = $_SERVER['REMOTE_ADDR'];
        }
        else if (isset($_SERVER['HTTP_CLIENT_IP']))
        {
           $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        }
        else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
           $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        else if(isset($_SERVER['HTTP_X_FORWARDED']))
        {   
           $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        }  
        else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        {   
           $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        }
        else if(isset($_SERVER['HTTP_FORWARDED']))
        {   
           $ipaddress = $_SERVER['HTTP_FORWARDED'];
        }       
        else
        {
           $ipaddress = 'UNKNOWN';
        }
        
        $client_ip = $ipaddress;
        $enabled = WC_Admin_Settings::get_option( 'wc_settings_ipbased_payment_method_enable' );
        $ip_address = WC_Admin_Settings::get_option( 'wc_settings_ipbased_payment_method_ip' );
        $setting_payment_method = WC_Admin_Settings::get_option( 'wc_settings_ipbased_payment_method_payment_methods' );
        $setting_ips = explode(',',$ip_address);
        
        if($enabled == 'yes' && !empty($setting_payment_method))
        {
            if(in_array($client_ip,$setting_ips))
            {
                return $gateways;
            } else {
                //Remove a specific payment option
                unset($gateways[$setting_payment_method]);
                return $gateways;
            }
        } else {
            return $gateways;
        }
                
        
    }

}
WC_Settings_Ipbased_Payment_Method::init();