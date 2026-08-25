<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/ebay_legacy_sdk.php';
require_once __DIR__ . '/ebay/EbayProviderInterface.php';
require_once __DIR__ . '/ebay/EbayLegacyProvider.php';
require_once __DIR__ . '/ebay/EbayRestProvider.php';

/**
 * Returns `legacy` or `rest`.
 */
function ebay_get_provider_name()
{
    $provider = strtolower(trim((string) getenv('EBAY_PROVIDER')));
    if ($provider !== 'rest') {
        return 'legacy';
    }
    return 'rest';
}

/**
 * @return EbayProviderInterface
 */
function ebay_get_provider()
{
    static $providerInstance = null;
    static $providerName = null;

    $name = ebay_get_provider_name();
    if ($providerInstance !== null && $providerName === $name) {
        return $providerInstance;
    }

    $providerName = $name;
    if ($name === 'rest') {
        $providerInstance = new EbayRestProvider();
    } else {
        $providerInstance = new EbayLegacyProvider();
    }

    return $providerInstance;
}

function ebay_get_provider_for_group($targetGroupId = null)
{
    $provider = ebay_get_provider();
    if ($provider instanceof EbayRestProvider) {
        $provider->setContextGroupId($targetGroupId);
    }
    return $provider;
}

function ebay_AddItem($params)
{
    $params = (array) $params;
    $idGroup = isset($params['idGroup']) ? (int) $params['idGroup'] : null;
    return ebay_get_provider_for_group($idGroup)->addItem($params);
}

function ebay_ReviseItem($params)
{
    $params = (array) $params;
    $idGroup = isset($params['idGroup']) ? (int) $params['idGroup'] : null;
    return ebay_get_provider_for_group($idGroup)->reviseItem($params);
}

function ebay_EndItem($params)
{
    $params = (array) $params;
    $idGroup = isset($params['idGroup']) ? (int) $params['idGroup'] : null;
    return ebay_get_provider_for_group($idGroup)->endItem($params);
}

function ebay_GetOrders($params)
{
    $params = (array) $params;
    $idGroup = isset($params['idGroup']) ? (int) $params['idGroup'] : null;
    return ebay_get_provider_for_group($idGroup)->getOrders($params);
}

function ebay_GetSellerList($params)
{
    $params = (array) $params;
    $idGroup = isset($params['idGroup']) ? (int) $params['idGroup'] : null;
    return ebay_get_provider_for_group($idGroup)->getSellerList($params);
}

/**
 * OAuth helper for admin UI.
 * Returns array with keys: ok, message.
 */
function ebay_oauth_start($targetGroupId = null)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array(
            'ok' => false,
            'message' => 'OAuth disponible uniquement avec EBAY_PROVIDER=rest.',
        );
    }

    $provider = ebay_get_provider_for_group($targetGroupId);
    if (!($provider instanceof EbayRestProvider)) {
        return array(
            'ok' => false,
            'message' => 'Provider eBay REST indisponible.',
        );
    }

    $state = bin2hex(random_bytes(16));
    $_SESSION['ebay_oauth_state'] = $state;
    if ((int) $targetGroupId > 0) {
        $_SESSION['ebay_oauth_group_id'] = (int) $targetGroupId;
    }

    $preflight = $provider->validateAuthorizationRequest($state);
    if (!empty($preflight['ok']) && $preflight['ok'] !== true) {
        return array(
            'ok' => false,
            'message' => (string) $preflight['message'],
            'url' => isset($preflight['url']) ? (string) $preflight['url'] : '',
        );
    }

    $redirectUrl = isset($preflight['url']) && $preflight['url'] !== ''
        ? (string) $preflight['url']
        : $provider->getAuthorizationUrl($state);
    if (!headers_sent()) {
        header('Location: ' . $redirectUrl);
    } else {
        $safeUrl = htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8');
        echo "<script>window.location.href=" . json_encode($redirectUrl) . ";</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=" . $safeUrl . "' /></noscript>";
    }
    exit;
}

/**
 * OAuth callback helper.
 */
function ebay_oauth_callback($code, $state, $targetGroupId = null)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array(
            'ok' => false,
            'message' => 'OAuth disponible uniquement avec EBAY_PROVIDER=rest.',
        );
    }

    if ((int) $targetGroupId <= 0 && !empty($_SESSION['ebay_oauth_group_id'])) {
        $targetGroupId = (int) $_SESSION['ebay_oauth_group_id'];
    }
    $provider = ebay_get_provider_for_group($targetGroupId);
    if (!($provider instanceof EbayRestProvider)) {
        return array(
            'ok' => false,
            'message' => 'Provider eBay REST indisponible.',
        );
    }

    $expectedState = isset($_SESSION['ebay_oauth_state']) ? (string) $_SESSION['ebay_oauth_state'] : '';
    $receivedState = trim((string) $state);

    if ($expectedState !== '' && $receivedState !== '' && !hash_equals($expectedState, $receivedState)) {
        return array(
            'ok' => false,
            'message' => 'OAuth state invalide. Relancez la connexion.',
        );
    }

    $stateWarning = false;
    if ($expectedState !== '' && $receivedState === '') {
        // Some eBay callback setups do not return state; allow token exchange in admin flow.
        $stateWarning = true;
    }

    try {
        $payload = $provider->exchangeCodeForToken($code, $targetGroupId);
        unset($_SESSION['ebay_oauth_state']);
        unset($_SESSION['ebay_oauth_group_id']);

        return array(
            'ok' => true,
            'message' => $stateWarning
                ? 'OAuth eBay connecté (state absent dans le callback). Refresh token enregistré.'
                : 'OAuth eBay connecté. Refresh token enregistré.',
            'payload' => $payload,
        );
    } catch (Exception $e) {
        return array(
            'ok' => false,
            'message' => 'OAuth callback en erreur: ' . $e->getMessage(),
        );
    }
}

function ebay_oauth_status($targetGroupId = null)
{
    $base = array(
        'provider' => ebay_get_provider_name(),
        'tokenOk' => false,
    );

    if (ebay_get_provider_name() !== 'rest') {
        $base['message'] = 'Provider legacy actif. Passez EBAY_PROVIDER=rest pour OAuth.';
        return $base;
    }

    $provider = ebay_get_provider_for_group($targetGroupId);
    if (!($provider instanceof EbayRestProvider)) {
        $base['message'] = 'Provider REST indisponible.';
        return $base;
    }

    return $provider->getOAuthStatus($targetGroupId);
}

function ebay_oauth_save_refresh_token($refreshToken, $targetGroupId = null)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'message' => 'Provider REST requis pour enregistrer un refresh token.');
    }

    $provider = ebay_get_provider_for_group($targetGroupId);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'message' => 'Provider eBay REST indisponible.');
    }

    try {
        $provider->setRefreshTokenManually($refreshToken, $targetGroupId);
        return array('ok' => true, 'message' => 'Refresh token enregistré.');
    } catch (Exception $e) {
        return array('ok' => false, 'message' => 'Erreur enregistrement refresh token: ' . $e->getMessage());
    }
}

function ebay_oauth_clear_refresh_token($targetGroupId = null)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'message' => 'Provider REST requis pour effacer le refresh token.');
    }

    $provider = ebay_get_provider_for_group($targetGroupId);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'message' => 'Provider eBay REST indisponible.');
    }

    try {
        $provider->clearRefreshToken($targetGroupId);
        return array('ok' => true, 'message' => 'Refresh token effacé.');
    } catch (Exception $e) {
        return array('ok' => false, 'message' => 'Erreur suppression refresh token: ' . $e->getMessage());
    }
}

function ebay_rest_get_setup_snapshot($idGroup = null)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'message' => 'Provider REST requis.', 'data' => array());
    }

    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'message' => 'Provider eBay REST indisponible.', 'data' => array());
    }

    try {
        return array('ok' => true, 'message' => '', 'data' => $provider->getSetupSnapshot($idGroup));
    } catch (Exception $e) {
        return array('ok' => false, 'message' => 'Erreur lecture configuration eBay: ' . $e->getMessage(), 'data' => array());
    }
}

function ebay_rest_save_setup($values, $idGroup = null)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'message' => 'Provider REST requis.', 'data' => array());
    }

    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'message' => 'Provider eBay REST indisponible.', 'data' => array());
    }

    try {
        $data = $provider->saveOfferConfiguration((array) $values, $idGroup);
        return array('ok' => true, 'message' => 'Configuration eBay enregistrée en base.', 'data' => $data);
    } catch (Exception $e) {
        return array('ok' => false, 'message' => 'Erreur sauvegarde configuration eBay: ' . $e->getMessage(), 'data' => array());
    }
}

function ebay_rest_list_setup_assets($idGroup = null)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'message' => 'Provider REST requis.', 'data' => array());
    }

    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'message' => 'Provider eBay REST indisponible.', 'data' => array());
    }

    try {
        $data = $provider->listSetupAssets();
        $msg = '';
        if (!empty($data['errors']) && is_array($data['errors'])) {
            $msg = implode(' | ', $data['errors']);
        }
        return array('ok' => true, 'message' => $msg, 'data' => $data);
    } catch (Exception $e) {
        return array('ok' => false, 'message' => 'Erreur listing eBay: ' . $e->getMessage(), 'data' => array());
    }
}

function ebay_rest_get_opted_programs($idGroup = null)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'message' => 'Provider REST requis.', 'data' => array());
    }

    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'message' => 'Provider eBay REST indisponible.', 'data' => array());
    }

    try {
        $data = $provider->getOptedInPrograms();
        return array('ok' => true, 'message' => '', 'data' => $data);
    } catch (Exception $e) {
        return array('ok' => false, 'message' => 'Erreur lecture programmes eBay: ' . $e->getMessage(), 'data' => array());
    }
}

function ebay_rest_opt_in_business_policies($idGroup = null)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'message' => 'Provider REST requis.', 'data' => array());
    }

    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'message' => 'Provider eBay REST indisponible.', 'data' => array());
    }

    try {
        $data = $provider->optInBusinessPolicies();
        return array('ok' => true, 'message' => 'Opt-in SELLING_POLICY_MANAGEMENT demandé avec succès.', 'data' => $data);
    } catch (Exception $e) {
        return array('ok' => false, 'message' => 'Erreur opt-in Business Policies: ' . $e->getMessage(), 'data' => array());
    }
}

function ebay_rest_create_policy($policyType, $payload, $idGroup = null)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'message' => 'Provider REST requis.', 'data' => array());
    }

    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'message' => 'Provider eBay REST indisponible.', 'data' => array());
    }

    try {
        $data = $provider->createPolicyFromAdmin((string) $policyType, (array) $payload);
        $label = ucfirst((string) $policyType);
        $id = isset($data['policyId']) ? (string) $data['policyId'] : '';
        $message = $label . ' policy créée.';
        if ($id !== '') {
            $message .= ' ID: ' . $id;
        }
        return array('ok' => true, 'message' => $message, 'data' => $data);
    } catch (Exception $e) {
        return array('ok' => false, 'message' => 'Erreur création policy ' . $policyType . ': ' . $e->getMessage(), 'data' => array());
    }
}

function ebay_rest_delete_policy($policyType, $policyId, $idGroup = null)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'message' => 'Provider REST requis.', 'data' => array());
    }

    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'message' => 'Provider eBay REST indisponible.', 'data' => array());
    }

    try {
        $data = $provider->deletePolicyFromAdmin((string) $policyType, (string) $policyId);
        return array('ok' => true, 'message' => 'Policy supprimée: ' . (string) $policyId, 'data' => $data);
    } catch (Exception $e) {
        return array('ok' => false, 'message' => 'Erreur suppression policy ' . $policyType . ': ' . $e->getMessage(), 'data' => array());
    }
}

function ebay_rest_create_location($values, $idGroup = null)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'message' => 'Provider REST requis.', 'data' => array());
    }

    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'message' => 'Provider eBay REST indisponible.', 'data' => array());
    }

    try {
        $data = $provider->createInventoryLocationFromAdmin((array) $values);
        return array('ok' => true, 'message' => 'Location eBay créée et enregistrée: ' . $data['merchantLocationKey'], 'data' => $data);
    } catch (Exception $e) {
        return array('ok' => false, 'message' => 'Erreur création location eBay: ' . $e->getMessage(), 'data' => array());
    }
}

function ebay_rest_get_group_settings($idGroup = null)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'message' => 'Provider REST requis.', 'data' => array());
    }
    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'message' => 'Provider eBay REST indisponible.', 'data' => array());
    }
    try {
        return array('ok' => true, 'message' => '', 'data' => $provider->getGroupSettings($idGroup));
    } catch (Exception $e) {
        return array('ok' => false, 'message' => 'Erreur lecture réglages groupe eBay: ' . $e->getMessage(), 'data' => array());
    }
}

function ebay_rest_save_group_settings($idGroup, $values)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'message' => 'Provider REST requis.', 'data' => array());
    }
    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'message' => 'Provider eBay REST indisponible.', 'data' => array());
    }
    try {
        return array('ok' => true, 'message' => 'Réglages eBay groupe enregistrés.', 'data' => $provider->saveGroupSettings($idGroup, (array) $values));
    } catch (Exception $e) {
        return array('ok' => false, 'message' => 'Erreur sauvegarde réglages groupe eBay: ' . $e->getMessage(), 'data' => array());
    }
}

function ebay_rest_sync_policy_catalog($idGroup = null)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'message' => 'Provider REST requis.', 'data' => array());
    }
    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'message' => 'Provider eBay REST indisponible.', 'data' => array());
    }
    try {
        $data = $provider->syncPolicyCatalog($idGroup);
        return array('ok' => true, 'message' => 'Policies eBay synchronisées.', 'data' => $data);
    } catch (Exception $e) {
        return array('ok' => false, 'message' => 'Erreur synchro policies eBay: ' . $e->getMessage(), 'data' => array());
    }
}

function ebay_rest_sync_setup_catalog($idGroup = null)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'message' => 'Provider REST requis.', 'data' => array());
    }
    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'message' => 'Provider eBay REST indisponible.', 'data' => array());
    }
    try {
        $data = $provider->syncSetupCatalog($idGroup);
        return array('ok' => true, 'message' => 'Policies + Merchant Locations synchronisées.', 'data' => $data);
    } catch (Exception $e) {
        return array('ok' => false, 'message' => 'Erreur synchro setup eBay: ' . $e->getMessage(), 'data' => array());
    }
}

function ebay_rest_get_policy_catalog($idGroup = null, $policyType = '')
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'message' => 'Provider REST requis.', 'data' => array());
    }
    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'message' => 'Provider eBay REST indisponible.', 'data' => array());
    }
    try {
        return array('ok' => true, 'message' => '', 'data' => $provider->getPolicyCatalog($idGroup, $policyType));
    } catch (Exception $e) {
        return array('ok' => false, 'message' => 'Erreur lecture catalogue policies eBay: ' . $e->getMessage(), 'data' => array());
    }
}

function ebay_rest_get_location_catalog($idGroup = null)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'message' => 'Provider REST requis.', 'data' => array());
    }
    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'message' => 'Provider eBay REST indisponible.', 'data' => array());
    }
    try {
        return array('ok' => true, 'message' => '', 'data' => $provider->getLocationCatalog($idGroup));
    } catch (Exception $e) {
        return array('ok' => false, 'message' => 'Erreur lecture catalogue locations eBay: ' . $e->getMessage(), 'data' => array());
    }
}

function ebay_rest_sync_store_categories($idGroup = null)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'message' => 'Provider REST requis.', 'data' => array());
    }
    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'message' => 'Provider eBay REST indisponible.', 'data' => array());
    }
    try {
        return array('ok' => true, 'message' => 'Rubriques boutique eBay synchronisées.', 'data' => $provider->syncStoreCategories($idGroup));
    } catch (Exception $e) {
        return array('ok' => false, 'message' => 'Erreur synchro rubriques boutique eBay: ' . $e->getMessage(), 'data' => array());
    }
}

function ebay_rest_get_store_categories($idGroup = null)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'message' => 'Provider REST requis.', 'data' => array());
    }
    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'message' => 'Provider eBay REST indisponible.', 'data' => array());
    }
    try {
        return array('ok' => true, 'message' => '', 'data' => $provider->getStoreCategoryCatalog($idGroup));
    } catch (Exception $e) {
        return array('ok' => false, 'message' => 'Erreur lecture rubriques boutique eBay: ' . $e->getMessage(), 'data' => array());
    }
}

function ebay_rest_create_store_category($idGroup, $values)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'message' => 'Provider REST requis.', 'data' => array());
    }
    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'message' => 'Provider eBay REST indisponible.', 'data' => array());
    }
    try {
        $data = $provider->createStoreCategoryFromAdmin($idGroup, (array) $values);
        return array('ok' => true, 'message' => 'Rubrique boutique eBay créée.', 'data' => $data);
    } catch (Exception $e) {
        return array('ok' => false, 'message' => 'Erreur création rubrique boutique eBay: ' . $e->getMessage(), 'data' => array());
    }
}

function ebay_rest_rename_store_category($idGroup, $storeCategoryId, $name)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'message' => 'Provider REST requis.', 'data' => array());
    }
    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'message' => 'Provider eBay REST indisponible.', 'data' => array());
    }
    try {
        $data = $provider->renameStoreCategoryFromAdmin($idGroup, $storeCategoryId, $name);
        return array('ok' => true, 'message' => 'Rubrique boutique eBay renommée.', 'data' => $data);
    } catch (Exception $e) {
        return array('ok' => false, 'message' => 'Erreur renommage rubrique boutique eBay: ' . $e->getMessage(), 'data' => array());
    }
}

function ebay_rest_delete_store_category($idGroup, $storeCategoryId)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'message' => 'Provider REST requis.', 'data' => array());
    }
    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'message' => 'Provider eBay REST indisponible.', 'data' => array());
    }
    try {
        $data = $provider->deleteStoreCategoryFromAdmin($idGroup, $storeCategoryId);
        return array('ok' => true, 'message' => 'Rubrique boutique eBay supprimée.', 'data' => $data);
    } catch (Exception $e) {
        return array('ok' => false, 'message' => 'Erreur suppression rubrique boutique eBay: ' . $e->getMessage(), 'data' => array());
    }
}

function ebay_rest_get_category_store_map($idGroup = null)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'message' => 'Provider REST requis.', 'data' => array());
    }
    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'message' => 'Provider eBay REST indisponible.', 'data' => array());
    }
    try {
        return array('ok' => true, 'message' => '', 'data' => $provider->getCategoryStoreMap($idGroup));
    } catch (Exception $e) {
        return array('ok' => false, 'message' => 'Erreur lecture mapping rubriques eBay: ' . $e->getMessage(), 'data' => array());
    }
}

function ebay_rest_save_category_store_map($idGroup, $idCategory, $storeCategoryId)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'message' => 'Provider REST requis.', 'data' => array());
    }
    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'message' => 'Provider eBay REST indisponible.', 'data' => array());
    }
    try {
        $ok = $provider->saveCategoryStoreMap($idGroup, $idCategory, $storeCategoryId);
        return array('ok' => (bool) $ok, 'message' => ($ok ? 'Mapping sauvegardé.' : 'Échec sauvegarde mapping.'), 'data' => array());
    } catch (Exception $e) {
        return array('ok' => false, 'message' => 'Erreur sauvegarde mapping rubriques eBay: ' . $e->getMessage(), 'data' => array());
    }
}

function ebay_rest_run_sync($idGroup = null, $idUser = 0, $triggerSource = 'manual', $batchLimit = 0, $options = array())
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'message' => 'Provider REST requis.', 'data' => array());
    }
    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'message' => 'Provider eBay REST indisponible.', 'data' => array());
    }
    try {
        $opts = is_array($options) ? $options : array();
        $data = $provider->runSyncNow($idGroup, $idUser, $triggerSource, $batchLimit, $opts);
        return array('ok' => true, 'message' => (string) $data['message'], 'data' => $data);
    } catch (Exception $e) {
        return array('ok' => false, 'message' => 'Erreur synchro eBay: ' . $e->getMessage(), 'data' => array());
    }
}

function ebay_rest_list_sync_runs($idGroup = null, $limit = 10)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'message' => 'Provider REST requis.', 'data' => array());
    }
    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'message' => 'Provider eBay REST indisponible.', 'data' => array());
    }
    try {
        return array('ok' => true, 'message' => '', 'data' => $provider->listSyncRuns($idGroup, $limit));
    } catch (Exception $e) {
        return array('ok' => false, 'message' => 'Erreur lecture historique synchro eBay: ' . $e->getMessage(), 'data' => array());
    }
}

function ebay_rest_list_sync_actions($runId, $idGroup = null, $limit = 1000)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'message' => 'Provider REST requis.', 'data' => array());
    }
    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'message' => 'Provider eBay REST indisponible.', 'data' => array());
    }
    try {
        return array('ok' => true, 'message' => '', 'data' => $provider->listSyncActions($runId, $idGroup, $limit));
    } catch (Exception $e) {
        return array('ok' => false, 'message' => 'Erreur lecture détails synchro eBay: ' . $e->getMessage(), 'data' => array());
    }
}

function ebay_rest_delete_sync_run($runId, $idGroup = null)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'message' => 'Provider REST requis.', 'data' => array());
    }
    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'message' => 'Provider eBay REST indisponible.', 'data' => array());
    }
    try {
        $data = $provider->deleteSyncRun($runId, $idGroup);
        if ((int) $data['deletedRuns'] > 0) {
            $msg = "Run de synchro #{$runId} supprimé. Actions supprimées: " . (int) $data['deletedActions'];
        } else {
            $msg = "Run de synchro #{$runId} introuvable ou déjà supprimé.";
        }
        return array('ok' => true, 'message' => $msg, 'data' => $data);
    } catch (Exception $e) {
        return array('ok' => false, 'message' => 'Erreur suppression run de synchro eBay: ' . $e->getMessage(), 'data' => array());
    }
}

function ebay_rest_reconcile_scan($idGroup = null, $idUser = 0, $triggerSource = 'manual', $maxOffers = 10000, $extraOptions = array())
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'code' => 'provider_required', 'message' => 'Provider REST requis.', 'data' => array(), 'stats' => array(), 'errors' => array());
    }
    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'code' => 'provider_unavailable', 'message' => 'Provider eBay REST indisponible.', 'data' => array(), 'stats' => array(), 'errors' => array());
    }
    try {
        $scanOptions = array(
            'triggerSource' => (string) $triggerSource,
            'maxOffers' => (int) $maxOffers,
        );
        if (is_array($extraOptions)) {
            foreach ($extraOptions as $optKey => $optValue) {
                $scanOptions[$optKey] = $optValue;
            }
        }
        $data = $provider->runReconcileScan($idGroup, $idUser, $scanOptions);
        return array('ok' => true, 'code' => 'ok', 'message' => (string) $data['message'], 'data' => $data, 'stats' => (array) $data['stats'], 'errors' => array());
    } catch (Exception $e) {
        return array('ok' => false, 'code' => 'server_error', 'message' => 'Erreur audit eBay: ' . $e->getMessage(), 'data' => array(), 'stats' => array(), 'errors' => array($e->getMessage()));
    }
}

function ebay_rest_list_reconcile_runs($idGroup = null, $limit = 20)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'code' => 'provider_required', 'message' => 'Provider REST requis.', 'data' => array(), 'stats' => array(), 'errors' => array());
    }
    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'code' => 'provider_unavailable', 'message' => 'Provider eBay REST indisponible.', 'data' => array(), 'stats' => array(), 'errors' => array());
    }
    try {
        $rows = $provider->listReconcileRuns($idGroup, $limit);
        return array('ok' => true, 'code' => 'ok', 'message' => '', 'data' => $rows, 'stats' => array('count' => count($rows)), 'errors' => array());
    } catch (Exception $e) {
        return array('ok' => false, 'code' => 'server_error', 'message' => 'Erreur historique audits eBay: ' . $e->getMessage(), 'data' => array(), 'stats' => array(), 'errors' => array($e->getMessage()));
    }
}

function ebay_rest_get_reconcile_items($runId, $idGroup = null)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'code' => 'provider_required', 'message' => 'Provider REST requis.', 'data' => array(), 'stats' => array(), 'errors' => array());
    }
    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'code' => 'provider_unavailable', 'message' => 'Provider eBay REST indisponible.', 'data' => array(), 'stats' => array(), 'errors' => array());
    }
    try {
        $rows = $provider->getReconcileItems($runId, $idGroup);
        return array('ok' => true, 'code' => 'ok', 'message' => '', 'data' => $rows, 'stats' => array('count' => count($rows)), 'errors' => array());
    } catch (Exception $e) {
        return array('ok' => false, 'code' => 'server_error', 'message' => 'Erreur lecture items audit eBay: ' . $e->getMessage(), 'data' => array(), 'stats' => array(), 'errors' => array($e->getMessage()));
    }
}

function ebay_rest_purge_reconcile_runs($idGroup = null, $keepLatest = 10)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'code' => 'provider_required', 'message' => 'Provider REST requis.', 'data' => array(), 'stats' => array(), 'errors' => array());
    }
    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'code' => 'provider_unavailable', 'message' => 'Provider eBay REST indisponible.', 'data' => array(), 'stats' => array(), 'errors' => array());
    }
    try {
        $data = $provider->purgeReconcileRuns($idGroup, $keepLatest);
        $msg = 'Nettoyage audits effectué. Runs supprimés: ' . (int) $data['deletedRuns'] . ' | Items supprimés: ' . (int) $data['deletedItems'] . ' | Runs conservés: ' . (int) $data['keptRuns'];
        return array('ok' => true, 'code' => 'ok', 'message' => $msg, 'data' => $data, 'stats' => $data, 'errors' => array());
    } catch (Exception $e) {
        return array('ok' => false, 'code' => 'server_error', 'message' => 'Erreur purge audits eBay: ' . $e->getMessage(), 'data' => array(), 'stats' => array(), 'errors' => array($e->getMessage()));
    }
}

function ebay_rest_delete_reconcile_run($runId, $idGroup = null)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'code' => 'provider_required', 'message' => 'Provider REST requis.', 'data' => array(), 'stats' => array(), 'errors' => array());
    }
    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'code' => 'provider_unavailable', 'message' => 'Provider eBay REST indisponible.', 'data' => array(), 'stats' => array(), 'errors' => array());
    }
    try {
        $data = $provider->deleteReconcileRun($runId, $idGroup);
        if ((int) $data['deletedRuns'] > 0) {
            $msg = "Audit #{$runId} supprimé. Items supprimés: " . (int) $data['deletedItems'];
        } else {
            $msg = "Audit #{$runId} introuvable ou déjà supprimé.";
        }
        return array('ok' => true, 'code' => 'ok', 'message' => $msg, 'data' => $data, 'stats' => $data, 'errors' => array());
    } catch (Exception $e) {
        return array('ok' => false, 'code' => 'server_error', 'message' => 'Erreur suppression audit eBay: ' . $e->getMessage(), 'data' => array(), 'stats' => array(), 'errors' => array($e->getMessage()));
    }
}

function ebay_rest_apply_reconcile_actions($runId, $selections, $idUser = 0, $idGroup = null)
{
    if (ebay_get_provider_name() !== 'rest') {
        return array('ok' => false, 'code' => 'provider_required', 'message' => 'Provider REST requis.', 'data' => array(), 'stats' => array(), 'errors' => array());
    }
    $provider = ebay_get_provider_for_group($idGroup);
    if (!($provider instanceof EbayRestProvider)) {
        return array('ok' => false, 'code' => 'provider_unavailable', 'message' => 'Provider eBay REST indisponible.', 'data' => array(), 'stats' => array(), 'errors' => array());
    }
    try {
        $data = $provider->applyReconcileActions($runId, (array) $selections, $idUser, $idGroup);
        return array('ok' => true, 'code' => 'ok', 'message' => (string) $data['message'], 'data' => $data, 'stats' => (array) $data['stats'], 'errors' => array());
    } catch (Exception $e) {
        return array('ok' => false, 'code' => 'server_error', 'message' => 'Erreur application corrections eBay: ' . $e->getMessage(), 'data' => array(), 'stats' => array(), 'errors' => array($e->getMessage()));
    }
}
