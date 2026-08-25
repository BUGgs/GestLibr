<?php
$menuTitle = "<h2>" . $txt["menu"] . "</h2>";
if ($m == "cl")
    $menuClients = "<p class='menuSelected'>" . $txt["clients"] . "</p>
	      &nbsp;&nbsp;<a href='?m=cl&a=cr' class='menuSubLink'>" . $txt["clientsNew"] . "</a><br/>
	      &nbsp;&nbsp;<a href='?m=cl&a=vi' class='menuSubLink'>" . $txt["clientsView"] . "</a><br/>";
else
    $menuClients = "<a href='?m=cl&a=' class='menuLink'>" . $txt["clients"] . "</a>";

if ($m == "bo")
    $menuBooks = "<p class='menuSelected'>" . $txt["books"] . "</p>
	      &nbsp;&nbsp;<a href='?m=bo&a=cr' class='menuSubLink'>" . $txt["booksNew"] . "</a><br/>
	      &nbsp;&nbsp;<a href='?m=bo&a=vi' class='menuSubLink'>" . $txt["booksView"] . "</a><br/>";
else
    $menuBooks = "<a href='?m=bo&a=' class='menuLink'>" . $txt["books"] . "</a>";

if ($m == "ca")
    $menuCatalogs = "<p class='menuSelected'>" . $txt["catalogs"] . "</p>
	      &nbsp;&nbsp;<a href='?m=ca&a=cr' class='menuSubLink'>" . $txt["catalogsCreate"] . "</a><br/>
	      &nbsp;&nbsp;<a href='?m=ca&a=vi' class='menuSubLink'>" . $txt["catalogsView"] . "</a><br/>";
else
    $menuCatalogs = "<a href='?m=ca&a=' class='menuLink'>" . $txt["catalogs"] . "</a>";

if ($m == "or")
    $menuOrders = "<p class='menuSelected'>" . $txt["orders"] . "</p>
	      &nbsp;&nbsp;<a href='?m=or&a=cr' class='menuSubLink'>" . $txt["ordersCreate"] . "</a><br/>
	      &nbsp;&nbsp;<a href='?m=or&a=vi' class='menuSubLink'>" . $txt["ordersView"] . "</a><br/>
        &nbsp;&nbsp;<a href='?m=or&a=in' class='menuSubLink'>" . $txt["internet"] . "</a><br/>";
else
    $menuOrders = "<a href='?m=or&a=' class='menuLink'>" . $txt["orders"] . "</a>";

if ($m == "ct")
    $menuCategories = "<p class='menuSelected'>" . $txt["categories"] . "</p>
	      &nbsp;&nbsp;<a href='?m=ct&a=cr' class='menuSubLink'>" . $txt["categoriesNew"] . "</a><br/>
	      &nbsp;&nbsp;<a href='?m=ct&a=vi' class='menuSubLink'>" . $txt["categoriesView"] . "</a><br/>";
else
    $menuCategories = "<a href='?m=ct&a=' class='menuLink'>" . $txt["categories"] . "</a>";

if ($m == "ed")
    $menuEditions = "<p class='menuSelected'>" . $txt["editions"] . "</p>
	      &nbsp;&nbsp;<a href='?m=ed&a=cl' class='menuSubLink'>" . $txt["editionsClients"] . "</a><br/>";
else
    $menuEditions = "<a href='?m=ed&a=' class='menuLink'>" . $txt["editions"] . "</a>";

if ($m == "in")
    $menuInternet = "<p class='menuSelected'>" . $txt["internet"] . "</p>
	      &nbsp;&nbsp;<a href='?m=in&a=eb' class='menuSubLink'>" . $txt["ebay"] . "</a><br/>
	      &nbsp;&nbsp;<a href='?m=in&a=ex' class='menuSubLink'>" . $txt["exclusion"] . "</a><br/>
	      &nbsp;&nbsp;<a href='?m=in&a=co' class='menuSubLink'>" . $txt["configure"] . "</a><br/>
	      &nbsp;&nbsp;<a href='?m=in&a=se' class='menuSubLink'>" . $txt["selection"] . "</a><br/>
	      &nbsp;&nbsp;<a href='?m=in&a=sy' class='menuSubLink'>" . $txt["synchro"] . "</a><br/>";
else
    $menuInternet = "<a href='?m=in&a=' class='menuLink'>" . $txt["internet"] . "</a>";

if ($m == "qu")
    $menuQuit = "<p class='menuSelected'>" . $txt["quit"] . "</p>";
else
    $menuQuit = "<a href='?m=qu&a=' class='menuLink'>" . $txt["quit"] . "</a>";

$template->set_filenames(array('menu' => 'menu.tpl'));
$template->assign_vars(array('MENU_TITLE' => $menuTitle,
        'MENU_CLIENTS' => $menuClients,
        'MENU_BOOKS' => $menuBooks,
        'MENU_CATALOGS' => $menuCatalogs,
        'MENU_ORDERS' => $menuOrders,
        'MENU_CATEGORIES' => $menuCategories,
        'MENU_EDITIONS' => $menuEditions,
        'MENU_INTERNET' => $menuInternet,
        'MENU_QUIT' => $menuQuit,
        ));
$template->pparse('menu');
?>
