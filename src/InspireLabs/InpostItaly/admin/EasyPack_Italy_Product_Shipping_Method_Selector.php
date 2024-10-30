<?php

namespace InspireLabs\InpostItaly\admin;

use InspireLabs\InpostItaly\EasyPack_Italy;
use WC_Shipping;
use WC_Shipping_Method;

class EasyPack_Italy_Product_Shipping_Method_Selector {

	const NO_METHODS_SELECTED = 0;

	const META_ID = EasyPack_Italy::ATTRIBUTE_PREFIX . '_shipping_methods_allowed';
    const META_ID_SIZE = EasyPack_Italy::ATTRIBUTE_PREFIX . '_parcel_dimensions';

	public static $inpost_methods;

	public function handle_product_edit_hooks() {
		add_filter( 'woocommerce_product_data_tabs', [ $this, 'filter_woocommerce_product_data_tabs' ], 10, 1 );
		add_action( 'woocommerce_product_data_panels', [ $this, 'action_woocommerce_product_data_panels' ], 10, 0 );
		add_action( 'woocommerce_admin_process_product_object', [ $this, 'action_woocommerce_admin_process_product_object' ], 10, 1 );
        add_action( 'woocommerce_product_options_shipping', [ $this, 'easypack_parcel_size_select' ], 10, 0 );

        add_action( 'woocommerce_product_bulk_edit_start', [ $this, 'products_bulk_edit_inpost_enabled' ], 9998 );
        add_action( 'woocommerce_product_bulk_edit_start', [ $this, 'products_bulk_edit_inpost_dimensions' ], 9999 );
        add_action( 'woocommerce_product_bulk_edit_save', [ $this, 'products_bulk_edit_inpost_save' ] );

        add_action( 'woocommerce_product_quick_edit_start', [ $this, 'products_bulk_quick_edit_inpost_enabled' ] );
        add_action( 'woocommerce_product_quick_edit_save', [ $this, 'products_bulk_edit_inpost_save' ] );

    }

	/**
	 * @return WC_Shipping_Method[]
	 */
	private function get_inpost_methods(): array {
		return self::$inpost_methods;
	}


	/**
	 * @param int $product_id
	 *
	 * @return array|null
	 */
	private function get_config_by_product_id( int $product_id ): ?array {
		$meta = get_post_meta( $product_id, self::META_ID, true );
        // if saved zero methods (all methods unchecked before save) - we have empty array
        // if product is new - return null to show all checked in is_checked function
		return is_array( $meta ) ? $meta : null;
	}

	/**
	 * @param int $product_id
	 *
	 * @return array
	 */
    private function get_inpost_methods_by_product_id( int $product_id  ): array {
        $return = [];

        $config_raw = $this->get_config_by_product_id( $product_id );

        $all_inpost_methods = $this->get_inpost_methods();

        if ( null === $config_raw ) { // by default for all products
            if ( !empty( $all_inpost_methods ) ) {
                foreach ( $all_inpost_methods as $method ) {
                    $return[] = $method->id;
                }
            }
        }

        if ( null !== $config_raw && is_array( $config_raw ) ) {
            return $config_raw;
        }

        return $return;
    }


	/**
	 * Add custom product setting tab.
	 */
	public function filter_woocommerce_product_data_tabs( $default_tabs ) {
		$default_tabs['custom_tab'] = [
			'label'    => esc_html__( 'InPost', 'inpost-italy' ),
			'target'   => 'wk_custom_tab_data',
			'priority' => 60,
			'class'    => [],
		];

		return $default_tabs;
	}

	/**
	 * @param array|null $config_by_product
	 * @param $method_id
	 *
	 * @return bool
	 */
	private function is_checked( ?array $config_by_product, $method_id ) {
        // first time product open for edit
	    if( null === $config_by_product ) {
            return true;
        }

		if ( is_array( $config_by_product ) && in_array( $method_id, $config_by_product ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Contents custom product setting tab.
	 */
	public function action_woocommerce_product_data_panels() {
		global $post;
		echo '<div id="wk_custom_tab_data" class="panel woocommerce_options_panel">';
		$config_by_product = $this->get_config_by_product_id( $post->ID );

		foreach ( $this->get_inpost_methods() as $method ) {
		    
			woocommerce_wp_checkbox( [
				'id'    => $this->get_post_key_from_method_id( $method->id ),
				'label' => $method->get_method_title(),
				'value' => $this->is_checked( $config_by_product, $method->id ) ? 'yes' : null,
			] );
		}

		echo '</div>';
	}

	/**
	 * @param string $method_id
	 *
	 * @return string
	 */
	private function get_post_key_from_method_id( string $method_id ): string {
		return '_' . EasyPack_Italy::ATTRIBUTE_PREFIX . '_shipping_method_id_' . $method_id;
	}


	/**
	 * Save the checkbox.
	 */
	public function action_woocommerce_admin_process_product_object( $product ) {
		$allowed_methods = [];
		foreach ( $this->get_inpost_methods() as $method ) {
			$post_key = $this->get_post_key_from_method_id( $method->id );
			if ( isset( $_POST[ $post_key ] ) && $_POST[ $post_key ] === 'yes' ) {
				$allowed_methods[] = $method->id;
			}
		}

        $product->update_meta_data( self::META_ID, $allowed_methods );

        if ( isset( $_POST[ 'easypack_parcel_dimensions' ] ) && ! empty( $_POST[ 'easypack_parcel_dimensions' ] ) ) {
            $product->update_meta_data( self::META_ID_SIZE, sanitize_text_field( $_POST[ 'easypack_parcel_dimensions' ] ) );
        }
        // clear shipping methods cache
        \WC_Cache_Helper::get_transient_version( 'shipping', true );

	}

    /**
     * Return allowed InPost shipping methods defined for products in cart
     *
     * @param array $contents_of_the_cart
     *
     * @return array
     */
    public function get_methods_allowed_by_cart( array $contents_of_the_cart ): array {

        $config_by_product = [];

        if( ! empty( $contents_of_the_cart ) ) {

            $physical_goods_ids = [];
            $total_weight = 0;
            foreach ( $contents_of_the_cart as $cart_item_key => $cart_item ) {

                // if variation in cart
                if( isset($cart_item['variation_id']) && ! empty($cart_item['variation_id']) ) {
                    $variant = wc_get_product( $cart_item['variation_id'] );
                    if( ! $variant->is_virtual() && ! $variant->is_downloadable() ) {
                        $physical_goods_ids[] = $cart_item['product_id'];
                        $total_weight += floatval( $variant->get_weight() ) * $cart_item['quantity'];
                    }

                } else {

                    $product = wc_get_product( $cart_item['product_id'] );
                    if ( ! $product->is_virtual() && ! $product->is_downloadable() ) {
                        $physical_goods_ids[] = $cart_item['product_id'];
                        $total_weight += floatval( $product->get_weight() ) * $cart_item['quantity'];
                    }
                }
            }

            $physical_goods_ids = array_unique( $physical_goods_ids );

            if( ! empty( $physical_goods_ids ) ) {
                foreach ( $physical_goods_ids as $id ) {
                    $config_by_product[$id] = $this->get_inpost_methods_by_product_id( $id );
                }

                if( count( $physical_goods_ids ) === 1 ) {
                    $config_by_product = $this->block_overweight( $total_weight, $config_by_product[$id] );
                    return  $config_by_product;
                }

                if( is_array( $config_by_product ) && count( $config_by_product ) > 1 ) {
                    $config_by_product = call_user_func_array( 'array_intersect', $config_by_product );
                    $config_by_product = $this->block_overweight( $total_weight, $config_by_product );
                }
            }
        }

        return $config_by_product;
    }


    /**
     * Add option with parcel dimensions on product edit page
     *
     */
    public function easypack_parcel_size_select() {
        global $post;

        $options = inpost_italy()->get_package_sizes_gabaryt();

        woocommerce_wp_select(
            array(
                'id'      => 'easypack_parcel_dimensions',
                'label'   => esc_html__( 'InPost parcel dimensions', 'inpost-italy' ),
                'options' => $options,
                'value'   => get_post_meta( $post->ID, self::META_ID_SIZE, true)
            )
        );

    }


    /**
     * Block weight over 25 kg for two methods: paczkomaty and courier_c2c
     *
     */
    private function block_overweight( $total_weight, $config_by_product ) {

        if( get_option( 'easypack_italy_over_weight' ) === 'yes') {
            if( $total_weight >= 25 ) {

                if( ! empty( $config_by_product) && is_array( $config_by_product ) ) {

                    if ( ($key = array_search('easypack_italy_parcel_machines', $config_by_product ) ) !== false ) {
                        unset( $config_by_product[$key] );
                    }
                    if ( ($key = array_search('easypack_italy_shipping_courier_c2c', $config_by_product ) ) !== false ) {
                        unset( $config_by_product[$key] );
                    }
                }
            }
        }

        return $config_by_product;
    }


    /**
     * Custom field "Is Inpost enabled" for Bulk Edit product action
     */
    function products_bulk_edit_inpost_enabled()
    {
        ?>
        <label>
            <span class="title"><?php esc_html_e( 'Is method Inpost enabled?', 'inpost-italy' ); ?></span>
            <span class="input-text-wrap">
				<select class="inpost_italy_enabled" name="_is_inpost_enabled">
					<?php
                    $options = array(
                        ''    => esc_html__( '— No change —', 'inpost-italy' ),
                        'yes' => esc_html__( 'Yes', 'inpost-italy' ),
                        'no'  => esc_html__( 'No', 'inpost-italy' ),
                    );
                    foreach ( $options as $key => $value ) {
                        echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
                    }
                    ?>
				</select>
			</span>
        </label>
        <?php
    }

    /**
     * Custom fields "Inpost dimensions" and "Is Inpost enabled" for Quick Edit product action
     */
    function products_bulk_quick_edit_inpost_enabled()
    {
        ?>
        <label>
            <span class="title"><?php esc_html_e( 'Is method Inpost enabled?', 'inpost-italy' ); ?></span>
            <span class="input-text-wrap">
				<select class="inpost_italy_enabled" name="_is_inpost_enabled">
					<?php
                    $options = array(
                        ''    => esc_html__( '— No change —', 'inpost-italy' ),
                        'yes' => esc_html__( 'Yes', 'inpost-italy' ),
                        'no'  => esc_html__( 'No', 'inpost-italy' ),
                    );
                    foreach ( $options as $key => $value ) {
                        echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
                    }
                    ?>
				</select>
			</span>
        </label>
        <br class="clear" />
        <label>
            <span class="title"><?php esc_html_e( 'InPost parcel dimensions', 'inpost-italy' ); ?></span>
            <span class="input-text-wrap">
				<select class="inpost_italy_enabled" name="_inpost_dimension">
					<?php
                    $options = array(
                        ''    => esc_html__( '— No change —', 'inpost-italy' )
                    );

                    $dimensions_options = inpost_italy()->get_package_sizes_gabaryt();

                    $options = array_merge( $options, $dimensions_options );

                    foreach ( $options as $key => $value ) {
                        echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
                    }
                    ?>
				</select>
			</span>
        </label>
        <br class="clear" />
        <?php
    }


    /**
     * Custom field "Inpost dimensions" for Bulk Edit product action
     */
    function products_bulk_edit_inpost_dimensions()
    {
        ?>
        <label>
            <span class="title"><?php esc_html_e( 'InPost parcel dimensions', 'inpost-italy' ); ?></span>
            <span class="input-text-wrap">
				<select class="inpost_italy_enabled" name="_inpost_dimension">
					<?php
                    $options = array(
                        ''    => esc_html__( '— No change —', 'inpost-italy' )
                    );

                    $dimensions_options = inpost_italy()->get_package_sizes_gabaryt();

                    $options = array_merge( $options, $dimensions_options );

                    foreach ( $options as $key => $value ) {
                        echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
                    }
                    ?>
				</select>
			</span>
        </label>
        <?php
    }

    /**
     * Save custom field during Bulk Edit product action
     */
    public function products_bulk_edit_inpost_save( $product ) {

        $product_id = $product->get_id();

        if( isset( $_REQUEST['_is_inpost_enabled'] ) && 'yes' === $_REQUEST['_is_inpost_enabled'] ) {
            update_post_meta( $product_id, 'inpost_italy_shipping_methods_allowed', array('easypack_italy_parcel_machines') );
        }

        if( isset( $_REQUEST['_is_inpost_enabled'] ) && 'no' === $_REQUEST['_is_inpost_enabled'] ) {
            update_post_meta( $product_id, 'inpost_italy_shipping_methods_allowed', [] );
        }

        if( isset( $_REQUEST['_inpost_dimension'] ) && ! empty( $_REQUEST['_inpost_dimension'] ) ) {
            update_post_meta( $product_id, 'inpost_italy_parcel_dimensions', wc_clean( $_REQUEST['_inpost_dimension'] ) );
        }
    }


}