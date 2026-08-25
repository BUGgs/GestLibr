<?php
/**
 * * * *    GestLibr: import.php     *
 * * * *   Import/conversion script  *
 * * * *  Romain DUCHENE, May 2005   *
 */

/*
Pour effacer toutes les donn�es AVANT migration :
TRUNCATE `book` ;
TRUNCATE `bookincategory` ;
TRUNCATE `catalog` ;
TRUNCATE `catalogdata` ;
TRUNCATE `customer` ;
TRUNCATE `customerincategory` ;
TRUNCATE `order` ;
TRUNCATE `orderdetails` ;
TRUNCATE `orderstatus` ;
TRUNCATE `payement` ;
TRUNCATE `shipment` ;
*/

die();

/* Calc exec time */
function getmicrotime(){
    list($usec, $sec) = explode(" ",microtime());
    return ((float)$usec + (float)$sec);
    }

$time_start = getmicrotime();


$import_livres = 0;
$import_catalogues = 0;
$import_clients = 0;


session_start();

include "config.php";
include "include/var.inc.php";
include "lib/mysqli.lib.php";

$babase = new DataBase;
$babase->DbConnect();
global $babase;

include "class/book.class.php";
$book = new Book();
include "class/category.class.php";
$category = new Category();
include "class/customer.class.php";
$customer = new Customer();
include "class/catalog.class.php";
$catalog = new Catalog();
include "class/order.class.php";
$order = new Order();

echo "<P>*************************************************************************************";
echo "<br>*                          IMPORTING TOOLS v1.0 R. Duchene                          *";
echo "<br>*************************************************************************************</P>";


if ($import_livres==1)
{
// On select tous les ouvrages et on importe !
echo "<P>*************************************************************************************";
echo "<br>*                          IMPORTING BOOKS AND CATEGORIES                           *";
echo "<br>*************************************************************************************</P>";

$ouvrage=$babase->DbSelect("SELECT * FROM ouvrages ORDER BY `Auteur`",$nb);
$i=0;
while ($i!=$nb)
{
  // Import des fiches brutes
  $book->data["author"] = $ouvrage[$i]["Auteur"];
  $book->data["title"] = $ouvrage[$i]["Titre"];
  $book->data["description"] = $ouvrage[$i]["Description"];
  $book->data["price"] = $ouvrage[$i]["Prix en francs"];
  $book->data["quantity"] = $ouvrage[$i]["Disponible"];
  $book->data["notes"] = $ouvrage[$i]["Notes"];
  $id=$book->add();
  // Ajout dans la cat�gorie
  $book->addCategory($ouvrage[$i]["Rubrique"]);
  $cat=$book->listCategory();
  // Import et cr�ation des anciens catalogues
  $catParu = explode(' ', $ouvrage[$i]["Cat paru"]);
  $dansCat="";
  foreach($catParu as $value){
    $dataCat=$babase->DbSelect("SELECT * FROM catalog WHERE name like 'Catalogue $value'",$nbCat);
    if ($nbCat==0)
      $catalog->idCatalog=$catalog->create('Catalogue '.$value,"Ancien catalogue import�", 'Y');
    else
      $catalog->idCatalog=$dataCat[0]["idCatalog"];
    $idCatalogData=$catalog->addBook($id);
    $dansCat .= $value." (id ".$idCatalogData.") | ";
  }
  // Affichage debug
  echo "Auteur : ".$ouvrage[$i]["Auteur"]."<br>Titre : ".$ouvrage[$i]["Titre"]."<br>ID : $id / Category : ".$cat[0]["name"]."<br />Dans catalog : $dansCat<hr>";
  $i++;
}
}

if ($import_catalogues==1)
{
echo "<P>*************************************************************************************";
echo "<br>*                          IMPORTING CATALOGS                                       *";
echo "<br>*************************************************************************************</P>";
$catList=$catalog->getList();
$i=0;
while($i!=(sizeof($catList)))
{
	$catalog->idCatalog=$catList[$i]["idCatalog"];
	$cat=$catalog->get();
	echo "Lock en cours de : " . $cat["name"] . "...";
	$catalog->lock();
	echo "OK !<br />";
	$i++;
}
}

if ($import_clients==1)
{
// On select tous les clients et on importe !
echo "<P>*************************************************************************************";
echo "<br>*                          IMPORTING CUSTOMERS                                      *";
echo "<br>*************************************************************************************</P>";
$client=$babase->DbSelect("SELECT * FROM clients ORDER BY `Nom`",$nb);
$i=0;
while ($i!=$nb)
{
  $customer->data["civility"] = $client[$i]["MrMme"];
  $customer->data["name"] = $client[$i]["Nom"];
  $customer->data["firstname"] = $client[$i]["Pr�nom"];
  $customer->data["city"] = $client[$i]["Ville"];
  $customer->data["postCode"] = $client[$i]["Code Postal"];
  $adress=$client[$i]["Adresse"];
  if ($client[$i]["Adresse2"]!="")
  $adress.="\n".$client[$i]["Adresse2"];
  if ($client[$i]["Adresse3"]!="")
  $adress.="\n".$client[$i]["Adresse3"];
  $customer->data["address"] = $adress;
  $customer->data["country"] = $client[$i]["Nation"];
  $customer->data["notes"] = $client[$i]["Liste de recherche"].$client[$i]["Notes"];
  $customer->data["telephone"] = $client[$i]["Telephone"];
  $customer->data["telephone2"] = $client[$i]["Telephone2"];
  $customer->data["fax"] = $client[$i]["Fax"];
  $customer->data["email"] = $client[$i]["e-mail"];
  $customer->data["active"] = $client[$i]["Actif"];
  
  $id=$customer->add(strtotime($client[$i]["Date d'entr�e"]));
  $customer->idCustomer=$id;
	// Ajout des Centre d'int�ret
	// TODO : savoir importer 'TOUT' ou 'TOUS'
  $interet = explode(' ', $client[$i]["Centre d'int�ret"]);
  $centreInteret="";
  foreach($interet as $value){
    $value=addslashes($value);
    $categ=$babase->DbSelect("SELECT * FROM category WHERE `idCategory` like '$value'",$nbCat);
    if ($nbCat==0)
      $centreInteret .= "CATEGORIE INCONNUE ($value)| ";
    else
    {
  		$customer->addCategory($value);
		}
  }
  $liste=$customer->listCategory();
	$j=0;
	while($j!=(sizeof($liste)))
	{
    $centreInteret .= $liste[$j]["name"] . " | ";
	  $j++;
	}

	// Ajout des achats catalogues
  $catAchat = explode(' ', $client[$i]["Achat Catalogue"]);
  $achatDansCat="";
  foreach($catAchat as $value){
    $dataCat=$babase->DbSelect("SELECT * FROM catalog WHERE name like 'Catalogue $value'",$nbCat);
    if ($nbCat==0)
      $achatDansCat .= "CATALOGUE INCONNU ($value)| ";
    else
    {
      $order->idOrder=$order->create($id,"N/A","import migration");
      // A REFAIRE... le type n'est pas bon !
      $order->addCatalogDetail($dataCat[0]["idCatalog"],"0","1");
      $order->close();
    	$achatDansCat .= "$value | ";
		}
  }

  echo "Nom : ".$client[$i]["Nom"]."<br>Pr�nom : ".$client[$i]["Pr�nom"]."<br>ID : $id<br />
	Achat dans catalogue : $achatDansCat<br />
	Cat�gories : $centreInteret<hr>";
  $i++;
}
}

/* Calc exec time */
$time_end = getmicrotime();
$time = $time_end - $time_start;
echo "<p>FIN<br>Temps d'execution : $time</p>";
?>
