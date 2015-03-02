<?php

add_action('transition_post_status', 'refresh_wp_redis_cache',10,3);
add_action('wp_ajax_clear_wp_redis_cache', 'clear_wp_redis_cache');
add_action( 'admin_footer', 'clear_wp_redis_cache_javascript' );

function get_redis_server() {
    if (class_exists('Redis')) {
        $redis_server = get_option('wp-redis-cache-server');
        $redis = new Redis();
        $redis->connect($redis_server);
    } else {
        include_once("predis5.2.php");  //we need this to use Redis inside of PHP
		$redis = new Predis_Client();
    }
    return $redis;
}


//clears the cache after you update a post
function refresh_wp_redis_cache( $new, $old, $post )
{
    $redis = get_redis_server();
    foreach($redis->keys($_SERVER['HTTP_HOST'].'_*') as $key) {
            $redis->del($key);
    }

}

// clears the whole cache
function clear_wp_redis_cache()
{
    $redis = get_redis_server();
    $i = 0;
    foreach($redis->keys($_SERVER['HTTP_HOST'].'_*') as $key) {
        $redis->del($key);
        $i++;
    }
	
	echo $i . " of " . $wp_query  -> found_posts . " posts was cleared in cache"; 
	die();
}

function clear_wp_redis_cache_javascript() {
?>
<script type="text/javascript" >
jQuery(document).ready(function($) {
	jQuery('#WPRedisClearCache').click(function(){
		var data = {
			action: 'clear_wp_redis_cache'
		};
		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		$.post(ajaxurl, data, function(response) {
			alert(response);
		});
	});
});
</script>
<?php 
}
?>
