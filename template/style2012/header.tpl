<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>{nomProg} version {verProg}</title>
<link rel="stylesheet" type="text/css" href="{TEMPLATE_FOLDER}/style.css" media="screen" />
<link type="text/css" href="{TEMPLATE_FOLDER}/menu.css" rel="stylesheet" />
<script type="text/javascript" src="{TEMPLATE_FOLDER}/jquery.js"></script>
<script type="text/javascript" src="{TEMPLATE_FOLDER}/menu.js"></script>
<script type="text/javascript" language="javascript">
<!--

// js form validation stuff
var errorMsg0   = '{FORM_INCOMPLETE}';
var errorMsg1   = '{FORM_NOT_NUMBER}';
var errorMsg2   = '{FORM_NOT_VALID_NUMBER}';
var confirmMsg  = '{FORM_CONFIRM}';
//-->
</script>

<script src="functions.js" type="text/javascript" language="javascript"></script>

<script language="javascript" type="text/javascript" src="tiny_mce/tiny_mce.js"></script>
<script language="javascript" type="text/javascript">
tinyMCE.init({
	mode : "exact",
	elements : "description-catalog",
	theme : "advanced",
	content_css : 'style_mce.css'
});
</script>
<script language="Javascript">
// Transfert une ligne de la liste Origine à la liste Destination
function TransfertListe(idOrigine, idDestination)
{	var objOrigine = document.getElementById(idOrigine);
	var objDestination = document.getElementById(idDestination);
	if (objOrigine.options.selectedIndex<0) return false;
	if (VerifValeurDansListe(idDestination, objOrigine.options[objOrigine.options.selectedIndex].value, true)) return false;
	var ADeplacer = new Option(objOrigine.options[objOrigine.options.selectedIndex].text, objOrigine.options[objOrigine.options.selectedIndex].value);
	objDestination.options[objDestination.length]=ADeplacer;
	objOrigine.options[objOrigine.options.selectedIndex]=null;
}

// Vérifie la présence de Valeur dans IdListe
function VerifValeurDansListe(IdListe, Valeur, blnAlerte) {
	var objListe = document.getElementById(IdListe);
	for (i=objListe.length-1;i>=0;i--) if (objListe.options[i].value == Valeur) {if (blnAlerte) alert('Déjà présent.'); return true;}
	return false;
}
</script>
</head>
<body>
<!-- DEBUT DU SCRIPT -->
<STYLE TYPE="text/css">
<!--   
#cache {
    position:absolute; top:200px; z-index:10; visibility:hidden;
}
-->
</STYLE>
<DIV ID="cache"><TABLE WIDTH=400 BGCOLOR=#000000 BORDER=0 CELLPADDING=2 CELLSPACING=0><TR><TD ALIGN=center VALIGN=middle><TABLE WIDTH=100% BGCOLOR=#D7DDE6 BORDER=0 CELLPADDING=0 CELLSPACING=0><TR><TD ALIGN=center VALIGN=middle><FONT FACE="Verdana" SIZE=4 COLOR=#000000><B><I><BR>Veuillez patienter pendant la prise en compte des informations<BR><IMG SRC='{TEMPLATE_FOLDER}/images/await.gif' ALT='Chargement'><BR><BR></I></B></FONT></TD>  </TR></TABLE></TD>  </TR></TABLE></DIV>

<SCRIPT LANGUAGE="JavaScript">
/*
SCRIPT EDITE SUR L'EDITEUR JAVASCRIPT
http://www.editeurjavascript.com
*/
var nava = (document.layers);
var dom = (document.getElementById);
var iex = (document.all);
if (nava) { cach = document.cache }
else if (dom) { cach = document.getElementById("cache").style }
else if (iex) { cach = cache.style }
largeur = screen.width;
cach.left = Math.round((largeur/2)-200);
cach.visibility = "visible";

function cacheOff()
	{
	cach.visibility = "hidden";
	}
window.onload = cacheOff
</SCRIPT>
<!-- FIN DU SCRIPT -->
<div id="menu">
    <ul class="menu">
        <li><a href="?" class="parent"><span>Accueil</span></a>
        </li>
        <li><a href="?m=cl&a=vi" class="parent"><span>Clients</span></a>
            <ul>
                <li><a href="?m=cl&a=cr"><span>Création</span></a></li>
                <li><a href="?m=cl&a=vi"><span>Rechercher</span></a></li>
            </ul>
        </li>
        <li><a href="?m=bo&a=vi" class="parent"><span>Livres</span></a>
            <ul>
                <li><a href="?m=bo&a=cr"><span>Création</span></a></li>
                <li><a href="?m=bo&a=vi"><span>Rechercher</span></a></li>
                <li><a href="?m=bo&a=ci"><span>Import ISBN</span></a></li>
                <li><a href="?m=bo&a=io"><span>ISBN en ligne</span></a></li>
            </ul>
        </li>
        <li><a href="?m=ca&a=vi" class="parent"><span>Catalogues</span></a>
            <ul>
                <li><a href="?m=ca&a=cr"><span>Création</span></a></li>
                <li><a href="?m=ca&a=vi"><span>Liste</span></a></li>
            </ul>
        </li>
        <li><a href="?m=or&a=in" class="parent"><span>Commandes</span></a>
            <ul>
                <li><a href="?m=or&a=cr"><span>Création</span></a></li>
                <li><a href="?m=or&a=vi"><span>Rechercher</span></a></li>
                <li><a href="?m=or&a=in"><span>Site Internet</span></a></li>
            </ul>
        </li>
        <li><a href="?m=ct&a=vi" class="parent"><span>Rubriques</span></a>
            <ul>
                <li><a href="?m=ct&a=cr"><span>Création</span></a></li>
                <li><a href="?m=ct&a=vi"><span>Liste</span></a></li>
            </ul>
        </li>
        <li><a href="?m=ed&a=cl" class="parent"><span>Editions</span></a>
            <ul>
                <li><a href="?m=ed&a=cl"><span>Clients</span></a></li>
            </ul>
        </li>
        <li><a href="?m=in&a=sy" class="parent"><span>Internet</span></a>
            <ul>
                <li><a href="?m=in&a=eb"><span>Ebay</span></a></li>
                <li><a href="?m=in&a=ex"><span>Exclusions de Catalogue</span></a></li>
                <li><a href="?m=in&a=co"><span>Configuration</span></a></li>
                <li><a href="?m=in&a=se"><span>Sélection Rubriques</span></a></li>
                <li><a href="?m=in&a=sy"><span>Synchronisation</span></a></li>
            </ul>
        </li>
        <li class="last"><a href="?m=lo"><span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;( {USERNAME} / {GROUPNAME} ) Déconnexion</span></a></li>
    </ul>
</div>
<div class="pageContent">
    <div id="main">
        <div class="container">
<!-- Fin header.tpl -->
