<?php

namespace Scanpay\PaymentModule\Controller\Index;
use Scanpay\PaymentModule\Model\ScanpayClient;
use Scanpay\PaymentModule\Model\Money;

class GetPaymentURL extends \Magento\Framework\App\Action\Action
{
    protected $request;
    protected $order;
    protected $quote;
    protected $checkoutSession;
    protected $scopeConfig;
    protected $crypt;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Sales\Model\Order $order,
        \Magento\Quote\Model\Quote $quote,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Encryption\Encryptor $crypt
    ) {
        parent::__construct($context);
        $this->request = $request;
        $this->order = $order;
        $this->quote = $quote;
        $this->checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
        $this->crypt = $crypt;
    }

    public function execute() {
        $order = $this->order->load($this->request->getParam('orderid'));
        if (!$order->getId()) {
            echo json_encode(['error' => 'order not found'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $orderid = $order->getIncrementId();
        
        /* Restore shopping cart (i.e. the quote) */
        $quote = $this->quote->loadByIdWithoutStore($order->getQuoteId());
        $quote->setIsActive(1)->setReservedOrderId(null)->save();
        $this->checkoutSession->replaceQuote($quote);

        $billaddr = $order->getBillingAddress();
        $shipaddr = $order->getShippingAddress();

        $data = [
            'orderid' => $orderid,
            'items'   => [],
        ];

        /* Add billing address to data */
        if (!empty($billaddr)) {
            if (!isset($data['address'])) { $data['address'] = []; }
            $data['address']['billing'] = array_filter([
                'name'    => $billaddr->getName(),
                'email'   => $billaddr->getEmail(),
                'phone'   => preg_replace('/\s+/', '', $billaddr->getTelephone()),
                'street'  => implode(', ', $billaddr->getStreet()),
                'city'    => $billaddr->getCity(),
                'zip'     => $billaddr->getPostcode(),
                'country' => $billaddr->getCountryId(),
                'state'   => $billaddr->getRegion(),
                'company' => $billaddr->getCompany(),
                'vatin'   => $billaddr->getVatId(),
            //  'gln'     => ?,
            ]);
        }

        /* Add shipping address to data */
        if (!empty($shipaddr)) {
            if (!isset($data['address'])) { $data['address'] = []; }
            $data['address']['shipping'] = array_filter([
                'email'   => $shipaddr->getEmail(),
                'phone'   => preg_replace('/\s+/', '', $billaddr->getTelephone()),
                'street'  => implode(', ', $billaddr->getStreet()),
                'city'    => $shipaddr->getCity(),
                'zip'     => $shipaddr->getPostcode(),
                'country' => $shipaddr->getCountryId(),
                'state'   => $shipaddr->getRegion(),
                'company' => $shipaddr->getCompany(),
            ]);
        }

        /* Add ordered items to data */
        $cur = $order->getOrderCurrencyCode();
        $orderItems = $this->order->getAllItems();

        $tot = 0;
        foreach ($orderItems as $item) {

            $itemprice = $item->getPrice() + ($item->getTaxAmount() - $item->getDiscountAmount()) / $item->getQtyOrdered();
            if ($itemprice < 0) {
                error_log('Cannot handle negative price for item');
                echo json_encode(['error' => 'internal server error']);
                return;
            }
            $tot += $itemprice * $item->getQtyOrdered();
            array_push($data['items'], [
                'name' => $item->getName(),
                'quantity' => intval($item->getQtyOrdered()),
                'sku' => $item->getSku(),
                'price' => (new Money($itemprice, $cur))->print(),
            ]);
        }
        $shipprice = $order->getShippingAmount() + $order->getShippingTaxAmount() - $order->getShippingDiscountAmount();
        if ($shipprice > 0) {
            array_push($data['items'], [
                'name' => 'Shipping: ' . $order->getShippingDescription(),
                'quantity' => 1,
                'price' => (new Money($shipprice, $cur))->print(),
            ]);
            $tot += $shipprice;
        }
        $apikey = trim($this->crypt->decrypt($this->scopeConfig->getValue('payment/scanpaypaymentmodule/apikey')));
        if (empty($apikey)) {
            error_log('Missing API key in scanpay payment method configuration');
            echo json_encode(['error' => 'internal server error']);
            return;
        }
        $client = new ScanpayClient([
            'host' => 'api.scanpay.dk',
            'apikey' => $apikey,
        ]);
        $paymenturl = '';
        try {
            $paymenturl = $client->GetPaymentURL($data, ['cardholderIP' => $_SERVER['REMOTE_ADDR']]);
        } catch (\Exception $e) {
            error_log('scanpay client exception: ' . $e->getMessage());
            echo json_encode(['error' => 'internal server error']);
            return;
        }
        $order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $order->save();
        echo json_encode(['url' => $paymenturl], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

}