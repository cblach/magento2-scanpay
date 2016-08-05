<?php

namespace Scanpay\PaymentModule\Controller\Index;
use Scanpay\PaymentModule\Model\ScanpayClient;

class GetPaymentURL extends \Magento\Framework\App\Action\Action
{
    protected $request;
    protected $order;
    protected $quote;
    protected $checkoutSession;
    protected $resultJsonFactory;
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Sales\Model\Order $order,
        \Magento\Quote\Model\Quote $quote,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->request = $request;
        $this->order = $order;
        $this->quote = $quote;
        $this->checkoutSession = $checkoutSession;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    public function execute() {
        echo 'start';
        $result = $this->resultJsonFactory->create();

        $order = $this->order->load($this->request->getParam('order'));
        echo $this->request->getParam('order') . "<br>";
        echo "id: [" . $order->getId() . ']';
        return;
        if (!$order->getId()) {
            return $result->setData(['error' => 'order not found']);
        }
        
        $orderItems = $this->order->getAllItems();
        foreach ($orderItems as $item) {
            echo $item->getProductId();
        }

        $quoteItems = $this->quote->getAllItems();
        foreach ($quoteItems as $item) {
            echo $item->getProductId();
        }
        echo 'done';
        return;
        //echo print_r($this->order);
        //echo print_r($this->quote);

        /*
        if (!$this->getRequest()->isPost()) {
            return;
        }
        */

        /* Create a scanpay client */
        $client = new ScanpayClient([
            'host' => 'api.scanpay.dk',
            'apikey' => '65:CzutXxU09RHXUSWqVoonTyPq/YTTchFffpcYnCv+ckeJiS2olDxC0ZNzUGWQnLIm',
        ]);

        /* Define the items purchased */
        $items = [
            [
                'name' => 'Pink Floyd: The Dark Side Of The Moon',
                'quantity' => 2,
                'price' => '99.99 DKK',
            ],
            [
                'name' => 'Dwarf Lemon Tree',
                'quantity' => 27,
                'price' => '800.1 DKK',
            ],
            [
                'name' => '巨人宏偉的帽子',
                'quantity' => 2,
                'price' => '420 DKK',
            ],
        ];

        $billingaddress = [
            'company' => 'The Shop A/S',
            'vatin' => '35413308',
            'gln' => '7495563456235',
            'name' => 'John Doe',
            'email' => 'john@doe.com',
            'phone' => '+4512345678',
            'street' => 'Langgade 23, 2. th',
            'city' => 'Havneby',
            'zip' => '1234',
            'country' => 'DK',
            'state' => '',
        ];

        $shippingaddress = [
            'company' => 'The Choppa A/S',
            'name' => 'Jan Dåh',
            'email' => 'jan@doh.com',
            'phone' => '+4587654321',
            'street' => 'Langgade 23, 1. th',
            'city' => 'Haveby',
            'zip' => '1235',
            'country' => 'DK',
            'state' => '',
        ];
        /* Construct the data object sent for URL generation */
        $data = [
            'orderid' => 'a766409',
            'ip' => $_SERVER['REMOTE_ADDR'],
            'items'   => $items,
            'address' => [
                'billing' => $billingaddress,
                'shipping' => $shippingaddress,
            ],
        ];



        try {
            $paymenturl = $client->GetPaymentURL($data);
        } catch (\Exception $e) {
            //die('Caught exception: ' . $e->getMessage() . "\n");
            return $result->setData(['error' => $e->getMessage()]);
        }
        return $result->setData(['url' => $paymenturl]);

        //$this->getRequest()->getParam('');
        /*
        if ($this->getRequest()->isAjax())) {
            $test = Array
            (
                'Firstname' => 'What is your firstname',
                'Email' => 'What is your emailId',
                'Lastname' => 'What is your lastname',
                'Country' => 'Your Country'
            );
            return $result->setData($test);
        }
        */
    }
}