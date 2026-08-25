<?php

/*
 * * * *      GestLibr: prestashop.lib.php      			*
 * * * *           Prestashop Webservice Class
 * * * *  Romain DUCHENE, April-May 2016
 * * * *
 */

/**
 * Include the engine to handle basic CRUD
 */

define('DEBUG', true);
define('PS_SHOP_PATH', 'http://store.librairieduchene.com/');
define('PS_WS_AUTH_KEY', 'CV99NWEH8IDHQZ2Y6RH7G641URT1Z18R');
define('PS_ROOT_CATEGORY', 2);
define('PS_FEATURE_ID_AUTHOR',8);
define('PS_FEATURE_ID_TITLE',9);

define('PS_ID_SHOP',1);

include("class/PSWebServiceLibrary.php");

// To transform any string to acceptable URL rewrite string
function stringToRewriteUrl($string) {
    $entities =     array('-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-');
    $replacements = array(' ', '!', '*', "'", "(", ")", ";", ":", "@", "&", "=", "+", "$", ",", "/", "?", "%", "#", "[", "]");
    return str_replace($replacements, $entities, $string);
}

// Listing all categories on website
function ps_category_list()
{
    global $babase;
    try {
        // creating webservice access
        $webService = new PrestaShopWebservice(PS_SHOP_PATH, PS_WS_AUTH_KEY, DEBUG);
        
        // call to retrieve all categories
        $opt['resource'] = 'categories';
        
        $xml = $webService->get($opt);
        $resources = $xml->categories->children();
    }
    catch (PrestaShopWebserviceException $ex) {
        // Here we are dealing with errors
        $trace = $ex->getTrace();
        if ($trace[0]['args'][0] == 404) echo 'Bad ID';
        else if ($trace[0]['args'][0] == 401) echo 'Bad auth key';
        else echo 'Other error<br />'.$ex->getMessage();
        return(false);
    }
    // if $resources is set we can lists element in it otherwise do nothing cause there's an error
    if (isset($resources))
    {
        foreach ($resources as $resource)
        {
            try 
            {
                // call to retrieve category info
                $opt['resource'] = 'categories';
                $opt['id'] = $resource->attributes();
        
                $xml = $webService->get($opt);
                $resources_detail = $xml->category->children();
            }
            catch (PrestaShopWebserviceException $ex) 
            {
                // Here we are dealing with errors
                $trace = $ex->getTrace();
                if ($trace[0]['args'][0] == 404) echo 'Bad ID';
                else if ($trace[0]['args'][0] == 401) echo 'Bad auth key';
                else echo 'Other error<br />'.$ex->getMessage();
                return(false);
            }

            // Iterates on the found IDs
            $results[] = array('id' => (int)$resources_detail->id, 'name' => (string)$resources_detail->name->language[0], 'name_english' => (string)$resources_detail->name->language[1] );
		}
    }
    return($results);
}

// Adding a category on the website
function ps_category_add($idCategory)
{
    global $babase;
    $idCategory=(int)$idCategory;
    $category = New Category();
    $category->idCategory = $idCategory;
    if (!($cat = $category->get()))
        // Category not found
        return(false);
    $babase->DbSelect("SELECT * from `ps_category` WHERE idCategory = '".$idCategory."';",$nb);
    if ($nb!=0)
        // Category already added
        return(false);
    
    // Here we use the WebService to get the schema of "categories" resource
    try {
        // creating webservice access
        $webService = new PrestaShopWebservice(PS_SHOP_PATH, PS_WS_AUTH_KEY, DEBUG);
        
        // call to retrieve categories blank schema
        $opt['url'] = PS_SHOP_PATH.'/api/categories?schema=blank';
        $xml = $webService->get($opt);
        $resources = $xml->children()->children();
    }
    catch (PrestaShopWebserviceException $ex) {
        // Here we are dealing with errors
        $trace = $ex->getTrace();
        if ($trace[0]['args'][0] == 404) echo 'Bad ID';
        else if ($trace[0]['args'][0] == 401) echo 'Bad auth key';
        else echo 'Other error<br />'.$ex->getMessage();
        return(false);
    }

    // Assign values
    unset($resources->id);
    unset($resources->position);
    unset($resources->date_add);
    unset($resources->date_upd);
    unset($resources->level_depth);
    unset($resources->nb_products_recursive);
    $resources ->id_shop_default = PS_ID_SHOP;
    $resources ->is_root_category = 0;
	$resources->id_parent = PS_ROOT_CATEGORY;
	$resources->active = true;
	$resources->name->language[0][0] = $cat["name"];
	$resources->name->language[1][0] = $cat["name"];
	$resources->link_rewrite->language[0][0] = stringToRewriteUrl($cat["name"]);
	$resources->link_rewrite->language[1][0] = stringToRewriteUrl($cat["name"]);
	$resources->title->language[0][0] = $cat["name"];
	$resources->title->language[1][0] = $cat["name"];
	$resources->description->language[0][0] = $cat["description"];
	$resources->description->language[1][0] = $cat["description"];
    
    // Send data
	try
	{
		$opt = array('resource' => 'categories');
		$opt['postXml'] = $xml->asXML();
		$xml = $webService->add($opt);
		// Successfully added
        $babase->DbInsert("INSERT INTO `ps_category` (`id_ps_category`, `idCategory`, `ps_id_category_website`, `ps_lastsync`) VALUES (NULL, '".$cat["idCategory"]."', '".$xml->children()->children()->id."', '".time()."');");
        return($xml->children()->children()->id);
	}
	catch (PrestaShopWebserviceException $ex)
	{
		// Here we are dealing with errors
		$trace = $ex->getTrace();
		if ($trace[0]['args'][0] == 404) echo 'Bad ID';
		else if ($trace[0]['args'][0] == 401) echo 'Bad auth key';
		else echo 'Other error<br />'.$ex->getMessage();
        return(false);
	}
}

// Deleting a category on the website
function ps_category_del($idCategory)
{
    global $babase;
    $idCategory=(int)$idCategory;
    $category = New Category();
    $category->idCategory = $idCategory;
    if (!($cat = $category->get()))
        // Category not found
        return(false);
    $results=$babase->DbSelect("SELECT * from `ps_category` WHERE idCategory = '".$idCategory."';",$nb);
    if ($nb!=1)
        // Category not found in the website category table
        return(false);
    
    // Send data
	try
	{
        // creating webservice access
        $webService = new PrestaShopWebservice(PS_SHOP_PATH, PS_WS_AUTH_KEY, DEBUG);
        
		$opt = array('resource' => 'categories', 'id' => intval($results[0]["ps_id_category_website"]));
		$webService->delete($opt);
		// Successfully added
        $babase->DbInsert("DELETE FROM `ps_category` WHERE `idCategory`='$idCategory';");
        return(true);
	}
	catch (PrestaShopWebserviceException $ex)
	{
		// Here we are dealing with errors
		$trace = $ex->getTrace();
		if ($trace[0]['args'][0] == 404) echo 'Bad ID';
		else if ($trace[0]['args'][0] == 401) echo 'Bad auth key';
		else echo 'Other error<br />'.$ex->getMessage();
        return(false);
	}
}

// Updating a category on the website
function ps_category_update($idCategory)
{
    global $babase;
    $idCategory=(int)$idCategory;
    $category = New Category();
    $category->idCategory = $idCategory;
    if (!($cat = $category->get()))
        // Category not found
        return(false);
    $results=$babase->DbSelect("SELECT * from `ps_category` WHERE idCategory = '".$idCategory."';",$nb);
    if ($nb!=1)
        // Category not found in the website category table
        return(false);
    
    // Here we use the WebService to get the schema of "customers" resource
    try {
        // creating webservice access
        $webService = new PrestaShopWebservice(PS_SHOP_PATH, PS_WS_AUTH_KEY, DEBUG);
        
        // call to retrieve categories blank schema
        $opt = array('resource' => 'categories');
		$opt['id'] = $results[0]["ps_id_category_website"];
        $xml = $webService->get($opt);
        $resources = $xml->children()->children();
    }
    catch (PrestaShopWebserviceException $ex) {
        // Here we are dealing with errors
        $trace = $ex->getTrace();
        if ($trace[0]['args'][0] == 404) echo 'Bad ID';
        else if ($trace[0]['args'][0] == 401) echo 'Bad auth key';
        else echo 'Other error<br />'.$ex->getMessage();
        return(false);
    }

    // Assign values
	$resources->name->language[0][0] = $cat["name"];
	$resources->name->language[1][0] = $cat["name"];
	$resources->link_rewrite->language[0][0] = stringToRewriteUrl($cat["name"]);
	$resources->link_rewrite->language[1][0] = stringToRewriteUrl($cat["name"]);
	$resources->title->language[0][0] = $cat["name"];
	$resources->title->language[1][0] = $cat["name"];
	$resources->description->language[0][0] = $cat["description"];
	$resources->description->language[1][0] = $cat["description"];
    unset($resources->date_add);
    unset($resources->date_upd);
    unset($resources->level_depth);
    unset($resources->nb_products_recursive);
    
    // Send data
	try
	{
		$opt = array('resource' => 'categories');
		$opt['putXml'] = $xml->asXML();
		$opt['id'] = $results[0]["ps_id_category_website"];
		$xml = $webService->edit($opt);
		// Successfully added
        $babase->DbInsert("INSERT INTO `ps_category` (`id_ps_category`, `idCategory`, `ps_id_category_website`, `ps_lastsync`) VALUES (NULL, '".$cat["idCategory"]."', '".$xml->children()->children()->id."', '".time()."');");
        return($xml->children()->children()->id);
	}
	catch (PrestaShopWebserviceException $ex)
	{
		// Here we are dealing with errors
		$trace = $ex->getTrace();
		if ($trace[0]['args'][0] == 404) echo 'Bad ID';
		else if ($trace[0]['args'][0] == 401) echo 'Bad auth key';
		else echo 'Other error<br />'.$ex->getMessage();
        return(false);
	}
}

function ps_category_sync()
{
    global $babase;
    $idCategory=(int)$idCategory;
    $category = New Category();
    $datas = $babase->DbSelect("SELECT * FROM category LEFT JOIN ps_category as c ON category.idCategory = c.idCategory WHERE onWebsite='1'",$nb);
    echo "<pre>";
    foreach($datas as $data)
    {
        echo "<br>ID : " . $data[0] . " / Nom : " . $data["name"];
        if (is_null($data["ps_lastsync"])) {
            // Category isn't on website, we add it
            echo " / Added";
            var_dump(ps_category_add($data[0]));
        }
        elseif ($data["ps_lastsync"]<$data["modifyDate"]) {
            // Category is on website but not up-to-date, we update it
            echo " / Updated";
            ps_category_update($data[0]);
        }
        else {
            // No update
            echo " / No Update";
        }
    }
    return(true);
}

// Adding a feature value on the website
function ps_feature_value_add($idFeature,$value)
{
    global $babase;
    $idFeature=(int)$idFeature;
    
    // Here we use the WebService to get the schema of "product_feature_values" resource
    try {
        // creating webservice access
        $webService = new PrestaShopWebservice(PS_SHOP_PATH, PS_WS_AUTH_KEY, DEBUG);
        
        // call to retrieve categories blank schema
        $opt['url'] = PS_SHOP_PATH.'/api/product_feature_values?schema=blank';
        $xml = $webService->get($opt);
        $resources = $xml->children()->children();
    }
    catch (PrestaShopWebserviceException $ex) {
        // Here we are dealing with errors
        $trace = $ex->getTrace();
        if ($trace[0]['args'][0] == 404) echo 'Bad ID';
        else if ($trace[0]['args'][0] == 401) echo 'Bad auth key';
        else echo 'Other error<br />'.$ex->getMessage();
        return(false);
    }

    // Assign values
    unset($resources->id);
    $resources ->id_feature = $idFeature;
	$resources->value->language[0][0] = $value;
	$resources->value->language[1][0] = $value;
    
    // Send data
	try
	{
		$opt = array('resource' => 'product_feature_values');
		$opt['postXml'] = $xml->asXML();
		$xml = $webService->add($opt);
        return($xml->children()->children()->id);
	}
	catch (PrestaShopWebserviceException $ex)
	{
		// Here we are dealing with errors
		$trace = $ex->getTrace();
		if ($trace[0]['args'][0] == 404) echo 'Bad ID';
		else if ($trace[0]['args'][0] == 401) echo 'Bad auth key';
		else echo 'Other error<br />'.$ex->getMessage();
        return(false);
	}
}

// Deleting a feature_value on the website
function ps_feature_value_del($idFeatureValue)
{
    global $babase;
    $idFeatureValue=(int)$idFeatureValue;
    
    // Send data
	try
	{
        // creating webservice access
        $webService = new PrestaShopWebservice(PS_SHOP_PATH, PS_WS_AUTH_KEY, DEBUG);
        
		$opt = array('resource' => 'product_feature_values', 'id' => $idFeatureValue);
		$webService->delete($opt);
        return(true);
	}
	catch (PrestaShopWebserviceException $ex)
	{
		// Here we are dealing with errors
		$trace = $ex->getTrace();
		if ($trace[0]['args'][0] == 404) echo 'Bad ID';
		else if ($trace[0]['args'][0] == 401) echo 'Bad auth key';
		else echo 'Other error<br />'.$ex->getMessage();
        return(false);
	}
}

// Updating a feature_value on the website
function ps_feature_value_update($idFeatureValue,$value)
{
    global $babase;
    $idFeatureValue=(int)$idFeatureValue;
    
    // Here we use the WebService to get the schema of "customers" resource
    try {
        // creating webservice access
        $webService = new PrestaShopWebservice(PS_SHOP_PATH, PS_WS_AUTH_KEY, DEBUG);
        
        // call to retrieve categories blank schema
        $opt = array('resource' => 'product_feature_values');
		$opt['id'] = $idFeatureValue;
        $xml = $webService->get($opt);
        $resources = $xml->children()->children();
    }
    catch (PrestaShopWebserviceException $ex) {
        // Here we are dealing with errors
        $trace = $ex->getTrace();
        if ($trace[0]['args'][0] == 404) echo 'Bad ID';
        else if ($trace[0]['args'][0] == 401) echo 'Bad auth key';
        else echo 'Other error<br />'.$ex->getMessage();
        return(false);
    }

    // Assign values
	$resources->value->language[0][0] = $value;
	$resources->value->language[1][0] = $value;

    
    // Send data
	try
	{
		$opt = array('resource' => 'product_feature_values');
		$opt['putXml'] = $xml->asXML();
		$opt['id'] = $idFeatureValue;
		$xml = $webService->edit($opt);
        return($xml->children()->children()->id);
	}
	catch (PrestaShopWebserviceException $ex)
	{
		// Here we are dealing with errors
		$trace = $ex->getTrace();
		if ($trace[0]['args'][0] == 404) echo 'Bad ID';
		else if ($trace[0]['args'][0] == 401) echo 'Bad auth key';
		else echo 'Other error<br />'.$ex->getMessage();
        return(false);
	}
}

// Listing all products on website
function ps_product_list()
{
    global $babase;
    try {
        // creating webservice access
        $webService = new PrestaShopWebservice(PS_SHOP_PATH, PS_WS_AUTH_KEY, DEBUG);
        
        // call to retrieve all categories
        $opt['resource'] = 'products';
        
        $xml = $webService->get($opt);
        $resources = $xml->products->children();
    }
    catch (PrestaShopWebserviceException $ex) {
        // Here we are dealing with errors
        $trace = $ex->getTrace();
        if ($trace[0]['args'][0] == 404) echo 'Bad ID';
        else if ($trace[0]['args'][0] == 401) echo 'Bad auth key';
        else echo 'Other error<br />'.$ex->getMessage();
        return(false);
    }
    // if $resources is set we can lists element in it otherwise do nothing cause there's an error
    if (isset($resources))
    {
        foreach ($resources as $resource)
        {
            try 
            {
                // call to retrieve category info
                $opt['resource'] = 'products';
                $opt['id'] = $resource->attributes();
        
                $xml = $webService->get($opt);
                $resources_detail = $xml->product->children();
            }
            catch (PrestaShopWebserviceException $ex) 
            {
                // Here we are dealing with errors
                $trace = $ex->getTrace();
                if ($trace[0]['args'][0] == 404) echo 'Bad ID';
                else if ($trace[0]['args'][0] == 401) echo 'Bad auth key';
                else echo 'Other error<br />'.$ex->getMessage();
                return(false);
            }

            // Iterates on the found IDs
            $results[] = array('id' => (int)$resources_detail->id, 'name' => (string)$resources_detail->name->language[0], 'name_english' => (string)$resources_detail->name->language[1],'description' => (string)$resources_detail->description->language[0] );
		}
    }
    return($results);
}

function ps_set_product_quantity($product_id, $quantity)
{
    
    try {
        // creating webservice access
        $webService = new PrestaShopWebservice(PS_SHOP_PATH, PS_WS_AUTH_KEY, DEBUG);
        
        // Get $stock_id and $id_product_attribute for this product (only if product have one stock)
        $xml = $webService -> get(array('url' => PS_SHOP_PATH . '/api/products/'.$product_id));
        $resources = $xml -> children() -> children();
        $stock_id = (int)$resources->associations->stock_availables->stock_available->id;
        $id_product_attribute = (int)$resources->associations->stock_availables->stock_available->id_product_attribute;
        
        // Update stock
        $xml = $webService -> get(array('url' => PS_SHOP_PATH . '/api/stock_availables?schema=blank'));
        $resources = $xml -> children() -> children();
        $resources->id = $stock_id;
        $resources->id_product  = $product_id;
        $resources->quantity = $quantity;
        $resources->id_shop = PS_ID_SHOP;
        $resources->out_of_stock=1;
        $resources->depends_on_stock = 0;
        $resources->id_product_attribute=$id_product_attribute;
        $opt = array('resource' => 'stock_availables');
        $opt['putXml'] = $xml->asXML();
        $opt['id'] = $stock_id ;
//  echo 'Quantity updated for product '.$product_id.' / stock_id : '.$stock_id.' / product_attribute_id '. $id_product_attribute.' id shop: '.$resources->id_shop;
        $xml = $webService->edit($opt);
    }catch (PrestaShopWebserviceException $ex)
            {
                // Here we are dealing with errors
                $trace = $ex->getTrace();
                if ($trace[0]['args'][0] == 404) echo 'Bad ID';
                else if ($trace[0]['args'][0] == 401) echo 'Bad auth key';
                else echo 'Other error<br />'.$ex->getMessage();
                return(false);
            }
}

// Adding a product on the website
function ps_product_add($idProduct)
{
    global $babase;
    $idProduct=(int)$idProduct;
    $book = New Book();
    if (!($product = $book->get($idProduct)))
        // Book not found
        return(false);
    $catList = $book->listCategory($idProduct);
    $babase->DbSelect("SELECT * from `ps_product` WHERE idBook = '".$idProduct."';",$nb);
    if ($nb!=0)
        // Book already added
        return(false);
    
    // Here we use the WebService to get the schema of "products" resource
    try {
        // creating webservice access
        $webService = new PrestaShopWebservice(PS_SHOP_PATH, PS_WS_AUTH_KEY, DEBUG);
        
        // call to retrieve products blank schema
        $opt['url'] = PS_SHOP_PATH.'/api/products?schema=blank';
        $xml = $webService->get($opt);
        $resources = $xml->children()->children();
    }
    catch (PrestaShopWebserviceException $ex) {
        // Here we are dealing with errors
        $trace = $ex->getTrace();
        if ($trace[0]['args'][0] == 404) echo 'Bad ID';
        else if ($trace[0]['args'][0] == 401) echo 'Bad auth key';
        else echo 'Other error<br />'.$ex->getMessage();
        return(false);
    }
    
    // Make title
    $title = $product["author"] . " - " .$product["title"];

    // TODO
    $cat_ps = 1;
    $supplier_ps = 1;

    // Assign values
    unset($resources->id);
    unset($resources->id_default_image);
    unset($resources->position_in_category);
    unset($resources->unity);
    unset($resources->date_add);
    unset($resources->date_upd);

/*    unset($resources->id_default_combination);
    $resources->id_manufacturer = 1;
    $resources->id_category_default = $cat_ps;
    $resources->id_supplier = $supplier_ps;
//    $resources->id_tax_rules_group = ??;
//    $resources->type = ?? ;
    $resources ->id_shop_default = 1;
//    $resources->location = ??;
//    $resources->width = ??;
//    $resources->height = ??;
//    $resources->depth = ??;
//    $resources->weight = ??;
//    $resources->quantity_discount = ??;
//    $resources->upc = ??;
//    $resources->cache_is_pack = ??;
//    $resources->cache_has_attachments = ??
    $resources->is_virtual = false;
    $resources->on_sale = true;
    $resources->online_only = false;
    $resources->ecotax = 0;
//    $resources->minimal_quantity = ??;
    $resources->price = $product["price"];
    $resources->wholesale_price = 
    $resources->
    $resources->
    $resources->
    $resources->
    $resources->
*/        
    
	$resources->active = 1;
	$resources->name->language[0][0] = $title;
	$resources->name->language[1][0] = $title;
	$resources->link_rewrite->language[0][0] = stringToRewriteUrl($title);
	$resources->link_rewrite->language[1][0] = stringToRewriteUrl($title);
	$resources->name->language[0][0] = $title;
	$resources->name->language[1][0] = $title;
	$resources->description_short->language[0][0] = $product["description"];
	$resources->description_short->language[1][0] = $product["description"];
    $resources->ean13 = $product["isbn"];
//    $resources->upc = $product["isbn"];
    $resources->price = $product["price"];
//    $resources->quantity = $product["quantity"];
    $resources->reference = $product["idBook"];
    $resources->supplier_reference = $product["idBook"];
//    $resources_product->out_of_stock = 2;
    unset($resources->position_in_category);
    unset($resources->manufacturer_name);
    unset($resources->id_default_combination);
    unset($resources->quantity);

    // --------
    // Category
    // --------
    
    $sqlTxt=" 1=0";
    foreach($catList as $item)
        $sqlTxt.=" OR `idCategory`='".$item["idCategory"]."'";
    $psCatList = $babase->DbSelect("SELECT * FROM `ps_category` WHERE $sqlTxt",$nb);
    if ($nb==0)
        return(false);
    foreach($psCatList as $item)
    {
        $catxml = $resources->associations->categories->addChild("category");
        $catxml->addChild("id",$item["ps_id_category_website"]); 
    }

    // --------
    // Features
    // --------
    
    // Lenght
    // Height
    // Deep
    // Weight
    // Author
    
    $id_feature_author = ps_feature_value_add(PS_FEATURE_ID_AUTHOR,$product["author"]);
    $featureXml = $resources->associations->product_features->addChild("product_features");
    $featureXml->addChild("id",PS_FEATURE_ID_AUTHOR); 
    $featureXml->addChild("id_feature_value","$id_feature_author"); 

    $id_feature_title = ps_feature_value_add(PS_FEATURE_ID_TITLE,$product["title"]);
    $featureXml = $resources->associations->product_features->addChild("product_features");
    $featureXml->addChild("id",PS_FEATURE_ID_TITLE); 
    $featureXml->addChild("id_feature_value","$id_feature_title"); 
/*
        
    $featureXml = $resources->associations->product_features->addChild("product_feature");
    $featureXml->addChild("id",PS_FEATURE_ID_AUTHOR); 
    $featureXml->addChild("custom","1"); 
    $featureXml = $resources->associations->product_features->product_feature[1]->addChild("id_feature_value");
    $featureXml->addChild("custom","1"); 
    $featureXml = $resources->associations->product_features->product_feature[1]->id_feature_value->addChild("value");
    $featureXml->addChild("language",$product["author"]); 
    $featureXml->addAttribute('id', '1');
    $featureXml->addChild("language",$product["author"]); 
    $featureXml->addAttribute('id', '2');
*/

//    $featureXml->addChild("id_feature_value",$product["author"]); 

    // Title
//    $featureXml = $resources->associations->product_features->addChild("product_features");
//    $featureXml->addChild("id",PS_FEATURE_ID_TITLE); 
//    $featureXml->addChild("id_feature_value","43"); 
//    $featureXml->addChild("id_feature_value",$product["title"]); 
    // Condition
    // ISBN
    // Collection
    // Editor
    // Publisher
    // Location
    // Format
    // Pages
    // Binding
    // Language
    
    // --------
    // Image
    // --------
    
    // Send data
	try
	{
		$opt = array('resource' => 'products');
		$opt['postXml'] = $xml->asXML();
		$xml = $webService->add($opt);
		// Successfully added
        $babase->DbInsert("INSERT INTO `ps_product` (`id_ps_product`, `idBook`, `ps_id_product_website`, `ps_lastsync`) VALUES (NULL, '".$product["idBook"]."', '".$xml->children()->children()->id."', '".time()."');");
        $product_id = $xml->children()->children()->id;
        ps_set_product_quantity($product_id, $product["quantity"]);
        return($product_id);
	}
	catch (PrestaShopWebserviceException $ex)
	{
		// Here we are dealing with errors
		$trace = $ex->getTrace();
		if ($trace[0]['args'][0] == 404) echo 'Bad ID';
		else if ($trace[0]['args'][0] == 401) echo 'Bad auth key';
		else echo 'Other error<br />'.$ex->getMessage();
        echo 'Code : '.$ex->getCode().'<br />';
  echo 'Trace : '.print_r($ex->getTrace()).'<br />';
  echo $ex->getFile().'<br />';
  echo $ex->getLine().'<br />';
        return(false);
	}
}

// Deleting a product on the website
function ps_product_del($idProduct)
{
    global $babase;
    $idProduct=(int)$idProduct;
    $book = New Book();
    if (!($product = $book->get($idProduct)))
        // Book not found
        return(false);

    $results=$babase->DbSelect("SELECT * from `ps_product` WHERE idBook = '".$idProduct."';",$nb);
    if ($nb!=1)
        // Book not found in the website product table
        return(false);

    // Retrieve product data to delete feature values resource
    try {
        // creating webservice access
        $webService = new PrestaShopWebservice(PS_SHOP_PATH, PS_WS_AUTH_KEY, DEBUG);
        
        // call to retrieve categories blank schema
        $opt = array('resource' => 'products');
		$opt['id'] = $results[0]["ps_id_product_website"];
        $xml = $webService->get($opt);
        $resources = $xml->children()->children();
    }
    catch (PrestaShopWebserviceException $ex) {
        // Here we are dealing with errors
        $trace = $ex->getTrace();
        if ($trace[0]['args'][0] == 404) echo 'Bad ID';
        else if ($trace[0]['args'][0] == 401) echo 'Bad auth key';
        else echo 'Other error<br />'.$ex->getMessage();
        return(false);
    }
    // TODO: Loop to clean all feature_value of this product
    
    
    
    // Send data
	try
	{
        // creating webservice access
        $webService = new PrestaShopWebservice(PS_SHOP_PATH, PS_WS_AUTH_KEY, DEBUG);
        
		$opt = array('resource' => 'products', 'id' => intval($results[0]["ps_id_product_website"]));
		$webService->delete($opt);
		// Successfully added
        $babase->DbInsert("DELETE FROM `ps_product` WHERE `idBook`='$idProduct';");
        return(true);
	}
	catch (PrestaShopWebserviceException $ex)
	{
		// Here we are dealing with errors
		$trace = $ex->getTrace();
		if ($trace[0]['args'][0] == 404) echo 'Bad ID';
		else if ($trace[0]['args'][0] == 401) echo 'Bad auth key';
		else echo 'Other error<br />'.$ex->getMessage();
        return(false);
	}
}

// Updating a product on the website
function ps_product_update($idProduct)
{
    global $babase;
    $idProduct=(int)$idProduct;
    $book = New Book();
    if (!($product = $book->get($idProduct)))
        // Book not found
        return(false);

    $results=$babase->DbSelect("SELECT * from `ps_product` WHERE idBook = '".$idProduct."';",$nb);
    if ($nb!=1)
        // Book not found in the website product table
        return(false);
    
    // Here we use the WebService to get the schema of "products" resource
    try {
        // creating webservice access
        $webService = new PrestaShopWebservice(PS_SHOP_PATH, PS_WS_AUTH_KEY, DEBUG);
        
        // call to retrieve categories blank schema
        $opt = array('resource' => 'products');
		$opt['id'] = $results[0]["ps_id_product_website"];
        $xml = $webService->get($opt);
        $resources = $xml->children()->children();
    }
    catch (PrestaShopWebserviceException $ex) {
        // Here we are dealing with errors
        $trace = $ex->getTrace();
        if ($trace[0]['args'][0] == 404) echo 'Bad ID';
        else if ($trace[0]['args'][0] == 401) echo 'Bad auth key';
        else echo 'Other error<br />'.$ex->getMessage();
        return(false);
    }

    // Assign values
	$resources->name->language[0][0] = $cat["name"];
	$resources->name->language[1][0] = $cat["name"];
	$resources->link_rewrite->language[0][0] = stringToRewriteUrl($cat["name"]);
	$resources->link_rewrite->language[1][0] = stringToRewriteUrl($cat["name"]);
	$resources->title->language[0][0] = $cat["name"];
	$resources->title->language[1][0] = $cat["name"];
	$resources->description->language[0][0] = $cat["description"];
	$resources->description->language[1][0] = $cat["description"];
    unset($resources->date_add);
    unset($resources->date_upd);
    unset($resources->level_depth);
    unset($resources->nb_products_recursive);
    
    // Send data
	try
	{
		$opt = array('resource' => 'categories');
		$opt['putXml'] = $xml->asXML();
		$opt['id'] = $results[0]["ps_id_category_website"];
		$xml = $webService->edit($opt);
		// Successfully added
        $babase->DbInsert("INSERT INTO `ps_category` (`id_ps_category`, `idCategory`, `ps_id_category_website`, `ps_lastsync`) VALUES (NULL, '".$cat["idCategory"]."', '".$xml->children()->children()->id."', '".time()."');");
        return($xml->children()->children()->id);
	}
	catch (PrestaShopWebserviceException $ex)
	{
		// Here we are dealing with errors
		$trace = $ex->getTrace();
		if ($trace[0]['args'][0] == 404) echo 'Bad ID';
		else if ($trace[0]['args'][0] == 401) echo 'Bad auth key';
		else echo 'Other error<br />'.$ex->getMessage();
        return(false);
	}
}

function ps_product_sync()
{
    global $babase;
    $idCategory=(int)$idCategory;
    $category = New Category();
    $datas = $babase->DbSelect("SELECT * FROM category LEFT JOIN ps_category as c ON category.idCategory = c.idCategory WHERE onWebsite='1'",$nb);
    echo "<pre>";
    foreach($datas as $data)
    {
        echo "<br>ID : " . $data[0] . " / Nom : " . $data["name"];
        if (is_null($data["ps_lastsync"])) {
            // Category isn't on website, we add it
            echo " / Added";
            ps_category_add($data[0]);
        }
        elseif ($data["ps_lastsync"]<$data["modifyDate"]) {
            // Category is on website but not up-to-date, we update it
            echo " / Updated";
            ps_category_update($data[0]);
        }
        else {
            // No update
            echo " / No Update";
        }
    }
    return(true);
}


?>