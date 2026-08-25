<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>{nomProg} version {verProg}</title>
<LINK REL="stylesheet" HREF="style.css" TYPE="text/css">
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
	elements : "description,notes",
	theme : "simple",
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
<DIV ID="cache"><TABLE WIDTH=400 BGCOLOR=#000000 BORDER=0 CELLPADDING=2 CELLSPACING=0><TR><TD ALIGN=center VALIGN=middle><TABLE WIDTH=100% BGCOLOR=#D7DDE6 BORDER=0 CELLPADDING=0 CELLSPACING=0><TR><TD ALIGN=center VALIGN=middle><FONT FACE="Verdana" SIZE=4 COLOR=#000000><B><I><BR>Veuillez patienter pendant la prise en compte des informations<BR><IMG SRC='images/await.gif' ALT='Chargement'><BR><BR></I></B></FONT></TD>  </TR></TABLE></TD>  </TR></TABLE></DIV>

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
<center><img src='images/gestlibr.gif' height='45' alt='Gestlibr'></center>
<table cellpadding="0" cellspacing="0">
<tr><td align='left' valign='top' width='180'>
<!-- Fin header.tpl -->
