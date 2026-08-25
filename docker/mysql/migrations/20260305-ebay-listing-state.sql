-- Migration production: eBay REST listing state
CREATE TABLE IF NOT EXISTS `ebay_listing_state` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `idGroup` int(11) NOT NULL DEFAULT '0',
  `idBook` int(11) NOT NULL DEFAULT '0',
  `sku` varchar(64) NOT NULL DEFAULT '',
  `offerId` varchar(64) NOT NULL DEFAULT '',
  `listingId` varchar(64) NOT NULL DEFAULT '',
  `listingStatus` varchar(32) NOT NULL DEFAULT '',
  `marketplaceId` varchar(32) NOT NULL DEFAULT '',
  `lastSyncTs` int(11) unsigned NOT NULL DEFAULT '0',
  `lastErrorCode` varchar(64) NOT NULL DEFAULT '',
  `lastErrorMessage` text,
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_group_book` (`idGroup`, `idBook`),
  KEY `idx_offer_id` (`offerId`),
  KEY `idx_listing_id` (`listingId`),
  KEY `idx_sku` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
