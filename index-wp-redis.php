<?php

// Start the timer so we can track the page load time
$start = microtime();

function getMicroTime($t)
{
    list($usec, $sec) = explode(" ", $t);
    return ((float) $usec + (float) $sec);
}


$debug               =  true;
$cache				 =  false;
$ip_of_your_website  =  '127.0.0.1';
$secret_string       =  'changeme';




// so we don't confuse the cloudflare server 
if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
    $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
}
 

if(!defined('WP_USE_THEMES')) {
    define('WP_USE_THEMES', true);
}

$current_url = str_replace(array("?refresh=${secret_string}","&refresh=${secret_string}"), '', "http://${_SERVER['HTTP_HOST']}${_SERVER['REQUEST_URI']}"); //clean up the URL
$redis_key = md5($current_url);

// check if the user was  logged in to wp
$cookie = var_export($_COOKIE, true);
$loggedin = preg_match("/wordpress_logged_in/", $cookie);

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
    
	// if the user was logged in, don't show a cached site    
	} else if ($loggedin) {
		
		require('./wp-blog-header.php');
		
    // This page is cached, lets display it
    } else if ($redis->exists($redis_key)) {
		$cache  = true;
        $html_of_page = $redis->get($redis_key);
		echo $html_of_page;
        
     // If the cache does not exist lets display the user the normal page without cache, and then fetch a new cache page
    } else if ($_SERVER['REMOTE_ADDR'] != $ip_of_your_website && strstr($current_url, 'preview=true') == false) {
        
        $isPOST = ($_SERVER['REQUEST_METHOD'] === 'POST') ? 1 : 0;
        
        if ($isPost == 0) {
            ob_start();
            require('./wp-blog-header.php');
            $html_of_page = ob_get_contents();
            ob_end_clean();
			echo $html_of_page;
			
			$unlimited			 =  get_option('wp-redis-cache-debug',false);
			$seconds_cache_redis =  get_option('wp-redis-cache-seconds',43200);
			if (!is_numeric($seconds_cache_redis)) {
				$seconds_cache_redis = 43200;
			}
			
			
			// When a page displays after an "HTTP 404: Not Found" error occurs, do not cache
			// When the search was used, do not cache
            if ((!is_404()) and (!is_search()))  {
                if ($unlimited) {
                	$redis->set($redis_key, $html_of_page);
                }
				else
				{
					$redis->setex($redis_key, $seconds_cache_redis, $html_of_page);
				}

            }
		//either the user is logged in, or is posting a comment, show them uncached
        } else {
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


$end  = microtime();
$time = (@getMicroTime($end) - @getMicroTime($start));
if ($debug) {
	echo "<!-- Cache system by Benjamin Adams. Page generated in " . round($time, 5) . " seconds. -->";
	echo "<!-- Site was cached  = " . $cache . " -->";
	echo "<!-- wp-redis-cache-seconds  = " . $seconds_cache_redis . " -->";
	echo "<!-- wp-redis-cache-secret  = " . $secret_string . "-->";
	echo "<!-- wp-redis-cache-ip  = " . $ip_of_your_website . "-->";
	echo "<!-- wp-redis-cache-unlimited = " . $unlimited . "-->";
	echo "<!-- wp-redis-cache-debug  = " . $debug . "-->";
}

