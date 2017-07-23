<?php
include('dhl-php-sdk.php');

// your customer and api credentials from/for dhl
$credentials = array(
    'user' => 'geschaeftskunden_api', 
    'signature' => 'Dhl_ep_test1', 
    'ekp' => '5000000000',
    'api_user'  => 'waheed',
    'api_password'  => '0JBTr|6g2',
    'log' => true
    );


// your company info
$info = array(
    'company_name'    => 'Kindehochdrei GmbH',
    'street_name'     => 'Clayallee',
    'street_number'   => '241',
    'zip'             => '14165',
    'country'         => 'germany',
    'city'            => 'Berlin',
    'email'           => 'bestellung@kindhochdrei.de',
    'phone'           => '01788338795',
    'internet'        => 'http://www.kindhochdrei.de',
    'contact_person'  => 'Nina Boeing'
    
);


// receiver details
$customer_details = array(
    'first_name'    => 'Hello',
    'last_name'     => 'Newman!',
    'c/o'           => 'Dill',
    'street_name'   => 'Hocksteinweg',
    'street_number' => '1',
    'country'       => 'germany',
    'zip'           => '10551',
    'city'          => 'Berlin'
);


$dhl = new DHLBusinessShipment($credentials, $info);

$response = $dhl->createNationalShipment($customer_details);

if($response !== false) {
  
  var_dump($response);
  
} else {
  
  var_dump($dhl->errors);
  
}

?>