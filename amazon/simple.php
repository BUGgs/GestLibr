<?php
/*
 * Copyright 2011 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 
</head>

<body onload="document.forms[0].isbn.focus();">
  
<form action="simple.php" method="get" name="recherche">
  ISBN: <input type="text" size="13" name=isbn>
  <input type=submit value="Rechercher">
</form>

<?php

if (isset($_GET["isbn"]))
{
  require_once '../lib/google-api-php-client/src/apiClient.php';
  require_once '../lib/google-api-php-client/src/contrib/apiBooksService.php';

  $client = new apiClient();
  $client->setApplicationName("My_Books_API_Example");
  $service = new apiBooksService($client);

  $optParams = array('filter' => 'full');
  $results = $service->volumes->listVolumes('isbn:'.$_GET["isbn"]);
  //$results = $service->volumes->listVolumes('isbn:2266199544', $optParams);

  if (sizeof($results['items']))
  {
    foreach ($results['items'] as $item) {
      print('Titre: ' . $item['volumeInfo']['title'] . ' - ' . $item['volumeInfo']['subtitle'] . ' <br />Auteur: ' . $item['volumeInfo']['authors'][0] . ' <br />
            Editeur: ' . $item['volumeInfo']['publisher'] . ' <br /> Date: ' . $item['volumeInfo']['publishedDate'] . ' <br />
            Pages: ' . $item['volumeInfo']['pageCount'] . '<br />Description: ' . $item['volumeInfo']['description'] . ' <br />
            <a href="' . $item['volumeInfo']['infoLink'] . '" target="_blank"><img src="' . $item['volumeInfo']['imageLinks']['thumbnail'] . '" border=0>
            <br>Infos</a><hr>');
    }
    echo "Données brutes :<br /><pre>";
    print_r($results['items']);
    echo "</pre>";
  }
  else
    echo "Pas de Résultat";
}

echo "</body>  
</html>";