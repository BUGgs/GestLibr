<section class="dashboard">
    <div class="dashboard-header">
        <h1>{PROG}</h1>
        <hr>
        <p class="intro-text">Tableau de bord principal de l'application.</p>
    </div>

    <div class="quick-grid">
        <form action="?m=cl&a=cr" method="post" class="quick-card">
            <input type="submit" value="Nouveau Client" class="submit" />
            <p class="quick-card__hint">Creer une nouvelle fiche client.</p>
        </form>

        <form action="?m=cl&a=vi" method="post" class="quick-card">
            <input type="submit" value="Recherche Client" class="submit" />
            <p class="quick-card__hint">Retrouver et modifier un client existant.</p>
        </form>

        <form action="?m=bo&a=cr" method="post" class="quick-card">
            <input type="submit" value="Nouveau Livre" class="submit" />
            <p class="quick-card__hint">Ajouter un nouvel ouvrage au stock.</p>
        </form>

        <form action="?m=bo&a=vi" method="post" class="quick-card">
            <input type="submit" value="Recherche de Livre" class="submit" />
            <p class="quick-card__hint">Consulter les fiches livres de la base.</p>
        </form>

        <form action="?m=ca&a=cr" method="post" class="quick-card">
            <input type="submit" value="Nouveau Catalogue" class="submit" />
            <p class="quick-card__hint">Construire un nouveau catalogue de vente.</p>
        </form>

        <form action="?m=ca&a=vi" method="post" class="quick-card">
            <input type="submit" value="Liste des Catalogues" class="submit" />
            <p class="quick-card__hint">Acceder aux catalogues existants.</p>
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-item">
            <span class="stat-item__label">{TOTAL_BOOKS}</span>
            <span class="stat-item__value">{TOTAL_BOOKS_VALUE}</span>
        </div>
        <div class="stat-item">
            <span class="stat-item__label">{TOTAL_BOOKS_AVAILABLE}</span>
            <span class="stat-item__value">{TOTAL_BOOKS_AVAILABLE_VALUE}</span>
        </div>
        <div class="stat-item">
            <span class="stat-item__label">{TOTAL_PRICE_AVAILABLE}</span>
            <span class="stat-item__value">{TOTAL_PRICE_AVAILABLE_VALUE} EUR</span>
        </div>
        <div class="stat-item">
            <span class="stat-item__label">{AVERAGE_PRICE_AVAILABLE}</span>
            <span class="stat-item__value">{AVERAGE_PRICE_AVAILABLE_VALUE} EUR / {BOOK}</span>
        </div>
    </div>
</section>
