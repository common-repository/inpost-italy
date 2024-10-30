<?php

namespace InspireLabs\InpostItaly;

use Exception;
use InspireLabs\InpostItaly\admin\Alerts;
use InspireLabs\InpostItaly\shipx\models\courier_pickup\ShipX_Dispatch_Order_Point_Address_Model;
use InspireLabs\InpostItaly\shipx\models\courier_pickup\ShipX_Dispatch_Order_Point_Model;

/**
 * EasyPack API
 *
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'InspireLabs\InpostItaly\EasyPack_Italy_API' ) ) :

	class EasyPack_Italy_API {

        const API_URL_PRODUCTION_IT = 'https://api-shipx-it.easypack24.net';
        const API_URL_SANDBOX_IT = 'https://stage-api-shipx-it.easypack24.net';
        const ENVIRONMENT_PRODUCTION = 'production';
        const ENVIRONMENT_SANDBOX = 'sandbox';

		/**
		 * @var self
		 */
		protected static $instance;

		/**
		 * @var string
		 */
		private $environment;

		/**
		 * @var string
		 */
		private $country;

		/**
		 * @var string
		 */
		private $api_url;

		protected $token;

		protected $cache_period = DAY_IN_SECONDS;


		public function __construct() {
			$this->token = get_option( 'easypack_token_italy' );
			$this->setupEnvironment();
		}

		private function setupEnvironment() {
			if ( 'sandbox' === get_option( 'easypack_italy_api_environment' ) ) {
				$this->environment = self::ENVIRONMENT_SANDBOX;
			} else {
				$this->environment = self::ENVIRONMENT_PRODUCTION;
			}

			$this->api_url = $this->make_api_url();
		}

		/**
		 * @param string $country_code
		 *
		 * @return string
		 */
		public function normalize_country_code_for_geowidget( $country_code ) {
			return strtolower( $country_code );
		}

		/**
		 * @param string $country_code
		 *
		 * @return string
		 */
		public function normalize_country_code_for_inpost( $country_code ) {
			return strtoupper( $country_code );
		}

		/**
		 * @return bool
		 */
		public function is_sandbox_env() {
			return self::ENVIRONMENT_SANDBOX === $this->environment;
		}

		/**
		 * @return bool
		 */
		public function is_production_env() {
			return self::ENVIRONMENT_PRODUCTION === $this->environment;
		}

		/**
		 * @return bool
		 */
		public function is_italy() {
			return 'IT';
		}


		public static function EasyPack_Italy_API() {
			if ( self::$instance === null ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * @param null $url
		 *
		 * @return null|string
		 */
        public function make_api_url($url = null)
        {

            if (self::ENVIRONMENT_SANDBOX === $this->environment) {
                $url = self::API_URL_SANDBOX_IT;
            }

            if (self::ENVIRONMENT_PRODUCTION === $this->environment) {
                $url = self::API_URL_PRODUCTION_IT;
            }

            $url = untrailingslashit($url);
            $parsed_url = wp_parse_url($url);
            if (!isset($parsed_url['path']) || trim($parsed_url['path']) == '') {
                $url .= '/v1';
            }

            return $url;
        }

		public function clear_cache() {
			$this->token   = get_option( 'easypack_token_italy' );
			$this->api_url = $this->make_api_url();
		}

		function translate_error( $error ) {
			$errors = [
				'receiver_email'     => esc_html__( 'Recipient e-mail', 'inpost-italy' ),
				'forbidden'          => esc_html__( 'forbidden', 'inpost-italy' ),
                'receiver_phone'     => esc_html__( 'Recipient phone', 'inpost-italy' ),
				'address'            => esc_html__( 'Address', 'inpost-italy' ),
				'phone'              => esc_html__( 'Phone', 'inpost-italy' ),
				'email'              => esc_html__( 'Email', 'inpost-italy' ),
				'post_code'          => esc_html__( 'Post code', 'inpost-italy' ),
				'postal_code'        => esc_html__( 'Post code', 'inpost-italy' ),
				'default_machine_id' => esc_html__( 'Default parcel locker', 'inpost-italy' ),

				'not_an_email'             => esc_html__( 'not valid', 'inpost-italy' ),
				'invalid'                  => esc_html__( 'invalid', 'inpost-italy' ),
				'not_found'                => esc_html__( 'not found', 'inpost-italy' ),
				'invalid_format'           => esc_html__( 'invalid format', 'inpost-italy' ),
				'required, invalid_format' => esc_html__( 'required', 'inpost-italy' ),
				'too_many_characters'      => esc_html__( 'too many characters', 'inpost-italy' ),
                'Action (cancel) can not be taken on shipment with status (confirmed).'
				                           => esc_html__( 'Action (cancel) can not be taken on shipment with status (confirmed).', 'inpost-italy' ),
				'There are some validation errors. Check details object for more info.'
				                           => esc_html__( 'There are some validation errors.', 'inpost-italy' ),

				'Access to this resource is forbidden'        => esc_html__( 'Invalid login or token', 'inpost-italy' ),
				'Sorry, access to this resource is forbidden' => esc_html__( 'Invalid login', 'inpost-italy' ),
				'Token is missing or invalid' => esc_html__( 'Token is missing or invalid', 'inpost-italy' ),
				'Box machine name cannot be empty' => esc_html__( 'Parcel Point is empty. Please fill in this field.', 'inpost-italy' ),
				'Default parcel machine' => esc_html__( 'Default send parcel locker: ', 'inpost-italy' ),
				'The transaction can not be completed due to the balance of your account' => esc_html__( 'The transaction can not be completed due to the balance of your account',
                    'inpost-italy' ),
                'You have not enough funds to pay for this parcel' => esc_html__( 'Can not create label. You have not enough funds to pay for this parcel',
                    'inpost-italy' )
			];

			if ( isset( $errors[ $error ] ) ) {
				return $errors[ $error ];
			}

			return $error;
		}

		public function get_error( $errors ) {
			return $this->get_error_recursive( $errors, 10 );
		}

		/**
		 * @param      $array
		 * @param int $level
		 * @param null $key_recursive
		 *
		 * @return string
		 */
		private function get_error_recursive(
			$array,
			$level = 1,
			$key_recursive = null
		) {

			$output = '';
			if ( null !== $key_recursive
			     && ! is_numeric( $key_recursive )
			) {
				$output .= $key_recursive . ' ';
			}
			foreach ( $array as $key => $value ) {

				if ( is_array( $value ) ) {

					$output .= $this->get_error_recursive( $value, $level + 1,
						$key );
				} else {
					if ( ! is_numeric( $key ) ) {
						$value  = str_replace( '_', ' ', $value );
						$output .= $key . ': ' . $value . '<br>';
					} else {
						if ( ! is_array( $value ) ) {
							$output .= $value . '<br>';
						}
					}
				}
			}

			return $output;
		}

		/**
		 * @param array $response
		 *
		 * @return bool
		 */
		private function is_binary_response( $response ) {
			if ( ! isset( $response['headers']['content-transfer-encoding'] ) ) {
				return false;
			}

			$headers = $response['headers'];
			$data    = $headers->getAll();

			return $data['content-transfer-encoding'] === 'binary';
		}


		public function post( $path, $args = [], $method = 'POST' ) {
			$url = untrailingslashit( $this->api_url ) . str_replace( ' ', '%20', str_replace( '@', '%40', $path ) );
			$request_args = [ 'timeout' => 30, 'method' => $method ];

			$request_args['headers'] = [
				'Authorization' => 'Bearer ' . $this->token,
				'Content-Type'  => 'application/json',
			];

			$request_args['body'] = $args;
			$request_args['body'] = wp_json_encode( $args );

			$response = wp_remote_post( $url, $request_args );

			if ( is_wp_error( $response ) ) {
				throw new Exception( esc_html( $response->get_error_message() ) );
			} else {

				if ( $this->is_binary_response( $response ) ) {
					return [
						'headers' => $response['headers'],
						'body' => $response['body'],
					];
				}

				$ret = json_decode( $response['body'], true );
				if ( ! is_array( $ret ) ) {
					throw new Exception( esc_html__( 'Bad API response. Check API URL', 'inpost-italy' ), 503 );
				} else {
					if ( isset( $ret['status'] ) ) {
						$errors = '';
						if ( isset( $ret['error'] ) && ! empty( $ret['error'] ) ) {
							if ( is_array( $ret['details'] ) ) {
								if ( count( $ret['details'] ) ) {
									$errors = $this->get_error( $ret['details'] );
								}
							} else {
								$errors = ': ' . $ret['details'];
							}
						} else {
							if ( isset( $ret['message'] ) ) {
								$errors = $this->translate_error( $ret['message'] );
							}
						}
						if ( isset( $ret['errors'] ) || isset( $ret['error'] ) ) {
							if ( empty( $errors ) ) {
								$errors = $ret['message'];
							}
                            $errors = str_replace( '_', ' ', $errors );
							throw new Exception( esc_html($errors), esc_html($ret['status']) );
						}
					}
				}

				return $ret;
			}

		}


		/**
		 * @param        $path
		 * @param array $args
		 * @param string $method
		 *
		 * @return array|mixed|object
		 * @throws Exception
		 */
		public function delete( $path, $args = [], $method = 'DELETE' ) {
			$url = untrailingslashit( $this->api_url ) . str_replace( ' ', '%20', str_replace( '@', '%40', $path ) );
			$request_args = [ 'timeout' => 30, 'method' => $method ];

			$request_args['headers'] = [
				'Authorization' => 'Bearer ' . $this->token,
				'Content-Type'  => 'application/json',
			];

			$request_args['body'] = $args;
			$request_args['body'] = wp_json_encode( $args );

			$response = wp_remote_post( $url, $request_args );
			if ( is_wp_error( $response ) ) {
				throw new Exception( esc_html( $response->get_error_message() ) );
			} else {

				if ( $this->is_binary_response( $response ) ) {
					return [
						'headers' => $response['headers'],
						'body'    => $response['body'],
					];
				}

				$ret = json_decode( $response['body'], true );
				if ( ! is_array( $ret ) ) {
					throw new Exception( esc_html__( 'Bad API response. Check API URL', 'inpost-italy' ), 503 );
				} else {
					if ( isset( $ret['status'] ) ) {
						$errors = '';

						if ( isset( $ret['error'] ) && is_array( $ret['error'] ) && count( $ret['error'] ) ) {
							if ( ! empty( $ret['message'] ) ) {
								$errors = $this->translate_error( $ret['message'] );
								throw new Exception( esc_html($errors), esc_html($ret['status']) );
							}
							if ( is_array( $ret['details'] ) ) {
								if ( count( $ret['details'] ) ) {
									$errors = $this->get_error( $ret['details'] );
								}
							} else {
								$errors = ': ' . $ret['details'];
							}
						} else {
							$errors = $this->translate_error( $ret['message'] );
						}
						if ( isset( $ret['errors'] ) || isset( $ret['error'] ) ) {
							if ( empty( $errors ) ) {
								$errors = $ret['message'];
							}
							throw new Exception( esc_html($errors), esc_html($ret['status']) );
						}
					}
				}

				return $ret;
			}

		}


		public function put( $path, $args = [], $method = 'PUT' ) {
			return $this->post( $path, $args, 'PUT' );
		}

		public function get(
			$path,
			$args = [],
			$params = [],
			$decode_url = false
		) {

		    $ret = null;

		    $url = untrailingslashit( $this->api_url ) . str_replace( ' ',	'%20',	str_replace( '@', '%40', $path ) );

			if ( ! empty( $params ) ) {
				$newUrl = $url;
				foreach ( $params as $k => $v ) {
					$newUrl = add_query_arg( $k, $v, $newUrl );
				}

				if ( true === $decode_url ) {
					$url = preg_replace( '/\[[^\[\]]*\]/', '[]', urldecode( $newUrl ) );
				} else {
					$url = $newUrl;
				}
			}

            if ( ! empty( $this->token ) ) {

                $request_args = [ 'timeout' => 30 ];

                $request_args['headers'] = [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type'  => 'application/json',
                ];

                $response = wp_remote_get( $url, $request_args );

                if ( is_wp_error( $response ) ) {
                    $this->authorizationError( $response->get_error_message() . ' ( Endpoint: ' . $url . ' )', $response->get_error_code() );
                } else {

                    if ($this->is_binary_response($response)) {
                        return [
                            'headers' => $response['headers'],
                            'body' => $response['body'],
                        ];
                    }

                    $ret = json_decode($response['body'], true);
                    if ( ! is_array( $ret ) ) {
                        throw new Exception(esc_html__( 'Bad API response. Check API URL', 'inpost-italy' ), 503 );
                    } else {
                        if ( isset( $ret['status'] ) ) {
                            $errors = '';
                            if ( isset( $ret['error'] ) && ! empty( $ret['error'] ) ) {
                                if ( is_array( $ret['details'] ) ) {
                                    if ( count( $ret['details'] ) ) {
                                        $errors = $this->get_error( $ret['details'] );
                                    }
                                } else {
                                    if ( ! empty( $ret['details'] ) ) {
                                        $errors = ': ' . $ret['details'];
                                    }

                                    if ( ! empty( $ret['message'] ) ) {
                                        $errors = ': ' . $ret['message'];
                                    }
                                }
                            } else {
                                if ( isset( $ret['message'] ) ) {
                                    $errors = $this->translate_error( $ret['message'] );
                                }
                            }
                            if ( isset( $ret['errors'] ) || isset( $ret['error'] ) ) {
                                if ( empty( $errors ) ) {
                                    $errors = $ret['message'];
                                }

                                $this->authorizationError( $errors, $ret['status'] );
                            }
                        }
                    }
                }
			}

            return $ret;

		}

		/**
		 * @param null $id
		 *
		 * @return array|mixed|object
		 * @throws Exception
		 */
		public function ping( $id = null ) {
            $res = null;
            if( ! empty( $this->token ) ) {
			    $organizationId = ( null !== $id ) ? $id : get_option( 'easypack_organization_id_italy' );
			    $res            = $this->get( sprintf( '/organizations/%s', $organizationId ) );

                if ( $res && ! isset( $res['error'] ) ) {
                    $alerts = new Alerts();
                    $alerts->add_success( 'Inpost Italy: ' . esc_html__( 'New API settings connection test passed.', 'inpost-italy' ) );

                    update_option( 'inpost_italy_api_login_error', '0' );
                } else {
                    update_option( 'inpost_italy_api_login_error', '1' );
                }
            }

			return $res;
		}


		/**
		 * @param null $id
		 *
		 * @return array|mixed|object
		 * @throws Exception
		 */
		public function get_organization( $id = null ) {
            $res = null;
            if( ! empty( $this->token ) ) {
                $organizationId = ( null !== $id ) ? $id : get_option( 'easypack_organization_id_italy' );
                $res = $this->get( sprintf('/organizations/%d', $organizationId ) );

                if ( isset( $res['error'] ) ) {
                    $status = isset( $res['status'] ) ? (int) $res['status'] : 401;
                    $this->authorizationError( $res['error'], $status );

                    return null;
                }
            }

			return $res;
		}

		/**
		 * @return array|mixed|object
		 * @throws Exception
		 */
		public function getServicesGlobal() {
			$res = $this->get( '/services/' );

			if ( isset( $res['error'] ) ) {
				$status = isset( $res['status'] ) ? (int) $res['status'] : 401;
				$this->authorizationError( $res['error'], $status );
			}

			return $res;
		}

		/**
		 * @param string $message
		 * @param int $status
		 *
		 * @throws Exception
		 */
		private function authorizationError( $message, $status ) {
			$errors = $this->translate_error( $message );

			$alerts = new Alerts();
			$alerts->add_error( 'Inpost Italy: ' . ( is_string( $errors )
                    ? $errors
					: serialize( $errors ) . $message . ' ( ' . $status . ' )' ) );

		}


		/**
		 * @param $dispatch_point_id
		 *
		 * @return array|mixed|object
		 * @throws Exception
		 *
		 * @depecated
		 */
		public function dispatch_point( $dispatch_point_id ) {
			return $this->get( '/dispatch_points/' . $dispatch_point_id );
		}


		/**
		 * @param $args
		 *
		 * @return array|mixed|object
		 * @throws Exception
		 */
		public function dispatch_order( $args ) {
			$organisationId = get_option( 'easypack_organization_id_italy' );
			$response = $this->post( sprintf( '/organizations/%d/dispatch_orders', $organisationId ), $args );

			return $response;
		}


		public function customer_parcel_create( $args ) {
			$organizationId = get_option( 'easypack_organization_id_italy' );
			$response = $this->post( sprintf( '/organizations/%d/shipments', $organizationId ), $args );

			return $response;
		}


		public function customer_parcel_get_by_id( $id ) {

			$response = $this->get( sprintf( '/shipments/%d', $id ) );

			return $response;
		}

		/**
		 * @return string
		 * @throws Exception
		 */
		public function get_statuses() {
			$response = $this->get( '/statuses' );

			return $response;
		}

		/**
		 * @param $parcel_id
		 *
		 * @return array|mixed|object
		 * @throws Exception
		 */
		public function customer_parcel_cancel( $parcel_id ) {
			$response = $this->delete( '/shipments/' . $parcel_id );

			return $response;
		}

		public function customer_parcel_pay( $parcel_id ) {
            $args = array();
			$response = $this->post( '/parcels/' . $parcel_id . '/pay', $args );

			return $response;
		}


		public function customer_parcel_sticker( $parcel_id ) {
			return $this->get( '/shipments/' . $parcel_id . '/label', [ 'format' => 'Pdf' ] );
		}


		/**
		 * @param $shipment_ids
		 *
		 * @return array|mixed|object
		 * @throws Exception
		 */
		public function customer_shipments_labels( $shipment_ids ) {
			$organizationId = get_option( 'easypack_organization_id_italy' );
			$labelFormat    = get_option( 'easypack_italy_label_format' );

			$args = [
				'format'       => 'pdf',
				'shipment_ids' => $shipment_ids,
				'type'         => $labelFormat === 'A4' ? 'normal' : 'A6',
			];

			return $this->get( sprintf( '/organizations/%d/shipments/labels', $organizationId ), [], $args, true );
		}

		/**
		 * @param $shipment_ids
		 *
		 * @return array|mixed|object
		 * @throws Exception
		 */
		public function customer_shipments_return_labels( $shipment_ids ) {
			$organizationId = get_option( 'easypack_organization_id_italy' );
			$args = [
				'format'       => 'pdf',
				'shipment_ids' => $shipment_ids,
			];

			return $this->get( sprintf( '/organizations/%d/shipments/return_labels',
					$organizationId )
				, []
				, $args
				, true
			);
		}

		/**
		 * @param $dispatch_order_id
		 *
		 * @return array|mixed|object
		 * @throws Exception
		 */
		public function dispatch_order_pdf( $dispatch_order_id ) {
			$organizationId = get_option( 'easypack_organization_id_italy' );
			$args = [
				'format' => 'Pdf',
			];

			return $this->get( sprintf( '/organizations/%d/dispatch_orders/%d/printout',
					$organizationId, $dispatch_order_id )
				, null
				, $args
				, true
			);
		}

		public function customer_parcel( $parcel_id ) {
			$response = $this->get( '/parcels/' . $parcel_id );
			$parcel   = $response;

			return $parcel;
		}


		/**
		 * @return mixed
		 * @deprecated
		 */
        public function api_country() {
            return 'IT';
        }

		public function validate_phone( $phone ) {

            if ( preg_match( "^(\(?(((\+)|00)39)?\)?(3)(\d{8,9}))$", $phone ) ) {
                return true;
            } else {
                return esc_html__( 'Invalid phone number. Valid phone number must contains 9-10 digits and must begins with 3.',
                    'inpost-italy' );
            }


			return esc_html__( 'Invalid phone number.', 'inpost-italy' );

		}

		/**
		 * @param $shipments
		 *
		 * @return array|mixed|object
		 * @throws Exception
		 */
		public function calculate_shipments( $shipments ) {
			$organizationId = get_option( 'easypack_organization_id_italy' );
			$response = $this->post( sprintf( '/organizations/%d/shipments/calculate', $organizationId ), $shipments );

			return $response;
		}


		/**
		 * @return mixed
		 */
		public function getCountry() {
			return $this->country;
		}

	}


endif;

