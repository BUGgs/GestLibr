<?php
include "class/book.class.php";
$book = new Book();
include "class/category.class.php";
$category = new Category();
include "class/catalog.class.php";
$catalog = new Catalog();
include "class/internet.class.php";
$internet = new Internet();
include_once "class/order.class.php";
$order = new Order();
$postSub = isset($_POST["sub"]) ? $_POST["sub"] : "";
$postId = isset($_POST["id"]) ? $_POST["id"] : "";
$getId = isset($_GET["id"]) ? $_GET["id"] : "";
$getStart = isset($_GET["start"]) ? $_GET["start"] : "";
$getRow = isset($_GET["row"]) ? $_GET["row"] : "";
$getDel = isset($_GET["del"]) ? $_GET["del"] : "0";
$getResearch = isset($_GET["research"]) ? $_GET["research"] : "";
$postResearch = isset($_POST["research"]) ? $_POST["research"] : "";
$postCategory = isset($_POST["category"]) ? $_POST["category"] : "";
$postAddInCatalog = isset($_POST["addInCatalog"]) ? $_POST["addInCatalog"] : "";
$postOpt1 = isset($_POST["opt1"]) ? $_POST["opt1"] : "";
$isbnDebugEnabled = (isset($_GET["debug_isbn"]) && $_GET["debug_isbn"] == "1");
$isBookAjaxAction = (isset($_REQUEST["book_action"]) && $_REQUEST["book_action"] !== "");
$isBookPhotoAjaxAction = (isset($_REQUEST["photo_action"]) && $_REQUEST["photo_action"] !== "");

// Keep JSON responses clean even in dev mode where warnings are displayed.
if ($isBookAjaxAction || $isBookPhotoAjaxAction)
{
    if (function_exists('ob_start'))
        @ob_start();
    @ini_set('display_errors', '0');
}

function book_photo_build_payload($photos)
{
    if (!is_array($photos))
        $photos = array();

    $cacheToken = (string)round(microtime(true) * 1000);

    $appendToken = function ($path) use ($cacheToken) {
        $path = (string)$path;
        if ($path == "")
            return "";
        $sep = (strpos($path, '?') === false) ? '?' : '&';
        return $path . $sep . 'v=' . $cacheToken;
    };

    $payload = array();
    $i = 0;
    while ($i < sizeof($photos))
    {
        $photo = $photos[$i];
        $payload[] = array(
            "id" => (int)(isset($photo["idBookPhoto"]) ? $photo["idBookPhoto"] : 0),
            "thumb" => $appendToken(isset($photo["fileThumbnail"]) ? $photo["fileThumbnail"] : ""),
            "medium" => $appendToken(isset($photo["fileMedium"]) ? $photo["fileMedium"] : ""),
            "original" => $appendToken(isset($photo["file"]) ? $photo["file"] : ""),
            "description" => (string)(isset($photo["description"]) ? $photo["description"] : ""),
            "width" => (int)(isset($photo["width"]) ? $photo["width"] : 0),
            "height" => (int)(isset($photo["height"]) ? $photo["height"] : 0),
            "sortOrder" => (int)(isset($photo["sortOrder"]) ? $photo["sortOrder"] : ($i + 1)),
            "isPrimary" => ($i == 0),
        );
        $i++;
    }
    return $payload;
}

function book_photo_json_response($ok, $message, $photos = array(), $errors = array(), $bookData = array())
{
    if (function_exists('ob_get_level'))
    {
        while (ob_get_level() > 0)
            @ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array(
        "ok" => (bool)$ok,
        "message" => (string)$message,
        "photos" => book_photo_build_payload($photos),
        "book" => is_array($bookData) ? $bookData : array(),
        "errors" => is_array($errors) ? array_values($errors) : array(),
    ));
    exit;
}

function book_truthy($value)
{
    $value = strtolower(trim((string)$value));
    return in_array($value, array("1", "true", "yes", "on"), true);
}

function book_has_valid_ebay_price($record)
{
    if (!is_array($record))
        return false;

    $preferred = trim((string)(isset($record["price_ebay"]) ? $record["price_ebay"] : ""));
    if ($preferred !== "")
    {
        $preferred = str_replace(",", ".", $preferred);
        if (is_numeric($preferred) && (float)$preferred > 0)
            return true;
    }

    $fallback = trim((string)(isset($record["price"]) ? $record["price"] : ""));
    if ($fallback !== "")
    {
        $fallback = str_replace(",", ".", $fallback);
        if (is_numeric($fallback) && (float)$fallback > 0)
            return true;
    }

    return false;
}

function book_missing_ebay_price_message()
{
    return "Publication eBay bloquée: renseignez un prix ou un prix eBay supérieur à 0.";
}

function book_decode_entities_recursive($value)
{
    $flags = ENT_QUOTES;

    $decoded = (string)$value;
    $i = 0;
    while ($i < 5)
    {
        $next = html_entity_decode($decoded, $flags, 'UTF-8');
        if ($next === $decoded)
            break;
        $decoded = $next;
        $i++;
    }
    return $decoded;
}

function book_autosave_enabled()
{
    $env = getenv("BOOK_AUTOSAVE_ENABLED");
    if ($env !== false && $env !== "")
        return book_truthy($env);

    $cfg = config("book_autosave_enabled");
    if ($cfg === false || $cfg === "")
    {
        $host = isset($_SERVER["HTTP_HOST"]) ? strtolower((string)$_SERVER["HTTP_HOST"]) : "";
        if ($host != "" && (strpos($host, "localhost") !== false || strpos($host, "127.0.0.1") !== false))
            return true;
        return false;
    }
    return book_truthy($cfg);
}

function book_build_payload($record)
{
    if (!is_array($record))
        $record = array();
    return array(
        "idBook" => (int)(isset($record["idBook"]) ? $record["idBook"] : 0),
        "author" => book_decode_entities_recursive(isset($record["author"]) ? $record["author"] : ""),
        "title" => book_decode_entities_recursive(isset($record["title"]) ? $record["title"] : ""),
        "subtitle" => book_decode_entities_recursive(isset($record["subtitle"]) ? $record["subtitle"] : ""),
        "description" => book_decode_entities_recursive(isset($record["description"]) ? $record["description"] : ""),
        "price" => (string)(isset($record["price"]) ? $record["price"] : ""),
        "price_ebay" => (string)(isset($record["price_ebay"]) ? $record["price_ebay"] : ""),
        "price_amazon" => (string)(isset($record["price_amazon"]) ? $record["price_amazon"] : ""),
        "notes" => book_decode_entities_recursive(isset($record["notes"]) ? $record["notes"] : ""),
        "internet" => (int)(isset($record["internet"]) ? $record["internet"] : 0),
        "onEbay" => (int)(isset($record["onEbay"]) ? $record["onEbay"] : 0),
        "ebayUseDescriptionTemplate" => (int)(isset($record["ebayUseDescriptionTemplate"]) ? $record["ebayUseDescriptionTemplate"] : 0),
        "location" => book_decode_entities_recursive(isset($record["location"]) ? $record["location"] : ""),
        "isbn" => book_decode_entities_recursive(isset($record["isbn"]) ? $record["isbn"] : ""),
        "editor" => book_decode_entities_recursive(isset($record["editor"]) ? $record["editor"] : ""),
        "collection" => book_decode_entities_recursive(isset($record["collection"]) ? $record["collection"] : ""),
        "publishDate" => book_decode_entities_recursive(isset($record["publishDate"]) ? $record["publishDate"] : ""),
        "publishLocation" => book_decode_entities_recursive(isset($record["publishLocation"]) ? $record["publishLocation"] : ""),
        "format" => (string)(isset($record["format"]) ? $record["format"] : ""),
        "pageNumber" => (string)(isset($record["pageNumber"]) ? $record["pageNumber"] : ""),
        "weight_g" => (string)(isset($record["weight_g"]) ? $record["weight_g"] : "0"),
        "binding" => (string)(isset($record["binding"]) ? $record["binding"] : ""),
        "language" => (string)(isset($record["language"]) ? $record["language"] : ""),
        "condition" => (string)(isset($record["condition"]) ? $record["condition"] : ""),
        "ebayCategoryId" => (string)(isset($record["ebayCategoryId"]) ? $record["ebayCategoryId"] : ""),
        "ebayCategoryIdSecondary" => (string)(isset($record["ebayCategoryIdSecondary"]) ? $record["ebayCategoryIdSecondary"] : ""),
        "ebayShippingCost" => (string)(isset($record["ebayShippingCost"]) ? $record["ebayShippingCost"] : ""),
        "ebayShippingCostInternational" => (string)(isset($record["ebayShippingCostInternational"]) ? $record["ebayShippingCostInternational"] : ""),
        "ebayPolicyMode" => (string)(isset($record["ebayPolicyMode"]) ? $record["ebayPolicyMode"] : "default"),
        "ebayPaymentPolicyIdOverride" => (string)(isset($record["ebayPaymentPolicyIdOverride"]) ? $record["ebayPaymentPolicyIdOverride"] : ""),
        "ebayFulfillmentPolicyIdOverride" => (string)(isset($record["ebayFulfillmentPolicyIdOverride"]) ? $record["ebayFulfillmentPolicyIdOverride"] : ""),
        "ebayReturnPolicyIdOverride" => (string)(isset($record["ebayReturnPolicyIdOverride"]) ? $record["ebayReturnPolicyIdOverride"] : ""),
        "ebayItemId" => (string)(isset($record["ebayItemId"]) ? $record["ebayItemId"] : ""),
        "ebayLastSync" => (int)(isset($record["ebayLastSync"]) ? $record["ebayLastSync"] : 0),
        "quantity" => (int)(isset($record["quantity"]) ? $record["quantity"] : 0),
        "modifyDate" => (int)(isset($record["modifyDate"]) ? $record["modifyDate"] : 0),
    );
}

function book_json_response($ok, $code, $message, $bookData = array(), $ui = array(), $errors = array())
{
    if (function_exists('ob_get_level'))
    {
        while (ob_get_level() > 0)
            @ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    $payload = array(
        "ok" => (bool)$ok,
        "code" => (string)$code,
        "message" => (string)$message,
        "book" => is_array($bookData) ? $bookData : array(),
        "ui" => is_array($ui) ? $ui : array(),
        "errors" => is_array($errors) ? array_values($errors) : array(),
    );
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json === false)
        $json = "{\"ok\":false,\"code\":\"server_error\",\"message\":\"Reponse JSON impossible a encoder.\",\"book\":{},\"ui\":{},\"errors\":[\"json_encode failed\"]}";
    echo $json;
    exit;
}

function book_suggest_json_response($ok, $field, $query, $suggestions = array(), $message = "")
{
    if (function_exists('ob_get_level'))
    {
        while (ob_get_level() > 0)
            @ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    $payload = array(
        "ok" => (bool)$ok,
        "field" => (string)$field,
        "q" => (string)$query,
        "suggestions" => is_array($suggestions) ? array_values($suggestions) : array(),
        "message" => (string)$message,
    );
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json === false)
        $json = "{\"ok\":false,\"field\":\"" . addslashes((string)$field) . "\",\"q\":\"\",\"suggestions\":[],\"message\":\"Reponse JSON impossible a encoder.\"}";
    echo $json;
    exit;
}

function book_isbn_lookup_json_response($ok, $code, $message, $data = array(), $errors = array())
{
    if (function_exists('ob_get_level'))
    {
        while (ob_get_level() > 0)
            @ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    $payload = array(
        "ok" => (bool)$ok,
        "code" => (string)$code,
        "message" => (string)$message,
        "data" => is_array($data) ? $data : array(),
        "errors" => is_array($errors) ? array_values($errors) : array(),
    );
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json === false)
        $json = "{\"ok\":false,\"code\":\"server_error\",\"message\":\"Reponse JSON impossible a encoder.\",\"data\":{},\"errors\":[\"json_encode failed\"]}";
    echo $json;
    exit;
}

function book_normalize_isbn($value)
{
    $value = trim((string)$value);
    $value = str_replace(array(" ", "-"), "", $value);
    $value = strtoupper($value);
    return $value;
}

function book_export_cell_value($value)
{
    if ($value === null)
        return "";
    if (is_bool($value))
        return $value ? "1" : "0";
    if (is_array($value) || is_object($value))
    {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false)
            return "";
        return $json;
    }
    return book_decode_entities_recursive((string)$value);
}

function book_export_lookup_map($book, $table, $idColumn, $nameColumn)
{
    $map = array();
    $idGroup = isset($_SESSION["idGroup"]) ? (int)$_SESSION["idGroup"] : 0;
    if ($idGroup <= 0)
        return $map;

    $sql = "SELECT `" . $idColumn . "` AS id, `" . $nameColumn . "` AS name FROM `" . $table . "` WHERE `idGroup`='" . $idGroup . "'";
    $rows = $book->babase->DbSelect($sql, $nbRows);
    if (!is_array($rows))
        return $map;

    $i = 0;
    while ($i < sizeof($rows))
    {
        $id = isset($rows[$i]["id"]) ? trim((string)$rows[$i]["id"]) : "";
        if ($id !== "")
            $map[$id] = book_decode_entities_recursive((string)(isset($rows[$i]["name"]) ? $rows[$i]["name"] : ""));
        $i++;
    }
    return $map;
}

function book_export_is_date_field($field)
{
    $field = strtolower((string)$field);
    if ($field === "")
        return false;

    if (strpos($field, "date") !== false)
        return true;
    if (strpos($field, "sync") !== false)
        return true;
    return false;
}

function book_export_format_datetime($value)
{
    $raw = trim(book_decode_entities_recursive((string)$value));
    if ($raw === "")
        return "";

    if (is_numeric($raw))
    {
        $ts = (int)$raw;
        if ($ts <= 0)
            return "";
        if (strlen($raw) >= 13)
            $ts = (int)floor($ts / 1000);
        return date("Y-m-d H:i:s", $ts);
    }

    $ts = strtotime($raw);
    if ($ts !== false)
        return date("Y-m-d H:i:s", $ts);
    return $raw;
}

function book_export_category_map($bookRows, $book)
{
    $map = array();
    if (!is_array($bookRows) || sizeof($bookRows) === 0)
        return $map;

    $ids = array();
    $i = 0;
    while ($i < sizeof($bookRows))
    {
        $idBook = isset($bookRows[$i]["idBook"]) ? (int)$bookRows[$i]["idBook"] : 0;
        if ($idBook > 0)
            $ids[$idBook] = $idBook;
        $i++;
    }

    if (sizeof($ids) === 0)
        return $map;

    $idGroup = isset($_SESSION["idGroup"]) ? (int)$_SESSION["idGroup"] : 0;
    $chunks = array_chunk(array_values($ids), 500);
    $chunkIndex = 0;
    while ($chunkIndex < sizeof($chunks))
    {
        $chunk = $chunks[$chunkIndex];
        $idList = implode(",", $chunk);
        $sql = "SELECT bic.idBook, GROUP_CONCAT(DISTINCT cat.name ORDER BY cat.name SEPARATOR ' | ') AS categories " .
               "FROM bookincategory AS bic " .
               "INNER JOIN category AS cat ON cat.idCategory=bic.idCategory AND cat.idGroup='" . $idGroup . "' " .
               "WHERE bic.idBook IN (" . $idList . ") GROUP BY bic.idBook";
        $rows = $book->babase->DbSelect($sql, $nbRows);
        if (is_array($rows))
        {
            $j = 0;
            while ($j < sizeof($rows))
            {
                $idBook = isset($rows[$j]["idBook"]) ? (int)$rows[$j]["idBook"] : 0;
                if ($idBook > 0)
                    $map[$idBook] = book_decode_entities_recursive((string)(isset($rows[$j]["categories"]) ? $rows[$j]["categories"] : ""));
                $j++;
            }
        }
        $chunkIndex++;
    }

    return $map;
}

function book_export_search_results_xlsx($book, $txt)
{
    $sqlSearch = isset($_SESSION["searchcache"]) ? (string)$_SESSION["searchcache"] : "";
    if ($sqlSearch === "")
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo "Aucune recherche en cache à exporter.";
        exit;
    }

    $sqlOrder = isset($_SESSION["ordercache"]) ? (string)$_SESSION["ordercache"] : " ORDER BY author,title";
    $rows = $book->babase->DbSelect($sqlSearch . $sqlOrder, $nbRows);
    if (!is_array($rows))
        $rows = array();

    $columnRows = $book->babase->DbSelect("SHOW COLUMNS FROM `book`", $nbColumns);
    $bookFields = array();
    if (is_array($columnRows))
    {
        $i = 0;
        while ($i < sizeof($columnRows))
        {
            $field = isset($columnRows[$i]["Field"]) ? trim((string)$columnRows[$i]["Field"]) : "";
            if ($field !== "")
                $bookFields[] = $field;
            $i++;
        }
    }
    if (sizeof($bookFields) === 0)
        $bookFields = array("idBook", "author", "title", "subtitle", "description", "price", "quantity", "location");

    $categoryMap = book_export_category_map($rows, $book);
    $bindingMap = book_export_lookup_map($book, "binding", "idBinding", "bindingName");
    $languageMap = book_export_lookup_map($book, "language", "idLanguage", "languageName");
    $formatMap = book_export_lookup_map($book, "format", "idFormat", "formatName");

    try
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ouvrages');

        $headers = $bookFields;
        $headers[] = "rubriques";

        $col = 1;
        $h = 0;
        while ($h < sizeof($headers))
        {
            $sheet->setCellValueByColumnAndRow($col, 1, $headers[$h]);
            $col++;
            $h++;
        }

        $rowNum = 2;
        $r = 0;
        while ($r < sizeof($rows))
        {
            $col = 1;
            $f = 0;
            while ($f < sizeof($bookFields))
            {
                $field = $bookFields[$f];
                $value = isset($rows[$r][$field]) ? $rows[$r][$field] : "";
                $fieldLower = strtolower((string)$field);

                if ($fieldLower === "binding")
                {
                    $bindingId = trim((string)$value);
                    if ($bindingId !== "" && isset($bindingMap[$bindingId]))
                        $value = $bindingMap[$bindingId];
                }
                else if ($fieldLower === "language")
                {
                    $languageId = trim((string)$value);
                    if ($languageId !== "" && isset($languageMap[$languageId]))
                        $value = $languageMap[$languageId];
                }
                else if ($fieldLower === "format")
                {
                    $formatId = trim((string)$value);
                    if ($formatId !== "" && isset($formatMap[$formatId]))
                        $value = $formatMap[$formatId];
                }

                if (book_export_is_date_field($fieldLower))
                    $value = book_export_format_datetime($value);

                $sheet->setCellValueExplicitByColumnAndRow(
                    $col,
                    $rowNum,
                    book_export_cell_value($value),
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                );
                $col++;
                $f++;
            }

            $idBook = isset($rows[$r]["idBook"]) ? (int)$rows[$r]["idBook"] : 0;
            $categories = ($idBook > 0 && isset($categoryMap[$idBook])) ? $categoryMap[$idBook] : "";
            $sheet->setCellValueExplicitByColumnAndRow(
                $col,
                $rowNum,
                book_export_cell_value($categories),
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );
            $rowNum++;
            $r++;
        }

        $lastHeaderColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(sizeof($headers));
        $sheet->getStyle('A1:' . $lastHeaderColumn . '1')->getFont()->setBold(true);
        $sheet->freezePane('A2');

        $filename = "gestlibr-ouvrages-" . date("Ymd-His") . ".xlsx";
        if (function_exists('ob_get_level'))
        {
            while (ob_get_level() > 0)
                @ob_end_clean();
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
        header('Pragma: public');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
    catch (\Throwable $e)
    {
        if (!headers_sent())
        {
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
        }
        echo "Export XLSX impossible : " . $e->getMessage();
        exit;
    }
}

function book_render_category_html($book, $category, $config, $txt, $fromresultsearch = "", $autosaveEnabled = false)
{
    $bookcategory = $book->listCategory();
    if (!is_array($bookcategory))
        $bookcategory = array();

    $templateBase = "template/" . $config["template"];
    $html = "<table class='book-inline-table' border='1'>";
    $i = 0;
    while ($i != sizeof($bookcategory))
    {
        if ($autosaveEnabled)
            $onclick = "";
        else
            $onclick = " onclick=\"return confirmLink(this, '" . $txt["bookInCatConfirmErase"] . "')\"";
        $html .= "<tr><td><a class='book-chip-remove book-category-del' href='?m=bo&a=vi&sa=mo&id=" . $book->idBook . "&delcategory=" . $bookcategory[$i]["idCategory"] . $fromresultsearch . "' data-book-action='category_del' data-category-id='" . $bookcategory[$i]["idCategory"] . "'" . $onclick . "><img class='icon-svg' src='" . $templateBase . "/images/icon-trash.svg' alt='" . $txt["erase"] . "'></a></td><td>" . $bookcategory[$i]["name"] . "</td></tr>";
        $i++;
    }
    $html .= "</table>" . $txt["addCategory"] . "<br />";

    $catList = $category->getList();
    if (!is_array($catList))
        $catList = array();
    if ($autosaveEnabled)
        $onchange = "";
    else
        $onchange = " onchange=\"this.form.submit();\"";
    $html .= "<SELECT NAME='category' class='book-category-add'" . $onchange . "><OPTION VALUE='' SELECTED></OPTION>";
    $i = 0;
    while ($i != sizeof($catList))
    {
        if (strlen($catList[$i]["name"]) > 30)
            $name = substr($catList[$i]["name"], 0, 30) . "...";
        else
            $name = $catList[$i]["name"];
        $html .= "<OPTION VALUE='" . $catList[$i]["idCategory"] . "'>" . $name . "</OPTION>";
        $i++;
    }
    $html .= "</SELECT>";
    return $html;
}

function book_render_catalog_html($book, $catalog, $config, $txt, $fromresultsearch = "", $autosaveEnabled = false)
{
    $catalogList = $book->listCatalog();
    if (!is_array($catalogList))
        $catalogList = array();

    $templateBase = "template/" . $config["template"];
    $html = "<table class='book-inline-table' border='1'>";
    $i = 0;
    while ($i != sizeof($catalogList))
    {
        if (strlen($catalogList[$i]["name"]) > 20)
            $name = substr($catalogList[$i]["name"], 0, 20) . "...";
        else
            $name = $catalogList[$i]["name"];

        $catalog->idCatalog = $catalogList[$i]["idCatalog"];
        if (!($catalog->islocked()))
        {
            if ($autosaveEnabled)
                $onclick = "";
            else
                $onclick = " onclick=\"return confirmLink(this, '" . $txt["bookInCatalogConfirmErase"] . "')\"";
            $del = "<a class='book-chip-remove book-catalog-del' href='?m=bo&a=vi&sa=mo&id=" . $book->idBook . "&delfromcatalog=" . $catalogList[$i]["idCatalog"] . $fromresultsearch . "' data-book-action='catalog_del' data-catalog-id='" . $catalogList[$i]["idCatalog"] . "'" . $onclick . "><img class='icon-svg' src='" . $templateBase . "/images/icon-trash.svg' alt='" . $txt["erase"] . "'></a>";
        }
        else
            $del = "";

        $html .= "<tr><td>" . $del . "</td><td>" . $name . " (" . date("d/m/Y", $catalogList[$i]["date"]) . ")</td></tr>";
        $i++;
    }

    $catUnlocked = $catalog->getListUnlocked();
    if (!is_array($catUnlocked))
        $catUnlocked = array();

    if ($autosaveEnabled)
        $onchange = "";
    else
        $onchange = " onchange=\"this.form.submit();\"";
    $html .= "</table>" . $txt["addToCatalog"] . " :<br /><SELECT NAME='addInCatalog' class='book-catalog-add'" . $onchange . "><OPTION VALUE='' SELECTED></OPTION>";
    $j = 0;
    while ($j != sizeof($catUnlocked))
    {
        $catalog->idCatalog = $catUnlocked[$j]["idCatalog"];
        $chap = $catalog->getChapter();
        if (!is_array($chap))
            $chap = array();
        $jj = 0;
        while ($jj != sizeof($chap))
        {
            $html .= "<OPTION VALUE='" . $chap[$jj]["idCatalog"] . "-" . $chap[$jj]["orderInCatalog"] . "'>" . $catUnlocked[$j]["name"] . " - " . substr($chap[$jj]["name"], 0, 30) . "</OPTION>";
            $jj++;
        }
        $j++;
    }
    $html .= "</SELECT>";
    return $html;
}

function book_ui_payload($book, $category, $catalog, $config, $txt, $fromresultsearch = "", $autosaveEnabled = false)
{
    return array(
        "categoryHtml" => book_render_category_html($book, $category, $config, $txt, $fromresultsearch, $autosaveEnabled),
        "catalogHtml" => book_render_catalog_html($book, $catalog, $config, $txt, $fromresultsearch, $autosaveEnabled),
    );
}

function book_normalize_changes($changes, &$errors)
{
    if (!is_array($errors))
        $errors = array();

    $allowed = array(
        "author", "title", "subtitle", "description", "price", "price_ebay", "price_amazon", "notes",
        "internet", "onEbay", "location", "isbn", "editor", "collection", "publishDate",
        "publishLocation", "format", "pageNumber", "weight_g", "binding", "language", "condition",
        "ebayCategoryId", "ebayCategoryIdSecondary", "ebayShippingCost", "ebayShippingCostInternational",
        "ebayUseDescriptionTemplate", "ebayPolicyMode", "ebayPaymentPolicyIdOverride", "ebayFulfillmentPolicyIdOverride", "ebayReturnPolicyIdOverride"
    );
    $numericDecimal = array("price", "price_ebay", "price_amazon", "ebayShippingCost", "ebayShippingCostInternational");
    $numericInt = array("pageNumber", "weight_g");
    $legacySelect = array("format", "binding", "language", "condition");
    $booleanFields = array("internet", "onEbay", "ebayUseDescriptionTemplate");

    $normalized = array();
    foreach ($changes as $field => $value)
    {
        if (!in_array($field, $allowed, true))
            continue;

        if (is_array($value))
        {
            $errors[] = "Valeur invalide pour " . $field . ".";
            continue;
        }

        $value = trim((string)$value);

        if ($field == "location")
            $value = strtoupper($value);
        if (in_array($field, $legacySelect, true) && $value === "-1")
            $value = "";
        if (in_array($field, $booleanFields, true))
            $value = ($value === "1" || strtolower($value) === "true" || strtolower($value) === "on") ? "1" : "0";
        if ($field == "ebayPolicyMode")
        {
            if ($value !== "override")
                $value = "default";
        }

        if (in_array($field, $numericDecimal, true))
        {
            if ($value !== "")
            {
                $tmp = str_replace(",", ".", $value);
                if (!preg_match('/^-?\d+(\.\d+)?$/', $tmp))
                {
                    $errors[] = "Valeur numerique invalide pour " . $field . ".";
                    continue;
                }
                $value = $tmp;
            }
        }
        else if (in_array($field, $numericInt, true))
        {
            if ($value !== "" && !preg_match('/^\d+$/', $value))
            {
                $errors[] = "Valeur entiere invalide pour " . $field . ".";
                continue;
            }
        }

        $normalized[$field] = $value;
    }
    return $normalized;
}

function book_fetch_ebay_policy_catalog_rows($idGroup, $policyType)
{
    global $babase;
    $idGroup = (int)$idGroup;
    $policyType = trim((string)$policyType);
    if ($idGroup <= 0 || $policyType == "")
        return array();
    $safeType = mysqli_real_escape_string($babase->DB_CONN, $policyType);
    $rows = $babase->DbSelect(
        "SELECT `policyId`, `name`, `description` FROM `ebay_policy_catalog` WHERE `idGroup`='" . $idGroup . "' AND `policyType`='" . $safeType . "' ORDER BY `name` ASC, `policyId` ASC",
        $nb
    );
    if ($nb <= 0 || !is_array($rows))
        return array();
    return $rows;
}

function book_build_ebay_policy_select($fieldName, $selectedValue, $policyRows, $autosaveField)
{
    $selectedValue = trim((string)$selectedValue);
    $html = "<select name='" . htmlspecialchars($fieldName, ENT_QUOTES, "UTF-8") . "'";
    if ($autosaveField != "")
        $html .= " data-autosave-field='" . htmlspecialchars($autosaveField, ENT_QUOTES, "UTF-8") . "'";
    $html .= ">";
    $html .= "<option value=''>Par défaut groupe</option>";

    $i = 0;
    while ($i < sizeof($policyRows))
    {
        $policyId = isset($policyRows[$i]["policyId"]) ? trim((string)$policyRows[$i]["policyId"]) : "";
        $name = isset($policyRows[$i]["name"]) ? trim((string)$policyRows[$i]["name"]) : "";
        if ($policyId != "")
        {
            $label = ($name != "") ? ($name . " (" . $policyId . ")") : $policyId;
            $selected = ($policyId === $selectedValue) ? " selected='selected'" : "";
            $html .= "<option value='" . htmlspecialchars($policyId, ENT_QUOTES, "UTF-8") . "'" . $selected . ">" . htmlspecialchars($label, ENT_QUOTES, "UTF-8") . "</option>";
        }
        $i++;
    }

    $html .= "</select>";
    return $html;
}

function book_build_ebay_policy_mode_select($selectedValue, $autosaveField)
{
    $selectedValue = trim((string)$selectedValue);
    if ($selectedValue !== "override")
        $selectedValue = "default";

    $html = "<select name='ebayPolicyMode'";
    if ($autosaveField != "")
        $html .= " data-autosave-field='" . htmlspecialchars($autosaveField, ENT_QUOTES, "UTF-8") . "'";
    $html .= ">";
    $html .= "<option value='default'" . (($selectedValue === "default") ? " selected='selected'" : "") . ">Par défaut (groupe)</option>";
    $html .= "<option value='override'" . (($selectedValue === "override") ? " selected='selected'" : "") . ">Override ouvrage</option>";
    $html .= "</select>";
    return $html;
}

switch ($a) {
    case "cr": 
        $bookActionCreate = isset($_REQUEST["book_action"]) ? (string)$_REQUEST["book_action"] : "";
        if ($bookActionCreate === "isbn_lookup")
        {
            $isbnRaw = isset($_REQUEST["isbn"]) ? $_REQUEST["isbn"] : "";
            $isbn = book_normalize_isbn($isbnRaw);
            $isbnLength = function_exists("mb_strlen") ? mb_strlen($isbn, "UTF-8") : strlen($isbn);

            if ($isbnLength !== 10 && $isbnLength !== 13)
                book_isbn_lookup_json_response(false, "validation_error", "ISBN invalide: 10 ou 13 caracteres requis.", array(
                    "prefill" => array(),
                    "duplicate" => array("found" => false),
                    "source" => ""
                ), array("ISBN invalide."));

            $duplicate = $book->findByIsbnStrict($isbn);
            if ($duplicate !== false)
            {
                $duplicateId = isset($duplicate["idBook"]) ? (int)$duplicate["idBook"] : 0;
                $duplicateData = array(
                    "found" => true,
                    "idBook" => $duplicateId,
                    "openUrl" => ($duplicateId > 0 ? "?m=bo&a=vi&sa=mo&id=" . $duplicateId : ""),
                    "title" => (string)(isset($duplicate["title"]) ? book_decode_entities_recursive($duplicate["title"]) : ""),
                    "author" => (string)(isset($duplicate["author"]) ? book_decode_entities_recursive($duplicate["author"]) : ""),
                );
                book_isbn_lookup_json_response(true, "duplicate", "Cet ISBN existe deja dans la base.", array(
                    "prefill" => array(),
                    "duplicate" => $duplicateData,
                    "source" => "local"
                ), array());
            }

            $result = $book->IsbnSearch($isbn);
            $source = (string)(isset($result["method"]) ? $result["method"] : "");
            if (!is_array($result) || !isset($result["found"]) || !$result["found"])
            {
                $errors = array();
                if (isset($result["debug"]) && is_array($result["debug"]))
                    $errors = $result["debug"];
                book_isbn_lookup_json_response(false, "not_found", "Aucun resultat trouve pour cet ISBN.", array(
                    "prefill" => array(),
                    "duplicate" => array("found" => false),
                    "source" => $source
                ), $errors);
            }

            $prefill = array("isbn" => $isbn);
            $fieldMap = array(
                "author" => "author",
                "title" => "title",
                "subtitle" => "subtitle",
                "description" => "description",
                "editor" => "editor",
                "publishDate" => "publishDate",
                "pageNumber" => "pageNumber",
                "publishLocation" => "publishLocation",
                "collection" => "collection",
            );
            foreach ($fieldMap as $targetField => $sourceField)
            {
                $value = isset($result[$sourceField]) ? trim((string)$result[$sourceField]) : "";
                if ($value !== "")
                    $prefill[$targetField] = book_decode_entities_recursive($value);
            }

            book_isbn_lookup_json_response(true, "ok", "Donnees ISBN recuperees.", array(
                "prefill" => $prefill,
                "duplicate" => array("found" => false),
                "source" => $source
            ), array());
        }

        // Display create header
        $template->set_filenames(array('bookCreateHeader' => 'book_create_header.tpl'));
        $template->assign_vars(array('BOOK_CREATE' => $txt["createBook"], 'CREATEFROMISBN' => $txt["returnIsbn"]));
	
        // Filling category data
	$catList=$category->getList();
        $cat="<SELECT NAME='category'><OPTION VALUE='' SELECTED></OPTION>";
        $i=0;
        while($i!=(sizeof($catList)))
        {
            if (strlen($catList[$i]["name"])>30) $name = substr($catList[$i]["name"], 0, 30) . "...";
	    else $name=$catList[$i]["name"];
	    $cat.="<OPTION VALUE='".$catList[$i]["idCategory"]."'>".$name."</OPTION>";
	    $i++;
	}
        $cat.="</SELECT>";
	        
	        // Generating shorcuts to add to an open catalog
		$catUnlocked=$catalog->getListUnlocked();
		$inCatalog = "";
		$inCatalog.= $txt["addToCatalog"] . " :<br /><SELECT NAME='addInCatalog'><OPTION VALUE='' SELECTED></OPTION>";
	$j=0;
	while($j!=(sizeof($catUnlocked)))
	{
	    $catalog->idCatalog=$catUnlocked[$j]["idCatalog"];
	    $chap=$catalog->getChapter();
	    $jj=0;
	    while($jj!=(sizeof($chap)))
	    {
		$inCatalog .= "<OPTION VALUE='" . $chap[$jj]["idCatalog"] . "-" . $chap[$jj]["orderInCatalog"] . "'>" . $catUnlocked[$j]["name"] . " - " . substr($chap[$jj]["name"], 0, 30) . "</OPTION>";
		$jj++;
	    }
	    $j++;
        }
	$inCatalog .= "</SELECT>";
				
        // Display empty mask
        $template->set_filenames(array('bookCreateMask' => 'book_main_mask.tpl'));
        $template->_tpldata["bookEbaySale"] = array();
        $template->assign_vars(array('BOOKS_ID' => $txt["id"], 'BOOKS_AUTHOR' => $txt["author"], 'BOOKS_TITLE' => $txt["title"], 'BOOKS_SUBTITLE' => (isset($txt["subtitle"]) ? $txt["subtitle"] : "Sous-titre"), 'BOOKS_ON_INTERNET' => $txt["onInternet"], 'BOOKS_LOCATION' => $txt["location"], 'BOOKS_DESCRIPTION' => $txt["description"], 'BOOKS_PRICE' => $txt["price"], 'BOOKS_PRICE_EBAY' => $txt["price_ebay"], 'BOOKS_PRICE_AMAZON' => $txt["price_amazon"], 'BOOKS_QUANTITY' => $txt["quantity"], 'BOOKS_NOTES' => $txt["notes"], 'BOOKS_CATEGORY' => $txt["category"], 'BOOKS_ON_EBAY' => $txt["onEbay"], 'BOOKS_ISBN' => $txt["isbn"], 'BOOKS_EDITOR' => $txt["editor"], 'BOOKS_LANGUAGE' => $txt["language"], 'BOOKS_COLLECTION' => $txt["collection"], 'BOOKS_PUBLISHDATE' => $txt["publishDate"], 'BOOKS_PUBLISHLOCATION' => $txt["publishLocation"], 'BOOKS_FORMAT' => $txt["format"], 'BOOKS_PAGENUMBER' => $txt["pageNumber"], 'BOOKS_WEIGHT_G' => "Poids (g)", 'BOOKS_BINDING' => $txt["binding"], 'BOOKS_EBAYCATEGORY' => $txt["ebayCategory"], 'BOOKS_EBAYCATEGORYSECONDARY' => $txt["ebayCategorySecondary"], 'BOOKS_EBAYITEMID' => $txt["ebayItemId"], 'BOOKS_EBAYLASTSYNC' => $txt["ebayLastSync"], 'BOOKS_EBAYSHIPPINGCOST' => $txt["ebayShippingCost"], 'BOOKS_EBAYSHIPPINGCOSTINTERNATIONAL' => $txt["ebayShippingCostInternational"], 'BOOKS_CONDITION' => $txt["condition"], 'BOOKS_PHOTOS' => $txt["photos"],
		'LANGUAGE_V' => $book->getLanguageHtmlForm(config("defaultBookLanguage")),
		'BINDING_V' => $book->getBindingHtmlForm(),
		'FORMAT_V' => $book->getFormatHtmlForm(),
		'CONDITION_V' => $book->getConditionHtmlForm(),
		'EBAYCATEGORY_V' => $book->getEbayCategoryHtmlForm(1),
		'EBAYCATEGORYSECONDARY_V' => $book->getEbayCategoryHtmlForm(2),
                        'SUBTITLE_V' => "",
			'EBAY_POLICY_MODE_V' => "",
			'EBAY_POLICY_PAYMENT_OVERRIDE_V' => "",
			'EBAY_POLICY_FULFILLMENT_OVERRIDE_V' => "",
			'EBAY_POLICY_RETURN_OVERRIDE_V' => "",
			'EBAY_USE_DESCRIPTION_TEMPLATE_V' => "checked='checked'",
			'IN_CATALOG_V' => $inCatalog,
	                'ONEBAY_V' => "checked='checked'",
	                'INTERNET_V' => "checked='checked'",
	                'QUANTITY_V' => "1",
	                'EBAYSHIPPINGCOST_V' => config("ebayShippingCost"),
	                'EBAYSHIPPINGCOSTINTERNATIONAL_V' => config("ebayShippingCostInternational"),
	                'CATEGORY_V' => $cat,
                        'PHOTO_MANAGER_ENABLED' => "1",
                        'PHOTO_MANAGER_MODE' => "create",
                        'PHOTO_API_URL' => "",
                        'PHOTO_MAX_FILES' => "20",
                        'PHOTO_MAX_SIZE_MB' => "10",
                        'PHOTO_HELP_TEXT' => "Ajoutez vos photos maintenant: elles seront rattachees a la fiche lors de l'enregistrement.",
                        'BOOK_AUTOSAVE_ENABLED' => "0",
                        'BOOK_FORM_MODE' => "create",
                        'BOOK_API_URL' => "",
                        'BOOK_ISBN_LOOKUP_URL' => "?m=bo&a=cr&book_action=isbn_lookup",
                        'BOOK_ISBN_AUTOFOCUS' => "autofocus='autofocus'",
                        'BOOK_AUTHOR_AUTOFOCUS' => "",
                        'BOOK_SEARCH_AUTOCOMPLETE_ENABLED' => "0",
                        'BOOK_SEARCH_SUGGEST_URL' => "",
                        'BOOK_SEARCH_SUGGEST_MIN_CHARS' => "4",
                        'CLIENT_MODIFY_DATE_V' => "0",
                        'BOOK_AUTOSAVE_STATUS' => "",
                        'BOOK_ID_READONLY' => "readonly='readonly'",
	                        'BOOK_ID_PLACEHOLDER' => "Auto",
	                        'BOOK_ID_META' => "",
	                        'WEIGHT_G_V' => "0",
	                        'EBAYITEMID_V' => "—",
		                        'EBAYITEMID_LINK_HREF' => "#",
		                        'EBAYITEMID_LINK_TARGET' => "_self",
		                        'EBAYITEMID_LINK_CLASS' => " book-ebay-item-link--empty",
		                        'EBAYLASTSYNC_V' => "—",
		                        'EBAYLASTSYNC_TS_V' => "0",
		                        'EBAY_SYNC_NEEDED_HIDDEN' => "hidden='hidden'",
		                        'EBAYADD_V' => "",
		                        'EBAYDEL_V' => "",
		                        'EBAYUPDATE_V' => "",
	                        'EBAY_SALES_EMPTY' => "",
	                        'BOOKS_FORM' => "?m=bo&a=cr", // FORM Target URL <----------------------
	                        'BOOKS_SUBMIT' => $txt["submit"]));

	        if ($postSub == "ok")
		{ // If form is filled...
            if (($_POST["title"] AND $_POST["author"]))
	    { // Check for correct entry
                // Ok, we're recording the new book
                $book->assignData($_POST);
                $add=$book->add();
                if ($add > -1)
                {
                    $book->idBook = $add;
                    $createMsg = $txt["bookCreateOk"];
                    if ($postCategory!="")
                        $book->addCategory($postCategory);
                    if ($postAddInCatalog!="")
                    {
                        $addInCatalog = explode('-', $postAddInCatalog);
                        $catalog->idCatalog = $addInCatalog[0];
                        $catalog->addBook($book->idBook, $addInCatalog[1] + 1);
                        $createMsg .= "<br />" . $txt["addInCatalogDone"];
                    }

                    $hasBulkUpload = false;
                    if (isset($_FILES['photos']['tmp_name']))
                    {
                        if (is_array($_FILES['photos']['tmp_name']))
                        {
                            $kk = 0;
                            while ($kk < sizeof($_FILES['photos']['tmp_name']))
                            {
                                if ($_FILES['photos']['tmp_name'][$kk] != "")
                                {
                                    $hasBulkUpload = true;
                                    break;
                                }
                                $kk++;
                            }
                        }
                        else if ($_FILES['photos']['tmp_name'] != "")
                            $hasBulkUpload = true;
                    }

                    if ($hasBulkUpload)
                    {
                        $uploadResult = $book->addPhotosBulk($book->idBook, $_FILES['photos']);
                        if (((int)$uploadResult["added"]) > 0)
                            $createMsg .= "<br />" . $uploadResult["added"] . " photo(s) ajoutee(s).";
                        if (is_array($uploadResult["errors"]) && sizeof($uploadResult["errors"]) > 0)
                        {
                            $kk = 0;
                            while ($kk < sizeof($uploadResult["errors"]))
                            {
                                $createMsg .= "<br />" . $uploadResult["errors"][$kk];
                                $kk++;
                            }
                        }
                    }

                    if ($postOpt1=="fromisbnimport")
                        $template->assign_block_vars('isbnImportReturn', array());

                    $template->assign_vars(array('BOOK_MSG' => $createMsg));
                }
                else
			    $template->assign_vars(array('BOOK_MSG' => $txt["bookCreateDbError"]));
                $template->pparse('bookCreateHeader');
            }
	    else
	    { // minimal fields are not set !
                // We fill the form with actual values
                $template->assign_vars(array('AUTHOR_V' => $_POST["author"], 'TITLE_V' => $_POST["title"], 'SUBTITLE_V' => (isset($_POST["subtitle"]) ? $_POST["subtitle"] : ""), 'DESCRIPTION_V' => book_decode_entities_recursive($_POST["description"]), 'PRICE_V' => $_POST["price"], 'PRICE_EBAY_V' => $_POST["price_ebay"], 'PRICE_AMAZON_V' => $_POST["price_amazon"], 'QUANTITY_V' => $_POST["quantity"], 'LOCATION_V' => $_POST["location"], 'NOTES_V' => $_POST["notes"], 'ISBN_V' => $_POST["isbn"], 'ONEBAY_V' => "checked='checked'", 'INTERNET_V' => "checked='checked'", 'EDITOR_V' => $_POST["editor"], 'COLLECTION_V' => $_POST["collection"], 'PUBLISHDATE_V' => $_POST["publishDate"], 'PUBLISHLOCATION_V' => $_POST["publishLocation"], 'PAGENUMBER_V' => $_POST["pageNumber"], 'WEIGHT_G_V' => (isset($_POST["weight_g"]) ? $_POST["weight_g"] : "0"), 'EBAY_USE_DESCRIPTION_TEMPLATE_V' => (isset($_POST["ebayUseDescriptionTemplate"]) ? "checked='checked'" : ""),
                        'BOOKS_FORM' => "?m=bo&a=cr", // FORM Target URL <----------------------
                        'BOOKS_SUBMIT' => $txt["submit"])); 
                // Display mask with error message
                $template->assign_vars(array('BOOK_MSG' => $txt["bookCreateMissedField"]));
                $template->pparse('bookCreateHeader');
                $template->pparse('bookCreateMask');
            } 
        }
	else
	{ // form not filled... New form
	    if (isset($_GET["fromisbn"]))
	    {
		// If the page has been called by ISBN Import, we search online for infos
		$result = $book->IsbnSearch($_GET["fromisbn"]);
		if ($result["found"])
		{
		    // Book found online !
		            $template->assign_vars(array('AUTHOR_V' => $result["author"], 'TITLE_V' => $result["title"], 'SUBTITLE_V' => (isset($result["subtitle"]) ? $result["subtitle"] : ""), 'DESCRIPTION_V' => book_decode_entities_recursive($result["description"]), 'ISBN_V' => $result["isbn"], 'ONEBAY_V' => "checked='checked'", 'INTERNET_V' => "checked='checked'", 'EDITOR_V' => $result["editor"], 'PUBLISHDATE_V' => $result["publishDate"], 'PUBLISHLOCATION_V' => (isset($result["publishLocation"]) ? $result["publishLocation"] : ""), 'PAGENUMBER_V' => $result["pageNumber"], 'WEIGHT_G_V' => (isset($result["weight_g"]) ? $result["weight_g"] : "0"), 'EBAY_USE_DESCRIPTION_TEMPLATE_V' => "checked='checked'", 'OPT1_V' => "fromisbnimport", 'IMAGEFROMURL_V' => $result["image"],
                        'BOOKS_FORM' => "?m=bo&a=cr", // FORM Target URL <----------------------
                        'BOOKS_SUBMIT' => $txt["submit"])); 
	    
		}
	    }
            // display empty mask
            $template->pparse('bookCreateHeader');
            $template->pparse('bookCreateMask');
        } 
        break;
    case "vi":
        switch ($sa)
	{
            case "re": // Display search results
	                $bookAction = isset($_REQUEST["book_action"]) ? (string)$_REQUEST["book_action"] : "";
	                if ($bookAction === "export_excel")
	                    book_export_search_results_xlsx($book, $txt);
	                if ($bookAction === "suggest")
	                {
	                    $field = isset($_REQUEST["field"]) ? trim((string)$_REQUEST["field"]) : "";
	                    $query = isset($_REQUEST["q"]) ? trim((string)$_REQUEST["q"]) : "";
	                    $minChars = 4;
	                    $length = function_exists("mb_strlen") ? mb_strlen($query, "UTF-8") : strlen($query);

	                    if ($field !== "author" && $field !== "title")
	                        book_suggest_json_response(false, $field, $query, array(), "Champ non autorise.");

	                    if ($length < $minChars)
	                        book_suggest_json_response(true, $field, $query, array(), "");

	                    $suggestions = $book->suggestValues($field, $query, 10);
	                    $message = (sizeof($suggestions) > 0) ? "" : "Aucune suggestion.";
	                    book_suggest_json_response(true, $field, $query, $suggestions, $message);
	                }
	                if ($bookAction === "quick_save_location")
	                {
	                    $idBookAjax = isset($_REQUEST["idBook"]) ? (int)$_REQUEST["idBook"] : 0;
	                    if ($idBookAjax <= 0)
	                        book_json_response(false, "validation_error", "ID ouvrage manquant.", array(), array(), array("ID ouvrage manquant."));

	                    $locationRaw = isset($_REQUEST["location"]) ? (string)$_REQUEST["location"] : "";
	                    $locationRaw = trim(book_decode_entities_recursive($locationRaw));
	                    if (function_exists("mb_strtoupper"))
	                        $locationNormalized = mb_strtoupper($locationRaw, "UTF-8");
	                    else
	                        $locationNormalized = strtoupper($locationRaw);

	                    if (!$book->updateFields($idBookAjax, array("location" => $locationNormalized), "autosave:search_location"))
	                        book_json_response(false, "server_error", "Sauvegarde du rayon impossible.", array(), array(), array("Echec updateFields(location)."));

	                    $recordUpdated = $book->get($idBookAjax);
	                    if (!$recordUpdated)
	                        $recordUpdated = array("idBook" => $idBookAjax, "location" => $locationNormalized, "modifyDate" => time());
	                    book_json_response(true, "ok", "Rayon mis à jour.", book_build_payload($recordUpdated), array(), array());
	                }
//                if (($_POST["sub"] == "ok") OR ($_GET["sub"] == "ok")) // If form is filled...
	                if ($postId != "")
	                    $id = $postId;
	                else
			    $id = $getId;
			// We check the start & row attributes...
	                if (($getStart!="") AND ($getRow!=""))
	                {
	                    $start=$getStart;
	                    $row=$getRow;
	                    $_SESSION["start"]=$start;
	                    $_SESSION["row"]=$row;
	                }
	                else
	                {
	                    if ((!empty($_SESSION["start"])) AND (!empty($_SESSION["row"])))
	                    {
			        $start=$_SESSION["start"];
	                        $row=$_SESSION["row"];
                    }
                    else
                    {
                        $start=0;
                        $row=$config["maxBookRow"];
                    }
                }
	                if ($getDel == "1")
			{ // Delete the record whith the id
		    $book->idBook=$id;
                      if ($book->del())
                        $template->assign_vars(array('BOOKS_MSG' => ($txt["bookEraseDone"])));
                      else
                        $template->assign_vars(array('BOOKS_MSG' => ($txt["bookEraseNotOk"])));
                } 

	                if (($getResearch == "ok") or ($postResearch == "ok"))
	                    $search=$book->search("cache","",$start,$row);
	                else
			{
	                    $book->assignData($_POST);
			    $search=$book->search("","",$start,$row);
			}
			if (!is_array($search))
			    $search = array();
			if (!isset($search["nb"]))
			    $search["nb"] = 0;
			$nbResult=$search["nb"];

			// If there is exactly one result, open the book record directly.
			if ((int)$nbResult === 1)
			{
			    $singleId = 0;
			    if (isset($search[0]["idBook"]))
			        $singleId = (int)$search[0]["idBook"];
			    else
			    {
			        // The current page can be empty with stale pagination state;
			        // refetch first row from the cached query.
			        $singleSearch = $book->search("cache", "", 0, 1);
			        if (is_array($singleSearch) && isset($singleSearch[0]["idBook"]))
			            $singleId = (int)$singleSearch[0]["idBook"];
			    }

			    if ($singleId > 0)
			    {
			        $singleUrl = "?m=bo&a=vi&sa=mo&id=" . $singleId;
			        if (!headers_sent())
			        {
			            header("Location: " . $singleUrl);
			            exit;
			        }
			        echo "<script type='text/javascript'>window.location.href=" . json_encode($singleUrl) . ";</script>";
			        echo "<noscript><meta http-equiv='refresh' content='0;url=" . htmlspecialchars($singleUrl, ENT_QUOTES, 'UTF-8') . "'></noscript>";
			        exit;
			    }
			}

			$templateBase = "template/".$config["template"];
			$displayEnd = min($start+$row, $nbResult);
			$navLinks = "";
			if ($start!=0)
			{
			    $navLinks .= " <a class='result-nav__link' href='?m=bo&a=vi&sa=re&research=ok&start=0&row=" . $row . "' title='Premier'><img class='icon-svg' src='" . $templateBase . "/images/icon-chevrons-left.svg' alt='Premier'></a>";
			    $navLinks .= " <a class='result-nav__link' href='?m=bo&a=vi&sa=re&research=ok&start=" . ($start-$row) . "&row=" . $row . "' title='" . $txt["previous"] . "'><img class='icon-svg' src='" . $templateBase . "/images/icon-chevron-left.svg' alt='" . $txt["previous"] . "'><span>" . $txt["previous"] . "</span></a>";
			}
			if (($row+$start)<$nbResult)
			{
			    $navLinks .= " <a class='result-nav__link' href='?m=bo&a=vi&sa=re&research=ok&start=" . ($start+$row) . "&row=" . $row . "' title='" . $txt["next"] . "'><span>" . $txt["next"] . "</span><img class='icon-svg' src='" . $templateBase . "/images/icon-chevron-right.svg' alt='" . $txt["next"] . "'></a>";
			    $navLinks .= " <a class='result-nav__link' href='?m=bo&a=vi&sa=re&research=ok&start=" . ($nbResult-$row) . "&row=" . $row . "' title='Dernier'><img class='icon-svg' src='" . $templateBase . "/images/icon-chevrons-right.svg' alt='Dernier'></a>";
			}

	                // ROW NUMBER MODIFICATION
		                if ($getStart !== "") $start = $getStart;
		                    elseif (!(isset($start))) $start = 0;
		                if ($getRow !== "") $row = $getRow;
		                    else $row = $config["maxBookRow"];
		                $nav = "<div class='result-nav'>" .
		                       "<span class='result-nav__meta'>" . $txt["nbResultDisplayed"] . " " . $start . " - " . $displayEnd . "</span>" .
		                       "<span class='result-nav__links'>" . $navLinks . "</span>" .
		                       "<span class='result-nav__row'>| " . $txt["nbPerPage"] .
		                       " <input type='number' min='1' class='result-nav__input' name='row' value='" . $row . "' " .
		                       "onchange=\"window.location='?m=bo&a=vi&sa=re&research=ok&start=" . $start . "&row=' + this.value\" " .
		                       "onkeydown=\"if(event.key === 'Enter'){event.preventDefault();window.location='?m=bo&a=vi&sa=re&research=ok&start=" . $start . "&row=' + this.value;}\">" .
		                       "</span>" .
		                       "</div>";
                    // $config["maxBookRow"]
                  
                //$search = $babase->DbSelect($sqlSearch, $nbResult);
                // Display display/search header
                $template->set_filenames(array('bookDisplayHeader' => 'book_display_header.tpl')); 
                // Display search table header
                $template->set_filenames(array('bookDisplaySearchResultsHeader' => 'book_display_search_results_header.tpl'));
                $template->assign_vars(array('BOOK_DATABASE' => $txt["bookDatabase"], 'NUM_RESULTS_FOUND' => $txt["numResultsFound"], 'NB_RESULTS' => $nbResult));
	                $template->assign_vars(array('BOOKS_ID' => $txt["id"], 'BOOKS_AUTHOR' => $txt["author"], 'BOOKS_TITLE' => $txt["title"], 'BOOKS_DESCRIPTION' => $txt["description"], 'BOOKS_PRICE' => $txt["price"], 'BOOKS_ON_EBAY' => $txt["onEbay"], 'BOOKS_PRICE_AMAZON' => $txt["price_amazon"], 'BOOKS_PRICE_EBAY' => $txt["price_ebay"], 'BOOKS_LOCATION' => $txt["location"], 'BOOKS_QUANTITY' => $txt["quantity"],  'BOOKS_CATEGORY' => $txt["category"], 'BOOKS_MODIFY' => "<img class='icon-svg' src='".$templateBase."/images/icon-edit.svg' alt='" . $txt["modify"] . "'>", 'BOOKS_ERASE' => "<img class='icon-svg' src='".$templateBase."/images/icon-trash.svg' alt='" . $txt["erase"] . "'>", 'NAV' => $nav, 'BOOKS_MODIFY_SEARCH_SUBMIT' => (isset($txt["bookModifySearchSubmit"]) ? $txt["bookModifySearchSubmit"] : "Modifier la recherche"), 'BOOKS_MODIFY_SEARCH_FORM' => "index.php", 'BOOKS_EXPORT_EXCEL' => (isset($txt["bookExportExcel"]) ? $txt["bookExportExcel"] : "Export Excel"), 'BOOKS_RESULT_LOCATION_SAVE_URL' => "?m=bo&a=vi&sa=re&book_action=quick_save_location"));
                $template->pparse('bookDisplayHeader');
                $template->pparse('bookDisplaySearchResultsHeader'); 
                // Display search results body
                $template->set_filenames(array('bookDisplaySearchResultsBody' => 'book_display_search_results_body.tpl'));
                $i = 0;
                while ($i != (sizeof($search)-1))
		{
		    // Looking for photos to display
		    $photos = $book->getPhoto($search[$i]["idBook"]);
		    $template->_tpldata["bookPhoto."] = array();
		    if (sizeof($photos) > 0)
		    {
		        $template->assign_block_vars("bookPhoto", array(
				'FILEMINIATURE' => $photos[0]["fileThumbnail"],
				'DESCRIPTION' => $photos[0]["description"],
				));
		    } 
                    // trunk the description
                    $descriptionText = strip_tags(book_decode_entities_recursive($search[$i]["description"]));
                    $description = substr($descriptionText, 0, 30) . "...";
                    $locationRaw = trim(book_decode_entities_recursive(isset($search[$i]["location"]) ? $search[$i]["location"] : ""));
                    $locationLabel = ($locationRaw !== "") ? $locationRaw : "—";
                    $select="<input type='checkbox' name='selected_book[]'' value='".$search[$i]["idBook"]."' id='checkbox_book_".$search[$i]["idBook"]."' />";
                    $template->assign_vars(array('BOOKS_RESULT_MODIFY' => "?m=bo&a=vi&sa=mo&fromsearch=1&numres=" . ($i+$start), 'BOOKS_RESULT_ERASE' => "?m=bo&a=vi&sa=re&del=1&sub=ok&research=ok&id=" . $search[$i]["idBook"],
                                'BOOKS_RESULT_AUTHOR' => $search[$i]["author"], 'BOOKS_RESULT_TITLE' => $search[$i]["title"], 'BOOKS_RESULT_DESCRIPTION' => $description, 'BOOKS_RESULT_PRICE' => $search[$i]["price"], 'BOOKS_RESULT_QUANTITY' => $search[$i]["quantity"], 'BOOKS_CATEGORY' => $txt["category"], 'BOOKS_RESULT_SELECT' => $select,
                                'BOOKS_RESULT_ID' => (int)$search[$i]["idBook"], 'BOOKS_RESULT_LOCATION' => htmlspecialchars($locationRaw, ENT_QUOTES, "UTF-8"), 'BOOKS_RESULT_LOCATION_LABEL' => htmlspecialchars($locationLabel, ENT_QUOTES, "UTF-8"), 'BOOK_CONFIRM_ERASE' => htmlspecialchars((string)$txt["bookConfirmErase"], ENT_QUOTES, "UTF-8")));
                    $template->pparse('bookDisplaySearchResultsBody');
                    $i++;
                } 
                // Footer
                $template->set_filenames(array('bookDisplaySearchResultsFooter' => 'book_display_search_results_footer.tpl'));
                $template->assign_vars(array('SELECT_ALL' => $txt["selectAll"], 'UNSELECT_ALL' => $txt["unselectAll"], 'FOR_SELECTION' => $txt["forSelection"]));
                $template->pparse('bookDisplaySearchResultsFooter');
                break;
            case "mo": // Display Modify form
	                if ($postId != "")
	                    $id = $postId;
	                else
	                    $id = $getId;
	                $showReturnButton = false;
	                if (isset($_GET['fromcatalog']))
                {
		    // We want to display a book from a catalogData list
		    $id=$_GET['id'];
		    $fromresultsearch = "&fromcatalog=" . $_GET['fromcatalog'];
		    $returnToResult = "?m=ca&a=ed&sa=vi&idCatalog=" . $_GET['fromcatalog'];
		    $showReturnButton = true;
		    // TODO: Create an 'else' to display error
                }
	                $book->idBook=$id;
	                $msg = "";
	                if (!isset($fromresultsearch))
	                    $fromresultsearch = "";
	                if (!isset($returnToResult))
	                    $returnToResult = "?m=bo&a=vi&sa=re&research=ok";
	                if ((isset($_GET['fromsearch']) ? $_GET['fromsearch'] : "") == "1")
	                {
	                    $numresTmp = isset($_GET['numres']) ? (int)$_GET['numres'] : 0;
	                    $fromresultsearch = "&fromsearch=1&numres=" . $numresTmp;
	                    $showReturnButton = true;
	                }
	                $bookAutosaveEnabled = book_autosave_enabled();

	                $bookAction = isset($_REQUEST["book_action"]) ? $_REQUEST["book_action"] : "";
	                if ($bookAction != "")
	                {
	                    if (!$bookAutosaveEnabled)
	                        book_json_response(false, "server_error", "Auto-sauvegarde desactivee.", array(), array(), array("BOOK_AUTOSAVE_ENABLED=0"));

	                    $idBookAjax = (int)$id;
	                    if ($idBookAjax <= 0)
	                        book_json_response(false, "validation_error", "ID ouvrage manquant.", array(), array(), array("ID ouvrage manquant."));

	                    $book->idBook = $idBookAjax;
	                    $recordNow = $book->get($idBookAjax);
	                    if (!$recordNow)
	                        book_json_response(false, "validation_error", "Ouvrage introuvable.", array(), array(), array("Ouvrage introuvable."));

	                    if ($bookAction == "status")
	                    {
	                        $ui = book_ui_payload($book, $category, $catalog, $config, $txt, $fromresultsearch, true);
	                        book_json_response(true, "ok", "Etat charge.", book_build_payload($recordNow), $ui, array());
	                    }

	                    if ($bookAction == "save_fields")
	                    {
	                        $changes = isset($_POST["changes"]) && is_array($_POST["changes"]) ? $_POST["changes"] : array();
	                        $clientModifyDate = isset($_POST["clientModifyDate"]) ? (int)$_POST["clientModifyDate"] : (isset($_GET["clientModifyDate"]) ? (int)$_GET["clientModifyDate"] : 0);
	                        $serverModifyDate = (int)(isset($recordNow["modifyDate"]) ? $recordNow["modifyDate"] : 0);
	                        if ($clientModifyDate > 0 && $serverModifyDate > 0 && $clientModifyDate != $serverModifyDate)
	                        {
	                            $ui = book_ui_payload($book, $category, $catalog, $config, $txt, $fromresultsearch, true);
	                            book_json_response(false, "conflict", "Conflit detecte: la fiche a ete modifiee ailleurs.", book_build_payload($recordNow), $ui, array("Conflit de version."));
	                        }

	                        $errors = array();
	                        $normalizedChanges = book_normalize_changes($changes, $errors);
	                        if (sizeof($errors) > 0)
	                            book_json_response(false, "validation_error", "Validation des champs impossible.", book_build_payload($recordNow), array(), $errors);
	                        if (sizeof($normalizedChanges) == 0)
	                            book_json_response(false, "validation_error", "Aucun champ modifiable recu.", book_build_payload($recordNow), array(), array("Aucune modification a enregistrer."));

	                        if (!$book->updateFields($idBookAjax, $normalizedChanges, "autosave"))
	                            book_json_response(false, "server_error", "Sauvegarde impossible.", book_build_payload($recordNow), array(), array("Erreur SQL pendant la sauvegarde."));

	                        $recordNow = $book->get($idBookAjax);
	                        $ui = book_ui_payload($book, $category, $catalog, $config, $txt, $fromresultsearch, true);
	                        book_json_response(true, "ok", "Sauvegarde effectuee.", book_build_payload($recordNow), $ui, array());
	                    }

	                    if ($bookAction == "qty_delta")
	                    {
	                        $delta = isset($_POST["delta"]) ? (int)$_POST["delta"] : (isset($_GET["delta"]) ? (int)$_GET["delta"] : 0);
	                        if ($delta == 0)
	                            book_json_response(false, "validation_error", "Delta quantite manquant.", book_build_payload($recordNow), array(), array("delta requis."));
	                        if ($delta > 0)
	                            $ok = $book->addQty("autosave", abs($delta), $idBookAjax);
	                        else
	                            $ok = $book->delQty("autosave", abs($delta), $idBookAjax);
	                        if (!$ok)
	                            book_json_response(false, "server_error", "Mise a jour quantite impossible.", book_build_payload($recordNow), array(), array("Echec mise a jour quantite."));
	                        $recordNow = $book->get($idBookAjax);
	                        book_json_response(true, "ok", ($delta > 0 ? $txt["addQtyDone"] : $txt["delQtyDone"]), book_build_payload($recordNow), array(), array());
	                    }

	                    if ($bookAction == "category_add")
	                    {
	                        $idCategory = isset($_POST["categoryId"]) ? (int)$_POST["categoryId"] : (isset($_POST["category"]) ? (int)$_POST["category"] : (isset($_GET["categoryId"]) ? (int)$_GET["categoryId"] : 0));
	                        if ($idCategory <= 0)
	                            book_json_response(false, "validation_error", "Categorie manquante.", book_build_payload($recordNow), array(), array("idCategory requis."));
	                        if (!$book->addCategory($idCategory, $idBookAjax))
	                            book_json_response(false, "validation_error", "Ajout rubrique impossible.", book_build_payload($recordNow), array(), array($book->viewError($book->error)));
	                        $recordNow = $book->get($idBookAjax);
	                        $ui = book_ui_payload($book, $category, $catalog, $config, $txt, $fromresultsearch, true);
	                        book_json_response(true, "ok", $txt["addCategoryDone"], book_build_payload($recordNow), $ui, array());
	                    }

	                    if ($bookAction == "category_del")
	                    {
	                        $idCategory = isset($_POST["categoryId"]) ? (int)$_POST["categoryId"] : (isset($_GET["categoryId"]) ? (int)$_GET["categoryId"] : 0);
	                        if ($idCategory <= 0)
	                            book_json_response(false, "validation_error", "Categorie manquante.", book_build_payload($recordNow), array(), array("idCategory requis."));
	                        if (!$book->delCategory($idCategory, $idBookAjax))
	                            book_json_response(false, "validation_error", "Suppression rubrique impossible.", book_build_payload($recordNow), array(), array("Suppression impossible."));
	                        $recordNow = $book->get($idBookAjax);
	                        $ui = book_ui_payload($book, $category, $catalog, $config, $txt, $fromresultsearch, true);
	                        book_json_response(true, "ok", $txt["delCategoryDone"], book_build_payload($recordNow), $ui, array());
	                    }

	                    if ($bookAction == "catalog_add")
	                    {
	                        $addInCatalogValue = isset($_POST["addInCatalog"]) ? $_POST["addInCatalog"] : (isset($_GET["addInCatalog"]) ? $_GET["addInCatalog"] : "");
	                        if ($addInCatalogValue == "")
	                            book_json_response(false, "validation_error", "Valeur catalogue manquante.", book_build_payload($recordNow), array(), array("addInCatalog requis."));
	                        $parts = explode("-", $addInCatalogValue);
	                        if (sizeof($parts) < 2)
	                            book_json_response(false, "validation_error", "Format catalogue invalide.", book_build_payload($recordNow), array(), array("Format attendu: idCatalog-orderInCatalog."));
	                        $idCatalog = (int)$parts[0];
	                        $orderInCatalog = (int)$parts[1];
	                        if ($idCatalog <= 0)
	                            book_json_response(false, "validation_error", "idCatalog invalide.", book_build_payload($recordNow), array(), array("idCatalog invalide."));
	                        $catalog->idCatalog = $idCatalog;
	                        if (!$catalog->addBook($idBookAjax, $orderInCatalog + 1))
	                            book_json_response(false, "server_error", "Ajout au catalogue impossible.", book_build_payload($recordNow), array(), array("Echec ajout au catalogue."));
	                        $book->touchModifyDate($idBookAjax, "autosave:catalog_add");
	                        $recordNow = $book->get($idBookAjax);
	                        $ui = book_ui_payload($book, $category, $catalog, $config, $txt, $fromresultsearch, true);
	                        book_json_response(true, "ok", $txt["addInCatalogDone"], book_build_payload($recordNow), $ui, array());
	                    }

	                    if ($bookAction == "catalog_del")
	                    {
	                        $idCatalog = isset($_POST["catalogId"]) ? (int)$_POST["catalogId"] : (isset($_GET["catalogId"]) ? (int)$_GET["catalogId"] : 0);
	                        if ($idCatalog <= 0)
	                            book_json_response(false, "validation_error", "idCatalog manquant.", book_build_payload($recordNow), array(), array("idCatalog requis."));
	                        $catalog->idCatalog = $idCatalog;
	                        if (!$catalog->delIdBook($idBookAjax))
	                            book_json_response(false, "server_error", "Suppression du catalogue impossible.", book_build_payload($recordNow), array(), array("Echec suppression du catalogue."));
	                        $book->touchModifyDate($idBookAjax, "autosave:catalog_del");
	                        $recordNow = $book->get($idBookAjax);
	                        $ui = book_ui_payload($book, $category, $catalog, $config, $txt, $fromresultsearch, true);
	                        book_json_response(true, "ok", $txt["delInCatalogOk"], book_build_payload($recordNow), $ui, array());
	                    }

	                    if ($bookAction == "ebay_add")
	                    {
	                        try
	                        {
	                            if (!book_has_valid_ebay_price($recordNow))
	                            {
	                                $lastError = book_missing_ebay_price_message();
	                                book_json_response(false, "validation_error", $lastError, book_build_payload($recordNow), array(), array($lastError));
	                            }

	                            if ((string)(isset($recordNow["onEbay"]) ? $recordNow["onEbay"] : "0") !== "1")
	                                $book->updateFields($idBookAjax, array("onEbay" => "1"), "autosave:ebay_add_intent");

	                            $listingId = $internet->addOnEbay($idBookAjax);
	                            if (!$listingId)
	                            {
	                                $lastError = $internet->getEbayLastError($idBookAjax);
	                                if ($lastError == "")
	                                    $lastError = "Ajout eBay impossible.";
	                                $recordNow = $book->get($idBookAjax);
	                                book_json_response(false, "server_error", $lastError, book_build_payload($recordNow), array(), array($lastError));
	                            }

	                            $recordNow = $book->get($idBookAjax);
	                            book_json_response(true, "ok", "Ouvrage publié sur eBay.", book_build_payload($recordNow), array(), array());
	                        }
	                        catch (Throwable $e)
	                        {
	                            $recordNow = $book->get($idBookAjax);
	                            $lastError = "Erreur eBay (ajout): " . $e->getMessage();
	                            book_json_response(false, "server_error", $lastError, book_build_payload($recordNow), array(), array($lastError));
	                        }
	                    }

	                    if ($bookAction == "ebay_update")
	                    {
	                        try
	                        {
	                            if (!book_has_valid_ebay_price($recordNow))
	                            {
	                                $lastError = book_missing_ebay_price_message();
	                                book_json_response(false, "validation_error", $lastError, book_build_payload($recordNow), array(), array($lastError));
	                            }

	                            if ((string)(isset($recordNow["onEbay"]) ? $recordNow["onEbay"] : "0") !== "1")
	                                $book->updateFields($idBookAjax, array("onEbay" => "1"), "autosave:ebay_update_intent");

	                            $hasItemId = trim((string)(isset($recordNow["ebayItemId"]) ? $recordNow["ebayItemId"] : "")) !== "";
	                            if ($hasItemId)
	                                $result = $internet->updateEbayItemId($idBookAjax);
	                            else
	                                $result = $internet->addOnEbay($idBookAjax);

	                            if (!$result)
	                            {
	                                $lastError = $internet->getEbayLastError($idBookAjax);
	                                if ($lastError == "")
	                                    $lastError = "Mise à jour eBay impossible.";
	                                $recordNow = $book->get($idBookAjax);
	                                book_json_response(false, "server_error", $lastError, book_build_payload($recordNow), array(), array($lastError));
	                            }

	                            $recordNow = $book->get($idBookAjax);
	                            book_json_response(true, "ok", "Annonce eBay mise à jour.", book_build_payload($recordNow), array(), array());
	                        }
	                        catch (Throwable $e)
	                        {
	                            $recordNow = $book->get($idBookAjax);
	                            $lastError = "Erreur eBay (mise à jour): " . $e->getMessage();
	                            book_json_response(false, "server_error", $lastError, book_build_payload($recordNow), array(), array($lastError));
	                        }
	                    }

	                    if ($bookAction == "ebay_remove")
	                    {
	                        try
	                        {
	                            $currentItemId = trim((string)(isset($recordNow["ebayItemId"]) ? $recordNow["ebayItemId"] : ""));
	                            $removed = false;
	                            if ($currentItemId !== "")
	                                $removed = $internet->delOnEbay($idBookAjax);
	                            else
	                                $removed = true;

	                            if (!$removed)
	                            {
	                                $lastError = $internet->getEbayLastError($idBookAjax);
	                                $notFound = (stripos($lastError, "404") !== false || stripos($lastError, "not found") !== false || stripos($lastError, "introuvable") !== false);
	                                if ($notFound)
	                                {
	                                    $book->delEbayId($idBookAjax);
	                                    $removed = true;
	                                }
	                                else
	                                {
	                                    if ($lastError == "")
	                                        $lastError = "Suppression eBay impossible.";
	                                    $recordNow = $book->get($idBookAjax);
	                                    book_json_response(false, "server_error", $lastError, book_build_payload($recordNow), array(), array($lastError));
	                                }
	                            }

		                            if ($currentItemId === "")
		                                $book->delEbayId($idBookAjax);

		                            $recordNow = $book->get($idBookAjax);
		                            book_json_response(true, "ok", "Annonce eBay retirée.", book_build_payload($recordNow), array(), array());
	                        }
	                        catch (Throwable $e)
	                        {
	                            $recordNow = $book->get($idBookAjax);
	                            $lastError = "Erreur eBay (suppression): " . $e->getMessage();
	                            book_json_response(false, "server_error", $lastError, book_build_payload($recordNow), array(), array($lastError));
	                        }
	                    }

	                    book_json_response(false, "validation_error", "Action ouvrage inconnue.", book_build_payload($recordNow), array(), array("book_action inconnu."));
	                }

	                $photoAction = isset($_REQUEST["photo_action"]) ? $_REQUEST["photo_action"] : "";
	                if ($photoAction != "")
	                {
	                    $idBookAjax = (int)$id;
	                    if ($idBookAjax <= 0)
	                        book_photo_json_response(false, "ID ouvrage manquant.", array(), array("ID ouvrage manquant."), array());

	                    switch ($photoAction)
	                    {
	                        case "list":
	                            $photos = $book->getPhoto($idBookAjax);
	                            $recordNow = $book->get($idBookAjax);
	                            book_photo_json_response(true, "Galerie chargée.", $photos, array(), book_build_payload($recordNow));
	                            break;

	                        case "upload":
	                            $uploadFiles = array();
	                            if (isset($_FILES["photos"]))
	                                $uploadFiles = $_FILES["photos"];
	                            else if (isset($_FILES["photo"]))
	                                $uploadFiles = $_FILES["photo"];

	                            $uploadResult = $book->addPhotosBulk($idBookAjax, $uploadFiles);
	                            $photos = $book->getPhoto($idBookAjax);
	                            $ok = ((int)$uploadResult["added"] > 0) && (sizeof($uploadResult["errors"]) == 0);
	                            if ((int)$uploadResult["added"] > 0)
	                                $message = $uploadResult["added"] . " photo(s) importée(s).";
	                            else
	                                $message = "Aucune photo importée.";
	                            $recordNow = $book->get($idBookAjax);
	                            book_photo_json_response($ok, $message, $photos, $uploadResult["errors"], book_build_payload($recordNow));
	                            break;

	                        case "rotate_left":
	                        case "rotate_right":
	                            $photoId = isset($_REQUEST["photo_id"]) ? (int)$_REQUEST["photo_id"] : 0;
	                            $photoData = $book->getPhotoById($photoId);
	                            if ($photoData === false)
	                                book_photo_json_response(false, "Photo introuvable.", $book->getPhoto($idBookAjax), array("Photo introuvable."), book_build_payload($book->get($idBookAjax)));
	                            $direction = ($photoAction == "rotate_right") ? "right" : "left";
	                            if ($book->rotatePhotoByDirection($photoId, $direction))
	                                book_photo_json_response(true, $txt["rotatePhotoDone"], $book->getPhoto((int)$photoData["idBook"]), array(), book_build_payload($book->get((int)$photoData["idBook"])));
	                            book_photo_json_response(false, "Échec de la rotation.", $book->getPhoto((int)$photoData["idBook"]), array("Échec de la rotation."), book_build_payload($book->get((int)$photoData["idBook"])));
	                            break;

	                        case "delete":
	                            $photoId = isset($_REQUEST["photo_id"]) ? (int)$_REQUEST["photo_id"] : 0;
	                            $photoData = $book->getPhotoById($photoId);
	                            if ($photoData === false)
	                                book_photo_json_response(false, "Photo introuvable.", $book->getPhoto($idBookAjax), array("Photo introuvable."), book_build_payload($book->get($idBookAjax)));
	                            $bookIdFromPhoto = (int)$photoData["idBook"];
	                            if ($book->deletePhoto($photoId))
	                                book_photo_json_response(true, $txt["delPhotoDone"], $book->getPhoto($bookIdFromPhoto), array(), book_build_payload($book->get($bookIdFromPhoto)));
	                            book_photo_json_response(false, "Échec de la suppression.", $book->getPhoto($bookIdFromPhoto), array("Échec de la suppression."), book_build_payload($book->get($bookIdFromPhoto)));
	                            break;

	                        case "reorder":
	                            $orderedIds = array();
	                            if (isset($_POST["photo_ids"]) && is_array($_POST["photo_ids"]))
	                                $orderedIds = $_POST["photo_ids"];
	                            else if (isset($_POST["photo_ids"]) && is_string($_POST["photo_ids"]))
	                                $orderedIds = explode(",", $_POST["photo_ids"]);
	                            else if (isset($_GET["photo_ids"]) && is_string($_GET["photo_ids"]))
	                                $orderedIds = explode(",", $_GET["photo_ids"]);

	                            if ($book->reorderPhotos($idBookAjax, $orderedIds))
	                                book_photo_json_response(true, "Ordre des photos mis à jour.", $book->getPhoto($idBookAjax), array(), book_build_payload($book->get($idBookAjax)));
	                            book_photo_json_response(false, "Impossible de sauvegarder l'ordre des photos.", $book->getPhoto($idBookAjax), array("Impossible de sauvegarder l'ordre."), book_build_payload($book->get($idBookAjax)));
	                            break;

	                        default:
	                            book_photo_json_response(false, "Action photo inconnue.", array(), array("Action photo inconnue."), book_build_payload($book->get($idBookAjax)));
	                            break;
	                    }
	                }
	                if (isset($_GET["delfromcatalog"]))
	                {
		    $catalog->idCatalog=$_GET["delfromcatalog"];
		    if ($catalog->delIdBook($book->idBook))
			$msg.=$txt["delInCatalogOk"]."<br />";
		    else
		    	$msg.=$txt["delInCatalogNotOk"]."<br />";
		}
                if (isset($_GET["delcategory"]))
                {
		    if ($book->delCategory($_GET["delcategory"]))
			$msg.=$txt["delCategoryDone"]."<br />";
		}
                if (isset($_GET["qtyAdd"]))
                {
		    if ($book->addQty("manual",1,$book->idBook))
			$msg.=$txt["addQtyDone"]."<br />";
		}
                if (isset($_GET["qtyDel"]))
                {
		    if ($book->delQty("manual",1,$book->idBook))
			$msg.=$txt["delQtyDone"]."<br />";
		}
                if (isset($_GET["ebay_add"]))
                {
                    $recordBeforeEbay = $book->get($book->idBook);
                    if ($recordBeforeEbay)
                    {
                        if (!book_has_valid_ebay_price($recordBeforeEbay))
                        {
                            $msg .= book_missing_ebay_price_message() . "<br />";
                        }
                        else
                        {
                        if ((string)(isset($recordBeforeEbay["onEbay"]) ? $recordBeforeEbay["onEbay"] : "0") !== "1")
                            $book->updateFields($book->idBook, array("onEbay" => "1"), "manual:ebay_add_intent");

                        $listingId = $internet->addOnEbay($book->idBook);
                        if ($listingId)
                            $msg .= "Ouvrage publié sur eBay.<br />";
                        else
                        {
                            $lastError = $internet->getEbayLastError($book->idBook);
                            if ($lastError == "")
                                $lastError = "Ajout eBay impossible.";
                            $msg .= $lastError . "<br />";
                        }
                        }
                    }
                    else
                        $msg .= "Ouvrage introuvable.<br />";
                }
                if (isset($_GET["ebay_update"]))
                {
                    $recordBeforeEbay = $book->get($book->idBook);
                    if ($recordBeforeEbay)
                    {
                        if (!book_has_valid_ebay_price($recordBeforeEbay))
                        {
                            $msg .= book_missing_ebay_price_message() . "<br />";
                        }
                        else
                        {
                        if ((string)(isset($recordBeforeEbay["onEbay"]) ? $recordBeforeEbay["onEbay"] : "0") !== "1")
                            $book->updateFields($book->idBook, array("onEbay" => "1"), "manual:ebay_update_intent");

                        $hasItemId = trim((string)(isset($recordBeforeEbay["ebayItemId"]) ? $recordBeforeEbay["ebayItemId"] : "")) !== "";
                        if ($hasItemId)
                            $resultEbay = $internet->updateEbayItemId($book->idBook);
                        else
                            $resultEbay = $internet->addOnEbay($book->idBook);

                        if ($resultEbay)
                            $msg .= "Annonce eBay mise à jour.<br />";
                        else
                        {
                            $lastError = $internet->getEbayLastError($book->idBook);
                            if ($lastError == "")
                                $lastError = "Mise à jour eBay impossible.";
                            $msg .= $lastError . "<br />";
                        }
                        }
                    }
                    else
                        $msg .= "Ouvrage introuvable.<br />";
                }
                if (isset($_GET["ebay_remove"]))
                {
                    $recordBeforeEbay = $book->get($book->idBook);
                    if ($recordBeforeEbay)
                    {
                        $currentItemId = trim((string)(isset($recordBeforeEbay["ebayItemId"]) ? $recordBeforeEbay["ebayItemId"] : ""));
                        if ($currentItemId !== "")
                            $removedEbay = $internet->delOnEbay($book->idBook);
                        else
                            $removedEbay = true;

                        if (!$removedEbay)
                        {
                            $lastError = $internet->getEbayLastError($book->idBook);
                            $notFound = (stripos($lastError, "404") !== false || stripos($lastError, "not found") !== false || stripos($lastError, "introuvable") !== false);
                            if ($notFound)
                            {
                                $book->delEbayId($book->idBook);
                                $removedEbay = true;
                            }
                            else
                            {
                                if ($lastError == "")
                                    $lastError = "Suppression eBay impossible.";
                                $msg .= $lastError . "<br />";
                            }
                        }

                        if ($currentItemId === "")
                            $book->delEbayId($book->idBook);

                        if ($removedEbay)
                            $msg .= "Annonce eBay retirée.<br />";
                    }
                    else
                        $msg .= "Ouvrage introuvable.<br />";
                }
                if (isset($_GET["delphoto"]))
                {
		    if ($book->delPhoto($_GET["delphoto"]))
			$msg.=$txt["delPhotoDone"]."<br />";
		}
                if (isset($_GET["rotateleft"]))
                {
		    if ($book->rotatePhoto($_GET["rotateleft"],"left"))
			$msg.=$txt["rotatePhotoDone"]."<br />";
		}
                if (isset($_GET["rotateright"]))
                {
		    if ($book->rotatePhoto($_GET["rotateright"],"right"))
			$msg.=$txt["rotatePhotoDone"]."<br />";
		}
		$hasLegacySingleUpload = (isset($_FILES['photo']['tmp_name']) && !is_array($_FILES['photo']['tmp_name']) && $_FILES['photo']['tmp_name'] != "");
		$hasBulkUpload = false;
		if (isset($_FILES['photos']['tmp_name']))
		{
		    if (is_array($_FILES['photos']['tmp_name']))
		    {
			$kk = 0;
			while ($kk < sizeof($_FILES['photos']['tmp_name']))
			{
			    if ($_FILES['photos']['tmp_name'][$kk] != "")
			    {
				$hasBulkUpload = true;
				break;
			    }
			    $kk++;
			}
		    }
		    else if ($_FILES['photos']['tmp_name'] != "")
			$hasBulkUpload = true;
		}
		if ($hasBulkUpload)
		{
		    $uploadResult = $book->addPhotosBulk($book->idBook, $_FILES['photos']);
		    if (((int)$uploadResult["added"]) > 0)
			$msg .= $uploadResult["added"] . " photo(s) ajoutée(s).<br />";
		    if (is_array($uploadResult["errors"]) && sizeof($uploadResult["errors"]) > 0)
		    {
			$kk = 0;
			while ($kk < sizeof($uploadResult["errors"]))
			{
			    $msg .= $uploadResult["errors"][$kk] . "<br />";
			    $kk++;
			}
		    }
		}
		else if ($hasLegacySingleUpload)
		{
		    // Legacy fallback
		    if ($book->addPhoto($book->idBook))
			$msg.=$txt["addPhotoDone"]."<br />";
		}
                if (isset($_GET["fromisbn"]))
                {
		    if ($book->addQty("ISBN Import",1,$book->idBook))
			$msg.=$txt["addQtyDone"]."<br />";
		    $template->assign_vars(array('OPT1_V' => "fromisbnimport"));
		    $template->assign_block_vars('isbnImportReturn', array());
		}
			$isPhotoUploadOnly = (isset($_POST["photoUploadOnly"]) && $_POST["photoUploadOnly"] == "1");
	                if (($postSub == "ok") && !$isPhotoUploadOnly)
			{ // Update the current record
	                    $book->assignData($_POST);
	                    $book->update();
	              	    if ($postCategory!="")
			    {
				if ($book->addCategory($postCategory))
				    $msg.=$txt["addCategoryDone"]."<br />";
				else
				    $msg.=$book->viewError($book->error)."<br />";
			    }
			    if ($postAddInCatalog!="")
			    {
				$addInCatalog=explode('-', $postAddInCatalog);
	                	$catalog->idCatalog=$addInCatalog[0];
	                	$catalog->addBook($book->idBook,$addInCatalog[1]+1);
	                	$msg.=$txt["addInCatalogDone"]."<br />";
			    }
	                    $msg.=$txt["bookModificationDone"]."<br />";
	                }
			$template->assign_vars(array('BOOK_MSG' => $msg, 'CREATEFROMISBN' => $txt["returnIsbn"]));
			// To get the current result record
		                $nav = "";
		                $hasSearchContext = false;
		                if ((isset($_GET['fromsearch']) ? $_GET['fromsearch'] : "") == "1")
	                {
			    $numres = isset($_GET['numres']) ? (int)$_GET['numres'] : -1;
			    $hasSearchCache = (
				isset($_SESSION["searchcache"]) && trim((string)$_SESSION["searchcache"]) != "" &&
				isset($_SESSION["ordercache"]) && trim((string)$_SESSION["ordercache"]) != "" &&
				isset($_SESSION["limitcache"]) && trim((string)$_SESSION["limitcache"]) != ""
			    );
			    if ($numres >= 0 && $hasSearchCache)
			    {
				$data = $book->search("cache", "", $numres, 1);
				if (is_array($data) && isset($data[0]["idBook"]))
				{
				    $id = (int)$data[0]["idBook"];
				    $hasSearchContext = true;
				}
			    }
			    if ($hasSearchContext)
			    {
				$templateBase = "template/" . $config["template"];
				if ($numres > 0)
				    $navLeft = "<a class='record-nav__link' href='?m=bo&a=vi&sa=mo&fromsearch=1&numres=" . ($numres - 1) . "'><img class='icon-svg' src='" . $templateBase . "/images/icon-chevron-left.svg' alt='" . $txt["previous"] . "'><span>" . $txt["previous"] . "</span></a>";
				else
				    $navLeft = "<span class='record-nav__link record-nav__link--disabled'><img class='icon-svg' src='" . $templateBase . "/images/icon-chevron-left.svg' alt='" . $txt["previous"] . "'><span>" . $txt["previous"] . "</span></span>";
				$navRight = "<a class='record-nav__link' href='?m=bo&a=vi&sa=mo&fromsearch=1&numres=" . ($numres + 1) . "'><span>" . $txt["next"] . "</span><img class='icon-svg' src='" . $templateBase . "/images/icon-chevron-right.svg' alt='" . $txt["next"] . "'></a>";
				$nav = "<div class='record-nav'><span class='record-nav__links'>" . $navLeft . "<span class='record-nav__sep'>|</span>" . $navRight . "</span></div>";
				$fromresultsearch = "&fromsearch=1&numres=" . $numres;
				$returnToResult = "?m=bo&a=vi&sa=re&research=ok";
			    }
	                }
                // Get book data
                $book->idBook=$id;
                $record=$book->get($id);
                $sessionGroupId = isset($_SESSION["idGroup"]) ? (int)$_SESSION["idGroup"] : 0;
                $bookGroupId = (isset($record["idGroup"]) ? (int)$record["idGroup"] : 0);
                if ($bookGroupId <= 0)
                    $bookGroupId = $sessionGroupId;
	                $salesHistory = $order->getBookEbaySalesHistory($bookGroupId, (int)$record["idBook"], 100);
	                $template->_tpldata["showEbaySales."] = array();
	                $template->assign_block_vars("showEbaySales", array());
	                $ebaySalesEmpty = "Aucune vente eBay enregistrée pour cette fiche.";
	                if (is_array($salesHistory) && sizeof($salesHistory) > 0)
	                {
	                    $ebaySalesEmpty = "";
	                    $sh = 0;
                    while ($sh < sizeof($salesHistory))
                    {
                        $sale = $salesHistory[$sh];
                        $orderIdValue = isset($sale["ebayOrderId"]) ? (string)$sale["ebayOrderId"] : "";
                        $orderIdHtml = ($orderIdValue !== "")
                            ? ("<a target='_blank' href='https://www.ebay.fr/sh/ord/details?orderid=" . rawurlencode($orderIdValue) . "'>" . htmlspecialchars($orderIdValue, ENT_QUOTES, "UTF-8") . "</a>")
                            : "—";
                        $creationTs = isset($sale["creationDateTs"]) ? (int)$sale["creationDateTs"] : 0;
                        $dateLabel = ($creationTs > 0) ? date("d/m/Y H:i", $creationTs) : "—";
                        $qtyValue = isset($sale["quantity"]) ? (int)$sale["quantity"] : 0;
                        $priceValue = isset($sale["unitPrice"]) ? (string)$sale["unitPrice"] : "";
                        $currencyValue = isset($sale["currency"]) ? (string)$sale["currency"] : "";
                        $priceLabel = trim($priceValue . " " . $currencyValue);
                        if ($qtyValue > 1 && $priceLabel !== "")
                            $priceLabel .= " x " . $qtyValue;
	                        $template->assign_block_vars("showEbaySales.bookEbaySale", array(
	                            "ORDER_ID_HTML" => $orderIdHtml,
	                            "ORDER_DATE" => htmlspecialchars($dateLabel, ENT_QUOTES, "UTF-8"),
	                            "PRICE" => htmlspecialchars(($priceLabel !== "" ? $priceLabel : "—"), ENT_QUOTES, "UTF-8"),
	                            "BUYER" => htmlspecialchars((isset($sale["buyerUsername"]) ? (string)$sale["buyerUsername"] : "—"), ENT_QUOTES, "UTF-8"),
	                            "PAYMENT_STATUS" => htmlspecialchars((isset($sale["orderPaymentStatus"]) ? (string)$sale["orderPaymentStatus"] : "—"), ENT_QUOTES, "UTF-8"),
                            "FULFILLMENT_STATUS" => htmlspecialchars((isset($sale["orderFulfillmentStatus"]) ? (string)$sale["orderFulfillmentStatus"] : "—"), ENT_QUOTES, "UTF-8"),
                            "VALIDATED" => (((isset($sale["isValidated"]) ? (int)$sale["isValidated"] : 0) === 1) ? "Oui" : "Non"),
                            "EBAY_ITEM_ID" => htmlspecialchars((isset($sale["ebayItemId"]) && trim((string)$sale["ebayItemId"]) !== "" ? (string)$sale["ebayItemId"] : "—"), ENT_QUOTES, "UTF-8"),
                        ));
                        $sh++;
                    }
                }
                // Display display/search header
                $template->set_filenames(array('bookDisplayHeader' => 'book_display_header.tpl')); 
                // Display Modify txt header
                $template->set_filenames(array('bookModify' => 'book_modify.tpl'));
                $template->assign_vars(array('BOOKS_DATABASE' => $txt["bookDatabase"], 'BOOKS_MODIFY_TXT' => $txt["bookModification"])); 
                // Re-search cache form
                $template->assign_vars(array(
                    'BOOKS_SEARCH_CACHE_SUBMIT' => $txt["bookSearchCacheSubmit"],
                    'BOOKS_SEARCH_FORM' => $returnToResult,
                    'BOOKS_MODIFY_SEARCH_SUBMIT' => (isset($txt["bookModifySearchSubmit"]) ? $txt["bookModifySearchSubmit"] : "Modifier la recherche"),
	                    'BOOKS_MODIFY_SEARCH_FORM' => "index.php",
	                ));
                if ($showReturnButton)
                    $template->assign_block_vars('showReturnButton', array());
                
		// Fetching Photo data
                $photos=$book->getPhoto($id);
		// Assigning row data
                $i=0;
		$template->_tpldata["bookPhoto"] = array();
	                while($i!=sizeof($photos))
	                {
			    $photoOrderLabel = ($i == 0) ? "Principale" : (string)($i + 1);
			    $template->assign_block_vars('bookPhoto', array(
					'ID' => $photos[$i]["idBookPhoto"],
					'FILE' => $photos[$i]["file"],
					'FILEMINIATURE' => $photos[$i]["fileThumbnail"],
					'DESCRIPTION' => $photos[$i]["description"],
					'ORDER_LABEL' => $photoOrderLabel,
					'ROTATELEFTURL' => "?m=bo&a=vi&sa=mo&id=".$book->idBook."&rotateleft=".$photos[$i]["idBookPhoto"]."$fromresultsearch",
					'ROTATERIGHTURL' => "?m=bo&a=vi&sa=mo&id=".$book->idBook."&rotateright=".$photos[$i]["idBookPhoto"]."$fromresultsearch",
					'DELETEURL' => "?m=bo&a=vi&sa=mo&id=".$book->idBook."&delphoto=".$photos[$i]["idBookPhoto"]."$fromresultsearch"
					));
		    $i++;
		}

                $cat = book_render_category_html($book, $category, $config, $txt, $fromresultsearch, $bookAutosaveEnabled);
                $inCatalog = book_render_catalog_html($book, $catalog, $config, $txt, $fromresultsearch, $bookAutosaveEnabled);
		
		// OnInternet value
		if ($record["internet"]==1)
		    $onInternet = "checked='checked'";
		else
		    $onInternet = "";								
			// onEbay value
			if ($record["onEbay"]==1)
			    $onEbay = "checked='checked'";
			else
			    $onEbay= "";
			// Ebay actions (AJAX when autosave is enabled, legacy fallback via href)
			$hasEbayItemId = (trim((string)$record["ebayItemId"]) != "");
			$ebayAddHidden = $hasEbayItemId ? " hidden='hidden'" : "";
			$ebayDelHidden = $hasEbayItemId ? "" : " hidden='hidden'";
			$ebayUpdateHidden = $hasEbayItemId ? "" : " hidden='hidden'";
			$bookActionBaseUrl = "?m=bo&a=vi&sa=mo&id=" . (int)$record["idBook"] . $fromresultsearch;
			$ebayAdd = "<a class='action-chip action-chip--add'".$ebayAddHidden." data-book-action='ebay_add' data-no-page-loader='1' href='" . $bookActionBaseUrl . "&ebay_add=1' title='Ajouter cet ouvrage sur eBay'>" . $txt["add"] . " eBay</a>";
			$ebayDel = "<a class='action-chip action-chip--delete'".$ebayDelHidden." data-book-action='ebay_remove' data-no-page-loader='1' href='" . $bookActionBaseUrl . "&ebay_remove=1' title='Retirer cet ouvrage de eBay'>" . $txt["del"] . " eBay</a>";
			$ebayUpdate = "<a class='action-chip action-chip--update'".$ebayUpdateHidden." data-book-action='ebay_update' data-no-page-loader='1' href='" . $bookActionBaseUrl . "&ebay_update=1' title='Mettre à jour cet ouvrage sur eBay'>" . $txt["update"] . " eBay</a>";
			if ($hasEbayItemId)
			{
			    $ebayItemIdDisplay = $record["ebayItemId"];
			    $ebayItemLinkHref = "https://www.ebay.fr/itm/" . rawurlencode((string)$record["ebayItemId"]);
			    $ebayItemLinkTarget = "_blank";
			    $ebayItemLinkClass = "";
			}
			else
			{
			    $ebayItemIdDisplay = "—";
			    $ebayItemLinkHref = "#";
			    $ebayItemLinkTarget = "_self";
			    $ebayItemLinkClass = " book-ebay-item-link--empty";
			}
			$ebayLastSyncLabel = ((int)$record["ebayLastSync"] > 0) ? date("d/m/Y H:i", (int)$record["ebayLastSync"]) : "—";
			$ebaySyncNeeded = ($hasEbayItemId && ((int)$record["onEbay"] === 1) && ((int)$record["modifyDate"] > (int)$record["ebayLastSync"]));
			$ebaySyncNeededHidden = $ebaySyncNeeded ? "" : "hidden='hidden'";
			$ebayUseDescriptionTemplateChecked = ((string)(isset($record["ebayUseDescriptionTemplate"]) ? $record["ebayUseDescriptionTemplate"] : "0") === "1") ? "checked='checked'" : "";

			$paymentPolicies = book_fetch_ebay_policy_catalog_rows($bookGroupId, "payment");
			$fulfillmentPolicies = book_fetch_ebay_policy_catalog_rows($bookGroupId, "fulfillment");
			$returnPolicies = book_fetch_ebay_policy_catalog_rows($bookGroupId, "return");
			$policyModeValue = isset($record["ebayPolicyMode"]) ? $record["ebayPolicyMode"] : "default";
			$policyModeSelect = book_build_ebay_policy_mode_select($policyModeValue, ($bookAutosaveEnabled ? "ebayPolicyMode" : ""));
			$paymentOverrideSelect = book_build_ebay_policy_select(
			    "ebayPaymentPolicyIdOverride",
			    (isset($record["ebayPaymentPolicyIdOverride"]) ? $record["ebayPaymentPolicyIdOverride"] : ""),
			    $paymentPolicies,
			    ($bookAutosaveEnabled ? "ebayPaymentPolicyIdOverride" : "")
			);
			$fulfillmentOverrideSelect = book_build_ebay_policy_select(
			    "ebayFulfillmentPolicyIdOverride",
			    (isset($record["ebayFulfillmentPolicyIdOverride"]) ? $record["ebayFulfillmentPolicyIdOverride"] : ""),
			    $fulfillmentPolicies,
			    ($bookAutosaveEnabled ? "ebayFulfillmentPolicyIdOverride" : "")
			);
			$returnOverrideSelect = book_build_ebay_policy_select(
			    "ebayReturnPolicyIdOverride",
			    (isset($record["ebayReturnPolicyIdOverride"]) ? $record["ebayReturnPolicyIdOverride"] : ""),
			    $returnPolicies,
			    ($bookAutosaveEnabled ? "ebayReturnPolicyIdOverride" : "")
			);
		// Assign Data
		$template->set_filenames(array('bookCreateMask' => 'book_main_mask.tpl'));
                $template->assign_vars(array('BOOKS_ID' => $txt["id"], 'BOOKS_AUTHOR' => $txt["author"], 'BOOKS_TITLE' => $txt["title"], 'BOOKS_SUBTITLE' => (isset($txt["subtitle"]) ? $txt["subtitle"] : "Sous-titre"), 'BOOKS_ON_INTERNET' => $txt["onInternet"], 'BOOKS_LOCATION' => $txt["location"], 'BOOKS_DESCRIPTION' => $txt["description"], 'BOOKS_PRICE' => $txt["price"], 'BOOKS_PRICE_EBAY' => $txt["price_ebay"], 'BOOKS_PRICE_AMAZON' => $txt["price_amazon"], 'BOOKS_QUANTITY' => $txt["quantity"], 'BOOKS_NOTES' => $txt["notes"], 'BOOKS_PHOTOS' => $txt["photos"], 'BOOKS_CATEGORY' => $txt["category"], 'BOOKS_IN_CATALOG' => $txt["bookInCatalog"], 'BOOKS_MODIFYDATE' => $txt["bookModifyDate"], 'BOOKS_ISBN' => $txt["isbn"], 'BOOKS_ON_EBAY' => $txt["onEbay"], 'BOOKS_EDITOR' => $txt["editor"], 'BOOKS_LANGUAGE' => $txt["language"], 'BOOKS_COLLECTION' => $txt["collection"], 'BOOKS_PUBLISHDATE' => $txt["publishDate"], 'BOOKS_PUBLISHLOCATION' => $txt["publishLocation"], 'BOOKS_FORMAT' => $txt["format"], 'BOOKS_PAGENUMBER' => $txt["pageNumber"], 'BOOKS_WEIGHT_G' => "Poids (g)", 'BOOKS_BINDING' => $txt["binding"], 'BOOKS_EBAYCATEGORY' => $txt["ebayCategory"], 'BOOKS_EBAYCATEGORYSECONDARY' => $txt["ebayCategorySecondary"], 'BOOKS_EBAYITEMID' => $txt["ebayItemId"], 'BOOKS_EBAYLASTSYNC' => $txt["ebayLastSync"], 'BOOKS_EBAYSHIPPINGCOST' => $txt["ebayShippingCost"], 'BOOKS_EBAYSHIPPINGCOSTINTERNATIONAL' => $txt["ebayShippingCostInternational"], 'BOOKS_CONDITION' => $txt["condition"], 
                'BOOKS_FORM' => "?m=bo&a=vi&sa=mo&id=$id".$fromresultsearch, // FORM Target URL <----------------------
                'BOOKS_SUBMIT' => $txt["submit"], 'ID_V' => $record["idBook"], 'AUTHOR_V' => $record["author"], 'TITLE_V' => $record["title"], 'SUBTITLE_V' => (isset($record["subtitle"]) ? book_decode_entities_recursive($record["subtitle"]) : ""), 'DESCRIPTION_V' => book_decode_entities_recursive($record["description"]), 'PRICE_V' => $record["price"], 'PRICE_EBAY_V' => $record["price_ebay"], 'PRICE_AMAZON_V' => $record["price_amazon"], 'QUANTITY_V' => $record["quantity"], 'NOTES_V' => $record["notes"], 'INTERNET_V' => $onInternet, 'LOCATION_V' => $record["location"], 'MODIFYDATE_V' => date("d/m/Y H:i", $record["modifyDate"]), 'CATEGORY_V' => $cat, 'IN_CATALOG_V' => $inCatalog, 'ISBN_V' => $record["isbn"], 'ONEBAY_V' => $onEbay, 'EDITOR_V' => $record["editor"], 'COLLECTION_V' => $record["collection"], 'PUBLISHDATE_V' => $record["publishDate"], 'PUBLISHLOCATION_V' => $record["publishLocation"], 'PAGENUMBER_V' => $record["pageNumber"], 'WEIGHT_G_V' => (isset($record["weight_g"]) ? $record["weight_g"] : "0"), 'EBAYSHIPPINGCOST_V' => $record["ebayShippingCost"], 'EBAYSHIPPINGCOSTINTERNATIONAL_V' => $record["ebayShippingCostInternational"], 'EBAY_USE_DESCRIPTION_TEMPLATE_V' => $ebayUseDescriptionTemplateChecked, 'EBAYITEMID_V' => $ebayItemIdDisplay, 'EBAYITEMID_LINK_HREF' => $ebayItemLinkHref, 'EBAYITEMID_LINK_TARGET' => $ebayItemLinkTarget, 'EBAYITEMID_LINK_CLASS' => $ebayItemLinkClass, 'EBAYLASTSYNC_V' => $ebayLastSyncLabel, 'EBAYLASTSYNC_TS_V' => (int)$record["ebayLastSync"], 'EBAY_SYNC_NEEDED_HIDDEN' => $ebaySyncNeededHidden, 'LANGUAGE_V' => $book->getLanguageHtmlForm($record["language"], ($bookAutosaveEnabled ? "language" : "")), 'BINDING_V' => $book->getBindingHtmlForm($record["binding"], ($bookAutosaveEnabled ? "binding" : "")), 'FORMAT_V' => $book->getFormatHtmlForm($record["format"], ($bookAutosaveEnabled ? "format" : "")), 'EBAYCATEGORY_V' => $book->getEbayCategoryHtmlForm(1,$record["ebayCategoryId"], ($bookAutosaveEnabled ? "ebayCategoryId" : "")), 'EBAYCATEGORYSECONDARY_V' => $book->getEbayCategoryHtmlForm(2,$record["ebayCategoryIdSecondary"], ($bookAutosaveEnabled ? "ebayCategoryIdSecondary" : "")), 'EBAYADD_V' => $ebayAdd, 'EBAYDEL_V' => $ebayDel, 'EBAYUPDATE_V' => $ebayUpdate, 'CONDITION_V' => $book->getConditionHtmlForm($record["condition"], ($bookAutosaveEnabled ? "condition" : "")), 'NAV' => $nav,
                'EBAY_POLICY_MODE_V' => $policyModeSelect,
                'EBAY_POLICY_PAYMENT_OVERRIDE_V' => $paymentOverrideSelect,
                'EBAY_POLICY_FULFILLMENT_OVERRIDE_V' => $fulfillmentOverrideSelect,
                'EBAY_POLICY_RETURN_OVERRIDE_V' => $returnOverrideSelect,
                'PHOTO_MANAGER_ENABLED' => "1",
                'PHOTO_MANAGER_MODE' => "modify",
                'PHOTO_API_URL' => "?m=bo&a=vi&sa=mo&id=$id".$fromresultsearch,
                'PHOTO_MAX_FILES' => "20",
                'PHOTO_MAX_SIZE_MB' => "10",
		                'PHOTO_HELP_TEXT' => "Glissez-déposez vos photos ici ou sélectionnez jusqu'à 20 fichiers (10 Mo max par fichier).",
		                'BOOK_AUTOSAVE_ENABLED' => ($bookAutosaveEnabled ? "1" : "0"),
                        'BOOK_FORM_MODE' => "modify",
		                'BOOK_API_URL' => "?m=bo&a=vi&sa=mo&id=$id".$fromresultsearch,
                        'BOOK_ISBN_LOOKUP_URL' => "",
                        'BOOK_ISBN_AUTOFOCUS' => "",
                        'BOOK_AUTHOR_AUTOFOCUS' => "autofocus='autofocus'",
		                'BOOK_SEARCH_AUTOCOMPLETE_ENABLED' => "0",
		                'BOOK_SEARCH_SUGGEST_URL' => "",
		                'BOOK_SEARCH_SUGGEST_MIN_CHARS' => "4",
		                'CLIENT_MODIFY_DATE_V' => (int)$record["modifyDate"],
		                'BOOK_AUTOSAVE_STATUS' => ($bookAutosaveEnabled ? "Sauvegarde automatique active." : ""),
		                'BOOK_ID_READONLY' => "readonly='readonly'",
	                        'BOOK_ID_PLACEHOLDER' => "",
	                        'BOOK_ID_META' => "",
	                        'EBAY_SALES_EMPTY' => $ebaySalesEmpty));
	                $template->assign_vars(array('BOOK_DATABASE' => $txt["bookDatabase"]));
	                $template->pparse('bookDisplayHeader');
                $template->pparse('bookModify');
                $template->pparse('bookCreateMask');
                break;
            default: // Display default search screen
                // We reset sessions variable for start & row attributes
                $_SESSION["start"]="";
                $_SESSION["row"]="";
                // Display display/search header
                $template->set_filenames(array('bookDisplayHeader' => 'book_display_header.tpl')); 
                // Display search txt
                $template->set_filenames(array('bookSearchForm' => 'book_search_form.tpl'));
                $template->assign_vars(array('BOOK_DATABASE' => $txt["bookDatabase"], 'BOOKS_SEARCH_TXT' => $txt["bookSearchTxt"]));

                $template->set_filenames(array('bookCreateMask' => 'book_main_mask.tpl'));
                $catList=$category->getList();
                $cat="<SELECT NAME='category'><OPTION VALUE='' SELECTED></OPTION>";
                $i=0;
                while($i!=(sizeof($catList)))
                {
		    if (strlen($catList[$i]["name"])>30) $name = substr($catList[$i]["name"], 0, 30) . "...";
		    else $name=$catList[$i]["name"];
		    $cat.="<OPTION VALUE='".$catList[$i]["idCategory"]."'>".$name."</OPTION>";
		    $i++;
		}
	                $cat.="</SELECT>";
	                // We're creating the catalog list
			$catList=$catalog->getList();
			$inCatalog = "";
			$inCatalog.="<SELECT NAME='inCatalog'><OPTION VALUE='' SELECTED></OPTION>";
		$j=0;
		while($j!=(sizeof($catList)))
		{
		    $inCatalog .= "<OPTION VALUE='" . $catList[$j]["idCatalog"] . "'>" . $catList[$j]["name"] . "</OPTION>";
		     $j++;
                }
		$inCatalog .= "</SELECT>";
		// Display search page
                $template->_tpldata["bookEbaySale"] = array();
                $template->assign_vars(array('BOOKS_ID' => $txt["id"], 'BOOKS_AUTHOR' => $txt["author"], 'BOOKS_TITLE' => $txt["title"], 'BOOKS_SUBTITLE' => (isset($txt["subtitle"]) ? $txt["subtitle"] : "Sous-titre"), 'BOOKS_ON_INTERNET' => $txt["onInternet"], 'BOOKS_LOCATION' => $txt["location"], 'BOOKS_DESCRIPTION' => $txt["description"], 'BOOKS_PRICE' => $txt["price"], 'BOOKS_PRICE_AMAZON' => $txt["price_amazon"], 'BOOKS_PRICE_EBAY' => $txt["price_ebay"], 'BOOKS_QUANTITY' => $txt["quantity"], 'BOOKS_NOTES' => $txt["notes"], 'BOOKS_CATEGORY' => $txt["category"], 'CATEGORY_V' => $cat, 'BOOKS_ISBN' => $txt["isbn"], 'BOOKS_ON_EBAY' => $txt["onEbay"], 'BOOKS_EDITOR' => $txt["editor"], 'BOOKS_LANGUAGE' => $txt["language"], 'BOOKS_COLLECTION' => $txt["collection"], 'BOOKS_PUBLISHDATE' => $txt["publishDate"], 'BOOKS_PUBLISHLOCATION' => $txt["publishLocation"], 'BOOKS_FORMAT' => $txt["format"], 'BOOKS_PAGENUMBER' => $txt["pageNumber"], 'BOOKS_WEIGHT_G' => "Poids (g)", 'BOOKS_BINDING' => $txt["binding"], 'BOOKS_EBAYCATEGORY' => $txt["ebayCategory"], 'BOOKS_EBAYCATEGORYSECONDARY' => $txt["ebayCategorySecondary"], 'BOOKS_EBAYITEMID' => $txt["ebayItemId"], 'BOOKS_EBAYLASTSYNC' => $txt["ebayLastSync"], 'BOOKS_EBAYSHIPPINGCOST' => $txt["ebayShippingCost"], 'BOOKS_EBAYSHIPPINGCOSTINTERNATIONAL' => $txt["ebayShippingCostInternational"], 'BOOKS_CONDITION' => $txt["condition"], 'BOOKS_PHOTOS' => $txt["photos"],
			'LANGUAGE_V' => $book->getLanguageHtmlForm(),
			'BINDING_V' => $book->getBindingHtmlForm(),
			'FORMAT_V' => $book->getFormatHtmlForm(),
			'CONDITION_V' => $book->getConditionHtmlForm(),
			'EBAYCATEGORY_V' => $book->getEbayCategoryHtmlForm(1),
			'EBAYCATEGORYSECONDARY_V' => $book->getEbayCategoryHtmlForm(2),                       
			'EBAY_POLICY_MODE_V' => "",
			'EBAY_POLICY_PAYMENT_OVERRIDE_V' => "",
			'EBAY_POLICY_FULFILLMENT_OVERRIDE_V' => "",
			'EBAY_POLICY_RETURN_OVERRIDE_V' => "",
			'EBAY_USE_DESCRIPTION_TEMPLATE_V' => "",
			'INTERNET_SEARCH' => " (active <input type='checkbox' name='internetSearch' value='1'>)",
			'ONEBAY_V' => "checked='checked'",
			'BOOKS_IN_CATALOG' => $txt["catalogs"], 'IN_CATALOG_V' => $inCatalog,
	                        'PHOTO_MANAGER_ENABLED' => "0",
	                        'PHOTO_MANAGER_MODE' => "search",
	                        'PHOTO_API_URL' => "",
	                        'PHOTO_MAX_FILES' => "20",
	                        'PHOTO_MAX_SIZE_MB' => "10",
		                        'PHOTO_HELP_TEXT' => "L'import photo est disponible depuis la fiche ouvrage.",
		                        'BOOK_AUTOSAVE_ENABLED' => "0",
                                'BOOK_FORM_MODE' => "search",
		                        'BOOK_API_URL' => "",
                                'BOOK_ISBN_LOOKUP_URL' => "",
                                'BOOK_ISBN_AUTOFOCUS' => "",
                                'BOOK_AUTHOR_AUTOFOCUS' => "autofocus='autofocus'",
		                        'BOOK_SEARCH_AUTOCOMPLETE_ENABLED' => "1",
		                        'BOOK_SEARCH_SUGGEST_URL' => "?m=bo&a=vi&sa=re&book_action=suggest",
		                        'BOOK_SEARCH_SUGGEST_MIN_CHARS' => "4",
		                        'CLIENT_MODIFY_DATE_V' => "0",
		                        'BOOK_AUTOSAVE_STATUS' => "",
		                        'BOOK_ID_READONLY' => "",
	                                'BOOK_ID_PLACEHOLDER' => "",
	                                'BOOK_ID_META' => "",
	                                'SUBTITLE_V' => "",
			                        'EBAYITEMID_V' => "—",
				                        'EBAYITEMID_LINK_HREF' => "#",
				                        'EBAYITEMID_LINK_TARGET' => "_self",
				                        'EBAYITEMID_LINK_CLASS' => " book-ebay-item-link--empty",
				                        'EBAYLASTSYNC_V' => "—",
				                        'EBAYLASTSYNC_TS_V' => "0",
				                        'EBAY_SYNC_NEEDED_HIDDEN' => "hidden='hidden'",
				                        'EBAYADD_V' => "",
				                        'EBAYDEL_V' => "",
				                        'EBAYUPDATE_V' => "",
	                                'EBAY_SALES_EMPTY' => "",
			                        'QUANTITY_NOT_NULL' => $txt["notNull"]." <input type='checkbox' name='quantitynotnull' value='1'>",
			                        'BOOKS_FORM' => "?m=bo&a=vi&sa=re", // FORM Target URL <----------------------
			                        'BOOKS_SUBMIT' => $txt["search"]));
                $template->pparse('bookDisplayHeader');
                $template->pparse('bookSearchForm');
                $template->pparse('bookCreateMask');
                break;
        } 
        break;
    case "io": 
        // Display template
        $template->set_filenames(array('bookIsbnOnlineSearch' => 'book_isbn_search_online.tpl'));
        $template->assign_vars(array('BOOK_ISBN_ONLINE_SEARCH' => $txt["IsbnOnlineSearch"], 'ISBN' => $txt["isbn"], 'SEARCH_SUBMIT' => $txt["search"], 'ISBN_FORM' => "?m=bo&a=io", 'AUTHOR' => $txt["author"], 'TITLE' => $txt["title"], 'EDITOR' => $txt["editor"], 'PUBLISHDATE' => $txt["publishDate"], 'PAGENUMBER' => $txt["pageNumber"], 'DESCRIPTION' => $txt["description"], 'IMAGE' => $txt["photos"], 'INFOLINK' => $txt["infoLink"]));
	if (isset($_POST["isbn"]))
	{
	    // An ISBN has been submitted
	    $result = $book->IsbnSearch($_POST["isbn"]);
		    if ($result["found"])
			// Book found !
			$template->assign_block_vars('isbnResult', array('ISBN_V' => $result["isbn"], 'AUTHOR_V' => $result["author"], 'TITLE_V' => $result["title"], 'EDITOR_V' => $result["editor"], 'PUBLISHDATE_V' => $result["publishDate"], 'PAGENUMBER_V' => $result["pageNumber"], 'DESCRIPTION_V' => $result["description"], 'IMAGE_V' => $result["image"], 'INFOLINK_V' => $result["infoLink"]));
		    else
		    {
			$isbnMsg = $txt["isbnNotFound"];
			if ($isbnDebugEnabled)
			{
			    $method = isset($result["method"]) ? $result["method"] : "unknown";
			    $debugLines = (isset($result["debug"]) && is_array($result["debug"])) ? implode(" | ", $result["debug"]) : "Aucun détail";
			    $isbnMsg .= "<br/><small><strong>Debug ISBN</strong> - méthode: " . htmlspecialchars($method, ENT_QUOTES, 'UTF-8') . "<br/>" . htmlspecialchars($debugLines, ENT_QUOTES, 'UTF-8') . "</small>";
			}
			else
			    $isbnMsg .= "<br/><small>Pour le diagnostic, ajoutez <code>&debug_isbn=1</code> à l'URL.</small>";
			$template->assign_vars(array('ISBN_MSG' => $isbnMsg));
		    }
	}	
        $template->pparse('bookIsbnOnlineSearch');
	break;
    case "ci": 
        // Display template
        $template->set_filenames(array('bookCreateFromIsbn' => 'book_create_from_isbn.tpl'));
        $template->assign_vars(array('BOOK_CREATE_FROM_ISBN' => $txt["createFromIsbn"], 'ISBN' => $txt["isbn"], 'SEARCH_SUBMIT' => $txt["search"], 'ISBN_FORM' => "?m=bo&a=ci"));
	
	// If ISBN has been posted
		if ((isset($_POST["createfromisbn"]) ? $_POST["createfromisbn"] : "") == 1)
	{
	    if (isset($_POST["isbn"]))
	    {
		// An ISBN has been submitted
		$search = $book->getFromIsbn($_POST["isbn"]);
		if ($search)
		{
		    // Book is already existing in database
		    $template->assign_vars(array('ISBN_MSG' => '<script type="text/javascript">
<!--
window.location = "http://' . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . '?m=bo&a=vi&sa=mo&id=' . $search["idBook"] . '&fromisbn=' . $_POST["isbn"] . '"
//-->
</script>'));
		}
		else
		{
		    // Book isn't already in databse
		    $result = $book->IsbnSearch($_POST["isbn"]);
		    if ($result["found"])
			// Book found online ! Redirection on creating page
			$template->assign_vars(array('ISBN_MSG' => '<script type="text/javascript">
<!--
window.location = "http://' . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . '?m=bo&a=cr&fromisbn=' . $_POST["isbn"] . '"
//-->
</script>'));
			    else
			    {
				// Book not found
				$isbnMsg = $txt["isbnNotFound"];
				if ($isbnDebugEnabled)
				{
				    $method = isset($result["method"]) ? $result["method"] : "unknown";
				    $debugLines = (isset($result["debug"]) && is_array($result["debug"])) ? implode(" | ", $result["debug"]) : "Aucun détail";
				    $isbnMsg .= "<br/><small><strong>Debug ISBN</strong> - méthode: " . htmlspecialchars($method, ENT_QUOTES, 'UTF-8') . "<br/>" . htmlspecialchars($debugLines, ENT_QUOTES, 'UTF-8') . "</small>";
				}
				else
				    $isbnMsg .= "<br/><small>Pour le diagnostic, ajoutez <code>&debug_isbn=1</code> à l'URL.</small>";
				$template->assign_vars(array('ISBN_MSG' => $isbnMsg));
			    }

		}
	    }	
	}
	
        $template->pparse('bookCreateFromIsbn');
	break;
    default:
        $template->set_filenames(array('body' => 'book_home.tpl'));
        $stats=$book->stats();
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

?>
