<?php
/**
 * * * *      GestLibr: editions.lib.php      	*
 * * * *					Editions Lib               		*
 * * * *  Romain DUCHENE, feb 2006 - ?				 	*
 */

// include "class/book.class.php";
// $book = new Book();
// include "class/catalog.class.php";
// $catalog = new Catalog();
// include "class/category.class.php";
// $category=New Category();

switch ($a) {
    case "cl":
        $template->set_filenames(array('body' => 'edition_customer_label.tpl'));
        $template->assign_vars(array('EDITIONS' => $txt["editions"],
                'LABEL_ACTIVES_CUSTOMERS' => $txt["labelActivesCustomers"],
                'LISTING_CATEGORY_ORDERS_ACTIVES_CUSTOMERS' => $txt["listingCategoryOrdersActivesCustomers"]));
        $template->pparse('body');
        break;
    default:
        $template->set_filenames(array('body' => 'edition_home.tpl'));
        $template->assign_vars(array('EDITIONS' => $txt["editions"],
                'EDITIONS_HOME' => ""));
        $template->pparse('body');
        break;
}

?>
