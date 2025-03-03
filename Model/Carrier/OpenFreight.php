<?php

namespace TIG\OpenFreight\Model\Carrier;

use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Psr\Log\LoggerInterface;
use Magento\Framework\DataObject;
use Magento\Shipping\Model\Config;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Store\Model\ScopeInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;

class OpenFreight extends AbstractCarrier implements CarrierInterface
{
    /**
     * Carrier's code
     *
     * @var string
     */
    protected $_code = 'tig';

    /**
     * Whether this carrier has fixed rates calculation
     *
     * @var bool
     */
    protected $_isFixed = true;

    /**
     * @var ResultFactory
     */
    protected $_rateResultFactory;

    /**
     * @var MethodFactory
     */
    protected $_rateMethodFactory;

    /**
     * @var Magento\Framework\HTTP\Client\Curl
     */
    protected $_curl;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param ResultFactory $rateResultFactory
     * @param MethodFactory $rateMethodFactory
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        Curl $curl,
        array $data = []
    ) {
        $this->_curl = $curl;
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * Generates list of allowed carrier`s shipping methods
     * Displays on cart price rules page
     *
     * @return array
     * @api
     */
    public function getAllowedMethods()
    {
        return [$this->getCarrierCode() => __($this->getConfigData('name'))];
    }

    /**
     * Collect and get rates for storefront
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param RateRequest $request
     * @return DataObject|bool|null
     * @api
     */
    public function collectRates(RateRequest $request)
    {
        /**
         * Make sure that Shipping method is enabled
         */
        if (!$this->isActive()) {
            return false;
        }

        // send receiver address in so we can check for po box, etc...
        $receiver_address   = null;
        $receiver_address   = trim((string)$request->getDestStreet());
        $receiver_suburb    = trim((string)$request->getDestCity());
        $receiver_postcode  = trim((string)$request->getDestPostcode());

        if ($receiver_suburb == '' || $receiver_postcode == '') {
            return false;
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $valid = $this->_isValidAddress($receiver_suburb, $receiver_postcode, $objectManager);

        $tig_endpoint = trim((string)$this->getConfigData('api_endpoint'));
        $tig_username = trim((string)$this->getConfigData('api_username'));
        $tig_key = trim((string)$this->getConfigData('api_key'));

        $valid = true;//$this->_validateLocation($receiver_suburb, $receiver_postcode,$tig_endpoint, $tig_username, $tig_key);
        // Only continue if the address is valid.
        if (!$valid) {
            return false;
        }

        // set empty packages array for return
        $packages = [];

        //array for our configurable skus and the cart quantities
        $qtyConfigurable = [];
        $qtyBundle = [];

        foreach ($request->getAllItems() as $item) {
            $product = $item->getProduct();

            //If this SKU is the Parent/Base/Bundle product then we'll add the SKU to the configurable array with the updated quantity
            //This has to happen as Magent adds a "phantom" base product whenever we add a configurable product
            //When you update the quantity in the cart of this line item Magento searches the cart for the SKU and updates the first one it finds.
            //In our case it is the configurable product that we don't quote on which is why OpenFreight is never receiving the actual cart quantity.
            if ($product->getTypeId() == Configurable::TYPE_CODE) {
                $configurableItemId = $item->getId();
                $qtyConfigurable[$configurableItemId] = (int)$item->getQty();
            }

            if ($product->getTypeId() == Type::TYPE_BUNDLE) {
                $bundleItemId = $item->getId();
                $qtyBundle[$bundleItemId] = (int)$item->getQty();
            }

            $product = $objectManager->create('Magento\Catalog\Model\Product')->load($product->getId());

            // Don't add products that are marked as free shipping
            if ($product->getData('tig_free_shipping') == 0) {
                $parentItemId = $item->getParentItemId();

                if (array_key_exists($parentItemId, $qtyConfigurable)) {
                    $quantity = max([$qtyConfigurable[$parentItemId], (int)$item->getQty()]);
                } else {
                    $quantity = (int)$item->getQty();
                }

                $weight = $product->getWeight();

                if (array_key_exists($parentItemId, $qtyBundle)) {
                    $quantity = $qtyBundle[$parentItemId] * $quantity;
                }

                $packages[] = [
                    'qty'       => max(1, ceil($product->getData('tig_package_qty'))) * max(1, $quantity), //multiply package quantity by ordered quantity
                    'weight'    => $product->getData('tig_weight') > 0 ? $product->getData('tig_weight') : $weight,
                    'length'    => max(1, ceil($product->getData('tig_length'))),
                    'width'     => max(1, ceil($product->getData('tig_width'))),
                    'height'    => max(1, ceil($product->getData('tig_height'))),
                    'cube'    => max(1, ceil($product->getData('tig_volume'))),
                    'dg'        => $product->getData('tig_dg') == 0 ? false : true,
                    'sku'       => $product->getSku(),
                    'package'   => $product->getData('tig_package_type')
                ];
            }
        }

        // Only make API call if there are products without free shipping
        if (count($packages)) {
            // Get Details from config to make API Call
            $tig_postcode = trim((string)$this->getConfigData('sender_postcode'));
            $tig_suburb = trim((string)$this->getConfigData('sender_suburb'));
            $tig_cheapest = $this->getConfigData('cheapest');
            $repack_packages = $this->getConfigData('repack_packages');
            $markup_dollars = $this->getConfigData('markup_dollars');
            $markup_percent = $this->getConfigData('markup_percent');
            $add_transit_days = $this->getConfigData('add_transit_days');

            $data = $this->_getQuotes($packages, $receiver_address, $receiver_suburb, $receiver_postcode, $tig_endpoint, $tig_username, $tig_key, $tig_postcode, $tig_suburb, $repack_packages);
        }

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->_rateResultFactory->create();

        // Get data from API call if made otherwise set Free Shipping Method
        if (isset($data)) {
            if (isset($data->error_code)) {
                return null;
            } else {
                $new_data = $data->cost_estimate;
                $count = 1;

                foreach ($new_data as $value) {
                    $method = $this->_rateMethodFactory->create();

                    if (isset($value->est_ett)) {
                        $transit_days = $value->est_ett;
                        $transit_days = $transit_days + (int)$add_transit_days; // add x to number of days
                        $method_title = $value->service_name . " (Estimated Delivery Time: " . $transit_days . " day(s))";
                    } else {
                        $method_title = $value->service_name . " (No estimate available)";
                    }

                    /**
                     * Set carrier's method data
                     */
                    $method->setCarrier($this->getCarrierCode());
                    $method->setCarrierTitle($value->carrier_name);

                    /**
                     * Add Markup Dollars and Percent
                     */
                    // original cost
                    $openfreight_rate = $value->est_cost;

                    // add markup $$
                    $openfreight_rate = $openfreight_rate + (float)$markup_dollars;

                    // add markup %%
                    $openfreight_rate = (float)$markup_percent > 0 ? $openfreight_rate + ($openfreight_rate * ((float)$markup_percent / 100)) : $openfreight_rate;

                    /**
                     * Displayed as shipping method under Carrier
                     */
                    $method->setMethod($value->service_name . "_" . $count);
                    $method->setMethodTitle($method_title);
                    $method->setPrice($openfreight_rate);
                    $method->setCost($openfreight_rate);
                    $result->append($method);

                    // Only show the first (cheapest) option if cheapest is set to Yes
                    if ($count === 1 && $tig_cheapest == true) {
                        break;
                    }
                    $count++;
                }
            }
        } else {
            $method = $this->_rateMethodFactory->create();
            /**
             * Set carrier's method data
             */
            $method->setCarrier($this->getCarrierCode());
            $method->setCarrierTitle('Free Shipping');

            /**
             * Displayed as shipping method under Carrier
             */
            $method->setMethod('free');
            $method->setMethodTitle('Free Shipping');
            $method->setPrice(0);
            $method->setCost(0);
            $result->append($method);
        }

        return $result;
    }

    /**
     * Call OpenFreight API and return shipping methods
     *
     * @param Product Data       $packages
     * @param Receiver Suburb    $receiver_suburb
     * @param Receiver Postcode  $receiver_postcode
     * @param API Endpoint       $tig_endpoint
     * @param API Username       $tig_username
     * @param API Key            $tig_key
     * @param Sender Postcode    $tig_postcode
     * @param Sender Suburb      $tig_suburb
     * @return $data
     */
    public function _getQuotes($packages, $receiver_address, $receiver_suburb, $receiver_postcode, $tig_endpoint, $tig_username, $tig_key, $tig_postcode, $tig_suburb, $repack_packages)
    {

        $api_request = array(
            'request'           => 'GetCostEstimate',
            'username'          => $tig_username,
            'key'               => $tig_key,
            'estimate_date'     => date('Y-m-d'),
            'sender_town'       => $tig_suburb,
            'sender_postcode'   => $tig_postcode,
            'receiver_address'  => $receiver_address,
            'receiver_town'     => $receiver_suburb,
            'receiver_postcode' => $receiver_postcode,
            'service_code'      => null,
            'packages'          => $packages,
            'repack_packages'   => false
        );

        $json = json_encode($api_request, true);
        $this->_curl->post($tig_endpoint, $json);
        $data = json_decode($this->_curl->getBody());

        return $data;
    }


    public function _validateLocation($town, $postcode, $tig_endpoint, $tig_username, $tig_key)
    {
        $json = json_encode([
            "request" => "ValidateLocations",
            "username" => $tig_username,
            "key" => $tig_key,
            "locations" => [
                [
                    "town" => $town,
                    "postcode" => $postcode
                ]
            ]
        ]);

        $this->_curl->post($tig_endpoint, $json);

        $data = json_decode($this->_curl->getBody());

        if (isset($data->count->valid) && $data->count->valid == 1) {
            return true;
        }

        return false;
    }

    /**
     * Check Postcode and City in Database
     *
     * @param postcode       $postcode
     * @param city           $city
     * @param objectManager  $objectManager
     * @return $valid
     */
    public function _isValidAddress($city, $postcode, $objectManager)
    {
        $valid = false;

        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');

        $connection = $resource->getConnection();

        $select = $connection->select()
            ->from(
                ['tig' => 'tig_pcs'],
                ['city']
            )->where('postcode = ?', $postcode);

        $data = $connection->fetchAll($select);

        if (empty($data)) {
            return false;
        }

        // strip out all whitespace
        $city = preg_replace('/\s*/', '', $city);
        // convert the string to all lowercase
        $city = strtolower($city);

        foreach ($data as $value) {
            $db_value = $value['city'];

            if ($city == $db_value) {
                $valid = true;
                return $valid;
            }
        }

        return $valid;
    }
}
