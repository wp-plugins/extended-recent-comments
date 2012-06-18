<?php
/*
Plugin Name: Extended Recent Comments
Description: Add a recent comments widget that shows Gravatars.
Author: Louy Alakkad
Version: 1.2
Author URI: http://louyblog.wordpress.com/
Plugin URL: http://l0uy.wordpress.com/tag/erc/
Text Domain: erc-widget
Domain Path: /languages
*/
/**
 * init ERC by registering our widget.
 */
function erc_init() {
	register_widget('Extended_Recent_Comments_Widget');
}
add_action( 'widgets_init', 'erc_init' );

// load translations
load_plugin_textdomain( 'erc-widget', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

/**
 * Recent_Comments widget class
 *
 * @since 2.8.0
 */
class Extended_Recent_Comments_Widget extends WP_Widget {

	function Extended_Recent_Comments_Widget() {
		$widget_ops = array('classname' => 'widget_erc', 'description' => __( 'The most recent comments, with Gravatars.' , 'erc-widget') );
		$this->WP_Widget('extended-recent-comments', __('Extended Recent Comments', 'erc-widget'), $widget_ops);
		$this->alt_option_name = 'widget_erc';

		if ( is_active_widget(false, false, $this->id_base) )
			add_action( 'wp_head', array(&$this, 'widget_style') );

		add_action( 'comment_post', array(&$this, 'flush_widget_cache') );
		add_action( 'transition_comment_status', array(&$this, 'flush_widget_cache') );
	}

	function widget_style() { ?>
	<style type="text/css">#erc{padding:0;margin:0;list-style:none !important;} #erc img{padding:0;margin:3px;float:<?php echo is_rtl() ? 'right' : 'left'; ?>;}</style>
<?php
	}

	function flush_widget_cache() {
		wp_cache_delete('widget_erc', 'widget');
	}

	function widget( $args, $instance ) {
		global $comments, $comment;

		$cache = wp_cache_get('widget_erc', 'widget');

		if ( ! is_array( $cache ) )
			$cache = array();

		if ( isset( $cache[$args['widget_id']] ) ) {
			echo $cache[$args['widget_id']];
			return;
		}

 		extract($args, EXTR_SKIP);
 		$output = '';
 		$title = apply_filters('widget_title', empty($instance['title']) ? __('Recent Comments', 'erc-widget') : $instance['title']);

		if ( ! $number = (int) $instance['number'] )
 			$number = 5;
 		else if ( $number < 1 )
 			$number = 1;

		$size = $instance['size'];

		$comments = get_comments( array( 'number' => $number, 'status' => 'approve' ) );
		$output .= $before_widget;
		if ( $title )
			$output .= $before_title . $title . $after_title;

		$output .= '<ul id="erc">';
		if ( $comments ) {
			foreach ( (array) $comments as $comment) {
				$output .=  '<li class="erc-comment">';
				$output .=  get_avatar(get_comment_author_email($comment->comment_ID), $size) . ' ';
				$output .=  /* translators: extended comments widget: 1: comment author, 2: post link */ sprintf(__('%1$s on %2$s', 'erc-widget'), get_comment_author_link(), '<a href="' . esc_url( get_comment_link($comment->comment_ID) ) . '">' . get_the_title($comment->comment_post_ID) . '</a>');
				$output .=  '<br style="clear:both;height:0;margin:0;padding:0;" /></li>';
			}
 		}
		$output .= '</ul>';
		$output .= $after_widget;

		echo $output;
		$cache[$args['widget_id']] = $output;
		wp_cache_set('widget_erc', $cache, 'widget');
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['number'] = (int) $new_instance['number'];
		$instance['size'] = ( $new_instance['size'] < 20 ) ? 20 : (int) $new_instance['size'];
		
		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset($alloptions['widget_erc']) )
			delete_option('widget_erc');

		return $instance;
	}

	function form( $instance ) {
		$title = isset($instance['title']) ? esc_attr($instance['title']) : '';
		$number = isset($instance['number']) ? absint($instance['number']) : 5;
		$size = isset($instance['size']) ? absint($instance['size']) : 40;
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'erc-widget'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of comments to show:', 'erc-widget'); ?></label>
		<input id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>
		
		<p><label for="<?php echo $this->get_field_id('size'); ?>"><?php _e('Avatar size:', 'erc-widget'); ?></label>
		<input id="<?php echo $this->get_field_id('size'); ?>" name="<?php echo $this->get_field_name('size'); ?>" type="text" value="<?php echo $size; ?>" size="3" /></p>
<?php
	}
}
