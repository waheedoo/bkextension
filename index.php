<?php
/**
 * Created by PhpStorm.
 * User: waheed
 * Date: 25.06.17
 * Time: 19:45
 */

require(__DIR__.'/config.php');
//composer
$loader = require(__DIR__ . '/vendor/autoload.php');

require(__DIR__.'/vendor/bookingkit/BookingkitApi.php');
require(__DIR__.'/vendor/bookingkit/BkException.php');
use \bookingkit\BookingkitApi;

echo "Hello Newman!!";

//get all the shipments we have:
$trackings = new AfterShip\Trackings(AFTERSHIP_API_KEY);
$allTrackings = $trackings->get_all();
echo '<pre>';
print_r($allTrackings);

exit;
foreach($allTrackings as $t) {
    
}
echo '<pre>';
print_r($trackings->get_all());

//get all the orders from bookingkit
$client = new \bookingkit\BookingkitApi([
    'server'=>BOOKINGKIT_API_SERVER,
    'client_id'=>BOOKINGKIT_API_CLIENT,
    'client_secret'=>BOOKINGKIT_API_SECRET,
    'cachePath'=>__DIR__."/tmp",
    'scope'=>'orders_read_owned calendar_read'
]);

$bkOrders = $client->getOrders(['start_date' => '2017-06-01']);
foreach($bkOrders as $order) {
    if(!empty($order->products)) {
        echo '<pre>';
        print_r($order);
    }
}

//echo '<pre>';
//print_r($bkOrders);


