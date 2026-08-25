<?php
/**
 * * * *         GestLibr: index.php      			*
 * * * *                Core               			*
 * * * *  Romain DUCHENE, june 2003 - march 2026  	*
 */

date_default_timezone_set("Europe/Paris");

/* Calc exec time */
function getmicrotime(){
    list($usec, $sec) = explode(" ",microtime());
    return ((float)$usec + (float)$sec);
    }
$time_start = getmicrotime();

$isHttps = false;
if ((isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "" && $_SERVER["HTTPS"] !== "off")
    || (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && strtolower((string)$_SERVER["HTTP_X_FORWARDED_PROTO"]) === "https"))
{
    $isHttps = true;
}
if (PHP_VERSION_ID >= 70300)
{
    session_set_cookie_params(array(
        "lifetime" => 0,
        "path" => "/",
        "secure" => $isHttps,
        "httponly" => true,
        "samesite" => "Lax"
    ));
}
else
{
    session_set_cookie_params(0, "/; samesite=Lax", "", $isHttps, true);
}
session_start();

include "config.php";
include "lang/" . $config["lang"] . ".lang.php";
include "include/var.inc.php";
include "class/mysqli.class.php";
include "lib/utils.lib.php";
include "lib/template.php";

$babase = new DataBase;
$babase->DbConnect();
global $babase;

function sanitize_request_value($value)
{
    if (is_array($value))
    {
        foreach ($value as $k => $v)
            $value[$k] = sanitize_request_value($v);
        return $value;
    }
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// securing POST & GET var
foreach ($_POST as $key => $val) {
    if ($key != "selected_book") {
        $_POST[$key] = sanitize_request_value($val);
    }
}
// We must unsecure $post["selected_book"] to avoid a table corruption...

foreach ($_GET as $key => $val) {
    $_GET[$key] = sanitize_request_value($val);
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

$isPhotoAjaxRequest = (
    $m === "bo" &&
    $a === "vi" &&
    (
        (isset($_POST['photo_action']) && $_POST['photo_action'] != "") ||
        (isset($_GET['photo_action']) && $_GET['photo_action'] != "")
    )
);
$isBookAjaxRequest = (
    $m === "bo" &&
    (
        (isset($_POST['book_action']) && $_POST['book_action'] != "") ||
        (isset($_GET['book_action']) && $_GET['book_action'] != "")
    )
);

$template_path = 'template/';
$template_name = $config["template"];
$template = new Template($template_path . $template_name);

include "lib/securite.lib.php";


if ($m!="mi" && !$isPhotoAjaxRequest && !$isBookAjaxRequest) {
    $isAdminSession = (isset($_SESSION["admin"]) && (int)$_SESSION["admin"] === 1);
    $paramAdminDesktopLinks = "";
    $paramAdminMobileLinks = "";
    if ($isAdminSession) {
        $paramAdminDesktopLinks =
            "<a href='?m=pa&a=cfg' class='block rounded-md px-3 py-2 text-slate-100 no-underline hover:bg-slate-800'>Réglages</a>" .
            "<a href='?m=pa&a=usr' class='block rounded-md px-3 py-2 text-slate-100 no-underline hover:bg-slate-800'>Utilisateurs</a>";
        $paramAdminMobileLinks =
            "<a href='?m=pa&a=cfg' class='block rounded-md px-3 py-2 text-slate-300 no-underline hover:bg-slate-700'>Réglages</a>" .
            "<a href='?m=pa&a=usr' class='block rounded-md px-3 py-2 text-slate-300 no-underline hover:bg-slate-700'>Utilisateurs</a>";
    }

	$template->set_filenames(array('header' => 'header.tpl'));
	$template->assign_vars(array('nomProg' => $var["nomProg"], 'verProg' => $var["verProg"]));
	$template->assign_vars(array('TEMPLATE_FOLDER'=> $template_path.$template_name, 'FORM_INCOMPLETE' => $txt["formIncomplete"], 'FORM_NOT_NUMBER' => $txt["formNotNumber"], 'FORM_NOT_VALID_NUMBER' => $txt["formNotValidNumber"], 'FORM_CONFIRM' => $txt["formConfirm"]));
	$template->assign_vars(array(
            'USERNAME' => $_SESSION["name"],
            'GROUPNAME' => $_SESSION["groupName"],
            'CONFIRM_DELETE' => $txt["confirmDelete"],
            'PARAM_LINK_ME' => "?m=pa&a=me",
            'PARAM_ADMIN_LINKS' => $paramAdminDesktopLinks,
            'PARAM_ADMIN_LINKS_MOBILE' => $paramAdminMobileLinks
        ));
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
    case "pa":
        include "lib/param.lib.php";
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
                'verProg' => $var["verProg"]));
        $template->pparse('body');
        break;
} 

if ($m!="mi" && !$isPhotoAjaxRequest && !$isBookAjaxRequest) {
	$template->set_filenames(array('footer' => 'footer.tpl'));
	/* Calc exec time */
	$time_end = getmicrotime();
	$execTime = round($time_end - $time_start, 3);
	$template->assign_vars(array('nomProg' => $var["nomProg"], 'verProg' => $var["verProg"], 'execTime' => $execTime, 'Time' => date("d/m/Y H:i:s", time())));
	$template->pparse('footer');
}

?>
