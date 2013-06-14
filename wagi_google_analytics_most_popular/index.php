<?php

 /*
   Plugin Name: Google Analyitcs API:: Most Popular
   Plugin URI: http://www.mindvalley.com
   Description: Uses google analytics api that shows the most popular posts in descending order.
   Author: Ahmed Janim
   Version: 1.0
   Author URI: http://www.janim.me
   License: GPLv2
  */

 /**
  * init, main interface, handle main functions and dispaching
  * @package    wagi: wordpress google analytics api
  * @author     Ahmed Janim <ahmed@janim.me>
  * @license    http://www.gnu.org/licenses/gpl.txt GPL2
  * @version    1.0
  * @link       https://github.com/Janim007/wagi
  * @since      Class available since Release 1.0
  */
 error_reporting( E_ALL & ~E_WARNING & ~E_NOTICE & ~E_NOTICE );
 require_once dirname( __FILE__ ) . '/loader.php';
 require_once dirname( __FILE__ ) . '/google_api.php';
 require_once dirname( __FILE__ ) . '/widget.php';

 wp_register_style( 'wagi_stylesheet', plugins_url( 'wagi_stylesheet.css', __FILE__ ) );
 wp_enqueue_style( 'wagi_stylesheet' );

 class wagi_plugin {

     const OPTION_KEY          = 'wagi_option_key';
     const PREFIX              = 'wagi_';
     const OPTION_PAGE         = 'wagi_option_page';
     const SECTION             = 'wagi_main_section';
     const CHOOSE_USER_SECTION = 'wagi_choose_user_section';
     const ACCOUNTS_CACHE      = 'wagi_account_list';

     /**
      *  first hit, start by addmin menu item hook
      */
     public function __construct() {
         add_action( 'admin_menu', array( &$this, 'admin_setting' ) );

         if ( isset( $_GET[ 'wagi_reset' ] ) ) {
             $this->reset_options();
         }

     }

     /**
      * dispatch menu, install admin dashboard widget if it's time
      */
     public function admin_setting() {

         $options = get_option( self::OPTION_KEY );
         $loader  = new wagi_loader();

         // if user reached to add access token, install dashboard widget
         if ( $options[ 'access_token' ] != '' ) {
             $widget = new wagi_dashboard_widgets();
             $widget->init();
         }

         // attach admin_menu
         add_options_page( 'Popular posts', 'Wagi Analytics', 1, self::PREFIX . 'google_api_popular_Posts', array( &$loader, 'admin_interface' ) );

     }

     /**
      * when clicking Reset credentials button delete all realated plugin from database
      * make sure to redirect user away from reseting
      */
     public function reset_options() {
         // delete options
         delete_option( self::OPTION_KEY );

         // get accounts
         $accounts = get_transient( self::ACCOUNTS_CACHE );

         // loop throgh account and delete any related cache
         foreach( $accounts as $id => $account ) {
             delete_transient( 'wagi_posts_results_' . $id );
         }

         // delete account
         delete_transient( self::ACCOUNTS_CACHE );

         // reditect away
         header( 'Location: ' . str_replace( '&wagi_reset', '', $_SERVER[ 'REQUEST_URI' ] ) );

     }

     /**
      * init Google analytics api object passing previously configured credentials
      * @return \GoogleAnalyticsAPI
      */
     public function google_client() {

         // get the plugin options
         $options = get_option( self::OPTION_KEY );

         // current wordpress page, ex: http://local.com/wp-admin/options-general.php?page=wagi_google_api_popular_Posts
         $url = 'http://' . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'PHP_SELF' ] . '?page=' . $_GET[ 'page' ];
         $ga  = new GoogleAnalyticsAPI();
         $ga->auth->setClientId( $options[ 'client_id' ] );
         $ga->auth->setClientSecret( $options[ 'client_secret' ] );
         $ga->auth->setRedirectUri( $url );
         $ga->setAccessToken( $options[ 'access_token' ] );
         $ga->setAccountId( $options[ 'account_id' ] );

         // after redirecting from google, google sends code param in url, get it and deal with it
         if ( @isset( $_GET[ 'code' ] ) ) {

             $auth = $ga->auth->getAccessToken( $_GET[ 'code' ] );
             if ( $auth[ 'http_code' ] == 200 ) {
                 $ga->setAccessToken( $auth[ 'access_token' ] );
                 @$this->refresh_token( $auth );

                 // notifiy user about success
                 echo '<div class="updated">Authorized Successfully, check your dashboard!</div>';
             }
         }

         // whenever got chance, just try to refresh access token before it get expire
         if ( $options[ 'token_expires' ] != '' && ((time() - $options[ 'created_in' ]) >= $options[ 'token_expires' ]) ) {

             $auth = $ga->auth->refreshAccessToken( $options[ 'refresh_token' ] );

             if ( $auth[ 'http_code' ] == 200 ) {

                 $ga->setAccessToken( $auth[ 'access_token' ] );
                 @$this->refresh_token( $auth );
             }
         }

         return $ga;

     }

     /**
      * Google anayltics user usualy have multiple domains
      * Try to get/cache/display them to the user in the dashboard
      *
      * @return array
      */
     public function get_accounts() {

         $ga = $this->google_client();
         try
         {
             $profiles = $ga->getProfiles();
             if ( $profiles[ 'http_code' ] == 200 ) {
                 $accounts = array( );
                 foreach( $profiles[ 'items' ] as $item ) {
                     $id              = "ga:{$item[ 'id' ]}";
                     $name            = $item[ 'name' ];
                     $accounts[ $id ] = $name;
                 }
                 set_transient( self::ACCOUNTS_CACHE, $accounts, 60 * 60 * 24 * 30 );
                 return $accounts;
             }
             else {
                 throw new Exception( _( 'Sorry, could get accounts!' ), $profiles[ 'http_code' ] );
             }
         } catch ( Exception $e )
         {
             echo '<div class="updated">' . $e->getMessage() . ' ga<a href="' . $url . '">Authorize</a></div>';
             return 0;
         }

     }

     // re enter token data to database on refresh
     public function refresh_token( $auth ) {
         $options                    = get_option( self::OPTION_KEY );
         @$options[ 'access_token' ]  = @$auth[ 'access_token' ];
         @$options[ 'token_expires' ] = @$auth[ 'expires_in' ];
         @$options[ 'refresh_token' ] = @$auth[ 'refresh_token' ];
         @$options[ 'created_in' ]    = time();
         update_option( self::OPTION_KEY, $options );

     }

     /**
      * wordpress ajax action interface: all_actions
      * recive all actions from wordpress ajax and distebute them again
      */
     public function handle_ajax_requests() {
         $widget = new wagi_dashboard_widgets();
         echo $widget->handle_ajax_requests();
         die();

     }

 }


 $wagi = new wagi_plugin();
 add_action( 'wp_ajax_all_actions', array( $wagi, 'handle_ajax_requests' ) );