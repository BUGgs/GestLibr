<?php
/**
 * * * *         GestLibr: index.php      			*
 * * * *                Core               			*
 * * * *  Romain DUCHENE, june 2003-june 2005  	*
 */

date_default_timezone_set("Europe/Paris");

/* Calc exec time */
function getmicrotime(){
    list($usec, $sec) = explode(" ",microtime());
    return ((float)$usec + (float)$sec);
    }
$time_start = getmicrotime();

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

// securing POST & GET var
reset ($_POST);
while (list ($key, $val) = each ($_POST)) {
    if ($key != "selected_book") @$_POST["$key"]=htmlspecialchars($val);
}
// We must unsecure $post["selected_book"] to avoid a table corruption...

reset ($_GET);
while (list ($key, $val) = each ($_GET)) {
    @$_GET["$key"]=htmlspecialchars($val);
}

// Set action variable
if ((isset($_POST['m'])) and ($_POST['m'] != ""))
    $m = $_POST['m'];
else
    if ((isset($_GET['m']))) $m = $_GET['m']; else $m="";
if ((isset($_POST['a'])) and ($_POST['a'] != ""))
    $a = $_POST['a'];
else
    if ((isset($_GET['a']))) $a = $_GET['a']; else $a="";
if ((isset($_POST['sa'])) and ($_POST['sa'] != ""))
    $sa = $_POST['sa'];
else
    if ((isset($_GET['sa']))) $sa = $_GET['sa']; else $sa="";

$template_path = 'template/';
$template_name = $config["template"];
$template = new Template($template_path . $template_name);

include "lib/securite.lib.php";


if ($m!="mi") {
	$template->set_filenames(array('header' => 'header.tpl'));
	$template->assign_vars(array('nomProg' => $var["nomProg"], 'verProg' => $var["verProg"]));
	$template->assign_vars(array('TEMPLATE_FOLDER'=> $template_path.$template_name, 'FORM_INCOMPLETE' => $txt["formIncomplete"], 'FORM_NOT_NUMBER' => $txt["formNotNumber"], 'FORM_NOT_VALID_NUMBER' => $txt["formNotValidNumber"], 'FORM_CONFIRM' => $txt["formConfirm"]));
	$template->assign_vars(array('USERNAME' => $_SESSION["name"], 'GROUPNAME' => $_SESSION["groupName"], 'CONFIRM_DELETE' => $txt["confirmDelete"]));
	$template->pparse('header');
}

switch ($m) {
    case "cl":
        include "lib/clients.lib.php";
        break;
    case "bo":
        include "lib/books.lib.php";
        break;
    case "ct":
        include "lib/categories.lib.php";
        break;
    case "ca":
        include "lib/catalog.lib.php";
        break;
    case "or":
        include "lib/order.lib.php";
        break;
    case "ed":
        include "lib/editions.lib.php";
        break;
    case "in":
        include "lib/internet.lib.php";
        break;
    case "mi":
        include "lib/mini.lib.php";
        break;
    default:
        $template->set_filenames(array('body' => 'home.tpl'));
        include "class/book.class.php";
	$book = new Book();
        $stats=$book->stats();
	$template->assign_vars(array('PROG' => $var["nomProg"] . " " . $var["verProg"]));
        $template->assign_vars(array('BOOK_DATABASE' => $txt["bookDatabase"],
                'BOOK_HOME' => $txt["booksHome"],
                'STATS' => $txt["statistics"],
                'TOTAL_BOOKS' => $txt["totalBooks"],
                'TOTAL_BOOKS_VALUE' => $stats["totalBooks"],
                'TOTAL_BOOKS_AVAILABLE' => $txt["totalBooksAvailable"],
                'TOTAL_BOOKS_AVAILABLE_VALUE' => $stats["totalBooksAvailable"],
                'TOTAL_PRICE_AVAILABLE' => $txt["totalPriceAvailable"],
                'TOTAL_PRICE_AVAILABLE_VALUE' => $stats["totalPriceAvailable"],
                'AVERAGE_PRICE_AVAILABLE' => $txt["averagePriceAvailable"],
                'AVERAGE_PRICE_AVAILABLE_VALUE' => $stats["averagePriceAvailable"],
                'BOOK' => $txt["book"],
                'verProg' => $txt["verProg"]));
        $template->pparse('body');
        break;
} 

if ($m!="mi") {
	$template->set_filenames(array('footer' => 'footer.tpl'));
	/* Calc exec time */
	$time_end = getmicrotime();
	$execTime = $time_end - $time_start;
	$template->assign_vars(array('nomProg' => $var["nomProg"], 'verProg' => $var["verProg"], 'execTime' => $execTime, 'Time' => date("d/m/Y H:i:s", time())));
	$template->pparse('footer');
}

?>
