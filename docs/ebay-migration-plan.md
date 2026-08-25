# Plan de migration eBay (ciblé GestLibr)

## 1) Objectif métier retenu
Périmètre demandé (minimal) :
- créer une annonce eBay depuis un livre GestLibr ;
- mettre à jour l'annonce (prix/quantité/contenu) ;
- retirer l'annonce quand le livre est vendu ou désactivé ;
- (optionnel) lire les commandes pour contrôle.

Ce périmètre correspond à votre flux actuel `updateAll` (NEW/SOLD/REMOVED/UPDATE).

## 2) Ce que fait le code actuel
Flux applicatif :
- `web/lib/internet.lib.php` (action `updateAll`) pilote les lots NEW/SOLD/REMOVED/UPDATE.
- `web/class/internet.class.php` appelle :
  - `addOnEbay()` -> `ebay_AddItem()`
  - `delOnEbay()` -> `ebay_EndItem()`
  - `updateEbayItemId()` -> `ebay_ReviseItem()`
  - `getEbayOrder()` -> `ebay_GetOrders()` (debug)
  - `resyncEbay()` -> `ebay_GetSellerList()`
- `web/class/ebay.class.php` utilise le SDK `dts/ebay-sdk-php` (Trading/Shopping API).

## 3) API minimale recommandée aujourd'hui
Best practice eBay actuelle pour la publication/gestion d'annonces : **Sell Inventory API (REST)**.

Mapping minimal proposé (sans changer votre logique métier) :
- `addOnEbay(idBook)`
  1. `createOrReplaceInventoryItem` (SKU = `idBook`)
  2. `createOffer`
  3. `publishOffer`
  4. stocker `listingId` (+ idéalement `offerId`)
- `updateEbayItemId(idBook)`
  1. `createOrReplaceInventoryItem` (maj détails produit)
  2. `getOffers?sku=...` pour retrouver l'offre
  3. `updateOffer` (prix, qty, policies, etc.)
  4. si offre non publiée: `publishOffer`
- `delOnEbay(idBook)`
  1. `getOffers?sku=...`
  2. `withdrawOffer(offerId)`
  3. vider `ebayItemId` localement (comme aujourd'hui)
- `resyncEbay()`
  - `getOffers?sku=...` par SKU local (ou lot piloté côté DB), puis recalage `listingId/status`.
- `getEbayOrder()` (optionnel)
  - remplacer par `Fulfillment API getOrders` (readonly).

## 4) APIs NON nécessaires pour votre besoin
Pour votre usage actuel, pas besoin d'implémenter au départ :
- variation groups (`publishOfferByInventoryItemGroup`) ;
- refund/dispute ;
- realtime inventory callback ;
- bulk migrate ;
- gestion catalogue avancée.

## 5) Pré-requis eBay obligatoires (REST Inventory)
Avant publication d'offre, eBay impose :
- seller opt-in business policies ;
- policies `payment`, `fulfillment`, `return` ;
- une `inventory location` (merchantLocationKey).

Donc l'application doit pouvoir récupérer/configurer :
- `paymentPolicyId`, `fulfillmentPolicyId`, `returnPolicyId` ;
- `merchantLocationKey` ;
- marketplace (`EBAY_FR`).

## 6) Authentification
Les APIs Sell Inventory/Fulfillment nécessitent un **User token OAuth (authorization code grant)**, pas un token applicatif.

Scopes minimaux :
- `sell.inventory`
- `sell.account.readonly` (ou `sell.account` si création/modif policies)
- `sell.fulfillment.readonly` (si lecture commandes)

## 7) Contrainte technique clé de votre projet
Le runtime actuel Docker est en `php:5.6-apache`.

Conséquence :
- votre SDK actuel `dts/ebay-sdk-php` est abandonné/archivé ;
- les SDK REST modernes trouvés sont majoritairement PHP 8.1+.

Donc 2 options réalistes :

### Option A (recommandée pour fiabilité long terme)
Migrer le runtime de l'app en PHP 8.2+, puis utiliser un client REST moderne (SDK généré ou client HTTP standard).

### Option B (migration fonctionnelle immédiate sans upgrade global)
Garder PHP 5.6 et écrire un client REST eBay minimal en `cURL` (maison) derrière votre abstraction existante.

## 8) Plan d'implémentation concret (incrémental)

### Phase 0 - Sécurité & configuration
- sortir les secrets eBay de `web/include/ebay.config.php` ;
- basculer vers `.env.dev` / `.env.prod` (déjà en place pour Docker) ;
- ajouter variables eBay :
  - `EBAY_ENV`, `EBAY_CLIENT_ID`, `EBAY_CLIENT_SECRET`, `EBAY_REDIRECT_URI`
  - `EBAY_REFRESH_TOKEN`
  - `EBAY_MARKETPLACE_ID=EBAY_FR`
  - `EBAY_MERCHANT_LOCATION_KEY`
  - `EBAY_PAYMENT_POLICY_ID`, `EBAY_FULFILLMENT_POLICY_ID`, `EBAY_RETURN_POLICY_ID`

### Phase 1 - Adapter interne sans casser le code
- introduire une interface interne `EbayGateway` avec 4 méthodes métier:
  - `createListing(BookData): ListingRef`
  - `updateListing(BookData, ListingRef): ListingRef`
  - `withdrawListing(BookData, ListingRef): bool`
  - `syncListing(BookData): ListingRef|null`
- conserver `addOnEbay/delOnEbay/updateEbayItemId/resyncEbay` et les faire déléguer au gateway.

### Phase 2 - Implémenter le gateway REST minimal
- gestion OAuth (refresh token -> access token, cache TTL) ;
- endpoints REST minimaux :
  - `PUT /sell/inventory/v1/inventory_item/{sku}`
  - `POST /sell/inventory/v1/offer`
  - `PUT /sell/inventory/v1/offer/{offerId}`
  - `POST /sell/inventory/v1/offer/{offerId}/publish`
  - `POST /sell/inventory/v1/offer/{offerId}/withdraw`
  - `GET /sell/inventory/v1/offer?sku=...`
- mapper votre schéma livre vers payload eBay (titre, description, image, isbn, qty, prix, catégorie).

### Phase 3 - Persistance minimale
- garder `book.ebayItemId` pour `listingId` (compatibilité).
- ajouter **recommandé** :
  - `book.ebayOfferId` (indispensable pour withdraw/update robustes)
  - éventuellement `book.ebayListingStatus`.

### Phase 4 - Bascule & fallback
- feature flag `EBAY_PROVIDER=legacy|rest` ;
- test sandbox complet ;
- passage progressif prod par petits lots (`ebay_max_book_per_batch`).

## 9) Corrections rapides à faire même avant migration complète
Dans le code actuel :
- `web/class/ebay.class.php`: `ebay_GetItemFromSku()` utilise `$itemID` non défini ;
- `web/class/ebay.class.php`: `ebay_GetOrders()` référence `Enums\SeverityCodeType` sans namespace `Trading\Enums` ;
- `web/class/internet.class.php`: `getEbayOrder()` a une date de fin codée en dur (`2020-12-30 15:00`) ;
- `web/include/ebay.config.php`: secrets en clair dans le repo (à retirer immédiatement).

## 10) Décision recommandée
Pour votre objectif (publier/mettre à jour/retirer) et limiter les changements métier :
- garder l'abstraction actuelle ;
- remplacer seulement son backend par un gateway REST Inventory ;
- commencer en mode minimal (single-SKU fixed-price) ;
- ajouter `ebayOfferId` pour fiabilité opérationnelle.

---

## Sources (consultées)

Documentation eBay officielle :
- Inventory API overview: https://developer.ebay.com/api-docs/sell/inventory/overview.html
- Flow inventory -> offer: https://developer.ebay.com/api-docs/sell/static/inventory/inventory-item-to-offer.html
- Required fields for publishing: https://developer.ebay.com/api-docs/sell/static/inventory/publishing-offers.html
- createOrReplaceInventoryItem: https://developer.ebay.com/api-docs/sell/inventory/resources/inventory_item/methods/createOrReplaceInventoryItem
- createOffer: https://developer.ebay.com/api-docs/sell/inventory/resources/offer/methods/createOffer
- publishOffer: https://developer.ebay.com/api-docs/sell/inventory/resources/offer/methods/publishOffer
- withdrawOffer: https://developer.ebay.com/api-docs/sell/inventory/resources/offer/methods/withdrawOffer
- getOffers: https://developer.ebay.com/api-docs/sell/inventory/resources/offer/methods/getOffers
- OAuth scopes/tokens: https://developer.ebay.com/api-docs/static/oauth-scopes.html
- OAuth auth code grant: https://developer.ebay.com/api-docs/static/oauth-authorization-code-grant.html
- Account optInToProgram: https://developer.ebay.com/api-docs/sell/account/resources/program/methods/optInToProgram
- Account fulfillment policies: https://developer.ebay.com/api-docs/sell/account/resources/fulfillment_policy/methods/getFulfillmentPolicies
- Fulfillment getOrders: https://developer.ebay.com/api-docs/sell/fulfillment/resources/order/methods/getOrders

État des librairies PHP :
- dts/ebay-sdk-php (abandonné): https://packagist.org/packages/dts/ebay-sdk-php
- exemple SDK Inventory PHP moderne (PHP 8.1+): https://packagist.org/packages/sapientpro/ebay-inventory-sdk-php

Références code local :
- `web/class/ebay.class.php`
- `web/class/internet.class.php`
- `web/lib/internet.lib.php`
- `web/include/ebay.config.php`
- `docker/php/Dockerfile`
