# phpMyAdmin SQL Dump
# version 2.5.3
# http://www.phpmyadmin.net
#
# Serveur: localhost
# Généré le : Lundi 07 Novembre 2005 à 00:11
# Version du serveur: 4.0.15
# Version de PHP: 4.3.3
# 
# Base de données: `gestlibr`
# 

# --------------------------------------------------------

#
# Structure de la table `book`
#

CREATE TABLE `book` (
  `idBook` int(11) NOT NULL auto_increment,
  `author` text NOT NULL,
  `author2` varchar(100) NOT NULL default '',
  `title` text NOT NULL,
  `title2` varchar(200) NOT NULL default '',
  `description` text NOT NULL,
  `price` varchar(20) NOT NULL default '',
  `quantity` int(11) NOT NULL default '0',
  `notes` text NOT NULL,
  `entryDate` int(11) NOT NULL default '0',
  `modifyDate` int(11) NOT NULL default '0',
  PRIMARY KEY  (`idBook`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

#
# Contenu de la table `book`
#


# --------------------------------------------------------

#
# Structure de la table `bookincategory`
#

CREATE TABLE `bookincategory` (
  `idBookInCategory` int(11) NOT NULL auto_increment,
  `idBook` int(11) NOT NULL default '0',
  `idCategory` int(11) NOT NULL default '0',
  PRIMARY KEY  (`idBookInCategory`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

#
# Contenu de la table `bookincategory`
#


# --------------------------------------------------------

#
# Structure de la table `bookphoto`
#

CREATE TABLE `bookphoto` (
  `idBookPhoto` int(11) NOT NULL auto_increment,
  `name` varchar(200) NOT NULL default '',
  `description` text NOT NULL,
  `file` varchar(150) NOT NULL default '',
  `default` char(1) NOT NULL default '',
  `sizeV` int(11) NOT NULL default '0',
  `sizeH` int(11) NOT NULL default '0',
  `date` int(11) NOT NULL default '0',
  PRIMARY KEY  (`idBookPhoto`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

#
# Contenu de la table `bookphoto`
#


# --------------------------------------------------------

#
# Structure de la table `catalog`
#

CREATE TABLE `catalog` (
  `idCatalog` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `description` text NOT NULL,
  `dateCreate` int(11) NOT NULL default '0',
  `dateModify` int(11) NOT NULL default '0',
  `lock` enum('Y','N') NOT NULL default 'N',
  `nbGeneration` int(11) NOT NULL default '0',
  `bypassNoQuantity` enum('Y','N') NOT NULL default 'N',
  PRIMARY KEY  (`idCatalog`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

#
# Contenu de la table `catalog`
#


# --------------------------------------------------------

#
# Structure de la table `catalogdata`
#

CREATE TABLE `catalogdata` (
  `idCatalogData` int(11) NOT NULL auto_increment,
  `idCatalog` int(11) NOT NULL default '0',
  `orderInCatalog` int(11) NOT NULL default '0',
  `number` int(11) NOT NULL default '0',
  `type` enum('book','chapter','title','newpage','image') NOT NULL default 'book',
  `name` varchar(255) NOT NULL default '',
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL default '0.00',
  `date` int(11) NOT NULL default '0',
  PRIMARY KEY  (`idCatalogData`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

#
# Contenu de la table `catalogdata`
#


# --------------------------------------------------------

#
# Structure de la table `catalogtemplate`
#

CREATE TABLE `catalogtemplate` (
  `idCatalogTemplate` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `type` enum('book','chapter','title','newpage','image') NOT NULL default 'book',
  `content` text NOT NULL,
  `date` int(11) NOT NULL default '0',
  PRIMARY KEY  (`idCatalogTemplate`)
) TYPE=MyISAM AUTO_INCREMENT=5 ;

#
# Contenu de la table `catalogtemplate`
#

INSERT INTO `catalogtemplate` (`idCatalogTemplate`, `name`, `type`, `content`, `date`) VALUES (1, 'Page Vide', 'title', '', 0);
INSERT INTO `catalogtemplate` (`idCatalogTemplate`, `name`, `type`, `content`, `date`) VALUES (2, 'Première de couverture', 'title', '<img src="images/photo-titre.jpg" alt="image" width="700" height="1000">', 0);
INSERT INTO `catalogtemplate` (`idCatalogTemplate`, `name`, `type`, `content`, `date`) VALUES (3, 'Conditions de Ventes', 'title', '<p>\r\n<center>\r\n<table border=\'1\' width=\'700\'><tr><td align=\'center\' size=\'8\'>\r\nConditions de vente\r\n</td></tr></table>\r\n</center></p>\r\n<p>\r\nConformes aux usages du Syndicat de la Librairie Ancienne et Moderne et aux règlements de la Ligue Internationale de la Librairie Ancienne.\r\n</p>\r\n<p>Les prix indiqués sont nets. Le port et l’assurance sont à la charge du client.\r\nRèglement à réception de notre facture ou, au plus tard, à réception des ouvrages, par mandat, chèque bancaire, chèque postal ou virement.\r\n</p>\r\n<pre>\r\nInformations Bancaires (RIB) :\r\n    Domiciliation : BPTP St Orens de Gameville\r\n    Code Banque : 17807\r\n    Code Guichet : 00040\r\n    Compte : 04021037951\r\n    Clé RIB : 92\r\n    Code IBAN : FR76 1780 7000 4004 0210 3795 192  \r\n    Adresse SWIFT : CCBPFRPPTLS\r\n  \r\n\r\nInformations C.C.P. : C.C.P. Dijon 5.488.27 P\r\n    Code IBAN : FR 02 20041   01004   0548827PO25   06\r\n    BIC : PSSTFRPPDIJ                                  \r\n</pre>\r\n<p>Pour tout paiement de l’étranger, les frais bancaires étant très importants, il est recommandé de régler par mandat international ou par chèque sur un compte en France.\r\n</p>\r\n<p>Les commandes par téléphone doivent être confirmées par lettre dans les trois jours qui suivent. Passé ce délai, les ouvrages seront remis en vente.\r\n</p>\r\n<p>Lors d’une première commande, les nouveaux clients recevront un facture pro forma mais les livres leur seront réservés dès leur commande téléphonique ou E-Mail.\r\n</p>\r\n<p>Les livres ne seront repris pour aucun motif, exception faite si la description du catalogue est erronée ou non conforme à la réalité.\r\n</p>\r\n<p>Vus les frais de correspondance élevés, nous n’avisons nos clients, lorsque leur commande ne peut être satisfaite, que si celle-ci atteint une somme importante ou s’ils nous en font la demande expresse.\r\n</p>\r\n\r\n<p align=\'center\'>\r\nIl est impératif de téléphoner avant vos visites à la Librairie.\r\n</p>\r\n<p align=\'center\'>\r\n<b>\r\nTél : 05 61 54 14 18<br />\r\nFax : 05 61 54 39 45<br />\r\nEn cas d’absence : 06 81 46 83 59<br />\r\n</b>\r\n</p>\r\n<p align=\'center\'>\r\nLa librairie sur Internet:<br />\r\nhttp://www.librairieduchene.com<br />\r\nE-Mail : diane.duchene@librairieduchene.com<br />\r\n</p>', 0);
INSERT INTO `catalogtemplate` (`idCatalogTemplate`, `name`, `type`, `content`, `date`) VALUES (4, 'Plan de la Librairie', 'title', '<img src="images/plan-librairie.gif" alt="image" width="700" height="1000">', 0);

# --------------------------------------------------------

#
# Structure de la table `category`
#

CREATE TABLE `category` (
  `idCategory` int(11) NOT NULL auto_increment,
  `name` varchar(200) NOT NULL default '',
  `description` text NOT NULL,
  `createDate` int(11) NOT NULL default '0',
  `modifyDate` int(11) NOT NULL default '0',
  PRIMARY KEY  (`idCategory`)
) TYPE=MyISAM AUTO_INCREMENT=29 ;

#
# Contenu de la table `category`
#

INSERT INTO `category` (`idCategory`, `name`, `description`, `createDate`, `modifyDate`) VALUES (1, 'ALGERIE - AFRIQUE DU NORD', '', 1089379923, 1089379923);
INSERT INTO `category` (`idCategory`, `name`, `description`, `createDate`, `modifyDate`) VALUES (5, 'FRANCE', '', 0, 0);
INSERT INTO `category` (`idCategory`, `name`, `description`, `createDate`, `modifyDate`) VALUES (2, 'INDOCHINE - COREE - VIET-NAM', '', 1089380002, 1116087896);
INSERT INTO `category` (`idCategory`, `name`, `description`, `createDate`, `modifyDate`) VALUES (3, 'PREMIERE GUERRE MONDIALE', '', 1114976648, 1116087911);
INSERT INTO `category` (`idCategory`, `name`, `description`, `createDate`, `modifyDate`) VALUES (4, 'MILITARIA ET HISTOIRE GENERALE', '', 0, 0);
INSERT INTO `category` (`idCategory`, `name`, `description`, `createDate`, `modifyDate`) VALUES (7, 'ITALIE', '', 0, 0);
INSERT INTO `category` (`idCategory`, `name`, `description`, `createDate`, `modifyDate`) VALUES (8, 'AUTRES PAYS', '', 0, 0);
INSERT INTO `category` (`idCategory`, `name`, `description`, `createDate`, `modifyDate`) VALUES (9, 'CAMPAGNE 1939 - 1940', '', 0, 0);
INSERT INTO `category` (`idCategory`, `name`, `description`, `createDate`, `modifyDate`) VALUES (10, 'OCCUPATION - VICHY - RESISTANCE - MEMOIRES 2° G.M.', '', 0, 0);
INSERT INTO `category` (`idCategory`, `name`, `description`, `createDate`, `modifyDate`) VALUES (11, 'SAUVEGARDE 14-18', '', 0, 0);
INSERT INTO `category` (`idCategory`, `name`, `description`, `createDate`, `modifyDate`) VALUES (12, 'DEPORTATION - PRISONNIERS DE GUERRE', '', 0, 0);
INSERT INTO `category` (`idCategory`, `name`, `description`, `createDate`, `modifyDate`) VALUES (13, 'CAMPAGNES D\'AFRIQUE - FRANCE LIBRE', '', 0, 0);
INSERT INTO `category` (`idCategory`, `name`, `description`, `createDate`, `modifyDate`) VALUES (14, 'GUERRE A L\'EST', '', 0, 0);
INSERT INTO `category` (`idCategory`, `name`, `description`, `createDate`, `modifyDate`) VALUES (15, 'CAMPAGNES D\'EUROPE 1944 - 1945', '', 0, 0);
INSERT INTO `category` (`idCategory`, `name`, `description`, `createDate`, `modifyDate`) VALUES (16, 'N/A', '', 0, 0);
INSERT INTO `category` (`idCategory`, `name`, `description`, `createDate`, `modifyDate`) VALUES (17, 'MILITARIA ET HISTOIRE GENERALE', '', 0, 0);
INSERT INTO `category` (`idCategory`, `name`, `description`, `createDate`, `modifyDate`) VALUES (18, 'MARINE', '', 0, 0);
INSERT INTO `category` (`idCategory`, `name`, `description`, `createDate`, `modifyDate`) VALUES (19, 'AVIATION', '', 0, 0);
INSERT INTO `category` (`idCategory`, `name`, `description`, `createDate`, `modifyDate`) VALUES (20, 'HISTORIQUES REGIMENTAIRES', '', 0, 0);
INSERT INTO `category` (`idCategory`, `name`, `description`, `createDate`, `modifyDate`) VALUES (21, 'GUERRE DU PACIFIQUE', '', 0, 0);
INSERT INTO `category` (`idCategory`, `name`, `description`, `createDate`, `modifyDate`) VALUES (22, 'N/A', '', 0, 0);
INSERT INTO `category` (`idCategory`, `name`, `description`, `createDate`, `modifyDate`) VALUES (23, 'RUSSIE - URSS', '', 0, 0);
INSERT INTO `category` (`idCategory`, `name`, `description`, `createDate`, `modifyDate`) VALUES (24, 'ESPAGNE', '', 0, 0);
INSERT INTO `category` (`idCategory`, `name`, `description`, `createDate`, `modifyDate`) VALUES (25, 'ENTOMOLOGIE', '', 0, 0);
INSERT INTO `category` (`idCategory`, `name`, `description`, `createDate`, `modifyDate`) VALUES (26, 'LEGION', '', 0, 0);
INSERT INTO `category` (`idCategory`, `name`, `description`, `createDate`, `modifyDate`) VALUES (27, 'BLINDES - CAVALERIE', '', 0, 0);
INSERT INTO `category` (`idCategory`, `name`, `description`, `createDate`, `modifyDate`) VALUES (28, 'N/A', '', 0, 0);

# --------------------------------------------------------

#
# Structure de la table `customer`
#

CREATE TABLE `customer` (
  `idCustomer` int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  `firstname` varchar(50) NOT NULL default '',
  `civility` varchar(15) NOT NULL default '',
  `organisation` varchar(100) NOT NULL default '',
  `address` text NOT NULL,
  `postCode` varchar(10) NOT NULL default '',
  `city` varchar(60) NOT NULL default '',
  `country` varchar(60) NOT NULL default '',
  `entryDate` int(11) NOT NULL default '0',
  `searchList` text NOT NULL,
  `notes` text NOT NULL,
  `telephone` varchar(20) NOT NULL default '',
  `telephone2` varchar(20) NOT NULL default '',
  `mobile` varchar(20) NOT NULL default '',
  `fax` varchar(20) NOT NULL default '',
  `email` varchar(60) NOT NULL default '',
  `active` char(1) NOT NULL default '',
  PRIMARY KEY  (`idCustomer`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

#
# Contenu de la table `customer`
#


# --------------------------------------------------------

#
# Structure de la table `customerincategory`
#

CREATE TABLE `customerincategory` (
  `idCustomerInCategory` int(11) NOT NULL auto_increment,
  `idCustomer` int(11) NOT NULL default '0',
  `idCategory` int(11) NOT NULL default '0',
  PRIMARY KEY  (`idCustomerInCategory`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

#
# Contenu de la table `customerincategory`
#


# --------------------------------------------------------

#
# Structure de la table `errormsg`
#

CREATE TABLE `errormsg` (
  `idErrormsg` int(11) NOT NULL auto_increment,
  `errorCode` int(11) NOT NULL default '0',
  `lang` varchar(10) NOT NULL default '',
  `text` text NOT NULL,
  PRIMARY KEY  (`idErrormsg`)
) TYPE=MyISAM AUTO_INCREMENT=9 ;

#
# Contenu de la table `errormsg`
#

INSERT INTO `errormsg` (`idErrormsg`, `errorCode`, `lang`, `text`) VALUES (1, 1, 'fr', 'Impossible d\'ajouter cette catégorie : cet ouvrage est déjà dans cette catégorie.');
INSERT INTO `errormsg` (`idErrormsg`, `errorCode`, `lang`, `text`) VALUES (2, 2, 'fr', 'Impossible d\'ajouter la catégorie: l\'ouvrage n\'existe pas.');
INSERT INTO `errormsg` (`idErrormsg`, `errorCode`, `lang`, `text`) VALUES (3, 3, 'fr', 'Impossible d\'ajouter la catégorie: la catégorie n\'existe pas.');
INSERT INTO `errormsg` (`idErrormsg`, `errorCode`, `lang`, `text`) VALUES (4, 4, 'fr', 'Impossible d\'ajouter la catégorie: Erreur lors de l\'ajout, contactez l\'administateur.');
INSERT INTO `errormsg` (`idErrormsg`, `errorCode`, `lang`, `text`) VALUES (5, 5, 'fr', 'Impossible d\'enlever la catégorie de cet ouvrage: l\'ouvrage n\'est déjà pas dans cette catégorie.');
INSERT INTO `errormsg` (`idErrormsg`, `errorCode`, `lang`, `text`) VALUES (6, 6, 'fr', 'Impossible d\'ajouter cette catégorie : ce client est déjà dans cette catégorie.');
INSERT INTO `errormsg` (`idErrormsg`, `errorCode`, `lang`, `text`) VALUES (7, 7, 'fr', 'Impossible d\'ajouter la catégorie: le client n\'existe pas.');
INSERT INTO `errormsg` (`idErrormsg`, `errorCode`, `lang`, `text`) VALUES (8, 8, 'fr', 'Impossible d\'enlever la catégorie de ce client: le client n\'est déjà pas dans cette catégorie.');

# --------------------------------------------------------

#
# Structure de la table `order`
#

CREATE TABLE `order` (
  `idOrder` int(11) NOT NULL auto_increment,
  `refOrder` varchar(255) NOT NULL default '',
  `source` varchar(255) NOT NULL default '',
  `date` int(11) NOT NULL default '0',
  `idCustomer` int(11) NOT NULL default '0',
  `price` decimal(10,2) NOT NULL default '0.00',
  `vtaPrice` decimal(10,2) NOT NULL default '0.00',
  `totalPrice` decimal(10,2) NOT NULL default '0.00',
  `status` varchar(255) NOT NULL default '',
  `idUser` int(11) NOT NULL default '0',
  PRIMARY KEY  (`idOrder`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

#
# Contenu de la table `order`
#


# --------------------------------------------------------

#
# Structure de la table `orderdetails`
#

CREATE TABLE `orderdetails` (
  `idOrderDetails` int(11) NOT NULL auto_increment,
  `idOrder` int(11) NOT NULL default '0',
  `type` varchar(255) NOT NULL default '',
  `id1` varchar(255) NOT NULL default '',
  `id2` varchar(255) NOT NULL default '',
  `name` varchar(255) NOT NULL default '',
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL default '0.00',
  `vta` decimal(10,2) NOT NULL default '0.00',
  `quantity` int(11) NOT NULL default '1',
  `totalPrice` decimal(10,2) NOT NULL default '0.00',
  PRIMARY KEY  (`idOrderDetails`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

#
# Contenu de la table `orderdetails`
#


# --------------------------------------------------------

#
# Structure de la table `orderstatus`
#

CREATE TABLE `orderstatus` (
  `idOrderStatus` int(11) NOT NULL auto_increment,
  `idOrder` int(11) NOT NULL default '0',
  `status` varchar(255) NOT NULL default '',
  `description` text NOT NULL,
  `idUser` int(11) NOT NULL default '0',
  PRIMARY KEY  (`idOrderStatus`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

#
# Contenu de la table `orderstatus`
#


# --------------------------------------------------------

#
# Structure de la table `payement`
#

CREATE TABLE `payement` (
  `idPayement` int(11) NOT NULL auto_increment,
  `idOrder` int(11) NOT NULL default '0',
  `payementType` varchar(255) NOT NULL default '',
  `createDate` int(11) NOT NULL default '0',
  `receivedDate` int(11) NOT NULL default '0',
  `priceToPaid` decimal(10,2) NOT NULL default '0.00',
  `priceReceived` decimal(10,2) NOT NULL default '0.00',
  `data` text NOT NULL,
  `status` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`idPayement`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

#
# Contenu de la table `payement`
#


# --------------------------------------------------------

#
# Structure de la table `shipment`
#

CREATE TABLE `shipment` (
  `idShipment` int(11) NOT NULL auto_increment,
  `idOrder` int(11) NOT NULL default '0',
  `trackType` varchar(255) NOT NULL default '',
  `trackCode` varchar(255) NOT NULL default '',
  `sentDate` int(11) NOT NULL default '0',
  `receivedDate` int(11) NOT NULL default '0',
  `status` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`idShipment`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

#
# Contenu de la table `shipment`
#


# --------------------------------------------------------

#
# Structure de la table `update`
#

CREATE TABLE `update` (
  `idUpdate` int(11) NOT NULL auto_increment,
  `idUser` int(11) NOT NULL default '0',
  `table` varchar(50) NOT NULL default '',
  `type` varchar(50) NOT NULL default '',
  `date` int(11) NOT NULL default '0',
  `comment` text NOT NULL,
  PRIMARY KEY  (`idUpdate`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

#
# Contenu de la table `update`
#

