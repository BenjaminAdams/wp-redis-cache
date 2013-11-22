<?php

// Start the timer so we can track the page load time
$start = microtime();

function getMicroTime($t)
{
    list($usec, $sec) = explode(" ", $t);
    return ((float) $usec + (float) $sec);
}

// 12 hours by default, you can change in this in wp-admin options page
$seconds_cache_redis = 60 * 60 * 12;

//You must set this to the IP of your website
$ip_of_your_website  = '127.0.0.1'; 

/*This is if you want to manually refresh the cache
ex: http://example.com/sample-post?refresh=changeme    */
$secret_string       = "changeme";

// so we don't confuse the cloudflare server 
if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
    $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
}

define('WP_USE_THEMES', true);
$current_url = str_replace(array("?refresh=${secret_string}","&refresh=${secret_string}"), '', "http://${_SERVER['HTTP_HOST']}${_SERVER['REQUEST_URI']}"); //clean up the URL
$redis_key = md5($current_url);

try {
    // check if PECL Extension is available
    if (class_exists('Redis')) {
        $redis = new Redis();
        
        // Sockets can be used as well. Documentation @ https://github.com/nicolasff/phpredis/#connection
        $redis->connect('127.0.0.1');
        
    } else // Fallback to predis5.2.php
    {
        include_once("wp-content/plugins/wp-redis-cache/predis5.2.php"); //we need this to use Redis inside of PHP
        $redis = new Predis_Client();
    }
    
    //Either manual refresh cache by adding ?refresh=secret_string after the URL or somebody posting a comment
    if (isset($_GET['refresh']) || $_GET['refresh'] == $secret_string || strpos($_SERVER['REQUEST_URI'],"refresh=${secret_string}")!==false || ($_SERVER['HTTP_REFERER'] == $current_url && $_SERVER['REQUEST_URI'] != '/' && $_SERVER['REMOTE_ADDR'] != $ip_of_your_website)) {
        
        $redis->del($redis_key);
        require('./wp-blog-header.php');
        
    // This page is cached, lets display it
    } else if ($redis->exists($redis_key)) {
        $html_of_page = $redis->get($redis_key);
        echo $html_of_page;
        
     // If the cache does not exist lets display the user the normal page without cache, and then fetch a new cache page
    } else if ($_SERVER['REMOTE_ADDR'] != $ip_of_your_website && strstr($current_url, 'preview=true') == false) {
        
        $isPOST = ($_SERVER['REQUEST_METHOD'] === 'POST') ? 1 : 0;
        
        $loggedIn = preg_match("/wordpress_logged_in/", var_export($_COOKIE, true));
        if ($isPost == 0 && $loggedIn == 0) {
            ob_start();
            require('./wp-blog-header.php');
            $html_of_page = ob_get_contents();
            ob_end_clean();
            echo $html_of_page;
            
			//if the user has the seconds defined in the admin section use it
            $usr_seconds = get_option('wp-redis-cache-seconds');
            if (isset($usr_seconds) && is_numeric($usr_seconds)) {
                $seconds_cache_redis = $usr_seconds;
            }
			
			// When a page displays after an "HTTP 404: Not Found" error occurs, do not cache
            if (!is_404()) {
                $redis->setex($redis_key, $seconds_cache_redis, $html_of_page);
            }
        } else //either the user is logged in, or is posting a comment, show them uncached
            {
            require('./wp-blog-header.php');
        }
        
    } else if ($_SERVER['REMOTE_ADDR'] != $ip_of_your_website && strstr($current_url, 'preview=true') == true) {
        require('./wp-blog-header.php');
    }
     // else {   // This is what your server should get if no cache exists  //depricated, as the ob_start() is cleaner
        // require('./wp-blog-header.php');
    // }
} catch (Exception $e) {
    require('./wp-blog-header.php');
}

if ($_SERVER['REMOTE_ADDR'] != $ip_of_your_website) {
    // How long did it take to load the page? (CloudFlare may strip out comments)
    $end  = microtime();
    $time = (@getMicroTime($end) - @getMicroTime($start));
    echo "<!-- Cache system by Benjamin Adams. Page generated in " . round($time, 5) . " seconds. -->";
}
