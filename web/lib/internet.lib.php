<?php
/**
 * * * *      GestLibr: internet.lib.php      	*
 * * * *					Internet Lib               		*
 * * * *  Romain DUCHENE, Mar 2006 - ?				 	*
 */

include "class/book.class.php";
$book = new Book();
include "class/category.class.php";
$category=New Category();
include "class/catalog.class.php";
$catalog=New Catalog();
include "class/internet.class.php";
$internet=New Internet();

require_once "class/ebay.class.php";

function internet_ebay_policy_select_html($name, $rows, $selectedValue)
{
    $name = (string) $name;
    $selectedValue = trim((string) $selectedValue);
    if (!is_array($rows))
        $rows = array();

    $html = "<select name='" . htmlspecialchars($name, ENT_QUOTES, "UTF-8") . "'>";
    $html .= "<option value=''>-- non défini --</option>";
    $i = 0;
    while ($i < sizeof($rows))
    {
        $row = $rows[$i];
        $policyId = isset($row["policyId"]) ? trim((string)$row["policyId"]) : "";
        $policyName = isset($row["name"]) ? trim((string)$row["name"]) : "";
        if ($policyId !== "")
        {
            $label = ($policyName !== "") ? ($policyName . " (" . $policyId . ")") : $policyId;
            $selected = ($policyId === $selectedValue) ? " selected='selected'" : "";
            $html .= "<option value='" . htmlspecialchars($policyId, ENT_QUOTES, "UTF-8") . "'" . $selected . ">" . htmlspecialchars($label, ENT_QUOTES, "UTF-8") . "</option>";
        }
        $i++;
    }
    $html .= "</select>";
    return $html;
}

function internet_ebay_sync_runs_html($runs, $selectedRunId = 0, $targetGroupId = 0)
{
    $selectedRunId = (int)$selectedRunId;
    $targetGroupId = (int)$targetGroupId;
    if (!is_array($runs))
        $runs = array();
    if (sizeof($runs) == 0)
        return "<p>Aucun historique de synchronisation.</p>";

    $html = "<table class='bordered'><tr><th>Run</th><th>Date</th><th>Mode</th><th>Statut</th><th>Ventes</th><th>Ajout</th><th>Mise à jour</th><th>Suppression</th><th>Erreurs</th><th>Message</th><th>Action</th></tr>";
    $i = 0;
    while ($i < sizeof($runs))
    {
        $row = $runs[$i];
        $runId = isset($row["id"]) ? (int)$row["id"] : 0;
        $dateText = "";
        if (isset($row["startedAtTs"]) && (int)$row["startedAtTs"] > 0)
            $dateText = date("d/m/Y H:i:s", (int)$row["startedAtTs"]);
        $status = isset($row["status"]) ? $row["status"] : "";
        $isDryRun = (isset($row["isDryRun"]) && (int)$row["isDryRun"] === 1);
        $mode = $isDryRun ? "Simulation" : "Live";
        $nbAdded = isset($row["nbAdded"]) ? (int)$row["nbAdded"] : 0;
        $nbUpdated = isset($row["nbUpdated"]) ? (int)$row["nbUpdated"] : 0;
        $nbDeleted = isset($row["nbDeleted"]) ? (int)$row["nbDeleted"] : 0;
        $nbErrors = isset($row["nbErrors"]) ? (int)$row["nbErrors"] : 0;
        $message = isset($row["message"]) ? $row["message"] : "";
        $salesLabel = "-";
        if (isset($row["detailsJson"]) && trim((string)$row["detailsJson"]) != "")
        {
            $decoded = json_decode((string)$row["detailsJson"], true);
            if (is_array($decoded) && isset($decoded["stats"]) && is_array($decoded["stats"]))
            {
                $stockLines = isset($decoded["stats"]["stockAdjustedLines"]) ? (int)$decoded["stats"]["stockAdjustedLines"] : 0;
                $stockUnits = isset($decoded["stats"]["stockAdjustedUnits"]) ? (int)$decoded["stats"]["stockAdjustedUnits"] : 0;
                $salesLabel = $stockLines . " (" . $stockUnits . " u.)";
            }
        }
        $openLabel = ($runId === $selectedRunId) ? "Ouvert" : "Voir";
        $actionHtml = "<a class='submit submit--secondary' href='?m=in&a=eb&ebay_sync_run_id=" . $runId . "&" . internet_ebay_target_query($targetGroupId) . "'>"
            . htmlspecialchars($openLabel, ENT_QUOTES, "UTF-8") . "</a>";
        $actionHtml .= " <a class='submit submit--secondary' href='?m=in&a=eb&ebay_sync_delete_run_id=" . $runId . "&" . internet_ebay_target_query($targetGroupId)
            . "' onclick=\"return confirm('Supprimer ce run de synchronisation eBay et son détail ?');\">Effacer</a>";
        $html .= "<tr>"
              . "<td>" . $runId . "</td>"
              . "<td>" . htmlspecialchars($dateText, ENT_QUOTES, "UTF-8") . "</td>"
              . "<td>" . htmlspecialchars($mode, ENT_QUOTES, "UTF-8") . "</td>"
              . "<td>" . htmlspecialchars($status, ENT_QUOTES, "UTF-8") . "</td>"
              . "<td>" . htmlspecialchars($salesLabel, ENT_QUOTES, "UTF-8") . "</td>"
              . "<td>" . $nbAdded . "</td>"
              . "<td>" . $nbUpdated . "</td>"
              . "<td>" . $nbDeleted . "</td>"
              . "<td>" . $nbErrors . "</td>"
              . "<td>" . htmlspecialchars($message, ENT_QUOTES, "UTF-8") . "</td>"
              . "<td>" . $actionHtml . "</td>"
              . "</tr>";
        $i++;
    }
    $html .= "</table>";
    return $html;
}

function internet_ebay_sync_actions_html($rows, $ebayEnv = "production")
{
    $ebayEnv = strtolower(trim((string)$ebayEnv));
    $itemBaseUrl = "https://www.ebay.fr/itm/";
    $orderBaseUrl = "https://www.ebay.fr/sh/ord/details?orderid=";
    if ($ebayEnv === "sandbox")
    {
        $itemBaseUrl = "https://www.sandbox.ebay.com/itm/";
        $orderBaseUrl = "https://www.sandbox.ebay.com/sh/ord/details?orderid=";
    }

    $renderItemLink = function($value) use ($itemBaseUrl) {
        $value = trim((string)$value);
        if ($value === "")
            return "";
        if (!preg_match('/^\d+$/', $value))
            return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
        return "<a href='" . htmlspecialchars($itemBaseUrl . $value, ENT_QUOTES, "UTF-8") . "' target='_blank' rel='noopener noreferrer'>" . htmlspecialchars($value, ENT_QUOTES, "UTF-8") . "</a>";
    };

    $renderOrderLink = function($value) use ($orderBaseUrl) {
        $value = trim((string)$value);
        if ($value === "")
            return "";
        return "<a href='" . htmlspecialchars($orderBaseUrl . rawurlencode($value), ENT_QUOTES, "UTF-8") . "' target='_blank' rel='noopener noreferrer'>" . htmlspecialchars($value, ENT_QUOTES, "UTF-8") . "</a>";
    };

    if (!is_array($rows))
        $rows = array();
    if (sizeof($rows) == 0)
        return "<p>Aucune action détaillée pour ce run.</p>";

    $html = "<table class='bordered'><tr><th>Date</th><th>Type</th><th>idBook</th><th>Commande</th><th>Ligne</th><th>Qty</th><th>ItemID</th><th>Statut</th><th>Message</th></tr>";
    $i = 0;
    while ($i < sizeof($rows))
    {
        $row = $rows[$i];
        $createdAtTs = isset($row["createdAtTs"]) ? (int)$row["createdAtTs"] : 0;
        $dateText = ($createdAtTs > 0 ? date("d/m/Y H:i:s", $createdAtTs) : "");
        $qtyBefore = isset($row["qtyBefore"]) ? (int)$row["qtyBefore"] : 0;
        $qtyDelta = isset($row["qtyDelta"]) ? (int)$row["qtyDelta"] : 0;
        $qtyAfter = isset($row["qtyAfter"]) ? (int)$row["qtyAfter"] : 0;
        $qtyLabel = $qtyBefore . " / -" . $qtyDelta . " / " . $qtyAfter;
        $itemBefore = isset($row["ebayItemIdBefore"]) ? trim((string)$row["ebayItemIdBefore"]) : "";
        $itemAfter = isset($row["ebayItemIdAfter"]) ? trim((string)$row["ebayItemIdAfter"]) : "";
        $itemLabel = "-";
        if ($itemAfter != "" && $itemAfter != $itemBefore)
        {
            $beforeHtml = $renderItemLink($itemBefore);
            $afterHtml = $renderItemLink($itemAfter);
            if ($beforeHtml === "")
                $itemLabel = $afterHtml;
            else
                $itemLabel = $beforeHtml . " -> " . $afterHtml;
        }
        else if ($itemBefore != "")
            $itemLabel = $renderItemLink($itemBefore);
        else if ($itemAfter != "")
            $itemLabel = $renderItemLink($itemAfter);

        $idBook = (int)(isset($row["idBook"]) ? $row["idBook"] : 0);
        $idBookHtml = "-";
        if ($idBook > 0)
            $idBookHtml = "<a href='?m=bo&a=vi&sa=mo&id=" . $idBook . "' target='_blank' rel='noopener noreferrer'>" . $idBook . "</a>";

        $orderIdValue = isset($row["ebayOrderId"]) ? trim((string)$row["ebayOrderId"]) : "";
        $orderIdHtml = ($orderIdValue !== "") ? $renderOrderLink($orderIdValue) : "";

        $html .= "<tr>"
            . "<td>" . htmlspecialchars($dateText, ENT_QUOTES, "UTF-8") . "</td>"
            . "<td>" . htmlspecialchars(isset($row["actionType"]) ? (string)$row["actionType"] : "", ENT_QUOTES, "UTF-8") . "</td>"
            . "<td>" . $idBookHtml . "</td>"
            . "<td>" . $orderIdHtml . "</td>"
            . "<td>" . htmlspecialchars(isset($row["lineItemId"]) ? (string)$row["lineItemId"] : "", ENT_QUOTES, "UTF-8") . "</td>"
            . "<td>" . htmlspecialchars($qtyLabel, ENT_QUOTES, "UTF-8") . "</td>"
            . "<td>" . $itemLabel . "</td>"
            . "<td>" . htmlspecialchars(isset($row["status"]) ? (string)$row["status"] : "", ENT_QUOTES, "UTF-8") . "</td>"
            . "<td>" . htmlspecialchars(isset($row["message"]) ? (string)$row["message"] : "", ENT_QUOTES, "UTF-8") . "</td>"
            . "</tr>";
        $i++;
    }
    $html .= "</table>";
    return $html;
}

function internet_ebay_location_select_html($name, $rows, $selectedValue)
{
    $name = (string) $name;
    $selectedValue = trim((string) $selectedValue);
    if (!is_array($rows))
        $rows = array();

    $html = "<select name='" . htmlspecialchars($name, ENT_QUOTES, "UTF-8") . "'>";
    $html .= "<option value=''>-- non défini --</option>";
    $i = 0;
    while ($i < sizeof($rows))
    {
        $row = $rows[$i];
        $locationKey = isset($row["merchantLocationKey"]) ? trim((string)$row["merchantLocationKey"]) : "";
        if ($locationKey !== "")
        {
            $nameValue = isset($row["name"]) ? trim((string)$row["name"]) : "";
            $label = ($nameValue !== "") ? ($nameValue . " (" . $locationKey . ")") : $locationKey;
            $selected = ($selectedValue === $locationKey) ? " selected='selected'" : "";
            $html .= "<option value='" . htmlspecialchars($locationKey, ENT_QUOTES, "UTF-8") . "'" . $selected . ">" . htmlspecialchars($label, ENT_QUOTES, "UTF-8") . "</option>";
        }
        $i++;
    }
    $html .= "</select>";
    return $html;
}

function internet_ebay_resolve_target_group_id()
{
    global $babase;

    $sessionGroupId = isset($_SESSION["idGroup"]) ? (int) $_SESSION["idGroup"] : 0;
    $isAdmin = (isset($_SESSION["admin"]) && (int) $_SESSION["admin"] === 1);
    $requested = 0;

    if (isset($_POST["target_group_id"])) {
        $requested = (int) $_POST["target_group_id"];
    } elseif (isset($_GET["target_group_id"])) {
        $requested = (int) $_GET["target_group_id"];
    }

    $targetGroupId = $sessionGroupId;
    if ($isAdmin && $requested > 0) {
        $nb = 0;
        $rows = $babase->DbSelect(
            "SELECT `idGroup` FROM `groups` WHERE `idGroup`='" . $requested . "' LIMIT 1",
            $nb
        );
        if ($nb > 0 && !empty($rows[0]["idGroup"])) {
            $targetGroupId = (int) $rows[0]["idGroup"];
        }
    }

    if ($targetGroupId <= 0) {
        $targetGroupId = $sessionGroupId;
    }

    return array(
        "targetGroupId" => (int) $targetGroupId,
        "sessionGroupId" => (int) $sessionGroupId,
        "isAdmin" => $isAdmin,
    );
}

function internet_ebay_group_selector_html($targetGroupId, $isAdmin)
{
    global $babase;

    $targetGroupId = (int) $targetGroupId;
    if (!$isAdmin) {
        return "<div class='ebay-group-target'><strong>Groupe actif:</strong> #" . $targetGroupId . "</div>";
    }

    $nb = 0;
    $rows = $babase->DbSelect(
        "SELECT `idGroup`, `groupName` FROM `groups` ORDER BY `groupName` ASC, `idGroup` ASC",
        $nb
    );

    $html = "<form action='?m=in&a=eb' method='get' class='ebay-group-target-form'>"
          . "<input type='hidden' name='m' value='in'>"
          . "<input type='hidden' name='a' value='eb'>"
          . "<label for='target_group_id'><strong>Groupe cible:</strong></label> "
          . "<select name='target_group_id' id='target_group_id' onchange='this.form.submit()'>";
    $i = 0;
    while ($i < $nb) {
        $gid = isset($rows[$i]["idGroup"]) ? (int) $rows[$i]["idGroup"] : 0;
        $gname = isset($rows[$i]["groupName"]) ? (string) $rows[$i]["groupName"] : "";
        if ($gid > 0) {
            $selected = ($gid === $targetGroupId) ? " selected='selected'" : "";
            $label = "#" . $gid;
            if ($gname !== "") {
                $label .= " - " . $gname;
            }
            $html .= "<option value='" . $gid . "'" . $selected . ">" . htmlspecialchars($label, ENT_QUOTES, "UTF-8") . "</option>";
        }
        $i++;
    }
    $html .= "</select></form>";
    return $html;
}

function internet_ebay_target_hidden_input($targetGroupId)
{
    $targetGroupId = (int) $targetGroupId;
    return "<input type='hidden' name='target_group_id' value='" . $targetGroupId . "'>";
}

function internet_ebay_target_query($targetGroupId)
{
    $targetGroupId = (int) $targetGroupId;
    return "target_group_id=" . rawurlencode((string) $targetGroupId);
}

function internet_ebay_reconcile_resume_from_row($row)
{
    $resume = array(
        "canContinue" => false,
        "nextPage" => 1,
        "source" => "",
        "status" => "",
        "localStartAfterIdBook" => 0,
        "remoteOfferStartOffset" => 0,
    );
    if (!is_array($row))
        return $resume;

    $resume["status"] = isset($row["status"]) ? (string)$row["status"] : "";
    $statsJson = isset($row["statsJson"]) ? trim((string)$row["statsJson"]) : "";
    if ($statsJson != "")
    {
        $stats = json_decode($statsJson, true);
        if (is_array($stats))
        {
            $resumeData = isset($stats["resume"]) && is_array($stats["resume"]) ? $stats["resume"] : array();
            $source = isset($resumeData["source"]) ? trim((string)$resumeData["source"]) : "";
            $nextPage = isset($resumeData["nextPage"]) ? (int)$resumeData["nextPage"] : 0;
            $complete = isset($resumeData["complete"]) ? ((int)$resumeData["complete"] === 1) : true;
            $localStartAfterIdBook = isset($resumeData["localStartAfterIdBook"]) ? (int)$resumeData["localStartAfterIdBook"] : 0;
            $remoteOfferStartOffset = isset($resumeData["remoteOfferStartOffset"]) ? (int)$resumeData["remoteOfferStartOffset"] : 0;

            if ($localStartAfterIdBook > 0)
                $resume["localStartAfterIdBook"] = $localStartAfterIdBook;
            if ($remoteOfferStartOffset > 0)
                $resume["remoteOfferStartOffset"] = $remoteOfferStartOffset;

            if ($source === "trading" && $nextPage > 1 && $complete === false)
            {
                $resume["canContinue"] = true;
                $resume["nextPage"] = $nextPage;
                $resume["source"] = $source;
            }
        }
    }

    // Generic continuation for any partial run, including local comparison cursor.
    if ($resume["canContinue"] === false && ($resume["status"] === "partial" || $resume["status"] === "partial_with_errors"))
    {
        $resume["canContinue"] = true;
        if ($source !== "")
            $resume["source"] = $source;
    }
    return $resume;
}

function internet_ebay_book_exists_in_group($idBook, $idGroup)
{
    global $babase;
    $idBook = (int) $idBook;
    $idGroup = (int) $idGroup;
    if ($idBook <= 0 || $idGroup <= 0)
        return false;
    $babase->DbSelect(
        "SELECT `idBook` FROM `book` WHERE `idGroup`='" . $idGroup . "' AND `idBook`='" . $idBook . "' LIMIT 1",
        $nb
    );
    return ($nb == 1);
}

function internet_ebay_reconcile_runs_html($runs, $selectedRunId, $targetGroupId = 0)
{
    $selectedRunId = (int)$selectedRunId;
    $targetGroupId = (int)$targetGroupId;
    if (!is_array($runs))
        $runs = array();
    if (sizeof($runs) == 0)
        return "<p>Aucun audit de cohérence exécuté.</p>";

    $html = "<table class='bordered'><tr><th>Run</th><th>Date</th><th>Statut</th><th>Items</th><th>Pending</th><th>Applied</th><th>Ignored</th><th>Errors</th><th>Message</th><th>Action</th></tr>";
    $i = 0;
    while ($i < sizeof($runs))
    {
        $row = $runs[$i];
        $runId = isset($row["id"]) ? (int)$row["id"] : 0;
        $dateText = "";
        if (isset($row["startedAtTs"]) && (int)$row["startedAtTs"] > 0)
            $dateText = date("d/m/Y H:i:s", (int)$row["startedAtTs"]);
        $status = isset($row["status"]) ? (string)$row["status"] : "";
        $nbItems = isset($row["nbItems"]) ? (int)$row["nbItems"] : 0;
        $nbPending = isset($row["nbPending"]) ? (int)$row["nbPending"] : 0;
        $nbApplied = isset($row["nbApplied"]) ? (int)$row["nbApplied"] : 0;
        $nbIgnored = isset($row["nbIgnored"]) ? (int)$row["nbIgnored"] : 0;
        $nbErrors = isset($row["nbErrors"]) ? (int)$row["nbErrors"] : 0;
        $message = isset($row["message"]) ? (string)$row["message"] : "";
        $openLabel = ($runId === $selectedRunId) ? "Ouvert" : "Voir";

        $actionsHtml = "<a class='submit submit--secondary' href='?m=in&a=eb&ebay_reconcile_run_id=" . $runId . "&" . internet_ebay_target_query($targetGroupId) . "'>" . htmlspecialchars($openLabel, ENT_QUOTES, "UTF-8") . "</a>";
        $resumeInfo = internet_ebay_reconcile_resume_from_row($row);
        if (isset($resumeInfo["canContinue"]) && $resumeInfo["canContinue"] === true)
        {
            $continueLabel = "Continuer";
            if (isset($resumeInfo["nextPage"]) && (int)$resumeInfo["nextPage"] > 1)
                $continueLabel .= " p." . (int)$resumeInfo["nextPage"];
            $actionsHtml .= " <a class='submit submit--secondary' href='?m=in&a=eb&ebay_reconcile_scan=continue&ebay_reconcile_continue_run_id=" . $runId . "&" . internet_ebay_target_query($targetGroupId) . "'>" . htmlspecialchars($continueLabel, ENT_QUOTES, "UTF-8") . "</a>";
        }
        $actionsHtml .= " <a class='submit submit--secondary' href='?m=in&a=eb&ebay_reconcile_delete_run_id=" . $runId . "&" . internet_ebay_target_query($targetGroupId) . "' onclick=\"return confirm('Supprimer cet audit de cohérence eBay ?');\">Effacer</a>";

        $html .= "<tr>"
              . "<td>" . $runId . "</td>"
              . "<td>" . htmlspecialchars($dateText, ENT_QUOTES, "UTF-8") . "</td>"
              . "<td>" . htmlspecialchars($status, ENT_QUOTES, "UTF-8") . "</td>"
              . "<td>" . $nbItems . "</td>"
              . "<td>" . $nbPending . "</td>"
              . "<td>" . $nbApplied . "</td>"
              . "<td>" . $nbIgnored . "</td>"
              . "<td>" . $nbErrors . "</td>"
              . "<td>" . htmlspecialchars($message, ENT_QUOTES, "UTF-8") . "</td>"
              . "<td>" . $actionsHtml . "</td>"
              . "</tr>";
        $i++;
    }
    $html .= "</table>";
    return $html;
}

function internet_ebay_reconcile_items_html($runId, $items)
{
    $runId = (int)$runId;
    if ($runId <= 0)
        return "<p>Aucun run d'audit sélectionné.</p>";
    if (!is_array($items))
        $items = array();
    if (sizeof($items) == 0)
        return "<p>Aucun écart détecté pour ce run.</p>";

    $appliedCount = 0;
    $k = 0;
    while ($k < sizeof($items))
    {
        $statusCheck = isset($items[$k]["status"]) ? (string)$items[$k]["status"] : "pending";
        if ($statusCheck == "applied")
            $appliedCount++;
        $k++;
    }

    $html = "";
    if ($appliedCount > 0)
    {
        $html .= "<div class='reconcile-visibility-controls'>"
              . "<button type='button' class='submit submit--secondary' data-show-applied='0' data-applied-count='" . (int)$appliedCount . "' onclick=\"return reconcileToggleAppliedRows(this);\">Afficher les lignes corrigées (" . (int)$appliedCount . ")</button>"
              . "<span class='reconcile-visibility-hint'>Les lignes déjà corrigées (APPLIED) sont masquées par défaut.</span>"
              . "</div>";
    }

    $html .= "<table class='bordered'><tr><th>Sel.</th><th>Type</th><th>Action</th><th>idBook</th><th>Annonce eBay</th><th>Lien local</th><th>Titre / Auteur</th><th>Détails</th></tr>";
    $i = 0;
    while ($i < sizeof($items))
    {
        $row = $items[$i];
        $itemId = isset($row["id"]) ? (int)$row["id"] : 0;
        if ($itemId <= 0)
        {
            $i++;
            continue;
        }
        $status = isset($row["status"]) ? (string)$row["status"] : "pending";
        $actionSuggested = isset($row["actionSuggested"]) ? (string)$row["actionSuggested"] : "";
        $actionSelected = isset($row["actionSelected"]) ? trim((string)$row["actionSelected"]) : "";
        $actionValue = ($actionSelected != "") ? $actionSelected : $actionSuggested;
        if ($actionValue == "")
            $actionValue = "ignore";
        if ($actionValue == "link_orphan")
            $actionValue = "link_local";
        $idBook = isset($row["localBookId"]) ? (int)$row["localBookId"] : 0;
        $remoteSku = isset($row["remoteSku"]) ? (string)$row["remoteSku"] : "";
        $remoteListingId = isset($row["remoteListingId"]) ? (string)$row["remoteListingId"] : "";
        $title = isset($row["title"]) ? (string)$row["title"] : "";
        $author = isset($row["author"]) ? (string)$row["author"] : "";
        $discrepancyType = isset($row["discrepancyType"]) ? (string)$row["discrepancyType"] : "";
        $message = isset($row["message"]) ? (string)$row["message"] : "";
        $details = array();
        if (isset($row["detailsJson"]) && trim((string)$row["detailsJson"]) != "")
        {
            $decoded = json_decode((string)$row["detailsJson"], true);
            if (is_array($decoded))
                $details = $decoded;
        }
        if (
            $actionSelected == ""
            && isset($details["differences"])
            && is_array($details["differences"])
            && isset($details["differences"]["description"])
            && is_array($details["differences"]["description"])
        )
        {
            $actionValue = "update_local";
        }
        $suggestionsHtml = "";
        if (isset($details["suggestions"]) && is_array($details["suggestions"]) && sizeof($details["suggestions"]) > 0)
        {
            $j = 0;
            $suggestionsHtml .= "<div class='reconcile-suggestions'>";
            while ($j < sizeof($details["suggestions"]))
            {
                $s = $details["suggestions"][$j];
                $sidBook = isset($s["idBook"]) ? (int)$s["idBook"] : 0;
                if ($sidBook > 0)
                {
                    $sAuthor = isset($s["author"]) ? (string)$s["author"] : "";
                    $sTitle = isset($s["title"]) ? (string)$s["title"] : "";
                    $btnLabel = "#" . $sidBook . " - " . $sAuthor . " / " . $sTitle;
                    $suggestionsHtml .= "<button type='button' class='submit submit--secondary reconcile-suggest-btn' onclick=\"var f=document.getElementById('reconcile_link_idbook_" . $itemId . "'); if(f){f.value='" . $sidBook . "'; f.focus();}\">" . htmlspecialchars($btnLabel, ENT_QUOTES, "UTF-8") . "</button> ";
                }
                $j++;
            }
            $suggestionsHtml .= "</div>";
        }

        $detailsText = "";
        if (isset($details["reason"]))
            $detailsText = (string)$details["reason"];
        else if (isset($details["differences"]) && is_array($details["differences"]))
            $detailsText = "Différences: " . implode(", ", array_keys($details["differences"]));
        if ($detailsText == "" && $message != "")
            $detailsText = $message;

        $checked = ($status == "pending") ? " checked='checked'" : "";
        $disabled = ($status == "applied" || $status == "ignored") ? " disabled='disabled'" : "";
        $actionOptions = array(
            "add" => "Ajouter",
            "update" => "Mettre à jour",
            "update_local" => "Mise à jour de gestlibr",
            "remove" => "Supprimer",
            "link_local" => "Lier fiche locale (onEbay=1)",
            "set_on_ebay_1" => "Passer onEbay=1",
            "set_on_ebay_0" => "Passer onEbay=0",
            "set_qty_1" => "Aligner quantité locale sur eBay (1)",
            "ignore" => "Ignorer"
        );
        $actionHtml = "<select name='reconcile_action_" . $itemId . "'" . $disabled . ">";
        foreach ($actionOptions as $key => $label)
        {
            $selected = ($actionValue == $key) ? " selected='selected'" : "";
            $actionHtml .= "<option value='" . htmlspecialchars($key, ENT_QUOTES, "UTF-8") . "'" . $selected . ">" . htmlspecialchars($label, ENT_QUOTES, "UTF-8") . "</option>";
        }
        $actionHtml .= "</select>";

        $statusBadge = "<span class='reconcile-status reconcile-status--" . htmlspecialchars($status, ENT_QUOTES, "UTF-8") . "'>" . htmlspecialchars($status, ENT_QUOTES, "UTF-8") . "</span>";
        $remoteIdBook = 0;
        if (isset($details["remoteIdBook"]))
            $remoteIdBook = (int)$details["remoteIdBook"];
        else if (ctype_digit((string)$remoteSku))
            $remoteIdBook = (int)$remoteSku;
        $remoteIdentifier = isset($details["remoteIdentifier"]) ? trim((string)$details["remoteIdentifier"]) : "";
        if ($remoteIdentifier == "")
            $remoteIdentifier = $remoteSku;
        $remoteIdBookLabel = "invalide";
        if ($remoteIdBook > 0)
        {
            $remoteIdBookUrl = "?m=bo&a=vi&sa=mo&id=" . (int)$remoteIdBook;
            $remoteIdBookLabel = "<a href='" . htmlspecialchars($remoteIdBookUrl, ENT_QUOTES, "UTF-8")
                . "' target='_blank' rel='noopener noreferrer'>"
                . (int)$remoteIdBook . "</a>";
        }
        $remoteLabel = "idBook eBay: " . $remoteIdBookLabel;
        if ($remoteIdentifier != "")
        {
            $remoteIdentifierLabel = htmlspecialchars($remoteIdentifier, ENT_QUOTES, "UTF-8");
            if (ctype_digit((string)$remoteIdentifier) && (int)$remoteIdentifier > 0)
            {
                $remoteIdentifierUrl = "?m=bo&a=vi&sa=mo&id=" . (int)$remoteIdentifier;
                $remoteIdentifierLabel = "<a href='" . htmlspecialchars($remoteIdentifierUrl, ENT_QUOTES, "UTF-8")
                    . "' target='_blank' rel='noopener noreferrer'>"
                    . (int)$remoteIdentifier . "</a>";
            }
            $remoteLabel .= "<br>Réf.: " . $remoteIdentifierLabel;
        }
        if ($remoteListingId != "")
        {
            $remoteItemLabel = htmlspecialchars($remoteListingId, ENT_QUOTES, "UTF-8");
            if (ctype_digit((string)$remoteListingId))
            {
                $remoteItemUrl = "https://www.ebay.fr/itm/" . rawurlencode((string)$remoteListingId);
                $remoteItemLabel = "<a href='" . htmlspecialchars($remoteItemUrl, ENT_QUOTES, "UTF-8")
                    . "' target='_blank' rel='noopener noreferrer'>"
                    . htmlspecialchars($remoteListingId, ENT_QUOTES, "UTF-8") . "</a>";
            }
            $remoteLabel .= "<br>Item: " . $remoteItemLabel;
        }
        $localLinkStatus = isset($details["localLinkStatus"]) ? trim((string)$details["localLinkStatus"]) : "";
        if ($localLinkStatus == "")
            $localLinkStatus = ($idBook > 0 ? "n/a" : "-");

        $idBookInput = "<input type='text' id='reconcile_link_idbook_" . $itemId . "' name='reconcile_link_idbook_" . $itemId . "' value='" . ($idBook > 0 ? $idBook : "") . "' size='8'" . $disabled . ">";
        if ($idBook > 0)
        {
            $localBookUrl = "?m=bo&a=vi&sa=mo&id=" . (int)$idBook;
            $idBookInput .= "<br><a href='" . htmlspecialchars($localBookUrl, ENT_QUOTES, "UTF-8")
                . "' target='_blank' rel='noopener noreferrer'>Ouvrir #" . (int)$idBook . "</a>";
        }

        $rowStatusClass = preg_replace('/[^a-z0-9_-]/i', '', strtolower((string)$status));
        if ($rowStatusClass == "")
            $rowStatusClass = "unknown";
        $rowClass = "reconcile-item-row reconcile-item-row--status-" . $rowStatusClass;
        if ($status == "applied")
            $rowClass .= " reconcile-item-row--hidden-applied";

        $html .= "<tr class='" . htmlspecialchars($rowClass, ENT_QUOTES, "UTF-8") . "'>"
              . "<td><input type='checkbox' class='reconcile-row-checkbox' data-discrepancy-type='" . htmlspecialchars($discrepancyType, ENT_QUOTES, "UTF-8") . "' name='reconcile_selected[]' value='" . $itemId . "'" . $checked . $disabled . "><br>" . $statusBadge . "</td>"
              . "<td>" . htmlspecialchars($discrepancyType, ENT_QUOTES, "UTF-8") . "</td>"
              . "<td>" . $actionHtml . "</td>"
              . "<td>" . $idBookInput . "</td>"
              . "<td>" . $remoteLabel . "</td>"
              . "<td>" . htmlspecialchars($localLinkStatus, ENT_QUOTES, "UTF-8") . "</td>"
              . "<td>" . htmlspecialchars($author, ENT_QUOTES, "UTF-8") . "<br><strong>" . htmlspecialchars($title, ENT_QUOTES, "UTF-8") . "</strong></td>"
              . "<td>" . htmlspecialchars($detailsText, ENT_QUOTES, "UTF-8") . $suggestionsHtml . "</td>"
              . "</tr>";
        $i++;
    }
    $html .= "</table>";
    return $html;
}


switch ($a)
{
    case "co":
        $template->set_filenames(array('body' => 'internet_configure.tpl'));
	// If a Internet link is submited :
	if ((isset($_POST["id"])) AND (isset($_POST["name"])))
	{
	    if ($_POST["id"]==-1)
		// New creating
		$internet->create($_POST["name"],$_POST["description"],$_POST["method"],$_POST["header"],$_POST["template"],$_POST["param"]);
	    else
		// Update
		$internet->modify($_POST["id"],$_POST["name"],$_POST["description"],$_POST["method"],$_POST["header"],$_POST["template"],$_POST["param"]);
	}

	// If a Internet link is selected :
	if (isset($_POST["id"]))
	{
	    if ($_POST["id"]!=-1)
	    {
		$data = $internet->get($_POST["id"]);
		$optionListSelected = "<OPTION VALUE='" . $_POST["id"] . "'>" . $data["name"] . "</OPTION>";
		$optionMethodSelected = "<OPTION VALUE='" . $data["method"] . "'>" . $data["method"] . "</OPTION>";
	    }
	    else
	    {
		$data["name"] = $txt["new"];
		$data["idInternet"] = "-1";
	    }
	    $template->assign_vars(array('OPTION_LIST_SELECTED' => $optionListSelected, 'NAME_VALUE' => $data["name"], 'DESCRIPTION_VALUE' => $data["description"], 'METHOD_SELECTED' => $optionMethodSelected,
                'PARAM_VALUE' => $data["param"], 'HEADER_VALUE' => $data["header"], 'TEMPLATE_VALUE' => $data["template"],
                'ID_VALUE' => $data["idInternet"]));
	}

	// Creating the internet available list
	$list = $internet->listInternet();
	$i=0;
	$optionList = "";
	while($i!=sizeof($list))
	{
	    $optionList .= "<OPTION VALUE='" . $list[$i]["idInternet"] . "'>" . $list[$i]["name"] . "</OPTION>";
	    $i++;
	}
        $template->assign_vars(array('INTERNET' => $txt["internet"], 'CONFIGURE' => $txt["configure"],
                'NEW' => $txt["new"], 'CHANGE' => $txt["change"], 'OPTION_LIST' => $optionList, 'NAME' => $txt["name"], 'DESCRIPTION' => $txt["description"], 'METHOD' => $txt["method"],
                'PARAM' => $txt["parameters"], 'TEMPLATE' => $txt["template"], 'HEADER' => $txt["header"], 'SAVE' => $txt["submit"]));
        $template->pparse('body');
        break;
    case "se":
	// Creating the internet available list
	$list = $internet->listInternet();
	$i=0;
	$optionList = "";
	while($i!=sizeof($list))
	{
	    $optionList .= "<OPTION VALUE='" . $list[$i]["idInternet"] . "'>" . $list[$i]["name"] . "</OPTION>";
	    $i++;
	}
	// If an action is asked (add or del), we do it now
        if ((isset($_GET["id"])))
        {
	    if ((isset($_GET["add"])))
		$internet->addDetail($_GET["id"],$_GET["type"],$_GET["add"]);
	    if ((isset($_GET["del"])))
	        $internet->delDetail($_GET["del"]);
          $_POST["id"] = $_GET["id"];
        }
        // And now, we create list internet available
	if ((isset($_POST["id"])))
	{
	    $selected = $internet->get($_POST["id"]);
	    if ($selected)
	    {
		// We now check if there is an update
		if (isset($_POST["available"]))
		{
		    // TODO
		}
		// We create all the listings (available / selected)
		$optionSelected = "<OPTION VALUE='" . $selected["idInternet"] . "'>" . $selected["name"] . "</option>";
		$selected_list = "";
		$excluded_list = "";
		$i=0;
		while($i!=(sizeof($selected["details"])))
		{
		    switch($selected["details"][$i]["type"])
		    {
			case "cat":
			    $sel = $category->get($selected["details"][$i]["detail"]);
			    $selected_list .= "<a href='?m=in&a=se&id=" . $selected["idInternet"] . "&del=" . $selected["details"][$i]["idInternetDetails"] . "'>" . $sel["name"] . "</a><br/>";
			    break;
			case "excludeCatalog":
			    $catalog->idCatalog = $selected["details"][$i]["detail"]; 
			    $sel = $catalog->get();
			    $excluded_list .= "<a href='?m=in&a=se&id=" . $selected["idInternet"] . "&del=" . $selected["details"][$i]["idInternetDetails"] . "'>" . $sel["name"] . "</a><br/>";
			    break;
		    	default:
			    break;
		    }
		    $i++;
		}
		// Creating available category list, without selected !
		$cat = $category->getList();
		$available_list = "";
		$i=0;
		while($i!=(sizeof($cat)))
		{
		    // We check if current category isn't already in selected list
		    $j=0;
		    $isSelected = false;
		    while($j!=(sizeof($selected["details"])))
		    {
			if ($selected["details"][$j]["detail"]==$cat[$i]["idCategory"])
			    $isSelected = true;
			$j++;
		    }
		    // If current category ins't in selected list, we add it here in available list
		    if (!($isSelected))
			$available_list .= "<a href='?m=in&a=se&id=" . $selected["idInternet"] . "&add=" . $cat[$i]["idCategory"] . "&type=cat'>" . $cat[$i]["name"] . "</a><br/>";
		    $i++;
		}
        	$template->assign_vars(array('ID' => $_POST["id"], 'SELECTED_LIST' => $selected_list,
					'AVAILABLE_LIST' => $available_list, 'OPTION_LIST_SELECTED' => $optionSelected, '' => $txt[""], '' => $txt[""]));

		// Creating available catalog (for exclusion), without selected !
		$cat = $catalog->getList();
		$available_exclude = "";
		$i=0;
		while($i!=(sizeof($cat)))
		{
		    // We check if current category isn't already in selected list
		    $j=0;
		    $isSelected = false;
		    while($j!=(sizeof($selected["details"])))
		    {
		        if ($selected["details"][$j]["detail"]==$cat[$i]["idCatalog"])
		            $isSelected = true;
		        $j++;
		    }
		    // If current category ins't in selected list, we add it here in available list
		    if (!($isSelected))
			$available_exclude .= "<a href='?m=in&a=se&id=" . $selected["idInternet"] . "&add=" . $cat[$i]["idCatalog"] . "&type=excludeCatalog'>" . $cat[$i]["name"] . "</a><br/>";
		    $i++;
		}
        	$template->assign_vars(array('EXCLUDED_LIST' => $excluded_list,	'EXCLUDE_LIST' => $available_exclude));
	    }
	}

        $template->set_filenames(array('body' => 'internet_select.tpl'));
        $template->assign_vars(array('INTERNET' => $txt["internet"], 'SAVE' => $txt["submit"], 'SELECTION' => $txt["selection"],
                'OPTION_LIST' => $optionList, 'CHANGE' => $txt["change"], 'AVAILABLE_CATEGORY' => $txt["categorieList"],
								'SELECTED_CATEGORY' => $txt["seletedCategories"], 'EXCLUDE_CATALOG_LIST' => $txt["excludeCatalogList"], 'EXCLUDED_CATALOG_LIST' => $txt["excludedCatalogList"]));
        $template->pparse('body');
        break;
    case "sy":
        if (isset($_GET["sync"]))
        {
	    echo "SÈlection en cours...";
	    if ($internet->update($_GET["sync"]))
	        $template->assign_vars(array('MSG' => $txt["syncOK"]));
	    else
	        $template->assign_vars(array('MSG' => $txt["syncKO"]));
        }
	// Creating the internet available list
	$list = $internet->listInternet();
	$i=0;
	while($i!=sizeof($list))
	{
	    $optionList .= "<OPTION VALUE='" . $list[$i]["idInternet"] . "'>" . $list[$i]["name"] . "</OPTION>";
	    $i++;
	}
	// If there is a selection
	if ((isset($_POST["id"])))
	{
	    $selected = $internet->get($_POST["id"]);
	    if ($selected)
	    {
		$optionListSelected = "<OPTION VALUE='" . $selected["idInternet"] . "'>" . $selected["name"] . "</OPTION>";
		$bookList = $internet->getBookList($_POST["id"]);
		$template->assign_vars(array('OPTION_LIST_SELECTED' => $optionListSelected, 'AVAILABLE_TOTAL_BOOKS' => $txt["totalBooks"],
						  'NB_BOOKS' => (sizeof($bookList)), 'SYNCHRO_NOW' => $txt["synchroLaunch"], 'ID' => $_POST["id"]));
		// TO CONTINUE
            }
	}

//	$list=$internet->getBookListByCat($catList);
//     	$internet->createTAB($list,"abebooks.txt");
//     	$internet->ftpUploadAbebooks("abebooks.txt");
        $template->set_filenames(array('body' => 'internet_sync.tpl'));
        $template->assign_vars(array('INTERNET' => $txt["internet"], 'SYNCHRO_LAUNCH' => $txt["synchro"],
                'CHANGE' => $txt["change"], 'OPTION_LIST' => $optionList));
        $template->pparse('body');
        break;

	    // Ebay
	    case "eb":
		$msg = "";
		$addIdBook = isset($_GET["addIdBook"]) ? $_GET["addIdBook"] : "";
		$delIdBook = isset($_GET["delIdBook"]) ? $_GET["delIdBook"] : "";
	$updateIdBook = isset($_GET["update"]) ? $_GET["update"] : "";
	$updateAll = isset($_GET["updateAll"]) ? $_GET["updateAll"] : "";
	$getOrders = isset($_GET["getOrders"]) ? $_GET["getOrders"] : "";
	$resyncEbayId = isset($_GET["resyncebayid"]) ? $_GET["resyncebayid"] : "";
	$resetPage = isset($_GET["reset"]) ? $_GET["reset"] : "";
	$oauthStart = isset($_GET["oauth_start"]) ? $_GET["oauth_start"] : "";
	$oauthStatusRequest = isset($_GET["oauth_status"]) ? $_GET["oauth_status"] : "";
	$oauthCallback = isset($_GET["oauth_callback"]) ? $_GET["oauth_callback"] : "";
	$oauthCode = isset($_GET["code"]) ? $_GET["code"] : "";
	$oauthState = isset($_GET["state"]) ? $_GET["state"] : "";
	$oauthSaveRefreshToken = isset($_POST["oauth_save_refresh_token"]) ? $_POST["oauth_save_refresh_token"] : "";
	$oauthClearRefreshToken = isset($_POST["oauth_clear_refresh_token"]) ? $_POST["oauth_clear_refresh_token"] : "";
	$oauthManualRefreshToken = isset($_POST["oauth_refresh_token"]) ? trim($_POST["oauth_refresh_token"]) : "";
	$setupSave = isset($_POST["ebay_setup_save"]) ? $_POST["ebay_setup_save"] : "";
	$setupFetch = isset($_GET["ebay_setup_fetch"]) ? $_GET["ebay_setup_fetch"] : "";
	$setupCreateLocation = isset($_POST["ebay_setup_create_location"]) ? $_POST["ebay_setup_create_location"] : "";
	$setupOptInBusinessPolicies = isset($_REQUEST["ebay_setup_optin_business_policies"]) ? $_REQUEST["ebay_setup_optin_business_policies"] : "";
	$setupCreatePaymentPolicy = isset($_POST["ebay_setup_create_payment_policy"]) ? $_POST["ebay_setup_create_payment_policy"] : "";
	$setupCreateFulfillmentPolicy = isset($_POST["ebay_setup_create_fulfillment_policy"]) ? $_POST["ebay_setup_create_fulfillment_policy"] : "";
	$setupCreateReturnPolicy = isset($_POST["ebay_setup_create_return_policy"]) ? $_POST["ebay_setup_create_return_policy"] : "";
	$setupPaymentPolicyPayload = isset($_POST["setup_payment_policy_payload"]) ? trim($_POST["setup_payment_policy_payload"]) : "";
	$setupFulfillmentPolicyPayload = isset($_POST["setup_fulfillment_policy_payload"]) ? trim($_POST["setup_fulfillment_policy_payload"]) : "";
	$setupReturnPolicyPayload = isset($_POST["setup_return_policy_payload"]) ? trim($_POST["setup_return_policy_payload"]) : "";
	$setupApplyField = isset($_GET["ebay_setup_apply_field"]) ? $_GET["ebay_setup_apply_field"] : "";
	$setupApplyValue = isset($_GET["ebay_setup_apply_value"]) ? $_GET["ebay_setup_apply_value"] : "";
	$setupDeletePolicyType = isset($_GET["ebay_setup_delete_policy_type"]) ? trim($_GET["ebay_setup_delete_policy_type"]) : "";
	$setupDeletePolicyId = isset($_GET["ebay_setup_delete_policy_id"]) ? trim($_GET["ebay_setup_delete_policy_id"]) : "";
	$setupAutofill = isset($_GET["ebay_setup_autofill"]) ? $_GET["ebay_setup_autofill"] : "";
	$policySyncRequest = isset($_REQUEST["ebay_policy_sync"]) ? $_REQUEST["ebay_policy_sync"] : "";
	$setupSyncRequest = isset($_REQUEST["ebay_setup_sync"]) ? $_REQUEST["ebay_setup_sync"] : "";
		$syncRunRequest = isset($_REQUEST["ebay_sync_run"]) ? $_REQUEST["ebay_sync_run"] : "";
		$syncDryRunRequest = isset($_REQUEST["ebay_sync_dry_run"]) ? $_REQUEST["ebay_sync_dry_run"] : "";
		$syncRunSelectedId = isset($_REQUEST["ebay_sync_run_id"]) ? (int)$_REQUEST["ebay_sync_run_id"] : 0;
		$syncDeleteRunId = isset($_REQUEST["ebay_sync_delete_run_id"]) ? (int)$_REQUEST["ebay_sync_delete_run_id"] : 0;
			$reconcileScanRequest = isset($_REQUEST["ebay_reconcile_scan"]) ? $_REQUEST["ebay_reconcile_scan"] : "";
		$reconcileContinueRunId = isset($_REQUEST["ebay_reconcile_continue_run_id"]) ? (int)$_REQUEST["ebay_reconcile_continue_run_id"] : 0;
		$reconcileRunId = isset($_REQUEST["ebay_reconcile_run_id"]) ? (int)$_REQUEST["ebay_reconcile_run_id"] : 0;
		$reconcileDeleteRunId = isset($_REQUEST["ebay_reconcile_delete_run_id"]) ? (int)$_REQUEST["ebay_reconcile_delete_run_id"] : 0;
	$reconcileApplyRequest = isset($_POST["ebay_reconcile_apply"]) ? $_POST["ebay_reconcile_apply"] : "";
	$orphanLinkRequest = isset($_REQUEST["ebay_orphan_link"]) ? $_REQUEST["ebay_orphan_link"] : "";
	$orphanRunId = isset($_REQUEST["orphan_run_id"]) ? (int)$_REQUEST["orphan_run_id"] : 0;
	$orphanItemId = isset($_REQUEST["orphan_item_id"]) ? (int)$_REQUEST["orphan_item_id"] : 0;
	$orphanIdBook = isset($_REQUEST["orphan_idbook"]) ? (int)$_REQUEST["orphan_idbook"] : 0;
	$setupMarketplaceId = isset($_POST["setup_marketplace_id"]) ? trim($_POST["setup_marketplace_id"]) : "";
	$setupMerchantLocationKey = isset($_POST["setup_merchant_location_key"]) ? trim($_POST["setup_merchant_location_key"]) : "";
	$setupFixedCategoryId = isset($_POST["setup_fixed_category_id"]) ? trim($_POST["setup_fixed_category_id"]) : "";
	$setupPaymentPolicyId = isset($_POST["setup_payment_policy_id"]) ? trim($_POST["setup_payment_policy_id"]) : "";
	$setupFulfillmentPolicyId = isset($_POST["setup_fulfillment_policy_id"]) ? trim($_POST["setup_fulfillment_policy_id"]) : "";
	$setupReturnPolicyId = isset($_POST["setup_return_policy_id"]) ? trim($_POST["setup_return_policy_id"]) : "";
	$setupDescriptionHeaderHtml = isset($_POST["setup_description_header_html"]) ? (string)$_POST["setup_description_header_html"] : "";
	$setupDescriptionFooterHtml = isset($_POST["setup_description_footer_html"]) ? (string)$_POST["setup_description_footer_html"] : "";
	$setupLocationName = isset($_POST["setup_location_name"]) ? trim($_POST["setup_location_name"]) : "";
	$setupLocationAddress1 = isset($_POST["setup_location_address1"]) ? trim($_POST["setup_location_address1"]) : "";
	$setupLocationCity = isset($_POST["setup_location_city"]) ? trim($_POST["setup_location_city"]) : "";
	$setupLocationPostalCode = isset($_POST["setup_location_postal_code"]) ? trim($_POST["setup_location_postal_code"]) : "";
	$setupLocationCountry = isset($_POST["setup_location_country"]) ? trim($_POST["setup_location_country"]) : "";
	$targetGroupContext = internet_ebay_resolve_target_group_id();
	$currentGroupId = (int)$targetGroupContext["targetGroupId"];
	$isAdminUser = (bool)$targetGroupContext["isAdmin"];
	$targetGroupQuery = internet_ebay_target_query($currentGroupId);
	$targetGroupHidden = internet_ebay_target_hidden_input($currentGroupId);
	$targetGroupSelectorHtml = internet_ebay_group_selector_html($currentGroupId, $isAdminUser);

	$oauth = ebay_oauth_status($currentGroupId);
	$oauthResultMsg = "";
	$setup = array();
	$setupAssets = array(
	    "locations" => array(),
	    "paymentPolicies" => array(),
	    "fulfillmentPolicies" => array(),
	    "returnPolicies" => array(),
	    "errors" => array(),
	);
	$setupResultMsg = "";
	$reconcileSummary = "";

	if ($oauthStart != "")
	{
	    $oauthStartResult = ebay_oauth_start($currentGroupId);
	    if (is_array($oauthStartResult) && (!isset($oauthStartResult["ok"]) || $oauthStartResult["ok"] != true))
	    {
		$oauthResultMsg = isset($oauthStartResult["message"]) ? $oauthStartResult["message"] : "Erreur OAuth eBay.";
		$msg = $oauthResultMsg;
		$oauth = ebay_oauth_status($currentGroupId);
	    }
	}

	if ($oauthSaveRefreshToken != "")
	{
	    $saveResult = ebay_oauth_save_refresh_token($oauthManualRefreshToken, $currentGroupId);
	    $oauthResultMsg = $saveResult["message"];
	    if ($saveResult["ok"] != true)
		$msg = $saveResult["message"];
	    $oauth = ebay_oauth_status($currentGroupId);
	}

	if ($oauthClearRefreshToken != "")
	{
	    $clearResult = ebay_oauth_clear_refresh_token($currentGroupId);
	    $oauthResultMsg = $clearResult["message"];
	    if ($clearResult["ok"] != true)
		$msg = $clearResult["message"];
	    $oauth = ebay_oauth_status($currentGroupId);
	}

	if (($oauthCallback != "") || ($oauthCode != ""))
	{
	    if ($oauthCode != "")
	    {
		$oauthResult = ebay_oauth_callback($oauthCode, $oauthState, $currentGroupId);
		$oauthResultMsg = $oauthResult["message"];
		if ($oauthResult["ok"] != true)
		    $msg = $oauthResult["message"];
	    }
	    else
	    {
		$oauthResultMsg = "OAuth callback incomplet: paramètre code manquant.";
		$msg = $oauthResultMsg;
	    }
	    $oauth = ebay_oauth_status($currentGroupId);
	}

	if ($oauthStatusRequest != "")
	{
	    $oauth = ebay_oauth_status($currentGroupId);
	}

	$setupSnapshot = ebay_rest_get_setup_snapshot($currentGroupId);
	if ($setupSnapshot["ok"] == true && isset($setupSnapshot["data"]) && is_array($setupSnapshot["data"]))
	    $setup = $setupSnapshot["data"];
	else if (isset($setupSnapshot["message"]) && $setupSnapshot["message"] != "")
	    $setupResultMsg = $setupSnapshot["message"];

	if ($setupSave != "")
	{
	    if ($setupFixedCategoryId == "")
		$setupFixedCategoryId = "29223";
	    if (!preg_match('/^\d+$/', $setupFixedCategoryId))
	    {
		$setupResultMsg = "ID catégorie eBay fixe invalide: valeur numérique attendue.";
		$msg = $setupResultMsg;
	    }
	    else
	    {
		$saveSetupResult = ebay_rest_save_setup(array(
		    "marketplaceId" => $setupMarketplaceId,
		    "merchantLocationKey" => $setupMerchantLocationKey,
		    "fixedCategoryId" => $setupFixedCategoryId,
		    "paymentPolicyId" => $setupPaymentPolicyId,
		    "fulfillmentPolicyId" => $setupFulfillmentPolicyId,
		    "returnPolicyId" => $setupReturnPolicyId,
		    "descriptionHeaderHtml" => $setupDescriptionHeaderHtml,
		    "descriptionFooterHtml" => $setupDescriptionFooterHtml,
		), $currentGroupId);
		$setupResultMsg = $saveSetupResult["message"];
		if ($saveSetupResult["ok"] != true)
		    $msg = $saveSetupResult["message"];
		if (isset($saveSetupResult["data"]) && is_array($saveSetupResult["data"]))
		    $setup = $saveSetupResult["data"];
	    }
	}

	if ($setupApplyField != "" && $setupApplyValue != "")
	{
	    $allowed = array(
		"merchantLocationKey" => "merchantLocationKey",
		"paymentPolicyId" => "paymentPolicyId",
		"fulfillmentPolicyId" => "fulfillmentPolicyId",
		"returnPolicyId" => "returnPolicyId",
	    );
	    if (isset($allowed[$setupApplyField]))
	    {
		$applyValues = array();
		$applyValues[$allowed[$setupApplyField]] = $setupApplyValue;
		$applyResult = ebay_rest_save_setup($applyValues, $currentGroupId);
		$setupResultMsg = $applyResult["message"];
		if ($applyResult["ok"] != true)
		    $msg = $applyResult["message"];
		if (isset($applyResult["data"]) && is_array($applyResult["data"]))
		    $setup = $applyResult["data"];
		$setupFetch = "1";
	    }
	}

	if ($setupDeletePolicyType != "" && $setupDeletePolicyId != "")
	{
	    $deletePolicyResult = ebay_rest_delete_policy($setupDeletePolicyType, $setupDeletePolicyId, $currentGroupId);
	    $setupResultMsg = $deletePolicyResult["message"];
	    if ($deletePolicyResult["ok"] != true)
		$msg = $deletePolicyResult["message"];
	    $setupFetch = "1";
	    $setupSyncRequest = "1";
	}

	if ($setupCreateLocation != "")
	{
	    $createLocationResult = ebay_rest_create_location(array(
		"merchantLocationKey" => $setupMerchantLocationKey,
		"name" => $setupLocationName,
		"addressLine1" => $setupLocationAddress1,
		"city" => $setupLocationCity,
		"postalCode" => $setupLocationPostalCode,
		"country" => $setupLocationCountry,
	    ), $currentGroupId);
	    $setupResultMsg = $createLocationResult["message"];
	    if ($createLocationResult["ok"] != true)
		$msg = $createLocationResult["message"];
	    $setupSnapshot = ebay_rest_get_setup_snapshot($currentGroupId);
	    if ($setupSnapshot["ok"] == true && isset($setupSnapshot["data"]) && is_array($setupSnapshot["data"]))
		$setup = $setupSnapshot["data"];
	    $setupFetch = "1";
	    $setupSyncRequest = "1";
	}

	if ($setupOptInBusinessPolicies != "")
	{
	    $optInResult = ebay_rest_opt_in_business_policies($currentGroupId);
	    $setupResultMsg = $optInResult["message"];
	    if ($optInResult["ok"] != true)
		$msg = $optInResult["message"];
	    $setupFetch = "1";
	}

	if ($setupCreatePaymentPolicy != "")
	{
	    $payloadData = json_decode($setupPaymentPolicyPayload, true);
	    if (!is_array($payloadData))
	    {
		$setupResultMsg = "JSON Payment policy invalide.";
		$msg = $setupResultMsg;
	    }
	    else
	    {
		$createPolicyResult = ebay_rest_create_policy("payment", $payloadData, $currentGroupId);
		$setupResultMsg = $createPolicyResult["message"];
		if ($createPolicyResult["ok"] != true)
		    $msg = $createPolicyResult["message"];
		else if (isset($createPolicyResult["data"]["policyId"]) && $createPolicyResult["data"]["policyId"] != "")
		    ebay_rest_save_setup(array("paymentPolicyId" => $createPolicyResult["data"]["policyId"]), $currentGroupId);
		$setupFetch = "1";
		$setupSyncRequest = "1";
	    }
	}

	if ($setupCreateFulfillmentPolicy != "")
	{
	    $payloadData = json_decode($setupFulfillmentPolicyPayload, true);
	    if (!is_array($payloadData))
	    {
		$setupResultMsg = "JSON Fulfillment policy invalide.";
		$msg = $setupResultMsg;
	    }
	    else
	    {
		$createPolicyResult = ebay_rest_create_policy("fulfillment", $payloadData, $currentGroupId);
		$setupResultMsg = $createPolicyResult["message"];
		if ($createPolicyResult["ok"] != true)
		    $msg = $createPolicyResult["message"];
		else if (isset($createPolicyResult["data"]["policyId"]) && $createPolicyResult["data"]["policyId"] != "")
		    ebay_rest_save_setup(array("fulfillmentPolicyId" => $createPolicyResult["data"]["policyId"]), $currentGroupId);
		$setupFetch = "1";
		$setupSyncRequest = "1";
	    }
	}

	if ($setupCreateReturnPolicy != "")
	{
	    $payloadData = json_decode($setupReturnPolicyPayload, true);
	    if (!is_array($payloadData))
	    {
		$setupResultMsg = "JSON Return policy invalide.";
		$msg = $setupResultMsg;
	    }
	    else
	    {
		$createPolicyResult = ebay_rest_create_policy("return", $payloadData, $currentGroupId);
		$setupResultMsg = $createPolicyResult["message"];
		if ($createPolicyResult["ok"] != true)
		    $msg = $createPolicyResult["message"];
		else if (isset($createPolicyResult["data"]["policyId"]) && $createPolicyResult["data"]["policyId"] != "")
		    ebay_rest_save_setup(array("returnPolicyId" => $createPolicyResult["data"]["policyId"]), $currentGroupId);
		$setupFetch = "1";
		$setupSyncRequest = "1";
	    }
	}

	if ($setupAutofill != "")
	{
	    $listResult = ebay_rest_list_setup_assets($currentGroupId);
	    if (isset($listResult["data"]) && is_array($listResult["data"]))
		$setupAssets = $listResult["data"];
	    if ($listResult["ok"] != true)
	    {
		$setupResultMsg = $listResult["message"];
		$msg = $listResult["message"];
	    }
	    else
	    {
		$autofill = array();
		if ((!isset($setup["merchantLocationKey"]) || $setup["merchantLocationKey"] == "") && isset($setupAssets["locations"][0]["merchantLocationKey"]))
		    $autofill["merchantLocationKey"] = $setupAssets["locations"][0]["merchantLocationKey"];
		if ((!isset($setup["paymentPolicyId"]) || $setup["paymentPolicyId"] == "") && isset($setupAssets["paymentPolicies"][0]["paymentPolicyId"]))
		    $autofill["paymentPolicyId"] = $setupAssets["paymentPolicies"][0]["paymentPolicyId"];
		if ((!isset($setup["fulfillmentPolicyId"]) || $setup["fulfillmentPolicyId"] == "") && isset($setupAssets["fulfillmentPolicies"][0]["fulfillmentPolicyId"]))
		    $autofill["fulfillmentPolicyId"] = $setupAssets["fulfillmentPolicies"][0]["fulfillmentPolicyId"];
		if ((!isset($setup["returnPolicyId"]) || $setup["returnPolicyId"] == "") && isset($setupAssets["returnPolicies"][0]["returnPolicyId"]))
		    $autofill["returnPolicyId"] = $setupAssets["returnPolicies"][0]["returnPolicyId"];

		if (sizeof($autofill) > 0)
		{
		    $autofillResult = ebay_rest_save_setup($autofill, $currentGroupId);
		    $setupResultMsg = $autofillResult["message"];
		    if ($autofillResult["ok"] != true)
			$msg = $autofillResult["message"];
		    if (isset($autofillResult["data"]) && is_array($autofillResult["data"]))
			$setup = $autofillResult["data"];
		}
		else
		    $setupResultMsg = "Aucun ID à auto-remplir (déjà configuré ou introuvable).";
	    }
	    $setupSyncRequest = "1";
	}

	if ($setupFetch != "")
	{
	    $listResult = ebay_rest_list_setup_assets($currentGroupId);
	    if (isset($listResult["data"]) && is_array($listResult["data"]))
		$setupAssets = $listResult["data"];
	    if ($listResult["ok"] != true)
	    {
		$setupResultMsg = $listResult["message"];
		$msg = $listResult["message"];
	    }
	    else if (isset($listResult["message"]) && $listResult["message"] != "")
		$setupResultMsg = $listResult["message"];
	    $setupSyncRequest = "1";
	}

	$groupSettingsResult = ebay_rest_get_group_settings($currentGroupId);
	$groupSettings = array();
	if ($groupSettingsResult["ok"] == true && isset($groupSettingsResult["data"]) && is_array($groupSettingsResult["data"]))
	    $groupSettings = $groupSettingsResult["data"];
	else
	    $groupSettings = array(
		"idGroup" => $currentGroupId,
		"marketplaceId" => "",
		"merchantLocationKey" => "",
		"defaultPaymentPolicyId" => "",
		"defaultFulfillmentPolicyId" => "",
		"defaultReturnPolicyId" => "",
	    );

	if ($setupSyncRequest == "" && $policySyncRequest != "")
	    $setupSyncRequest = $policySyncRequest;

	if ($setupSyncRequest != "")
	{
	    $setupSyncResult = ebay_rest_sync_setup_catalog($currentGroupId);
	    if ($setupSyncResult["ok"] != true)
	    {
		$setupResultMsg = $setupSyncResult["message"];
		$msg = $setupSyncResult["message"];
	    }
	    else
		$setupResultMsg = $setupSyncResult["message"];
	}

	$policyCatalogRows = array();
	$policyCatalogResult = ebay_rest_get_policy_catalog($currentGroupId);
	if ($policyCatalogResult["ok"] == true && isset($policyCatalogResult["data"]) && is_array($policyCatalogResult["data"]))
	    $policyCatalogRows = $policyCatalogResult["data"];

	$paymentPolicyCatalog = array();
	$fulfillmentPolicyCatalog = array();
	$returnPolicyCatalog = array();
	$pc = 0;
	while ($pc < sizeof($policyCatalogRows))
	{
	    $row = $policyCatalogRows[$pc];
	    $type = isset($row["policyType"]) ? $row["policyType"] : "";
	    if ($type == "payment")
		$paymentPolicyCatalog[] = $row;
	    else if ($type == "fulfillment")
		$fulfillmentPolicyCatalog[] = $row;
	    else if ($type == "return")
		$returnPolicyCatalog[] = $row;
	    $pc++;
	}

	$syncRunSummary = "";
	$syncDryRunEnabled = ($syncDryRunRequest == "1" || $syncDryRunRequest == "on");
		if ($syncRunRequest != "")
		{
		    $syncRunResult = ebay_rest_run_sync(
		$currentGroupId,
		(isset($_SESSION["idUser"]) ? (int)$_SESSION["idUser"] : 0),
		"manual",
		(int)config("ebay_max_book_per_batch"),
		array("dryRun" => ($syncDryRunEnabled ? 1 : 0))
	    );
	    if ($syncRunResult["ok"] != true)
	    {
		$msg = $syncRunResult["message"];
		$syncRunSummary = $syncRunResult["message"];
	    }
		    else
		    {
			$syncRunSummary = $syncRunResult["message"];
			$msg = $syncRunSummary;
		    }
		}

		if ($syncDeleteRunId > 0)
		{
		    $deleteSyncResult = ebay_rest_delete_sync_run($syncDeleteRunId, $currentGroupId);
		    $syncRunSummary = $deleteSyncResult["message"];
		    if ($deleteSyncResult["ok"] != true)
			$msg = $deleteSyncResult["message"];
		    else
			$msg = $syncRunSummary;
		    if ($syncRunSelectedId === $syncDeleteRunId)
			$syncRunSelectedId = 0;
		}

		$syncRunHistoryRows = array();
		$syncRunHistory = ebay_rest_list_sync_runs($currentGroupId, 15);
		if ($syncRunHistory["ok"] == true && isset($syncRunHistory["data"]) && is_array($syncRunHistory["data"]))
		    $syncRunHistoryRows = $syncRunHistory["data"];

	$syncRunActionsRows = array();
	if ($syncRunSelectedId > 0)
	{
	    $syncRunActions = ebay_rest_list_sync_actions($syncRunSelectedId, $currentGroupId, 1200);
	    if ($syncRunActions["ok"] == true && isset($syncRunActions["data"]) && is_array($syncRunActions["data"]))
		$syncRunActionsRows = $syncRunActions["data"];
	}

	$reconcileRunsRows = array();
	$reconcileItemsRows = array();
	if ($reconcileDeleteRunId > 0)
	{
	    $deleteResult = ebay_rest_delete_reconcile_run($reconcileDeleteRunId, $currentGroupId);
	    $reconcileSummary = $deleteResult["message"];
	    if ($deleteResult["ok"] != true)
		$msg = $deleteResult["message"];
	    if ($reconcileRunId === $reconcileDeleteRunId)
		$reconcileRunId = 0;
	}
		if ($reconcileScanRequest != "")
		{
		    $reconcileMaxOffers = (int)config("ebay_reconcile_max_offers");
		    if ($reconcileMaxOffers <= 0)
			$reconcileMaxOffers = 10000;
		    $scanOptions = array();
		    $resumeWarning = "";
		    $scanOptions["maxDurationSeconds"] = 300;

		    $maxInventoryLookups = (int)config("ebay_reconcile_max_inventory_lookups");
		    if ($maxInventoryLookups > 0)
			$scanOptions["maxInventoryLookups"] = $maxInventoryLookups;
		    $maxFallbackSkuLookups = (int)config("ebay_reconcile_max_fallback_sku_lookups");
		    if ($maxFallbackSkuLookups > 0)
			$scanOptions["maxFallbackSkuLookups"] = $maxFallbackSkuLookups;
		    $maxTradingDescriptionLookups = (int)config("ebay_reconcile_max_trading_description_lookups");
		    if ($maxTradingDescriptionLookups > 0)
			$scanOptions["maxTradingDescriptionLookups"] = $maxTradingDescriptionLookups;

		    $scanTrigger = "manual";
		    if ($reconcileScanRequest === "continue")
		    {
			$scanTrigger = "manual_continue";
			if ($scanOptions["maxDurationSeconds"] < 300)
			    $scanOptions["maxDurationSeconds"] = 300;

			$resumeRunsResult = ebay_rest_list_reconcile_runs($currentGroupId, 20);
			if ($resumeRunsResult["ok"] == true && isset($resumeRunsResult["data"]) && is_array($resumeRunsResult["data"]))
			{
			    $resumeRows = $resumeRunsResult["data"];
			    $resumeRow = array();
			    $resumeRowRunId = 0;
			    $rr = 0;
			    while ($rr < sizeof($resumeRows))
			    {
				$candidate = $resumeRows[$rr];
				$candidateRunId = isset($candidate["id"]) ? (int)$candidate["id"] : 0;
				if ($reconcileContinueRunId > 0 && $candidateRunId === $reconcileContinueRunId)
				{
				    $resumeRow = $candidate;
				    $resumeRowRunId = $candidateRunId;
				    break;
				}
				if ($reconcileContinueRunId <= 0 && $rr === 0)
				{
				    $resumeRow = $candidate;
				    $resumeRowRunId = $candidateRunId;
				    break;
				}
				$rr++;
			    }

			    $resumeInfo = internet_ebay_reconcile_resume_from_row($resumeRow);
			    if ($resumeInfo["canContinue"] === true)
			    {
				$scanOptions["tradingStartPage"] = (int)$resumeInfo["nextPage"];
				if ($resumeRowRunId > 0)
				{
				    $scanOptions["continueRunId"] = (int)$resumeRowRunId;
				    $reconcileRunId = (int)$resumeRowRunId;
				}
				if (isset($resumeInfo["localStartAfterIdBook"]) && (int)$resumeInfo["localStartAfterIdBook"] > 0)
				    $scanOptions["resumeLocalStartIdBook"] = (int)$resumeInfo["localStartAfterIdBook"];
				if (isset($resumeInfo["remoteOfferStartOffset"]) && (int)$resumeInfo["remoteOfferStartOffset"] > 0)
				    $scanOptions["resumeRemoteOfferStartOffset"] = (int)$resumeInfo["remoteOfferStartOffset"];
			    }
			    else if ($reconcileContinueRunId > 0)
			    {
				$resumeWarning = "Reprise impossible: aucun curseur de continuation trouvé sur ce run. Relance complète effectuée.";
			    }
			}
		    }

		    $scanResult = ebay_rest_reconcile_scan($currentGroupId, (isset($_SESSION["idUser"]) ? (int)$_SESSION["idUser"] : 0), $scanTrigger, $reconcileMaxOffers, $scanOptions);
		    $reconcileSummary = $scanResult["message"];
		    if ($resumeWarning != "")
			$reconcileSummary = $resumeWarning . " | " . $reconcileSummary;
			    if ($scanResult["ok"] != true)
				$msg = $scanResult["message"];
		}

	if ($reconcileApplyRequest != "")
	{
	    $selectedRows = isset($_POST["reconcile_selected"]) && is_array($_POST["reconcile_selected"]) ? $_POST["reconcile_selected"] : array();
	    $selections = array();
	    $i=0;
	    while($i<sizeof($selectedRows))
	    {
		$itemId = (int)$selectedRows[$i];
		if ($itemId > 0)
		{
		    $selections[] = array(
			"itemId" => $itemId,
			"action" => (isset($_POST["reconcile_action_".$itemId]) ? $_POST["reconcile_action_".$itemId] : ""),
			"linkIdBook" => (isset($_POST["reconcile_link_idbook_".$itemId]) ? (int)$_POST["reconcile_link_idbook_".$itemId] : 0),
		    );
		}
		$i++;
	    }
	    if ($reconcileRunId <= 0)
		$reconcileRunId = isset($_POST["ebay_reconcile_run_id"]) ? (int)$_POST["ebay_reconcile_run_id"] : 0;
	    $applyResult = ebay_rest_apply_reconcile_actions($reconcileRunId, $selections, (isset($_SESSION["idUser"]) ? (int)$_SESSION["idUser"] : 0), $currentGroupId);
	    $reconcileSummary = $applyResult["message"];
	    if ($applyResult["ok"] != true)
		$msg = $applyResult["message"];
	}

	if ($orphanLinkRequest != "" && $orphanRunId > 0 && $orphanItemId > 0 && $orphanIdBook > 0)
	{
		$applyResult = ebay_rest_apply_reconcile_actions($orphanRunId, array(
		    array(
			"itemId" => $orphanItemId,
			"action" => "link_local",
			"linkIdBook" => $orphanIdBook,
		    ),
		), (isset($_SESSION["idUser"]) ? (int)$_SESSION["idUser"] : 0), $currentGroupId);
	    $reconcileSummary = $applyResult["message"];
	    if ($applyResult["ok"] != true)
		$msg = $applyResult["message"];
	    $reconcileRunId = $orphanRunId;
	}

		$reconcileRunsResult = ebay_rest_list_reconcile_runs($currentGroupId, 15);
		if ($reconcileRunsResult["ok"] == true && isset($reconcileRunsResult["data"]) && is_array($reconcileRunsResult["data"]))
		    $reconcileRunsRows = $reconcileRunsResult["data"];
		if ($reconcileSummary == "")
		{
	    $ri = 0;
	    while ($ri < sizeof($reconcileRunsRows))
	    {
		$row = $reconcileRunsRows[$ri];
		$rowId = isset($row["id"]) ? (int)$row["id"] : 0;
		if ($rowId === (int)$reconcileRunId)
		{
		    if (isset($row["message"]))
			$reconcileSummary = (string)$row["message"];
		    break;
		}
		$ri++;
	    }
	}
	if ($reconcileRunId > 0)
	{
	    $reconcileItemsResult = ebay_rest_get_reconcile_items($reconcileRunId, $currentGroupId);
	    if ($reconcileItemsResult["ok"] == true && isset($reconcileItemsResult["data"]) && is_array($reconcileItemsResult["data"]))
		$reconcileItemsRows = $reconcileItemsResult["data"];
	}
	$reconcileRunsHtml = internet_ebay_reconcile_runs_html($reconcileRunsRows, $reconcileRunId, $currentGroupId);
	$reconcileItemsHtml = internet_ebay_reconcile_items_html($reconcileRunId, $reconcileItemsRows);

	// If we need to add a book to ebay
	if ($addIdBook!="")
	{
	    if (internet_ebay_book_exists_in_group($addIdBook, $currentGroupId)) // If the book exists ?
	    {
		// We add the book on Ebay
			$id_ebay = $internet->addOnEbay($addIdBook, $currentGroupId);
		if ($id_ebay)
		    // Ok
		    $msg = $txt["bookAddedOnEbayWithItemID"].$id_ebay;
		else
		{
			    $lastError = $internet->getEbayLastError($addIdBook, $currentGroupId);
		    if ($lastError != "")
			$msg = $txt["errorDuringAddingBookOnEbay"].$addIdBook." : ".$lastError;
		    else
			$msg = $txt["errorDuringAddingBookOnEbay"].$addIdBook;
		}
	    }
	    else
		$msg = $txt["unknownIdBook"];
	}

	// If we need to delete a book from ebay
	if ($delIdBook!="")
	{
	    if (internet_ebay_book_exists_in_group($delIdBook, $currentGroupId)) // If the book exists ?
	    {
		// We delete the book on Ebay
			if ($internet->delOnEbay($delIdBook, $currentGroupId))
		    // Ok
		    $msg = $txt["bookDeletedOnEbayOk"];
		else
		    $msg = $txt["bookDeletedOnEbayError"].$delIdBook;		    
	    }
	    else
		$msg = $txt["unknownIdBook"];
	}

	// If we need to show Orders from ebay
	if ($getOrders!="")
	{
		    var_dump($internet->getEbayOrder($currentGroupId));
	}

	// If we need to update a book to ebay
	if ($updateIdBook!="")
	{
	    if (internet_ebay_book_exists_in_group($updateIdBook, $currentGroupId)) // If the book exists ?
	    {
		// We update the book on Ebay
			if ($internet->updateEbayItemId($updateIdBook, $currentGroupId))
		    // Ok
		    $msg = $txt["bookUpdatedOnEbayOk"].$book->data["ebayItemId"];
		else
		{
			    $lastError = $internet->getEbayLastError($updateIdBook, $currentGroupId);
		    if ($lastError != "")
			$msg = $txt["bookUpdatedOnEbayError"].$updateIdBook." : ".$lastError;
		    else
			$msg = $txt["bookUpdatedOnEbayError"].$updateIdBook;
		}
	    }
	    else
		$msg = $txt["unknownIdBook"];
	}

	// If we need to show Orders from ebay
	if ($updateAll!="")
	{
	    $validEbayPriceSql = "(CASE WHEN CAST(REPLACE(COALESCE(NULLIF(TRIM(`price_ebay`), ''), '0'), ',', '.') AS DECIMAL(12,2)) > 0 THEN CAST(REPLACE(COALESCE(NULLIF(TRIM(`price_ebay`), ''), '0'), ',', '.') AS DECIMAL(12,2)) ELSE CAST(REPLACE(COALESCE(NULLIF(TRIM(`price`), ''), '0'), ',', '.') AS DECIMAL(12,2)) END) > 0";
	    echo "<table border='1'>";
	    // 1) New book to add on Ebay (onEbay = 1 and quantity > 0 and no ebayItemId yet)
		    $sql = $babase->DbSelect("SELECT * FROM `book` WHERE `idGroup`='" . $currentGroupId . "' AND `quantity`>0 AND `ebayItemId`='' AND `onEbay`='1' AND `internet`='1' AND " . $validEbayPriceSql . " LIMIT 0,".config("ebay_max_book_per_batch"),$nbNew);
	    echo "<tr><td>&nbsp;</td><td>NEW ($nbNew)</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
	    $i=0;
	    while($i!=$nbNew)
	    {
		echo "<tr><td>".$sql[$i]["idBook"]."</td><td>".substr($sql[$i]["author"],0,29)."</td><td>".substr($sql[$i]["title"],0,29)."</td><td>".$txt["add"]."</td><td>";
		// We add the book on Ebay
			$id_ebay = $internet->addOnEbay($sql[$i]["idBook"], $currentGroupId);
		if ($id_ebay)
		    // Ok
		    echo "</td><td>".$txt["ok"]."</td><td>".$txt["bookAddedOnEbayWithItemID"].$id_ebay;
		else
		    echo "</td><td>".$txt["error"]."</td><td>".$txt["errorDuringAddingBookOnEbay"].$sql[$i]["idBook"];
		echo "</td></tr>";
		$i++;
	    }
	    // 2) Book to remove (because qty = 0)
		    $sql = $babase->DbSelect("SELECT * FROM `book` WHERE `idGroup`='" . $currentGroupId . "' AND `quantity`=0 AND `ebayItemId`!='' LIMIT 0,".config("ebay_max_book_per_batch"),$nbSold);
	    echo "<tr><td>&nbsp;</td><td>SOLD ($nbSold)</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
	    $i=0;
	    while($i!=$nbSold)
	    {
		echo "<tr><td>".$sql[$i]["idBook"]."</td><td>".substr($sql[$i]["author"],0,29)."</td><td>".substr($sql[$i]["title"],0,29)."</td><td>".$txt["del"]."</td><td>";
		// We add the book on Ebay
			$id_ebay = $internet->delOnEbay($sql[$i]["idBook"], $currentGroupId);
		if ($id_ebay)
		    // Ok
		    echo "</td><td>".$txt["ok"]."</td><td>".$txt["bookDeletedOnEbayOk"];
		else
		    echo "</td><td>".$txt["error"]."</td><td>".$txt["bookDeletedOnEbayError"].$sql[$i]["idBook"];
		echo "</td></tr>";
		$i++;
	    }
	    // 3) Book to remove (because onEbay has been removed and an ebayItemId exist)
		    $sql = $babase->DbSelect("SELECT * FROM `book` WHERE `idGroup`='" . $currentGroupId . "' AND `ebayItemId`!='' AND (`onEbay`='0'  OR `internet`='0') LIMIT 0,".config("ebay_max_book_per_batch"),$nbRemoved);
	    echo "<tr><td>&nbsp;</td><td>REMOVED ($nbRemoved)</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
	    $i=0;
	    while($i!=$nbRemoved)
	    {
		echo "<tr><td>".$sql[$i]["idBook"]."</td><td>".substr($sql[$i]["author"],0,29)."</td><td>".substr($sql[$i]["title"],0,29)."</td><td>".$txt["del"]."</td><td>";
		// We add the book on Ebay
			$id_ebay = $internet->delOnEbay($sql[$i]["idBook"], $currentGroupId);
		if ($id_ebay)
		    // Ok
		    echo "</td><td>".$txt["ok"]."</td><td>".$txt["bookDeletedOnEbayOk"];
		else
		    echo "</td><td>".$txt["error"]."</td><td>".$txt["bookDeletedOnEbayError"].$sql[$i]["idBook"];
		echo "</td></tr>";
		$i++;
	    }
	    // 4) Book to update (because ebayLastSync < modifyDate)
		    $sql = $babase->DbSelect("SELECT * FROM `book` WHERE `idGroup`='" . $currentGroupId . "' AND `ebayItemId`!='' AND `ebayLastSync`<`modifyDate` AND " . $validEbayPriceSql . " LIMIT 0,".config("ebay_max_book_per_batch"),$nbUpdate);
	    echo "<tr><td>&nbsp;</td><td>UPDATE ($nbUpdate)</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
	    $i=0;
	    while($i!=$nbUpdate)
	    {
		echo "<tr><td>".$sql[$i]["idBook"]."</td><td>".substr($sql[$i]["author"],0,29)."</td><td>".substr($sql[$i]["title"],0,29)."</td><td>".$txt["update"]."</td><td>";
		// We add the book on Ebay
			$id_ebay = $internet->updateEbayItemId($sql[$i]["idBook"], $currentGroupId);
		if ($id_ebay)
		    // Ok
		    echo "</td><td>".$txt["ok"]."</td><td>".$txt["bookAddedOnEbayWithItemID"].$id_ebay;
		else
		    echo "</td><td>".$txt["error"]."</td><td>".$txt["errorDuringAddingBookOnEbay"].$sql[$i]["idBook"];
		echo "</td></tr>";
		$i++;
	    }
	    // End of update  
	    echo "</table>";
	    
	    // checking if there is more update to do after, we put a javascript to auto update the page
	    if (($nbNew+$nbSold+$nbRemoved+$nbUpdate) > 0)
		echo "<script type=\"text/JavaScript\">
<!--
setTimeout(\"location.href = 'http://". $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . "';\",3000);
-->
</script>";
	    else
		$msg = $txt["AllBookUpdatedOnEbayOk"];
	    	    
	}

	// If we need to resync data from ebay
	if ($resyncEbayId!="")
	{
	    if ($resetPage==1)
		unset($_SESSION["PageNumber"]);
	    if (isset($_SESSION["PageNumber"]))
		$_SESSION["PageNumber"] = $_SESSION["PageNumber"]+1;
	    else
		$_SESSION["PageNumber"] = 1;
	    echo "<br>Page ".$_SESSION["PageNumber"]."<br>";
		    $response = $internet->resyncEbay($_SESSION["PageNumber"],50,$currentGroupId);
	    echo "<table border='1'>";
	    echo "<tr><td>idBook</td><td>ItemID Ebay</td><td>Title</td><td>&nbsp;</td></tr>";
	    if ($response && isset($response->ItemArray) && isset($response->ItemArray->Item))
	    {
		$i=0;
		while($i!=(sizeof($response->ItemArray->Item)))
		{
		    echo "<tr><td>".($response->ItemArray->Item[$i]->SKU)."</td><td>".($response->ItemArray->Item[$i]->ItemID)."</td><td>".substr($response->ItemArray->Item[$i]->Title,0,60)."</td>";
		    // We add the book on Ebay
		    if ((int)$response->ItemArray->Item[$i]->SKU)
		    {
			$bookEbay = $book->get($response->ItemArray->Item[$i]->SKU);
			if ($bookEbay["ebayItemId"]==$response->ItemArray->Item[$i]->ItemID)
			    // Ok
			    echo "<td>".$txt["ok"]."</td>";
			else
			    {
				echo "<td>".$txt["error"]." (ebayItemId=".$bookEbay["ebayItemId"].")</td>";
					$babase->DbQuery("UPDATE `book` SET `ebayItemId`='".($response->ItemArray->Item[$i]->ItemID)."' WHERE idGroup='" . $currentGroupId . "' AND `idBook`='".($response->ItemArray->Item[$i]->SKU)."'");
			    }
		    }
		    echo "</tr>";
		    $i++;
		}
	    }
	    else
	    {
		echo "<tr><td colspan='4'>Aucune donnée retournée par eBay.</td></tr>";
	    }
	    // End of update  
	    echo "</table>";
	    
	    // checking if there is more update to do after, we put a javascript to auto update the page
	    if ($response && isset($response->HasMoreItems) && $response->HasMoreItems)
		echo "<script type=\"text/JavaScript\">
<!--
setTimeout(\"location.href = 'http://". $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . "';\",1000);
-->
</script>";
	    else
		$msg = $txt["AllBookUpdatedOnEbayOk"];
	}
	
	$oauthStatusLabel = "KO";
	if ($oauth["tokenOk"] == true)
	    $oauthStatusLabel = "OK";

	$oauthProvider = isset($oauth["provider"]) ? $oauth["provider"] : "legacy";
	$oauthEnv = isset($oauth["environment"]) ? $oauth["environment"] : "-";
	$oauthTokenSource = isset($oauth["refreshTokenSource"]) ? $oauth["refreshTokenSource"] : "none";
	$oauthSellerReady = "-";
	if (isset($oauth["sellerRegistrationCompleted"]))
	    $oauthSellerReady = ($oauth["sellerRegistrationCompleted"] ? "OK" : "KO");
	$oauthSellerLabel = "Compte vendeur eBay";
	if ($oauthEnv == "sandbox")
	    $oauthSellerLabel = "Compte vendeur eBay (sandbox)";
	if ($oauthEnv == "production")
	    $oauthSellerLabel = "Compte vendeur eBay (production)";
	$oauthOfferConfig = "KO";
	if (
	    isset($oauth["merchantLocationKeyConfigured"]) &&
	    isset($oauth["paymentPolicyConfigured"]) &&
	    isset($oauth["fulfillmentPolicyConfigured"]) &&
	    isset($oauth["returnPolicyConfigured"]) &&
	    $oauth["merchantLocationKeyConfigured"] &&
	    $oauth["paymentPolicyConfigured"] &&
	    $oauth["fulfillmentPolicyConfigured"] &&
	    $oauth["returnPolicyConfigured"]
	)
	    $oauthOfferConfig = "OK";
	$oauthMessage = "";
	if (isset($oauth["message"]))
	    $oauthMessage = $oauth["message"];
	if (isset($oauth["tokenError"]) && $oauth["tokenError"] != "")
	    $oauthMessage = $oauth["tokenError"];
	if ($oauthResultMsg != "")
	    $oauthMessage = $oauthResultMsg;

	$setupMarketplaceValue = isset($groupSettings["marketplaceId"]) && $groupSettings["marketplaceId"] != "" ? $groupSettings["marketplaceId"] : (isset($setup["marketplaceId"]) ? $setup["marketplaceId"] : "EBAY_FR");
	$setupMerchantLocationValue = isset($groupSettings["merchantLocationKey"]) && $groupSettings["merchantLocationKey"] != "" ? $groupSettings["merchantLocationKey"] : (isset($setup["merchantLocationKey"]) ? $setup["merchantLocationKey"] : "");
	$setupFixedCategoryValue = isset($setup["fixedCategoryId"]) && $setup["fixedCategoryId"] != "" ? $setup["fixedCategoryId"] : "29223";
	$setupPaymentPolicyValue = isset($groupSettings["defaultPaymentPolicyId"]) && $groupSettings["defaultPaymentPolicyId"] != "" ? $groupSettings["defaultPaymentPolicyId"] : (isset($setup["paymentPolicyId"]) ? $setup["paymentPolicyId"] : "");
	$setupFulfillmentPolicyValue = isset($groupSettings["defaultFulfillmentPolicyId"]) && $groupSettings["defaultFulfillmentPolicyId"] != "" ? $groupSettings["defaultFulfillmentPolicyId"] : (isset($setup["fulfillmentPolicyId"]) ? $setup["fulfillmentPolicyId"] : "");
	$setupReturnPolicyValue = isset($groupSettings["defaultReturnPolicyId"]) && $groupSettings["defaultReturnPolicyId"] != "" ? $groupSettings["defaultReturnPolicyId"] : (isset($setup["returnPolicyId"]) ? $setup["returnPolicyId"] : "");
	$setupDescriptionHeaderValue = (isset($setup["descriptionHeaderHtml"]) ? (string)$setup["descriptionHeaderHtml"] : "");
	$setupDescriptionFooterValue = (isset($setup["descriptionFooterHtml"]) ? (string)$setup["descriptionFooterHtml"] : "");
	if ($setupSave != "")
	{
	    $setupDescriptionHeaderValue = $setupDescriptionHeaderHtml;
	    $setupDescriptionFooterValue = $setupDescriptionFooterHtml;
	    if (isset($saveSetupResult["ok"]) && $saveSetupResult["ok"] == true && isset($saveSetupResult["data"]) && is_array($saveSetupResult["data"]))
	    {
		$setupDescriptionHeaderValue = (string)(isset($saveSetupResult["data"]["descriptionHeaderHtml"]) ? $saveSetupResult["data"]["descriptionHeaderHtml"] : "");
		$setupDescriptionFooterValue = (string)(isset($saveSetupResult["data"]["descriptionFooterHtml"]) ? $saveSetupResult["data"]["descriptionFooterHtml"] : "");
	    }
	}
	$locationCatalogRows = array();
	$locationCatalogResult = ebay_rest_get_location_catalog($currentGroupId);
	if ($locationCatalogResult["ok"] == true && isset($locationCatalogResult["data"]) && is_array($locationCatalogResult["data"]))
	    $locationCatalogRows = $locationCatalogResult["data"];
	$setupLocationSelect = internet_ebay_location_select_html("setup_merchant_location_key", $locationCatalogRows, $setupMerchantLocationValue);
		$setupPaymentPolicySelect = internet_ebay_policy_select_html("setup_payment_policy_id", $paymentPolicyCatalog, $setupPaymentPolicyValue);
		$setupFulfillmentPolicySelect = internet_ebay_policy_select_html("setup_fulfillment_policy_id", $fulfillmentPolicyCatalog, $setupFulfillmentPolicyValue);
		$setupReturnPolicySelect = internet_ebay_policy_select_html("setup_return_policy_id", $returnPolicyCatalog, $setupReturnPolicyValue);
		$syncRunHistoryHtml = internet_ebay_sync_runs_html($syncRunHistoryRows, $syncRunSelectedId, $currentGroupId);
		$syncRunDetailsHtml = "";
		if ($syncRunSelectedId > 0)
		{
		    $syncRunActionsHtml = internet_ebay_sync_actions_html($syncRunActionsRows, $oauthEnv);
		    $syncRunDetailsHtml = "<h3>Détail des actions du run #" . (int)$syncRunSelectedId . "</h3><div>" . $syncRunActionsHtml . "</div>";
		}
		$syncDryRunCheckedAttr = ($syncDryRunEnabled ? " checked='checked'" : "");
		$reconcileDetailsHtml = "";
		if ($reconcileRunId > 0)
		{
		    $reconcileDetailsHtml .= "<h4>Détail audit #" . (int)$reconcileRunId . "</h4>";
		    $reconcileDetailsHtml .= "<form action='?m=in&a=eb' method='post' name='reconcile_apply' onsubmit='return reconcilePrepareSubmit(this);'>"
		        . "<input type='hidden' name='m' value='in'>"
		        . "<input type='hidden' name='a' value='eb'>"
		        . "<input type='hidden' name='target_group_id' value='" . (int)$currentGroupId . "'>"
		        . "<input type='hidden' name='ebay_reconcile_run_id' value='" . (int)$reconcileRunId . "'>"
		        . "<div>" . $reconcileItemsHtml . "</div>"
		        . "<div class='reconcile-bulk-actions' style='margin-top:10px;display:flex;flex-wrap:wrap;gap:8px;align-items:center;'>"
		        . "<span><strong>Sélection rapide :</strong></span>"
		        . "<button type='button' class='submit submit--secondary' onclick=\"return reconcileBulkSelection('select','missing_local_link');\">Sélectionner liens manquants</button>"
		        . "<button type='button' class='submit submit--secondary' onclick=\"return reconcileBulkSelection('select','missing_remote');\">Sélectionner corrections onEbay=0</button>"
		        . "<button type='button' class='submit submit--secondary' onclick=\"return reconcileBulkSelection('select','missing_local');\">Sélectionner orphelins</button>"
		        . "<button type='button' class='submit submit--secondary' onclick=\"return reconcileBulkSelection('unselect','missing_local_link');\">Désélectionner liens manquants</button>"
		        . "<button type='button' class='submit submit--secondary' onclick=\"return reconcileBulkSelection('unselect','missing_remote');\">Désélectionner corrections onEbay=0</button>"
		        . "<button type='button' class='submit submit--secondary' onclick=\"return reconcileBulkSelection('unselect','missing_local');\">Désélectionner orphelins</button>"
		        . "<button type='button' class='submit submit--secondary' onclick=\"return reconcileBulkSelection('unselect','');\">Tout désélectionner</button>"
		        . "</div>"
		        . "<div style='margin-top:10px;'><input class='submit' type='submit' name='ebay_reconcile_apply' value='Appliquer sélection' /></div>"
		        . "</form>";
		}
	$setupMarketplaceSource = isset($setup["marketplaceSource"]) ? $setup["marketplaceSource"] : "none";
	$setupMerchantSource = isset($setup["merchantLocationKeySource"]) ? $setup["merchantLocationKeySource"] : "none";
	$setupFixedCategorySource = isset($setup["fixedCategorySource"]) ? $setup["fixedCategorySource"] : "config";
	$setupPaymentSource = isset($setup["paymentPolicySource"]) ? $setup["paymentPolicySource"] : "none";
	$setupFulfillmentSource = isset($setup["fulfillmentPolicySource"]) ? $setup["fulfillmentPolicySource"] : "none";
	$setupReturnSource = isset($setup["returnPolicySource"]) ? $setup["returnPolicySource"] : "none";
	$setupDescriptionHeaderSource = isset($setup["descriptionHeaderSource"]) ? $setup["descriptionHeaderSource"] : "none";
	$setupDescriptionFooterSource = isset($setup["descriptionFooterSource"]) ? $setup["descriptionFooterSource"] : "none";
	$setupLocationNameValue = $setupLocationName;
	if ($setupLocationNameValue == "")
	    $setupLocationNameValue = "Gestlibr";
	$setupLocationAddressValue = $setupLocationAddress1;
	if ($setupLocationAddressValue == "")
	    $setupLocationAddressValue = "1 rue exemple";
	$setupLocationCityValue = $setupLocationCity;
	if ($setupLocationCityValue == "")
	    $setupLocationCityValue = "Paris";
	$setupLocationPostalValue = $setupLocationPostalCode;
	if ($setupLocationPostalValue == "")
	    $setupLocationPostalValue = "75001";
	$setupLocationCountryValue = $setupLocationCountry;
	if ($setupLocationCountryValue == "")
	    $setupLocationCountryValue = "FR";
	if ($setupPaymentPolicyPayload == "")
	{
	    $setupPaymentPolicyPayload = json_encode(array(
		"name" => "GestLibr Payment",
		"description" => "Policy de paiement créée par GestLibr",
		"marketplaceId" => $setupMarketplaceValue,
		"categoryTypes" => array(
		    array("name" => "ALL_EXCLUDING_MOTORS_VEHICLES"),
		),
		"immediatePay" => true
	    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	}
	if ($setupFulfillmentPolicyPayload == "")
	{
	    $setupFulfillmentPolicyPayload = json_encode(array(
		"name" => "GestLibr Fulfillment",
		"description" => "Policy logistique créée par GestLibr",
		"marketplaceId" => $setupMarketplaceValue,
		"categoryTypes" => array(
		    array("name" => "ALL_EXCLUDING_MOTORS_VEHICLES"),
		),
		"handlingTime" => array(
		    "value" => 1,
		    "unit" => "DAY"
		),
		"localPickup" => true,
		"pickupDropOff" => false
	    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	}
	if ($setupReturnPolicyPayload == "")
	{
	    $setupReturnPolicyPayload = json_encode(array(
		"name" => "GestLibr Return",
		"description" => "Policy de retour créée par GestLibr",
		"marketplaceId" => $setupMarketplaceValue,
		"categoryTypes" => array(
		    array("name" => "ALL_EXCLUDING_MOTORS_VEHICLES"),
		),
		"returnsAccepted" => true,
		"returnPeriod" => array(
		    "value" => 30,
		    "unit" => "DAY"
		),
		"returnShippingCostPayer" => "BUYER"
	    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	}

	$setupBusinessPolicyStatusValue = "Inconnu";
	$setupProgramsValue = "-";
	if (isset($oauth["businessPoliciesOptedIn"]) && $oauth["businessPoliciesOptedIn"] !== null)
	    $setupBusinessPolicyStatusValue = ($oauth["businessPoliciesOptedIn"] ? "OK" : "KO");
	if (isset($oauth["programs"]) && is_array($oauth["programs"]) && sizeof($oauth["programs"]) > 0)
	    $setupProgramsValue = implode(", ", $oauth["programs"]);
	if (($setupFetch != "" || $setupAutofill != "") && isset($setupAssets["sellingPolicyManagementOptedIn"]) && $setupAssets["sellingPolicyManagementOptedIn"] !== null)
	    $setupBusinessPolicyStatusValue = ($setupAssets["sellingPolicyManagementOptedIn"] ? "OK" : "KO");
	if (($setupFetch != "" || $setupAutofill != "") && isset($setupAssets["programs"]) && is_array($setupAssets["programs"]) && sizeof($setupAssets["programs"]) > 0)
	    $setupProgramsValue = implode(", ", $setupAssets["programs"]);

	$setupAssetsHtml = "<p>Cliquez sur <strong>Lister</strong> pour récupérer les policies et locations existantes depuis eBay.</p>";
	if ($setupFetch != "" || $setupAutofill != "")
	{
	    $setupAssetsHtml = "";
	    if (isset($setupAssets["errors"]) && is_array($setupAssets["errors"]) && sizeof($setupAssets["errors"]) > 0)
	    {
		$setupAssetsHtml .= "<div class='err'>" . htmlspecialchars(implode(" | ", $setupAssets["errors"]), ENT_QUOTES, "UTF-8") . "</div>";
	    }

	    $setupAssetsHtml .= "<h3>Locations eBay</h3>";
	    if (isset($setupAssets["locations"]) && is_array($setupAssets["locations"]) && sizeof($setupAssets["locations"]) > 0)
	    {
		$setupAssetsHtml .= "<table class='bordered'><tr><th>Key</th><th>Nom</th><th>Ville</th><th>Pays</th><th>Action</th></tr>";
		$i=0;
		while($i!=sizeof($setupAssets["locations"]))
		{
		    $row = $setupAssets["locations"][$i];
		    $keyRaw = isset($row["merchantLocationKey"]) ? $row["merchantLocationKey"] : "";
		    $setupAssetsHtml .= "<tr><td>" . htmlspecialchars($keyRaw, ENT_QUOTES, "UTF-8") . "</td><td>" . htmlspecialchars(isset($row["name"]) ? $row["name"] : "", ENT_QUOTES, "UTF-8") . "</td><td>" . htmlspecialchars(isset($row["city"]) ? $row["city"] : "", ENT_QUOTES, "UTF-8") . "</td><td>" . htmlspecialchars(isset($row["country"]) ? $row["country"] : "", ENT_QUOTES, "UTF-8") . "</td><td><a href='?m=in&a=eb&ebay_setup_apply_field=merchantLocationKey&ebay_setup_apply_value=" . rawurlencode($keyRaw) . "&ebay_setup_fetch=1&" . $targetGroupQuery . "'>Utiliser</a></td></tr>";
		    $i++;
		}
		$setupAssetsHtml .= "</table>";
	    }
	    else
		$setupAssetsHtml .= "<p>Aucune location trouvée.</p>";

	    $setupAssetsHtml .= "<h3>Payment policies</h3>";
	    if (isset($setupAssets["paymentPolicies"]) && is_array($setupAssets["paymentPolicies"]) && sizeof($setupAssets["paymentPolicies"]) > 0)
	    {
		$setupAssetsHtml .= "<table class='bordered'><tr><th>ID</th><th>Nom</th><th>Action</th></tr>";
		$i=0;
		while($i!=sizeof($setupAssets["paymentPolicies"]))
		{
		    $row = $setupAssets["paymentPolicies"][$i];
		    $idRaw = isset($row["paymentPolicyId"]) ? $row["paymentPolicyId"] : "";
		    $setupAssetsHtml .= "<tr><td>" . htmlspecialchars($idRaw, ENT_QUOTES, "UTF-8") . "</td><td>" . htmlspecialchars(isset($row["name"]) ? $row["name"] : "", ENT_QUOTES, "UTF-8") . "</td><td><a href='?m=in&a=eb&ebay_setup_apply_field=paymentPolicyId&ebay_setup_apply_value=" . rawurlencode($idRaw) . "&ebay_setup_fetch=1&" . $targetGroupQuery . "'>Utiliser</a> | <a href='?m=in&a=eb&ebay_setup_delete_policy_type=payment&ebay_setup_delete_policy_id=" . rawurlencode($idRaw) . "&ebay_setup_fetch=1&" . $targetGroupQuery . "' onclick=\"return confirm('Supprimer cette payment policy eBay ?');\">Supprimer</a></td></tr>";
		    $i++;
		}
		$setupAssetsHtml .= "</table>";
	    }
	    else
		$setupAssetsHtml .= "<p>Aucune payment policy trouvée.</p>";

	    $setupAssetsHtml .= "<h3>Fulfillment policies</h3>";
	    if (isset($setupAssets["fulfillmentPolicies"]) && is_array($setupAssets["fulfillmentPolicies"]) && sizeof($setupAssets["fulfillmentPolicies"]) > 0)
	    {
		$setupAssetsHtml .= "<table class='bordered'><tr><th>ID</th><th>Nom</th><th>Action</th></tr>";
		$i=0;
		while($i!=sizeof($setupAssets["fulfillmentPolicies"]))
		{
		    $row = $setupAssets["fulfillmentPolicies"][$i];
		    $idRaw = isset($row["fulfillmentPolicyId"]) ? $row["fulfillmentPolicyId"] : "";
		    $setupAssetsHtml .= "<tr><td>" . htmlspecialchars($idRaw, ENT_QUOTES, "UTF-8") . "</td><td>" . htmlspecialchars(isset($row["name"]) ? $row["name"] : "", ENT_QUOTES, "UTF-8") . "</td><td><a href='?m=in&a=eb&ebay_setup_apply_field=fulfillmentPolicyId&ebay_setup_apply_value=" . rawurlencode($idRaw) . "&ebay_setup_fetch=1&" . $targetGroupQuery . "'>Utiliser</a> | <a href='?m=in&a=eb&ebay_setup_delete_policy_type=fulfillment&ebay_setup_delete_policy_id=" . rawurlencode($idRaw) . "&ebay_setup_fetch=1&" . $targetGroupQuery . "' onclick=\"return confirm('Supprimer cette fulfillment policy eBay ?');\">Supprimer</a></td></tr>";
		    $i++;
		}
		$setupAssetsHtml .= "</table>";
	    }
	    else
		$setupAssetsHtml .= "<p>Aucune fulfillment policy trouvée.</p>";

	    $setupAssetsHtml .= "<h3>Return policies</h3>";
	    if (isset($setupAssets["returnPolicies"]) && is_array($setupAssets["returnPolicies"]) && sizeof($setupAssets["returnPolicies"]) > 0)
	    {
		$setupAssetsHtml .= "<table class='bordered'><tr><th>ID</th><th>Nom</th><th>Action</th></tr>";
		$i=0;
		while($i!=sizeof($setupAssets["returnPolicies"]))
		{
		    $row = $setupAssets["returnPolicies"][$i];
		    $idRaw = isset($row["returnPolicyId"]) ? $row["returnPolicyId"] : "";
		    $setupAssetsHtml .= "<tr><td>" . htmlspecialchars($idRaw, ENT_QUOTES, "UTF-8") . "</td><td>" . htmlspecialchars(isset($row["name"]) ? $row["name"] : "", ENT_QUOTES, "UTF-8") . "</td><td><a href='?m=in&a=eb&ebay_setup_apply_field=returnPolicyId&ebay_setup_apply_value=" . rawurlencode($idRaw) . "&ebay_setup_fetch=1&" . $targetGroupQuery . "'>Utiliser</a> | <a href='?m=in&a=eb&ebay_setup_delete_policy_type=return&ebay_setup_delete_policy_id=" . rawurlencode($idRaw) . "&ebay_setup_fetch=1&" . $targetGroupQuery . "' onclick=\"return confirm('Supprimer cette return policy eBay ?');\">Supprimer</a></td></tr>";
		    $i++;
		}
		$setupAssetsHtml .= "</table>";
	    }
	    else
		$setupAssetsHtml .= "<p>Aucune return policy trouvée.</p>";
	}

	$ebaySetupMessage = $setupResultMsg;
	if ($ebaySetupMessage == "" && isset($setupAssets["errors"]) && is_array($setupAssets["errors"]) && sizeof($setupAssets["errors"]) > 0)
	    $ebaySetupMessage = implode(" | ", $setupAssets["errors"]);

		$quickActionsHtml = ""
		    . "<div class='quick-card'><strong>" . htmlspecialchars($txt["updateAllBooksOnEbay"], ENT_QUOTES, "UTF-8") . "</strong>"
		    . "<form action='?m=in&a=eb' method='get' class='quick-card__stack'><input type='hidden' name='m' value='in'><input type='hidden' name='a' value='eb'>" . $targetGroupHidden . "<input type='hidden' name='ebay_sync_run' value='1'><input class='submit' type='submit' value='Lancer synchro'><label class='quick-card__checkbox'><input type='checkbox' name='ebay_sync_dry_run' value='1'" . $syncDryRunCheckedAttr . "> Simulation (dry-run)</label></form>"
		    . "<span class='quick-card__hint'>Ajout / mise à jour / suppression selon état GestLibr.</span></div>"
	    . "<div class='quick-card'><strong>Audit de cohérence</strong>"
	    . "<form action='?m=in&a=eb' method='get' class='quick-card__stack'><input type='hidden' name='m' value='in'><input type='hidden' name='a' value='eb'>" . $targetGroupHidden . "<input type='hidden' name='ebay_reconcile_scan' value='1'>"
	    . "<input class='submit submit--secondary' type='submit' value='Scanner eBay'></form>"
	    . "<span class='quick-card__hint'>Scan limité à 300 secondes. Reprise depuis le tableau des audits partiels.</span></div>"
	    . "<div class='quick-card'><strong>Setup eBay REST</strong>"
	    . "<form action='?m=in&a=eb' method='get'><input type='hidden' name='m' value='in'><input type='hidden' name='a' value='eb'>" . $targetGroupHidden . "<input type='hidden' name='ebay_setup_sync' value='1'><input class='submit submit--secondary' type='submit' value='Sync policies + locations'></form>"
	    . "<span class='quick-card__hint'>Recharge le catalogue local (policies + merchant locations).</span></div>"
	    . "<div class='quick-card'><strong>" . htmlspecialchars($txt["getEbayOrders"], ENT_QUOTES, "UTF-8") . "</strong>"
	    . "<form action='?m=in&a=eb' method='get'><input type='hidden' name='m' value='in'><input type='hidden' name='a' value='eb'>" . $targetGroupHidden . "<input type='hidden' name='getOrders' value='1'><input class='submit submit--secondary' type='submit' value='" . htmlspecialchars($txt["update"], ENT_QUOTES, "UTF-8") . "'></form>"
	    . "<span class='quick-card__hint'>Affiche les commandes eBay côté API.</span></div>";

	$manualActionsHtml = ""
	    . "<table class='bordered'>"
	    . "<tr><td>" . htmlspecialchars($txt["addIdBookOnEbay"], ENT_QUOTES, "UTF-8") . "</td><td><form action='?m=in&a=eb' method='get'><input type='hidden' name='m' value='in'><input type='hidden' name='a' value='eb'>" . $targetGroupHidden . "<input type='text' size='8' name='addIdBook'><input class='submit submit--secondary' type='submit' value='" . htmlspecialchars($txt["add"], ENT_QUOTES, "UTF-8") . "'></form></td></tr>"
	    . "<tr><td>" . htmlspecialchars($txt["delIdBookOnEbay"], ENT_QUOTES, "UTF-8") . "</td><td><form action='?m=in&a=eb' method='get'><input type='hidden' name='m' value='in'><input type='hidden' name='a' value='eb'>" . $targetGroupHidden . "<input type='text' size='8' name='delIdBook'><input class='submit submit--secondary' type='submit' value='" . htmlspecialchars($txt["del"], ENT_QUOTES, "UTF-8") . "'></form></td></tr>"
	    . "<tr><td>" . htmlspecialchars($txt["bookUpdatedOnEbay"], ENT_QUOTES, "UTF-8") . "</td><td><form action='?m=in&a=eb' method='get'><input type='hidden' name='m' value='in'><input type='hidden' name='a' value='eb'>" . $targetGroupHidden . "<input type='text' size='8' name='update'><input class='submit submit--secondary' type='submit' value='" . htmlspecialchars($txt["update"], ENT_QUOTES, "UTF-8") . "'></form></td></tr>"
	    . "<tr><td>" . htmlspecialchars($txt["resyncAllEbayId"], ENT_QUOTES, "UTF-8") . "</td><td><form action='?m=in&a=eb' method='get'><input type='hidden' name='m' value='in'><input type='hidden' name='a' value='eb'>" . $targetGroupHidden . "<input type='hidden' name='resyncebayid' value='1'><input class='submit submit--secondary' type='submit' value='" . htmlspecialchars($txt["update"], ENT_QUOTES, "UTF-8") . "'></form></td></tr>"
	    . "</table>";

	// Form to display
        $template->set_filenames(array('body' => 'internet_ebay.tpl'));
	        $template->assign_vars(array('EBAY' => $txt["ebay"],
                'ADD' => $txt["add"], 'ADD_BOOK_FROM_ID' => $txt["addIdBookOnEbay"], 
                'DEL_BOOK_FROM_ID' => $txt["delIdBookOnEbay"], 'DEL' => $txt["del"], 'RESYNC_EBAYID' => $txt["resyncAllEbayId"],
                'SYNC_ALL_BOOKS_ON_EBAY' => $txt["updateAllBooksOnEbay"], 'UPDATE' => $txt["update"], 'UPDATE_BOOK_FROM_ID' => $txt["bookUpdatedOnEbay"],
                'GET_EBAY_ORDERS' => $txt["getEbayOrders"], 'MSG' => $msg,
		'EBAY_TARGET_GROUP_HTML' => $targetGroupSelectorHtml,
		'TARGET_GROUP_ID' => (int)$currentGroupId,
		'OAUTH_TITLE' => "Connexion OAuth eBay",
		'OAUTH_START' => "Connecter eBay",
		'OAUTH_STATUS_BTN' => "Rafraîchir statut OAuth",
		'OAUTH_PROVIDER_LABEL' => "Provider",
		'OAUTH_ENV_LABEL' => "Environnement",
		'OAUTH_TOKEN_LABEL' => "Token",
		'OAUTH_SELLER_READY_LABEL' => $oauthSellerLabel,
		'OAUTH_OFFER_CONFIG_LABEL' => "Config offre eBay",
		'OAUTH_TOKEN_SOURCE_LABEL' => "Source token OAuth",
		'OAUTH_MESSAGE_LABEL' => "Message",
		'OAUTH_MANUAL_TOKEN_LABEL' => "Refresh token (dev / localhost)",
		'OAUTH_MANUAL_TOKEN_PLACEHOLDER' => "Coller un refresh token eBay ici",
		'OAUTH_MANUAL_TOKEN_SAVE' => "Enregistrer refresh token",
		'OAUTH_MANUAL_TOKEN_CLEAR' => "Effacer refresh token",
		'OAUTH_PROVIDER_VALUE' => $oauthProvider,
		'OAUTH_ENV_VALUE' => $oauthEnv,
		'OAUTH_TOKEN_VALUE' => $oauthStatusLabel,
		'OAUTH_SELLER_READY_VALUE' => $oauthSellerReady,
		'OAUTH_OFFER_CONFIG_VALUE' => $oauthOfferConfig,
		'OAUTH_TOKEN_SOURCE_VALUE' => $oauthTokenSource,
		'OAUTH_MESSAGE_VALUE' => $oauthMessage,
		'SETUP_TITLE' => "Configuration eBay REST (spécifique au groupe)",
		'SETUP_HELP' => "Les réglages ci-dessous sont stockés par groupe. Seul le keyset global (client id/secret/redirect URI) reste global (ENV > config).",
		'SETUP_SAVE' => "Enregistrer en base",
		'SETUP_FETCH' => "Lister locations & policies",
		'SETUP_AUTOFILL' => "Auto-remplir valeurs manquantes",
		'SETUP_OPTIN_BUSINESS_POLICIES' => "Activer Business Policies",
		'SETUP_CREATE_LOCATION' => "Créer location eBay",
		'SETUP_BUSINESS_POLICY_STATUS_LABEL' => "Business Policies (SELLING_POLICY_MANAGEMENT)",
		'SETUP_BUSINESS_POLICY_STATUS_VALUE' => htmlspecialchars($setupBusinessPolicyStatusValue, ENT_QUOTES, "UTF-8"),
		'SETUP_PROGRAMS_LABEL' => "Programmes opt-in",
		'SETUP_PROGRAMS_VALUE' => htmlspecialchars($setupProgramsValue, ENT_QUOTES, "UTF-8"),
		'SETUP_MARKETPLACE_LABEL' => "Marketplace ID",
		'SETUP_MERCHANT_LOCATION_LABEL' => "Merchant location key",
		'SETUP_FIXED_CATEGORY_LABEL' => "Catégorie eBay fixe (ID)",
		'SETUP_PAYMENT_POLICY_LABEL' => "Payment policy ID",
		'SETUP_FULFILLMENT_POLICY_LABEL' => "Fulfillment policy ID",
		'SETUP_RETURN_POLICY_LABEL' => "Return policy ID",
		'SETUP_DESCRIPTION_HEADER_LABEL' => "Template description eBay - Header",
		'SETUP_DESCRIPTION_FOOTER_LABEL' => "Template description eBay - Footer",
		'SETUP_DESCRIPTION_HELP' => "Le header/footer est appliqué uniquement à l’envoi eBay quand la fiche a le flag template activé.",
		'SETUP_LOCATION_SELECT' => $setupLocationSelect,
		'SETUP_PAYMENT_POLICY_SELECT' => $setupPaymentPolicySelect,
		'SETUP_FULFILLMENT_POLICY_SELECT' => $setupFulfillmentPolicySelect,
		'SETUP_RETURN_POLICY_SELECT' => $setupReturnPolicySelect,
		'SETUP_SOURCE_LABEL' => "Source",
		'SETUP_MARKETPLACE_VALUE' => htmlspecialchars($setupMarketplaceValue, ENT_QUOTES, "UTF-8"),
		'SETUP_MERCHANT_LOCATION_VALUE' => htmlspecialchars($setupMerchantLocationValue, ENT_QUOTES, "UTF-8"),
		'SETUP_FIXED_CATEGORY_VALUE' => htmlspecialchars($setupFixedCategoryValue, ENT_QUOTES, "UTF-8"),
		'SETUP_PAYMENT_POLICY_VALUE' => htmlspecialchars($setupPaymentPolicyValue, ENT_QUOTES, "UTF-8"),
		'SETUP_FULFILLMENT_POLICY_VALUE' => htmlspecialchars($setupFulfillmentPolicyValue, ENT_QUOTES, "UTF-8"),
		'SETUP_RETURN_POLICY_VALUE' => htmlspecialchars($setupReturnPolicyValue, ENT_QUOTES, "UTF-8"),
		'SETUP_MARKETPLACE_SOURCE' => htmlspecialchars($setupMarketplaceSource, ENT_QUOTES, "UTF-8"),
		'SETUP_MERCHANT_SOURCE' => htmlspecialchars($setupMerchantSource, ENT_QUOTES, "UTF-8"),
		'SETUP_FIXED_CATEGORY_SOURCE' => htmlspecialchars($setupFixedCategorySource, ENT_QUOTES, "UTF-8"),
		'SETUP_PAYMENT_SOURCE' => htmlspecialchars($setupPaymentSource, ENT_QUOTES, "UTF-8"),
		'SETUP_FULFILLMENT_SOURCE' => htmlspecialchars($setupFulfillmentSource, ENT_QUOTES, "UTF-8"),
		'SETUP_RETURN_SOURCE' => htmlspecialchars($setupReturnSource, ENT_QUOTES, "UTF-8"),
		'SETUP_DESCRIPTION_HEADER_SOURCE' => htmlspecialchars($setupDescriptionHeaderSource, ENT_QUOTES, "UTF-8"),
		'SETUP_DESCRIPTION_FOOTER_SOURCE' => htmlspecialchars($setupDescriptionFooterSource, ENT_QUOTES, "UTF-8"),
		'SETUP_DESCRIPTION_HEADER_VALUE' => htmlspecialchars($setupDescriptionHeaderValue, ENT_QUOTES, "UTF-8"),
		'SETUP_DESCRIPTION_FOOTER_VALUE' => htmlspecialchars($setupDescriptionFooterValue, ENT_QUOTES, "UTF-8"),
		'SETUP_LOCATION_NAME_LABEL' => "Nom location",
		'SETUP_LOCATION_ADDRESS_LABEL' => "Adresse",
		'SETUP_LOCATION_CITY_LABEL' => "Ville",
		'SETUP_LOCATION_POSTAL_LABEL' => "Code postal",
		'SETUP_LOCATION_COUNTRY_LABEL' => "Pays",
		'SETUP_LOCATION_NAME_VALUE' => htmlspecialchars($setupLocationNameValue, ENT_QUOTES, "UTF-8"),
		'SETUP_LOCATION_ADDRESS_VALUE' => htmlspecialchars($setupLocationAddressValue, ENT_QUOTES, "UTF-8"),
		'SETUP_LOCATION_CITY_VALUE' => htmlspecialchars($setupLocationCityValue, ENT_QUOTES, "UTF-8"),
		'SETUP_LOCATION_POSTAL_VALUE' => htmlspecialchars($setupLocationPostalValue, ENT_QUOTES, "UTF-8"),
		'SETUP_LOCATION_COUNTRY_VALUE' => htmlspecialchars($setupLocationCountryValue, ENT_QUOTES, "UTF-8"),
		'SETUP_POLICY_CREATE_TITLE' => "Créer policies eBay via API",
		'SETUP_PAYMENT_PAYLOAD_LABEL' => "Payload Payment policy (JSON)",
		'SETUP_FULFILLMENT_PAYLOAD_LABEL' => "Payload Fulfillment policy (JSON)",
		'SETUP_RETURN_PAYLOAD_LABEL' => "Payload Return policy (JSON)",
		'SETUP_CREATE_PAYMENT_POLICY' => "Créer payment policy",
		'SETUP_CREATE_FULFILLMENT_POLICY' => "Créer fulfillment policy",
		'SETUP_CREATE_RETURN_POLICY' => "Créer return policy",
		'SETUP_SYNC_POLICIES' => "Synchroniser setup eBay",
		'SETUP_PAYMENT_PAYLOAD_VALUE' => htmlspecialchars($setupPaymentPolicyPayload, ENT_QUOTES, "UTF-8"),
		'SETUP_FULFILLMENT_PAYLOAD_VALUE' => htmlspecialchars($setupFulfillmentPolicyPayload, ENT_QUOTES, "UTF-8"),
		'SETUP_RETURN_PAYLOAD_VALUE' => htmlspecialchars($setupReturnPolicyPayload, ENT_QUOTES, "UTF-8"),
		'SETUP_ASSETS_HTML' => $setupAssetsHtml,
		'SETUP_MESSAGE' => htmlspecialchars($ebaySetupMessage, ENT_QUOTES, "UTF-8"),
		'EBAY_SYNC_RUN_LABEL' => "Synchro eBay",
			'EBAY_SYNC_RUN_SUBMIT' => "Lancer la synchro",
			'EBAY_SYNC_RUN_SUMMARY' => htmlspecialchars($syncRunSummary, ENT_QUOTES, "UTF-8"),
			'EBAY_SYNC_RUN_HISTORY_HTML' => $syncRunHistoryHtml,
			'EBAY_SYNC_RUN_DETAILS_HTML' => $syncRunDetailsHtml,
			'EBAY_SYNC_RUN_ID' => (int)$syncRunSelectedId,
			'EBAY_QUICK_ACTIONS_HTML' => $quickActionsHtml,
			'EBAY_MANUAL_ACTIONS_HTML' => $manualActionsHtml,
			'EBAY_RECONCILE_RUN_ID' => (int)$reconcileRunId,
			'EBAY_RECONCILE_SUMMARY' => htmlspecialchars($reconcileSummary, ENT_QUOTES, "UTF-8"),
			'EBAY_RECONCILE_RUNS_HTML' => $reconcileRunsHtml,
			'EBAY_RECONCILE_DETAILS_HTML' => $reconcileDetailsHtml));
	        $template->pparse('body');
		break;
    case "ex":
        if ($_GET["resetoninternet"]==1)
        {
	    $book->resetOnInternet();
	    $msg .= $txt["catalogExclusionReseted"];
        }
        if (isset($_POST["removecatalogoninternet"]))
        {
	    $book->disableCatalogOnInternet($_POST["removecatalogoninternet"]);
	    $msg .= $txt["catalogExcluded"];
        }
	// Creating available catalog (for exclusion), without selected !
	$cat = $catalog->getList();
	$excluded = "";
	$i=0;
	while($i!=(sizeof($cat)))
	{
	    $optionList .= "<OPTION VALUE='" . $cat[$i]["idCatalog"] . "'>" . $cat[$i]["name"] . "</OPTION>";
	    if ($cat[$i]["onInternet"]==0) // We find an excluded catalog
		$excluded .= $cat[$i]["name"] ."<br />";
	    $i++;
	}

        $template->set_filenames(array('body' => 'internet_exclude.tpl'));
        $template->assign_vars(array('INTERNET' => $txt["internet"],
                'EXCLUSION' => $txt["exclusion"], 'RESET_ON_INTERNET_CATALOG' => $txt["resetOnInternetCatalog"], 
                'CHOOSE_CATALOG_TO_DISABLE' => $txt["chooseCatalogToDisable"], 'EXCLUDED_LIST' => $excluded,
                'OPTION_LIST_CATALOG' => $optionList, 'EXCLUDED_CATALOG' => $txt["excludedCatalogList"],
                'CHANGE' => $txt["change"], 'MSG' => $msg));
        $template->pparse('body');
        break;

// ---------------------------------------
//          Prestashop Website
// ---------------------------------------

    case "ws":
        
        echo "<pre>";
        include "lib/prestashop.lib.php";
        
        // If we need to list PS website category
        if ($_GET["listCategories"]!="")
        {
            $msg = "<table border=1><tr><th>Category ID</th><th>Nom</th><th>Nom Anglais</th></tr>";
            $cat = ps_category_list();
            foreach ($cat as $cat_detail)
            {
                $msg.="<tr><td>".$cat_detail["id"]."</td><td>".$cat_detail["name"]."</td><td>".$cat_detail["name_english"]."</td></tr>";
            }
            $msg.="</table>";
        }                

        // If we need to add a category to PS Website
        if ($_GET["addCategory"]!="")
	    {
            if ($category->get($_GET["addCategory"])) // If the category exists ?
            {
                // We add the category on PS Website
                $id = ps_category_add($_GET["addCategory"]);
                if ($id)
                    // Ok
                    $msg = $txt["categoryAddedOnWebsite"].$id;
                else
                    $msg = $txt["errorDuringAddingCategory"].$_GET["addCategory"];		    
            }
            else
                $msg = $txt["unknownIdCategory"];
        }

        // If we need to update a category to PS Website
        if ($_GET["updateCategory"]!="")
	    {
            if ($category->get($_GET["updateCategory"])) // If the category exists ?
            {
                // We update the category on PS Website
                $id = ps_category_update($_GET["updateCategory"]);
                if ($id)
                    // Ok
                    $msg = $txt["categoryUpdatedOnWebsite"].$_GET["updateCategory"];
                else
                    $msg = $txt["errorDuringUpdatedCategory"].$_GET["updateCategory"];		    
            }
            else
                $msg = $txt["unknownIdCategory"];
        }

        // If we need to delete a category to PS Website
        if ($_GET["delCategory"]!="")
	    {
            if ($category->get($_GET["delCategory"])) // If the category exists ?
            {
                // We delete the category on PS Website
                $id = ps_category_del($_GET["delCategory"]);
                if ($id)
                    // Ok
                    $msg = $txt["categoryDeletedOnWebsite"];
                else
                    $msg = $txt["errorDuringDeletingCategory"].$_GET["delCategory"];		    
            }
            else
                $msg = $txt["unknownIdCategory"];
        }

        // If we need to sync all categories to PS Website
        if ($_GET["syncAllCategories"]!="")
	    {
            // We sync all categories on PS Website
            $id = ps_category_sync();
            if ($id)
                // Ok
                $msg = $txt["categorySyncedOnWebsite"];
            else
                $msg = $txt["errorDuringSyncingCategories"];		    
        }

        // If we need to list PS website products
        if ($_GET["listBooks"]!="")
        {
            $msg = "<table border=1><tr><th>Product ID</th><th>Nom</th><th>Description</th></tr>";
            $cat = ps_product_list();
            foreach ($cat as $cat_detail)
            {
                $msg.="<tr><td>".$cat_detail["id"]."</td><td>".$cat_detail["name"]."</td><td>".$cat_detail["description"]."</td></tr>";
            }
            $msg.="</table>";
        }                
        // If we need to add a book to PS Website
        if ($_GET["addBook"]!="")
	    {
            if ($book->get($_GET["addBook"])) // If the book exists ?
            {
                // We add the book on PS Website
                $id = ps_product_add($_GET["addBook"]);
                if ($id)
                    // Ok
                    $msg = $txt["bookAddedOnWebsite"].$id;
                else
                    $msg = $txt["errorDuringAddingbook"].$_GET["addBook"];		    
            }
            else
                $msg = $txt["unknownIdBook"];
        }

        // If we need to update a book to PS Website
        if ($_GET["updateBook"]!="")
	    {
            if ($book->get($_GET["updateBook"])) // If the book exists ?
            {
                // We update the book on PS Website
                $id = ps_product_update($_GET["updateBook"]);
                if ($id)
                    // Ok
                    $msg = $txt["bookUpdatedOnWebsite"].$_GET["updateBook"];
                else
                    $msg = $txt["errorDuringUpdatedBook"].$_GET["updateBook"];		    
            }
            else
                $msg = $txt["unknownIdBook"];
        }

        // If we need to delete a book to PS Website
        if ($_GET["delBook"]!="")
	    {
            if ($book->get($_GET["delBook"])) // If the book exists ?
            {
                // We delete the book on PS Website
                $id = ps_product_del($_GET["delBook"]);
                if ($id)
                    // Ok
                    $msg = $txt["bookDeletedOnWebsite"];
                else
                    $msg = $txt["errorDuringDeletingBook"].$_GET["delBook"];		    
            }
            else
                $msg = $txt["unknownIdBook"];
        }

        // If we need to sync all books to PS Website
        if ($_GET["syncAllBooks"]!="")
	    {
            // We sync all books on PS Website
            $id = ps_product_sync();
            if ($id)
                // Ok
                $msg = $txt["bookSyncedOnWebsite"];
            else
                $msg = $txt["errorDuringSyncingBooks"];		    
        }



        
	// Form to display
        $template->set_filenames(array('body' => 'internet_website.tpl'));
        $template->assign_vars(array('WEBSITE' => $txt["website"],
                'ADD' => $txt["add"], 'LIST_WEBSITE_CATEGORIES' => $txt["listWebsiteCategories"], 
                'UPDATE_WEBSITE_CATEGORIES' => $txt["syncCategoryToWebsite"], 'DEL' => $txt["del"], 'ADD_WEBSITE_CATEGORY_FROM_ID' => $txt["addWebsiteCategoryFromId"], 'DEL_WEBSITE_CATEGORY_FROM_ID' => $txt["delWebsiteCategoryFromId"], 'VIEW' => $txt["view"], 'UPDATE_WEBSITE_CATEGORY_FROM_ID' => $txt["updateWebsiteCategoryFromId"], 'LIST_WEBSITE_BOOKS' => $txt["ListWebsiteBooks"],  'UPDATE_WEBSITE_BOOKS' => $txt["syncBookToWebsite"], 'ADD_WEBSITE_BOOK_FROM_ID' => $txt["addWebsiteBookFromId"], 'DEL_WEBSITE_BOOK_FROM_ID' => $txt["delWebsiteBookFromId"],  'UPDATE_WEBSITE_BOOK_FROM_ID' => $txt["updateWebsiteBookFromId"], 'UPDATE' => $txt["update"], 'GET_WEBSITE_ORDERS' => $txt["getWebsiteOrders"], 'MSG' => $msg));
        $template->pparse('body');
        break;

    default:
        $template->set_filenames(array('body' => 'internet_home.tpl'));
        $template->assign_vars(array('INTERNET' => $txt["internet"], 'EDITIONS_HOME' => ""));
        $template->pparse('body');
        break;
}

?>
