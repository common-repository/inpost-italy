<?php

namespace InspireLabs\InpostItaly\shipx\services\courier_pickup;

use Exception;
use InspireLabs\InpostItaly\admin\Alerts;
use InspireLabs\InpostItaly\EasyPack_Italy;
use InspireLabs\InpostItaly\shipx\models\courier_pickup\ShipX_Dispatch_Order_Model;
use InspireLabs\InpostItaly\shipx\models\courier_pickup\ShipX_Dispatch_Order_Point_Address_Model;
use InspireLabs\InpostItaly\shipx\models\courier_pickup\ShipX_Dispatch_Order_Point_Model;
use InspireLabs\InpostItaly\shipx\models\courier_pickup\ShipX_Dispatch_Order_Shipment_Model;
use InspireLabs\InpostItaly\shipx\models\shipment\ShipX_Shipment_Dispatch_Status;
use InspireLabs\InpostItaly\shipx\models\shipment\ShipX_Shipment_Model;


class ShipX_Courier_Pickup_Service {


	/**
	 * @param string|array|null $dpoint
	 *
	 * @return string
	 */
	public function getDispatchPointStr( $dpoint = null ): string {
		return is_array( $dpoint ) ? $dpoint['street']
		                             . ' ' . $dpoint['building_number']
		                             . '<br>' . $dpoint['post_code']
		                             . ' ' . $dpoint['city'] : (string) $dpoint;
	}

	/**
	 * @param int $pointId
	 *
	 * @return array
	 */
	public function getDispatchPoint( int $pointId ): array {
		$return  = [];
		$dpoints = (array) get_option( EasyPack_Italy::ATTRIBUTE_PREFIX . '_dpoint' );

		$return['street']          = $dpoints['street'][ $pointId ];
		$return['building_number'] = $dpoints['building_number'][ $pointId ];
		$return['post_code']       = $dpoints['postal_code'][ $pointId ];
		$return['city']            = $dpoints['city'][ $pointId ];
		$return['country_code']    = 'PL';
		$return['id']              = md5( $dpoints['street'][ $pointId ]
		                                  . $dpoints['building_number'][ $pointId ]
		                                  . $dpoints['postal_code'][ $pointId ]
		                                  . $dpoints['city'][ $pointId ] );

		return $return;
	}


	/**
	 * @param ShipX_Dispatch_Order_Model $dispath_order
	 */
	public function createDispatchOrderPost( $dispath_order ) {
		$post               = [
			'post_type'   => 'dispatch_order',
			'post_status' => 'publish',
		];
		$post['post_title'] = strtoupper( 'IT' )
		                      . ' - '
		                      . date_i18n( get_option( 'date_format' )
		                                   . ' ' . get_option( 'time_format' ),
				current_time( 'timestamp' ) );
		$post_id            = wp_insert_post( $post );
		$post               = get_post( $post_id );
		$post->post_title   = $post_id . ' - ' . $post->post_title;
		wp_update_post( $post );
		update_post_meta( $post_id, '_dispath_order', $dispath_order );
	}


	/**
	 * @param array $dispatch_point_address
	 * @param ShipX_Shipment_Model[] $shipments
	 *
	 * @throws Exception
	 */
	public function createDispatchOrder(
		array $dispatch_point_address,
		$shipments
	) {

		$api = inpost_italy_api();

		$shipmentsToDispath = [];
		foreach ( $shipments as $k => $shipment ) {
			$shipmentsToDispath[] = $shipment->getInternalData()->getInpostId();
		}

		$result = $api->dispatch_order(
			[
				'address'   =>
					$dispatch_point_address,
				'shipments' => $shipmentsToDispath,
			]
		);


		$dispatch_order        = $this->mapDispathOrder( $result );
		$dispatch_order_status = new ShipX_Shipment_Dispatch_Status();

		$dispatch_order_status->setDispathOrderPointName( $dispatch_point_address );
		$dispatch_order_status->setDispathOrderId( $dispatch_order->getId() );
		$dispatch_order_status->setDispathOrderPointId( $dispatch_order->getAddress()
		                                                               ->getId() );
		$dispatch_order_status->setDispathOrderStatus( $dispatch_order->getStatus() );

		$shipment_service = inpost_italy()->get_shipment_service();

		foreach ( $shipments as $shipment ) {
			$internal_data = $shipment->getInternalData();
			$internal_data->setDispatchStatus( $dispatch_order_status );
			$shipment->setInternalData( $internal_data );
			$shipment_service->update_shipment_to_db( $shipment );
		}

		$this->createDispatchOrderPost( $dispatch_order );


	}


	/**
	 * @param array $dispatch_order_response
	 *
	 * @return ShipX_Dispatch_Order_Model
	 */
	private function mapDispathOrder( $dispatch_order_response ) {
		$dispatch_order = new ShipX_Dispatch_Order_Model();
		$dispatch_order->setId( $dispatch_order_response['id'] );
		$dispatch_order->setHref( $dispatch_order_response['href'] );
		$dispatch_order->setStatus(
			$this->get_dispath_order_status_int( $dispatch_order_response['status'] ) );
		$dispatch_order->setCreatedAt( $dispatch_order_response['created_at'] );
		$address = new ShipX_Dispatch_Order_Point_Address_Model();


		$address->setId( $dispatch_order_response['address']['id'] );
		$address->setBuildingNumber( $dispatch_order_response['address']['building_number'] );
		$address->setPostCode( $dispatch_order_response['address']['post_code'] );
		$address->setCity( $dispatch_order_response['address']['post_code'] );
		$address->setCountryCode( $dispatch_order_response['address']['country_code'] );
		$address->setStreet( $dispatch_order_response['address']['street'] );
		$dispatch_order->setAddress( $address );
		$shipments = [];
		foreach ( $dispatch_order_response['shipments'] as $shipment ) {
			$shipment_model = new ShipX_Dispatch_Order_Shipment_Model;
			$shipment_model->setId( $shipment['id'] );
			$shipment_model->setHref( $shipment['href'] );
			$shipment_model->setTrackingNumber( $shipment['tracking_number'] );
			$shipments[] = $shipment_model;
		}
		$dispatch_order->setShipments( $shipments );

		return $dispatch_order;
	}

	/**
	 * @param string $status
	 *
	 * @return int
	 */
	private function get_dispath_order_status_int( $status ) {
		switch ( $status ) {
			case 'sent':
				return ShipX_Dispatch_Order_Model::STATUS_SENT;

			case 'new':
				return ( ShipX_Dispatch_Order_Model::STATUS_NEW );

			case 'accepted':
				return ( ShipX_Dispatch_Order_Model::STATUS_ACCEPTED );

			case 'done':
				return ( ShipX_Dispatch_Order_Model::STATUS_DONE );

			case 'rejected':
				return ( ShipX_Dispatch_Order_Model::STATUS_REJECTED );

			case 'canceled';
				return ( ShipX_Dispatch_Order_Model::STATUS_CANCELED );
		}
	}

	/**
	 * @param int $status
	 *
	 * @return string
	 */
	public function get_dispatch_order_status_string( $status ) {
		switch ( $status ) {
			case ShipX_Dispatch_Order_Model::STATUS_SENT:
				return 'sent';

			case ShipX_Dispatch_Order_Model::STATUS_NEW:
				return 'new';

			case ShipX_Dispatch_Order_Model::STATUS_ACCEPTED:
				return 'accepted';

			case ShipX_Dispatch_Order_Model::STATUS_DONE:
				return 'done';

			case ShipX_Dispatch_Order_Model::STATUS_REJECTED:
				return 'rejected';

			case ShipX_Dispatch_Order_Model::STATUS_CANCELED;
				return 'canceled';
		}
	}


}