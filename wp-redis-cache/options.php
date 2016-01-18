<?php
/*
Plugin Name: WP Redis Cache
Description: Cache Wordpress using Redis, the fastest way to date to cache Wordpress.
Plugin URI: https://github.com/BenjaminAdams/wp-redis-cache
Version: 1.0
Author: Benjamin Adams
Author URI: http://dudelol.com

    Copyright 2013  Benjamin Adams  (email : ben@dudelol.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
	
*/


//Custom Theme Settings
add_action('admin_menu', 'add_redis_interface');

function add_redis_interface() {
    add_options_page('Wp Redis Cache', 'Wp Redis Cache', 'manage_options', 'functions', 'edit_redis_options');
}

function edit_redis_options() {
    ?>
    <div class='wrap'>
    <h2>Wp-Redis Options</h2>
    <form method="post" action="options.php">
    <?php wp_nonce_field('update-options') ?>
	
	<p>This plugin does not work out of the box and requires additional steps.<br />
	Please follow these install instructions: <a target='_blank' href='https://github.com/BenjaminAdams/wp-redis-cache'>https://github.com/BenjaminAdams/wp-redis-cache</a></p>
	
	<p>If you do not have Redis installed on your machine this will NOT work! </p>

    <p><strong>Seconds of Caching:</strong><br />
	How many seconds would you like to cache?  *Recommended 12 hours or 43200 seconds <br />
    <input type="text" name="wp-redis-cache-seconds" size="45" value="<?php echo get_option('wp-redis-cache-seconds'); ?>" /></p>
 
    <p><strong>Cache unlimited:</strong><br />
		If this options set the cache never expire. This option overrides the setting "Seconds of Caching"<br />
    <input type="checkbox" name="wp-redis-cache-unlimited" size="45" value="true" <?php checked('true', get_option('wp-redis-cache-unlimited')); ?>/></p>
	  
    <p><input type="submit" name="Submit" value="Update Options" /></p>
	<p><input type="button" id="WPRedisClearCache" name="WPRedisClearCache" value="Clear Cache"></p>
    <input type="hidden" name="action" value="update" />
    <input type="hidden" name="page_options" value="wp-redis-cache-seconds,wp-redis-cache-unlimited" />

    </form>
    </div>
    <?php
}

include('cache.php');
