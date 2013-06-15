<?php

/**
 * Controller of front-end widget installation
 * just simple as wordpress example :)
 *
 * @copyright  2013 Ahmed Janim <ahmed@janim.me>
 * @license    http://www.gnu.org/licenses/gpl.txt GPL2
 * @version    Release: @1.0
 * @link       https://github.com/Janim007/wagi
 * @since      Class available since Release 1.0
 */
require_once dirname( __FILE__ ) . '/loader.php';
class wagi_frond_widget extends WP_Widget {

  /**
   *  override parent constructor
   */
  public function __construct() {
    parent::__construct(
      'wagi_frond_widget',
      'Wagi widget',
      array( 'description' => __( 'Wagi most popular posts from google analytics', 'text_domain' ) )
    );
  }
  /**
   * override parent widget
   *
   * @param unknown $args
   * @param unknown $instance
   */
  public function widget( $args, $instance ) {

    extract( $args );
    $title = apply_filters( 'widget_title', $instance[ 'title' ] );

    $index = new wagi_plugin();

    $results = json_decode( $index->get_data() );

    if ( count( $results ) > 0 ) {
      echo $before_widget;
      if ( !empty( $title ) )
        echo $before_title . $title . $after_title;
      echo '<ul>';
      foreach ( $results as $result ) {
        echo sprintf( '<li><a href="%1$s">%2$s</a></li>', $result[1], $result[3] );

      } // end foreach
      echo '</ul>';
    }
    echo $after_widget;

  }


  /**
   *
   *
   * @param unknown $instance
   */
  public function form( $instance ) {
    if ( isset( $instance[ 'title' ] ) ) {
      $title = $instance[ 'title' ];
    }
    else {
      $title = __( 'Most Popular', 'text_domain' );
    }

?>
         <p>
             <label for="<?php echo $this->get_field_name( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
             <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
         </p>
         <?php

  }


}


/**
 * hook the widget
 */
add_action( 'widgets_init', function() {
    register_widget( 'wagi_frond_widget' );
  } );

?>
