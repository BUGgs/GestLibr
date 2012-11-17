<center>
<p><h2>{ORDER}</h2></p>
<hr>
<p>
<form method="POST" action="index.php" target="search">
Recherche client: <input type="text" size="20" name="name">

<input type="submit" value="rechercher">
</form>

Type d'achat : <input type="radio" name="source" value="catalog" onClick="document.catSearch.hide"/>Catalogue <input type="radio" name="source" value="catalog" onClick="document.catSearch.show"/>Recherche libre

<div name="catSearch" style="visible:none;">RECHERCHE CATALOGUE</div>
<div name="freeSearch">RECHERCHE LIBRE</div>

Panier

</p>
<p>&nbsp;</p><p>&nbsp;</p>
<p>