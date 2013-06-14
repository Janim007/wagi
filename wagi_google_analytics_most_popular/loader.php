<?php

 /**
  * Controller of adminitration section of the plugin
  *
  * @copyright  2013 Ahmed Janim <ahmed@janim.me>
  * @license    http://www.gnu.org/licenses/gpl.txt GPL2
  * @version    Release: @1.0
  * @link       https://github.com/Janim007/wagi
  * @since      Class available since Release 1.0
  */
 class wagi_loader extends wagi_plugin {

     /**
      *  initlize setting page
      */
     public function __construct() {

         add_action( 'admin_init', array( &$this, 'admin_init' ) );

     }

     /**
      * Display setting page init content
      *
      * @return null
      */
     function admin_interface() {
         $options = get_option( self::OPTION_KEY, $default = false );

         ?>
         <div class="wrap">
             <div class="icon32" id="icon-options-general"><br></div>
             <h2>Wagi, Most popular posts</h2>
             <p>Please Enter your google data, you can find/get/update them here: <a href="https://code.google.com/apis/console/?api=plus" target="_blank">https://code.google.com/apis/console/?api=plus</a></p>
             <p>For installation information visit <a href="https://github.com/Janim007/wagi">https://github.com/Janim007/wagi</a></p>
             <form action="options.php" method="post">
                 <?php settings_fields( self::PREFIX . 'option_group' ); ?>
                 <?php do_settings_sections( self::OPTION_PAGE ); ?>
                 <p class="submit">
                     <input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Credentials ' ); ?>" />
                 </p>
             </form>
         </div>
         <?php

     }

     /**
      * Start wordpress settings API
      * 1. display client_id, client_secret fields first
      * 2. after (1) generate authentication url and let user use it
      *
      *
      */
     public function admin_init() {

         $options = get_option( self::OPTION_KEY, $default = false );

         add_settings_section( self::SECTION, 'Google API Data:', array( &$this, 'section_cb' ), self::OPTION_PAGE );

         add_settings_field(
                   self::PREFIX . 'plugin_text_id', 'Client ID', array( &$this, 'setting_field_fn' ), self::OPTION_PAGE, self::SECTION, array( 'field' => 'client_id' )
         );

         add_settings_field(
                   self::PREFIX . 'plugin_text_secret', 'Client Secret', array( &$this, 'setting_field_fn' ), self::OPTION_PAGE, self::SECTION, array( 'field' => 'client_secret' )
         );

         // if we got the client id, let's check for authentication
         if ( $options[ 'client_id' ] != '' && $options[ 'client_secret' ] != '' ) {

             // build auth url
             $ga   = $this->google_client();
             $data = array( 'url' => $ga->auth->buildAuthUrl() );

             add_settings_section( self::CHOOSE_USER_SECTION, 'Authorise plugin to access your data!', array( &$this, 'select_account_section_cb' ), self::OPTION_PAGE );

             // if no access_token, auth, else view reset button
             if ( $options[ 'access_token' ] == '' ) {
                 add_settings_field(
                           self::PREFIX . 'plugin_button_authorize', 'Click this after you add your credentials', array( &$this, 'login_button_fb' ), self::OPTION_PAGE, self::CHOOSE_USER_SECTION, $data
                 );
             }
             else {

                 // Reset Button
                 add_settings_field(
                           self::PREFIX . 'plugin_button_reset', 'Reset', array( &$this, 'reset_button' ), self::OPTION_PAGE, self::CHOOSE_USER_SECTION
                 );
             }
         }

         register_setting( self::PREFIX . 'option_group', self::OPTION_KEY, array( &$this, 'options_validator' ) );

     }

     function options_validator( $input ) {
         // @TODO:: filter, but Google credentioals are little wierd. not sure @fixme
         return $input;

     }

     /** method returns as echo description of client_id, secret section
      *  part of setting API
      */
     function section_cb() {

     }

     /* method returns as echo description of auth section
      * part of setting API
      */

     function select_account_section_cb() {

     }

     /**
      * return as echo form fields
      * note: making sure, not to loose the options array hide other values and resend them to the db
      * @param type $args
      */
     function setting_field_fn( $args ) {
         $id      = $args[ 'field' ];
         $options = get_option( self::OPTION_KEY );
         echo sprintf( '<input type="text" size="50" id="%s" name="%s" value="%s" autocomplete="off"/>', $id, self::OPTION_KEY . '[' . $id . ']', $options[ $id ] );

         if ( $id == 'client_id' ) {
             $html .= '<input type="hidden" id="created_in" name="wagi_option_key[created_in]" value="' . $options[ 'created_in' ] . '"/>';
             $html .= '<input type="hidden" id="token_expires" name="wagi_option_key[token_expires]" value="' . $options[ 'token_expires' ] . '"/>';
             $html .= '<input type="hidden" id="refresh_token" name="wagi_option_key[refresh_token]" value="' . $options[ 'refresh_token' ] . '"/>';
             $html .= '<input type="hidden" id="access_token" name="wagi_option_key[access_token]" value="' . $options[ 'access_token' ] . '"/>';
             $html .= '<input type="hidden" id="time_period" name="wagi_option_key[time_period]" value="' . $options[ 'time_period' ] . '"/>';
             $html .= '<input type="hidden" id="account_id" name="wagi_option_key[account_id]" value="' . $options[ 'account_id' ] . '"/>';
             echo $html;
         }

     }

     /**
      * after generating url from parent::google_client display the button to redirect user to google page
      * part of setting API
      * @param type $v
      */
     public function login_button_fb( $v ) {
         echo '<a href="' . $v[ 'url' ] . '" class="button button-primary button-large">Click here to Authorize Google Analyitcs to use this plugin!</a>';

     }

     /**
      * click to reset, adds &wagi_reset param to the url, where app sees and reset all options in parent::reset_options
      */
     public function reset_button() {
         echo '<a href="' . $_SERVER[ 'REQUEST_URI' ] . '&wagi_reset" class="button button-large">Reset credentials</a>';

     }
 }

?>
