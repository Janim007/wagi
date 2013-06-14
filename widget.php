<?php

 /**
  * Short description for class
  *
  * Long description for class (if any)...
  *
  * @copyright  2006 Zend Technologies
  * @license    http://www.zend.com/license/3_0.txt   PHP License 3.0
  * @version    Release: @package_version@
  * @link       http://dev.zend.com/package/PackageName
  * @since      Class available since Release 1.2.0
  */
 class wagi_dashboard_widgets extends wagi_plugin {

     public $error = '';

     public function init() {
         add_action( 'wp_dashboard_setup', array( $this, 'create_dashboard_widget' ) );
         add_action( 'admin_footer', array( $this, 'wagi_footer_content' ) );
         $this->get_data();

     }

     public function create_dashboard_widget() {
         wp_add_dashboard_widget(
                   self::PREFIX . 'dashboard_widget', 'Most popular posts GA', array( &$this, 'dashboard_widgets_content' )
         );

     }

     public function dashboard_widgets_content() {
         $options  = get_option( self::OPTION_KEY, $default  = false );
         $accounts = get_transient( self::ACCOUNTS_CACHE );
         if ( !$accounts ) {
             $accounts = $this->get_accounts();
         }

         ?>
         <div class="">
             <?= ($this->error != '') ? '<div class="alert alert-error">' . $this->error . '</div>' : '' ?>
             <div class="pull-left">
                 <select class="wagi-change-time-period-selector">
                     <?php foreach( array( 7, 30, 360 ) as $time ) { ?>
                         <option value="<?= $time ?>" <?= ($options[ 'time_period' ] == $time) ? "selected='selected'" : '' ?>>
                             <?= ($time == 360) ? 'One Year' : $time . ' Days' ?>
                         </option>
                     <?php } ?>
                 </select>

             </div>
             <div class="pull-right">
                 <input type="button" class="button wagi-refresh-data" value="Refresh data" />
             </div>
             <div class="clearfix"></div><br/>
             <div class="most-popular-posts-list table table_content">
                 <?php

                 $results = json_decode( $this->get_data() );

                 if ( count( $results ) > 0 ) {

                     ?>

                     <table class="table table_content">
                         <thead><tr><td><?= _( 'Post' ) ?></td><td><?= _( 'Visits' ) ?></td></tr></thead>
                         <tbody>
                             <?php

                             foreach( $results as $result ) {

                                 ?>
                                 <tr>
                                     <td><a href="<?= $result[ 1 ] ?>"><?= $result[ 3 ] ?></a>
                                     </td><td><?= $result[ 2 ] ?></td>
                                 </tr>
                             <?php } // end foreach  ?>
                         </tbody>
                     </table>
                 <?php }else { // else results  == 0 ?>
                 <div class="alert alert-danger"><?=_('No results.')?></div>
                 <?php } // end if count result > 0?>
             </div>
             <select class="wagi-change-account-id-selector">
                 <?php foreach( $accounts as $id => $account ) { ?>
                     <option value="<?= $id ?>" <?php if ( $options[ 'account_id' ] == $id ) { ?> selected="selected" <?php } ?>>
                         <?= $account ?>
                     </option>
                 <?php } ?>
                 <?php if ( $options[ 'account_id' ] == '' ) { ?>
                     <option value="0" selected="selected">Select Account!</option>
                 <?php } ?>
             </select>
         </div>
         <?php

     }

     public function wagi_footer_content() {

         ?>
         <script>
             jQuery(function(){
                 jQuery('.wagi-change-account-id-selector').on('change', function(e) {
                     var new_account_id = jQuery(this).val();
                     jQuery.post(ajaxurl, {'action':'all_actions', 'account_id': new_account_id, 'wagi_action': 'change_account_id'}, function(response) {
                         if(response != 1) { alert('could not change account!') }
                         jQuery('.wagi-refresh-data').trigger('click');
                     });
                 });

                 jQuery('.wagi-change-time-period-selector').on('change', function(e) {
                     var new_time_period = jQuery(this).val();
                     jQuery.post(ajaxurl, {'action':'all_actions', 'time_period': new_time_period, 'wagi_action': 'change_time_period'}, function(response) {
                         if(response != 1) { alert('could not change time period!') }
                         jQuery('.wagi-refresh-data').trigger('click');
                     });
                 });

                 jQuery('.wagi-refresh-data').on('click', function(e) {
                     var self = this;
                     jQuery(self).val('Refreshing Data ...').attr('disabled', 'disabled');
                     jQuery.ajax({
                         url: ajaxurl,
                         type: 'POST',
                         data: {
                             'action': 'all_actions',
                             'wagi_action': 'refresh_data'
                         },
                         success: function(json) {
                             jQuery('.most-popular-posts-list').html('');

                             var html = "<table class='table table_content'>"+
                                 "<thead><tr>"+
                                 "<td>Post</td><td>Visits</td>"+
                                 "<tr></thead>"+
                                 "<tbody>"
                             jQuery.each(json, function(item) {

                                 html += "<tr>"+
                                     "<td><a href='" + json[item][1] + "'>" + json[item][3] + "</a></td>"+
                                     "<td>" + json[item][2] + "</td>"+
                                     "</tr>";

                             });

                             html += "</tbody></table>";
                             jQuery('.most-popular-posts-list').append(html);
                             jQuery(self).val('Refresh Data').removeAttr('disabled');
                         },
                         error: function() {
                             jQuery(self).val('Refresh Data').removeAttr('disabled');
                             jQuery('.most-popular-posts-list').html('<div class="alert alert-error">Something wrong with WAGI, please check setting page</div>');
                         }
                     });
                 });
             });
         </script>
         <?php

     }

     public function get_data() {

         $options         = get_option( self::OPTION_KEY );
         $is_ajax_request = isset( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) && strtolower( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) == 'xmlhttprequest';

         $link_value = get_permalink( 1 );
         $url_data   = parse_url( $link_value );

         if ( get_option( 'permalink_structure' ) ) {

             $wp_permalink_structure = get_option( 'permalink_structure' );
             $url_path               = explode( '/', $url_data[ 'path' ] );
             $wp_path                = explode( '/', $wp_permalink_structure );
             if ( $url_path[ 1 ] != $wp_path[ 1 ] ) {
                 $filter                 = '~^/' . $url_path[ 1 ];
             }else {
                 $filter = '~';
             }
             $wp_permalink_structure = preg_quote( $wp_permalink_structure );
             $wp_permalink_structure = str_replace( array( '%year%', '%monthnum%', '%day%', '%hour%', '%minute%', '%second%', '%post_id%', '%postname%', '%category%', '%author%' ), '.*', $wp_permalink_structure );
             $filter = $filter . $wp_permalink_structure;
         }
         else {
             $url    = $url_data[ 'path' ] . '?' . $url_data[ 'query' ];
             $match  = preg_replace( '/p\=\d+.*/', '\?p\=.*', $url );
             $filter = '~^' . $match;
         }

         $client         = $this->google_client();
         $from_past_days = ($options[ 'time_period' ] == '') ? 7 : $options[ 'time_period' ];

         $params = array(
              'metrics'     => 'ga:visits',
              'dimensions'  => 'ga:pagePath',
              'sort'        => '-ga:visits',
              'max-results' => 10,
              'start-date'  => date( 'Y-m-d', strtotime( "-$from_past_days days" ) ),
              'end-date'    => date( 'Y-m-d' ),
              'filters'     => 'ga:pagePath=' . $filter
         );
         try
         {

             if ( !$is_ajax_request ) {
                 $results_from_cache = get_transient( 'wagi_posts_results_' . $options[ 'account_id' ] );
                 if ( $results_from_cache ) {
                     return json_encode( $results_from_cache );
                 }
             }

             $results = $client->query( $params );

             if ( $results[ 'http_code' ] == 200 ) {
                 $posts = array( );
                 foreach( $results[ 'rows' ] as $result ) {

                     $post_id = url_to_postid( $result[ 0 ] );

                     if ( $post_id ) {

                         $posts[] = array($post_id, $result[ 0 ], $result[ 1 ], get_the_title( $post_id ) );
                     }
                 }

                 set_transient( 'wagi_posts_results_' . $options[ 'account_id' ], $posts, 60 * 60 * 1 );

                 return json_encode( $posts );
             }
             else {
                 $this->error = 'Wagi popular posts error: ' . $results[ 'error' ][ 'message' ];

                 throw new Exception( $this->error, 200 );
             }
             // die();
         } catch ( Exception $e )
         {
             if ( $is_ajax_request ) {
                 http_response_code( 401 );
                 return 1;
             }
             else {
                 echo sprintf( '<div class="error">Wagi: %s</div>', $e->getMessage() );
             }
         }

     }

     public function handle_ajax_requests() {

         $action = @$_POST[ 'wagi_action' ];
         switch ( $action )
         {
             case 'change_account_id':
                 $options                  = get_option( self::OPTION_KEY );
                 $options[ 'account_id' ]  = @$_POST[ 'account_id' ];
                 update_option( self::OPTION_KEY, $options );
                 return 1;
                 break;
             case 'change_time_period':
                 $options                  = get_option( self::OPTION_KEY );
                 $options[ 'time_period' ] = @$_POST[ 'time_period' ];
                 update_option( self::OPTION_KEY, $options );
                 return 1;
                 break;
             case 'refresh_data':
                 header( 'Content-Type: text/json' );
                 echo $this->get_data();
                 break;
         }

     }

 }