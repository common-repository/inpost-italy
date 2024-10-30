<?php
namespace InspireLabs\InpostItaly\shipx\services\shipment;

use InspireLabs\InpostItaly\EasyPack_Italy;
use InspireLabs\InpostItaly\EasyPack_Italy_API;
use InspireLabs\InpostItaly\shipx\models\shipment\ShipX_Shipment_Cod_Model;
use InspireLabs\InpostItaly\shipx\models\shipment\ShipX_Shipment_Contstants;
use InspireLabs\InpostItaly\shipx\models\shipment\ShipX_Shipment_Custom_Attributes_Model;
use InspireLabs\InpostItaly\shipx\models\shipment\ShipX_Shipment_Insurance_Model;
use InspireLabs\InpostItaly\shipx\models\shipment\ShipX_Shipment_Internal_Data;
use InspireLabs\InpostItaly\shipx\models\shipment\ShipX_Shipment_Model;
use InspireLabs\InpostItaly\shipx\models\shipment\ShipX_Shipment_Parcel_Dimensions_Model;
use InspireLabs\InpostItaly\shipx\models\shipment\ShipX_Shipment_Parcel_Model;
use InspireLabs\InpostItaly\shipx\models\shipment\ShipX_Shipment_Parcel_Weight_Model;
use InspireLabs\InpostItaly\shipx\models\shipment\ShipX_Shipment_Receiver_Address_Model;
use InspireLabs\InpostItaly\shipx\models\shipment\ShipX_Shipment_Receiver_Model;
use InspireLabs\InpostItaly\shipx\models\shipment\ShipX_Shipment_Sender_Address_Model;
use InspireLabs\InpostItaly\shipx\models\shipment\ShipX_Shipment_Sender_Model;
use InspireLabs\InpostItaly\shipx\models\shipment\ShipX_Shipment_Status_History_Item_Model;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Automattic\WooCommerce\Utilities\OrderUtil;


class ShipX_Shipment_Service
{

    /**
     * @param ShipX_Shipment_Model $shipment
     */
    public function update_shipment_to_db(ShipX_Shipment_Model $shipment)
    {
	    if (  null === $shipment->getInternalData()->getLastStatusFromHistory()
	          || $shipment->getInternalData()->getStatus()
	             !== $shipment->getInternalData()->getLastStatusFromHistory()->get_name()
	    ) {

		    $statusHistoryItem = new ShipX_Shipment_Status_History_Item_Model();
		    $statusHistoryItem->set_name( $shipment->getInternalData()->getStatus() );
		    $statusHistoryItem->set_timestamp( $shipment->getInternalData()->getStatusChangedTimestamp() );
		    $shipment->getInternalData()->putStatusHistoryItem( $statusHistoryItem );
	    }

        update_post_meta($shipment->getInternalData()->getOrderId(), '_shipx_shipment_object', $shipment);
    }

    /**
     * @param $order_id
     *
     * @return ShipX_Shipment_Model|null
     */
    public function get_shipment_by_order_id($order_id)
    {
        $order = wc_get_order($order_id);
        $from_order_meta = null;

        if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
            // HPOS usage is enabled.
            $from_order_meta_raw = isset( get_post_meta( $order_id )['_shipx_shipment_object'][0] )
                ? get_post_meta( $order_id )['_shipx_shipment_object'][0]
                : '';

            if( !empty( $from_order_meta_raw ) ) {
                $from_order_meta = unserialize( $from_order_meta_raw );
            }

        } else {
            // Traditional orders are in use.
            $from_order_meta = $order->get_meta('_shipx_shipment_object');

        }

        return $from_order_meta instanceof ShipX_Shipment_Model
            ? $from_order_meta
            : null;
    }

    /**
     * @param array       $parcels
     * @param int         $order_id
     * @param string      $send_method
     * @param string      $service
     * @param array       $sizes
     *
     * @param string      $parcel_machine_id
     *
     * @param null        $cod_amount
     *
     * @param float|null  $insurance_amount
     *
     * @param string|null $reference_number
     *
     * @return ShipX_Shipment_Model
     */
    public function create_shipment_object_by_shiping_data(
        $parcels,
	    $order_id,
	    $send_method,
	    $service,
	    $sizes = [],
	    $parcel_machine_id = null,
	    $cod_amount = null,
	    $insurance_amount = null,
	    $reference_number = null
    ) {

        $is_service_courier_type = $this->is_service_id_courier_type($service);

        $insurance_amount = floatval($insurance_amount);

        if ($insurance_amount <= 0) {
            $insurance_amount = null;
        }

        $shipment = new ShipX_Shipment_Model();
		$shipment->setExternalCustomerid('woocommerce');
        $additional_services = [];
        $order = wc_get_order($order_id);
        $customAttributes = new ShipX_Shipment_Custom_Attributes_Model();

        if (false === $is_service_courier_type) {
            $customAttributes->setTargetPoint($parcel_machine_id);
        }

        switch ($send_method) {
            case 'parcel_machine':
                //$customAttributes->setDropoffPoint(get_option('easypack_default_machine_id'));
                $customAttributes->setDropoffPoint(null);
                $customAttributes->setSendingMethod(ShipX_Shipment_Custom_Attributes_Model::SENDING_METHOD_PARCEL_LOCKER);
                break;

            case 'courier':
                $customAttributes->setSendingMethod(ShipX_Shipment_Custom_Attributes_Model::SENDING_METHOD_DISPATCH_ORDER);
                break;

            case 'pop':
                $customAttributes->setSendingMethod(ShipX_Shipment_Custom_Attributes_Model::SENDING_METHOD_POP);
                break;
        }

        $shipment->setCustomAttributes($customAttributes);
        $receiver = new ShipX_Shipment_Receiver_Model();
        $receiver->setFirstName($order->get_shipping_first_name());
        $receiver->setLastName($order->get_shipping_last_name());
        $receiver->setEmail($order->get_billing_email());
        $receiver->setPhone( $order->get_billing_phone() );

        $receiverAddress = new ShipX_Shipment_Receiver_Address_Model();

        if (!empty($order->get_shipping_address_1())) {
            $receiverAddress->setStreet($order->get_shipping_address_1());
            if (empty($order->get_shipping_address_2())) {
                $receiverAddress->setBuildingNumber($order->get_shipping_address_1());
            } else {
                $receiverAddress->setBuildingNumber($order->get_shipping_address_2());
            }
            $receiver->setCompanyName($order->get_shipping_company());
            $receiverAddress->setPostCode($order->get_shipping_postcode());
            $receiverAddress->setCity($order->get_shipping_city());
            $receiverAddress->setCountryCode($order->get_shipping_country());
        } else {
            $receiverAddress->setStreet($order->get_billing_address_1());
            if (empty($order->get_shipping_address_2())) {
                $receiverAddress->setBuildingNumber($order->get_billing_address_1());
            } else {
                $receiverAddress->setBuildingNumber($order->get_billing_address_2());
            }
            $receiver->setCompanyName($order->get_billing_company());
            $receiverAddress->setPostCode($order->get_shipping_postcode());
            $receiverAddress->setCity($order->get_shipping_city());
            $receiverAddress->setCountryCode($order->get_shipping_country());
        }

        $receiver->setAddress($receiverAddress);
        $shipment->setReceiver($receiver);

        $sender = new ShipX_Shipment_Sender_Model();
        //$sender->setFirstName(get_option('easypack_sender_first_name'));
        //$sender->setLastName(get_option('easypack_sender_last_name'));
        $sender->setEmail(get_option('easypack_italy_sender_email'));
        $sender->setPhone(get_option('easypack_italy_sender_phone'));
        $sender->setCompanyName(get_option('easypack_italy_sender_company_name'));
        $senderAddress = new ShipX_Shipment_Sender_Address_Model();

        $senderAddress->setCity(get_option('easypack_italy_sender_city'));
        $senderAddress->setStreet(get_option('easypack_italy_sender_street'));
        $senderAddress->setBuildingNumber(get_option('easypack_italy_sender_building_no'));
        $senderAddress->setPostCode(get_option('easypack_italy_sender_post_code'));
        $sender->setAddress($senderAddress);
        $shipment->setSender($sender);

        $internalData = new ShipX_Shipment_Internal_Data();
        $internalData->setStatus('new');

        if (inpost_italy_api()->is_production_env()) {
            $internalData->setApiVersion($internalData::API_VERSION_PRODUCTION);
        } else {
            $internalData->setApiVersion($internalData::API_VERSION_SANDBOX);
        }

        $shipment->setInternalData($internalData);


        $shipment->setService($service);

        if (null !== $cod_amount) {
            //$additional_services[] = $shipment::ADDITIONAL_SERVICES_COD;
            $cod = new ShipX_Shipment_Cod_Model();
            $cod->setCurrency(ShipX_Shipment_Contstants::CURRENCY_EURO);
            $cod->setAmount((float)$cod_amount);
            $shipment->setCod($cod);
        }

        if (null !== $insurance_amount) {
            $insurance = new ShipX_Shipment_Insurance_Model();
            $insurance->setCurrency(ShipX_Shipment_Contstants::CURRENCY_EURO);
            $insurance->setAmount((float)$insurance_amount);
            $shipment->setInsurance($insurance);
        }

        $shipment->setReference($reference_number);

        $parcelsCollection = [];


        if (true === $is_service_courier_type) {
            $parcel = new ShipX_Shipment_Parcel_Model();
            $parcel->setIsNonstandard(false);
            $parcel->setId($order_id.'_1');
            $dimensions = new ShipX_Shipment_Parcel_Dimensions_Model();
            $dimensions->setUnit('mm');
            $dimensions->setLength($sizes['length']);
            $dimensions->setWidth($sizes['width']);
            $dimensions->setHeight($sizes['height']);
            $parcel->setDimensions($dimensions);
            $weight = new ShipX_Shipment_Parcel_Weight_Model();
            $weight->setUnit('kg');
            $weight->setAmount($sizes['weight']);
            $parcel->setWeight($weight);
            if ($sizes['non_standard'] === 'yes') {
                $non_standard = true;
            } else {
                $non_standard = false;
            }
            //$parcel->setIsNonstandard($non_standard);
            $parcelsCollection[] = $parcel;
        } else {

            foreach ($parcels as $counter_id => $p) {
                $parcel = new ShipX_Shipment_Parcel_Model();
                $parcel->setId($order_id.'_'.$counter_id);
                $parcel->setIsNonstandard(false);

                $p = isset( $p['package_size'] ) ? $p['package_size'] : $p;

                switch ($p) {
                    case $parcel::SIZE_TEMPLATE_SMALL:
                        $parcel->setTemplate($parcel::SIZE_TEMPLATE_SMALL);
                        break;

                    case $parcel::SIZE_TEMPLATE_MEDIUM:
                        $parcel->setTemplate($parcel::SIZE_TEMPLATE_MEDIUM);
                        break;

                    case $parcel::SIZE_TEMPLATE_LARGE:
                        $parcel->setTemplate($parcel::SIZE_TEMPLATE_LARGE);
                        break;
                }


                $parcelsCollection[] = $parcel;
            }
        }

        if (!empty($parcelsCollection)) {
            $shipment->setParcels($parcelsCollection);
        }

        if (!empty($additional_services)) {
            $shipment->setAdditionalServices($additional_services);
        }

        return $shipment;
    }


    /**
     * @param ShipX_Shipment_Model $shipX_Shipment_Model
     *
     * @return array
     *
     * @throws ReflectionException
     */
    public function shipment_to_array($shipX_Shipment_Model)
    {

        $refl = new ReflectionClass($shipX_Shipment_Model);

        $temp = array_map(function ($prop) use ($shipX_Shipment_Model) {
            /**
             * @var ReflectionProperty $prop
             */
            $prop->setAccessible(true);

            if ('shop_data' === $prop->getName()) {
                return null;
            }

            $p = $prop->getValue($shipX_Shipment_Model);
            if (is_object($p)) {
                return [
                    $prop->getName(),
                    $this->shipment_to_array($prop->getValue($shipX_Shipment_Model)),
                ];
            } else {
                return [
                    $prop->getName(),
                    $prop->getValue($shipX_Shipment_Model),
                ];
            }

        }, $refl->getProperties());

        foreach ($temp as $key => $property) {

            if (null === $property) {
                unset($temp[$key]);
                continue;
            }

            $kname = $property[0];
            unset($temp[$key]);
            $temp[$kname] = $property[1];

            if ($kname === 'parcels') {
                foreach ($property[1] as $k => $parcel) {
                    $parcels[] = $this->shipment_to_array($parcel);
                }
                $temp[$kname] = $parcels;
            }

        }

        return $temp;

    }

    /**
     * @param ShipX_Shipment_Model $shipment
     *
     * @return bool
     */
    public function is_courier_service($shipment)
    {

        if (in_array($shipment->getService()
            , [
                $shipment::SERVICE_INPOST_COURIER_LOCAL_SUPER_EXPRESS,
                $shipment::SERVICE_INPOST_COURIER_LOCAL_STANDARD,
                $shipment::SERVICE_INPOST_COURIER_EXPRESS_1700,
                $shipment::SERVICE_INPOST_COURIER_EXPRESS_1200,
                $shipment::SERVICE_INPOST_COURIER_EXPRESS_1000,
                $shipment::SERVICE_INPOST_COURIER_STANDARD,
                $shipment::SERVICE_INPOST_COURIER_PALETTE,
                $shipment::SERVICE_INPOST_COURIER_ALLEGRO,
                $shipment::SERVICE_INPOST_COURIER_LOCAL_EXPRESS,
            ])
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param string $method_id
     *
     * @return bool
     */
    public function is_service_id_courier_type($method_id)
    {
        if (in_array($method_id
            , [
                ShipX_Shipment_Model::SERVICE_INPOST_COURIER_LOCAL_SUPER_EXPRESS,
                ShipX_Shipment_Model::SERVICE_INPOST_COURIER_LOCAL_STANDARD,
                ShipX_Shipment_Model::SERVICE_INPOST_COURIER_EXPRESS_1700,
                ShipX_Shipment_Model::SERVICE_INPOST_COURIER_EXPRESS_1200,
                ShipX_Shipment_Model::SERVICE_INPOST_COURIER_EXPRESS_1000,
                ShipX_Shipment_Model::SERVICE_INPOST_COURIER_STANDARD,
                ShipX_Shipment_Model::SERVICE_INPOST_COURIER_PALETTE,
                ShipX_Shipment_Model::SERVICE_INPOST_COURIER_ALLEGRO,
                ShipX_Shipment_Model::SERVICE_INPOST_COURIER_LOCAL_EXPRESS,
            ])
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param ShipX_Shipment_Model $shipment
     *
     * @return bool
     */
    public function is_courier_sending_method($shipment)
    {
        if ($shipment::SENDING_METHOD_DISPATCH_ORDER
            === $shipment->getCustomAttributes()->getSendingMethod()
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param ShipX_Shipment_Model $shipment
     *
     * @return bool
     */
    public function is_pop_sending_method($shipment)
    {
        if ($shipment::SENDING_METHOD_POP ===
            $shipment->getCustomAttributes()->getSendingMethod()
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param ShipX_Shipment_Model $shipment
     *
     * @return bool
     */
    public function is_parcel_locker_sending_method($shipment)
    {
        if ($shipment::SENDING_METHOD_PARCEL_LOCKER
            === $shipment->getCustomAttributes()->getSendingMethod()
        ) {
            return true;
        }

        return false;
    }


    /**
     * @param ShipX_Shipment_Model $shipment
     *
     * @return bool
     */
    public function getTrackingUrl($shipment)
    {
        return sprintf('https://inpost.it/trova-il-tuo-pacco?number=%s',
            $shipment->getInternalData()->getTrackingNumber()
        );
    }

    /**
     * @param ShipX_Shipment_Model $shipment
     *
     * @return bool
     */
    public function is_shipment_match_to_current_api(
        ShipX_Shipment_Model $shipment
    ) {
        $internal_data = $shipment->getInternalData();
        $shipment_api = $internal_data->getApiVersion();

        if ($internal_data::API_VERSION_PRODUCTION === $shipment_api) {
            if (inpost_italy_api()->is_production_env()) {
                return true;
            } else {
                return false;
            }
        }

        if ($internal_data::API_VERSION_SANDBOX === $shipment_api) {
            if (inpost_italy_api()->is_sandbox_env()) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * @param ShipX_Shipment_Model $shipment
     *
     * @return bool
     */
    public function is_shipment_cancellable(ShipX_Shipment_Model $shipment)
    {
        $shipment_status = $shipment->getInternalData()->getStatus();
        if ('created' === $shipment_status
            || 'offers_prepared' === $shipment_status
        ) {

            return true;
        } else {

            return false;
        }
    }

    /**
     * @param ShipX_Shipment_Model $shipment
     *
     * @return string
     */
    public function get_customer_service_name(ShipX_Shipment_Model $shipment)
    {
        $service_id = $shipment->getService();

        return $this->get_customer_service_name_by_id($service_id);
    }

    /**
     * @param string $service_id
     *
     * @return string
     */
    public function get_customer_service_name_by_id($service_id)
    {
        switch ($service_id) {
            case ShipX_Shipment_Model::SERVICE_INPOST_LETTER_ECOMMERCE:
                return esc_html__('Parcel e-commerce InPost', 'inpost-italy');
            case ShipX_Shipment_Model::SERVICE_INPOST_LOCKER_STANDARD:
                return esc_html__('Standard parcel locker shipment', 'inpost-italy');
            case ShipX_Shipment_Model::SERVICE_INPOST_LOCKER_PASS_THRU:
                return esc_html__('PassThru parcel (no logistics)', 'inpost-italy');
            case ShipX_Shipment_Model::SERVICE_INPOST_LOCKER_ALLEGRO:
                return esc_html__('Allegro InPost Parcel Lockers shipment', 'inpost-italy');
            case ShipX_Shipment_Model::SERVICE_INPOST_LETTER_ALLEGRO:
                return esc_html__('Allegro InPost Registered Mail shipment', 'inpost-italy');
            case ShipX_Shipment_Model::SERVICE_INPOST_COURIER_ALLEGRO:
                return esc_html__('Allegro InPost Courier shipment', 'inpost-italy');
            case ShipX_Shipment_Model::SERVICE_INPOST_COURIER_PALETTE:
                return esc_html__('Standard Pallet courier shipment', 'inpost-italy');
            case ShipX_Shipment_Model::SERVICE_INPOST_COURIER_STANDARD:
                return esc_html__('Standard courier shipment', 'inpost-italy');
            case ShipX_Shipment_Model::SERVICE_INPOST_COURIER_EXPRESS_1000:
                return esc_html__('Courier shipment with delivery to 10 a.m. on the following day', 'inpost-italy');
            case ShipX_Shipment_Model::SERVICE_INPOST_COURIER_EXPRESS_1200:
                return esc_html__('Courier shipment with delivery to 12 p.m. on the following day', 'inpost-italy');
            case ShipX_Shipment_Model::SERVICE_INPOST_COURIER_EXPRESS_1700:
                return esc_html__('Courier shipment with delivery to 5 p.m. on the following day', 'inpost-italy');
            case ShipX_Shipment_Model::SERVICE_INPOST_COURIER_LOCAL_STANDARD:
                return esc_html__('Standard local courier service', 'inpost-italy');
            case ShipX_Shipment_Model::SERVICE_INPOST_COURIER_LOCAL_EXPRESS:
                return esc_html__('Express local courier service', 'inpost-italy');
            case ShipX_Shipment_Model::SERVICE_INPOST_COURIER_LOCAL_SUPER_EXPRESS:
                return esc_html__('Super Express local courier service', 'inpost-italy');
			case ShipX_Shipment_Model::SERVICE_INPOST_COURIER_C2C:
                return esc_html__('InPost Courier C2C', 'inpost-italy');
        }

        return esc_html__('Unknown service', 'inpost-italy');
    }

    /**
     * @return array
     */
    public function get_services_key_value()
    {
        //$services[] = 'inpost_courier_standard';
        //$services[] = 'inpost_courier_express_1000';
        //$services[] = 'inpost_courier_express_1200';
        //services[] = 'inpost_courier_express_1700';
        //$services[] = 'inpost_courier_palette';
        //$services[] = 'inpost_courier_local_standard';
        //$services[] = 'inpost_courier_local_express';
        //$services[] = 'inpost_courier_local_super_express';
        $services[] = 'inpost_locker_standard';
        //$services[] = 'inpost_courier_c2c';
        //$services[] = 'inpost_locker_allegro';
        //$services[] = 'inpost_letter_ecommerce';
        //$services[] = 'inpost_courier_allegro';

        $return = [
            'any' => esc_html__('Any service', 'inpost-italy'),
        ];
        foreach ($services as $id) {
            $return[$id] = $this->get_customer_service_name_by_id( $id );
        }

        return $return;
    }

    /**
     * @param ShipX_Shipment_Model $shipment
     *
     * @return null|string
     */
    public function get_table_attributes(ShipX_Shipment_Model $shipment)
    {
        $parcels = $shipment->getParcels();
        foreach ($parcels as $parcel) {
            if ($this->is_courier_service($shipment)) {

                $dimensions = $parcel->getDimensions();
                $weight = $parcel->getWeight();
                if (null === $dimensions || null === $weight) {
                    return null;
                }
                $weight_unit = $weight->getUnit();
                $dim_unit = $dimensions->getUnit();
                $length = sprintf('%s: %s %s',
                    esc_html__('Length', 'inpost-italy'),
                    $dimensions->getLength(),
                    $dim_unit);
                $width = sprintf('%s: %s %s',
                    esc_html__('Width', 'inpost-italy'),
                    $dimensions->getWidth(),
                    $dim_unit);
                $height = sprintf('%s: %s %s',
                    esc_html__('Height', 'inpost-italy'),
                    $dimensions->getHeight(),
                    $dim_unit);
                $weight = sprintf('%s: %s %s',
                    esc_html__('Weight', 'inpost-italy'),
                    $weight->getAmount(),
                    $weight_unit);
                $non_standard = sprintf('%s: %s',
                    esc_html__('Non standard', 'inpost-italy'),
                    ($parcel->is_non_standard() === true
                        ? esc_html__('yes', 'inpost-italy')
                        : esc_html__('no', 'inpost-italy'))
                );

                return sprintf('%s <br> %s <br> %s <br> %s <br> %s',
                    $length, $width, $height, $weight, $non_standard);
            }
            $size = $parcel->getTemplate();

            return $size;
        }

        return null;
    }
}
