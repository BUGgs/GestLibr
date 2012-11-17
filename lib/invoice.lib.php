<?
define('FPDF_FONTPATH','font/');
require('class/invoice.class.php');

function generer_facture($id,$source)
  {
	global $babase;
	switch ($source)
	{
	case "internet":
    // Request
		$babase_internet = new DataBase();
		$babase_internet->DbInit(config("order_internet_username"), config("order_internet_password"), config("order_internet_server"), config("order_internet_database"));
		$babase_internet->DbConnect();
		$order = $babase_internet->DbSelect("SELECT o.datePaiement, o.id, o.dateCreation, o.payementType, o.shipPrice, o.totalPrice, c.company, c.name, c.firstname, c.address, c.postalcode, c.city, c.state, c.country FROM `order` as o, `customer` as c WHERE o.idCustomer=c.id  AND o.id='$id'",$nul);
		$order = $order[0];
		$tmp = $babase_internet->DbSelect("SELECT * FROM `country` WHERE id='" . $order["country"] . "'",$nul);
		$order["country"] = $tmp[0]["name"];
    $detail= $babase_internet->DbSelect("SELECT * FROM `order_details` WHERE idOrder='$id'",$nbDetail);
		$babase = new DataBase();
		$babase->DbConnect();
    break;
  case "gestlibr":
    // TODO
    // Request
    $detail=$babase->DbSelect("SELECT * FROM factures WHERE idCommandes='$id'",$nbDetail);
    $cmde  =$babase->DbSelect("SELECT * FROM commandes WHERE idCommande='".$detail[0][idCommandes]."'",$nbCmde);
    break;
  default:
    die("Erreur");
    break;
	}
  /************** Commande *****************/
	// Affichage du type de facture
	if ($order["datePaiement"]!=0)
	  {	
	    $type_facture = "FACTURE";
	    $etat = "Acquitée";
	  }
	  else
	  {
	    $type_facture = "FACTURE PRO FORMA";
	    $etat = "Non acquitée";
	  }
  // Définition des variables d'entete
  $ref      = $order["id"];
  $date     = date("d/m/Y", $order["dateCreation"]);
  $reglement= $order["payementType"];
  $reference= "int-".$order["id"];
	// Définition du commentaire en fin de facture
	switch ($order["payementType"])
	  {	
	  case "CHQ":
	    $remarque = "Cette facture devra avoir été réglée à sa date d'échéance, tout retard entraînera des pénalités de retard (taux de 1,5%/mois)";
	    break;
	  case "CB":
	    $remarque = "";
	    break;
	  default :
	    break;
	  }
  // Définition des informations Client
  $societe = $order["company"];
  $civilite= "";
  $nom     = $order["name"];
  $prenom  = $order["firstname"];
  $adresse = $order["address"];
  $cp      = $order["postalcode"];
  $ville   = $order["city"];
  $pays    = $order["state"] . "\n" . $order["country"];

	/************ Génération de la page pdf A4 *************/
  $pdf = new INVOICE( 'P', 'mm', 'A4' );
	
	/************ CADRE D'ENTETE *************/
  $pdf->Open();
  $pdf->AddPage();
  $pdf->addSociete( "LIBRAIRIE DUCHENE",
	                  "47 bis Chemin de Malepère\n" .
                    "31400 TOULOUSE\n".
                    "N° Siret : 32118816100046\n" .
                    "Code APE : 525Z"  );
  $pdf->fact_dev( "$type_facture", "$ref" );
  $pdf->temporaire( "$etat" );
  $pdf->addDate( "$date");
  //$pdf->addClient("$client"); // Cadre Client qui s'affiche à coté du cadre de la date
  $pdf->addPageNumber("1");
  $pdf->addClientAdresse("$societe\n$nom $prenom\n$adresse\n$adresse2\n$cp $ville\n$pays");
  $pdf->addReglement("$reglement");
  $pdf->addEcheance("$echeance");
  $pdf->addNumTVA("TVA non applicable - Article 293B du C.G.I. ");
  $pdf->addReference("$reference");
	
	/************ CADRE CENTRAL (AFFICHAGE DU DETAIL DE LA FACTURE) *************/
  $cols=array( "REFERENCE"    => 23,
               "DESIGNATION"  => 89,
               "QUANTITE"     => 22,
               "P.U. TTC"      => 26,
               "MONTANT TTC" => 30);
  $pdf->addCols( $cols);
  $cols=array( "REFERENCE"    => "L",
               "DESIGNATION"  => "L",
               "QUANTITE"     => "C",
               "P.U. TTC"      => "R",
               "MONTANT TTC" => "R");
  $pdf->addLineFormat($cols);

  $y    = 109;

  // Order details
  $i=0;
  $total = 0;
  while($i!=(sizeof($detail)))
  {
    $line = array( "REFERENCE"    => $detail[$i]["idGestlibr"],
                   "DESIGNATION"  => $detail[$i]["description"],
                   "QUANTITE"     => $detail[$i]["nb"],
                   "P.U. TTC"     => $detail[$i]["price"],
                   "MONTANT TTC"  => sprintf("%0.2f", $detail[$i]["nb"]*$detail[$i]["price"]));
    $size = $pdf->addLine( $y, $line );
    $y   += $size + 2;
    $i++;
  }
  $line = array( "REFERENCE"    => "PORT",
                 "DESIGNATION"  => "Frais de port & d'emballage",
                 "QUANTITE"     => "1",
                 "P.U. TTC"     => $order["shipPrice"],
                 "MONTANT TTC"  => $order["shipPrice"]);
  $size = $pdf->addLine( $y, $line );
  
	
	/************ CADRE DU RECAP TOTAL DES MONTANTS *************/
  $pdf->addCadreTVAs();
        
  $tot_prods = array( array ( "px_unit" => $order["totalPrice"], "qte" => 1, "tva" => 0 ) );
  $tab_tva = array( "1"       => 19.6,
                    "2"       => 5.5);
  $params  = array( "RemiseGlobale" => 0,  // 1 = une remise / 0 = Pas de remise
                      "remise_tva"     => 0,       // {la remise s'applique sur ce code TVA}
                      "remise"         => 0,       // {montant de la remise}
                      "remise_percent" => 0,      // {pourcentage de remise sur ce montant de TVA}
                  "FraisPort"     => 0,  // 1 = frais de port comptabilise / 0 = Pas de frais de port
                      "portTTC"        => 0,      // montant des frais de ports TTC
                                                   // par defaut la TVA = 19.6 %
                      "portHT"         => 0,       // montant des frais de ports HT
                      "portTVA"        => 19.6,    // valeur de la TVA a appliquer sur le montant HT
                  "AccompteExige" => 0,  // 1 = Accompte demandé / 0 = Pas d'accompte
                      "accompte"         => 0,     // montant de l'acompte (TTC)
                      "accompte_percent" => 15,    // pourcentage d'acompte (TTC)
                  "Remarque" => "$remarque" );

  $pdf->addTVAs( $params, $tab_tva, $tot_prods);
  $pdf->addCadreEurosFrancs();
  $pdf->Output();
}

?>
