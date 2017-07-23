<?php

namespace app\controllers;

use AfterShip\Exception\AftershipException;
use Codeception\Codecept;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
require(__DIR__.'/../vendor/bookingkit/BookingkitApi.php');
require(__DIR__.'/../vendor/bookingkit/BkException.php');
use bookingkit\BookingkitApi;

use DHL\Entity\GB\ShipmentResponse;
use DHL\Entity\GB\ShipmentRequest;
use DHL\Client\Web as WebserviceClient;
use DHL\Datatype\GB\Piece;
use DHL\Datatype\GB\SpecialService;
require(__DIR__ . '/../../vendor/alfallouji/dhl_api/init.php');

class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        //aftership api key:
        define('AFTERSHIP_API_KEY', 'ffa96377-6fcc-47db-aa28-fe2548ad3f06');

        //bookingkit api client details:
        define('BOOKINGKIT_API_SERVER','http://lapi.bookingkit.de');
        define('BOOKINGKIT_API_CLIENT', 'vendor_3_client_id');
        define('BOOKINGKIT_API_SECRET', 'secret');

        define('TEMP_FOLDER',realpath(__DIR__.'/../tmp'));

        //get all the orders from bookingkit

        $client = new \bookingkit\BookingkitApi([
            'server'=>BOOKINGKIT_API_SERVER,
            'client_id'=>BOOKINGKIT_API_CLIENT,
            'client_secret'=>BOOKINGKIT_API_SECRET,
            'cachePath'=>__DIR__."/tmp",
            'scope'=>'orders_read_owned calendar_read'
        ]);

        $bkOrders = $client->getOrders(['start_date' => '2017-06-01']);
        //get all the shipments we have:
        $trackings = new \AfterShip\Trackings(AFTERSHIP_API_KEY);
        $allTrackings = $trackings->get_all();
//        echo '<pre>';
//        print_r($allTrackings['data']['trackings']);exit;
        $ordersTrackings = [];
        $ordersWithProducts = [];
        foreach($bkOrders as $order) {
            if(!empty($order->products)) {
                $ordersWithProducts[] = $order;
                foreach($allTrackings['data']['trackings'] as $t) {
                    if($t['order_id'] == $order->code) {
                        $ordersTrackings[$order->code] = $t;
                        break;
                    }
                }
            }
        }

        return $this->render('orders', ['orders' => $ordersWithProducts, 'trackings' => $ordersTrackings]);
    }

    public function actionCreateTracking() {
        if($_POST['order_id']) {
            $tracking = new \AfterShip\Trackings(AFTERSHIP_API_KEY);
            $response = $tracking->create($_POST['tracking_number'], ['order_id' => $_POST['order_id']]);
        }

        return $this->goHome();
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    public function actionDhl()
    {
        // DHL settings
        $dhl = array(
        // ID to use to connect to DHL
        'id' => 'waheed',

        // Password to use to connect to DHL
        'pass' => '0JBTr|6g2',

        // Shipper, Billing and Duty Account numbers
        'shipperAccountNumber' => 'YOUR_NUMBER',
        'billingAccountNumber' => 'YOUR_NUMBER',
        'dutyAccountNumber' => 'YOUR_NUMBER',
    );

        // Test a ShipmentRequest using DHL XML API
        $sample = new ShipmentRequest();
        //echo '<pre>';print_r($dhl['id']);exit;
        // Assuming there is a config array variable with id and pass to DHL XML Service
        $sample->SiteID = $dhl['id'];
        $sample->Password = $dhl['pass'];

        // Set values of the request
        $sample->MessageTime = '2001-12-17T09:30:47-05:00';
        $sample->MessageReference = '1234567890123456789012345678901';
        $sample->RegionCode = 'AM';
        $sample->RequestedPickupTime = 'Y';
        $sample->NewShipper = 'Y';
        $sample->LanguageCode = 'en';
        $sample->PiecesEnabled = 'Y';
        $sample->Billing->ShipperAccountNumber = $dhl['shipperAccountNumber'];
        $sample->Billing->ShippingPaymentType = 'S';
        $sample->Billing->BillingAccountNumber = $dhl['billingAccountNumber'];
        $sample->Billing->DutyPaymentType = 'S';
        $sample->Billing->DutyAccountNumber = $dhl['dutyAccountNumber'];
        $sample->Consignee->CompanyName = 'Ssense';
        $sample->Consignee->addAddressLine('333 Chabanel West, #900');
        $sample->Consignee->City = 'Montreal';
        $sample->Consignee->PostalCode = 'H3E1G6';
        $sample->Consignee->CountryCode = 'CA';
        $sample->Consignee->CountryName = 'Canada';
        $sample->Consignee->Contact->PersonName = 'Bashar Al-Fallouji';
        $sample->Consignee->Contact->PhoneNumber = '0435 336 653';
        $sample->Consignee->Contact->PhoneExtension = '123';
        $sample->Consignee->Contact->FaxNumber = '506-851-7403';
        $sample->Consignee->Contact->Telex = '506-851-7121';
        $sample->Consignee->Contact->Email = 'bashar@alfallouji.com';
        $sample->Commodity->CommodityCode = 'cc';
        $sample->Commodity->CommodityName = 'cn';
        $sample->Dutiable->DeclaredValue = '200.00';
        $sample->Dutiable->DeclaredCurrency = 'USD';
        $sample->Dutiable->ScheduleB = '3002905110';
        $sample->Dutiable->ExportLicense = 'D123456';
        $sample->Dutiable->ShipperEIN = '112233445566';
        $sample->Dutiable->ShipperIDType = 'S';
        $sample->Dutiable->ImportLicense = 'ALFAL';
        $sample->Dutiable->ConsigneeEIN = 'ConEIN2123';
        $sample->Dutiable->TermsOfTrade = 'DTP';
        $sample->Reference->ReferenceID = 'AM international shipment';
        $sample->Reference->ReferenceType = 'St';
        $sample->ShipmentDetails->NumberOfPieces = 2;

        $piece = new Piece();
        $piece->PieceID = '1';
        $piece->PackageType = 'EE';
        $piece->Weight = '5.0';
        $piece->DimWeight = '600.0';
        $piece->Width = '50';
        $piece->Height = '100';
        $piece->Depth = '150';
        $sample->ShipmentDetails->addPiece($piece);

        $piece = new Piece();
        $piece->PieceID = '2';
        $piece->PackageType = 'EE';
        $piece->Weight = '5.0';
        $piece->DimWeight = '600.0';
        $piece->Width = '50';
        $piece->Height = '100';
        $piece->Depth = '150';
        $sample->ShipmentDetails->addPiece($piece);

        $sample->ShipmentDetails->Weight = '10.0';
        $sample->ShipmentDetails->WeightUnit = 'L';
        $sample->ShipmentDetails->GlobalProductCode = 'P';
        $sample->ShipmentDetails->LocalProductCode = 'P';
        $sample->ShipmentDetails->Date = date('Y-m-d');
        $sample->ShipmentDetails->Contents = 'AM international shipment contents';
        $sample->ShipmentDetails->DoorTo = 'DD';
        $sample->ShipmentDetails->DimensionUnit = 'I';
        $sample->ShipmentDetails->InsuredAmount = '1200.00';
        $sample->ShipmentDetails->PackageType = 'EE';
        $sample->ShipmentDetails->IsDutiable = 'Y';
        $sample->ShipmentDetails->CurrencyCode = 'USD';
        $sample->Shipper->ShipperID = '751008818';
        $sample->Shipper->CompanyName = 'IBM Corporation';
        $sample->Shipper->RegisteredAccount = '751008818';
        $sample->Shipper->addAddressLine('1 New Orchard Road');
        $sample->Shipper->addAddressLine('Armonk');
        $sample->Shipper->City = 'New York';
        $sample->Shipper->Division = 'ny';
        $sample->Shipper->DivisionCode = 'ny';
        $sample->Shipper->PostalCode = '10504';
        $sample->Shipper->CountryCode = 'US';
        $sample->Shipper->CountryName = 'United States Of America';
        $sample->Shipper->Contact->PersonName = 'Mr peter';
        $sample->Shipper->Contact->PhoneNumber = '1 905 8613402';
        $sample->Shipper->Contact->PhoneExtension = '3403';
        $sample->Shipper->Contact->FaxNumber = '1 905 8613411';
        $sample->Shipper->Contact->Telex = '1245';
        $sample->Shipper->Contact->Email = 'test@email.com';

        $specialService = new SpecialService();
        $specialService->SpecialServiceType = 'A';
        $sample->addSpecialService($specialService);

        $specialService = new SpecialService();
        $specialService->SpecialServiceType = 'I';
        $sample->addSpecialService($specialService);

        $sample->EProcShip = 'N';
        $sample->LabelImageFormat = 'PDF';

        // Call DHL XML API
        $start = microtime(true);

        // Display the XML that will be sent to DHL
        echo $sample->toXML();

        // DHL webservice client using the staging environment
        $client = new WebserviceClient('staging');

        // Call the DHL service and display the XML result
        echo $client->call($sample);
        echo PHP_EOL . 'Executed in ' . (microtime(true) - $start) . ' seconds.' . PHP_EOL;
    }
}
