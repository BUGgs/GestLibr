<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\StreamHandler;
use GuzzleHttp\HandlerStack;

require_once __DIR__ . '/../integration_settings.class.php';

class EbayRestProvider implements EbayProviderInterface
{
    const SANDBOX_API_BASE = 'https://api.sandbox.ebay.com';
    const PRODUCTION_API_BASE = 'https://api.ebay.com';
    const SANDBOX_MEDIA_BASE = 'https://apim.sandbox.ebay.com';
    const PRODUCTION_MEDIA_BASE = 'https://apim.ebay.com';
    const SANDBOX_AUTH_BASE = 'https://auth.sandbox.ebay.com';
    const PRODUCTION_AUTH_BASE = 'https://auth.ebay.com';

    /** @var Client */
    protected $http;

    /** @var DataBase|null */
    protected $db;

    /** @var string */
    protected $accessToken = '';

    /** @var int */
    protected $accessTokenExpiresAt = 0;

    /** @var string */
    protected $directAccessToken = '';

    /** @var array */
    protected $tableExistsCache = array();

    /** @var array */
    protected $taxonomyAspectsCache = array();

    /** @var array */
    protected $lookupNameCache = array();

    /** @var array */
    protected $mediaImageUploadCache = array();

    /** @var int */
    protected $contextGroupId = 0;

    public function __construct()
    {
        $handlerStack = HandlerStack::create(new StreamHandler());
        $this->http = new Client(array(
            'timeout' => 45,
            'connect_timeout' => 10,
            'handler' => $handlerStack,
        ));
        $this->db = null;
    }

    public function setContextGroupId($idGroup)
    {
        $idGroup = (int) $idGroup;
        $this->contextGroupId = ($idGroup > 0) ? $idGroup : 0;
        return $this;
    }

    public function addItem(array $params)
    {
        return $this->upsertListing($params, false);
    }

    public function reviseItem(array $params)
    {
        return $this->upsertListing($params, true);
    }

    public function endItem(array $params)
    {
        $sku = trim((string) $this->arrayValue($params, 'SKU', ''));
        $idBook = (int) $this->arrayValue($params, 'idBook', 0);
        $idGroup = $this->resolveGroupId();

        if ($sku === '' && $idBook > 0) {
            $sku = (string) $idBook;
        }

        if ($sku === '' && !empty($params['ItemID'])) {
            $state = $this->findStateByListingId((string) $params['ItemID']);
            if (!empty($state)) {
                $sku = (string) $state['sku'];
                $idBook = (int) $state['idBook'];
                if (!empty($state['idGroup'])) {
                    $idGroup = (int) $state['idGroup'];
                }
            }
        }

        if ($sku === '') {
            return false;
        }

        if ($idBook <= 0 && ctype_digit($sku)) {
            $idBook = (int) $sku;
        }
        if ($idGroup > 0) {
            $this->setContextGroupId($idGroup);
        }

        try {
            $offer = $this->findOfferBySku($sku);
            if (!empty($offer) && !empty($offer['offerId'])) {
                $this->withdrawOffer((string) $offer['offerId']);
            }

            $this->clearListingState($idGroup, $idBook, $sku);
            return true;
        } catch (Exception $e) {
            $message = $e->getMessage();
            $this->saveErrorState($idGroup, $idBook, $sku, 'WITHDRAW_ERROR', $message);

            if (
                stripos($message, '404') !== false ||
                stripos($message, 'not found') !== false ||
                $this->isOfferUnavailableError($message)
            ) {
                $this->clearListingState($idGroup, $idBook, $sku);
                return true;
            }
            return false;
        }
    }

    public function getOrders(array $params)
    {
        $limit = max(1, min(200, (int) $this->arrayValue($params, 'limit', 50)));
        $offset = max(0, (int) $this->arrayValue($params, 'offset', 0));
        $query = array(
            'limit' => $limit,
            'offset' => $offset,
        );
        $filter = trim((string) $this->arrayValue($params, 'filter', ''));
        if ($filter !== '') {
            $query['filter'] = $filter;
        }
        $orderIds = trim((string) $this->arrayValue($params, 'orderIds', ''));
        if ($orderIds !== '') {
            $query['orderIds'] = $orderIds;
        }

        try {
            $response = $this->requestJson('GET', '/sell/fulfillment/v1/order', array(
                'query' => $query,
            ));

            return $response;
        } catch (Exception $e) {
            return array(
                '__error' => (string) $e->getMessage(),
                'orders' => array(),
                'total' => 0,
                'limit' => $limit,
                'offset' => $offset,
            );
        }
    }

    public function getSellerList(array $params)
    {
        $page = max(1, (int) $this->arrayValue($params, 'PageNumber', 1));
        $limit = max(1, min(200, (int) $this->arrayValue($params, 'EntriesPerPage', 50)));
        $offset = ($page - 1) * $limit;

        $result = (object) array(
            'HasMoreItems' => false,
            'ItemArray' => (object) array('Item' => array()),
            'PaginationResult' => (object) array(
                'TotalNumberOfPages' => 1,
                'TotalNumberOfEntries' => 0,
            ),
        );

        try {
            $payload = $this->requestJson('GET', '/sell/inventory/v1/offer', array(
                'query' => array(
                    'marketplace_id' => $this->getMarketplaceId(),
                    'limit' => $limit,
                    'offset' => $offset,
                ),
            ));

            $offers = array();
            if (!empty($payload['offers']) && is_array($payload['offers'])) {
                $offers = $payload['offers'];
            }

            $items = array();
            foreach ($offers as $offer) {
                $items[] = (object) array(
                    'SKU' => (string) $this->arrayValue($offer, 'sku', ''),
                    'ItemID' => (string) $this->arrayValue($offer, 'listingId', ''),
                    'Title' => (string) $this->arrayValue($offer, 'listingDescription', ''),
                );
            }

            $total = (int) $this->arrayValue($payload, 'total', count($items));
            $result->ItemArray = (object) array('Item' => $items);
            $result->HasMoreItems = ($offset + count($items)) < $total;
            $result->PaginationResult = (object) array(
                'TotalNumberOfPages' => ($limit > 0) ? (int) ceil($total / $limit) : 1,
                'TotalNumberOfEntries' => $total,
            );
            return $result;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getAuthorizationUrl($state)
    {
        $query = http_build_query(array(
            'client_id' => $this->getClientId(),
            'redirect_uri' => $this->getRedirectUri(),
            'response_type' => 'code',
            'scope' => implode(' ', $this->getScopes()),
            'state' => $state,
        ), '', '&', PHP_QUERY_RFC3986);

        return rtrim($this->getAuthBaseUrl(), '/') . '/oauth2/authorize?' . $query;
    }

    public function validateAuthorizationRequest($state = '')
    {
        $state = trim((string) $state);
        if ($state === '') {
            $state = bin2hex(random_bytes(8));
        }

        $url = $this->getAuthorizationUrl($state);
        try {
            $response = $this->http->request('GET', $url, array(
                'allow_redirects' => array(
                    'max' => 5,
                    'strict' => false,
                    'track_redirects' => true,
                ),
                'http_errors' => false,
                'timeout' => 15,
                'connect_timeout' => 8,
                'headers' => array(
                    'Accept' => 'application/json,text/html;q=0.9,*/*;q=0.8',
                ),
            ));
        } catch (Exception $e) {
            return array(
                'ok' => true,
                'message' => '',
                'url' => $url,
            );
        }

        $statusCode = (int) $response->getStatusCode();
        $rawBody = trim((string) $response->getBody());
        $decoded = array();
        if ($rawBody !== '') {
            $decoded = json_decode($rawBody, true);
            if (!is_array($decoded)) {
                $decoded = array();
            }
        }

        if (
            $statusCode >= 400 ||
            !empty($decoded['error']) ||
            !empty($decoded['error_id']) ||
            (!empty($decoded['errors']) && is_array($decoded['errors']))
        ) {
            $message = $this->buildErrorMessage($statusCode, $decoded, $rawBody);
            if (stripos($message, 'invalid_request') !== false) {
                $message .= ' | Vérifiez que EBAY_REDIRECT_URI/EBAY_RUNAME correspond exactement au RuName du keyset '
                    . strtoupper($this->getEbayEnvironment()) . ' (client_id actuel).';
            }
            return array(
                'ok' => false,
                'message' => $message,
                'url' => $url,
            );
        }

        return array(
            'ok' => true,
            'message' => '',
            'url' => $url,
        );
    }

    public function exchangeCodeForToken($code, $idGroup = null)
    {
        if ($idGroup !== null) {
            $this->setContextGroupId($idGroup);
        }
        $code = trim((string) $code);
        if ($code === '') {
            throw new RuntimeException('OAuth code manquant.');
        }

        $payload = $this->requestToken(array(
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->getRedirectUri(),
        ));

        if (!empty($payload['refresh_token'])) {
            $resolvedGroupId = $this->resolveGroupId();
            if ($resolvedGroupId <= 0) {
                throw new RuntimeException('Groupe cible introuvable pour stocker le refresh token OAuth eBay.');
            }
            $this->setEbayGroupSetting($resolvedGroupId, 'refresh_token', (string) $payload['refresh_token'], 1);
        }

        return $payload;
    }

    public function getOAuthStatus($idGroup = null)
    {
        if ($idGroup !== null) {
            $this->setContextGroupId($idGroup);
        }
        $resolvedGroupId = $this->resolveGroupId();
        $refreshToken = $this->getRefreshToken($resolvedGroupId);
        $status = array(
            'provider' => 'rest',
            'environment' => $this->getEbayEnvironment(),
            'clientConfigured' => ($this->getClientId() !== '' && $this->getClientSecret() !== ''),
            'redirectUri' => $this->getRedirectUri(),
            'marketplaceId' => $this->getMarketplaceId($resolvedGroupId),
            'merchantLocationKeyConfigured' => $this->getMerchantLocationKey($resolvedGroupId) !== '',
            'paymentPolicyConfigured' => $this->getPaymentPolicyId($resolvedGroupId) !== '',
            'fulfillmentPolicyConfigured' => $this->getFulfillmentPolicyId($resolvedGroupId) !== '',
            'returnPolicyConfigured' => $this->getReturnPolicyId($resolvedGroupId) !== '',
            'refreshTokenSource' => $this->getRefreshTokenSource($resolvedGroupId),
            'hasRefreshToken' => $refreshToken !== '',
            'tokenOk' => false,
            'tokenError' => '',
            'sellerRegistrationCompleted' => null,
            'businessPoliciesOptedIn' => null,
            'programs' => array(),
            'scopes' => implode(' ', $this->getScopes()),
        );

        if (!$status['clientConfigured']) {
            $status['message'] = 'Client eBay non configuré (EBAY_CLIENT_ID / EBAY_CLIENT_SECRET).';
            return $status;
        }

        if (!$status['hasRefreshToken']) {
            $redirectUri = $this->getRedirectUri();
            if ($redirectUri !== '' && stripos($redirectUri, 'http://') !== 0 && stripos($redirectUri, 'https://') !== 0) {
                $status['message'] = 'Aucun refresh token. Avec un RuName en dev localhost, collez un refresh token manuellement ci-dessous.';
            } else {
                $status['message'] = 'Aucun refresh token. Lancez "Connecter eBay" puis validez le callback OAuth.';
            }
            return $status;
        }

        try {
            $this->refreshAccessToken(true);
            $status['tokenOk'] = true;
        } catch (Exception $e) {
            $directTokenError = '';
            if ($this->isValidDirectAccessToken($refreshToken, $directTokenError)) {
                $this->directAccessToken = $refreshToken;
                $status['tokenOk'] = true;
                $status['message'] = 'Token utilisateur OAuth détecté (mode direct sans renouvellement automatique).';
            } else {
                $status['tokenError'] = $e->getMessage();
                if ($directTokenError !== '') {
                    $status['message'] = 'Le token fourni ne peut pas être utilisé en refresh_token ni en access token direct.';
                }
            }
        }

        if ($status['tokenOk'] === true) {
            try {
                $privilege = $this->requestJson('GET', '/sell/account/v1/privilege', array());
                if (array_key_exists('sellerRegistrationCompleted', $privilege)) {
                    $status['sellerRegistrationCompleted'] = (bool) $privilege['sellerRegistrationCompleted'];
                    if ($status['sellerRegistrationCompleted'] === false) {
                        $status['message'] = 'Compte vendeur eBay (' . $this->getEbayEnvironment() . ') non activé pour cet utilisateur. La publication d\'offres est impossible.';
                    }
                }
            } catch (Exception $e) {
                // Non-bloquant pour le statut token.
            }

            try {
                $programStatus = $this->getOptedInPrograms();
                $status['businessPoliciesOptedIn'] = $programStatus['sellingPolicyManagementOptedIn'];
                $status['programs'] = $programStatus['programs'];
                if ($status['businessPoliciesOptedIn'] === false) {
                    $notice = 'Business Policies non activées (SELLING_POLICY_MANAGEMENT).';
                    if (!empty($status['message'])) {
                        $status['message'] .= ' ' . $notice;
                    } else {
                        $status['message'] = $notice;
                    }
                }
            } catch (Exception $e) {
                // Non-bloquant pour le statut token.
            }
        }

        $offerConfigError = $this->getOfferConfigurationError();
        if ($offerConfigError !== '') {
            if (!empty($status['message'])) {
                $status['message'] .= ' ' . $offerConfigError;
            } else {
                $status['message'] = $offerConfigError;
            }
        }

        return $status;
    }

    public function setRefreshTokenManually($refreshToken, $idGroup = null)
    {
        if ($idGroup !== null) {
            $this->setContextGroupId($idGroup);
        }
        $refreshToken = trim((string) $refreshToken);
        if ($refreshToken === '') {
            throw new RuntimeException('Refresh token vide.');
        }
        $resolvedGroupId = $this->resolveGroupId();
        if ($resolvedGroupId <= 0) {
            throw new RuntimeException('Groupe cible introuvable pour stocker le refresh token OAuth eBay.');
        }
        $this->setEbayGroupSetting($resolvedGroupId, 'refresh_token', $refreshToken, 1);
        $this->accessToken = '';
        $this->accessTokenExpiresAt = 0;
        $this->directAccessToken = '';
        return true;
    }

    public function clearRefreshToken($idGroup = null)
    {
        if ($idGroup !== null) {
            $this->setContextGroupId($idGroup);
        }
        $resolvedGroupId = $this->resolveGroupId();
        if ($resolvedGroupId <= 0) {
            throw new RuntimeException('Groupe cible introuvable pour effacer le refresh token OAuth eBay.');
        }
        $this->setEbayGroupSetting($resolvedGroupId, 'refresh_token', '', 1);
        $this->accessToken = '';
        $this->accessTokenExpiresAt = 0;
        $this->directAccessToken = '';
        return true;
    }

    public function getSetupSnapshot($idGroup = null)
    {
        $idGroup = $this->normalizeGroupId($idGroup);
        $groupSettings = $this->getGroupSettingsRow($idGroup);
        $marketplaceValue = $this->getMarketplaceId($idGroup);
        $merchantLocationValue = $this->resolveMerchantLocationKey($idGroup);
        $paymentPolicyValue = $this->resolveDefaultPaymentPolicyId($idGroup);
        $fulfillmentPolicyValue = $this->resolveDefaultFulfillmentPolicyId($idGroup);
        $returnPolicyValue = $this->resolveDefaultReturnPolicyId($idGroup);

        return array(
            'idGroup' => $idGroup,
            'marketplaceId' => $marketplaceValue,
            'merchantLocationKey' => $merchantLocationValue,
            'fixedCategoryId' => $this->getFixedCategoryId($idGroup),
            'paymentPolicyId' => $paymentPolicyValue,
            'fulfillmentPolicyId' => $fulfillmentPolicyValue,
            'returnPolicyId' => $returnPolicyValue,
            'descriptionHeaderHtml' => (string) $this->arrayValue($groupSettings, 'descriptionHeaderHtml', ''),
            'descriptionFooterHtml' => (string) $this->arrayValue($groupSettings, 'descriptionFooterHtml', ''),
            'marketplaceSource' => $this->getSettingSourceForGroup($groupSettings, 'marketplaceId'),
            'merchantLocationKeySource' => $this->getSettingSourceForGroup($groupSettings, 'merchantLocationKey'),
            'fixedCategorySource' => $this->getFixedCategorySource($idGroup),
            'paymentPolicySource' => $this->getSettingSourceForGroup($groupSettings, 'defaultPaymentPolicyId'),
            'fulfillmentPolicySource' => $this->getSettingSourceForGroup($groupSettings, 'defaultFulfillmentPolicyId'),
            'returnPolicySource' => $this->getSettingSourceForGroup($groupSettings, 'defaultReturnPolicyId'),
            'descriptionHeaderSource' => $this->getSettingSourceForGroup($groupSettings, 'descriptionHeaderHtml'),
            'descriptionFooterSource' => $this->getSettingSourceForGroup($groupSettings, 'descriptionFooterHtml'),
        );
    }

    public function saveOfferConfiguration(array $values, $idGroup = null)
    {
        $idGroup = $this->normalizeGroupId($idGroup);
        if ($idGroup <= 0) {
            throw new RuntimeException('Groupe cible requis pour enregistrer la configuration eBay.');
        }
        $mappedValues = array();

        if (array_key_exists('marketplaceId', $values)) {
            $mappedValues['marketplaceId'] = trim((string) $values['marketplaceId']);
        }
        if (array_key_exists('merchantLocationKey', $values)) {
            $mappedValues['merchantLocationKey'] = trim((string) $values['merchantLocationKey']);
        }
        if (array_key_exists('paymentPolicyId', $values)) {
            $mappedValues['defaultPaymentPolicyId'] = trim((string) $values['paymentPolicyId']);
        }
        if (array_key_exists('fulfillmentPolicyId', $values)) {
            $mappedValues['defaultFulfillmentPolicyId'] = trim((string) $values['fulfillmentPolicyId']);
        }
        if (array_key_exists('returnPolicyId', $values)) {
            $mappedValues['defaultReturnPolicyId'] = trim((string) $values['returnPolicyId']);
        }
        if (array_key_exists('fixedCategoryId', $values)) {
            $fixedCategoryId = trim((string) $values['fixedCategoryId']);
            if ($fixedCategoryId === '') {
                $fixedCategoryId = '29223';
            }
            if (!preg_match('/^\d+$/', $fixedCategoryId)) {
                throw new RuntimeException('ID catégorie eBay fixe invalide.');
            }
            $mappedValues['fixedCategoryId'] = $fixedCategoryId;
        }
        if (array_key_exists('descriptionHeaderHtml', $values)) {
            $mappedValues['descriptionHeaderHtml'] = $this->normalizeTemplateHtml((string) $values['descriptionHeaderHtml']);
        }
        if (array_key_exists('descriptionFooterHtml', $values)) {
            $mappedValues['descriptionFooterHtml'] = $this->normalizeTemplateHtml((string) $values['descriptionFooterHtml']);
        }

        $this->saveGroupSettings($idGroup, $mappedValues);

        return $this->getSetupSnapshot($idGroup);
    }

    public function getOptedInPrograms()
    {
        $result = array(
            'programs' => array(),
            'sellingPolicyManagementOptedIn' => false,
        );

        $payload = $this->requestJson('GET', '/sell/account/v1/program/get_opted_in_programs', array());
        if (!empty($payload['programs']) && is_array($payload['programs'])) {
            foreach ($payload['programs'] as $program) {
                $programType = trim((string) $this->arrayValue((array) $program, 'programType', ''));
                if ($programType === '') {
                    continue;
                }
                $result['programs'][] = $programType;
            }
        }

        $result['programs'] = array_values(array_unique($result['programs']));
        $result['sellingPolicyManagementOptedIn'] = in_array('SELLING_POLICY_MANAGEMENT', $result['programs']);
        return $result;
    }

    public function optInBusinessPolicies()
    {
        try {
            $this->requestJson('POST', '/sell/account/v1/program/opt_in', array(
                'json' => array(
                    'programType' => 'SELLING_POLICY_MANAGEMENT',
                ),
            ));
        } catch (Exception $e) {
            $message = $e->getMessage();
            if (stripos($message, '25803') === false && stripos($message, 'already opted in') === false) {
                throw $e;
            }
        }

        return $this->getOptedInPrograms();
    }

    public function listSetupAssets()
    {
        $marketplaceId = $this->getMarketplaceId();
        $result = array(
            'marketplaceId' => $marketplaceId,
            'programs' => array(),
            'sellingPolicyManagementOptedIn' => null,
            'locations' => array(),
            'storeCategories' => array(),
            'paymentPolicies' => array(),
            'fulfillmentPolicies' => array(),
            'returnPolicies' => array(),
            'errors' => array(),
        );
        $businessPolicyIssueDetected = false;

        try {
            $programStatus = $this->getOptedInPrograms();
            $result['programs'] = $programStatus['programs'];
            $result['sellingPolicyManagementOptedIn'] = $programStatus['sellingPolicyManagementOptedIn'];
            if ($result['sellingPolicyManagementOptedIn'] === false) {
                $businessPolicyIssueDetected = true;
                $result['errors'][] = 'Business Policies non activées pour ce compte. Cliquez sur "Activer Business Policies".';
            }
        } catch (Exception $e) {
            $result['errors'][] = 'Programmes vendeur: ' . $e->getMessage();
        }

        try {
            $payload = $this->requestJson('GET', '/sell/inventory/v1/location', array(
                'query' => array(
                    'limit' => 200,
                    'offset' => 0,
                ),
            ));
            $locations = array();
            if (!empty($payload['locations']) && is_array($payload['locations'])) {
                $locations = $payload['locations'];
            } elseif (!empty($payload['inventoryLocations']) && is_array($payload['inventoryLocations'])) {
                $locations = $payload['inventoryLocations'];
            }

            foreach ($locations as $location) {
                $address = $this->arrayValue((array) $this->arrayValue((array) $location, 'location', array()), 'address', array());
                $result['locations'][] = array(
                    'merchantLocationKey' => (string) $this->arrayValue((array) $location, 'merchantLocationKey', ''),
                    'name' => (string) $this->arrayValue((array) $location, 'name', ''),
                    'city' => (string) $this->arrayValue((array) $address, 'city', ''),
                    'postalCode' => (string) $this->arrayValue((array) $address, 'postalCode', ''),
                    'country' => (string) $this->arrayValue((array) $address, 'country', ''),
                    'status' => (string) $this->arrayValue((array) $location, 'merchantLocationStatus', ''),
                );
            }
        } catch (Exception $e) {
            $result['errors'][] = 'Locations: ' . $e->getMessage();
        }

        try {
            $storePayload = $this->requestJson('GET', '/sell/stores/v1/store/categories', array());
            $storeCategories = $this->extractStoreCategoriesFromPayload($storePayload);
            foreach ($storeCategories as $storeCategory) {
                $result['storeCategories'][] = array(
                    'storeCategoryId' => (string) $this->arrayValue($storeCategory, 'storeCategoryId', ''),
                    'parentStoreCategoryId' => (string) $this->arrayValue($storeCategory, 'parentStoreCategoryId', ''),
                    'name' => (string) $this->arrayValue($storeCategory, 'name', ''),
                    'displayOrder' => (int) $this->arrayValue($storeCategory, 'displayOrder', 0),
                    'level' => (int) $this->arrayValue($storeCategory, 'level', 0),
                    'leaf' => (int) $this->arrayValue($storeCategory, 'leaf', 0),
                );
            }
        } catch (Exception $e) {
            $result['errors'][] = 'Store categories: ' . $e->getMessage();
        }

        if ($result['sellingPolicyManagementOptedIn'] !== false) {
            try {
                $payload = $this->requestJson('GET', '/sell/account/v1/payment_policy', array(
                    'query' => array('marketplace_id' => $marketplaceId),
                ));
                if (!empty($payload['paymentPolicies']) && is_array($payload['paymentPolicies'])) {
                    foreach ($payload['paymentPolicies'] as $policy) {
                        $result['paymentPolicies'][] = array(
                            'paymentPolicyId' => (string) $this->arrayValue((array) $policy, 'paymentPolicyId', ''),
                            'name' => (string) $this->arrayValue((array) $policy, 'name', ''),
                            'description' => (string) $this->arrayValue((array) $policy, 'description', ''),
                        );
                    }
                }
            } catch (Exception $e) {
                if ($this->isBusinessPolicyEligibilityError($e->getMessage())) {
                    $businessPolicyIssueDetected = true;
                } else {
                    $result['errors'][] = 'Payment policies: ' . $e->getMessage();
                }
            }

            try {
                $payload = $this->requestJson('GET', '/sell/account/v1/fulfillment_policy', array(
                    'query' => array('marketplace_id' => $marketplaceId),
                ));
                if (!empty($payload['fulfillmentPolicies']) && is_array($payload['fulfillmentPolicies'])) {
                    foreach ($payload['fulfillmentPolicies'] as $policy) {
                        $result['fulfillmentPolicies'][] = array(
                            'fulfillmentPolicyId' => (string) $this->arrayValue((array) $policy, 'fulfillmentPolicyId', ''),
                            'name' => (string) $this->arrayValue((array) $policy, 'name', ''),
                            'description' => (string) $this->arrayValue((array) $policy, 'description', ''),
                        );
                    }
                }
            } catch (Exception $e) {
                if ($this->isBusinessPolicyEligibilityError($e->getMessage())) {
                    $businessPolicyIssueDetected = true;
                } else {
                    $result['errors'][] = 'Fulfillment policies: ' . $e->getMessage();
                }
            }

            try {
                $payload = $this->requestJson('GET', '/sell/account/v1/return_policy', array(
                    'query' => array('marketplace_id' => $marketplaceId),
                ));
                if (!empty($payload['returnPolicies']) && is_array($payload['returnPolicies'])) {
                    foreach ($payload['returnPolicies'] as $policy) {
                        $result['returnPolicies'][] = array(
                            'returnPolicyId' => (string) $this->arrayValue((array) $policy, 'returnPolicyId', ''),
                            'name' => (string) $this->arrayValue((array) $policy, 'name', ''),
                            'description' => (string) $this->arrayValue((array) $policy, 'description', ''),
                        );
                    }
                }
            } catch (Exception $e) {
                if ($this->isBusinessPolicyEligibilityError($e->getMessage())) {
                    $businessPolicyIssueDetected = true;
                } else {
                    $result['errors'][] = 'Return policies: ' . $e->getMessage();
                }
            }
        }

        if ($businessPolicyIssueDetected) {
            $result['errors'][] = 'Business Policies indisponibles pour ce compte. Activez SELLING_POLICY_MANAGEMENT puis attendez la propagation eBay.';
        }

        return $result;
    }

    public function createInventoryLocationFromAdmin(array $values)
    {
        $merchantLocationKey = strtoupper(trim((string) $this->arrayValue($values, 'merchantLocationKey', '')));
        if ($merchantLocationKey === '') {
            throw new RuntimeException('merchantLocationKey obligatoire.');
        }

        $name = trim((string) $this->arrayValue($values, 'name', $merchantLocationKey));
        if ($name === '') {
            $name = $merchantLocationKey;
        }

        $addressLine1 = trim((string) $this->arrayValue($values, 'addressLine1', 'Adresse'));
        $city = trim((string) $this->arrayValue($values, 'city', 'Paris'));
        $postalCode = trim((string) $this->arrayValue($values, 'postalCode', '75001'));
        $country = strtoupper(trim((string) $this->arrayValue($values, 'country', 'FR')));

        $payload = array(
            'name' => $name,
            'merchantLocationStatus' => 'ENABLED',
            'locationTypes' => array('WAREHOUSE'),
            'location' => array(
                'address' => array(
                    'addressLine1' => $addressLine1,
                    'city' => $city,
                    'postalCode' => $postalCode,
                    'country' => $country,
                ),
            ),
        );

        $this->requestJson('POST', '/sell/inventory/v1/location/' . rawurlencode($merchantLocationKey), array(
            'json' => $payload,
            'headers' => array(
                'Content-Language' => 'fr-FR',
            ),
        ));

        $idGroup = $this->resolveGroupId();
        if ($idGroup > 0) {
            $this->saveGroupSettings($idGroup, array('merchantLocationKey' => $merchantLocationKey));
        }
        return array(
            'merchantLocationKey' => $merchantLocationKey,
            'name' => $name,
        );
    }

    public function createPolicyFromAdmin($policyType, array $payload)
    {
        $policyType = strtolower(trim((string) $policyType));
        $map = $this->resolvePolicyMap($policyType);

        if (empty($map)) {
            throw new RuntimeException('Type de policy non supporté: ' . $policyType);
        }

        if (!isset($payload['name']) || trim((string) $payload['name']) === '') {
            $payload['name'] = 'GestLibr ' . ucfirst($policyType) . ' ' . date('Ymd-His');
        }
        if (!isset($payload['marketplaceId']) || trim((string) $payload['marketplaceId']) === '') {
            $payload['marketplaceId'] = $this->getMarketplaceId();
        }
        if (empty($payload['categoryTypes']) || !is_array($payload['categoryTypes'])) {
            $payload['categoryTypes'] = array(
                array('name' => 'ALL_EXCLUDING_MOTORS_VEHICLES'),
            );
        }

        $response = $this->requestJson('POST', $map['collectionPath'], array(
            'json' => $payload,
            'headers' => array(
                'Content-Language' => 'fr-FR',
            ),
        ));

        $policyId = (string) $this->arrayValue($response, $map['idField'], '');
        return array(
            'policyType' => $policyType,
            'policyId' => $policyId,
            'response' => $response,
        );
    }

    public function deletePolicyFromAdmin($policyType, $policyId)
    {
        $policyType = strtolower(trim((string) $policyType));
        $policyId = trim((string) $policyId);
        $map = $this->resolvePolicyMap($policyType);

        if (empty($map)) {
            throw new RuntimeException('Type de policy non supporté: ' . $policyType);
        }
        if ($policyId === '') {
            throw new RuntimeException('ID policy manquant.');
        }

        $this->requestJson('DELETE', $map['collectionPath'] . '/' . rawurlencode($policyId), array());
        return array(
            'policyType' => $policyType,
            'policyId' => $policyId,
        );
    }

    public function getGroupSettings($idGroup = null)
    {
        $idGroup = $this->normalizeGroupId($idGroup);
        return $this->getGroupSettingsRow($idGroup);
    }

    public function saveGroupSettings($idGroup, array $values)
    {
        $idGroup = $this->normalizeGroupId($idGroup);
        if ($idGroup <= 0) {
            return $this->getGroupSettings($idGroup);
        }

        $current = $this->getGroupSettingsRow($idGroup);
        $allowed = array(
            'marketplaceId' => 'marketplace_id',
            'merchantLocationKey' => 'merchant_location_key',
            'defaultPaymentPolicyId' => 'default_payment_policy_id',
            'defaultFulfillmentPolicyId' => 'default_fulfillment_policy_id',
            'defaultReturnPolicyId' => 'default_return_policy_id',
            'fixedCategoryId' => 'fixed_category_id',
            'descriptionHeaderHtml' => 'description_header_html',
            'descriptionFooterHtml' => 'description_footer_html',
        );

        foreach ($allowed as $fieldName => $settingKey) {
            if (array_key_exists($fieldName, $values)) {
                if ($fieldName === 'descriptionHeaderHtml' || $fieldName === 'descriptionFooterHtml') {
                    $current[$fieldName] = (string) $values[$fieldName];
                } else {
                    $current[$fieldName] = trim((string) $values[$fieldName]);
                }
            }
        }

        foreach ($allowed as $fieldName => $settingKey) {
            if (!array_key_exists($fieldName, $values)) {
                continue;
            }
            $this->setEbayGroupSetting(
                $idGroup,
                $settingKey,
                (string) $current[$fieldName],
                0
            );
        }

        return $this->getGroupSettingsRow($idGroup);
    }

    protected function saveLegacyGroupSettingsTable($idGroup, array $values)
    {
        $idGroup = (int) $idGroup;
        if ($idGroup <= 0 || !$this->tableExists('ebay_group_settings')) {
            return;
        }

        $current = $this->getLegacyEbayGroupSettingsRow($idGroup);
        $allowed = array(
            'marketplaceId',
            'merchantLocationKey',
            'defaultPaymentPolicyId',
            'defaultFulfillmentPolicyId',
            'defaultReturnPolicyId',
        );

        foreach ($allowed as $key) {
            if (array_key_exists($key, $values)) {
                $current[$key] = trim((string) $values[$key]);
            }
        }

        $now = time();
        $this->dbQuery(
            "INSERT INTO `ebay_group_settings` (`idGroup`, `marketplaceId`, `merchantLocationKey`, `defaultPaymentPolicyId`, `defaultFulfillmentPolicyId`, `defaultReturnPolicyId`, `updatedAtTs`) VALUES "
            . "('" . (int) $idGroup . "', '"
            . $this->escapeSql((string) $current['marketplaceId']) . "', '"
            . $this->escapeSql((string) $current['merchantLocationKey']) . "', '"
            . $this->escapeSql((string) $current['defaultPaymentPolicyId']) . "', '"
            . $this->escapeSql((string) $current['defaultFulfillmentPolicyId']) . "', '"
            . $this->escapeSql((string) $current['defaultReturnPolicyId']) . "', '"
            . (int) $now . "') "
            . "ON DUPLICATE KEY UPDATE `marketplaceId`=VALUES(`marketplaceId`), `merchantLocationKey`=VALUES(`merchantLocationKey`), `defaultPaymentPolicyId`=VALUES(`defaultPaymentPolicyId`), `defaultFulfillmentPolicyId`=VALUES(`defaultFulfillmentPolicyId`), `defaultReturnPolicyId`=VALUES(`defaultReturnPolicyId`), `updatedAtTs`=VALUES(`updatedAtTs`)"
        );
    }

    public function syncPolicyCatalog($idGroup = null)
    {
        $idGroup = $this->normalizeGroupId($idGroup);
        $marketplaceId = $this->getMarketplaceId($idGroup);
        $syncedAtTs = time();

        $types = array(
            'payment' => array(
                'path' => '/sell/account/v1/payment_policy',
                'arrayKey' => 'paymentPolicies',
                'idField' => 'paymentPolicyId',
            ),
            'fulfillment' => array(
                'path' => '/sell/account/v1/fulfillment_policy',
                'arrayKey' => 'fulfillmentPolicies',
                'idField' => 'fulfillmentPolicyId',
            ),
            'return' => array(
                'path' => '/sell/account/v1/return_policy',
                'arrayKey' => 'returnPolicies',
                'idField' => 'returnPolicyId',
            ),
        );

        $result = array(
            'idGroup' => $idGroup,
            'marketplaceId' => $marketplaceId,
            'payment' => array(),
            'fulfillment' => array(),
            'return' => array(),
        );

        foreach ($types as $type => $meta) {
            $payload = $this->requestJson('GET', $meta['path'], array(
                'query' => array('marketplace_id' => $marketplaceId),
            ));
            $rows = array();
            if (!empty($payload[$meta['arrayKey']]) && is_array($payload[$meta['arrayKey']])) {
                $rows = $payload[$meta['arrayKey']];
            }

            $normalizedRows = array();
            $seenPolicyIds = array();
            foreach ($rows as $policyRow) {
                $policyRow = (array) $policyRow;
                $policyId = trim((string) $this->arrayValue($policyRow, $meta['idField'], ''));
                if ($policyId === '') {
                    continue;
                }

                $normalized = array(
                    'policyType' => $type,
                    'policyId' => $policyId,
                    'name' => trim((string) $this->arrayValue($policyRow, 'name', '')),
                    'description' => trim((string) $this->arrayValue($policyRow, 'description', '')),
                    'marketplaceId' => trim((string) $this->arrayValue($policyRow, 'marketplaceId', $marketplaceId)),
                );
                $normalizedRows[] = $normalized;
                $seenPolicyIds[] = $policyId;

                if ($this->tableExists('ebay_policy_catalog')) {
                    $this->dbQuery(
                        "INSERT INTO `ebay_policy_catalog` (`idGroup`, `policyType`, `policyId`, `name`, `description`, `marketplaceId`, `rawJson`, `syncedAtTs`) VALUES "
                        . "('" . (int) $idGroup . "', '"
                        . $this->escapeSql($type) . "', '"
                        . $this->escapeSql($policyId) . "', '"
                        . $this->escapeSql($normalized['name']) . "', '"
                        . $this->escapeSql($normalized['description']) . "', '"
                        . $this->escapeSql($normalized['marketplaceId']) . "', '"
                        . $this->escapeSql(json_encode($policyRow)) . "', '"
                        . (int) $syncedAtTs . "') "
                        . "ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `description`=VALUES(`description`), `marketplaceId`=VALUES(`marketplaceId`), `rawJson`=VALUES(`rawJson`), `syncedAtTs`=VALUES(`syncedAtTs`)"
                    );
                }
            }

            if ($this->tableExists('ebay_policy_catalog')) {
                if (empty($seenPolicyIds)) {
                    $this->dbQuery(
                        "DELETE FROM `ebay_policy_catalog` WHERE `idGroup`='" . (int) $idGroup . "' AND `policyType`='" . $this->escapeSql($type) . "'"
                    );
                } else {
                    $this->dbQuery(
                        "DELETE FROM `ebay_policy_catalog` WHERE `idGroup`='" . (int) $idGroup . "' AND `policyType`='" . $this->escapeSql($type) . "' AND `policyId` NOT IN (" . $this->buildSqlStringList($seenPolicyIds) . ")"
                    );
                }
            }

            $result[$type] = $normalizedRows;
        }

        return $result;
    }

    public function syncLocationCatalog($idGroup = null)
    {
        $idGroup = $this->normalizeGroupId($idGroup);
        $syncedAtTs = time();
        $locations = array();

        $payload = $this->requestJson('GET', '/sell/inventory/v1/location', array(
            'query' => array(
                'limit' => 200,
                'offset' => 0,
            ),
        ));

        $rows = array();
        if (!empty($payload['locations']) && is_array($payload['locations'])) {
            $rows = $payload['locations'];
        } elseif (!empty($payload['inventoryLocations']) && is_array($payload['inventoryLocations'])) {
            $rows = $payload['inventoryLocations'];
        }

        foreach ($rows as $location) {
            $location = (array) $location;
            $address = $this->arrayValue((array) $this->arrayValue($location, 'location', array()), 'address', array());
            $merchantLocationKey = trim((string) $this->arrayValue($location, 'merchantLocationKey', ''));
            if ($merchantLocationKey === '') {
                continue;
            }

            $normalized = array(
                'merchantLocationKey' => $merchantLocationKey,
                'name' => trim((string) $this->arrayValue($location, 'name', '')),
                'city' => trim((string) $this->arrayValue((array) $address, 'city', '')),
                'postalCode' => trim((string) $this->arrayValue((array) $address, 'postalCode', '')),
                'country' => trim((string) $this->arrayValue((array) $address, 'country', '')),
                'status' => trim((string) $this->arrayValue($location, 'merchantLocationStatus', '')),
                'raw' => $location,
            );
            $locations[] = $normalized;
        }

        if ($this->tableExists('ebay_location_catalog')) {
            $this->dbQuery("DELETE FROM `ebay_location_catalog` WHERE `idGroup`='" . (int) $idGroup . "'");
            foreach ($locations as $row) {
                $this->dbQuery(
                    "INSERT INTO `ebay_location_catalog` (`idGroup`, `merchantLocationKey`, `name`, `city`, `postalCode`, `country`, `status`, `rawJson`, `syncedAtTs`) VALUES "
                    . "('" . (int) $idGroup . "', '"
                    . $this->escapeSql((string) $row['merchantLocationKey']) . "', '"
                    . $this->escapeSql((string) $row['name']) . "', '"
                    . $this->escapeSql((string) $row['city']) . "', '"
                    . $this->escapeSql((string) $row['postalCode']) . "', '"
                    . $this->escapeSql((string) $row['country']) . "', '"
                    . $this->escapeSql((string) $row['status']) . "', '"
                    . $this->escapeSql(json_encode($row['raw'])) . "', '"
                    . (int) $syncedAtTs . "')"
                );
            }
        }

        return $locations;
    }

    public function getLocationCatalog($idGroup = null)
    {
        $idGroup = $this->normalizeGroupId($idGroup);
        if (!$this->tableExists('ebay_location_catalog')) {
            return array();
        }

        return $this->dbSelect(
            "SELECT `merchantLocationKey`, `name`, `city`, `postalCode`, `country`, `status`, `syncedAtTs` FROM `ebay_location_catalog` WHERE `idGroup`='" . (int) $idGroup . "' ORDER BY `name` ASC, `merchantLocationKey` ASC"
        );
    }

    public function syncSetupCatalog($idGroup = null)
    {
        $idGroup = $this->normalizeGroupId($idGroup);
        return array(
            'idGroup' => $idGroup,
            'marketplaceId' => $this->getMarketplaceId($idGroup),
            'policies' => $this->syncPolicyCatalog($idGroup),
            'locations' => $this->syncLocationCatalog($idGroup),
        );
    }

    public function getPolicyCatalog($idGroup = null, $policyType = '')
    {
        $idGroup = $this->normalizeGroupId($idGroup);
        if (!$this->tableExists('ebay_policy_catalog')) {
            return array();
        }

        $sql = "SELECT `policyType`, `policyId`, `name`, `description`, `marketplaceId`, `syncedAtTs` FROM `ebay_policy_catalog` WHERE `idGroup`='" . (int) $idGroup . "'";
        $policyType = trim((string) $policyType);
        if ($policyType !== '') {
            $sql .= " AND `policyType`='" . $this->escapeSql($policyType) . "'";
        }
        $sql .= " ORDER BY `policyType` ASC, `name` ASC, `policyId` ASC";

        return $this->dbSelect($sql);
    }

    public function syncStoreCategories($idGroup = null)
    {
        $idGroup = $this->normalizeGroupId($idGroup);
        $payload = $this->requestJson('GET', '/sell/stores/v1/store/categories', array());
        $flatCategories = $this->extractStoreCategoriesFromPayload($payload);
        $syncedAtTs = time();

        if ($this->tableExists('ebay_store_category')) {
            $this->dbQuery("DELETE FROM `ebay_store_category` WHERE `idGroup`='" . (int) $idGroup . "'");
            foreach ($flatCategories as $row) {
                $this->dbQuery(
                    "INSERT INTO `ebay_store_category` (`idGroup`, `storeCategoryId`, `parentStoreCategoryId`, `name`, `displayOrder`, `level`, `leaf`, `rawJson`, `syncedAtTs`) VALUES "
                    . "('" . (int) $idGroup . "', '"
                    . $this->escapeSql((string) $row['storeCategoryId']) . "', '"
                    . $this->escapeSql((string) $row['parentStoreCategoryId']) . "', '"
                    . $this->escapeSql((string) $row['name']) . "', "
                    . "'" . (int) $row['displayOrder'] . "', "
                    . "'" . (int) $row['level'] . "', "
                    . "'" . (int) $row['leaf'] . "', '"
                    . $this->escapeSql(json_encode($row['raw'])) . "', '"
                    . (int) $syncedAtTs . "') "
                );
            }
        }

        return $flatCategories;
    }

    public function getStoreCategoryCatalog($idGroup = null)
    {
        $idGroup = $this->normalizeGroupId($idGroup);
        if (!$this->tableExists('ebay_store_category')) {
            return array();
        }
        return $this->dbSelect(
            "SELECT `storeCategoryId`, `parentStoreCategoryId`, `name`, `displayOrder`, `level`, `leaf`, `syncedAtTs` FROM `ebay_store_category` WHERE `idGroup`='" . (int) $idGroup . "' ORDER BY `level` ASC, `displayOrder` ASC, `name` ASC"
        );
    }

    public function createStoreCategoryFromAdmin($idGroup, array $values)
    {
        $idGroup = $this->normalizeGroupId($idGroup);
        $name = trim((string) $this->arrayValue($values, 'name', ''));
        if ($name === '') {
            throw new RuntimeException('Nom rubrique boutique eBay obligatoire.');
        }

        $payload = array(
            'categoryName' => $name,
        );
        $parentStoreCategoryId = trim((string) $this->arrayValue($values, 'parentStoreCategoryId', ''));
        if ($parentStoreCategoryId !== '') {
            $payload['destinationParentCategoryId'] = $parentStoreCategoryId;
        }

        $response = $this->requestJson('POST', '/sell/stores/v1/store/categories', array(
            'json' => $payload,
            'headers' => array('Content-Language' => 'fr-FR'),
        ));
        $this->syncStoreCategories($idGroup);
        return $response;
    }

    public function renameStoreCategoryFromAdmin($idGroup, $storeCategoryId, $name)
    {
        $idGroup = $this->normalizeGroupId($idGroup);
        $storeCategoryId = trim((string) $storeCategoryId);
        $name = trim((string) $name);
        if ($storeCategoryId === '' || $name === '') {
            throw new RuntimeException('ID et nom de rubrique boutique eBay obligatoires.');
        }

        $response = $this->requestJson('PUT', '/sell/stores/v1/store/categories/' . rawurlencode($storeCategoryId), array(
            'json' => array('categoryName' => $name),
            'headers' => array('Content-Language' => 'fr-FR'),
        ));
        $this->syncStoreCategories($idGroup);
        return $response;
    }

    public function deleteStoreCategoryFromAdmin($idGroup, $storeCategoryId)
    {
        $idGroup = $this->normalizeGroupId($idGroup);
        $storeCategoryId = trim((string) $storeCategoryId);
        if ($storeCategoryId === '') {
            throw new RuntimeException('ID rubrique boutique eBay obligatoire.');
        }

        $response = $this->requestJson('DELETE', '/sell/stores/v1/store/categories/' . rawurlencode($storeCategoryId), array());
        if ($this->tableExists('ebay_category_store_map')) {
            $this->dbQuery(
                "DELETE FROM `ebay_category_store_map` WHERE `idGroup`='" . (int) $idGroup . "' AND `storeCategoryId`='" . $this->escapeSql($storeCategoryId) . "'"
            );
        }
        $this->syncStoreCategories($idGroup);
        return $response;
    }

    public function getCategoryStoreMap($idGroup = null)
    {
        $idGroup = $this->normalizeGroupId($idGroup);
        if (!$this->tableExists('ebay_category_store_map')) {
            return array();
        }

        return $this->dbSelect(
            "SELECT map.`idCategory`, map.`storeCategoryId`, map.`updatedAtTs`, cat.`name` AS `categoryName`, store.`name` AS `storeCategoryName` "
            . "FROM `ebay_category_store_map` AS map "
            . "LEFT JOIN `category` AS cat ON cat.`idCategory`=map.`idCategory` AND cat.`idGroup`=map.`idGroup` "
            . "LEFT JOIN `ebay_store_category` AS store ON store.`storeCategoryId`=map.`storeCategoryId` AND store.`idGroup`=map.`idGroup` "
            . "WHERE map.`idGroup`='" . (int) $idGroup . "' "
            . "ORDER BY cat.`name` ASC, map.`idCategory` ASC"
        );
    }

    public function saveCategoryStoreMap($idGroup, $idCategory, $storeCategoryId)
    {
        $idGroup = $this->normalizeGroupId($idGroup);
        $idCategory = (int) $idCategory;
        $storeCategoryId = trim((string) $storeCategoryId);
        $now = time();

        if (!$this->tableExists('ebay_category_store_map')) {
            return false;
        }
        if ($idGroup <= 0 || $idCategory <= 0) {
            return false;
        }

        if ($storeCategoryId === '') {
            $this->dbQuery(
                "DELETE FROM `ebay_category_store_map` WHERE `idGroup`='" . (int) $idGroup . "' AND `idCategory`='" . (int) $idCategory . "'"
            );
            return true;
        }

        $this->dbQuery(
            "INSERT INTO `ebay_category_store_map` (`idGroup`, `idCategory`, `storeCategoryId`, `updatedAtTs`) VALUES ('"
            . (int) $idGroup . "', '" . (int) $idCategory . "', '" . $this->escapeSql($storeCategoryId) . "', '" . (int) $now . "') "
            . "ON DUPLICATE KEY UPDATE `storeCategoryId`=VALUES(`storeCategoryId`), `updatedAtTs`=VALUES(`updatedAtTs`)"
        );
        return true;
    }

    public function runSyncNow($idGroup = null, $idUser = 0, $triggerSource = 'manual', $batchLimit = 0, array $options = array())
    {
        $idGroup = $this->normalizeGroupId($idGroup);
        $idUser = (int) $idUser;
        $triggerSource = trim((string) $triggerSource);
        if ($triggerSource === '') {
            $triggerSource = 'manual';
        }

        $configuredLimit = (int) config('ebay_max_book_per_batch');
        if ($configuredLimit <= 0) {
            $configuredLimit = 50;
        }
        $batchLimit = (int) $batchLimit;
        if ($batchLimit <= 0) {
            $batchLimit = $configuredLimit;
        }

        $dryRun = ((int) $this->arrayValue($options, 'dryRun', 0) === 1);
        $validPriceCondition = $this->sqlValidListingPriceCondition();
        $stats = array(
            'ordersFetched' => 0,
            'ordersUpsert' => 0,
            'orderItemsUpsert' => 0,
            'stockAdjustedLines' => 0,
            'stockAdjustedUnits' => 0,
            'added' => 0,
            'updated' => 0,
            'deleted' => 0,
            'skippedNoPrice' => 0,
            'errors' => 0,
            'batchLimit' => $batchLimit,
            'isDryRun' => ($dryRun ? 1 : 0),
        );
        $details = array(
            'errors' => array(),
            'orderSync' => array(),
        );

        if (!$this->acquireSyncLock($idGroup)) {
            throw new RuntimeException('Une synchronisation eBay est déjà en cours pour ce groupe.');
        }

        $runId = 0;
        $remaining = $batchLimit;
        $nowTs = time();
        $status = 'running';
        $message = '';

        try {
            $runId = $this->startSyncRun($idGroup, $idUser, $triggerSource, $batchLimit, ($dryRun ? 1 : 0));
            try {
            require_once __DIR__ . '/../order.class.php';
            $order = new Order();
            $orderSyncResult = $order->syncEbayOrders($idGroup, array(
                'limit' => 100,
                'maxPages' => 500,
                'dryRun' => ($dryRun ? 1 : 0),
            ));

            $orderSyncStats = isset($orderSyncResult['stats']) && is_array($orderSyncResult['stats']) ? $orderSyncResult['stats'] : array();
            $stats['ordersFetched'] = (int) $this->arrayValue($orderSyncStats, 'fetchedOrders', 0);
            $stats['ordersUpsert'] = (int) $this->arrayValue($orderSyncStats, 'upsertOrders', 0);
            $stats['orderItemsUpsert'] = (int) $this->arrayValue($orderSyncStats, 'upsertItems', 0);
            $details['orderSync'] = $orderSyncResult;

            $this->logSyncAction($runId, $idGroup, 'order_sync', array(
                'isDryRun' => ($dryRun ? 1 : 0),
                'status' => ($orderSyncResult['ok'] ? 'ok' : 'error'),
                'message' => (string) $this->arrayValue($orderSyncResult, 'message', ''),
                'details' => $orderSyncResult,
            ));

            if (!$orderSyncResult['ok']) {
                $stats['errors']++;
                $details['errors'][] = array(
                    'action' => 'order_sync',
                    'message' => (string) $this->arrayValue($orderSyncResult, 'message', 'Erreur synchro commandes eBay.'),
                );
            }

            if ($this->columnExists('ebay_order_item', 'stockAppliedQty') && $this->columnExists('ebay_order_item', 'stockAppliedAtTs')) {
                if ($dryRun) {
                    $validatedLines = isset($orderSyncResult['validatedLines']) && is_array($orderSyncResult['validatedLines'])
                        ? $orderSyncResult['validatedLines']
                        : array();
                    $pendingLines = $this->buildPendingStockAdjustmentsFromValidatedLines($idGroup, $validatedLines);
                } else {
                    $pendingLines = $this->getPendingStockAdjustments($idGroup);
                }

                foreach ($pendingLines as $line) {
                    $idBook = (int) $this->arrayValue($line, 'idBook', 0);
                    $lineItemId = (string) $this->arrayValue($line, 'lineItemId', '');
                    $ebayOrderId = (string) $this->arrayValue($line, 'ebayOrderId', '');
                    $deltaQty = (int) $this->arrayValue($line, 'deltaQty', 0);
                    if ($idBook <= 0 || $deltaQty <= 0 || $lineItemId === '') {
                        continue;
                    }

                    $bookRows = $this->dbSelect(
                        "SELECT `idBook`,`quantity`,`ebayItemId`,`ebayLastSync` FROM `book` WHERE `idGroup`='" . (int) $idGroup . "' AND `idBook`='" . (int) $idBook . "' LIMIT 1"
                    );
                    if (empty($bookRows[0])) {
                        $stats['errors']++;
                        $errorMessage = 'idBook introuvable pour appliquer la vente validée.';
                        $details['errors'][] = array(
                            'idBook' => $idBook,
                            'lineItemId' => $lineItemId,
                            'action' => 'stock_decrement',
                            'message' => $errorMessage,
                        );
                        $this->logSyncAction($runId, $idGroup, 'stock_decrement', array(
                            'isDryRun' => ($dryRun ? 1 : 0),
                            'idBook' => $idBook,
                            'ebayOrderId' => $ebayOrderId,
                            'lineItemId' => $lineItemId,
                            'qtyDelta' => $deltaQty,
                            'status' => 'error',
                            'message' => $errorMessage,
                            'details' => $line,
                        ));
                        continue;
                    }

                    $book = $bookRows[0];
                    $qtyBefore = (int) $this->arrayValue($book, 'quantity', 0);
                    $appliedQty = min($deltaQty, max(0, $qtyBefore));
                    $qtyAfter = max(0, $qtyBefore - $appliedQty);
                    $ebayItemBefore = (string) $this->arrayValue($book, 'ebayItemId', '');
                    $ebayItemAfter = $ebayItemBefore;

                    if ($qtyAfter <= 0) {
                        $ebayItemAfter = '';
                    }

                    if (!$dryRun) {
                        $this->dbQuery(
                            "UPDATE `book` SET `quantity`='" . (int) $qtyAfter . "', `modifyDate`='" . (int) $nowTs . "', `ebayItemId`='"
                            . $this->escapeSql($ebayItemAfter) . "', `ebayLastSync`='" . ($qtyAfter <= 0 ? 0 : (int) $this->arrayValue($book, 'ebayLastSync', 0))
                            . "' WHERE `idGroup`='" . (int) $idGroup . "' AND `idBook`='" . (int) $idBook . "' LIMIT 1"
                        );
                        $this->dbQuery(
                            "UPDATE `ebay_order_item` SET `stockAppliedQty`=`quantity`, `stockAppliedAtTs`='" . (int) $nowTs . "', `stockAppliedRunId`='" . (int) $runId
                            . "' WHERE `idGroup`='" . (int) $idGroup . "' AND `lineItemId`='" . $this->escapeSql($lineItemId) . "' LIMIT 1"
                        );
                    }

                    $stats['stockAdjustedLines']++;
                    $stats['stockAdjustedUnits'] += (int) $appliedQty;

                    $stockMessage = ($dryRun ? 'Simulation' : 'Application') . ' vente validée: -' . (int) $appliedQty . ' unité(s).';
                    if ($deltaQty > $appliedQty) {
                        $stockMessage .= ' Stock local insuffisant, quantité plafonnée à 0.';
                    }

                    $this->logSyncAction($runId, $idGroup, 'stock_decrement', array(
                        'isDryRun' => ($dryRun ? 1 : 0),
                        'idBook' => $idBook,
                        'ebayOrderId' => $ebayOrderId,
                        'lineItemId' => $lineItemId,
                        'ebayItemIdBefore' => $ebayItemBefore,
                        'ebayItemIdAfter' => $ebayItemAfter,
                        'qtyBefore' => $qtyBefore,
                        'qtyDelta' => $appliedQty,
                        'qtyAfter' => $qtyAfter,
                        'status' => 'ok',
                        'message' => $stockMessage,
                        'details' => $line,
                    ));
                }
            } else {
                $stats['errors']++;
                $details['errors'][] = array(
                    'action' => 'stock_decrement',
                    'message' => 'Colonnes stockApplied* absentes sur ebay_order_item (migration manquante).',
                );
            }

            // 3) delete
            $deleteRows = $this->dbSelect(
                "SELECT * FROM `book` WHERE `idGroup`='" . (int) $idGroup . "' AND `ebayItemId`<>'' AND (`onEbay`='0' OR `quantity`<=0) ORDER BY `idBook` ASC LIMIT " . (int) $remaining
            );
            foreach ($deleteRows as $row) {
                if ($remaining <= 0) {
                    break;
                }
                $remaining--;
                $idBook = (int) $this->arrayValue($row, 'idBook', 0);
                if ($dryRun) {
                    $stats['deleted']++;
                    $this->logSyncAction($runId, $idGroup, 'remove', array(
                        'isDryRun' => 1,
                        'idBook' => $idBook,
                        'ebayItemIdBefore' => (string) $this->arrayValue($row, 'ebayItemId', ''),
                        'ebayItemIdAfter' => '',
                        'status' => 'planned',
                        'message' => 'Suppression eBay prévue.',
                    ));
                    continue;
                }

                $ok = $this->endItem(array(
                    'ItemID' => (string) $this->arrayValue($row, 'ebayItemId', ''),
                    'SKU' => (string) $this->arrayValue($row, 'idBook', ''),
                    'idBook' => $idBook,
                    'idGroup' => $idGroup,
                ));
                if ($ok) {
                    $this->dbQuery(
                        "UPDATE `book` SET `ebayItemId`='', `ebayLastSync`='0' WHERE `idGroup`='" . (int) $idGroup . "' AND `idBook`='" . (int) $idBook . "' LIMIT 1"
                    );
                    $stats['deleted']++;
                    $this->logSyncAction($runId, $idGroup, 'remove', array(
                        'idBook' => $idBook,
                        'ebayItemIdBefore' => (string) $this->arrayValue($row, 'ebayItemId', ''),
                        'ebayItemIdAfter' => '',
                        'status' => 'ok',
                        'message' => 'Annonce eBay supprimée.',
                    ));
                } else {
                    $stats['errors']++;
                    $errorMessage = $this->getLastErrorMessageForBook($idGroup, $idBook);
                    $details['errors'][] = array(
                        'idBook' => $idBook,
                        'action' => 'delete',
                        'message' => $errorMessage,
                    );
                    $this->logSyncAction($runId, $idGroup, 'remove', array(
                        'idBook' => $idBook,
                        'status' => 'error',
                        'message' => $errorMessage,
                    ));
                }
            }

            // 4) add
            if ($remaining > 0) {
                $addSkippedRows = $this->dbSelect(
                    "SELECT `idBook`,`price`,`price_ebay` FROM `book` WHERE `idGroup`='" . (int) $idGroup . "' AND `onEbay`='1' AND `quantity`>0 AND `ebayItemId`='' AND NOT (" . $validPriceCondition . ") ORDER BY `idBook` ASC LIMIT " . (int) $remaining
                );
                foreach ($addSkippedRows as $row) {
                    $stats['skippedNoPrice']++;
                    $this->logSyncAction($runId, $idGroup, 'add', array(
                        'isDryRun' => ($dryRun ? 1 : 0),
                        'idBook' => (int) $this->arrayValue($row, 'idBook', 0),
                        'status' => 'skipped',
                        'message' => $this->missingListingPriceMessage(),
                        'details' => array(
                            'price' => (string) $this->arrayValue($row, 'price', ''),
                            'price_ebay' => (string) $this->arrayValue($row, 'price_ebay', ''),
                        ),
                    ));
                }
                $addRows = $this->dbSelect(
                    "SELECT * FROM `book` WHERE `idGroup`='" . (int) $idGroup . "' AND `onEbay`='1' AND `quantity`>0 AND `ebayItemId`='' AND (" . $validPriceCondition . ") ORDER BY `idBook` ASC LIMIT " . (int) $remaining
                );
                foreach ($addRows as $row) {
                    if ($remaining <= 0) {
                        break;
                    }
                    $remaining--;
                    $idBook = (int) $this->arrayValue($row, 'idBook', 0);
                    if ($dryRun) {
                        $stats['added']++;
                        $this->logSyncAction($runId, $idGroup, 'add', array(
                            'isDryRun' => 1,
                            'idBook' => $idBook,
                            'status' => 'planned',
                            'message' => 'Ajout eBay prévu.',
                        ));
                        continue;
                    }

                    $params = $this->buildListingParamsFromBookRow($row);
                    $listingId = $this->addItem($params);
                    if ($listingId !== false) {
                        $this->dbQuery(
                            "UPDATE `book` SET `ebayItemId`='" . $this->escapeSql((string) $listingId) . "', `onEbay`='1', `ebayLastSync`='" . (int) $nowTs . "' WHERE `idGroup`='" . (int) $idGroup . "' AND `idBook`='" . (int) $idBook . "' LIMIT 1"
                        );
                        $stats['added']++;
                        $this->logSyncAction($runId, $idGroup, 'add', array(
                            'idBook' => $idBook,
                            'ebayItemIdAfter' => (string) $listingId,
                            'status' => 'ok',
                            'message' => 'Annonce eBay ajoutée.',
                        ));
                    } else {
                        $stats['errors']++;
                        $errorMessage = $this->getLastErrorMessageForBook($idGroup, $idBook);
                        $details['errors'][] = array(
                            'idBook' => $idBook,
                            'action' => 'add',
                            'message' => $errorMessage,
                        );
                        $this->logSyncAction($runId, $idGroup, 'add', array(
                            'idBook' => $idBook,
                            'status' => 'error',
                            'message' => $errorMessage,
                        ));
                    }
                }
            }

            // 5) update
            if ($remaining > 0) {
                $updateSkippedRows = $this->dbSelect(
                    "SELECT `idBook`,`ebayItemId`,`price`,`price_ebay` FROM `book` WHERE `idGroup`='" . (int) $idGroup . "' AND `onEbay`='1' AND `quantity`>0 AND `ebayItemId`<>'' AND `modifyDate`>`ebayLastSync` AND NOT (" . $validPriceCondition . ") ORDER BY `idBook` ASC LIMIT " . (int) $remaining
                );
                foreach ($updateSkippedRows as $row) {
                    $stats['skippedNoPrice']++;
                    $this->logSyncAction($runId, $idGroup, 'update', array(
                        'isDryRun' => ($dryRun ? 1 : 0),
                        'idBook' => (int) $this->arrayValue($row, 'idBook', 0),
                        'ebayItemIdBefore' => (string) $this->arrayValue($row, 'ebayItemId', ''),
                        'status' => 'skipped',
                        'message' => $this->missingListingPriceMessage(),
                        'details' => array(
                            'price' => (string) $this->arrayValue($row, 'price', ''),
                            'price_ebay' => (string) $this->arrayValue($row, 'price_ebay', ''),
                        ),
                    ));
                }
                $updateRows = $this->dbSelect(
                    "SELECT * FROM `book` WHERE `idGroup`='" . (int) $idGroup . "' AND `onEbay`='1' AND `quantity`>0 AND `ebayItemId`<>'' AND `modifyDate`>`ebayLastSync` AND (" . $validPriceCondition . ") ORDER BY `idBook` ASC LIMIT " . (int) $remaining
                );
                foreach ($updateRows as $row) {
                    if ($remaining <= 0) {
                        break;
                    }
                    $remaining--;
                    $idBook = (int) $this->arrayValue($row, 'idBook', 0);
                    if ($dryRun) {
                        $stats['updated']++;
                        $this->logSyncAction($runId, $idGroup, 'update', array(
                            'isDryRun' => 1,
                            'idBook' => $idBook,
                            'ebayItemIdBefore' => (string) $this->arrayValue($row, 'ebayItemId', ''),
                            'status' => 'planned',
                            'message' => 'Mise à jour eBay prévue.',
                        ));
                        continue;
                    }

                    $params = $this->buildListingParamsFromBookRow($row);
                    $result = $this->reviseItem($params);
                    if ($result !== false) {
                        $this->dbQuery(
                            "UPDATE `book` SET `onEbay`='1', `ebayLastSync`='" . (int) $nowTs . "' WHERE `idGroup`='" . (int) $idGroup . "' AND `idBook`='" . (int) $idBook . "' LIMIT 1"
                        );
                        $stats['updated']++;
                        $this->logSyncAction($runId, $idGroup, 'update', array(
                            'idBook' => $idBook,
                            'ebayItemIdBefore' => (string) $this->arrayValue($row, 'ebayItemId', ''),
                            'status' => 'ok',
                            'message' => 'Annonce eBay mise à jour.',
                        ));
                    } else {
                        $stats['errors']++;
                        $errorMessage = $this->getLastErrorMessageForBook($idGroup, $idBook);
                        $details['errors'][] = array(
                            'idBook' => $idBook,
                            'action' => 'update',
                            'message' => $errorMessage,
                        );
                        $this->logSyncAction($runId, $idGroup, 'update', array(
                            'idBook' => $idBook,
                            'status' => 'error',
                            'message' => $errorMessage,
                        ));
                    }
                }
            }

            $status = ($stats['errors'] > 0) ? 'done_with_errors' : 'done';
            $message = ($dryRun ? '[SIMULATION] ' : '')
                . "Ventes(stock): " . (int) $stats['stockAdjustedLines'] . " lignes / " . (int) $stats['stockAdjustedUnits']
                . " unité(s) | Ajouts: " . (int) $stats['added']
                . " | Mises à jour: " . (int) $stats['updated']
                . " | Suppressions: " . (int) $stats['deleted']
                . " | Sans prix: " . (int) $stats['skippedNoPrice']
                . " | Erreurs: " . (int) $stats['errors'];
            } catch (Exception $e) {
                $status = 'failed';
                $message = 'Erreur synchronisation eBay: ' . $e->getMessage();
                $stats['errors']++;
                $details['errors'][] = array(
                    'action' => 'sync',
                    'message' => $e->getMessage(),
                );
                $this->logSyncAction($runId, $idGroup, 'sync', array(
                    'isDryRun' => ($dryRun ? 1 : 0),
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ));
            }

            $details['stats'] = $stats;
            $this->finishSyncRun($runId, $stats, $status, $message, $details);
        } finally {
            $this->releaseSyncLock($idGroup);
        }

        return array(
            'runId' => $runId,
            'status' => $status,
            'message' => $message,
            'stats' => $stats,
            'details' => $details,
        );
    }

    public function listSyncRuns($idGroup = null, $limit = 10)
    {
        $idGroup = $this->normalizeGroupId($idGroup);
        $limit = max(1, min(100, (int) $limit));
        if (!$this->tableExists('ebay_sync_run')) {
            return array();
        }
        return $this->dbSelect(
            "SELECT * FROM `ebay_sync_run` WHERE `idGroup`='" . (int) $idGroup . "' ORDER BY `id` DESC LIMIT " . (int) $limit
        );
    }

    public function listSyncActions($runId, $idGroup = null, $limit = 1000)
    {
        $runId = (int) $runId;
        $idGroup = $this->normalizeGroupId($idGroup);
        $limit = max(1, min(5000, (int) $limit));
        if ($runId <= 0 || !$this->tableExists('ebay_sync_action')) {
            return array();
        }
        return $this->dbSelect(
            "SELECT * FROM `ebay_sync_action` WHERE `idRun`='" . (int) $runId . "' AND `idGroup`='" . (int) $idGroup . "' ORDER BY `id` ASC LIMIT " . (int) $limit
        );
    }

    public function deleteSyncRun($runId, $idGroup = null)
    {
        $runId = (int) $runId;
        $idGroup = $this->normalizeGroupId($idGroup);
        if ($runId <= 0 || $idGroup <= 0) {
            return array(
                'deletedRuns' => 0,
                'deletedActions' => 0,
                'message' => 'Run invalide.',
            );
        }
        if (!$this->tableExists('ebay_sync_run')) {
            return array(
                'deletedRuns' => 0,
                'deletedActions' => 0,
                'message' => 'Historique sync indisponible.',
            );
        }

        $existsRows = $this->dbSelect(
            "SELECT `id` FROM `ebay_sync_run` WHERE `id`='" . (int) $runId . "' AND `idGroup`='" . (int) $idGroup . "' LIMIT 1"
        );
        if (empty($existsRows)) {
            return array(
                'deletedRuns' => 0,
                'deletedActions' => 0,
                'message' => 'Run introuvable.',
            );
        }

        $deletedActions = 0;
        if ($this->tableExists('ebay_sync_action')) {
            $countRows = $this->dbSelect(
                "SELECT COUNT(*) AS `nb` FROM `ebay_sync_action` WHERE `idRun`='" . (int) $runId . "' AND `idGroup`='" . (int) $idGroup . "'"
            );
            if (!empty($countRows) && isset($countRows[0]['nb'])) {
                $deletedActions = (int) $countRows[0]['nb'];
            }
            $this->dbQuery(
                "DELETE FROM `ebay_sync_action` WHERE `idRun`='" . (int) $runId . "' AND `idGroup`='" . (int) $idGroup . "'"
            );
        }

        $this->dbQuery(
            "DELETE FROM `ebay_sync_run` WHERE `id`='" . (int) $runId . "' AND `idGroup`='" . (int) $idGroup . "' LIMIT 1"
        );

        return array(
            'deletedRuns' => 1,
            'deletedActions' => $deletedActions,
            'message' => 'Run supprimé.',
        );
    }

    public function runReconcileScan($idGroup = null, $idUser = 0, array $options = array())
    {
        $idGroup = $this->normalizeGroupId($idGroup);
        $idUser = (int) $idUser;
        $triggerSource = trim((string) $this->arrayValue($options, 'triggerSource', 'manual'));
        if ($triggerSource === '') {
            $triggerSource = 'manual';
        }
        $maxOffers = (int) $this->arrayValue($options, 'maxOffers', 10000);
        if ($maxOffers <= 0) {
            $maxOffers = 10000;
        }
        $maxOffers = min($maxOffers, 20000);
        $maxInventoryLookups = (int) $this->arrayValue($options, 'maxInventoryLookups', 120);
        if ($maxInventoryLookups < 0) {
            $maxInventoryLookups = 0;
        }
        $maxInventoryLookups = min($maxInventoryLookups, 1000);
        $maxFallbackSkuLookups = (int) $this->arrayValue($options, 'maxFallbackSkuLookups', 120);
        if ($maxFallbackSkuLookups < 0) {
            $maxFallbackSkuLookups = 0;
        }
        $maxFallbackSkuLookups = min($maxFallbackSkuLookups, 1000);
        $maxTradingDescriptionLookups = (int) $this->arrayValue($options, 'maxTradingDescriptionLookups', 500);
        if ($maxTradingDescriptionLookups < 0) {
            $maxTradingDescriptionLookups = 0;
        }
        $maxTradingDescriptionLookups = min($maxTradingDescriptionLookups, 5000);
        $tradingStartPage = (int) $this->arrayValue($options, 'tradingStartPage', 1);
        if ($tradingStartPage < 1) {
            $tradingStartPage = 1;
        }
        $continueRunId = (int) $this->arrayValue($options, 'continueRunId', 0);
        $resumeLocalStartIdBook = (int) $this->arrayValue($options, 'resumeLocalStartIdBook', 0);
        if ($resumeLocalStartIdBook < 0) {
            $resumeLocalStartIdBook = 0;
        }
        $resumeRemoteOfferStartOffset = (int) $this->arrayValue($options, 'resumeRemoteOfferStartOffset', 0);
        if ($resumeRemoteOfferStartOffset < 0) {
            $resumeRemoteOfferStartOffset = 0;
        }
        $maxDurationSeconds = (int) $this->arrayValue($options, 'maxDurationSeconds', 90);
        if ($maxDurationSeconds < 15) {
            $maxDurationSeconds = 15;
        }
        $maxDurationSeconds = min($maxDurationSeconds, 1800);

        $runId = 0;
        if ($continueRunId > 0) {
            $runId = $this->startReconcileRunContinuation($continueRunId, $idGroup, $idUser, $triggerSource);
        }
        if ($runId <= 0) {
            $runId = $this->startReconcileRun($idGroup, $idUser, $triggerSource);
        }
        if ($continueRunId > 0 && $runId > 0) {
            $resumeHints = $this->getReconcileResumeHintsFromRun($runId);
            if ($tradingStartPage <= 1 && (int) $this->arrayValue($resumeHints, 'tradingStartPage', 0) > 1) {
                $tradingStartPage = (int) $this->arrayValue($resumeHints, 'tradingStartPage', 1);
            }
            if ($resumeLocalStartIdBook <= 0 && (int) $this->arrayValue($resumeHints, 'localStartAfterIdBook', 0) > 0) {
                $resumeLocalStartIdBook = (int) $this->arrayValue($resumeHints, 'localStartAfterIdBook', 0);
            }
            if ($resumeRemoteOfferStartOffset <= 0 && (int) $this->arrayValue($resumeHints, 'remoteOfferStartOffset', 0) > 0) {
                $resumeRemoteOfferStartOffset = (int) $this->arrayValue($resumeHints, 'remoteOfferStartOffset', 0);
            }
        }
        $items = array();
        $errors = array();
        $notes = array();
        $scanStartedAt = time();
        $inventoryLookupCount = 0;
        $fallbackSkuLookupCount = 0;
        $inventoryLookupLimitReached = false;
        $fallbackLookupLimitReached = false;
        $tradingDescriptionLookupLimitReached = false;
        $scanInterrupted = false;
        $remoteOffersComplete = true;
        $resumeData = array();

        try {
            $remoteOffers = array();
            $remoteOffersLoaded = false;
            $remoteOffersSource = 'inventory';
            $forceTradingListing = $this->shouldForceTradingListingForReconcile($idGroup);

            if ($forceTradingListing) {
                try {
                    $tradingNextPage = 1;
                    $tradingComplete = true;
                    $remoteOffers = $this->fetchAllOffersViaTradingApi($maxOffers, $maxDurationSeconds, $tradingStartPage, $tradingNextPage, $tradingComplete);
                    $remoteOffersLoaded = true;
                    $remoteOffersSource = 'trading';
                    $remoteOffersComplete = $tradingComplete;
                    $resumeData = array(
                        'source' => 'trading',
                        'startPage' => (int) $tradingStartPage,
                        'nextPage' => (int) $tradingNextPage,
                        'complete' => ($tradingComplete ? 1 : 0),
                    );
                    // In trading mode, prioritize linking/state checks and avoid heavy inventory diffs.
                    $maxInventoryLookups = 0;
                    if (!$tradingComplete) {
                        $scanInterrupted = true;
                        $notes[] = 'liste eBay partielle: reprise possible à partir de la page Trading #' . (int) $tradingNextPage . '.';
                    }
                } catch (Throwable $fallbackError) {
                    $tradingErrorMessage = $fallbackError->getMessage();
                    try {
                        $remoteOffers = $this->fetchAllOffersForReconcile($maxOffers);
                        $remoteOffersLoaded = true;
                        $remoteOffersSource = 'inventory';
                        $remoteOffersComplete = true;
                        $resumeData = array();
                        $notes[] = 'fallback listing eBay via Inventory REST activé (Trading API indisponible).';
                        $notes[] = 'erreur Trading API: ' . $tradingErrorMessage;
                    } catch (Throwable $inventoryFallbackError) {
                        $errors[] = 'chargement offres distantes (Trading): ' . $tradingErrorMessage;
                        $errors[] = 'fallback inventory REST indisponible: ' . $inventoryFallbackError->getMessage();
                    }
                }
            } else {
                try {
                    $remoteOffers = $this->fetchAllOffersForReconcile($maxOffers);
                    $remoteOffersLoaded = true;
                } catch (Throwable $e) {
                    $inventoryListError = $e->getMessage();
                    $inventoryInvalidSkuError = $this->isInventoryOfferListingInvalidSkuError($inventoryListError);
                    if ($inventoryInvalidSkuError) {
                        $this->setEbayGroupSetting($idGroup, 'reconcile_listing_source', 'trading', 0);
                    }
                    try {
                        $tradingNextPage = 1;
                        $tradingComplete = true;
                        $remoteOffers = $this->fetchAllOffersViaTradingApi($maxOffers, $maxDurationSeconds, $tradingStartPage, $tradingNextPage, $tradingComplete);
                        $remoteOffersLoaded = true;
                        $remoteOffersSource = 'trading';
                        $remoteOffersComplete = $tradingComplete;
                        $resumeData = array(
                            'source' => 'trading',
                            'startPage' => (int) $tradingStartPage,
                            'nextPage' => (int) $tradingNextPage,
                            'complete' => ($tradingComplete ? 1 : 0),
                        );
                        if (!$inventoryInvalidSkuError) {
                            $notes[] = 'fallback listing eBay via Trading API (GetMyeBaySelling) activé.';
                            $notes[] = 'listing inventory REST indisponible: ' . $inventoryListError;
                        }
                        // In trading fallback mode, prioritize linking/state checks and avoid heavy inventory diffs.
                        $maxInventoryLookups = 0;
                        if (!$tradingComplete) {
                            $scanInterrupted = true;
                            $notes[] = 'liste eBay partielle: reprise possible à partir de la page Trading #' . (int) $tradingNextPage . '.';
                        }
                    } catch (Throwable $fallbackError) {
                        $errors[] = 'chargement offres distantes: ' . $inventoryListError;
                        $errors[] = 'fallback trading eBay indisponible: ' . $fallbackError->getMessage();
                    }
                }
            }
            $remoteOffersMeta = array();
            $remoteByIdBook = array();
            foreach ($remoteOffers as $offerIndex => $remoteOfferRaw) {
                $offer = (array) $remoteOfferRaw;
                $rawIdentifier = '';
                $remoteIdBook = $this->extractRemoteIdBookFromOffer($offer, $rawIdentifier);
                $offerKey = $this->buildReconcileOfferKey($offer, $offerIndex);
                while (isset($remoteOffersMeta[$offerKey])) {
                    $offerKey .= '-dup';
                }
                $remoteOffersMeta[$offerKey] = array(
                    'offer' => $offer,
                    'remoteIdBook' => $remoteIdBook,
                    'rawIdentifier' => $rawIdentifier,
                );
                if ($remoteIdBook > 0 && !isset($remoteByIdBook[$remoteIdBook])) {
                    $remoteByIdBook[$remoteIdBook] = $offerKey;
                }
            }

            $localByIdBook = array();
            $localRows = $this->dbSelect(
                "SELECT * FROM `book` WHERE `idGroup`='" . (int) $idGroup . "' AND (`onEbay`='1' OR `ebayItemId`<>'')"
            );
            foreach ($localRows as $row) {
                $idBook = (int) $this->arrayValue($row, 'idBook', 0);
                if ($idBook > 0) {
                    $localByIdBook[$idBook] = $row;
                }
            }

            if ($remoteOffersLoaded && !empty($remoteByIdBook)) {
                $missingLookupIds = array();
                foreach ($remoteByIdBook as $remoteIdBook => $offerKey) {
                    if (!isset($localByIdBook[$remoteIdBook])) {
                        $missingLookupIds[] = (int) $remoteIdBook;
                    }
                }
                if (!empty($missingLookupIds)) {
                    $chunks = array_chunk($missingLookupIds, 300);
                    foreach ($chunks as $chunkIds) {
                        $intValues = array();
                        foreach ($chunkIds as $chunkId) {
                            $chunkId = (int) $chunkId;
                            if ($chunkId > 0) {
                                $intValues[] = $chunkId;
                            }
                        }
                        if (empty($intValues)) {
                            continue;
                        }
                        $rows = $this->dbSelect(
                            "SELECT * FROM `book` WHERE `idGroup`='" . (int) $idGroup . "' AND `idBook` IN (" . implode(',', $intValues) . ")"
                        );
                        foreach ($rows as $row) {
                            $idBook = (int) $this->arrayValue($row, 'idBook', 0);
                            if ($idBook > 0) {
                                $localByIdBook[$idBook] = $row;
                            }
                        }
                    }
                }
            }

            if (!empty($localByIdBook)) {
                ksort($localByIdBook, SORT_NUMERIC);
            }

            if (!$remoteOffersLoaded) {
                throw new RuntimeException(
                    "chargement offres distantes impossible: la liste complète eBay n'a pas pu être récupérée (audit strict par idBook impossible)."
                );
            }

            $inventoryCache = array();
            $offerDetailsCache = array();
            $tradingDescriptionCache = array();
            $tradingDescriptionLookupCount = 0;
            $matchedOfferKeys = array();
            $lastProcessedLocalIdBook = 0;

            foreach ($localByIdBook as $idBook => $row) {
                if ($resumeLocalStartIdBook > 0 && (int) $idBook <= (int) $resumeLocalStartIdBook) {
                    continue;
                }
                if ((time() - $scanStartedAt) >= $maxDurationSeconds) {
                    $scanInterrupted = true;
                    $resumeSource = (string) $this->arrayValue($resumeData, 'source', '');
                    $resumeComplete = (int) $this->arrayValue($resumeData, 'complete', 1);
                    if (empty($resumeData) || ($resumeSource === 'trading' && $resumeComplete === 1)) {
                        $resumeData = array(
                            'source' => 'local',
                            'phase' => 'local_compare',
                            'localStartAfterIdBook' => (int) $lastProcessedLocalIdBook,
                            'complete' => 0,
                        );
                    }
                    break;
                }
                try {
                    $localListingId = trim((string) $this->arrayValue($row, 'ebayItemId', ''));
                    $localOnEbay = ((string) $this->arrayValue($row, 'onEbay', '0') === '1');
                    $localQuantity = (int) $this->arrayValue($row, 'quantity', 0);
                    $expectedOnEbay = ($localOnEbay && $localQuantity > 0);

                    $offer = array();
                    $offerKey = '';
                    $remoteIdBook = 0;
                    $remoteIdentifier = '';
                    if (isset($remoteByIdBook[$idBook])) {
                        $offerKey = (string) $remoteByIdBook[$idBook];
                        $offerMeta = isset($remoteOffersMeta[$offerKey]) ? (array) $remoteOffersMeta[$offerKey] : array();
                        $offer = isset($offerMeta['offer']) ? (array) $offerMeta['offer'] : array();
                        $remoteIdBook = (int) $this->arrayValue($offerMeta, 'remoteIdBook', 0);
                        $remoteIdentifier = (string) $this->arrayValue($offerMeta, 'rawIdentifier', '');
                        if ($offerKey !== '') {
                            $matchedOfferKeys[$offerKey] = true;
                        }
                    }
                    if (empty($offer) && !$remoteOffersLoaded) {
                        if ($fallbackSkuLookupCount < $maxFallbackSkuLookups) {
                            $fallbackSkuLookupCount++;
                            $singleLookup = $this->findOfferBySku((string) $idBook);
                            if (!empty($singleLookup)) {
                                $offer = $singleLookup;
                                $remoteIdBook = $idBook;
                                $remoteIdentifier = trim((string) $this->arrayValue($offer, 'sku', (string) $idBook));
                            }
                        } else {
                            $fallbackLookupLimitReached = true;
                        }
                    }
                    $remoteListingId = trim((string) $this->arrayValue($offer, 'listingId', ''));
                    $linkStatus = $this->describeReconcileLocalLinkStatus($localListingId, $remoteListingId);

                    if ($expectedOnEbay && empty($offer)) {
                        if (!$remoteOffersComplete) {
                            // In partial listing mode, we cannot conclude that remote listing is missing yet.
                            continue;
                        }
                        $items[] = $this->createReconcileItemRow($runId, $idGroup, array(
                            'discrepancyType' => 'missing_remote',
                            'actionSuggested' => 'set_on_ebay_0',
                            'localBookId' => $idBook,
                            'localEbayItemId' => $localListingId,
                            'remoteSku' => '',
                            'title' => (string) $this->arrayValue($row, 'title', ''),
                            'author' => (string) $this->arrayValue($row, 'author', ''),
                            'priceLocal' => $this->normalizeFloat($this->arrayValue($row, 'price', 0)),
                            'quantityLocal' => (int) $this->arrayValue($row, 'quantity', 0),
                            'details' => array(
                                'reason' => 'Ouvrage attendu sur eBay mais absent côté eBay: correction locale recommandée (onEbay=0).',
                                'remoteIdBook' => 0,
                                'remoteIdentifier' => '',
                                'localLinkStatus' => $linkStatus['label'],
                            ),
                        ));
                        continue;
                    }

                    if (empty($offer)) {
                        continue;
                    }

                    // Priority: relink local listing metadata first when ebayItemId is missing/inconsistent.
                    if ($linkStatus['code'] !== 'ok') {
                        $reason = ($linkStatus['code'] === 'missing')
                            ? "Annonce eBay trouvée pour idBook {$idBook} mais ebayItemId local est vide."
                            : "Annonce eBay trouvée pour idBook {$idBook} mais ebayItemId local est incohérent.";
                        $details = array(
                            'reason' => $reason,
                            'remoteIdBook' => $remoteIdBook,
                            'remoteIdentifier' => $remoteIdentifier,
                            'localLinkStatus' => $linkStatus['label'],
                            'localListingId' => $localListingId,
                            'remoteListingId' => $remoteListingId,
                        );
                        if (!$localOnEbay) {
                            $details['secondaryAction'] = 'set_on_ebay_1';
                        }
                        $items[] = $this->createReconcileItemRow($runId, $idGroup, array(
                            'discrepancyType' => 'missing_local_link',
                            'actionSuggested' => 'link_local',
                            'localBookId' => $idBook,
                            'localEbayItemId' => $localListingId,
                            'remoteSku' => ($remoteIdentifier !== '' ? $remoteIdentifier : (string) $this->arrayValue($offer, 'sku', '')),
                            'remoteOfferId' => (string) $this->arrayValue($offer, 'offerId', ''),
                            'remoteListingId' => $remoteListingId,
                            'title' => (string) $this->arrayValue($row, 'title', ''),
                            'author' => (string) $this->arrayValue($row, 'author', ''),
                            'priceLocal' => $this->normalizeFloat($this->arrayValue($row, 'price', 0)),
                            'priceRemote' => $this->extractOfferPrice($offer),
                            'quantityLocal' => $localQuantity,
                            'quantityRemote' => $this->extractOfferQuantity($offer),
                            'details' => $details,
                        ));
                        continue;
                    }

                    if (!$localOnEbay) {
                        $items[] = $this->createReconcileItemRow($runId, $idGroup, array(
                            'discrepancyType' => 'state_mismatch',
                            'actionSuggested' => 'remove',
                            'localBookId' => $idBook,
                            'localEbayItemId' => $localListingId,
                            'remoteSku' => ($remoteIdentifier !== '' ? $remoteIdentifier : (string) $this->arrayValue($offer, 'sku', '')),
                            'remoteOfferId' => (string) $this->arrayValue($offer, 'offerId', ''),
                            'remoteListingId' => $remoteListingId,
                            'title' => (string) $this->arrayValue($row, 'title', ''),
                            'author' => (string) $this->arrayValue($row, 'author', ''),
                            'priceLocal' => $this->normalizeFloat($this->arrayValue($row, 'price', 0)),
                            'priceRemote' => $this->extractOfferPrice($offer),
                            'quantityLocal' => (int) $this->arrayValue($row, 'quantity', 0),
                            'quantityRemote' => $this->extractOfferQuantity($offer),
                            'details' => array(
                                'reason' => 'Annonce eBay active alors que la fiche locale ne doit plus être publiée.',
                                'secondaryAction' => 'set_on_ebay_1',
                                'remoteIdBook' => $remoteIdBook,
                                'remoteIdentifier' => $remoteIdentifier,
                                'localLinkStatus' => $linkStatus['label'],
                            ),
                        ));
                        continue;
                    }

                    if ($localQuantity <= 0) {
                        $items[] = $this->createReconcileItemRow($runId, $idGroup, array(
                            'discrepancyType' => 'state_mismatch',
                            'actionSuggested' => 'set_qty_1',
                            'localBookId' => $idBook,
                            'localEbayItemId' => $localListingId,
                            'remoteSku' => ($remoteIdentifier !== '' ? $remoteIdentifier : (string) $this->arrayValue($offer, 'sku', '')),
                            'remoteOfferId' => (string) $this->arrayValue($offer, 'offerId', ''),
                            'remoteListingId' => $remoteListingId,
                            'title' => (string) $this->arrayValue($row, 'title', ''),
                            'author' => (string) $this->arrayValue($row, 'author', ''),
                            'priceLocal' => $this->normalizeFloat($this->arrayValue($row, 'price', 0)),
                            'priceRemote' => $this->extractOfferPrice($offer),
                            'quantityLocal' => $localQuantity,
                            'quantityRemote' => $this->extractOfferQuantity($offer),
                            'details' => array(
                                'reason' => 'Annonce eBay active alors que la fiche locale a une quantité nulle: correction locale recommandée (quantity=1).',
                                'remoteIdBook' => $remoteIdBook,
                                'remoteIdentifier' => $remoteIdentifier,
                                'localLinkStatus' => $linkStatus['label'],
                                'secondaryAction' => 'remove',
                            ),
                        ));
                        continue;
                    }

                    $skuForInventory = trim((string) $this->arrayValue($offer, 'sku', ''));
                    if ($skuForInventory === '') {
                        $skuForInventory = (string) $idBook;
                    }
                    $params = $this->buildListingParamsFromBookRow($row);
                    $localInventoryPayload = $this->buildInventoryPayload($skuForInventory, $params);
                    $remoteInventory = array();
                    if ($inventoryLookupCount < $maxInventoryLookups) {
                        $inventoryLookupCount++;
                        $remoteInventory = $this->fetchInventoryItemForReconcile($skuForInventory, $inventoryCache);
                    } else {
                        $inventoryLookupLimitReached = true;
                    }

                    $differences = array();

                    $localProduct = (array) $this->arrayValue($localInventoryPayload, 'product', array());
                    if (!empty($remoteInventory)) {
                        $localTitle = trim((string) $this->arrayValue($localProduct, 'title', ''));
                        $remoteTitle = trim((string) $this->arrayValue((array) $this->arrayValue($remoteInventory, 'product', array()), 'title', ''));
                        if ($remoteTitle !== '' && !$this->sameText($localTitle, $remoteTitle)) {
                            $differences['title'] = array('local' => $localTitle, 'remote' => $remoteTitle);
                        }

                        $localAuthor = trim((string) $this->arrayValue($row, 'author', ''));
                        $remoteAuthor = $this->extractRemoteAuthor($remoteInventory);
                        if ($localAuthor !== '' && $remoteAuthor !== '' && !$this->sameText($localAuthor, $remoteAuthor)) {
                            $differences['author'] = array('local' => $localAuthor, 'remote' => $remoteAuthor);
                        }
                    }

                    $localDescription = trim((string) $this->arrayValue($localProduct, 'description', ''));
                    $remoteDescriptionMeta = $this->extractRemoteDescriptionForReconcile(
                        $offer,
                        $remoteInventory,
                        $offerDetailsCache,
                        $tradingDescriptionCache,
                        $tradingDescriptionLookupCount,
                        $maxTradingDescriptionLookups,
                        $tradingDescriptionLookupLimitReached
                    );
                    $remoteDescription = trim((string) $this->arrayValue($remoteDescriptionMeta, 'value', ''));
                    $remoteDescriptionSource = (string) $this->arrayValue($remoteDescriptionMeta, 'source', 'none');
                    $remoteDescriptionKnown = ((int) $this->arrayValue($remoteDescriptionMeta, 'known', 0) === 1);
                    if (($remoteDescriptionKnown || $remoteDescription !== '') && !$this->sameText($localDescription, $remoteDescription)) {
                        $differences['description'] = array(
                            'local' => $localDescription,
                            'remote' => $remoteDescription,
                            'source' => $remoteDescriptionSource,
                        );
                    }

                    $localPrice = $this->normalizeFloat($this->arrayValue($row, 'price', 0));
                    $remotePrice = $this->extractOfferPrice($offer);
                    if (abs($localPrice - $remotePrice) > 0.0001) {
                        $differences['price'] = array('local' => $localPrice, 'remote' => $remotePrice);
                    }

                    $localQuantity = (int) $this->arrayValue($row, 'quantity', 0);
                    $remoteQuantity = $this->extractOfferQuantity($offer);
                    if ($localQuantity !== $remoteQuantity) {
                        $differences['quantity'] = array('local' => $localQuantity, 'remote' => $remoteQuantity);
                    }

                    if (!empty($remoteInventory)) {
                        $localImages = $this->normalizeImageUrlList($this->arrayValue($localProduct, 'imageUrls', array()));
                        $remoteImages = $this->normalizeImageUrlList($this->arrayValue((array) $this->arrayValue($remoteInventory, 'product', array()), 'imageUrls', array()));
                        if (!$this->sameImageList($localImages, $remoteImages)) {
                            $differences['photos'] = array('local' => $localImages, 'remote' => $remoteImages);
                        }
                    }

                    if (!empty($differences)) {
                        $discrepancyType = isset($differences['photos']) ? 'photo_mismatch' : 'field_mismatch';
                        $suggestedAction = 'update';
                        if (isset($differences['description']) && is_array($differences['description'])) {
                            $suggestedAction = 'update_local';
                        }
                        $items[] = $this->createReconcileItemRow($runId, $idGroup, array(
                            'discrepancyType' => $discrepancyType,
                            'actionSuggested' => $suggestedAction,
                            'localBookId' => $idBook,
                            'localEbayItemId' => $localListingId,
                            'remoteSku' => ($remoteIdentifier !== '' ? $remoteIdentifier : (string) $this->arrayValue($offer, 'sku', '')),
                            'remoteOfferId' => (string) $this->arrayValue($offer, 'offerId', ''),
                            'remoteListingId' => $remoteListingId,
                            'title' => (string) $this->arrayValue($row, 'title', ''),
                            'author' => (string) $this->arrayValue($row, 'author', ''),
                            'priceLocal' => $localPrice,
                            'priceRemote' => $remotePrice,
                            'quantityLocal' => $localQuantity,
                            'quantityRemote' => $remoteQuantity,
                            'details' => array(
                                'differences' => $differences,
                                'remoteIdBook' => $remoteIdBook,
                                'remoteIdentifier' => $remoteIdentifier,
                                'localLinkStatus' => $linkStatus['label'],
                            ),
                        ));
                    }
                } catch (Throwable $e) {
                    $errors[] = 'idBook ' . $idBook . ': ' . $e->getMessage();
                }
                $lastProcessedLocalIdBook = (int) $idBook;
            }

            if ($remoteOffersLoaded) {
                $remoteOfferIndex = 0;
                foreach ($remoteOffersMeta as $offerKey => $offerMeta) {
                    if ($remoteOfferIndex < $resumeRemoteOfferStartOffset) {
                        $remoteOfferIndex++;
                        continue;
                    }
                    if ((time() - $scanStartedAt) >= $maxDurationSeconds) {
                        $scanInterrupted = true;
                        $resumeSource = (string) $this->arrayValue($resumeData, 'source', '');
                        $resumeComplete = (int) $this->arrayValue($resumeData, 'complete', 1);
                        if (empty($resumeData) || $resumeSource !== 'trading' || $resumeComplete === 1) {
                            $resumeData = array(
                                'source' => 'local',
                                'phase' => 'remote_orphans',
                                'remoteOfferStartOffset' => (int) $remoteOfferIndex,
                                'complete' => 0,
                            );
                        }
                        break;
                    }
                    try {
                        if (isset($matchedOfferKeys[$offerKey])) {
                            $remoteOfferIndex++;
                            continue;
                        }
                        $offer = isset($offerMeta['offer']) ? (array) $offerMeta['offer'] : array();
                        $offerSku = trim((string) $this->arrayValue($offer, 'sku', ''));
                        $remoteIdBook = (int) $this->arrayValue($offerMeta, 'remoteIdBook', 0);
                        $remoteIdentifier = trim((string) $this->arrayValue($offerMeta, 'rawIdentifier', ''));

                        if ($remoteIdBook > 0 && isset($localByIdBook[$remoteIdBook])) {
                            // Offre déjà rattachable à une fiche locale: ce n'est pas un orphelin.
                            $remoteOfferIndex++;
                            continue;
                        }

                        $remoteInventory = array();
                        if ($inventoryLookupCount < $maxInventoryLookups) {
                            $inventoryLookupCount++;
                            $remoteInventory = $this->fetchInventoryItemForReconcile($offerSku, $inventoryCache);
                        } else {
                            $inventoryLookupLimitReached = true;
                        }
                        $remoteTitle = trim((string) $this->arrayValue((array) $this->arrayValue($remoteInventory, 'product', array()), 'title', ''));
                        if ($remoteTitle === '') {
                            $remoteTitle = trim((string) $this->arrayValue($offer, 'listingDescription', ''));
                        }
                        $remoteAuthor = $this->extractRemoteAuthor($remoteInventory);
                        $suggestions = $this->findBookSuggestions($idGroup, $remoteTitle, $remoteAuthor, 5);
                        $defaultBookId = 0;
                        if (!empty($suggestions[0]['idBook']) && $remoteIdBook <= 0) {
                            $defaultBookId = (int) $suggestions[0]['idBook'];
                        }
                        $reason = 'Annonce eBay sans fiche GestLibr liée.';
                        if ($remoteIdBook > 0) {
                            $reason = 'Annonce eBay pour idBook #' . $remoteIdBook . ' absent de la base GestLibr.';
                        } elseif ($remoteIdentifier !== '' || $offerSku !== '') {
                            $reason = "Libellé personnalisé eBay invalide (idBook non numérique).";
                        }

                        $items[] = $this->createReconcileItemRow($runId, $idGroup, array(
                            'discrepancyType' => 'missing_local',
                            'actionSuggested' => 'link_local',
                            'localBookId' => $defaultBookId,
                            'localEbayItemId' => '',
                            'remoteSku' => ($remoteIdentifier !== '' ? $remoteIdentifier : $offerSku),
                            'remoteOfferId' => (string) $this->arrayValue($offer, 'offerId', ''),
                            'remoteListingId' => (string) $this->arrayValue($offer, 'listingId', ''),
                            'title' => $remoteTitle,
                            'author' => $remoteAuthor,
                            'priceLocal' => 0,
                            'priceRemote' => $this->extractOfferPrice($offer),
                            'quantityLocal' => 0,
                            'quantityRemote' => $this->extractOfferQuantity($offer),
                            'details' => array(
                                'reason' => $reason,
                                'remoteIdBook' => $remoteIdBook,
                                'remoteIdentifier' => $remoteIdentifier,
                                'suggestions' => $suggestions,
                            ),
                        ));
                    } catch (Throwable $e) {
                        $failedOffer = isset($offerMeta['offer']) ? (array) $offerMeta['offer'] : array();
                        $errors[] = 'offre ' . trim((string) $this->arrayValue($failedOffer, 'offerId', '')) . ': ' . $e->getMessage();
                    }
                    $remoteOfferIndex++;
                }
            } else {
                $errors[] = 'détection des annonces orphelines non exécutée (liste complète des offres indisponible).';
            }
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }

        if ($fallbackLookupLimitReached) {
            $notes[] = 'fallback SKU limité à ' . (int) $maxFallbackSkuLookups . ' lookups.';
        }
        if ($inventoryLookupLimitReached) {
            $notes[] = 'comparaison détaillée (auteur/photos) limitée à ' . (int) $maxInventoryLookups . ' inventaires.';
        }
        if ($tradingDescriptionLookupLimitReached) {
            $notes[] = 'comparaison description Trading limitée à ' . (int) $maxTradingDescriptionLookups . ' annonces.';
        }
        if ($scanInterrupted) {
            $notes[] = 'audit interrompu après ' . (int) $maxDurationSeconds . ' secondes pour éviter un blocage UI.';
        }
        if ($remoteOffersLoaded && !$remoteOffersComplete) {
            $notes[] = 'la liste des offres eBay est partielle: les écarts "missing_remote" sont reportés à un scan complet.';
        }

        $pendingCount = 0;
        $knownSignatures = $this->loadReconcileItemSignatures($runId);
        foreach ($items as $rowData) {
            $itemSignature = $this->buildReconcileItemSignature($rowData);
            if ($itemSignature !== '' && isset($knownSignatures[$itemSignature])) {
                continue;
            }
            $this->insertReconcileItem($rowData);
            if ($itemSignature !== '') {
                $knownSignatures[$itemSignature] = 1;
            }
            $pendingCount++;
        }

        $aggregatedCounts = $this->countReconcileItemsByStatus($runId);
        $totalItems = (int) $this->arrayValue($aggregatedCounts, 'nbItems', $pendingCount);
        $totalPending = (int) $this->arrayValue($aggregatedCounts, 'nbPending', $pendingCount);
        $totalApplied = (int) $this->arrayValue($aggregatedCounts, 'nbApplied', 0);
        $totalIgnored = (int) $this->arrayValue($aggregatedCounts, 'nbIgnored', 0);
        $totalActionErrors = (int) $this->arrayValue($aggregatedCounts, 'nbErrors', 0);
        $totalScanErrors = (!empty($errors) ? count($errors) : 0);
        $totalErrors = $totalActionErrors + $totalScanErrors;

        $status = empty($errors) ? 'done' : 'done_with_errors';
        if ($scanInterrupted || !$remoteOffersComplete) {
            $status = empty($errors) ? 'partial' : 'partial_with_errors';
        }
        $message = "Audit terminé. Nouveaux écarts: " . $pendingCount . ". Total run: " . $totalItems . ".";
        if (!empty($errors)) {
            $message .= " Erreurs: " . implode(' | ', $errors);
        }
        if (!empty($notes)) {
            $message .= " Notes: " . implode(' | ', $notes);
        }

        $stats = array(
            'nbItems' => $totalItems,
            'nbPending' => $totalPending,
            'nbApplied' => $totalApplied,
            'nbIgnored' => $totalIgnored,
            'nbErrors' => $totalErrors,
            'nbErrorsScan' => $totalScanErrors,
            'nbErrorsAction' => $totalActionErrors,
            'nbItemsNew' => $pendingCount,
            'errors' => $errors,
            'notes' => $notes,
            'remoteOffersLoadedCount' => (int) count($remoteOffers),
            'remoteOffersSource' => (string) $remoteOffersSource,
            'remoteOffersComplete' => ($remoteOffersComplete ? 1 : 0),
            'resume' => $resumeData,
        );
        $this->finishReconcileRun($runId, $status, $message, $stats);

        return array(
            'runId' => $runId,
            'status' => $status,
            'message' => $message,
            'stats' => $stats,
        );
    }

    public function listReconcileRuns($idGroup = null, $limit = 20)
    {
        $idGroup = $this->normalizeGroupId($idGroup);
        $limit = max(1, min(100, (int) $limit));
        if (!$this->tableExists('ebay_reconcile_run')) {
            return array();
        }
        return $this->dbSelect(
            "SELECT * FROM `ebay_reconcile_run` WHERE `idGroup`='" . (int) $idGroup . "' ORDER BY `id` DESC LIMIT " . (int) $limit
        );
    }

    public function purgeReconcileRuns($idGroup = null, $keepLatest = 10)
    {
        $idGroup = $this->normalizeGroupId($idGroup);
        $keepLatest = (int) $keepLatest;
        if ($keepLatest < 0) {
            $keepLatest = 0;
        }
        if ($keepLatest > 500) {
            $keepLatest = 500;
        }

        if (!$this->tableExists('ebay_reconcile_run')) {
            return array(
                'deletedRuns' => 0,
                'deletedItems' => 0,
                'keptRuns' => 0,
            );
        }

        $runs = $this->dbSelect(
            "SELECT `id` FROM `ebay_reconcile_run` WHERE `idGroup`='" . (int) $idGroup . "' ORDER BY `id` DESC"
        );
        if (empty($runs)) {
            return array(
                'deletedRuns' => 0,
                'deletedItems' => 0,
                'keptRuns' => 0,
            );
        }

        $allIds = array();
        foreach ($runs as $row) {
            $runId = (int) $this->arrayValue($row, 'id', 0);
            if ($runId > 0) {
                $allIds[] = $runId;
            }
        }
        if (empty($allIds)) {
            return array(
                'deletedRuns' => 0,
                'deletedItems' => 0,
                'keptRuns' => 0,
            );
        }

        $toDelete = array_slice($allIds, $keepLatest);
        if (empty($toDelete)) {
            return array(
                'deletedRuns' => 0,
                'deletedItems' => 0,
                'keptRuns' => count($allIds),
            );
        }

        $deletedItems = 0;
        if ($this->tableExists('ebay_reconcile_item')) {
            $itemRows = $this->dbSelect(
                "SELECT COUNT(*) AS nb FROM `ebay_reconcile_item` WHERE `idGroup`='" . (int) $idGroup . "' AND `idRun` IN (" . implode(',', $toDelete) . ")"
            );
            if (!empty($itemRows[0]['nb'])) {
                $deletedItems = (int) $itemRows[0]['nb'];
            }
            $this->dbQuery(
                "DELETE FROM `ebay_reconcile_item` WHERE `idGroup`='" . (int) $idGroup . "' AND `idRun` IN (" . implode(',', $toDelete) . ")"
            );
        }

        $this->dbQuery(
            "DELETE FROM `ebay_reconcile_run` WHERE `idGroup`='" . (int) $idGroup . "' AND `id` IN (" . implode(',', $toDelete) . ")"
        );

        return array(
            'deletedRuns' => count($toDelete),
            'deletedItems' => $deletedItems,
            'keptRuns' => max(0, count($allIds) - count($toDelete)),
        );
    }

    public function deleteReconcileRun($runId, $idGroup = null)
    {
        $runId = (int) $runId;
        $idGroup = $this->normalizeGroupId($idGroup);
        if ($runId <= 0) {
            return array(
                'deletedRuns' => 0,
                'deletedItems' => 0,
            );
        }
        if (!$this->tableExists('ebay_reconcile_run')) {
            return array(
                'deletedRuns' => 0,
                'deletedItems' => 0,
            );
        }

        $runRows = $this->dbSelect(
            "SELECT `id` FROM `ebay_reconcile_run` WHERE `id`='" . (int) $runId . "' AND `idGroup`='" . (int) $idGroup . "' LIMIT 1"
        );
        if (empty($runRows[0]['id'])) {
            return array(
                'deletedRuns' => 0,
                'deletedItems' => 0,
            );
        }

        $deletedItems = 0;
        if ($this->tableExists('ebay_reconcile_item')) {
            $itemRows = $this->dbSelect(
                "SELECT COUNT(*) AS nb FROM `ebay_reconcile_item` WHERE `idRun`='" . (int) $runId . "' AND `idGroup`='" . (int) $idGroup . "'"
            );
            if (!empty($itemRows[0]['nb'])) {
                $deletedItems = (int) $itemRows[0]['nb'];
            }
            $this->dbQuery(
                "DELETE FROM `ebay_reconcile_item` WHERE `idRun`='" . (int) $runId . "' AND `idGroup`='" . (int) $idGroup . "'"
            );
        }

        $this->dbQuery(
            "DELETE FROM `ebay_reconcile_run` WHERE `id`='" . (int) $runId . "' AND `idGroup`='" . (int) $idGroup . "' LIMIT 1"
        );

        return array(
            'deletedRuns' => 1,
            'deletedItems' => $deletedItems,
        );
    }

    public function getReconcileItems($runId, $idGroup = null)
    {
        $runId = (int) $runId;
        $idGroup = $this->normalizeGroupId($idGroup);
        if ($runId <= 0 || !$this->tableExists('ebay_reconcile_item')) {
            return array();
        }

        $where = "WHERE `idRun`='" . $runId . "'";
        if ($idGroup > 0) {
            if ($this->tableExists('ebay_reconcile_run')) {
                $runRows = $this->dbSelect(
                    "SELECT `id` FROM `ebay_reconcile_run` WHERE `id`='" . $runId . "' AND `idGroup`='" . (int) $idGroup . "' LIMIT 1"
                );
                if (empty($runRows[0]['id'])) {
                    return array();
                }
            }
            $where .= " AND `idGroup`='" . (int) $idGroup . "'";
        }

        return $this->dbSelect("SELECT * FROM `ebay_reconcile_item` " . $where . " ORDER BY `id` ASC");
    }

    public function applyReconcileActions($runId, array $selections, $idUser = 0, $idGroup = null)
    {
        $runId = (int) $runId;
        $idUser = (int) $idUser;
        $idGroup = $this->normalizeGroupId($idGroup);
        if ($runId <= 0 || !$this->tableExists('ebay_reconcile_item')) {
            return array(
                'runId' => $runId,
                'message' => 'Run audit invalide.',
                'stats' => array('applied' => 0, 'ignored' => 0, 'errors' => 0),
            );
        }

        if ($this->tableExists('ebay_reconcile_run')) {
            $runSql = "SELECT `id`, `idGroup` FROM `ebay_reconcile_run` WHERE `id`='" . (int) $runId . "'";
            if ($idGroup > 0) {
                $runSql .= " AND `idGroup`='" . (int) $idGroup . "'";
            }
            $runSql .= " LIMIT 1";
            $runRows = $this->dbSelect($runSql);
            if (empty($runRows[0]['id'])) {
                return array(
                    'runId' => $runId,
                    'message' => 'Run audit invalide.',
                    'stats' => array('applied' => 0, 'ignored' => 0, 'errors' => 0),
                );
            }
            if ($idGroup <= 0 && !empty($runRows[0]['idGroup'])) {
                $idGroup = (int) $runRows[0]['idGroup'];
            }
        }

        $stats = array('applied' => 0, 'ignored' => 0, 'errors' => 0);
        $nowTs = time();

        foreach ($selections as $selection) {
            $itemId = 0;
            $action = '';
            $linkIdBook = 0;

            if (is_array($selection)) {
                $itemId = (int) $this->arrayValue($selection, 'itemId', 0);
                $action = trim((string) $this->arrayValue($selection, 'action', ''));
                $linkIdBook = (int) $this->arrayValue($selection, 'linkIdBook', 0);
            } else {
                $itemId = (int) $selection;
            }

            if ($itemId <= 0) {
                continue;
            }

            $itemSql = "SELECT * FROM `ebay_reconcile_item` WHERE `id`='" . $itemId . "' AND `idRun`='" . $runId . "'";
            if ($idGroup > 0) {
                $itemSql .= " AND `idGroup`='" . (int) $idGroup . "'";
            }
            $itemSql .= " LIMIT 1";
            $rows = $this->dbSelect($itemSql);
            if (empty($rows[0])) {
                continue;
            }
            $item = $rows[0];
            $idGroup = (int) $this->arrayValue($item, 'idGroup', 0);
            if ($idGroup <= 0) {
                $idGroup = $this->resolveGroupId();
            }
            $localBookId = (int) $this->arrayValue($item, 'localBookId', 0);
            $remoteSku = trim((string) $this->arrayValue($item, 'remoteSku', ''));
            $remoteListingId = trim((string) $this->arrayValue($item, 'remoteListingId', ''));
            $remoteOfferId = trim((string) $this->arrayValue($item, 'remoteOfferId', ''));

            if ($action === '') {
                $action = trim((string) $this->arrayValue($item, 'actionSuggested', ''));
            }
            if ($action === '') {
                $action = 'ignore';
            }
            if ($action === 'link_orphan') {
                $action = 'link_local';
            }
            if ($linkIdBook <= 0) {
                $linkIdBook = $localBookId;
            }

            $status = 'applied';
            $message = 'Action appliquée.';
            try {
                if ($action === 'ignore') {
                    $status = 'ignored';
                    $message = 'Écart ignoré.';
                } elseif ($action === 'link_local') {
                    $targetBookId = ($localBookId > 0) ? (int) $localBookId : (int) $linkIdBook;
                    if ($targetBookId <= 0) {
                        throw new RuntimeException('idBook local manquant.');
                    }
                    if ($remoteListingId === '') {
                        throw new RuntimeException('listingId eBay distant manquant pour liaison locale.');
                    }
                    $bookRows = $this->dbSelect(
                        "SELECT `onEbay` FROM `book` WHERE `idGroup`='" . (int) $idGroup . "' AND `idBook`='" . (int) $targetBookId . "' LIMIT 1"
                    );
                    if (empty($bookRows[0])) {
                        throw new RuntimeException('Fiche ouvrage introuvable en base locale.');
                    }
                    $stateSku = (ctype_digit($remoteSku) && (int) $remoteSku > 0) ? $remoteSku : (string) $targetBookId;
                    $this->saveSuccessState($idGroup, $targetBookId, $stateSku, $remoteOfferId, $remoteListingId, 'PUBLISHED');
                    $this->dbQuery(
                        "UPDATE `book` SET `ebayItemId`='" . $this->escapeSql($remoteListingId) . "', `ebayLastSync`='" . (int) $nowTs . "', `onEbay`='1' WHERE `idGroup`='" . (int) $idGroup . "' AND `idBook`='" . (int) $targetBookId . "' LIMIT 1"
                    );
                    $message = 'Lien local eBay complété (ebayItemId + date de synchro + onEbay=1).';
                } elseif ($action === 'set_on_ebay_1') {
                    if ($localBookId <= 0) {
                        throw new RuntimeException('idBook local manquant.');
                    }
                    if ($remoteListingId === '') {
                        throw new RuntimeException('listingId eBay distant manquant.');
                    }
                    $stateSku = (ctype_digit($remoteSku) && (int) $remoteSku > 0) ? $remoteSku : (string) $localBookId;
                    $this->saveSuccessState($idGroup, $localBookId, $stateSku, $remoteOfferId, $remoteListingId, 'PUBLISHED');
                    $this->dbQuery(
                        "UPDATE `book` SET `onEbay`='1', `ebayItemId`='" . $this->escapeSql($remoteListingId) . "', `ebayLastSync`='" . (int) $nowTs . "' WHERE `idGroup`='" . (int) $idGroup . "' AND `idBook`='" . (int) $localBookId . "' LIMIT 1"
                    );
                    $message = 'Fiche locale basculée sur onEbay=1 et liée à l’annonce eBay.';
                } elseif ($action === 'set_on_ebay_0') {
                    if ($localBookId <= 0) {
                        throw new RuntimeException('idBook local manquant.');
                    }
                    $this->dbQuery(
                        "UPDATE `book` SET `onEbay`='0' WHERE `idGroup`='" . (int) $idGroup . "' AND `idBook`='" . (int) $localBookId . "' LIMIT 1"
                    );
                    $message = 'Fiche locale corrigée: onEbay=0.';
                } elseif ($action === 'set_qty_1') {
                    if ($localBookId <= 0) {
                        throw new RuntimeException('idBook local manquant.');
                    }
                    if ($remoteListingId === '') {
                        throw new RuntimeException('listingId eBay distant manquant.');
                    }
                    $stateSku = (ctype_digit($remoteSku) && (int) $remoteSku > 0) ? $remoteSku : (string) $localBookId;
                    $this->saveSuccessState($idGroup, $localBookId, $stateSku, $remoteOfferId, $remoteListingId, 'PUBLISHED');
                    $this->dbQuery(
                        "UPDATE `book` SET `quantity`='1', `onEbay`='1', `ebayItemId`='" . $this->escapeSql($remoteListingId) . "', `ebayLastSync`='" . (int) $nowTs . "', `modifyDate`='" . (int) $nowTs . "' WHERE `idGroup`='" . (int) $idGroup . "' AND `idBook`='" . (int) $localBookId . "' LIMIT 1"
                    );
                    $message = 'Quantité locale corrigée à 1 depuis l’état eBay (et lien local resynchronisé).';
                } elseif ($action === 'update_local') {
                    if ($localBookId <= 0) {
                        throw new RuntimeException('idBook local manquant.');
                    }
                    $bookRows = $this->dbSelect(
                        "SELECT * FROM `book` WHERE `idGroup`='" . (int) $idGroup . "' AND `idBook`='" . (int) $localBookId . "' LIMIT 1"
                    );
                    if (empty($bookRows[0])) {
                        throw new RuntimeException('Fiche ouvrage introuvable en base locale.');
                    }

                    $details = array();
                    $detailsJson = (string) $this->arrayValue($item, 'detailsJson', '');
                    if ($detailsJson !== '') {
                        $decodedDetails = json_decode($detailsJson, true);
                        if (is_array($decodedDetails)) {
                            $details = $decodedDetails;
                        }
                    }
                    $differences = (array) $this->arrayValue($details, 'differences', array());

                    $assignments = array();
                    $updatedFields = array();

                    $remoteDescription = '';

                    // 1) Reuse snapshot value if already captured during scan.
                    if (isset($differences['description']) && is_array($differences['description'])) {
                        $remoteDescription = trim((string) $this->arrayValue($differences['description'], 'remote', ''));
                    }

                    // 2) Canonical source: Inventory item description (when SKU is compatible).
                    if ($remoteDescription === '') {
                        $skuForDescription = trim((string) $remoteSku);
                        if ($skuForDescription === '' && $localBookId > 0) {
                            $skuForDescription = (string) $localBookId;
                        }
                        if ($skuForDescription !== '' && $this->isInventorySkuCompatible($skuForDescription)) {
                            try {
                                $inventoryItem = $this->requestJson('GET', '/sell/inventory/v1/inventory_item/' . rawurlencode($skuForDescription), array());
                                $remoteDescription = trim((string) $this->arrayValue((array) $this->arrayValue($inventoryItem, 'product', array()), 'description', ''));
                            } catch (Throwable $ignoreInventoryError) {
                                $remoteDescription = '';
                            }
                        }
                    }

                    // 3) Offer detail listingDescription for Inventory offers.
                    if ($remoteDescription === '' && $remoteOfferId !== '' && stripos($remoteOfferId, 'TRADING:') !== 0) {
                        try {
                            $offerPayload = $this->getOfferById($remoteOfferId);
                            $remoteDescription = trim((string) $this->arrayValue($offerPayload, 'listingDescription', ''));
                        } catch (Throwable $ignoreOfferError) {
                            $remoteDescription = '';
                        }
                    }

                    // 4) Trading fallback by ItemID for legacy/trading offers.
                    $tradingListingId = trim((string) $remoteListingId);
                    if ($tradingListingId === '' && stripos($remoteOfferId, 'TRADING:') === 0) {
                        $tradingListingId = trim((string) substr($remoteOfferId, 8));
                    }
                    if ($remoteDescription === '' && $tradingListingId !== '') {
                        $tradingMeta = $this->getTradingItemDescriptionMeta($tradingListingId);
                        if ((int) $this->arrayValue($tradingMeta, 'known', 0) === 1) {
                            $remoteDescription = trim((string) $this->arrayValue($tradingMeta, 'value', ''));
                        }
                    }

                    // 5) Final fallback: if description is present in details payload outside differences.
                    if ($remoteDescription === '' && isset($details['remoteDescription'])) {
                        $remoteDescription = trim((string) $this->arrayValue($details, 'remoteDescription', ''));
                    }

                    $remoteDescription = (string) $remoteDescription;
                    if (trim($remoteDescription) !== '') {
                        $assignments[] = "`description`='" . $this->escapeSql($remoteDescription) . "'";
                        $updatedFields[] = 'description';
                    }
                    if (empty($assignments)) {
                        throw new RuntimeException("Aucune description distante exploitable à mettre à jour depuis eBay.");
                    }

                    $assignments[] = "`modifyDate`='" . (int) $nowTs . "'";
                    if ($remoteListingId !== '') {
                        $assignments[] = "`ebayLastSync`='" . (int) $nowTs . "'";
                    }

                    $this->dbQuery(
                        "UPDATE `book` SET " . implode(', ', $assignments) . " WHERE `idGroup`='" . (int) $idGroup . "' AND `idBook`='" . (int) $localBookId . "' LIMIT 1"
                    );

                    // Post-update verification: avoid reporting success if the description stayed unchanged.
                    if (in_array('description', $updatedFields, true)) {
                        $checkRows = $this->dbSelect(
                            "SELECT `description` FROM `book` WHERE `idGroup`='" . (int) $idGroup . "' AND `idBook`='" . (int) $localBookId . "' LIMIT 1"
                        );
                        $savedDescription = '';
                        if (!empty($checkRows[0])) {
                            $savedDescription = (string) $this->arrayValue($checkRows[0], 'description', '');
                        }

                        if (!$this->sameText($savedDescription, $remoteDescription)) {
                            // Last chance: decode HTML entities then retry once.
                            $decodedDescription = html_entity_decode($remoteDescription, ENT_QUOTES, 'UTF-8');
                            if ($decodedDescription !== $remoteDescription) {
                                $this->dbQuery(
                                    "UPDATE `book` SET `description`='" . $this->escapeSql($decodedDescription) . "', `modifyDate`='" . (int) $nowTs . "'"
                                    . ($remoteListingId !== '' ? ", `ebayLastSync`='" . (int) $nowTs . "'" : "")
                                    . " WHERE `idGroup`='" . (int) $idGroup . "' AND `idBook`='" . (int) $localBookId . "' LIMIT 1"
                                );
                                $checkRows = $this->dbSelect(
                                    "SELECT `description` FROM `book` WHERE `idGroup`='" . (int) $idGroup . "' AND `idBook`='" . (int) $localBookId . "' LIMIT 1"
                                );
                                $savedDescription = !empty($checkRows[0]) ? (string) $this->arrayValue($checkRows[0], 'description', '') : '';
                                if ($this->sameText($savedDescription, $decodedDescription)) {
                                    $remoteDescription = $decodedDescription;
                                }
                            }
                        }

                        if (!$this->sameText($savedDescription, $remoteDescription)) {
                            throw new RuntimeException(
                                "La description locale n'a pas été mise à jour (idGroup=" . (int) $idGroup . ", idBook=" . (int) $localBookId . ")."
                            );
                        }
                    }

                    $message = "Mise à jour de gestlibr depuis eBay (" . implode(', ', $updatedFields) . ").";
                } elseif ($action === 'add' || $action === 'update') {
                    if ($localBookId <= 0) {
                        throw new RuntimeException('idBook local manquant.');
                    }
                    $bookRows = $this->dbSelect(
                        "SELECT * FROM `book` WHERE `idGroup`='" . (int) $idGroup . "' AND `idBook`='" . (int) $localBookId . "' LIMIT 1"
                    );
                    if (empty($bookRows[0])) {
                        throw new RuntimeException('Fiche ouvrage introuvable en base locale.');
                    }
                    $params = $this->buildListingParamsFromBookRow($bookRows[0]);
                    $result = ($action === 'add') ? $this->addItem($params) : $this->reviseItem($params);
                    if ($result === false) {
                        throw new RuntimeException($this->getLastErrorMessageForBook($idGroup, $localBookId));
                    }
                    $listingId = trim((string) $result);
                    $this->dbQuery(
                        "UPDATE `book` SET `onEbay`='1', `ebayLastSync`='" . (int) $nowTs . "'"
                        . ($listingId !== '' ? ", `ebayItemId`='" . $this->escapeSql($listingId) . "'" : '')
                        . " WHERE `idGroup`='" . (int) $idGroup . "' AND `idBook`='" . (int) $localBookId . "' LIMIT 1"
                    );
                    $message = ($action === 'add') ? 'Annonce ajoutée sur eBay.' : 'Annonce mise à jour sur eBay.';
                } elseif ($action === 'remove') {
                    $skuForRemove = '';
                    if ($localBookId > 0) {
                        $skuForRemove = (string) $localBookId;
                    } elseif ($remoteSku !== '') {
                        $skuForRemove = $remoteSku;
                    }
                    $endParams = array(
                        'ItemID' => $remoteListingId,
                        'SKU' => $skuForRemove,
                        'idBook' => $localBookId,
                        'idGroup' => $idGroup,
                    );
                    $ok = $this->endItem($endParams);
                    if (!$ok) {
                        throw new RuntimeException('Échec suppression annonce eBay.');
                    }
                    if ($localBookId > 0) {
                        $this->dbQuery(
                            "UPDATE `book` SET `onEbay`='0', `ebayItemId`='', `ebayLastSync`='0' WHERE `idGroup`='" . (int) $idGroup . "' AND `idBook`='" . (int) $localBookId . "' LIMIT 1"
                        );
                    }
                    $message = "Annonce retiree d'eBay.";
                } else {
                    throw new RuntimeException('Action inconnue: ' . $action);
                }
            } catch (Exception $e) {
                $status = 'error';
                $message = $e->getMessage();
            }

            $this->dbQuery(
                "UPDATE `ebay_reconcile_item` SET `actionSelected`='" . $this->escapeSql($action) . "', `status`='" . $this->escapeSql($status) . "', `message`='" . $this->escapeSql(substr((string) $message, 0, 250)) . "', `updatedAtTs`='" . (int) $nowTs . "' WHERE `id`='" . (int) $itemId . "' LIMIT 1"
            );

            if ($status === 'applied') {
                $stats['applied']++;
            } elseif ($status === 'ignored') {
                $stats['ignored']++;
            } else {
                $stats['errors']++;
            }
        }

        $runStats = $this->refreshReconcileRunStats($runId, $idUser);
        $message = 'Corrections appliquées. OK: ' . $stats['applied'] . ' | Ignorés: ' . $stats['ignored'] . ' | Erreurs: ' . $stats['errors'];
        if (!empty($runStats['status']) && $runStats['status'] !== 'running') {
            $message .= ' | Statut run: ' . $runStats['status'];
        }

        return array(
            'runId' => $runId,
            'message' => $message,
            'stats' => $stats,
            'runStats' => $runStats,
        );
    }

    protected function startReconcileRun($idGroup, $idUser, $triggerSource)
    {
        if (!$this->tableExists('ebay_reconcile_run')) {
            return 0;
        }
        $now = time();
        return (int) $this->db()->DbInsert(
            "INSERT INTO `ebay_reconcile_run` (`idGroup`, `startedAtTs`, `endedAtTs`, `triggerSource`, `status`, `message`, `idUser`, `nbItems`, `nbPending`, `nbApplied`, `nbIgnored`, `nbErrors`, `statsJson`) VALUES ('"
            . (int) $idGroup . "', '" . (int) $now . "', '0', '" . $this->escapeSql((string) $triggerSource) . "', 'running', '', '" . (int) $idUser . "', '0', '0', '0', '0', '0', '')",
            1
        );
    }

    protected function getReconcileResumeHintsFromRun($runId)
    {
        $runId = (int) $runId;
        $hints = array(
            'tradingStartPage' => 0,
            'localStartAfterIdBook' => 0,
            'remoteOfferStartOffset' => 0,
        );
        if ($runId <= 0 || !$this->tableExists('ebay_reconcile_run')) {
            return $hints;
        }

        $runRows = $this->dbSelect(
            "SELECT `statsJson` FROM `ebay_reconcile_run` WHERE `id`='" . (int) $runId . "' LIMIT 1"
        );
        if (!empty($runRows[0]['statsJson'])) {
            $stats = json_decode((string) $runRows[0]['statsJson'], true);
            if (is_array($stats)) {
                $resume = (array) $this->arrayValue($stats, 'resume', array());
                $source = trim((string) $this->arrayValue($resume, 'source', ''));
                $complete = (int) $this->arrayValue($resume, 'complete', 1);
                $nextPage = (int) $this->arrayValue($resume, 'nextPage', 0);
                if ($source === 'trading' && $complete === 0 && $nextPage > 1) {
                    $hints['tradingStartPage'] = $nextPage;
                }
                $localStartAfter = (int) $this->arrayValue($resume, 'localStartAfterIdBook', 0);
                if ($localStartAfter > 0) {
                    $hints['localStartAfterIdBook'] = $localStartAfter;
                }
                $remoteOffset = (int) $this->arrayValue($resume, 'remoteOfferStartOffset', 0);
                if ($remoteOffset > 0) {
                    $hints['remoteOfferStartOffset'] = $remoteOffset;
                }
            }
        }

        if ($hints['localStartAfterIdBook'] <= 0 && $this->tableExists('ebay_reconcile_item')) {
            $rows = $this->dbSelect(
                "SELECT MAX(`localBookId`) AS maxLocalBookId FROM `ebay_reconcile_item` WHERE `idRun`='" . (int) $runId . "' AND `localBookId`>0"
            );
            if (!empty($rows[0]['maxLocalBookId'])) {
                $hints['localStartAfterIdBook'] = (int) $rows[0]['maxLocalBookId'];
            }
        }

        return $hints;
    }

    protected function startReconcileRunContinuation($runId, $idGroup, $idUser, $triggerSource)
    {
        $runId = (int) $runId;
        $idGroup = (int) $idGroup;
        if ($runId <= 0 || !$this->tableExists('ebay_reconcile_run')) {
            return 0;
        }

        $rows = $this->dbSelect(
            "SELECT `id` FROM `ebay_reconcile_run` WHERE `id`='" . (int) $runId . "' AND `idGroup`='" . (int) $idGroup . "' LIMIT 1"
        );
        if (empty($rows[0]['id'])) {
            return 0;
        }

        $this->dbQuery(
            "UPDATE `ebay_reconcile_run` SET `endedAtTs`='0', `status`='running', `triggerSource`='"
            . $this->escapeSql((string) $triggerSource) . "', `idUser`='" . (int) $idUser
            . "', `message`='Reprise audit en cours...' WHERE `id`='" . (int) $runId . "' LIMIT 1"
        );
        return $runId;
    }

    protected function finishReconcileRun($runId, $status, $message, array $stats)
    {
        if ($runId <= 0 || !$this->tableExists('ebay_reconcile_run')) {
            return;
        }
        $this->dbQuery(
            "UPDATE `ebay_reconcile_run` SET `endedAtTs`='" . (int) time() . "', `status`='" . $this->escapeSql((string) $status) . "', `message`='"
            . $this->escapeSql((string) $message) . "', `nbItems`='" . (int) $this->arrayValue($stats, 'nbItems', 0) . "', `nbPending`='"
            . (int) $this->arrayValue($stats, 'nbPending', 0) . "', `nbApplied`='" . (int) $this->arrayValue($stats, 'nbApplied', 0) . "', `nbIgnored`='"
            . (int) $this->arrayValue($stats, 'nbIgnored', 0) . "', `nbErrors`='" . (int) $this->arrayValue($stats, 'nbErrors', 0) . "', `statsJson`='"
            . $this->escapeSql($this->jsonEncodeSafe($stats)) . "' WHERE `id`='" . (int) $runId . "' LIMIT 1"
        );
    }

    protected function refreshReconcileRunStats($runId, $idUser = 0)
    {
        $runId = (int) $runId;
        if ($runId <= 0 || !$this->tableExists('ebay_reconcile_item') || !$this->tableExists('ebay_reconcile_run')) {
            return array();
        }

        $rows = $this->dbSelect(
            "SELECT `status`, COUNT(*) AS nb FROM `ebay_reconcile_item` WHERE `idRun`='" . $runId . "' GROUP BY `status`"
        );

        $nbItems = 0;
        $nbPending = 0;
        $nbApplied = 0;
        $nbIgnored = 0;
        $nbErrors = 0;
        foreach ($rows as $row) {
            $status = (string) $this->arrayValue($row, 'status', '');
            $nb = (int) $this->arrayValue($row, 'nb', 0);
            $nbItems += $nb;
            if ($status === 'pending') {
                $nbPending += $nb;
            } elseif ($status === 'applied') {
                $nbApplied += $nb;
            } elseif ($status === 'ignored') {
                $nbIgnored += $nb;
            } elseif ($status === 'error') {
                $nbErrors += $nb;
            }
        }

        $status = 'done';
        if ($nbPending > 0) {
            $status = 'pending';
        } elseif ($nbErrors > 0) {
            $status = 'done_with_errors';
        }

        $stats = array(
            'nbItems' => $nbItems,
            'nbPending' => $nbPending,
            'nbApplied' => $nbApplied,
            'nbIgnored' => $nbIgnored,
            'nbErrors' => $nbErrors,
            'idUser' => (int) $idUser,
        );

        $this->dbQuery(
            "UPDATE `ebay_reconcile_run` SET `endedAtTs`='" . (int) time() . "', `status`='" . $this->escapeSql($status) . "', `nbItems`='" . (int) $nbItems . "', `nbPending`='"
            . (int) $nbPending . "', `nbApplied`='" . (int) $nbApplied . "', `nbIgnored`='" . (int) $nbIgnored . "', `nbErrors`='"
            . (int) $nbErrors . "', `statsJson`='" . $this->escapeSql($this->jsonEncodeSafe($stats)) . "' WHERE `id`='" . (int) $runId . "' LIMIT 1"
        );

        return array(
            'status' => $status,
            'stats' => $stats,
        );
    }

    protected function insertReconcileItem(array $itemData)
    {
        if (!$this->tableExists('ebay_reconcile_item')) {
            return;
        }
        $now = time();
        $detailsJson = $this->jsonEncodeSafe($this->arrayValue($itemData, 'details', array()));
        $this->dbQuery(
            "INSERT INTO `ebay_reconcile_item` (`idRun`, `idGroup`, `discrepancyType`, `actionSuggested`, `actionSelected`, `status`, `message`, `localBookId`, `localEbayItemId`, `remoteSku`, `remoteOfferId`, `remoteListingId`, `title`, `author`, `priceLocal`, `priceRemote`, `quantityLocal`, `quantityRemote`, `detailsJson`, `createdAtTs`, `updatedAtTs`) VALUES ('"
            . (int) $this->arrayValue($itemData, 'idRun', 0) . "', '"
            . (int) $this->arrayValue($itemData, 'idGroup', 0) . "', '"
            . $this->escapeSql((string) $this->arrayValue($itemData, 'discrepancyType', '')) . "', '"
            . $this->escapeSql((string) $this->arrayValue($itemData, 'actionSuggested', '')) . "', '', 'pending', '', '"
            . (int) $this->arrayValue($itemData, 'localBookId', 0) . "', '"
            . $this->escapeSql((string) $this->arrayValue($itemData, 'localEbayItemId', '')) . "', '"
            . $this->escapeSql((string) $this->arrayValue($itemData, 'remoteSku', '')) . "', '"
            . $this->escapeSql((string) $this->arrayValue($itemData, 'remoteOfferId', '')) . "', '"
            . $this->escapeSql((string) $this->arrayValue($itemData, 'remoteListingId', '')) . "', '"
            . $this->escapeSql(substr((string) $this->arrayValue($itemData, 'title', ''), 0, 250)) . "', '"
            . $this->escapeSql(substr((string) $this->arrayValue($itemData, 'author', ''), 0, 250)) . "', '"
            . number_format($this->normalizeFloat($this->arrayValue($itemData, 'priceLocal', 0)), 2, '.', '') . "', '"
            . number_format($this->normalizeFloat($this->arrayValue($itemData, 'priceRemote', 0)), 2, '.', '') . "', '"
            . (int) $this->arrayValue($itemData, 'quantityLocal', 0) . "', '"
            . (int) $this->arrayValue($itemData, 'quantityRemote', 0) . "', '"
            . $this->escapeSql($detailsJson) . "', '" . (int) $now . "', '" . (int) $now . "')"
        );
    }

    protected function countReconcileItemsByStatus($runId)
    {
        $runId = (int) $runId;
        if ($runId <= 0 || !$this->tableExists('ebay_reconcile_item')) {
            return array(
                'nbItems' => 0,
                'nbPending' => 0,
                'nbApplied' => 0,
                'nbIgnored' => 0,
                'nbErrors' => 0,
            );
        }

        $rows = $this->dbSelect(
            "SELECT `status`, COUNT(*) AS nb FROM `ebay_reconcile_item` WHERE `idRun`='" . (int) $runId . "' GROUP BY `status`"
        );
        $counts = array(
            'nbItems' => 0,
            'nbPending' => 0,
            'nbApplied' => 0,
            'nbIgnored' => 0,
            'nbErrors' => 0,
        );
        foreach ($rows as $row) {
            $status = (string) $this->arrayValue($row, 'status', '');
            $nb = (int) $this->arrayValue($row, 'nb', 0);
            $counts['nbItems'] += $nb;
            if ($status === 'pending') {
                $counts['nbPending'] += $nb;
            } elseif ($status === 'applied') {
                $counts['nbApplied'] += $nb;
            } elseif ($status === 'ignored') {
                $counts['nbIgnored'] += $nb;
            } elseif ($status === 'error') {
                $counts['nbErrors'] += $nb;
            }
        }
        return $counts;
    }

    protected function loadReconcileItemSignatures($runId)
    {
        $runId = (int) $runId;
        $signatures = array();
        if ($runId <= 0 || !$this->tableExists('ebay_reconcile_item')) {
            return $signatures;
        }

        $rows = $this->dbSelect(
            "SELECT `discrepancyType`, `localBookId`, `localEbayItemId`, `remoteSku`, `remoteOfferId`, `remoteListingId`, `actionSuggested`"
            . " FROM `ebay_reconcile_item` WHERE `idRun`='" . (int) $runId . "'"
        );
        foreach ($rows as $row) {
            $signature = $this->buildReconcileItemSignature($row);
            if ($signature !== '') {
                $signatures[$signature] = 1;
            }
        }
        return $signatures;
    }

    protected function buildReconcileItemSignature(array $itemData)
    {
        $parts = array(
            (string) $this->arrayValue($itemData, 'discrepancyType', ''),
            (string) (int) $this->arrayValue($itemData, 'localBookId', 0),
            trim((string) $this->arrayValue($itemData, 'localEbayItemId', '')),
            trim((string) $this->arrayValue($itemData, 'remoteSku', '')),
            trim((string) $this->arrayValue($itemData, 'remoteOfferId', '')),
            trim((string) $this->arrayValue($itemData, 'remoteListingId', '')),
            (string) $this->arrayValue($itemData, 'actionSuggested', ''),
        );
        return sha1(implode('|', $parts));
    }

    protected function createReconcileItemRow($runId, $idGroup, array $values)
    {
        return array(
            'idRun' => (int) $runId,
            'idGroup' => (int) $idGroup,
            'discrepancyType' => (string) $this->arrayValue($values, 'discrepancyType', ''),
            'actionSuggested' => (string) $this->arrayValue($values, 'actionSuggested', ''),
            'localBookId' => (int) $this->arrayValue($values, 'localBookId', 0),
            'localEbayItemId' => (string) $this->arrayValue($values, 'localEbayItemId', ''),
            'remoteSku' => (string) $this->arrayValue($values, 'remoteSku', ''),
            'remoteOfferId' => (string) $this->arrayValue($values, 'remoteOfferId', ''),
            'remoteListingId' => (string) $this->arrayValue($values, 'remoteListingId', ''),
            'title' => (string) $this->arrayValue($values, 'title', ''),
            'author' => (string) $this->arrayValue($values, 'author', ''),
            'priceLocal' => $this->normalizeFloat($this->arrayValue($values, 'priceLocal', 0)),
            'priceRemote' => $this->normalizeFloat($this->arrayValue($values, 'priceRemote', 0)),
            'quantityLocal' => (int) $this->arrayValue($values, 'quantityLocal', 0),
            'quantityRemote' => (int) $this->arrayValue($values, 'quantityRemote', 0),
            'details' => (array) $this->arrayValue($values, 'details', array()),
        );
    }

    protected function fetchAllOffersForReconcile($maxOffers = 500)
    {
        $limit = 200;
        $offset = 0;
        $result = array();
        $seen = 0;
        $marketplaceId = (string) $this->getMarketplaceId();
        while (true) {
            $payload = $this->requestJson('GET', '/sell/inventory/v1/offer', array(
                'query' => array(
                    'marketplace_id' => $marketplaceId,
                    'limit' => $limit,
                    'offset' => $offset,
                ),
            ));
            $offers = (!empty($payload['offers']) && is_array($payload['offers'])) ? $payload['offers'] : array();
            if (empty($offers)) {
                break;
            }
            foreach ($offers as $offer) {
                $result[] = (array) $offer;
                $seen++;
                if ($seen >= $maxOffers) {
                    return $result;
                }
            }
            $offset += count($offers);
            $total = (int) $this->arrayValue($payload, 'total', 0);
            if ($total > 0 && $offset >= $total) {
                break;
            }
            if (count($offers) < $limit) {
                break;
            }
        }
        return $result;
    }

    protected function fetchAllOffersViaTradingApi($maxOffers = 500, $maxDurationSeconds = 120, $startPage = 1, &$nextPage = 1, &$isComplete = true)
    {
        $maxOffers = max(1, (int) $maxOffers);
        $maxDurationSeconds = max(20, (int) $maxDurationSeconds);
        $perPage = 200;
        $page = max(1, (int) $startPage);
        $result = array();
        $startedAt = time();
        $nextPage = $page;
        $isComplete = true;

        while (count($result) < $maxOffers) {
            if ((time() - $startedAt) >= $maxDurationSeconds) {
                $isComplete = false;
                $nextPage = $page;
                break;
            }

            $xml = $this->tradingCall('GetMyeBaySelling', $this->buildGetMyeBaySellingRequestXml($page, $perPage));
            $activeListNodes = $xml->xpath('/*[local-name()="GetMyeBaySellingResponse"]/*[local-name()="ActiveList"]');
            $activeList = (!empty($activeListNodes) && isset($activeListNodes[0])) ? $activeListNodes[0] : null;
            if (!$activeList) {
                break;
            }

            $items = $activeList->xpath('./*[local-name()="ItemArray"]/*[local-name()="Item"]');
            if ($items === false) {
                $items = array();
            }

            if (empty($items)) {
                break;
            }

            foreach ($items as $item) {
                if (count($result) >= $maxOffers) {
                    break;
                }
                $itemId = $this->xmlNodeText($item, './*[local-name()="ItemID"]');
                $sku = $this->xmlNodeText($item, './*[local-name()="SKU"]');
                $title = $this->xmlNodeText($item, './*[local-name()="Title"]');
                $qty = (int) $this->xmlNodeText($item, './*[local-name()="Quantity"]');
                $priceNode = $item->xpath('./*[local-name()="SellingStatus"]/*[local-name()="CurrentPrice"]');
                $priceValue = '';
                $priceCurrency = 'EUR';
                if (!empty($priceNode) && isset($priceNode[0])) {
                    $priceValue = trim((string) $priceNode[0]);
                    $currencyAttr = trim((string) $priceNode[0]['currencyID']);
                    if ($currencyAttr !== '') {
                        $priceCurrency = $currencyAttr;
                    }
                }

                $result[] = array(
                    'offerId' => ($itemId !== '' ? ('TRADING:' . $itemId) : ''),
                    'listingId' => $itemId,
                    'sku' => $sku,
                    'availableQuantity' => $qty,
                    'pricingSummary' => array(
                        'price' => array(
                            'value' => $priceValue,
                            'currency' => $priceCurrency,
                        ),
                    ),
                );
            }

            $totalPages = (int) $this->xmlNodeText(
                $activeList,
                './*[local-name()="PaginationResult"]/*[local-name()="TotalNumberOfPages"]'
            );
            if ($totalPages <= 0) {
                $totalPages = 1;
            }

            if ($page >= $totalPages || count($items) < $perPage) {
                $nextPage = $page + 1;
                break;
            }

            $page++;
            $nextPage = $page;
        }

        if (count($result) >= $maxOffers) {
            $isComplete = false;
        }

        return $result;
    }

    protected function xmlNodeText($xmlNode, $xpathExpression)
    {
        if (!($xmlNode instanceof SimpleXMLElement)) {
            return '';
        }
        $nodes = $xmlNode->xpath((string) $xpathExpression);
        if (empty($nodes) || !isset($nodes[0])) {
            return '';
        }
        return trim((string) $nodes[0]);
    }

    protected function buildGetMyeBaySellingRequestXml($page, $entriesPerPage)
    {
        $page = max(1, (int) $page);
        $entriesPerPage = max(1, min(200, (int) $entriesPerPage));
        return '<?xml version="1.0" encoding="utf-8"?>'
            . '<GetMyeBaySellingRequest xmlns="urn:ebay:apis:eBLBaseComponents">'
            . '<WarningLevel>High</WarningLevel>'
            . '<DetailLevel>ReturnAll</DetailLevel>'
            . '<ActiveList><Include>true</Include><Pagination><EntriesPerPage>' . $entriesPerPage . '</EntriesPerPage><PageNumber>' . $page . '</PageNumber></Pagination></ActiveList>'
            . '</GetMyeBaySellingRequest>';
    }

    protected function buildGetItemRequestXml($itemId)
    {
        $itemId = trim((string) $itemId);
        if ($itemId === '') {
            return '';
        }

        $escapedItemId = htmlspecialchars($itemId, ENT_QUOTES, 'UTF-8');
        return '<?xml version="1.0" encoding="utf-8"?>'
            . '<GetItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">'
            . '<WarningLevel>High</WarningLevel>'
            . '<DetailLevel>ReturnAll</DetailLevel>'
            . '<ItemID>' . $escapedItemId . '</ItemID>'
            . '</GetItemRequest>';
    }

    protected function getTradingItemDescriptionMeta($itemId)
    {
        $itemId = trim((string) $itemId);
        if ($itemId === '') {
            return array(
                'value' => '',
                'source' => 'trading_item',
                'known' => 0,
            );
        }

        try {
            $requestXml = $this->buildGetItemRequestXml($itemId);
            if ($requestXml === '') {
                return array(
                    'value' => '',
                    'source' => 'trading_item',
                    'known' => 0,
                );
            }

            $xml = $this->tradingCall('GetItem', $requestXml);
            $itemNodes = $xml->xpath('/*[local-name()="GetItemResponse"]/*[local-name()="Item"]');
            if (empty($itemNodes) || !isset($itemNodes[0])) {
                return array(
                    'value' => '',
                    'source' => 'trading_item',
                    'known' => 0,
                );
            }

            $descriptionNodes = $itemNodes[0]->xpath('./*[local-name()="Description"]');
            if ($descriptionNodes === false || empty($descriptionNodes) || !isset($descriptionNodes[0])) {
                return array(
                    'value' => '',
                    'source' => 'trading_item',
                    'known' => 1,
                );
            }

            return array(
                'value' => trim((string) $descriptionNodes[0]),
                'source' => 'trading_item',
                'known' => 1,
            );
        } catch (Throwable $e) {
            return array(
                'value' => '',
                'source' => 'trading_item_error',
                'known' => 0,
            );
        }
    }

    protected function tradingCall($callName, $requestXml)
    {
        $callName = trim((string) $callName);
        if ($callName === '') {
            throw new RuntimeException('Nom d’appel Trading manquant.');
        }

        $requestXml = (string) $requestXml;
        if (trim($requestXml) === '') {
            throw new RuntimeException('Payload Trading API vide.');
        }

        $siteId = $this->getTradingSiteId();
        $response = $this->http->request('POST', rtrim($this->getApiBaseUrl(), '/') . '/ws/api.dll', array(
            'timeout' => 60,
            'connect_timeout' => 15,
            'headers' => array(
                'Content-Type' => 'text/xml',
                'X-EBAY-API-CALL-NAME' => $callName,
                'X-EBAY-API-COMPATIBILITY-LEVEL' => '967',
                'X-EBAY-API-SITEID' => (string) $siteId,
                'X-EBAY-API-IAF-TOKEN' => $this->getAccessToken(),
            ),
            'body' => $requestXml,
            'http_errors' => false,
        ));

        $statusCode = (int) $response->getStatusCode();
        $rawBody = (string) $response->getBody();
        if ($statusCode >= 400) {
            throw new RuntimeException('Trading API HTTP ' . $statusCode . ' - ' . substr($rawBody, 0, 300));
        }
        if (trim($rawBody) === '') {
            throw new RuntimeException('Trading API réponse vide.');
        }

        $xml = @simplexml_load_string($rawBody);
        if (!$xml) {
            throw new RuntimeException('Trading API XML invalide.');
        }

        $ack = strtoupper(trim((string) (isset($xml->Ack) ? $xml->Ack : '')));
        if ($ack === '' || ($ack !== 'SUCCESS' && $ack !== 'WARNING')) {
            $errorCode = '';
            $errorMessage = '';
            if (isset($xml->Errors)) {
                $firstError = $xml->Errors;
                $errorCode = trim((string) (isset($firstError->ErrorCode) ? $firstError->ErrorCode : ''));
                $errorMessage = trim((string) (isset($firstError->LongMessage) ? $firstError->LongMessage : (isset($firstError->ShortMessage) ? $firstError->ShortMessage : '')));
            }
            $suffix = trim($errorCode . ' ' . $errorMessage);
            throw new RuntimeException('Trading API ' . $callName . ' en erreur' . ($suffix !== '' ? (': ' . $suffix) : '.'));
        }

        return $xml;
    }

    protected function getTradingSiteId()
    {
        $marketplaceId = strtoupper(trim((string) $this->getMarketplaceId()));
        $map = array(
            'EBAY_US' => 0,
            'EBAY_ENCA' => 2,
            'EBAY_GB' => 3,
            'EBAY_AU' => 15,
            'EBAY_AT' => 16,
            'EBAY_FR' => 71,
            'EBAY_DE' => 77,
            'EBAY_IT' => 101,
            'EBAY_ES' => 186,
            'EBAY_MOTORS_US' => 100,
        );

        if (isset($map[$marketplaceId])) {
            return (int) $map[$marketplaceId];
        }

        return 0;
    }

    protected function buildReconcileOfferKey(array $offer, $index = 0)
    {
        $offerId = trim((string) $this->arrayValue($offer, 'offerId', ''));
        if ($offerId !== '') {
            return 'offer:' . $offerId;
        }

        $listingId = trim((string) $this->arrayValue($offer, 'listingId', ''));
        if ($listingId !== '') {
            return 'listing:' . $listingId;
        }

        $sku = trim((string) $this->arrayValue($offer, 'sku', ''));
        if ($sku !== '') {
            return 'sku:' . $sku . ':' . (int) $index;
        }

        return 'idx:' . (int) $index;
    }

    protected function extractRemoteIdBookFromOffer(array $offer, &$rawIdentifier = '')
    {
        $candidates = array(
            $this->arrayValue($offer, 'sku', ''),
            $this->arrayValue($offer, 'customLabel', ''),
            $this->arrayValue($offer, 'customSku', ''),
            $this->arrayValue($offer, 'merchantSku', ''),
        );

        $rawIdentifier = '';
        foreach ($candidates as $candidate) {
            $value = trim((string) $candidate);
            if ($value === '') {
                continue;
            }

            if ($rawIdentifier === '') {
                $rawIdentifier = $value;
            }

            $parsedId = $this->parseIdBookCandidate($value);
            if ($parsedId > 0) {
                return $parsedId;
            }
        }

        return 0;
    }

    protected function parseIdBookCandidate($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return 0;
        }

        if (preg_match('/^[0-9]+$/', $value)) {
            $idBook = (int) $value;
            return ($idBook > 0 ? $idBook : 0);
        }

        // Tolère quelques formats historiques du libellé personnalisé: "idbook:12345", "BOOK-12345", etc.
        if (preg_match('/(?:^|[^0-9])([1-9][0-9]{1,9})(?:[^0-9]|$)/', $value, $matches)) {
            $idBook = (int) $matches[1];
            return ($idBook > 0 ? $idBook : 0);
        }

        return 0;
    }

    protected function describeReconcileLocalLinkStatus($localListingId, $remoteListingId)
    {
        $localListingId = trim((string) $localListingId);
        $remoteListingId = trim((string) $remoteListingId);

        if ($localListingId === '') {
            return array('code' => 'missing', 'label' => 'ebayItemId absent');
        }

        if ($remoteListingId !== '' && $localListingId !== $remoteListingId) {
            return array('code' => 'mismatch', 'label' => 'ebayItemId incoherent');
        }

        return array('code' => 'ok', 'label' => 'ebayItemId coherent');
    }

    protected function fetchInventoryItemForReconcile($sku, array &$inventoryCache)
    {
        $sku = trim((string) $sku);
        if ($sku === '') {
            return array();
        }
        if (!$this->isInventorySkuCompatible($sku)) {
            $inventoryCache[$sku] = array();
            return $inventoryCache[$sku];
        }
        if (array_key_exists($sku, $inventoryCache)) {
            return $inventoryCache[$sku];
        }
        try {
            $inventoryCache[$sku] = $this->requestJson('GET', '/sell/inventory/v1/inventory_item/' . rawurlencode($sku), array());
        } catch (Throwable $e) {
            $inventoryCache[$sku] = array();
        }
        return $inventoryCache[$sku];
    }

    protected function isInventorySkuCompatible($sku)
    {
        $sku = trim((string) $sku);
        if ($sku === '') {
            return false;
        }
        if (strlen($sku) > 50) {
            return false;
        }
        return (bool) preg_match('/^[A-Za-z0-9]+$/', $sku);
    }

    protected function shouldForceTradingListingForReconcile($idGroup = null)
    {
        $idGroup = $this->normalizeGroupId($idGroup);
        if ($idGroup <= 0) {
            return false;
        }
        $value = strtolower(trim((string) $this->getEbayGroupSetting($idGroup, 'reconcile_listing_source', '')));
        return ($value === 'trading');
    }

    protected function isInventoryOfferListingInvalidSkuError($message)
    {
        $message = strtolower(trim((string) $message));
        if ($message === '') {
            return false;
        }
        if (strpos($message, '25707') !== false) {
            return true;
        }
        if (strpos($message, 'invalid value for a sku') !== false) {
            return true;
        }
        return false;
    }

    protected function extractOfferPrice(array $offer)
    {
        $pricingSummary = (array) $this->arrayValue($offer, 'pricingSummary', array());
        $price = (array) $this->arrayValue($pricingSummary, 'price', array());
        return $this->normalizeFloat($this->arrayValue($price, 'value', 0));
    }

    protected function extractOfferQuantity(array $offer)
    {
        return (int) $this->arrayValue($offer, 'availableQuantity', 0);
    }

    protected function normalizeFloat($value)
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', $value);
        }
        return (float) $value;
    }

    protected function normalizeText($value)
    {
        $value = html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', trim($value));
        return strtolower((string) $value);
    }

    protected function sameText($left, $right)
    {
        return $this->normalizeText($left) === $this->normalizeText($right);
    }

    protected function extractRemoteAuthor(array $inventoryItem)
    {
        $product = (array) $this->arrayValue($inventoryItem, 'product', array());
        $aspects = (array) $this->arrayValue($product, 'aspects', array());
        $keys = array('Auteur', 'Author', 'AUTEUR', 'AUTHOR');
        foreach ($keys as $key) {
            if (!empty($aspects[$key]) && is_array($aspects[$key]) && !empty($aspects[$key][0])) {
                return trim((string) $aspects[$key][0]);
            }
        }
        return '';
    }

    protected function extractRemoteDescriptionForReconcile(
        array $offer,
        array $inventoryItem,
        array &$offerDetailsCache,
        array &$tradingDescriptionCache = array(),
        &$tradingDescriptionLookupCount = 0,
        $maxTradingDescriptionLookups = 0,
        &$tradingDescriptionLookupLimitReached = false
    )
    {
        $product = (array) $this->arrayValue($inventoryItem, 'product', array());
        $hasInventoryProduct = (!empty($inventoryItem) && array_key_exists('product', $inventoryItem));
        $inventoryDescription = trim((string) $this->arrayValue($product, 'description', ''));
        if ($hasInventoryProduct && $inventoryDescription !== '') {
            return array(
                'value' => $inventoryDescription,
                'source' => 'inventory',
                'known' => 1,
            );
        }

        $offerHasListingDescription = array_key_exists('listingDescription', $offer);
        $offerDescription = trim((string) $this->arrayValue($offer, 'listingDescription', ''));
        if ($offerHasListingDescription && $offerDescription !== '') {
            return array(
                'value' => $offerDescription,
                'source' => 'offer_list',
                'known' => 1,
            );
        }

        $offerId = trim((string) $this->arrayValue($offer, 'offerId', ''));
        if ($offerId !== '' && stripos($offerId, 'TRADING:') !== 0) {
            if (!array_key_exists($offerId, $offerDetailsCache)) {
                $offerDetailsCache[$offerId] = $this->getOfferById($offerId);
            }
            $offerDetails = (array) $offerDetailsCache[$offerId];
            $detailsHasListingDescription = array_key_exists('listingDescription', $offerDetails);
            $detailsDescription = trim((string) $this->arrayValue($offerDetails, 'listingDescription', ''));
            if ($detailsHasListingDescription) {
                return array(
                    'value' => $detailsDescription,
                    'source' => 'offer_detail',
                    'known' => 1,
                );
            }
        }

        $listingId = trim((string) $this->arrayValue($offer, 'listingId', ''));
        if ($listingId !== '') {
            if (array_key_exists($listingId, $tradingDescriptionCache)) {
                return (array) $tradingDescriptionCache[$listingId];
            }
            if ($maxTradingDescriptionLookups > 0 && (int) $tradingDescriptionLookupCount >= (int) $maxTradingDescriptionLookups) {
                $tradingDescriptionLookupLimitReached = true;
                return array(
                    'value' => '',
                    'source' => 'trading_item_limited',
                    'known' => 0,
                );
            }
            if ($maxTradingDescriptionLookups !== 0) {
                $tradingDescriptionLookupCount = (int) $tradingDescriptionLookupCount + 1;
            }
            $tradingMeta = $this->getTradingItemDescriptionMeta($listingId);
            $tradingDescriptionCache[$listingId] = $tradingMeta;
            if ((int) $this->arrayValue($tradingMeta, 'known', 0) === 1) {
                return (array) $tradingMeta;
            }
        }

        if ($hasInventoryProduct) {
            return array(
                'value' => $inventoryDescription,
                'source' => 'inventory',
                'known' => 1,
            );
        }

        if ($offerHasListingDescription) {
            return array(
                'value' => $offerDescription,
                'source' => 'offer_list',
                'known' => 1,
            );
        }

        return array(
            'value' => '',
            'source' => 'unknown',
            'known' => 0,
        );
    }

    protected function normalizeImageUrlList($values)
    {
        $urls = array();
        if (!is_array($values)) {
            return $urls;
        }
        foreach ($values as $value) {
            $url = trim((string) $value);
            if ($url === '') {
                continue;
            }
            if (!in_array($url, $urls, true)) {
                $urls[] = $url;
            }
        }
        return $urls;
    }

    protected function sameImageList(array $left, array $right)
    {
        if (count($left) !== count($right)) {
            return false;
        }
        $i = 0;
        while ($i < count($left)) {
            if (trim((string) $left[$i]) !== trim((string) $right[$i])) {
                return false;
            }
            $i++;
        }
        return true;
    }

    protected function findBookSuggestions($idGroup, $title, $author, $limit = 5)
    {
        $idGroup = (int) $idGroup;
        $limit = max(1, min(10, (int) $limit));
        $title = trim((string) $title);
        $author = trim((string) $author);
        $needles = array();
        if ($title !== '') {
            $needles[] = $title;
        }
        if ($author !== '' && $this->normalizeText($author) !== $this->normalizeText($title)) {
            $needles[] = $author;
        }
        if (empty($needles)) {
            return array();
        }

        $whereParts = array();
        $scoreParts = array();
        foreach ($needles as $needle) {
            $escaped = $this->escapeSql($needle);
            $prefix = $this->escapeSql($needle . '%');
            $contains = $this->escapeSql('%' . $needle . '%');
            $whereParts[] = "(LOWER(`author`) LIKE LOWER('" . $contains . "') OR LOWER(`title`) LIKE LOWER('" . $contains . "'))";
            $scoreParts[] = "(CASE WHEN LOWER(`title`) LIKE LOWER('" . $prefix . "') THEN 40 WHEN LOWER(`author`) LIKE LOWER('" . $prefix . "') THEN 30 WHEN LOWER(`title`) LIKE LOWER('" . $contains . "') THEN 20 WHEN LOWER(`author`) LIKE LOWER('" . $contains . "') THEN 10 ELSE 0 END)";
        }
        if (empty($whereParts)) {
            return array();
        }

        $rows = $this->dbSelect(
            "SELECT `idBook`, `author`, `title`, (" . implode(' + ', $scoreParts) . ") AS score "
            . "FROM `book` WHERE `idGroup`='" . (int) $idGroup . "' AND (" . implode(' OR ', $whereParts) . ") "
            . "ORDER BY score DESC, `modifyDate` DESC, `idBook` DESC LIMIT " . (int) $limit
        );

        $result = array();
        foreach ($rows as $row) {
            $result[] = array(
                'idBook' => (int) $this->arrayValue($row, 'idBook', 0),
                'author' => (string) $this->arrayValue($row, 'author', ''),
                'title' => (string) $this->arrayValue($row, 'title', ''),
                'score' => (int) $this->arrayValue($row, 'score', 0),
            );
        }
        return $result;
    }

    protected function linkOrphanToBook($idGroup, $idBook, $remoteSku, $remoteOfferId, $remoteListingId)
    {
        $idGroup = (int) $idGroup;
        $idBook = (int) $idBook;
        $remoteSku = trim((string) $remoteSku);
        $remoteOfferId = trim((string) $remoteOfferId);
        $remoteListingId = trim((string) $remoteListingId);
        if ($idGroup <= 0 || $idBook <= 0) {
            throw new RuntimeException('idGroup/idBook invalide pour lier un orphelin.');
        }
        if ($remoteSku === '') {
            $remoteSku = (string) $idBook;
        }

        $this->saveSuccessState($idGroup, $idBook, $remoteSku, $remoteOfferId, $remoteListingId, 'PUBLISHED');
        $this->dbQuery(
            "UPDATE `book` SET `ebayItemId`='" . $this->escapeSql($remoteListingId) . "', `onEbay`='1', `ebayLastSync`='" . (int) time() . "' WHERE `idGroup`='" . (int) $idGroup . "' AND `idBook`='" . (int) $idBook . "' LIMIT 1"
        );
    }

    protected function upsertListing(array $params, $isUpdate)
    {
        $sku = $this->resolveSku($params);
        $idBook = (int) $this->arrayValue($params, 'idBook', 0);
        $normalizedSku = $this->normalizeSkuForListing($sku, $idBook);
        if ($normalizedSku === '') {
            $message = 'SKU eBay invalide: idBook numérique obligatoire pour publication/mise à jour.';
            $idGroup = $this->resolveGroupIdFromParams($params);
            if ($idGroup > 0) {
                $this->setContextGroupId($idGroup);
            }
            $this->saveErrorState($idGroup, $idBook, $sku, $isUpdate ? 'UPDATE_ERROR' : 'ADD_ERROR', $message);
            return false;
        }
        $sku = $normalizedSku;
        if ($idBook <= 0 && ctype_digit($sku)) {
            $idBook = (int) $sku;
        }
        $idGroup = $this->resolveGroupIdFromParams($params);
        if ($idGroup > 0) {
            $this->setContextGroupId($idGroup);
        }
        $configError = $this->getOfferConfigurationError($idGroup, $params);
        if ($configError !== '') {
            $this->saveErrorState($idGroup, $idBook, $sku, 'CONFIG_ERROR', $configError);
            return false;
        }

        if (!$this->hasValidListingPrice($params)) {
            $this->saveErrorState($idGroup, $idBook, $sku, 'PRICE_MISSING', $this->missingListingPriceMessage());
            return false;
        }

        if (!$this->isSellerRegistrationCompleted()) {
            $this->saveErrorState(
                $idGroup,
                $idBook,
                $sku,
                'SELLER_NOT_READY',
                'Compte vendeur eBay (' . $this->getEbayEnvironment() . ') non activé pour cet utilisateur. Activez le mode vendeur sur eBay.'
            );
            return false;
        }

        try {
            $this->upsertInventoryItem($sku, $params);

            $offer = $this->findOfferBySku($sku);
            $offerId = '';
            if (!empty($offer) && !empty($offer['offerId'])) {
                $offerId = (string) $offer['offerId'];
                $offerDetails = $this->getOfferById($offerId);
                if (!empty($offerDetails)) {
                    $offer = array_merge($offer, $offerDetails);
                }
                try {
                    $this->updateOffer($offerId, $sku, $params, $offer);
                } catch (Exception $e) {
                    if ($this->isOfferUnavailableError($e->getMessage())) {
                        // Offer can exist in stale state on eBay. Recreate it cleanly.
                        $this->safeDeleteOffer($offerId);
                        $offerId = $this->createOffer($sku, $params);
                    } else {
                        throw $e;
                    }
                }
            } else {
                $offerId = $this->createOffer($sku, $params);
            }

            try {
                $publishPayload = $this->publishOffer($offerId);
            } catch (Exception $e) {
                if ($this->isOfferUnavailableError($e->getMessage())) {
                    $this->safeDeleteOffer($offerId);
                    $offerId = $this->createOffer($sku, $params);
                    $publishPayload = $this->publishOffer($offerId);
                } else {
                    throw $e;
                }
            }
            $listingId = (string) $this->arrayValue($publishPayload, 'listingId', '');
            if ($listingId === '' && !empty($offer['listingId'])) {
                $listingId = (string) $offer['listingId'];
            }

            $this->saveSuccessState($idGroup, $idBook, $sku, $offerId, $listingId, 'PUBLISHED');
            return $listingId !== '' ? $listingId : true;
        } catch (Exception $e) {
            $this->saveErrorState($idGroup, $idBook, $sku, $isUpdate ? 'UPDATE_ERROR' : 'ADD_ERROR', $e->getMessage());
            return false;
        }
    }

    protected function upsertInventoryItem($sku, array $params)
    {
        $payload = $this->buildInventoryPayload($sku, $params, true);

        $this->requestJson('PUT', '/sell/inventory/v1/inventory_item/' . rawurlencode($sku), array(
            'json' => $payload,
            'headers' => array(
                'Content-Language' => 'fr-FR',
            ),
        ));
    }

    protected function createOffer($sku, array $params)
    {
        $payload = $this->buildOfferPayload($sku, $params);
        $response = $this->requestJson('POST', '/sell/inventory/v1/offer', array(
            'json' => $payload,
            'headers' => array(
                'Content-Language' => 'fr-FR',
            ),
        ));

        $offerId = (string) $this->arrayValue($response, 'offerId', '');
        if ($offerId === '') {
            throw new RuntimeException('eBay createOffer: offerId manquant.');
        }
        return $offerId;
    }

    protected function updateOffer($offerId, $sku, array $params, array $existingOffer)
    {
        $payload = $this->buildOfferPayload($sku, $params, $existingOffer);
        $this->requestJson('PUT', '/sell/inventory/v1/offer/' . rawurlencode($offerId), array(
            'json' => $payload,
            'headers' => array(
                'Content-Language' => 'fr-FR',
            ),
        ));
    }

    protected function publishOffer($offerId)
    {
        return $this->requestJson('POST', '/sell/inventory/v1/offer/' . rawurlencode($offerId) . '/publish', array(
            'json' => new stdClass(),
        ));
    }

    protected function withdrawOffer($offerId)
    {
        $this->requestJson('POST', '/sell/inventory/v1/offer/' . rawurlencode($offerId) . '/withdraw', array(
            'query' => array(
                'marketplaceId' => $this->getMarketplaceId(),
            ),
            'json' => array(
                'reasonForHiding' => 'OTHER',
            ),
        ));
    }

    protected function findOfferBySku($sku)
    {
        $sku = trim((string) $sku);
        if (!$this->isInventorySkuCompatible($sku)) {
            return array();
        }
        try {
            $payload = $this->requestJson('GET', '/sell/inventory/v1/offer', array(
                'query' => array(
                    'sku' => $sku,
                    'marketplace_id' => $this->getMarketplaceId(),
                    'limit' => 1,
                    'offset' => 0,
                ),
            ));
        } catch (Exception $e) {
            $message = $e->getMessage();
            if (
                $this->isOfferUnavailableError($message) ||
                stripos($message, '404') !== false ||
                stripos($message, 'resource not found') !== false
            ) {
                return array();
            }
            throw $e;
        }

        if (empty($payload['offers']) || !is_array($payload['offers']) || empty($payload['offers'][0])) {
            return array();
        }

        return $payload['offers'][0];
    }

    protected function getOfferById($offerId)
    {
        $offerId = trim((string) $offerId);
        if ($offerId === '') {
            return array();
        }

        try {
            return $this->requestJson('GET', '/sell/inventory/v1/offer/' . rawurlencode($offerId), array());
        } catch (Exception $e) {
            return array();
        }
    }

    protected function isOfferUnavailableError($message)
    {
        $message = strtolower(trim((string) $message));
        if ($message === '') {
            return false;
        }
        return (
            strpos($message, '25713') !== false ||
            strpos($message, 'this offer is not available') !== false ||
            strpos($message, 'offer is not available') !== false
        );
    }

    protected function safeDeleteOffer($offerId)
    {
        $offerId = trim((string) $offerId);
        if ($offerId === '') {
            return;
        }

        try {
            $this->requestJson('DELETE', '/sell/inventory/v1/offer/' . rawurlencode($offerId), array());
        } catch (Exception $e) {
            // Best effort cleanup; ignore if already gone/not deletable.
        }
    }

    protected function buildInventoryPayload($sku, array $params, $withMediaUpload = false)
    {
        $idGroup = $this->resolveGroupIdFromParams($params);
        $marketplaceId = (string) $this->getMarketplaceId($idGroup);
        $categoryId = $this->getFixedCategoryId($idGroup);
        $fullTitle = $this->composeListingTitleFromParams($params);
        $title = $this->truncateTitleForEbay($fullTitle, 80);
        if ($title === '') {
            $title = 'Livre ' . $sku;
        }

        $description = $this->buildEffectiveEbayDescription($params, $idGroup, '');
        $quantity = max(0, (int) $this->arrayValue($params, 'quantity', 0));
        $aspects = $this->buildInventoryAspects($idGroup, $marketplaceId, $categoryId, $params, $fullTitle);

        $payload = array(
            'availability' => array(
                'shipToLocationAvailability' => array(
                    'quantity' => $quantity,
                ),
            ),
            'condition' => 'USED_GOOD',
            'product' => array(
                'title' => $title,
                'description' => $description,
            ),
        );

        if (!empty($aspects)) {
            $payload['product']['aspects'] = $aspects;
        }

        $weightGrams = (int) $this->arrayValue($params, 'weight_g', 0);
        if ($weightGrams > 0) {
            $weightKg = round(((float) $weightGrams) / 1000.0, 3);
            if ($weightKg <= 0) {
                $weightKg = 0.001;
            }
            $payload['packageWeightAndSize'] = array(
                'weight' => array(
                    'value' => number_format($weightKg, 3, '.', ''),
                    'unit' => 'KILOGRAM',
                ),
            );
        }

        $images = $this->resolveInventoryImageUrls($params, (bool) $withMediaUpload);
        if (!empty($images)) {
            $payload['product']['imageUrls'] = $images;
        }

        return $payload;
    }

    protected function buildOfferPayload($sku, array $params, array $existing = array())
    {
        $idGroup = $this->resolveGroupIdFromParams($params);
        $marketplaceId = (string) $this->getMarketplaceId($idGroup);
        $merchantLocationKey = (string) $this->resolveMerchantLocationKey($idGroup);
        $resolvedPolicies = $this->resolvePolicyIdsForOffer($idGroup, $params);
        $paymentPolicyId = (string) $resolvedPolicies['paymentPolicyId'];
        $fulfillmentPolicyId = (string) $resolvedPolicies['fulfillmentPolicyId'];
        $returnPolicyId = (string) $resolvedPolicies['returnPolicyId'];

        if ($merchantLocationKey === '' || $paymentPolicyId === '' || $fulfillmentPolicyId === '' || $returnPolicyId === '') {
            throw new RuntimeException('Configuration eBay REST incomplète: merchant location / policies manquants.');
        }

        $categoryId = $this->getFixedCategoryId($idGroup);
        $price = $this->resolveListingPrice($params);
        if ($price <= 0) {
            throw new RuntimeException($this->missingListingPriceMessage());
        }

        $existingListingDescription = trim((string) $this->arrayValue($existing, 'listingDescription', ''));
        $description = $this->buildEffectiveEbayDescription($params, $idGroup, $existingListingDescription);

        $payload = array();
        $preserveKeys = array(
            'listingPolicies',
            'tax',
            'includeCatalogProductDetails',
            'storeCategoryNames',
        );
        foreach ($preserveKeys as $preserveKey) {
            if (array_key_exists($preserveKey, $existing)) {
                $payload[$preserveKey] = $existing[$preserveKey];
            }
        }

        $payload['sku'] = (string) $sku;
        $payload['marketplaceId'] = $marketplaceId;
        $payload['format'] = 'FIXED_PRICE';
        $payload['listingDuration'] = 'GTC';
        $payload['availableQuantity'] = max(0, (int) $this->arrayValue($params, 'quantity', 0));
        $payload['categoryId'] = $categoryId;
        $payload['listingDescription'] = $description;
        $payload['listingPolicies'] = array(
            'fulfillmentPolicyId' => $fulfillmentPolicyId,
            'paymentPolicyId' => $paymentPolicyId,
            'returnPolicyId' => $returnPolicyId,
        );
        $payload['pricingSummary'] = array(
            'price' => array(
                'value' => number_format($price, 2, '.', ''),
                'currency' => 'EUR',
            ),
        );
        $payload['merchantLocationKey'] = $merchantLocationKey;
        $payload['quantityLimitPerBuyer'] = 1;

        $idBook = (int) $this->arrayValue($params, 'idBook', 0);
        $storeCategoryNames = $this->resolveStoreCategoryNamesForBook($idGroup, $idBook);
        if (!empty($storeCategoryNames)) {
            $payload['storeCategoryNames'] = $storeCategoryNames;
        } else if (isset($payload['storeCategoryNames'])) {
            unset($payload['storeCategoryNames']);
        }

        return $payload;
    }

    protected function buildEffectiveEbayDescription(array $params, $idGroup, $fallbackDescription = '')
    {
        $idGroup = $this->normalizeGroupId($idGroup);
        $rawDescription = trim((string) $this->arrayValue($params, 'description', ''));
        $fallbackDescription = trim((string) $fallbackDescription);
        $baseDescription = ($rawDescription !== '') ? $rawDescription : $fallbackDescription;

        $useTemplate = ((int) $this->arrayValue($params, 'ebayUseDescriptionTemplate', 0) === 1);
        if (!$useTemplate) {
            return $this->clampEbayDescription($baseDescription, $fallbackDescription);
        }

        $groupSettings = $this->getGroupSettingsRow($idGroup);
        $headerHtml = $this->normalizeTemplateHtml((string) $this->arrayValue($groupSettings, 'descriptionHeaderHtml', ''));
        $footerHtml = $this->normalizeTemplateHtml((string) $this->arrayValue($groupSettings, 'descriptionFooterHtml', ''));

        $parts = array();
        if ($headerHtml !== '') {
            $parts[] = $headerHtml;
        }
        if ($baseDescription !== '') {
            $parts[] = $baseDescription;
        }
        if ($footerHtml !== '') {
            $parts[] = $footerHtml;
        }

        if (empty($parts)) {
            return $this->clampEbayDescription($baseDescription, $fallbackDescription);
        }

        return $this->clampEbayDescription(implode("\n\n", $parts), $baseDescription);
    }

    protected function clampEbayDescription($value, $fallback = '')
    {
        $value = trim((string) $value);
        $fallback = trim((string) $fallback);
        if ($value === '') {
            $value = $fallback;
        }
        if ($value === '') {
            $value = 'Description indisponible.';
        }

        $maxLength = 4000;
        if (function_exists('mb_strlen')) {
            if ((int) mb_strlen($value, 'UTF-8') > $maxLength) {
                $value = (string) mb_substr($value, 0, $maxLength, 'UTF-8');
            }
        } else if (strlen($value) > $maxLength) {
            $value = (string) substr($value, 0, $maxLength);
        }

        $value = trim($value);
        if ($value === '') {
            $value = 'Description indisponible.';
        }
        return $value;
    }

    protected function normalizeTemplateHtml($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        // Backward compatibility: decode accidentally escaped HTML (&lt;p&gt;...).
        $pass = 0;
        while ($pass < 2) {
            if (
                strpos($value, '&lt;') === false &&
                strpos($value, '&gt;') === false &&
                strpos($value, '&#') === false &&
                strpos($value, '&amp;') === false
            ) {
                break;
            }
            $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($decoded === $value) {
                break;
            }
            $value = $decoded;
            $pass++;
        }

        return $value;
    }

    protected function resolveListingPrice(array $params)
    {
        $preferred = trim((string) $this->arrayValue($params, 'price_ebay', ''));
        if ($preferred !== '') {
            $preferred = str_replace(',', '.', $preferred);
            if (is_numeric($preferred) && (float) $preferred > 0) {
                return (float) $preferred;
            }
        }

        $fallback = trim((string) $this->arrayValue($params, 'price', ''));
        if ($fallback !== '') {
            $fallback = str_replace(',', '.', $fallback);
            if (is_numeric($fallback) && (float) $fallback > 0) {
                return (float) $fallback;
            }
        }

        return 0.0;
    }

    protected function hasValidListingPrice(array $params)
    {
        return $this->resolveListingPrice($params) > 0;
    }

    protected function missingListingPriceMessage()
    {
        return 'Publication eBay bloquée: renseignez un prix ou un prix eBay supérieur à 0.';
    }

    protected function sqlValidListingPriceCondition()
    {
        $priceEbay = "CAST(REPLACE(COALESCE(NULLIF(TRIM(`price_ebay`), ''), '0'), ',', '.') AS DECIMAL(12,2))";
        $price = "CAST(REPLACE(COALESCE(NULLIF(TRIM(`price`), ''), '0'), ',', '.') AS DECIMAL(12,2))";
        return "(CASE WHEN " . $priceEbay . " > 0 THEN " . $priceEbay . " ELSE " . $price . " END) > 0";
    }

    protected function composeListingTitleFromParams(array $params)
    {
        $author = trim((string) $this->arrayValue($params, 'author', ''));
        $title = trim((string) $this->arrayValue($params, 'title', ''));
        $subtitle = trim((string) $this->arrayValue($params, 'subtitle', ''));
        $composed = '';
        if ($author !== '' && $title !== '') {
            $composed = $author . ': ' . $title;
        } elseif ($title !== '') {
            $composed = $title;
        } elseif ($author !== '') {
            $composed = $author;
        }
        if ($subtitle !== '') {
            $composed = ($composed !== '') ? ($composed . ' - ' . $subtitle) : $subtitle;
        }
        if ($composed === '') {
            $composed = trim((string) $this->arrayValue($params, 'title80', ''));
        }
        return $composed;
    }

    protected function truncateTitleForEbay($value, $maxLength = 80)
    {
        $value = trim((string) $value);
        $maxLength = (int) $maxLength;
        if ($value === '' || $maxLength <= 0) {
            return '';
        }

        $ellipsis = '...';
        $ellipsisLength = strlen($ellipsis);
        if (function_exists('mb_strlen')) {
            $valueLength = (int) mb_strlen($value, 'UTF-8');
            if ($valueLength <= $maxLength) {
                return $value;
            }
            $keepLength = $maxLength - $ellipsisLength;
            if ($keepLength <= 0) {
                return mb_substr($value, 0, $maxLength, 'UTF-8');
            }
            return rtrim((string) mb_substr($value, 0, $keepLength, 'UTF-8')) . $ellipsis;
        }

        if (strlen($value) <= $maxLength) {
            return $value;
        }
        $keepLength = $maxLength - $ellipsisLength;
        if ($keepLength <= 0) {
            return substr($value, 0, $maxLength);
        }
        return rtrim((string) substr($value, 0, $keepLength)) . $ellipsis;
    }

    protected function limitText($value, $maxLength)
    {
        $value = trim((string) $value);
        $maxLength = (int) $maxLength;
        if ($value === '' || $maxLength <= 0) {
            return '';
        }
        if (function_exists('mb_substr')) {
            return trim((string) mb_substr($value, 0, $maxLength, 'UTF-8'));
        }
        return trim((string) substr($value, 0, $maxLength));
    }

    protected function buildInventoryAspects($idGroup, $marketplaceId, $categoryId, array $params, $fullTitle)
    {
        $idGroup = (int) $idGroup;
        $taxonomyMap = $this->getTaxonomyAspectMap($marketplaceId, $categoryId);
        $useTaxonomy = !empty($taxonomyMap);

        $isbn = strtoupper(trim((string) $this->arrayValue($params, 'isbn', '')));
        $isbn = preg_replace('/[^0-9X]/', '', $isbn);

        $weightGrams = (int) $this->arrayValue($params, 'weight_g', 0);
        $semanticValues = array(
            'author' => trim((string) $this->arrayValue($params, 'author', '')),
            'title' => trim((string) $this->arrayValue($params, 'title', '')),
            'subtitle' => trim((string) $this->arrayValue($params, 'subtitle', '')),
            'full_title' => trim((string) $fullTitle),
            'isbn' => $isbn,
            'language' => $this->resolveLookupLabel('language', 'idLanguage', 'languageName', $idGroup, $this->arrayValue($params, 'language', '')),
            'pages' => trim((string) $this->arrayValue($params, 'pageNumber', '')),
            'publish_location' => trim((string) $this->arrayValue($params, 'publishLocation', '')),
            'publisher' => trim((string) $this->arrayValue($params, 'editor', '')),
            'format' => $this->resolveLookupLabel('format', 'idFormat', 'formatName', $idGroup, $this->arrayValue($params, 'format', '')),
            'binding' => $this->resolveLookupLabel('binding', 'idBinding', 'bindingName', $idGroup, $this->arrayValue($params, 'binding', '')),
            'weight' => ($weightGrams > 0) ? ((string) $weightGrams . ' g') : '',
        );

        $aspectCandidates = array(
            'full_title' => array('Titre', 'Title', 'Book Title'),
            'author' => array('Auteur', 'Author', 'Written By'),
            'subtitle' => array('Sous-titre', 'Subtitle', 'Sub-Title'),
            'isbn' => array('ISBN', 'ISBN-13', 'ISBN-10'),
            'language' => array('Langue', 'Language'),
            'pages' => array('Nombre de pages', 'Pages', 'Number of Pages'),
            'publish_location' => array('Lieu de publication', 'Place of Publication', 'Publication Place'),
            'publisher' => array('Éditeur', 'Editeur', 'Publisher'),
            'format' => array('Format'),
            'binding' => array('Reliure', 'Binding'),
            'weight' => array('Poids', 'Weight'),
        );

        $aspects = array();
        if ($useTaxonomy) {
            foreach ($aspectCandidates as $semanticKey => $candidates) {
                $value = trim((string) $this->arrayValue($semanticValues, $semanticKey, ''));
                if ($value === '') {
                    continue;
                }
                $aspectMeta = $this->findMatchingAspectMeta($taxonomyMap, $candidates);
                if (empty($aspectMeta) || empty($aspectMeta['name'])) {
                    continue;
                }
                $aspectName = (string) $aspectMeta['name'];
                $aspectMaxLength = (int) $this->arrayValue($aspectMeta, 'maxLength', 0);
                if ($aspectMaxLength <= 0) {
                    $aspectMaxLength = 200;
                }
                $aspects[$aspectName] = array($this->limitText($value, $aspectMaxLength));
            }
            return $aspects;
        }

        // Fallback statique si Taxonomy indisponible.
        if ($semanticValues['full_title'] !== '') {
            $aspects['Titre'] = array($this->limitText($semanticValues['full_title'], 65));
        }
        if ($semanticValues['author'] !== '') {
            $aspects['Auteur'] = array($this->limitText($semanticValues['author'], 200));
        }
        if ($semanticValues['subtitle'] !== '') {
            $aspects['Sous-titre'] = array($this->limitText($semanticValues['subtitle'], 200));
        }
        if ($semanticValues['isbn'] !== '') {
            $aspects['ISBN'] = array($this->limitText($semanticValues['isbn'], 200));
        }
        if ($semanticValues['language'] !== '') {
            $aspects['Langue'] = array($this->limitText($semanticValues['language'], 200));
        }
        if ($semanticValues['pages'] !== '') {
            $aspects['Nombre de pages'] = array($this->limitText($semanticValues['pages'], 200));
        }
        if ($semanticValues['publish_location'] !== '') {
            $aspects['Lieu de publication'] = array($this->limitText($semanticValues['publish_location'], 200));
        }
        if ($semanticValues['publisher'] !== '') {
            $aspects['Editeur'] = array($this->limitText($semanticValues['publisher'], 200));
        }
        if ($semanticValues['format'] !== '') {
            $aspects['Format'] = array($this->limitText($semanticValues['format'], 200));
        }
        if ($semanticValues['binding'] !== '') {
            $aspects['Reliure'] = array($this->limitText($semanticValues['binding'], 200));
        }
        if ($semanticValues['weight'] !== '') {
            $aspects['Poids'] = array($this->limitText($semanticValues['weight'], 200));
        }

        return $aspects;
    }

    protected function getTaxonomyAspectMap($marketplaceId, $categoryId)
    {
        $marketplaceId = trim((string) $marketplaceId);
        $categoryId = trim((string) $categoryId);
        if ($marketplaceId === '' || $categoryId === '' || !preg_match('/^\d+$/', $categoryId)) {
            return array();
        }

        $cacheKey = $marketplaceId . '|' . $categoryId;
        if (array_key_exists($cacheKey, $this->taxonomyAspectsCache)) {
            return $this->taxonomyAspectsCache[$cacheKey];
        }

        try {
            $tree = $this->requestJson('GET', '/commerce/taxonomy/v1/get_default_category_tree_id', array(
                'query' => array('marketplace_id' => $marketplaceId),
            ));
            $categoryTreeId = trim((string) $this->arrayValue($tree, 'categoryTreeId', ''));
            if ($categoryTreeId === '') {
                $this->taxonomyAspectsCache[$cacheKey] = array();
                return array();
            }

            $payload = $this->requestJson('GET', '/commerce/taxonomy/v1/category_tree/' . rawurlencode($categoryTreeId) . '/get_item_aspects_for_category', array(
                'query' => array('category_id' => $categoryId),
            ));

            $map = array();
            $aspects = (array) $this->arrayValue($payload, 'aspects', array());
            foreach ($aspects as $aspect) {
                if (!is_array($aspect)) {
                    continue;
                }
                $name = trim((string) $this->arrayValue($aspect, 'localizedAspectName', ''));
                if ($name === '') {
                    continue;
                }
                $normalized = $this->normalizeAspectNameKey($name);
                if ($normalized === '') {
                    continue;
                }
                $constraint = (array) $this->arrayValue($aspect, 'aspectConstraint', array());
                $localizedConstraint = (array) $this->arrayValue($aspect, 'localizedAspectConstraint', array());
                $maxLength = (int) $this->arrayValue($constraint, 'aspectMaxLength', 0);
                if ($maxLength <= 0) {
                    $maxLength = (int) $this->arrayValue($localizedConstraint, 'aspectMaxLength', 0);
                }
                $map[$normalized] = array(
                    'name' => $name,
                    'maxLength' => ($maxLength > 0 ? $maxLength : 0),
                );
            }

            $this->taxonomyAspectsCache[$cacheKey] = $map;
            return $map;
        } catch (Throwable $e) {
            $this->taxonomyAspectsCache[$cacheKey] = array();
            return array();
        }
    }

    protected function normalizeAspectNameKey($name)
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }

        $normalized = html_entity_decode($name, ENT_QUOTES, 'UTF-8');
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
            if ($converted !== false) {
                $normalized = $converted;
            }
        }
        $normalized = strtolower($normalized);
        $normalized = preg_replace('/[^a-z0-9]+/', '', $normalized);
        return (string) $normalized;
    }

    protected function findMatchingAspectMeta(array $taxonomyMap, array $candidates)
    {
        foreach ($candidates as $candidate) {
            $candidateKey = $this->normalizeAspectNameKey($candidate);
            if ($candidateKey !== '' && isset($taxonomyMap[$candidateKey])) {
                return $this->normalizeAspectMeta($taxonomyMap[$candidateKey]);
            }
        }

        foreach ($candidates as $candidate) {
            $candidateKey = $this->normalizeAspectNameKey($candidate);
            if ($candidateKey === '') {
                continue;
            }
            foreach ($taxonomyMap as $normalizedKey => $aspectMeta) {
                if (strpos($normalizedKey, $candidateKey) !== false || strpos($candidateKey, $normalizedKey) !== false) {
                    return $this->normalizeAspectMeta($aspectMeta);
                }
            }
        }

        return array();
    }

    protected function normalizeAspectMeta($aspectMeta)
    {
        if (is_array($aspectMeta)) {
            $name = trim((string) $this->arrayValue($aspectMeta, 'name', ''));
            if ($name === '') {
                return array();
            }
            return array(
                'name' => $name,
                'maxLength' => (int) $this->arrayValue($aspectMeta, 'maxLength', 0),
            );
        }

        $name = trim((string) $aspectMeta);
        if ($name === '') {
            return array();
        }
        return array(
            'name' => $name,
            'maxLength' => 0,
        );
    }

    protected function findMatchingAspectName(array $taxonomyMap, array $candidates)
    {
        $meta = $this->findMatchingAspectMeta($taxonomyMap, $candidates);
        if (!empty($meta['name'])) {
            return (string) $meta['name'];
        }
        return '';
    }

    protected function resolveLookupLabel($tableName, $idField, $labelField, $idGroup, $value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        if (!preg_match('/^\d+$/', $value)) {
            return $value;
        }

        $idGroup = (int) $idGroup;
        $id = (int) $value;
        if ($idGroup <= 0 || $id <= 0 || !$this->tableExists($tableName)) {
            return '';
        }

        $cacheKey = $tableName . '|' . $idGroup . '|' . $id;
        if (array_key_exists($cacheKey, $this->lookupNameCache)) {
            return (string) $this->lookupNameCache[$cacheKey];
        }

        $rows = $this->dbSelect(
            "SELECT `" . $labelField . "` AS label FROM `" . $tableName . "` WHERE `idGroup`='" . $idGroup . "' AND `" . $idField . "`='" . $id . "' LIMIT 1"
        );
        $label = '';
        if (!empty($rows[0]['label'])) {
            $label = trim((string) $rows[0]['label']);
        }
        $this->lookupNameCache[$cacheKey] = $label;
        return $label;
    }

    protected function normalizeGroupId($idGroup = null)
    {
        $idGroup = (int) $idGroup;
        if ($idGroup > 0) {
            return $idGroup;
        }
        return $this->resolveGroupId();
    }

    protected function resolveGroupIdFromParams(array $params)
    {
        $idGroup = (int) $this->arrayValue($params, 'idGroup', 0);
        if ($idGroup > 0) {
            return $idGroup;
        }
        return $this->resolveGroupId();
    }

    protected function resolveMerchantLocationKey($idGroup)
    {
        $settings = $this->getGroupSettingsRow($idGroup);
        if (!empty($settings['merchantLocationKey'])) {
            return (string) $settings['merchantLocationKey'];
        }
        return '';
    }

    protected function resolveDefaultPaymentPolicyId($idGroup)
    {
        $settings = $this->getGroupSettingsRow($idGroup);
        if (!empty($settings['defaultPaymentPolicyId'])) {
            return (string) $settings['defaultPaymentPolicyId'];
        }
        return '';
    }

    protected function resolveDefaultFulfillmentPolicyId($idGroup)
    {
        $settings = $this->getGroupSettingsRow($idGroup);
        if (!empty($settings['defaultFulfillmentPolicyId'])) {
            return (string) $settings['defaultFulfillmentPolicyId'];
        }
        return '';
    }

    protected function resolveDefaultReturnPolicyId($idGroup)
    {
        $settings = $this->getGroupSettingsRow($idGroup);
        if (!empty($settings['defaultReturnPolicyId'])) {
            return (string) $settings['defaultReturnPolicyId'];
        }
        return '';
    }

    protected function resolvePolicyIdsForOffer($idGroup, array $params)
    {
        $mode = strtolower(trim((string) $this->arrayValue($params, 'ebayPolicyMode', 'default')));
        if ($mode !== 'override') {
            $mode = 'default';
        }

        $payment = '';
        $fulfillment = '';
        $return = '';

        if ($mode === 'override') {
            $payment = trim((string) $this->arrayValue($params, 'ebayPaymentPolicyIdOverride', ''));
            $fulfillment = trim((string) $this->arrayValue($params, 'ebayFulfillmentPolicyIdOverride', ''));
            $return = trim((string) $this->arrayValue($params, 'ebayReturnPolicyIdOverride', ''));
        }

        if ($payment === '') {
            $payment = $this->resolveDefaultPaymentPolicyId($idGroup);
        }
        if ($fulfillment === '') {
            $fulfillment = $this->resolveDefaultFulfillmentPolicyId($idGroup);
        }
        if ($return === '') {
            $return = $this->resolveDefaultReturnPolicyId($idGroup);
        }

        return array(
            'policyMode' => $mode,
            'paymentPolicyId' => trim((string) $payment),
            'fulfillmentPolicyId' => trim((string) $fulfillment),
            'returnPolicyId' => trim((string) $return),
        );
    }

    protected function resolveStoreCategoryNamesForBook($idGroup, $idBook)
    {
        $idGroup = (int) $idGroup;
        $idBook = (int) $idBook;
        if ($idGroup <= 0 || $idBook <= 0) {
            return array();
        }
        if (!$this->tableExists('ebay_category_store_map') || !$this->tableExists('ebay_store_category')) {
            return array();
        }

        $rows = $this->dbSelect(
            "SELECT store.`name` AS `storeCategoryName` "
            . "FROM `bookincategory` AS bic "
            . "INNER JOIN `category` AS cat ON cat.`idCategory`=bic.`idCategory` AND cat.`idGroup`='" . (int) $idGroup . "' "
            . "INNER JOIN `ebay_category_store_map` AS map ON map.`idGroup`='" . (int) $idGroup . "' AND map.`idCategory`=bic.`idCategory` "
            . "INNER JOIN `ebay_store_category` AS store ON store.`idGroup`=map.`idGroup` AND store.`storeCategoryId`=map.`storeCategoryId` "
            . "WHERE bic.`idBook`='" . (int) $idBook . "' "
            . "ORDER BY bic.`idBookInCategory` ASC LIMIT 1"
        );

        if (empty($rows[0]['storeCategoryName'])) {
            return array();
        }

        return array((string) $rows[0]['storeCategoryName']);
    }

    protected function getGroupSettingsRow($idGroup)
    {
        $idGroup = (int) $idGroup;
        $defaults = array(
            'idGroup' => $idGroup,
            'marketplaceId' => '',
            'merchantLocationKey' => '',
            'defaultPaymentPolicyId' => '',
            'defaultFulfillmentPolicyId' => '',
            'defaultReturnPolicyId' => '',
            'fixedCategoryId' => '',
            'descriptionHeaderHtml' => '',
            'descriptionFooterHtml' => '',
            'updatedAtTs' => 0,
            '__legacy' => false,
        );

        if ($idGroup <= 0) {
            return $defaults;
        }

        $settings = $this->getEbayGroupSettingsAssoc($idGroup);
        if (!empty($settings)) {
            $row = $defaults;
            $row['marketplaceId'] = isset($settings['marketplace_id']) ? (string) $settings['marketplace_id'] : '';
            $row['merchantLocationKey'] = isset($settings['merchant_location_key']) ? (string) $settings['merchant_location_key'] : '';
            $row['defaultPaymentPolicyId'] = isset($settings['default_payment_policy_id']) ? (string) $settings['default_payment_policy_id'] : '';
            $row['defaultFulfillmentPolicyId'] = isset($settings['default_fulfillment_policy_id']) ? (string) $settings['default_fulfillment_policy_id'] : '';
            $row['defaultReturnPolicyId'] = isset($settings['default_return_policy_id']) ? (string) $settings['default_return_policy_id'] : '';
            $row['fixedCategoryId'] = isset($settings['fixed_category_id']) ? (string) $settings['fixed_category_id'] : '';
            $row['descriptionHeaderHtml'] = isset($settings['description_header_html']) ? $this->normalizeTemplateHtml((string) $settings['description_header_html']) : '';
            $row['descriptionFooterHtml'] = isset($settings['description_footer_html']) ? $this->normalizeTemplateHtml((string) $settings['description_footer_html']) : '';
            return $row;
        }

        $legacy = $this->getLegacyEbayGroupSettingsRow($idGroup);
        if (!empty($legacy)) {
            return array_merge($defaults, $legacy, array('__legacy' => true));
        }

        return $defaults;
    }

    protected function getLegacyEbayGroupSettingsRow($idGroup)
    {
        $idGroup = (int) $idGroup;
        if ($idGroup <= 0 || !$this->tableExists('ebay_group_settings')) {
            return array();
        }
        $rows = $this->dbSelect(
            "SELECT * FROM `ebay_group_settings` WHERE `idGroup`='" . $idGroup . "' LIMIT 1"
        );
        if (empty($rows[0])) {
            return array();
        }
        return (array) $rows[0];
    }

    protected function getEbayGroupSettingsAssoc($idGroup)
    {
        $idGroup = (int) $idGroup;
        return IntegrationGroupSettings::getGroupSettings($idGroup, 'ebay');
    }

    protected function getEbayGroupSetting($idGroup, $settingKey, $defaultValue = '')
    {
        return IntegrationGroupSettings::getGroupSetting((int) $idGroup, 'ebay', (string) $settingKey, (string) $defaultValue);
    }

    protected function setEbayGroupSetting($idGroup, $settingKey, $value, $isSecret = 0)
    {
        $updatedByUser = isset($_SESSION['idUser']) ? (int) $_SESSION['idUser'] : 0;
        return IntegrationGroupSettings::setGroupSetting((int) $idGroup, 'ebay', (string) $settingKey, (string) $value, (int) $isSecret, $updatedByUser);
    }

    protected function getSettingSourceForGroup(array $groupSettings, $groupKey)
    {
        if (!empty($groupSettings[$groupKey])) {
            if (!empty($groupSettings['__legacy'])) {
                return 'legacy';
            }
            return 'group';
        }
        return 'none';
    }

    protected function tableExists($tableName)
    {
        $tableName = (string) $tableName;
        if (array_key_exists($tableName, $this->tableExistsCache)) {
            return (bool) $this->tableExistsCache[$tableName];
        }

        $rows = $this->dbSelect(
            "SELECT COUNT(*) AS nb FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='" . $this->escapeSql($tableName) . "'"
        );
        $exists = (!empty($rows[0]['nb']) && (int) $rows[0]['nb'] > 0);
        $this->tableExistsCache[$tableName] = $exists;
        return $exists;
    }

    protected function columnExists($tableName, $columnName)
    {
        $cacheKey = $tableName . ':' . $columnName;
        if (array_key_exists($cacheKey, $this->tableExistsCache)) {
            return (bool) $this->tableExistsCache[$cacheKey];
        }

        $rows = $this->dbSelect(
            "SELECT COUNT(*) AS nb FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='" . $this->escapeSql($tableName) . "' AND COLUMN_NAME='" . $this->escapeSql($columnName) . "'"
        );
        $exists = (!empty($rows[0]['nb']) && (int) $rows[0]['nb'] > 0);
        $this->tableExistsCache[$cacheKey] = $exists;
        return $exists;
    }

    protected function buildSqlStringList(array $values)
    {
        $parts = array();
        foreach ($values as $value) {
            $parts[] = "'" . $this->escapeSql((string) $value) . "'";
        }
        if (empty($parts)) {
            return "''";
        }
        return implode(', ', $parts);
    }

    protected function extractStoreCategoriesFromPayload(array $payload)
    {
        $nodes = array();
        if (!empty($payload['storeCategories']) && is_array($payload['storeCategories'])) {
            $nodes = $payload['storeCategories'];
        } elseif (!empty($payload['storeCategoryTreeNode']) && is_array($payload['storeCategoryTreeNode'])) {
            $nodes = $payload['storeCategoryTreeNode'];
        } elseif (!empty($payload['categories']) && is_array($payload['categories'])) {
            $nodes = $payload['categories'];
        } else {
            $nodes = array($payload);
        }

        $flat = array();
        $this->flattenStoreCategoryNodes($nodes, '', 0, $flat);

        return array_values($flat);
    }

    protected function flattenStoreCategoryNodes(array $nodes, $parentStoreCategoryId, $level, array &$flat)
    {
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }

            $storeCategoryId = trim((string) $this->arrayValue($node, 'storeCategoryId', $this->arrayValue($node, 'categoryId', '')));
            $name = trim((string) $this->arrayValue(
                $node,
                'name',
                $this->arrayValue($node, 'categoryName', $this->arrayValue($node, 'categoryname', ''))
            ));
            $displayOrder = (int) $this->arrayValue(
                $node,
                'displayOrder',
                $this->arrayValue($node, 'categoryDisplayOrder', $this->arrayValue($node, 'order', 0))
            );

            $childNodes = array();
            if (!empty($node['childStoreCategories']) && is_array($node['childStoreCategories'])) {
                $childNodes = $node['childStoreCategories'];
            } elseif (!empty($node['childCategoryTreeNodes']) && is_array($node['childCategoryTreeNodes'])) {
                $childNodes = $node['childCategoryTreeNodes'];
            } elseif (!empty($node['childCategories']) && is_array($node['childCategories'])) {
                $childNodes = $node['childCategories'];
            } elseif (!empty($node['childrenCategories']) && is_array($node['childrenCategories'])) {
                $childNodes = $node['childrenCategories'];
            } elseif (!empty($node['children']) && is_array($node['children'])) {
                $childNodes = $node['children'];
            }

            $leaf = (int) $this->arrayValue(
                $node,
                'leaf',
                $this->arrayValue($node, 'isLeaf', (empty($childNodes) ? 1 : 0))
            );

            if ($storeCategoryId !== '' && !isset($flat[$storeCategoryId])) {
                $flat[$storeCategoryId] = array(
                    'storeCategoryId' => $storeCategoryId,
                    'parentStoreCategoryId' => (string) $parentStoreCategoryId,
                    'name' => $name,
                    'displayOrder' => $displayOrder,
                    'level' => (int) $level,
                    'leaf' => $leaf,
                    'raw' => $node,
                );
            }

            if (!empty($childNodes)) {
                $nextParent = ($storeCategoryId !== '') ? $storeCategoryId : $parentStoreCategoryId;
                $this->flattenStoreCategoryNodes($childNodes, $nextParent, $level + 1, $flat);
            }
        }
    }

    protected function buildPublicPhotoUrl($path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $baseUrl = trim((string) config('website_baseurl'));
        if ($baseUrl === '') {
            return $path;
        }

        if (substr($path, 0, 1) === '/') {
            return rtrim($baseUrl, '/') . $path;
        }
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    protected function getWebRootPath()
    {
        static $webRoot = null;
        if ($webRoot !== null) {
            return $webRoot;
        }

        $resolved = realpath(dirname(__FILE__) . '/../../');
        if ($resolved === false) {
            $resolved = dirname(__FILE__) . '/../../';
        }
        $webRoot = rtrim((string) $resolved, '/');
        return $webRoot;
    }

    protected function resolveLocalPhotoAbsolutePath($path)
    {
        $path = trim((string) $path);
        if ($path === '' || preg_match('#^https?://#i', $path)) {
            return '';
        }

        $normalized = str_replace('\\', '/', $path);
        $trimmed = ltrim($normalized, '/');
        $decoded = rawurldecode($trimmed);
        $webRoot = $this->getWebRootPath();

        $candidates = array($trimmed, $decoded);
        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate === '') {
                continue;
            }

            $relativeCandidates = array($candidate);
            if (strpos($candidate, 'gestlibr/') === 0) {
                $relativeCandidates[] = substr($candidate, strlen('gestlibr/'));
            }

            foreach ($relativeCandidates as $relative) {
                $relative = ltrim((string) $relative, '/');
                if ($relative === '') {
                    continue;
                }

                $absolute = $webRoot . '/' . $relative;
                if (is_file($absolute) && is_readable($absolute)) {
                    return $absolute;
                }
            }
        }

        return '';
    }

    protected function resolvePhotoAssets($idGroup, $idBook)
    {
        $idGroup = (int) $idGroup;
        $idBook = (int) $idBook;
        if ($idGroup <= 0 || $idBook <= 0) {
            return array();
        }

        $orderSql = " ORDER BY `date` ASC, `idBookPhoto` ASC";
        if ($this->columnExists('bookphoto', 'sortOrder')) {
            $orderSql = " ORDER BY `sortOrder` ASC, `idBookPhoto` ASC";
        }
        $rows = $this->dbSelect(
            "SELECT `file` FROM `bookphoto` WHERE `idGroup`='" . (int) $idGroup . "' AND `idBook`='" . (int) $idBook . "'" . $orderSql
        );

        if (empty($rows)) {
            return array();
        }

        $assets = array();
        $seen = array();
        foreach ($rows as $row) {
            $file = isset($row['file']) ? trim((string) $row['file']) : '';
            if ($file === '') {
                continue;
            }

            $publicUrl = $this->buildPublicPhotoUrl($file);
            $absolutePath = $this->resolveLocalPhotoAbsolutePath($file);
            $dedupeKey = ($absolutePath !== '') ? ('file:' . $absolutePath) : ('url:' . $publicUrl);
            if ($dedupeKey === 'url:' || isset($seen[$dedupeKey])) {
                continue;
            }
            $seen[$dedupeKey] = true;

            $assets[] = array(
                'file' => $file,
                'publicUrl' => $publicUrl,
                'absolutePath' => $absolutePath,
            );

            if (count($assets) >= 24) {
                break;
            }
        }

        return $assets;
    }

    protected function resolvePhotoUrls($idGroup, $idBook)
    {
        $urls = array();
        $assets = $this->resolvePhotoAssets($idGroup, $idBook);
        foreach ($assets as $asset) {
            $url = trim((string) $this->arrayValue($asset, 'publicUrl', ''));
            if ($url === '') {
                continue;
            }
            if (!in_array($url, $urls, true)) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    protected function resolvePrimaryPhotoUrl($idGroup, $idBook)
    {
        $urls = $this->resolvePhotoUrls($idGroup, $idBook);
        if (empty($urls)) {
            return '';
        }
        return (string) $urls[0];
    }

    protected function buildListingParamsFromBookRow(array $row)
    {
        $idBook = (int) $this->arrayValue($row, 'idBook', 0);
        $idGroup = (int) $this->arrayValue($row, 'idGroup', $this->resolveGroupId());
        $author = (string) $this->arrayValue($row, 'author', '');
        $title = (string) $this->arrayValue($row, 'title', '');
        $subtitle = (string) $this->arrayValue($row, 'subtitle', '');
        $fullTitle = $this->composeListingTitleFromParams(array(
            'author' => $author,
            'title' => $title,
            'subtitle' => $subtitle,
        ));
        $shortTitle = $this->limitText($fullTitle, 72) . ' (' . $idBook . ')';
        if (function_exists('convert_to')) {
            $shortTitle = convert_to($shortTitle, 'UTF-8');
        }

        $photoAssets = $this->resolvePhotoAssets($idGroup, $idBook);
        $photoUrls = array();
        foreach ($photoAssets as $asset) {
            $url = trim((string) $this->arrayValue($asset, 'publicUrl', ''));
            if ($url !== '' && !in_array($url, $photoUrls, true)) {
                $photoUrls[] = $url;
            }
        }

        return array(
            'idBook' => $idBook,
            'idGroup' => $idGroup,
            'ebayItemId' => (string) $this->arrayValue($row, 'ebayItemId', ''),
            'title' => $title,
            'title80' => $shortTitle,
            'author' => $author,
            'subtitle' => $subtitle,
            'quantity' => (int) $this->arrayValue($row, 'quantity', 0),
            'price' => (string) $this->arrayValue($row, 'price', ''),
            'price_ebay' => (string) $this->arrayValue($row, 'price_ebay', ''),
            'description' => html_entity_decode((string) $this->arrayValue($row, 'description', ''), ENT_QUOTES, 'UTF-8'),
            'ebayUseDescriptionTemplate' => (int) $this->arrayValue($row, 'ebayUseDescriptionTemplate', 0),
            'ebayCategoryId' => (string) $this->arrayValue($row, 'ebayCategoryId', ''),
            'ebayCategoryIdSecondary' => (string) $this->arrayValue($row, 'ebayCategoryIdSecondary', ''),
            'ebayShippingCost' => (string) $this->arrayValue($row, 'ebayShippingCost', ''),
            'ebayShippingCostInternational' => (string) $this->arrayValue($row, 'ebayShippingCostInternational', ''),
            'ebayPolicyMode' => (string) $this->arrayValue($row, 'ebayPolicyMode', 'default'),
            'ebayPaymentPolicyIdOverride' => (string) $this->arrayValue($row, 'ebayPaymentPolicyIdOverride', ''),
            'ebayFulfillmentPolicyIdOverride' => (string) $this->arrayValue($row, 'ebayFulfillmentPolicyIdOverride', ''),
            'ebayReturnPolicyIdOverride' => (string) $this->arrayValue($row, 'ebayReturnPolicyIdOverride', ''),
            'isbn' => (string) $this->arrayValue($row, 'isbn', ''),
            'language' => (string) $this->arrayValue($row, 'language', ''),
            'pageNumber' => (string) $this->arrayValue($row, 'pageNumber', ''),
            'publishLocation' => (string) $this->arrayValue($row, 'publishLocation', ''),
            'editor' => (string) $this->arrayValue($row, 'editor', ''),
            'format' => (string) $this->arrayValue($row, 'format', ''),
            'binding' => (string) $this->arrayValue($row, 'binding', ''),
            'weight_g' => (int) $this->arrayValue($row, 'weight_g', 0),
            'photoAssets' => $photoAssets,
            'photoUrls' => $photoUrls,
            'photo' => !empty($photoUrls[0]) ? (string) $photoUrls[0] : '',
        );
    }

    protected function resolveInventoryImageUrls(array $params, $withMediaUpload = false)
    {
        $images = array();
        if (!empty($params['photoUrls']) && is_array($params['photoUrls'])) {
            foreach ($params['photoUrls'] as $imageUrl) {
                $url = trim((string) $imageUrl);
                if ($url !== '' && !in_array($url, $images, true)) {
                    $images[] = $url;
                }
            }
        }

        if (empty($images)) {
            $image = trim((string) $this->arrayValue($params, 'photo', ''));
            if ($image !== '') {
                $images[] = $image;
            }
        }

        if (!$withMediaUpload) {
            return $images;
        }

        $photoAssets = array();
        if (!empty($params['photoAssets']) && is_array($params['photoAssets'])) {
            foreach ($params['photoAssets'] as $asset) {
                if (is_array($asset)) {
                    $photoAssets[] = $asset;
                }
            }
        }

        if (empty($photoAssets)) {
            $idGroup = (int) $this->arrayValue($params, 'idGroup', 0);
            $idBook = (int) $this->arrayValue($params, 'idBook', 0);
            if ($idGroup > 0 && $idBook > 0) {
                $photoAssets = $this->resolvePhotoAssets($idGroup, $idBook);
            }
        }

        if (empty($photoAssets) && empty($images)) {
            return array();
        }

        $resolved = array();
        if (!empty($photoAssets)) {
            foreach ($photoAssets as $asset) {
                $uploadedUrl = $this->uploadPhotoAssetToEbay($asset);
                $finalUrl = $uploadedUrl;
                if ($finalUrl === '') {
                    $fallbackUrl = trim((string) $this->arrayValue($asset, 'publicUrl', ''));
                    if ($fallbackUrl !== '') {
                        $remoteUploaded = $this->uploadImageToEbayFromUrl($fallbackUrl);
                        if ($remoteUploaded !== '') {
                            $finalUrl = $remoteUploaded;
                        } else {
                            $finalUrl = $fallbackUrl;
                        }
                    }
                }

                if ($finalUrl === '') {
                    continue;
                }
                if (!in_array($finalUrl, $resolved, true)) {
                    $resolved[] = $finalUrl;
                }
                if (count($resolved) >= 24) {
                    break;
                }
            }
        } else {
            foreach ($images as $sourceUrl) {
                $uploadedUrl = $this->uploadImageToEbayFromUrl($sourceUrl);
                $finalUrl = ($uploadedUrl !== '') ? $uploadedUrl : $sourceUrl;
                if ($finalUrl === '') {
                    continue;
                }
                if (!in_array($finalUrl, $resolved, true)) {
                    $resolved[] = $finalUrl;
                }
                if (count($resolved) >= 24) {
                    break;
                }
            }
        }

        if (!empty($resolved)) {
            return $resolved;
        }

        return $images;
    }

    protected function uploadPhotoAssetToEbay(array $asset)
    {
        $absolutePath = trim((string) $this->arrayValue($asset, 'absolutePath', ''));
        if ($absolutePath === '' || !is_file($absolutePath) || !is_readable($absolutePath)) {
            return '';
        }

        $cacheKey = $absolutePath . '|' . (string) @filemtime($absolutePath) . '|' . (string) @filesize($absolutePath);
        if (array_key_exists($cacheKey, $this->mediaImageUploadCache)) {
            return (string) $this->mediaImageUploadCache[$cacheKey];
        }

        try {
            $url = $this->createEbayImageFromFile($absolutePath);
            $this->mediaImageUploadCache[$cacheKey] = $url;
            return $url;
        } catch (Exception $e) {
            $this->mediaImageUploadCache[$cacheKey] = '';
            return '';
        }
    }

    protected function uploadImageToEbayFromUrl($sourceUrl)
    {
        $sourceUrl = trim((string) $sourceUrl);
        if ($sourceUrl === '' || stripos($sourceUrl, 'https://') !== 0) {
            return '';
        }

        $cacheKey = 'url:' . $sourceUrl;
        if (array_key_exists($cacheKey, $this->mediaImageUploadCache)) {
            return (string) $this->mediaImageUploadCache[$cacheKey];
        }

        try {
            $url = $this->createEbayImageFromUrl($sourceUrl);
            $this->mediaImageUploadCache[$cacheKey] = $url;
            return $url;
        } catch (Exception $e) {
            $this->mediaImageUploadCache[$cacheKey] = '';
            return '';
        }
    }

    protected function createEbayImageFromFile($absolutePath)
    {
        $absolutePath = trim((string) $absolutePath);
        if ($absolutePath === '' || !is_file($absolutePath) || !is_readable($absolutePath)) {
            throw new RuntimeException('Fichier image introuvable pour upload eBay.');
        }

        $handle = fopen($absolutePath, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Impossible de lire le fichier image pour upload eBay.');
        }

        try {
            $payload = $this->requestJsonWithBearerToken('POST', '/commerce/media/v1_beta/image/create_image_from_file', $this->getAccessToken(), array(
                'baseUrl' => $this->getMediaBaseUrl(),
                'headers' => array(
                    'Content-Language' => 'fr-FR',
                ),
                'multipart' => array(
                    array(
                        'name' => 'image',
                        'contents' => $handle,
                        'filename' => basename($absolutePath),
                    ),
                ),
            ));
        } finally {
            if (is_resource($handle)) {
                @fclose($handle);
            }
        }

        $resolvedUrl = $this->extractEbayHostedImageUrl($payload);
        if ($resolvedUrl === '') {
            throw new RuntimeException('Upload image eBay réussi sans URL exploitable.');
        }

        return $resolvedUrl;
    }

    protected function createEbayImageFromUrl($sourceUrl)
    {
        $sourceUrl = trim((string) $sourceUrl);
        if ($sourceUrl === '' || stripos($sourceUrl, 'https://') !== 0) {
            throw new RuntimeException('URL image source invalide pour upload eBay.');
        }

        $payload = $this->requestJsonWithBearerToken('POST', '/commerce/media/v1_beta/image/create_image_from_url', $this->getAccessToken(), array(
            'baseUrl' => $this->getMediaBaseUrl(),
            'headers' => array(
                'Content-Language' => 'fr-FR',
            ),
            'json' => array(
                'imageUrl' => $sourceUrl,
            ),
        ));

        $resolvedUrl = $this->extractEbayHostedImageUrl($payload);
        if ($resolvedUrl === '') {
            throw new RuntimeException('Upload image eBay (URL) réussi sans URL exploitable.');
        }
        return $resolvedUrl;
    }

    protected function extractEbayHostedImageUrl(array $payload)
    {
        $image = (array) $this->arrayValue($payload, 'image', array());
        $candidates = array(
            $this->arrayValue($payload, 'imageUrl', ''),
            $this->arrayValue($payload, 'url', ''),
            $this->arrayValue($payload, 'href', ''),
            $this->arrayValue($image, 'imageUrl', ''),
            $this->arrayValue($image, 'url', ''),
            $this->arrayValue($image, 'href', ''),
        );

        foreach ($candidates as $candidate) {
            $url = trim((string) $candidate);
            if ($url !== '' && preg_match('#^https?://#i', $url)) {
                return $url;
            }
        }

        $imageId = trim((string) $this->arrayValue($image, 'imageId', $this->arrayValue($payload, 'imageId', '')));
        if ($imageId === '') {
            return '';
        }

        try {
            $details = $this->requestJsonWithBearerToken('GET', '/commerce/media/v1_beta/image/' . rawurlencode($imageId), $this->getAccessToken(), array(
                'baseUrl' => $this->getMediaBaseUrl(),
            ));
            $detailsImage = (array) $this->arrayValue($details, 'image', array());
            $fallbackCandidates = array(
                $this->arrayValue($details, 'imageUrl', ''),
                $this->arrayValue($detailsImage, 'imageUrl', ''),
                $this->arrayValue($detailsImage, 'url', ''),
                $this->arrayValue($detailsImage, 'href', ''),
            );
            foreach ($fallbackCandidates as $candidate) {
                $url = trim((string) $candidate);
                if ($url !== '' && preg_match('#^https?://#i', $url)) {
                    return $url;
                }
            }
        } catch (Exception $e) {
            return '';
        }

        return '';
    }

    protected function getLastErrorMessageForBook($idGroup, $idBook)
    {
        if (!$this->tableExists('ebay_listing_state')) {
            return '';
        }
        $rows = $this->dbSelect(
            "SELECT `lastErrorMessage` FROM `ebay_listing_state` WHERE `idGroup`='" . (int) $idGroup . "' AND `idBook`='" . (int) $idBook . "' ORDER BY `updatedAt` DESC LIMIT 1"
        );
        if (!empty($rows[0]['lastErrorMessage'])) {
            return (string) $rows[0]['lastErrorMessage'];
        }
        return '';
    }

    protected function acquireSyncLock($idGroup)
    {
        $idGroup = (int) $idGroup;
        if ($idGroup <= 0) {
            return false;
        }
        $lockName = 'gestlibr_ebay_sync_' . $idGroup;
        $rows = $this->dbSelect(
            "SELECT GET_LOCK('" . $this->escapeSql($lockName) . "', 0) AS lockOk"
        );
        if (empty($rows[0]['lockOk'])) {
            return false;
        }
        return ((int) $rows[0]['lockOk'] === 1);
    }

    protected function releaseSyncLock($idGroup)
    {
        $idGroup = (int) $idGroup;
        if ($idGroup <= 0) {
            return;
        }
        $lockName = 'gestlibr_ebay_sync_' . $idGroup;
        $this->dbSelect(
            "SELECT RELEASE_LOCK('" . $this->escapeSql($lockName) . "') AS releaseOk"
        );
    }

    protected function buildPendingStockAdjustmentsFromValidatedLines($idGroup, array $validatedLines)
    {
        $idGroup = (int) $idGroup;
        $out = array();
        if ($idGroup <= 0 || sizeof($validatedLines) === 0) {
            return $out;
        }

        $lineItemIds = array();
        $byLine = array();
        foreach ($validatedLines as $line) {
            if (!is_array($line)) {
                continue;
            }
            $lineItemId = trim((string) $this->arrayValue($line, 'lineItemId', ''));
            if ($lineItemId === '') {
                continue;
            }
            $lineItemIds[] = $lineItemId;
            $byLine[$lineItemId] = $line;
        }
        if (sizeof($lineItemIds) === 0) {
            return $out;
        }

        $existing = array();
        if ($this->tableExists('ebay_order_item')) {
            $existingRows = $this->dbSelect(
                "SELECT `lineItemId`,`stockAppliedQty` FROM `ebay_order_item` WHERE `idGroup`='" . (int) $idGroup . "' AND `lineItemId` IN (" . $this->buildSqlStringList($lineItemIds) . ")"
            );
            foreach ($existingRows as $row) {
                $existing[(string) $this->arrayValue($row, 'lineItemId', '')] = (int) $this->arrayValue($row, 'stockAppliedQty', 0);
            }
        }

        foreach ($byLine as $lineItemId => $line) {
            $qty = (int) $this->arrayValue($line, 'quantity', 0);
            $applied = isset($existing[$lineItemId]) ? (int) $existing[$lineItemId] : 0;
            $deltaQty = $qty - $applied;
            if ($deltaQty <= 0) {
                continue;
            }
            $line['deltaQty'] = (int) $deltaQty;
            $out[] = $line;
        }

        return $out;
    }

    protected function getPendingStockAdjustments($idGroup)
    {
        $idGroup = (int) $idGroup;
        if ($idGroup <= 0 || !$this->tableExists('ebay_order_item') || !$this->tableExists('ebay_order')) {
            return array();
        }

        $rows = $this->dbSelect(
            "SELECT i.`lineItemId`, i.`ebayOrderId`, i.`idBook`, i.`quantity`, i.`stockAppliedQty`, i.`ebayItemId` "
            . "FROM `ebay_order_item` i "
            . "INNER JOIN `ebay_order` o ON o.`idGroup`=i.`idGroup` AND o.`ebayOrderId`=i.`ebayOrderId` "
            . "WHERE i.`idGroup`='" . (int) $idGroup . "' AND o.`isValidated`='1' AND i.`idBook`>0 AND i.`quantity`>i.`stockAppliedQty` "
            . "ORDER BY o.`creationDateTs` ASC, i.`id` ASC"
        );

        $out = array();
        foreach ($rows as $row) {
            $qty = (int) $this->arrayValue($row, 'quantity', 0);
            $applied = (int) $this->arrayValue($row, 'stockAppliedQty', 0);
            $deltaQty = $qty - $applied;
            if ($deltaQty <= 0) {
                continue;
            }
            $out[] = array(
                'lineItemId' => (string) $this->arrayValue($row, 'lineItemId', ''),
                'ebayOrderId' => (string) $this->arrayValue($row, 'ebayOrderId', ''),
                'idBook' => (int) $this->arrayValue($row, 'idBook', 0),
                'quantity' => $qty,
                'stockAppliedQty' => $applied,
                'deltaQty' => $deltaQty,
                'ebayItemId' => (string) $this->arrayValue($row, 'ebayItemId', ''),
            );
        }
        return $out;
    }

    protected function logSyncAction($runId, $idGroup, $actionType, array $actionData = array())
    {
        $runId = (int) $runId;
        $idGroup = (int) $idGroup;
        if ($runId <= 0 || $idGroup <= 0 || !$this->tableExists('ebay_sync_action')) {
            return;
        }
        $now = time();
        $details = isset($actionData['details']) ? $actionData['details'] : array();
        $this->dbQuery(
            "INSERT INTO `ebay_sync_action` (`idRun`,`idGroup`,`actionType`,`idBook`,`ebayOrderId`,`lineItemId`,`ebayItemIdBefore`,`ebayItemIdAfter`,`qtyBefore`,`qtyDelta`,`qtyAfter`,`isDryRun`,`status`,`message`,`detailsJson`,`createdAtTs`) VALUES ('"
            . (int) $runId . "', '"
            . (int) $idGroup . "', '"
            . $this->escapeSql((string) $actionType) . "', '"
            . (int) $this->arrayValue($actionData, 'idBook', 0) . "', '"
            . $this->escapeSql((string) $this->arrayValue($actionData, 'ebayOrderId', '')) . "', '"
            . $this->escapeSql((string) $this->arrayValue($actionData, 'lineItemId', '')) . "', '"
            . $this->escapeSql((string) $this->arrayValue($actionData, 'ebayItemIdBefore', '')) . "', '"
            . $this->escapeSql((string) $this->arrayValue($actionData, 'ebayItemIdAfter', '')) . "', '"
            . (int) $this->arrayValue($actionData, 'qtyBefore', 0) . "', '"
            . (int) $this->arrayValue($actionData, 'qtyDelta', 0) . "', '"
            . (int) $this->arrayValue($actionData, 'qtyAfter', 0) . "', '"
            . (int) $this->arrayValue($actionData, 'isDryRun', 0) . "', '"
            . $this->escapeSql((string) $this->arrayValue($actionData, 'status', '')) . "', '"
            . $this->escapeSql((string) $this->arrayValue($actionData, 'message', '')) . "', '"
            . $this->escapeSql($this->jsonEncodeSafe($details)) . "', '"
            . (int) $now . "')"
        );
    }

    protected function startSyncRun($idGroup, $idUser, $triggerSource, $batchLimit, $isDryRun = 0)
    {
        if (!$this->tableExists('ebay_sync_run')) {
            return 0;
        }
        $now = time();
        $isDryRun = ((int) $isDryRun === 1) ? 1 : 0;
        $hasDryRunColumn = $this->columnExists('ebay_sync_run', 'isDryRun');
        if ($hasDryRunColumn) {
            return (int) $this->db()->DbInsert(
                "INSERT INTO `ebay_sync_run` (`idGroup`, `startedAtTs`, `endedAtTs`, `triggerSource`, `batchLimit`, `isDryRun`, `nbAdded`, `nbUpdated`, `nbDeleted`, `nbErrors`, `status`, `message`, `idUser`, `detailsJson`) VALUES ('"
                . (int) $idGroup . "', '" . (int) $now . "', '0', '" . $this->escapeSql((string) $triggerSource) . "', '" . (int) $batchLimit . "', '" . (int) $isDryRun . "', '0', '0', '0', '0', 'running', '', '" . (int) $idUser . "', '')",
                1
            );
        }
        return (int) $this->db()->DbInsert(
            "INSERT INTO `ebay_sync_run` (`idGroup`, `startedAtTs`, `endedAtTs`, `triggerSource`, `batchLimit`, `nbAdded`, `nbUpdated`, `nbDeleted`, `nbErrors`, `status`, `message`, `idUser`, `detailsJson`) VALUES ('"
            . (int) $idGroup . "', '" . (int) $now . "', '0', '" . $this->escapeSql((string) $triggerSource) . "', '" . (int) $batchLimit . "', '0', '0', '0', '0', 'running', '', '" . (int) $idUser . "', '')",
            1
        );
    }

    protected function finishSyncRun($runId, array $stats, $status, $message, array $details)
    {
        $runId = (int) $runId;
        if ($runId <= 0 || !$this->tableExists('ebay_sync_run')) {
            return;
        }

        $this->dbQuery(
            "UPDATE `ebay_sync_run` SET `endedAtTs`='" . (int) time() . "', `nbAdded`='" . (int) $this->arrayValue($stats, 'added', 0) . "', `nbUpdated`='"
            . (int) $this->arrayValue($stats, 'updated', 0) . "', `nbDeleted`='" . (int) $this->arrayValue($stats, 'deleted', 0) . "', `nbErrors`='"
            . (int) $this->arrayValue($stats, 'errors', 0) . "', `status`='" . $this->escapeSql((string) $status) . "', `message`='"
            . $this->escapeSql((string) $message) . "', `detailsJson`='" . $this->escapeSql($this->jsonEncodeSafe($details)) . "' WHERE `id`='" . $runId . "' LIMIT 1"
        );
    }

    protected function jsonEncodeSafe($value)
    {
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        if ($encoded !== false) {
            return (string) $encoded;
        }
        $sanitized = $this->sanitizeUtf8Recursively($value);
        $encoded = json_encode($sanitized, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        if ($encoded !== false) {
            return (string) $encoded;
        }
        return '{}';
    }

    protected function sanitizeUtf8Recursively($value)
    {
        if (is_array($value)) {
            $clean = array();
            foreach ($value as $key => $item) {
                $clean[$key] = $this->sanitizeUtf8Recursively($item);
            }
            return $clean;
        }
        if (is_object($value)) {
            $clean = new stdClass();
            foreach ((array) $value as $key => $item) {
                $clean->{$key} = $this->sanitizeUtf8Recursively($item);
            }
            return $clean;
        }
        if (is_string($value)) {
            if (!preg_match('//u', $value)) {
                $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
                if (is_string($converted) && $converted !== '') {
                    return $converted;
                }
                return '';
            }
        }
        return $value;
    }

    protected function requestJson($method, $path, array $options)
    {
        return $this->requestJsonWithBearerToken($method, $path, $this->getAccessToken(), $options);
    }

    protected function requestJsonWithBearerToken($method, $path, $bearerToken, array $options)
    {
        $headers = array(
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $bearerToken,
        );

        if (!empty($options['json'])) {
            $headers['Content-Type'] = 'application/json';
        }

        if (!empty($options['headers']) && is_array($options['headers'])) {
            $headers = array_merge($headers, $options['headers']);
        }

        $requestOptions = $options;
        $baseUrl = '';
        if (isset($requestOptions['baseUrl'])) {
            $baseUrl = trim((string) $requestOptions['baseUrl']);
            unset($requestOptions['baseUrl']);
        }

        // Guzzle 6.1 triggers PHP 8.2 deprecation warnings when query is passed as array
        // because it calls http_build_query(..., null, ...). Encode query ourselves.
        if (array_key_exists('query', $requestOptions)) {
            if (is_array($requestOptions['query'])) {
                $requestOptions['query'] = http_build_query($requestOptions['query'], '', '&', PHP_QUERY_RFC3986);
            } elseif ($requestOptions['query'] === null) {
                unset($requestOptions['query']);
            }
        }

        $requestOptions['headers'] = $headers;
        $requestOptions['http_errors'] = false;

        if ($baseUrl === '') {
            $baseUrl = $this->getApiBaseUrl();
        }

        $response = $this->http->request($method, rtrim($baseUrl, '/') . $path, $requestOptions);
        $statusCode = (int) $response->getStatusCode();
        $rawBody = (string) $response->getBody();

        $decoded = array();
        if ($rawBody !== '') {
            $decoded = json_decode($rawBody, true);
            if (!is_array($decoded)) {
                $decoded = array();
            }
        }

        if ($statusCode >= 400) {
            throw new RuntimeException($this->buildErrorMessage($statusCode, $decoded, $rawBody));
        }

        if (!empty($decoded['errors']) && is_array($decoded['errors'])) {
            throw new RuntimeException($this->buildErrorMessage($statusCode, $decoded, $rawBody));
        }

        return $decoded;
    }

    protected function buildErrorMessage($statusCode, array $decoded, $rawBody)
    {
        if (!empty($decoded['errors']) && is_array($decoded['errors']) && !empty($decoded['errors'][0])) {
            $error = $decoded['errors'][0];
            $errorId = (string) $this->arrayValue($error, 'errorId', '');
            $message = (string) $this->arrayValue($error, 'message', 'Erreur eBay');
            $longMessage = (string) $this->arrayValue($error, 'longMessage', '');
            return trim('HTTP ' . $statusCode . ' - ' . $errorId . ' ' . $message . ' ' . $longMessage);
        }

        if (!empty($decoded['error'])) {
            $message = 'HTTP ' . $statusCode . ' - ' . $decoded['error'];
            if (!empty($decoded['error_description'])) {
                $message .= ' - ' . $decoded['error_description'];
            }
            return $message;
        }

        if (!empty($decoded['error_id'])) {
            $message = 'HTTP ' . $statusCode . ' - ' . $decoded['error_id'];
            if (!empty($decoded['error_description'])) {
                $message .= ' - ' . $decoded['error_description'];
            }
            return $message;
        }

        return 'HTTP ' . $statusCode . ' - ' . substr($rawBody, 0, 500);
    }

    protected function getAccessToken()
    {
        if ($this->accessToken !== '' && (time() + 30) < $this->accessTokenExpiresAt) {
            return $this->accessToken;
        }

        if ($this->directAccessToken !== '') {
            return $this->directAccessToken;
        }

        try {
            $this->refreshAccessToken(false);
            return $this->accessToken;
        } catch (Exception $e) {
            $fallbackToken = $this->getRefreshToken($this->resolveGroupId());
            $directTokenError = '';
            if ($this->isValidDirectAccessToken($fallbackToken, $directTokenError)) {
                $this->directAccessToken = $fallbackToken;
                return $this->directAccessToken;
            }
            $message = trim((string) $e->getMessage());
            if ($directTokenError !== '') {
                if ($message !== '') {
                    $message .= ' | ';
                }
                $message .= 'Token direct invalide: ' . $directTokenError;
            }
            if ($message === '') {
                $message = 'Impossible de récupérer un token OAuth eBay valide.';
            }
            throw new RuntimeException($message);
        }
    }

    protected function refreshAccessToken($force)
    {
        if (!$force && $this->accessToken !== '' && (time() + 30) < $this->accessTokenExpiresAt) {
            return;
        }

        $refreshToken = $this->getRefreshToken($this->resolveGroupId());
        if ($refreshToken === '') {
            throw new RuntimeException('Refresh token eBay absent. Lancez OAuth depuis l\'admin.');
        }

        $tokenRequest = array(
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'scope' => implode(' ', $this->getScopes()),
        );
        try {
            $payload = $this->requestToken($tokenRequest);
        } catch (Exception $e) {
            if (stripos($e->getMessage(), 'invalid_scope') === false) {
                throw $e;
            }
            unset($tokenRequest['scope']);
            $payload = $this->requestToken($tokenRequest);
        }

        $token = (string) $this->arrayValue($payload, 'access_token', '');
        if ($token === '') {
            throw new RuntimeException('eBay OAuth: access_token manquant.');
        }

        $expiresIn = (int) $this->arrayValue($payload, 'expires_in', 7200);
        $this->accessToken = $token;
        $this->accessTokenExpiresAt = time() + max(60, $expiresIn);

        if (!empty($payload['refresh_token'])) {
            $idGroup = $this->resolveGroupId();
            if ($idGroup > 0) {
                $this->setEbayGroupSetting($idGroup, 'refresh_token', (string) $payload['refresh_token'], 1);
            }
        }
    }

    protected function requestToken(array $formParams)
    {
        $credentials = base64_encode($this->getClientId() . ':' . $this->getClientSecret());
        $encodedBody = http_build_query($formParams, '', '&', PHP_QUERY_RFC3986);
        $response = $this->http->request('POST', rtrim($this->getApiBaseUrl(), '/') . '/identity/v1/oauth2/token', array(
            'headers' => array(
                'Authorization' => 'Basic ' . $credentials,
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
            ),
            'body' => $encodedBody,
            'http_errors' => false,
        ));

        $statusCode = (int) $response->getStatusCode();
        $rawBody = (string) $response->getBody();
        $decoded = json_decode($rawBody, true);
        if (!is_array($decoded)) {
            $decoded = array();
        }

        if ($statusCode >= 400) {
            throw new RuntimeException($this->buildErrorMessage($statusCode, $decoded, $rawBody));
        }

        return $decoded;
    }

    protected function getApiBaseUrl()
    {
        if ($this->getEbayEnvironment() === 'production') {
            return self::PRODUCTION_API_BASE;
        }
        return self::SANDBOX_API_BASE;
    }

    protected function getMediaBaseUrl()
    {
        if ($this->getEbayEnvironment() === 'production') {
            return self::PRODUCTION_MEDIA_BASE;
        }
        return self::SANDBOX_MEDIA_BASE;
    }

    protected function getAuthBaseUrl()
    {
        if ($this->getEbayEnvironment() === 'production') {
            return self::PRODUCTION_AUTH_BASE;
        }
        return self::SANDBOX_AUTH_BASE;
    }

    protected function getEbayEnvironment()
    {
        $value = strtolower(trim((string) getenv('EBAY_ENV')));
        if ($value === 'production' || $value === 'prod') {
            return 'production';
        }
        return 'sandbox';
    }

    protected function getClientId()
    {
        $env = $this->getEbayEnvironment();
        $envSpecific = ($env === 'production') ? 'EBAY_CLIENT_ID_PRODUCTION' : 'EBAY_CLIENT_ID_SANDBOX';
        $configSpecific = ($env === 'production') ? 'ebay_client_id_production' : 'ebay_client_id_sandbox';
        return $this->getGlobalKeysetValue($envSpecific, 'EBAY_CLIENT_ID', $configSpecific, 'ebay_client_id');
    }

    protected function getClientSecret()
    {
        $env = $this->getEbayEnvironment();
        $envSpecific = ($env === 'production') ? 'EBAY_CLIENT_SECRET_PRODUCTION' : 'EBAY_CLIENT_SECRET_SANDBOX';
        $configSpecific = ($env === 'production') ? 'ebay_client_secret_production' : 'ebay_client_secret_sandbox';
        return $this->getGlobalKeysetValue($envSpecific, 'EBAY_CLIENT_SECRET', $configSpecific, 'ebay_client_secret');
    }

    protected function getRedirectUri()
    {
        $env = $this->getEbayEnvironment();

        $envKeys = ($env === 'production')
            ? array('EBAY_REDIRECT_URI_PRODUCTION', 'EBAY_RUNAME_PRODUCTION')
            : array('EBAY_REDIRECT_URI_SANDBOX', 'EBAY_RUNAME_SANDBOX');

        foreach ($envKeys as $key) {
            $value = trim((string) getenv($key));
            if ($value !== '') {
                return $value;
            }
        }

        $genericKeys = array('EBAY_REDIRECT_URI', 'EBAY_RUNAME');
        foreach ($genericKeys as $key) {
            $value = trim((string) getenv($key));
            if ($value !== '') {
                return $value;
            }
        }

        $cfgEnvKey = ($env === 'production') ? 'ebay_redirect_uri_production' : 'ebay_redirect_uri_sandbox';
        $value = trim((string) $this->getConfigValue($cfgEnvKey));
        if ($value !== '') {
            return $value;
        }

        return trim((string) $this->getConfigValue('ebay_redirect_uri'));
    }

    protected function getMarketplaceId($idGroup = null)
    {
        $idGroup = $this->normalizeGroupId($idGroup);
        if ($idGroup > 0) {
            $settings = $this->getGroupSettingsRow($idGroup);
            if (!empty($settings['marketplaceId'])) {
                return (string) $settings['marketplaceId'];
            }
        }
        return 'EBAY_FR';
    }

    protected function getFixedCategoryId($idGroup = null)
    {
        $idGroup = $this->normalizeGroupId($idGroup);
        $settings = $this->getGroupSettingsRow($idGroup);
        $value = isset($settings['fixedCategoryId']) ? trim((string) $settings['fixedCategoryId']) : '';
        if ($value !== '' && preg_match('/^\d+$/', $value)) {
            return $value;
        }

        return '29223';
    }

    protected function getFixedCategorySource($idGroup = null)
    {
        $idGroup = $this->normalizeGroupId($idGroup);
        $settings = $this->getGroupSettingsRow($idGroup);
        $value = isset($settings['fixedCategoryId']) ? trim((string) $settings['fixedCategoryId']) : '';
        if ($value !== '' && preg_match('/^\d+$/', $value)) {
            if (!empty($settings['__legacy'])) {
                return 'legacy';
            }
            return 'group';
        }
        return 'default';
    }

    protected function getMerchantLocationKey($idGroup = null)
    {
        return $this->resolveMerchantLocationKey($this->normalizeGroupId($idGroup));
    }

    protected function getPaymentPolicyId($idGroup = null)
    {
        return $this->resolveDefaultPaymentPolicyId($this->normalizeGroupId($idGroup));
    }

    protected function getFulfillmentPolicyId($idGroup = null)
    {
        return $this->resolveDefaultFulfillmentPolicyId($this->normalizeGroupId($idGroup));
    }

    protected function getReturnPolicyId($idGroup = null)
    {
        return $this->resolveDefaultReturnPolicyId($this->normalizeGroupId($idGroup));
    }

    protected function getScopes()
    {
        return array(
            'https://api.ebay.com/oauth/api_scope/sell.inventory',
            'https://api.ebay.com/oauth/api_scope/sell.account',
            'https://api.ebay.com/oauth/api_scope/sell.account.readonly',
            'https://api.ebay.com/oauth/api_scope/sell.stores',
            'https://api.ebay.com/oauth/api_scope/sell.fulfillment.readonly',
        );
    }

    protected function getOfferConfigurationError($idGroup = null, array $params = array())
    {
        $idGroup = $this->normalizeGroupId($idGroup);
        $merchantLocationKey = $this->resolveMerchantLocationKey($idGroup);
        $resolvedPolicies = $this->resolvePolicyIdsForOffer($idGroup, $params);
        $missing = array();
        if ($merchantLocationKey === '') {
            $missing[] = 'merchant_location_key';
        }
        if ($resolvedPolicies['paymentPolicyId'] === '') {
            $missing[] = 'default_payment_policy_id';
        }
        if ($resolvedPolicies['fulfillmentPolicyId'] === '') {
            $missing[] = 'default_fulfillment_policy_id';
        }
        if ($resolvedPolicies['returnPolicyId'] === '') {
            $missing[] = 'default_return_policy_id';
        }
        if (!empty($missing)) {
            return 'Configuration eBay REST incomplète pour ce groupe: réglages manquants ' . implode(', ', $missing) . '.';
        }
        return '';
    }

    protected function isSellerRegistrationCompleted()
    {
        try {
            $privilege = $this->requestJson('GET', '/sell/account/v1/privilege', array());
            if (array_key_exists('sellerRegistrationCompleted', $privilege)) {
                return (bool) $privilege['sellerRegistrationCompleted'];
            }
            return true;
        } catch (Exception $e) {
            // If eBay privilege check fails unexpectedly, do not block add/update here.
            return true;
        }
    }

    protected function getRefreshTokenSource($idGroup = null)
    {
        $idGroup = $this->normalizeGroupId($idGroup);
        $token = trim((string) $this->getEbayGroupSetting($idGroup, 'refresh_token', ''));
        if ($token !== '') {
            return 'group';
        }

        return 'none';
    }

    protected function isValidDirectAccessToken($token, &$errorMessage)
    {
        $token = trim((string) $token);
        $errorMessage = '';
        if ($token === '') {
            return false;
        }

        try {
            $this->requestJsonWithBearerToken('GET', '/sell/account/v1/privilege', $token, array());
            return true;
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            return false;
        }
    }

    protected function getRefreshToken($idGroup = null)
    {
        $idGroup = $this->normalizeGroupId($idGroup);
        return trim((string) $this->getEbayGroupSetting($idGroup, 'refresh_token', ''));
    }

    protected function getGlobalKeysetValue($envSpecificName, $envGenericName, $configSpecificName, $configLegacyName = '')
    {
        $envSpecificName = trim((string) $envSpecificName);
        $envGenericName = trim((string) $envGenericName);
        $configSpecificName = trim((string) $configSpecificName);
        $configLegacyName = trim((string) $configLegacyName);

        if ($envSpecificName !== '') {
            $value = trim((string) getenv($envSpecificName));
            if ($value !== '') {
                return $value;
            }
        }

        if ($envGenericName !== '') {
            $value = trim((string) getenv($envGenericName));
            if ($value !== '') {
                return $value;
            }
        }

        if ($configSpecificName !== '') {
            $value = trim((string) $this->getConfigValue($configSpecificName));
            if ($value !== '') {
                return $value;
            }
        }

        if ($configLegacyName !== '') {
            return trim((string) $this->getConfigValue($configLegacyName));
        }

        return '';
    }

    protected function getConfigValue($name)
    {
        if (function_exists('config')) {
            $value = config($name);
            if ($value !== false) {
                return $value;
            }
        }

        $escapedName = $this->escapeSql($name);
        $rows = $this->dbSelect("SELECT `value` FROM `config` WHERE `name`='" . $escapedName . "' LIMIT 1");
        if (empty($rows) || empty($rows[0]['value'])) {
            return '';
        }

        return (string) $rows[0]['value'];
    }

    protected function setConfigValue($name, $value)
    {
        $escapedName = $this->escapeSql($name);
        $escapedValue = $this->escapeSql($value);
        $escapedDescription = $this->escapeSql($this->getConfigDescription($name));

        $rows = $this->dbSelect("SELECT `id` FROM `config` WHERE `name`='" . $escapedName . "' LIMIT 1");
        if (!empty($rows[0]['id'])) {
            $this->dbQuery("UPDATE `config` SET `value`='" . $escapedValue . "' WHERE `id`='" . (int) $rows[0]['id'] . "' LIMIT 1");
            return;
        }

        $maxRows = $this->dbSelect('SELECT MAX(`id`) AS max_id FROM `config`');
        $nextId = 1;
        if (!empty($maxRows[0]['max_id'])) {
            $nextId = ((int) $maxRows[0]['max_id']) + 1;
        }

        $this->dbQuery("INSERT INTO `config` (`id`, `name`, `description`, `value`) VALUES ('" . $nextId . "', '" . $escapedName . "', '" . $escapedDescription . "', '" . $escapedValue . "')");
    }

    protected function getConfigDescription($name)
    {
        switch ($name) {
            case 'ebay_refresh_token':
                return 'eBay OAuth refresh token';
            case 'ebay_client_id_sandbox':
                return 'eBay OAuth sandbox client id (keyset global)';
            case 'ebay_client_secret_sandbox':
                return 'eBay OAuth sandbox client secret (keyset global)';
            case 'ebay_redirect_uri_sandbox':
                return 'eBay OAuth sandbox RuName/redirect URI (keyset global)';
            case 'ebay_client_id_production':
                return 'eBay OAuth production client id (keyset global)';
            case 'ebay_client_secret_production':
                return 'eBay OAuth production client secret (keyset global)';
            case 'ebay_redirect_uri_production':
                return 'eBay OAuth production RuName/redirect URI (keyset global)';
            case 'ebay_marketplace_id':
                return 'eBay marketplace id (REST)';
            case 'ebay_merchant_location_key':
                return 'eBay merchant location key (REST)';
            case 'ebay_payment_policy_id':
                return 'eBay payment policy id (REST)';
            case 'ebay_fulfillment_policy_id':
                return 'eBay fulfillment policy id (REST)';
            case 'ebay_return_policy_id':
                return 'eBay return policy id (REST)';
            default:
                return 'Configuration eBay';
        }
    }

    protected function getSettingSource($envName, $configName)
    {
        $envValue = trim((string) getenv($envName));
        if ($envValue !== '') {
            return 'env';
        }

        $configValue = trim((string) $this->getConfigValue($configName));
        if ($configValue !== '') {
            return 'config';
        }

        return 'none';
    }

    protected function isBusinessPolicyEligibilityError($message)
    {
        $msg = strtolower((string) $message);
        if ($msg === '') {
            return false;
        }

        return (strpos($msg, '20403') !== false ||
                strpos($msg, 'not eligible for business policy') !== false ||
                strpos($msg, 'business policy') !== false);
    }

    protected function resolvePolicyMap($policyType)
    {
        switch ($policyType) {
            case 'payment':
                return array(
                    'collectionPath' => '/sell/account/v1/payment_policy',
                    'idField' => 'paymentPolicyId',
                );
            case 'fulfillment':
                return array(
                    'collectionPath' => '/sell/account/v1/fulfillment_policy',
                    'idField' => 'fulfillmentPolicyId',
                );
            case 'return':
                return array(
                    'collectionPath' => '/sell/account/v1/return_policy',
                    'idField' => 'returnPolicyId',
                );
            default:
                return array();
        }
    }

    protected function resolveSku(array $params)
    {
        $sku = trim((string) $this->arrayValue($params, 'idBook', ''));
        if ($sku === '') {
            $sku = trim((string) $this->arrayValue($params, 'SKU', ''));
        }

        if ($sku === '' && !empty($params['ebayItemId'])) {
            $state = $this->findStateByListingId((string) $params['ebayItemId']);
            if (!empty($state['sku'])) {
                $sku = (string) $state['sku'];
            }
        }

        if ($sku === '') {
            throw new RuntimeException('SKU eBay introuvable (idBook manquant).');
        }

        return $sku;
    }

    protected function normalizeSkuForListing($sku, $idBook = 0)
    {
        $idBook = (int) $idBook;
        if ($idBook > 0) {
            return (string) $idBook;
        }

        $sku = trim((string) $sku);
        if ($sku === '' || !preg_match('/^[0-9]+$/', $sku)) {
            return '';
        }

        $skuInt = (int) $sku;
        if ($skuInt <= 0) {
            return '';
        }

        return (string) $skuInt;
    }

    protected function resolveGroupId()
    {
        if ((int) $this->contextGroupId > 0) {
            return (int) $this->contextGroupId;
        }
        if (!empty($_SESSION['idGroup'])) {
            return (int) $_SESSION['idGroup'];
        }
        return 0;
    }

    protected function findStateByListingId($listingId)
    {
        $listingId = $this->escapeSql($listingId);
        $rows = $this->dbSelect("SELECT * FROM `ebay_listing_state` WHERE `listingId`='" . $listingId . "' LIMIT 1");
        if (empty($rows[0])) {
            return array();
        }
        return $rows[0];
    }

    protected function saveSuccessState($idGroup, $idBook, $sku, $offerId, $listingId, $listingStatus)
    {
        $now = time();
        $createdAt = date('Y-m-d H:i:s', $now);

        $idGroup = (int) $idGroup;
        $idBook = (int) $idBook;

        $escapedSku = $this->escapeSql($sku);
        $escapedOfferId = $this->escapeSql($offerId);
        $escapedListingId = $this->escapeSql($listingId);
        $escapedStatus = $this->escapeSql($listingStatus);
        $escapedMarketplace = $this->escapeSql($this->getMarketplaceId($idGroup));
        $escapedCreatedAt = $this->escapeSql($createdAt);

        $sql = "INSERT INTO `ebay_listing_state` (`idGroup`, `idBook`, `sku`, `offerId`, `listingId`, `listingStatus`, `marketplaceId`, `lastSyncTs`, `lastErrorCode`, `lastErrorMessage`, `createdAt`, `updatedAt`) "
            . "VALUES ('" . $idGroup . "', '" . $idBook . "', '" . $escapedSku . "', '" . $escapedOfferId . "', '" . $escapedListingId . "', '" . $escapedStatus . "', '" . $escapedMarketplace . "', '" . $now . "', '', '', '" . $escapedCreatedAt . "', '" . $escapedCreatedAt . "') "
            . "ON DUPLICATE KEY UPDATE `sku`=VALUES(`sku`), `offerId`=VALUES(`offerId`), `listingId`=VALUES(`listingId`), `listingStatus`=VALUES(`listingStatus`), `marketplaceId`=VALUES(`marketplaceId`), `lastSyncTs`=VALUES(`lastSyncTs`), `lastErrorCode`='', `lastErrorMessage`='', `updatedAt`=VALUES(`updatedAt`)";

        $this->dbQuery($sql);
    }

    protected function saveErrorState($idGroup, $idBook, $sku, $errorCode, $errorMessage)
    {
        $now = time();
        $createdAt = date('Y-m-d H:i:s', $now);

        $idGroup = (int) $idGroup;
        $idBook = (int) $idBook;

        $escapedSku = $this->escapeSql($sku);
        $escapedCode = $this->escapeSql($errorCode);
        $escapedMessage = $this->escapeSql(substr($errorMessage, 0, 65500));
        $escapedMarketplace = $this->escapeSql($this->getMarketplaceId($idGroup));
        $escapedCreatedAt = $this->escapeSql($createdAt);

        $sql = "INSERT INTO `ebay_listing_state` (`idGroup`, `idBook`, `sku`, `offerId`, `listingId`, `listingStatus`, `marketplaceId`, `lastSyncTs`, `lastErrorCode`, `lastErrorMessage`, `createdAt`, `updatedAt`) "
            . "VALUES ('" . $idGroup . "', '" . $idBook . "', '" . $escapedSku . "', '', '', 'ERROR', '" . $escapedMarketplace . "', '" . $now . "', '" . $escapedCode . "', '" . $escapedMessage . "', '" . $escapedCreatedAt . "', '" . $escapedCreatedAt . "') "
            . "ON DUPLICATE KEY UPDATE `lastErrorCode`=VALUES(`lastErrorCode`), `lastErrorMessage`=VALUES(`lastErrorMessage`), `updatedAt`=VALUES(`updatedAt`), `lastSyncTs`=VALUES(`lastSyncTs`)";

        $this->dbQuery($sql);
    }

    protected function clearListingState($idGroup, $idBook, $sku)
    {
        $idGroup = (int) $idGroup;
        $idBook = (int) $idBook;
        $escapedSku = $this->escapeSql($sku);

        if ($idBook > 0) {
            $this->dbQuery("DELETE FROM `ebay_listing_state` WHERE `idGroup`='" . $idGroup . "' AND `idBook`='" . $idBook . "'");
            return;
        }

        if ($escapedSku !== '') {
            $this->dbQuery("DELETE FROM `ebay_listing_state` WHERE `sku`='" . $escapedSku . "'");
        }
    }

    protected function db()
    {
        if ($this->db instanceof DataBase) {
            return $this->db;
        }

        global $babase;
        if (isset($babase) && $babase instanceof DataBase) {
            $this->db = $babase;
            return $this->db;
        }

        $this->db = new DataBase();
        $this->db->DbConnect();
        return $this->db;
    }

    protected function dbSelect($sql)
    {
        $rows = $this->db()->DbSelect($sql, $count);
        if ($count <= 0 || !is_array($rows)) {
            return array();
        }
        return $rows;
    }

    protected function dbQuery($sql)
    {
        return $this->db()->DbQuery($sql);
    }

    protected function escapeSql($value)
    {
        $conn = $this->db()->DB_CONN;
        return mysqli_real_escape_string($conn, (string) $value);
    }

    protected function arrayValue(array $array, $key, $default)
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }
        return $default;
    }
}
