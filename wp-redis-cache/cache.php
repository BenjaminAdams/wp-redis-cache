<?php

add_action('transition_post_status', 'refresh_wp_redis_cache',10,3);

//clears the cache after you update a post
function refresh_wp_redis_cache( $new, $old, $post )
{

if($new == "publish")
{
$permalink = get_permalink( $post->ID );

include("predis5.2.php");  //we need this to use Redis inside of PHP
$redis = new Predis_Client();

$redis_key = md5($permalink);
$redis->del($redis_key);

//refresh the front page
$frontPage = get_home_url() . "/";
$redis_key = md5($frontPage);
$redis->del($redis_key);
}
}