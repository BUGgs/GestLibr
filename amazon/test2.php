<?php

/* PDF (catalog/label/listing) Generation Class
* v 1.0
* Romain DUCHENE
* 02 Sept 2005
*/

/* Calc exec time */
$execTimeStart = microtime();
session_start();

require_once 'Zend/Service/Amazon.php';

$amazon = new Zend_Service_Amazon('AKIAIQYCRTM4EXUOHATQ', 'FR', 'JHXcX8TaQqyC9TVRTTNQriLwyoXcgiqo6ouAkqWF');

$response = $amazon->itemSearch(array('SearchIndex' => 'Books',
                                      'Keywords' => 'php'));
$results = $amazon->itemSearch(array('SearchIndex' => 'Books',
                                     'Keywords' => 'php'));
foreach ($results as $result) {
    echo $result->Title . '<br />';
}

?>
