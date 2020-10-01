<?php

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
Plugin Name: Gravity Forms Trello Add-On
Plugin URI: https://gravityforms.com
Description: Integrates Gravity Forms with Trello
Version: 1.4
Author: Gravity Forms
Author URI: https://gravityforms.com
License: GPL-2.0+
Text Domain: gravityformstrello
Domain Path: /languages

------------------------------------------------------------------------
Copyright 2009-2020 Rocketgenius, Inc.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

define( 'GF_TRELLO_VERSION', '1.4' );

// If Gravity Forms is loaded, bootstrap the Trello Add-On.
add_action( 'gform_loaded', array( 'GF_Trello_Bootstrap', 'load' ), 5 );

/**
 * Class GF_Trello_Bootstrap
 *
 * Handles the loading of the Trello Add-On and registers with the Add-On Framework.
 */
class GF_Trello_Bootstrap {

	/**
	 * If the Feed Add-On Framework exists, Trello Add-On is loaded.
	 *
	 * @access public
	 * @static
	 */
	public static function load() {

		if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
			return;
		}

		require_once( 'class-gf-trello.php' );

		GFAddOn::register( 'GFTrello' );

	}

}

/**
 * Returns an instance of the GFTrello class
 *
 * @see    GFTrello::get_instance()
 *
 * @return object GFTrello
 */
function gf_trello() {
	return GFTrello::get_instance();
}
