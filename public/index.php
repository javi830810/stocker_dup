<?php

use Stocker\Http\GuzzleClientFactory;

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

$config = require 'config/local.php';

$client = GuzzleClientFactory::create('tradier', $config);

$result = $client->request(
    'GET',
    'v1/markets/quotes',
    [
        'query' => [
            'symbols' => implode(',',$config['stocks']['interested'])
        ]
    ]
);


$stocks = json_decode((string)$result->getBody(),true);

$calculated = [];
$unableToCalculate = [];

echo "Calculating factor for: ";
foreach ($stocks['quotes']['quote'] as $stock) {
    $symbol =  $stock['symbol'];
    $price =   $stock['last'];

    echo $symbol.',';

    $optionsResponse = $client->request(
        'GET',
        'v1/markets/options/chains',
        [
            'query' => [
                'symbol' => $symbol,
                'expiration' => $config['stocks']['optionExpiration']
            ]
        ]
    );

    if( ((int)$optionsResponse->getHeaders()["X-Ratelimit-Available"][0]) < 5){
        sleep(5);
    };

    $options = json_decode((string)$optionsResponse->getBody(),true);
    $selectedCallPrice = PHP_INT_MAX; //some stupid initialization :)
    $premium = 0;
    if(isset($options['options']['option'])){
        foreach ($options['options']['option'] as $option) {
            if($option['option_type'] == 'call' && $option['strike'] > $price && $option['strike'] < $selectedCallPrice){
                $selectedCallPrice = $option['strike'];
                $premium = $option['last'];
            }
        }


        $calculated[$symbol] = [
            'price' => $price,
            'factor' => $premium/$selectedCallPrice,
            'strike' => $selectedCallPrice,
            'premium' => $premium
        ];
    }else{
        $unableToCalculate[$symbol] = "Unable to find options for this stock";
    }

}

echo PHP_EOL.PHP_EOL;



//Method1: sorting the array using the usort function and a "callback that you define"
function byFactor($a,$b)
{
    return ($a["factor"] <= $b["factor"]) ? 1 : -1;
}
uasort($calculated, "byFactor");




foreach ($calculated as $symbol => $values) {
    echo sprintf("Stock: %s, Factor: %s, Price: %s", $symbol, $values['factor'], $values['price']). PHP_EOL;
}

echo PHP_EOL.PHP_EOL;

echo "Unable to calculate: ".PHP_EOL;

foreach ($unableToCalculate as $symbol => $values) {
    echo sprintf("Stock: %s, Reason: %s", $symbol, $values). PHP_EOL;
}


