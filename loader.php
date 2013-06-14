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
      *  initlize setting area
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
         if ( $options[ 'client_id' ] != '' && $options['client_secret'] != '') {

                 $ga              = $this->google_client();
                 $data            = array( 'url' => $ga->auth->buildAuthUrl() );

                 add_settings_section( self::CHOOSE_USER_SECTION, 'Authorise plugin to access your data!', array( &$this, 'select_account_section_cb' ), self::OPTION_PAGE );


                 if ($options['access_token'] == '') {
                 add_settings_field(
                           self::PREFIX . 'plugin_button_authorize', 'Click this after you add your credentials', array( &$this, 'login_button_fb' ), self::OPTION_PAGE, self::CHOOSE_USER_SECTION, $data
                 );
                 }else {
                  add_settings_field(
                           self::PREFIX . 'plugin_button_reset', 'Reset', array( &$this, 'reset_button' ), self::OPTION_PAGE, self::CHOOSE_USER_SECTION
                 );
                 }
         }

         register_setting( self::PREFIX . 'option_group', self::OPTION_KEY, array( &$this, 'options_validator' ) );

     }

     function options_validator( $input ) {
         //$input['api'] = wp_filter_nohtml_kses( $input['api'] );
         return $input;

     }

     function section_cb() {

     }

     function select_account_section_cb() {


     }

     function setting_field_fn( $args ) {
         $id    = $args[ 'field' ];
         $options = get_option( self::OPTION_KEY );
         echo sprintf( '<input type="text" size="50" id="%s" name="%s" value="%s" autocomplete="off"/>', $id, self::OPTION_KEY . '[' . $id . ']', $options[ $id ] );

         if($id == 'client_id') {
            $html .= '<input type="hidden" id="created_in" name="wagi_option_key[created_in]" value="' . $options[ 'created_in' ] . '"/>';
            $html .= '<input type="hidden" id="token_expires" name="wagi_option_key[token_expires]" value="' . $options[ 'token_expires' ] . '"/>';
            $html .= '<input type="hidden" id="refresh_token" name="wagi_option_key[refresh_token]" value="' . $options[ 'refresh_token' ] . '"/>';
            $html .= '<input type="hidden" id="access_token" name="wagi_option_key[access_token]" value="' . $options[ 'access_token' ] . '"/>';
            echo $html;
         }

     }

     public function login_button_fb( $v ) {
         echo '<a href="' . $v[ 'url' ] . '" class="button button-primary button-large">Click here to Authorize Google Analyitcs to use this plugin!</a>';

     }

     public function reset_button() {
         echo '<a href="'.$_SERVER['REQUEST_URI'].'&wagi_reset" class="button button-large">Reset credentials</a>';
     }

     public function select_account_fn( $v ) {
         $options = get_option( self::OPTION_KEY, $default = false );

         $html = '<select name="' . self::OPTION_KEY . '[' . $v[ 'field' ] . ']" id="' . $v[ 'field' ] . '">';
         foreach( $v[ 'accounts' ] as $key => $value ) {
             $html .= '<option value="' . $key . '">' . $value . '</option>';
         }
         $html .= '</select>';

         echo $html;

     }

 }


 $wagi_loader = new wagi_loader();

?>
