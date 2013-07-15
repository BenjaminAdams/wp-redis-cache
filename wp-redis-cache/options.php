<?php
/*
Plugin Name: Wp Redis Cache
Plugin URI: https://github.com/BenjaminAdams/wp-redis-cache
Description: Cache Wordpress with redis
Version: 1.0
Author: Benjamin Adams
Author URI: http://dudelol.com
/*  Copyright 2013  Benjamin Adams  (email : ben@dudelol.com)

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
    add_options_page('Wp Redis', 'Wp Redis', '8', 'functions', 'edit_redis_options');
}

function edit_redis_options() {
    ?>
    <div class='wrap'>
    <h2>Wp-Redis Options</h2>
    <form method="post" action="options.php">
    <?php wp_nonce_field('update-options') ?>

    <p><strong>Seconds of Caching:</strong><br />
	How many seconds would you like to cache?  *Recommended 12 hours or 43200 seconds <br />
    <input type="text" name="wp-redis-cache-seconds" size="45" value="<?php echo get_option('wp-redis-cache-seconds'); ?>" /></p>

    <p><strong>Secret String:</strong><br />
	To refresh the cache of your post manually you will need to set a secret string so do can refresh manually like so:
	<br />	http://example.com/post_name?refresh=secret_string.  <br />
	<br />Important! You must change this in the index-wp-redis.php file<br />
    <input type="text" name="wp-redis-secret" size="45" value="<?php echo get_option('wp-redis-secret'); ?>" /></p>

   
    <p><input type="submit" name="Submit" value="Update Options" /></p>

    <input type="hidden" name="action" value="update" />
    <input type="hidden" name="page_options" value="wp-redis-cache-seconds,wp-redis-secret" />

    </form>
    </div>
    <?php
}

include('cache.php');