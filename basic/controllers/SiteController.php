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

        //get all the shipments we have:
//        $trackings = new \AfterShip\Trackings(AFTERSHIP_API_KEY);
//        $allTrackings = $trackings->get_all();
//        echo '<pre>';
//        print_r($allTrackings);

        return $this->render('orders', ['orders' => $ordersWithProducts, 'trackings' => $ordersTrackings]);



        exit;
        return $this->render('index');
    }

    public function actionCreateTracking() {
        if($_POST['tracking_number'] && $_POST['order_id']) {
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
}
