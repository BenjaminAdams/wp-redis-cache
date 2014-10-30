<?php

// Start the timer so we can track the page load time
$start = microtime();

function getMicroTime($time) {
    list($usec, $sec) = explode(" ", $time);
    return ((float) $usec + (float) $sec);
}

function refreshHasSecret($secret) {
    return isset($_GET['refresh']) && $_GET['refresh'] == $secret;
}

function requestHasSecret($secret) {
    return strpos($_SERVER['REQUEST_URI'],"refresh=${secret}")!==false;
}

function isRemotePageLoad($currentUrl, $websiteIp) {
    return (isset($_SERVER['HTTP_REFERER'])
            && $_SERVER['HTTP_REFERER']== $currentUrl
            && $_SERVER['REQUEST_URI'] != '/' 
            && $_SERVER['REMOTE_ADDR'] != $websiteIp);
}

function handleCDNRemoteAddressing() {
    // so we don't confuse the cloudflare server 
    if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
}

function getCleanUrl($secret) {
    $replaceKeys = array("?refresh=${secret}","&refresh=${secret}");
    $url = "http://${_SERVER['HTTP_HOST']}${_SERVER['REQUEST_URI']}";
    $current_url = str_replace($replaceKeys, '', $url);
    return $current_url;
}

$debug          = true;
$cache          = true;
$websiteIp      = '127.0.0.1';
// if you use sockets, set this to true and use $redis_server for socket path
$sockets        = false;
// in case of sockets something like /home/user/.redis/sock
$redis_server   = '127.0.0.1';
$secret_string  = 'changeme';
$current_url    = getCleanUrl($secret_string);
// used to prefix ssl cached pages
$isSSL = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? "ssl_" : "";
$redis_key      = $isSSL.md5($current_url);

handleCDNRemoteAddressing();

if(!defined('WP_USE_THEMES')) {
    define('WP_USE_THEMES', true);
}

try {
    // check if PECL Extension is available
    if (class_exists('Redis')) {
        if ($debug) {
            echo "<!-- Redis PECL module found -->\n";
        }
        $redis = new Redis();

        // Sockets can be used as well. Documentation @ https://github.com/nicolasff/phpredis/#connection
        $redis->connect($redis_server);
        
    } else { // Fallback to predis5.2.php

        if ($debug) {
            echo "<!-- using predis as a backup -->\n";
        }
        include_once("wp-content/plugins/wp-redis-cache/predis5.2.php"); //we need this to use Redis inside of PHP
    
        if ($sockets) {
            $redis = new Predis_Client(array(
                                        'scheme' => 'unix',
                                        'path'   => $redis_server
                                        ));
        } else {
            $redis = new Predis_Client();
        }
    }
    
    //Either manual refresh cache by adding ?refresh=secret_string after the URL or somebody posting a comment
    if (refreshHasSecret($secret_string) || requestHasSecret($secret_string) || isRemotePageLoad($current_url, $websiteIp)) {
        if ($debug) {
            echo "<!-- manual refresh was required -->\n";
        }
        $redis->del($redis_key);
        $redis->del("ssl_".$redis_key);
        require('./wp-blog-header.php');
        
        $unlimited = get_option('wp-redis-cache-debug',false);
        $seconds_cache_redis = get_option('wp-redis-cache-seconds',43200);
    // This page is cached, lets display it
    } else if ($redis->exists($redis_key)) {
        if ($debug) {
            echo "<!-- serving page from cache: key: $redis_key -->\n";
        }
        $cache  = true;
        $html_of_page = $redis->get($redis_key);
        echo $html_of_page;

     // If the cache does not exist lets display the user the normal page without cache, and then fetch a new cache page
    } else if ($_SERVER['REMOTE_ADDR'] != $websiteIp && strstr($current_url, 'preview=true') == false) {
        if ($debug) {
            echo "<!-- displaying page without cache -->\n";
        }
        
        $isPOST = ($_SERVER['REQUEST_METHOD'] === 'POST') ? 1 : 0;
        
        $loggedIn = preg_match("/wordpress_logged_in/", var_export($_COOKIE, true));
        if (!$isPOST && !$loggedIn) {
            ob_start();
            require('./wp-blog-header.php');
            $html_of_page = ob_get_contents();
            ob_end_clean();
            echo $html_of_page;

            if (!is_numeric($seconds_cache_redis)) {
                $seconds_cache_redis = 43200;
            }

            // When a page displays after an "HTTP 404: Not Found" error occurs, do not cache
            // When the search was used, do not cache
            if ((!is_404()) and (!is_search()))  {
                if ($unlimited) {
                    $redis->set($redis_key, $html_of_page);
                } else {
                    $redis->setex($redis_key, $seconds_cache_redis, $html_of_page);
                }

            }
        } else { //either the user is logged in, or is posting a comment, show them uncached
            require('./wp-blog-header.php');
        }
        
    } else if ($_SERVER['REMOTE_ADDR'] != $websiteIp && strstr($current_url, 'preview=true') == true) {
        require('./wp-blog-header.php');
    }
     // else {   // This is what your server should get if no cache exists  //deprecated, as the ob_start() is cleaner
        // require('./wp-blog-header.php');
    // }
} catch (Exception $e) {
    //require('./wp-blog-header.php');
    echo "Something went wrong: " . $e->getMessage();
}

$end  = microtime();
$time = (@getMicroTime($end) - @getMicroTime($start));
if ($debug) {
    echo "<!-- Cache system by Benjamin Adams. Page generated in " . round($time, 5) . " seconds. -->\n";
    echo "<!-- Site was cached  = " . $cache . " -->\n";
    if (isset($seconds_cache_redis)) {
        echo "<!-- wp-redis-cache-seconds  = " . $seconds_cache_redis . " -->\n";
    }
    echo "<!-- wp-redis-cache-secret  = " . $secret_string . "-->\n";
    echo "<!-- wp-redis-cache-ip  = " . $websiteIp . "-->\n";
    if (isset($unlimited)) {
        echo "<!-- wp-redis-cache-unlimited = " . $unlimited . "-->\n";
    }
    echo "<!-- wp-redis-cache-debug  = " . $debug . "-->\n";
}