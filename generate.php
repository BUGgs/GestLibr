<?php

/* PDF (catalog/label/listing) Generation Class
* v 1.0
* Romain DUCHENE
* 02 Sept 2005
*/

/* Calc exec time */
$execTimeStart = microtime();
session_start();

include "config.php";
include "lang/" . $config["lang"] . ".lang.php";
include "include/var.inc.php";
include "class/mysql.class.php";
include "lib/utils.lib.php";
include "lib/template.php";

$babase = new DataBase;
$babase->DbConnect();
global $babase;

include "lib/securite.lib.php";

// securing POST & GET var
reset ($_POST);
while (list ($key, $val) = each ($_POST)) {
    $_POST["$key"]=htmlspecialchars($val);
}
reset ($_GET);
while (list ($key, $val) = each ($_GET)) {
    $_GET["$key"]=htmlspecialchars($val);
}

switch($_GET["t"])
{
case "cat":
	// We want to create a catalog
	include "class/catalog.class.php";
	$catalog = new Catalog();

	if ($_POST['idCatalog'] != "")
		$catalog->idCatalog = $_POST['idCatalog'];
	else
		$catalog->idCatalog = $_GET['idCatalog'];

	switch($_GET["a"])
	{
	case "htmlview":
 		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr" dir="ltr">
		<head>
		<title>HtmlView</title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		</head>
		<body>';
		echo $catalog->generate('htmllist');
		echo '</body></html>';
		break;
	case "pdf":
		echo $catalog->generate('pdf');
		break;
	case "pdfloc":
		echo $catalog->generate('pdfloc');
		break;
	default:
		echo "ERROR";
		break;
	}
	break;
case "label":
	// We want to create a customers label listing
	switch($_GET["a"])
	{
	case "allactives":
	  // We include ALL ACTIVES CUSTOMERS !
		define('FPDF_FONTPATH','font/');
		require_once('class/pdf_label.php');
		include "class/customer.class.php";
		$customer = new Customer();
		$data=$customer->getAllActives();
		/*-------------------------------------------------
		Pour créer l'objet on a 2 moyens :
		Soit on donne les valeurs en les passant dans un tableau (sert pour un format personnel)
		Soit on donne le type d'étiquette au format AVERY
		-------------------------------------------------*/
		// Dans cet exemple on va commencer l'impression des étiquettes à partir de la seconde colonne (cf les 2 derniers paramètres 1 et 2)
		//$pdf = new PDF_Label(array('name'=>'perso1', 'marginLeft'=>1, 'marginTop'=>1, 'NX'=>2, 'NY'=>7, 'SpaceX'=>0, 'SpaceY'=>0, 'width'=>99.1, 'height'=>'38.1', 'metric'=>'mm', 'font-size'=>14), 1, 2);
//		$pdf = new PDF_Label('L7163', 'mm', 1, 1);
		$pdf = new PDF_Label('etiquettes-catalog', 'mm', 1, 1);
		$pdf->Open();
		$pdf->AddPage();
		$i=0;
		while($i!=sizeof($data))
		{
			$pdf->Add_PDF_Label(html_entity_decode($data[$i]["civility"]) . " " . html_entity_decode($data[$i]["name"]) . " " . html_entity_decode($data[$i]["firstname"]) . "\n" . html_entity_decode($data[$i]["organisation"]) .
			"\n" . html_entity_decode($data[$i]["address"]) . "\n" . html_entity_decode($data[$i]["postCode"]) . " " . html_entity_decode($data[$i]["city"]) . "\n" . html_entity_decode($data[$i]["country"]));
			$i++;
		}
		$pdf->Output();
		break;
	case "internet":
		$babase_internet = new DataBase();
		$babase_internet->DbInit(config("order_internet_username"), config("order_internet_password"), config("order_internet_server"), config("order_internet_database"));
		$babase_internet->DbConnect();
		settype($_GET["id"], 'integer');
		$data = $babase_internet->DbSelect("SELECT * FROM `order` as o, `customer` as c WHERE c.idGroup='".$_SESSION["idGroup"]."' o.idCustomer=c.id  AND o.id='" . $_GET["id"] . "'",$nul);
		$data = $data[0];
		$tmp = $babase_internet->DbSelect("SELECT * FROM `country` WHERE id='" . $data["country"] . "'",$nul);
		$data["country"] = $tmp[0]["name"];
		$address = html_entity_decode($data["name"]) . " " . html_entity_decode($data["firstname"]) . "<br>" . html_entity_decode($data["company"]) . "<br>" . html_entity_decode($data["address"]) . "<br>" . html_entity_decode($data["postalcode"]) . " " . html_entity_decode($data["city"]) . "<br>" . html_entity_decode($data["state"]) . "<br>" . html_entity_decode($data["country"]);

    echo '<html><head></head><body><table border="0" cellpadding="6" cellspacing="0" width="100%">
         <tr>

            <td colspan="2" align="center" style="border-bottom: 1px solid #000; border-top: 1px solid #000;"><span style="font-size: 100%; font-weight: bold;">
                  						Bordereau de livraison
                  					</span></td>
         </tr>
         <tr>
            <td>
               <p style="font-size: 80%; white-space: nowrap;">
                  				If undeliverable, return to:<br><br>Librairie Duch&ecirc;ne<br>47 bis chemin de Malep&egrave;re<br>31400&nbsp;

                  Toulouse<br>France
               </p>
            </td>
            <td align="right" valign="top">LibrairieDuchene.com</td>
         </tr>
         <tr>
            <td width="40%">&nbsp;</td>
            <td>
               <p><font size=+2><strong>'.$address.'</strong></font>
               </p>
            </td>
         </tr>
      </table>
      <p style="border-top: 1px solid #000; text-align: center; font-size: 80%;">
      </body>
      </html>';
		break;
	}
	break;
case "listing":
	// We want to create a listing
	switch($_GET["a"])
	{
	case "cat-order-actives":
	  // We include ALL ACTIVES CUSTOMERS and we make a list with Orders & Categories!
		include "class/customer.class.php";
		$customer = new Customer();
		$data=$customer->getAllActives();
 		$result='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr" dir="ltr">
		<head>
		<title>Gestlibr</title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		</head>
		<body>
		<table border=1>
		<tr>
		<td>' . $txt["name"] . '</td>
		<td>' . $txt["firstname"] . '</td>
		<td>' . $txt["organisation"] . '</td>
		<td>' . $txt["city"] . '</td>
		<td>' . $txt["country"] . '</td>
		<td>' . $txt["categories"] . '</td>
		<td>' . $txt["orders"] . '</td>
		</tr>';
		$i=0;
		while($i!=sizeof($data))
		{
			// We fecth categories
		  $tCat=$customer->listCategory($data[$i]["idCustomer"]);
			$j=0;
			$txtCat="";
			while($j!=sizeof($tCat))
			{
		 		$txtCat .= $tCat[$j]["name"] . "<br />";
			  $j++;
			}
			// We fecth orders
		  $tOrder=$customer->listOrder($data[$i]["idCustomer"]);
			$j=0;
			$txtOrder="";
			while($j!=sizeof($tOrder))
			{
		 		$txtOrder .= $tOrder[$j]["source"] . "<br />";
			  $j++;
			}
			$result.= "<tr><td align='left'><small>".$data[$i]["name"] . "</small></td><td align='left'><small>" . $data[$i]["firstname"] . "</small></td><td align='left'><small>" . $data[$i]["organisation"] .
			"</small></td><td align='left'><small>" . $data[$i]["city"] . "</small></td><td align='left'><small>" . $data[$i]["country"]."</small></td><td align='left'><small>$txtCat</small></td><td align='left'><small>$txtOrder</small></td></tr>";
			$i++;
		}
		$result.='</table></body></html>';

		// Report simple running errors
		error_reporting(E_ERROR | E_WARNING | E_PARSE);

		//define('FPDF_FONTPATH','font/');
		//require('html2pdf/html2fpdf.php');
		//$pdf=new HTML2FPDF();
		//$pdf->AddPage();
		//$pdf->WriteHTML($result);
		//$pdf->Output(); //Outputs on browser screen
		echo $result;
	}
	break;
case "invoice":
  include "lib/invoice.lib.php";
  if ($_GET["type"]=="internet")
    generer_facture($_GET["id"],"internet");
  else
    generer_facture($_GET["id"],"gestlibr");
  break;
}
?>
