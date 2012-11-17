<?php
/**
 * * * *      GestLibr: mini.lib.php      			*
 * * * *           Mini Lib               			*
 * * * *  Romain DUCHENE, jan 2006 - ?				 	*
 */

include "class/book.class.php";
$book = new Book();
include "class/catalog.class.php";
$catalog = new Catalog();
include "class/category.class.php";
$category=New Category();
include "class/order.class.php";
$order=New Order();

switch ($a) {
    case "cl":
    
      break;
    default:
			?>
			<script type="text/javascript">
<!--
    document.writeln('<frameset cols="150,*" rows="*" border="1" frameborder="1" framespacing="0">');
    document.writeln('    <frameset rows="*, 50" framespacing="0" frameborder="0" border="0">');
    document.writeln('        <frame src="left.php?lang=fr-iso-8859-1&amp;server=1&amp;hash=475a2c3920cdf33abf66b5b4206c7d661138565697" name="nav" frameborder="0" />');
    document.writeln('        <frame src="queryframe.php?lang=fr-iso-8859-1&amp;server=1&amp;hash=475a2c3920cdf33abf66b5b4206c7d661138565697" name="queryframe" frameborder="0" scrolling="no" />');
    document.writeln('    </frameset>');
    document.writeln('    <frame src="main.php?lang=fr-iso-8859-1&amp;server=1" name="phpmain475a2c3920cdf33abf66b5b4206c7d661138565697" border="0" frameborder="0" />');
    document.writeln('    <noframes>');
    document.writeln('        <body bgcolor="#FFFFFF">');
    document.writeln('            <p>L\'utilisation de phpMyAdmin est plus aisée avec un navigateur <b>supportant les "frames"</b>.</p>');
    document.writeln('        </body>');
    document.writeln('    </noframes>');
    document.writeln('</frameset>');
//-->
</script>
<?
      break;
} 

?>
