<h1>{ORDER}</h1>
<hr>
<p>
<form method="POST" action="index.php" target="search">
    <fieldset class="inputs">
        Recherche client: <input type="text" size="20" name="name">
    </fieldset>
    <fieldset class="actions">
        <input type="submit" class="submit" value="rechercher">
    </fieldset>
</form>

<fieldset class="inputs">
    Type d'achat : <input type="radio" name="source" value="catalog" onClick="document.catSearch.hide"/>Catalogue <input type="radio" name="source" value="catalog" onClick="document.catSearch.show"/>Recherche libre
    <div name="catSearch" style="visible:none;">RECHERCHE CATALOGUE</div>
    <div name="freeSearch">RECHERCHE LIBRE</div>
</fieldset>

Panier

</p>
<p>&nbsp;</p><p>&nbsp;</p>
<p>