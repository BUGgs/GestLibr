<?php
/*********************************************/
/**       GestLibr: french.lang.php         **/
/**           French Translation            **/
/**  Romain DUCHENE, june 2003 - june 2005  **/
/*********************************************/

$txt["nonAvZone"]="Zone indisponible pour le moment";
$txt["formIncomplete"]="Formulaire incomplet !";
$txt["formNotNumber"]="Ce n\'est pas un nombre !";
$txt["formNotValidNumber"]=" n\'est pas un nombre valide !";
$txt["formConfirm"]="Voulez-vous vraiment effectuer ";
$txt[""]="";


/* Menu */
$txt["menu"]="MENU";
$txt["clients"]="Clients";
$txt["clientsNew"]="Nouveau";
$txt["clientsView"]="Visualiser";
$txt["books"]="Livres";
$txt["booksNew"]="Nouveau";
$txt["booksView"]="Visualiser";
$txt["catalogs"]="Catalogues";
$txt["catalogsCreate"]="Nouveau Catalogue";
$txt["catalogsView"]="Liste";
$txt["categories"]="Rubriques";
$txt["categoriesNew"]="Nouveau";
$txt["categoriesView"]="Visualiser";
$txt["editions"]="Editions";
$txt["editionsClients"]="Clients";
$txt["internet"]="Internet";
$txt["ebay"]="Ebay";
$txt["exclusion"]="Exclusion";
$txt["configure"]="Configurer";
$txt["selection"]="Selection";
$txt["synchro"]="Synchroniser";
$txt["quit"]="Quitter";

/* --------------- Login -------------------- */
$txt["login"]="Connexion";
$txt["username"]="Utilisateur";
$txt["password"]="Mot de passe";
$txt["ForgetPassword"]="Mot de passe oublié ?";
$txt["loginFailed"]="Utilisateur ou mot de passe invalide";

/* --------------- Clients -------------------- */
/* Clients */
$txt["clientsHome"]="Espace de gestion des clients";
$txt["organisation"]="Société";
$txt["name"]="Nom";
$txt["firstname"]="Prénom";
$txt["civility"]="Civilité";
$txt["address"]="Adresse";
$txt["postCode"]="Code Postal";
$txt["city"]="Ville";
$txt["country"]="Pays";
$txt["entryDate"]="Date de création";
$txt["notes"]="Notes";
$txt["telephone"]="Téléphone";
$txt["telephone2"]="Téléphone 2";
$txt["mobile"]="Mobile";
$txt["fax"]="Fax";
$txt["email"]="E-Mail";
$txt["active"]="Actif";
$txt["searchList"]="Liste de recherche";
$txt["createClient"]="Création Fiche Client";
$txt["submit"]="Enregistrer";
$txt["clientCreateOk"]="La création de la fiche Client a été effectuée succés.";
$txt["clientCreateMissedField"]="Les champs obligatoires ne sont pas remplis. <br/>Vous devez au moins remplir le Nom ou la Société.";
$txt["clientCreateDbError"]="La création de la fiche Client a échoué en raison d'un problème dans la base de données.";
$txt["clientDatabase"]="Fichier Client";

/* Client Search form */
$txt["clientSearchTxt"]="Recherche client";
$txt["search"]="Rechercher";

/* Search results */
$txt["numResultsFound"]="Nombre de résultats trouvés";
$txt["modify"]="Modifier";
$txt["erase"]="Effacer";
$txt["confirmDelete"]="Veuillez confirmer cet effacement";

/* Client Record Modification */
$txt["clientModification"]="Modification de Fiche Client";
$txt["clientModificationDone"]="Modification Enregistrée";
$txt["clientSearchCacheSubmit"]="Retourner aux résultats";
$txt["clientEraseDone"]="Fiche client effacée";
$txt["clientEraseNotOk"]="L'effacement n'a pas pu être effectué";

/* --------------- Books -------------------- */
$txt["book"]="livre";
$txt["booksHome"]="Espace de gestion des Ouvrages";
$txt["id"]="ID";
$txt["author"]="Auteur";
$txt["editor"]="Editeur";
$txt["language"]="Langage";
$txt["collection"]="Collection";
$txt["title"]="Titre";
$txt["description"]="Description";
$txt["price"]="Prix";
$txt["price_ebay"]="Ebay";
$txt["price_amazon"]="Amazon";
$txt["quantity"]="Quantité";
$txt["onInternet"]="Sur Internet";
$txt["onEbay"]="Sur Ebay";
$txt["bookInCatalog"]="Parution catalogue";
$txt["bookModifyDate"]="Modification";
$txt["location"]="Rayon";
$txt["isbn"]="ISBN";
$txt["publishDate"]="Publication";
$txt["publishLocation"]="Lieu";
$txt["format"]="Format";
$txt["pageNumber"]="Pages";
$txt["binding"]="Reliure";
$txt["ebayCategory"]="Cat Ebay";
$txt["ebayCategorySecondary"]="Cat Ebay 2";
$txt["ebayItemId"]="ID Ebay";
$txt["ebayLastSync"]="Sync Ebay";
$txt["ebayShippingCost"]="Livraison";
$txt["ebayShippingCostInternational"]="Internationnale";
$txt["photos"]="Photos";
$txt["condition"]="Etat";
$txt[""]="";

$txt["searchList"]="Liste de recherche";
$txt["createBook"]="Création Fiche Ouvrage";
$txt["bookCreateOk"]="La création de la fiche Ouvrage a été effectuée succés.";
$txt["bookCreateMissedField"]="Les champs obligatoires ne sont pas remplis. <br/>Vous devez au moins remplir l'auteur, et le titre.";
$txt["bookDatabase"]="Fichier Ouvrage";
$txt["addInCatalogDone"]="Ajout dans le catalogue effectué";
$txt["addToCatalog"]="Ajouter à un catalogue";
$txt["bookInCatalogConfirmErase"]="Confirmez vous l\'effacement de cet ouvrage du catalogue : ";
$txt["addQtyDone"]="Exemplaire ajouté";
$txt["delQtyDone"]="Exemplaire enlevé";

/* book Search form */
$txt["bookSearchTxt"]="Recherche Ouvrage";

/* book Stats */
$txt["statistics"]="Statistiques";
$txt["totalBooks"]="Nombre total d'ouvrages";
$txt["totalBooksAvailable"]="Nombre total d'ouvrages disponibles";
$txt["totalPriceAvailable"]="Prix total des ouvrages disponibles";
$txt["averagePriceAvailable"]="Prix moyen par ouvrage disponible";

/* Book Record Modification */
$txt["bookModification"]="Modification de Fiche Ouvrage";
$txt["bookModificationDone"]="Modification Enregistrée";
$txt["bookSearchCacheSubmit"]="Retourner aux résultats";
$txt["bookConfirmErase"]="Effacer la fiche ouvrage : ";
$txt["bookEraseDone"]="Fiche effacée : ";
$txt["bookEraseNotOk"]="L'effacement n'a pas pu être effectué : ";
$txt["bookCreateDbError"]="La création de la fiche Ouvrage a échoué en raison d'un problème dans la base de données.";
$txt["addCategoryDone"]="Ajout de la rubrique effectué avec succès";
$txt["backToRecord"]="Retour à la fiche";
$txt["bookInCatConfirmErase"]="Enlever de la fiche ouvrage la rubrique : ";
$txt["delCategory"]="Effacement d'un rubrique d'un ouvrage";
$txt["delCategoryDone"]="Ouvrage enlevé de la rubrique avec succès";
$txt["delInCatalogOk"]="Effacement de la fiche du catalogue effectué";
$txt["delInCatalogNotOk"]="Erreur lors de l'effacement dans le catalogue";
$txt["delPhotoDone"]="Effacement de la photo effectué";
$txt["addPhotoDone"]="Ajout de la photo effectué";

/* ISBN Search */
$txt["IsbnOnlineSearch"]="Recherche ISBN en ligne";
$txt["infoLink"]="Lien d'infos";
$txt["isbnNotFound"]="ISBN non trouvé en ligne !";
$txt["createFromIsbn"]="Création d'une fiche via ISBN";
$txt["returnIsbn"]="Retour Création ISBN";

/* --------------- Categories -------------------- */
$txt["category"]="Rubriques";
$txt["categoriesHome"]="Espace de gestion des Rubriques";
$txt["categoriesName"]="Nom de la Rubrique";
$txt["categoriesDescription"]="Description";

$txt["createCategorie"]="Création d'une Rubrique";
$txt["categoriesCreateOk"]="La création de la Rubrique a été effectuée avec succés.";
$txt["categoriesCreateMissedField"]="Les champs obligatoires ne sont pas remplis. <br/>Vous devez au moins remplir le nom de la rubrique.";
$txt["categoriesDatabase"]="Rubriques";
$txt["addCategory"]="Ajouter une rubrique";
$txt["addThisCategory"]="Ajouter cette rubrique";
$txt["quantity"]="Quantité";
$txt["nbCatAvail"]="Nombre de rubriques disponibles : ";


/* categorie list */
$txt["categorieList"]="Liste des Rubriques diponibles";

/* categorie stats */
$txt["totalCategories"]="Nombre total de rubriques";

/* categorie Search form */
$txt["categorieSearchTxt"]="Recherche d'une Rubrique";

/* categorie Record Modification */
$txt["categoriesModification"]="Modification d'une Rubrique";
$txt["categoriesModificationDone"]="Modification Enregistrée";
$txt["categoriesSearchCacheSubmit"]="Retourner aux résultats";
$txt["categoriesConfirmErase"]="Effacer la fiche rubrique (des ouvrages et clients pourront se retrouver sans rubrique) : ";
$txt["categoriesEraseDone"]="Rubrique effacée : ";
$txt["categoriesEraseNotOk"]="L'effacement n'a pas pu être effectué : ";

/* --------------- Catalog -------------------- */
$txt["bypassNoQuantity"]="Afficher les quantités à zéro";
$txt["catalogCreateOk"]="Création d'un nouveau catalogue effectuée avec succès";
$txt["catalogDbError"]="Erreur lors de la création d'un nouveau catalogue";
$txt["catalogCreateMissedField"]="Les champs obligatoires ne sont pas remplis. <br/>Vous devez au moins remplir le nom du catalogue.";
$txt["catalogModificationDone"]="Modification du catalogue effectuée.";
$txt["catalogModification"]="Modification d'un catalogue";
$txt["catalogEraseDone"]="L'effacement s'est déroulé correctement pour le catalogue ";
$txt["catalogEraseNotOk"]="Erreur lors de l'effacement du catalogue ";
$txt["catalogConfirmErase"]="Confirmez vous l\'effacement définitif du catalogue ";
$txt["locked"]="Vérouillé";
$txt["unlocked"]="Dévérouillé";
$txt["errorCatalogLocked"]="Ce catalogue est VEROUILLE. Vous ne pouvez pas le modifier ou l\'effacer. Il ne peut être que généré.";
$txt["errorCatalogNotLocked"]="Ce catalogue n\'est pas VEROUILLE. Vous ne pouvez pas le générer.";
$txt["catalogEdit"]="Edition du catalogue";
$txt["edit"]="Editer";
$txt["generate"]="Générer";
$txt["booksInCatalog"]="ouvrages dans le catalogue";
$txt["catalogAddSearch"]="Ajouter un résultat de recherche";
$txt["catalogAddChapter"]="Ajouter un chapitre";
$txt["catalogAddTitle"]="Ajouter une page à remplir";
$txt["catalogAddNewPage"]="Ajouter un saut de page";
$txt["content"]="Contenu";
$txt["up"]="Monter";
$txt["down"]="Descendre";
$txt["recordUp"]="L'enregistrement a été remonté.";
$txt["recordDown"]="L'enregistrement a été descendu.";
$txt["deleteRecordInCatalogOk"]="L'effacement de l'enregistrement du catalogue s'est déroulé correctement.";
$txt["addBefore"]="Ajouter Avant";
$txt["addToEnd"]="Ajouter à la fin";
$txt["addDone"]="Ajout effectué";
$txt["errorInAdd"]="Erreur lors de l'ajout";
$txt["generateCatalog"]="Génération du Catalogue";
$txt["generateHtmlView"]="Générer une vue HTML";
$txt["generatePDFCatalog"]="Générer un catalogue PDF";
$txt["generatePDFCatalogWithLocation"]="Générer un catalogue PDF avec localisation";
$txt["chapterTitle"]="Titre du chapitre";
$txt["catalogTemplatePage"]="Pages de template pour le catalogue";
$txt["catalogTitleWizard"]="Wizard de création de page de couverture";
$txt["sortIntoChapter"]="Classement intra-chapitre";
$txt["sortIntoChapterDone"]="Classement intra-chapitre effectué";

/* --------------- Internet -------------------- */
$txt["catList"]="Liste des Rubriques";
$txt["syncOK"]="Synchronisation effectuée avec succès";
$txt["syncKO"]="Erreur lors de la synchronisation";
$txt["new"]="Nouveau";
$txt["change"]="Changer";
$txt["method"]="Méthode";
$txt["parameters"]="Paramètres";
$txt["template"]="Template";
$txt["header"]="Header";
$txt["seletedCategories"]="Rubriques sélectionnées";
$txt["synchroLaunch"]="Lancement de la synchronisation";
$txt["excludeCatalogList"]="Liste des Catalogues pouvant être exclus";
$txt["excludedCatalogList"]="Liste des Catalogues exclus";
$txt["exclusion"]="Exclusion";
$txt["resetOnInternetCatalog"]="Remettre à zéro (tous les ouvrages seront activés sur Internet)";
$txt["chooseCatalogToDisable"]="Choisissez le catalogue à désactiver d'Internet";
$txt["catalogExcluded"]="Catalogue exclu";
$txt["catalogExclusionReseted"]="Exclusions remises à zéro";
$txt["addIdBookOnEbay"]="Ajouter un ouvrage (idBook) sur Ebay";
$txt["delIdBookOnEbay"]="Supprimer un ouvrage (idBook) sur Ebay";
$txt["updateAllBooksOnEbay"]="Mettre à jour toutes les fiches sur Ebay";
$txt["getEbayOrders"]="Voir les commandes Ebay";
$txt["unknownIdBook"]="Ouvrage (idBook) inconnu";
$txt["bookAddedOnEbayWithItemID"]="Livre ajouté correctement avec l'itemID : ";
$txt["errorDuringAddingBookOnEbay"]="Une erreur s'est produite lors de l'ajout de l'ouvrage : ";
$txt["bookDeletedOnEbayOk"]="Livre correctement supprimé de Ebay";
$txt["bookDeletedOnEbayError"]="Erreur durant la suppression du livre de Ebay : ";
$txt["bookUpdatedOnEbay"]="Mise à jour sur Ebay (idBook) : ";
$txt["bookUpdatedOnEbayOk"]="Mise à jour Ok sur Ebay de l'item : ";
$txt["bookUpdatedOnEbayError"]="Erreur lors de la mise à jour sur Ebay de l'idBook : ";
$txt["AllBookUpdatedOnEbayOk"]="Mise à jour Ebay terminée !";

/* --------------- Orders -------------------- */
$txt["createOrder"]="Créer une commande";
$txt["createOrderDone"]="Création de la commande effectuée";
$txt["orders"]="Commandes";
$txt["ordersCreate"]="Créer";
$txt["ordersView"]="Rechercher";

/* --------------- Editions -------------------- */
$txt["labelActivesCustomers"]="Etiquettes clients actifs";
$txt["listingCategoryOrdersActivesCustomers"]="Listing des clients actifs avec catégorie et achats catalogue";


/* --------------- Others -------------------- */
$txt["returnToList"]="Retour à la liste";
$txt["notNull"]="Non nulle";
$txt["add"]="Ajouter";
$txt["del"]="Supprimer";
$txt["update"]="Mise à jour";
$txt["order"]="Commande";
$txt["insert"]="Insérer";
$txt["validate"]="Valider";
$txt["ok"]="Ok";
$txt["error"]="Erreur...";
$txt["selectAll"]="Tout cocher";
$txt["unselectAll"]="Tout décocher";
$txt["forSelection"]="Pour la sélection";
$txt["addSelectionToCatalog"]="Ajouter la sélection au catalogue";
$txt["booksToAddToCatalog"]="Livres à ajouter au catalogue : ";
$txt["clicToReturnToMain"]="Cliquez ici pour revenir au menu principal";
$txt["previous"]="Précédent";
$txt["next"]="Suivant";
$txt["nbResultDisplayed"]="Résultats affichés : ";
$txt["nbPerPage"]="Nb / page : ";
$txt[""]="";

?>