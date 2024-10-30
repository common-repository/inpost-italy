<?php

namespace InspireLabs\InpostItaly;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CheckoutSchema;

/**
 * EasyPack_Italy_WooBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'EasyPack_Italy_WooBlocks' ) ) :

	class EasyPack_Italy_WooBlocks implements IntegrationInterface {


		/**
		 * The name of the integration.
		 *
		 * @return string
		 */
		public function get_name() {
			return 'inpost-italy';
		}

		/**
		 * When called invokes any initialization/setup for the integration.
		 */
		public function initialize() {

			$plugin_data = new EasyPack_Italy();
			$script_url  = $plugin_data->getPluginJs() . 'blocks/inpost-italy-block.js';

			$dep = array(
				'dependencies' => array( 'wc-settings', 'wp-data', 'wp-blocks', 'wp-components', 'wp-element', 'wp-i18n', 'wp-primitives' ),
				'version'      => INPOST_ITALY_PLUGIN_VERSION,
			);

			$script_asset = $dep;

			wp_register_script(
				'inpost-italy-wc-blocks-integration',
				$script_url,
				$script_asset['dependencies'],
				$script_asset['version'],
				true
			);
		}

		/**
		 * Returns an array of script handles to enqueue in the frontend context.
		 *
		 * @return string[]
		 */
		public function get_script_handles() {
			return array( 'inpost-italy-wc-blocks-integration' );
		}

		/**
		 * Returns an array of script handles to enqueue in the editor context.
		 *
		 * @return string[]
		 */
		public function get_editor_script_handles() {
			return array( 'inpost-italy-wc-blocks-integration' );
		}

		/**
		 * An array of key, value pairs of data made available to the block on the client side.
		 *
		 * @return array
		 */
		public function get_script_data() {
			return array(
				'expensive_data_calculation' => '',
			);
		}

		/**
		 * Get the file modified time as a cache buster if we're in dev mode.
		 *
		 * @param string $file Local path to the file.
		 * @return string The cache buster value to use for the given file.
		 */
		protected function get_file_version( $file ) {
			if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
				return filemtime( $file );
			}

			return INPOST_ITALY_PLUGIN_VERSION;
		}
	}


endif;
