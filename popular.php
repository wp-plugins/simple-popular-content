<?php
/*
Plugin Name: Simple Popular Content
Description: A simple sidebar widget for your most viewed content.
Plugin URI: http://www.simpleintranet.org
Description: Provides a simple sidebar widget for your most viewed content in your posts or pages.  You can limit the number of posts or pages to show, and exclude specific posts or pages via a comma separate list of their IDs.  You can also reset the statistics at any time.
Version: 1.0
Author: Simple Intranet
Author URI: http://www.simpleintranet.org
License: GPL2
*/

remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);

global $exclude;
$exclude=array();

function getPostViews($postID){
	$count_key = 'post_views_count';
    $count = get_post_meta($postID, $count_key, true);
		
		    if($count==''){
        delete_post_meta($postID, $count_key);
        add_post_meta($postID, $count_key, '0');
        return "0 Views";
    }	
	return $count.' Views';	
}
function setPostViews($postID) {
    $count_key = 'post_views_count';
    $count = get_post_meta($postID, $count_key, true);
    if($count==''){
        $count = 0;
        delete_post_meta($postID, $count_key);
        add_post_meta($postID, $count_key, '0');
    }else{
        $count++;
        update_post_meta($postID, $count_key, $count);
    }
}
// Add view counter to each page

add_filter ('the_content', 'view_counter');
function view_counter($content) {
  if(is_single() && !is_page()) {
      setPostViews(get_the_ID());
  }
   return $content;
}

// Add it to a column in WP-Admin
add_filter('manage_posts_columns', 'posts_column_views');
add_action('manage_posts_custom_column', 'posts_custom_column_views',5,2);
function posts_column_views($defaults){
    $defaults['post_views'] = __('Views');
    return $defaults;
}
function posts_custom_column_views($column_name, $id){
	if($column_name === 'post_views'){
        echo getPostViews(get_the_ID());
    }
}

// add widget to sidebar

class PopularPostsWidget extends WP_Widget
{
  function PopularPostsWidget()
  {
    $widget_ops = array('classname' => 'PopularPostsWidget', 'description' => 'Displays a list of the most viewed content.' );
    $this->WP_Widget('PopularPostsWidget', 'Most Popular Content', $widget_ops);
	  }
 
  function form($instance)
  {
	$exclude=array();
    $instance = wp_parse_args( (array) $instance, array( 'title' => 'Most Popular' ) );
    $title = $instance['title'];
	$instance = wp_parse_args( (array) $instance, array( 'max' => '10' ) );
    $max = $instance['max'];
	$instance = wp_parse_args( (array) $instance, array( 'exclude' => '' ) );
    $exclude = $instance['exclude'];
	$instance = wp_parse_args( (array) $instance, array( 'reset' => 'No' ) );
    $reset = $instance['reset'];
	
?>
  <p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
  <p><label for="<?php echo $this->get_field_id('max'); ?>">Number of posts/pages to show: <input id="<?php echo $this->get_field_id('max'); ?>" name="<?php echo $this->get_field_name('max'); ?>" type="text" value="<?php echo esc_attr($max); ?>" /></label></p>
   <p><label for="<?php echo $this->get_field_id('exclude'); ?>">Exclude IDs of posts/pages (separate by commas): <input id="<?php echo $this->get_field_id('exclude'); ?>" name="<?php echo $this->get_field_name('exclude'); ?>" type="text" value="<?php echo esc_attr($exclude); ?>" /></label></p>
    <p><label for="<?php echo $this->get_field_id('reset'); ?>">Reset statistics? <input name="<?php echo $this->get_field_name('reset'); ?>" type="checkbox" id="<?php echo $this->get_field_id('reset'); ?>" value="Yes"  <?php if ($reset=="Yes"){
		echo "checked=\"checked\"";
	} ?>>
      Yes</label>
    </p>
<?php
  }
 
  function update($new_instance, $old_instance)
  {
    $instance = $old_instance;
    $instance['title'] = $new_instance['title'];
	$instance['max'] = $new_instance['max'];
	$instance['exclude'] = $new_instance['exclude'];
	$instance['reset'] = $new_instance['reset'];
    return $instance;
  }
 
  function widget($args, $instance)
  {
    extract($args, EXTR_SKIP);
 $exclude=array();
    echo $before_widget;
    $title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
  $max = empty($instance['max']) ? ' ' : apply_filters('widget_title', $instance['max']);
  $exclude = empty($instance['exclude']) ? ' ' : apply_filters('widget_title', $instance['exclude']);
   $reset = empty($instance['reset']) ? ' ' : apply_filters('widget_title', $instance['reset']);
     if (!empty($title))
      echo $before_title . $title . $after_title;	   
 
    // WIDGET CODE GOES HERE		
?>
<ul>
<?php setPostViews(get_the_ID());
$ex=explode(",",$exclude);
query_posts(array('meta_key'=> 'post_views_count','posts_per_page'=>$max,'orderby'=>'meta_value_num','order'=>'DESC','post_type' => array('post', 'page'), 'post__not_in' =>$ex)); ?>
<?php if (have_posts()) : while (have_posts()) : the_post(); 
if ($reset=="Yes") { 
$id=get_the_ID();
update_post_meta($id, 'post_views_count','0');
}
else {
$reset="No";
}

?>
<li><a href="<? the_permalink(); ?>"><?php the_title(); ?></a> (<?php echo getPostViews(get_the_ID())?>)</li>
<?php endwhile; endif; wp_reset_query(); ?>
</ul>
<?php 

// END WIDGET CODE

    echo $after_widget;  
}
}
add_action( 'widgets_init', create_function('', 'return register_widget("PopularPostsWidget");') );

?>