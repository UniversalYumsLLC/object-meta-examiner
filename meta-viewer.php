<?php
/**
 * Plugin Name:       Meta Viewer
 * Plugin URI:        https://www.universalyums.com
 * Description:       Displays the meta fields used for WooCommerce orders and subscriptions.
 * Version:           0.1.0
 * Author:            Universal Yums
 * Author URI:        https://www.universalyums.com
 * Text Domain:       meta-viewer
 * Domain Path:       /languages
 * License:           GNU General Public License v3.0
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package MetaViewer
 */

defined( 'ABSPATH' ) || exit;

// Autoload Dependencies
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload_packages.php';

use MetaViewer\View;

/**
 * Main Plugin Class.
 */
class MetaViewer {
	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Gets the main instance of the plugin.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - Initializes the plugin.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks and dependencies.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		load_plugin_textdomain( 'object-meta-examiner', false, plugin_basename( __DIR__ ) . '/languages' );
		new View();
	}
}

/**
 * Initialize the plugin on `plugins_loaded` action.
 *
 * @return void
 */
function object_meta_examiner_init(): void {
	MetaViewer::instance();
}
add_action( 'plugins_loaded', 'object_meta_examiner_init' );
