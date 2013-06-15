<?php

 /**
  * Add dashboard widget to home and manage it
  *
  * @copyright  2013 Ahmed Janim <ahmed@janim.me>
  * @license    http://www.gnu.org/licenses/gpl.txt GPL2
  * @version    Release: @1.0
  * @link       https://github.com/Janim007/wagi
  * @since      Class available since Release 1.0
  */
 class wagi_dashboard_widgets extends wagi_plugin {

     public $error = '';

     /**
      * start by hooking wp_dashbaod_setup action
      * hook required javascript into footer
      */
     public function init() {
         add_action( 'wp_dashboard_setup', array( $this, 'create_dashboard_widget' ) );
         add_action( 'admin_footer', array( $this, 'wagi_footer_content' ) );

     }

     public function create_dashboard_widget() {
         wp_add_dashboard_widget(
                   self::PREFIX . 'dashboard_widget', 'Most popular posts GA', array( &$this, 'dashboard_widgets_content' )
         );

     }

     /**
      * build the dashboard widget here
      */
     public function dashboard_widgets_content() {

         $options = get_option( self::OPTION_KEY, $default = false );

         // get account form cache, if not ask google for them
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
             <?php

             // tell admin if widget installed on frond-end
             echo (!is_active_widget( false, false, 'wagi_frond_widget', true )) ? '<div class="alert">' . __( 'Widget is inactive' ) . '</div>' : '';

             ?>
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
                     <?php } // end foreach    ?>
                         </tbody>
                     </table>
                 <?php }
                 else { // else results  == 0

                     ?>
                     <div class="alert alert-danger"><?= _( 'No results.' ) ?></div>
                 <?php } // end if count result > 0  ?>
             </div>

             <!-- the account selector box -->
             <select class="wagi-change-account-id-selector">
                 <?php foreach( $accounts as $id => $account ) { ?>
                     <option value="<?= $id ?>" <?php if ( $options[ 'account_id' ] == $id ) { ?> selected="selected" <?php } ?>>
                     <?= $account ?>
                     </option>
                 <?php } ?>
                 <!-- add another option if empty, users don't know what onchange means -->
         <?php if ( $options[ 'account_id' ] == '' ) { ?>
                     <option value="0" selected="selected">Select Account!</option>
         <?php } ?>
             </select>
         </div>
         <?php

     }

     /**
      * javascript and ajax requests from dashboard
      *
      */
     public function wagi_footer_content() {

         ?>
         <script>
             jQuery(function(){
                 // call on select change account and send server ajax request to change it
                 jQuery('.wagi-change-account-id-selector').on('change', function(e) {
                     var new_account_id = jQuery(this).val();
                     jQuery.post(ajaxurl, {'action':'all_actions', 'account_id': new_account_id, 'wagi_action': 'change_account_id'}, function(response) {
                         if(response != 1) { alert('could not change account!') }
                         jQuery('.wagi-refresh-data').trigger('click');
                     });
                 });

                 // call on select change time period and send server ajax request to change the default one
                 jQuery('.wagi-change-time-period-selector').on('change', function(e) {
                     var new_time_period = jQuery(this).val();
                     jQuery.post(ajaxurl, {'action':'all_actions', 'time_period': new_time_period, 'wagi_action': 'change_time_period'}, function(response) {
                         if(response != 1) { alert('could not change time period!') }
                         jQuery('.wagi-refresh-data').trigger('click');
                     });
                 });

                 // click the refresh button
                 jQuery('.wagi-refresh-data').on('click', function(e) {
                     var self = this;

                     jQuery(self).val('Refreshing Data ...').attr('disabled', 'disabled');

                     // send the request to server, no cache
                     jQuery.ajax({
                         url: ajaxurl,
                         type: 'POST',
                         data: {
                             'action': 'all_actions',
                             'wagi_action': 'refresh_data'
                         },
                         success: function(json) {

                             //  expected response is json
                             // json object: {[INT(post_id), STR(post link), INT(visits), STR(post_title)], ...
                             jQuery('.most-popular-posts-list').html('');

                             // build table with coming data
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