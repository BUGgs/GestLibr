    <div class="container">
        <h1>{PROG}</h1>
        <hr>
        <center>
        <fieldset class="actions">
        <table>
            <tr>
                <td>
                    <h1>&nbsp;</h1>
                </td>
                <td>
                    <form action="?m=cl&a=cr" method="post">
                        <input type="submit" value="Nouveau Client" class="submit" />
                    </form>                    
                </td>
                <td>
                    <form action="?m=cl&a=vi" method="post">
                        <input type="submit" value="Recherche Client" class="submit" />
                    </form>                    
                </td>
            </tr>
            <tr>
                <td>
                    <h1>&nbsp;</h1>
                </td>
                <td>
                    <form action="?m=bo&a=cr" method="post">
                        <input type="submit" value="Nouveau Livre" class="submit" />
                    </form>                    
                </td>
                <td>
                    <form action="?m=bo&a=vi" method="post">
                        <input type="submit" value="Recherche de Livre" class="submit" />
                    </form>                    
                </td>
            </tr>
            <tr>
                <td>
                    <h1>&nbsp;</h1>
                </td>
                <td>
                    <form action="?m=ca&a=cr" method="post">
                        <input type="submit" value="Nouveau Catalogue" class="submit" />
                    </form>                    
                </td>
                <td>
                    <form action="?m=ca&a=vi" method="post">
                        <input type="submit" value="Liste des Catalogues" class="submit" />
                    </form>                    
                </td>
            </tr>
            
        </fieldset>
        <table>
            <tr><td>{TOTAL_BOOKS}</td><td> : {TOTAL_BOOKS_VALUE}</td></tr>
            <tr><td>{TOTAL_BOOKS_AVAILABLE}</td><td> : {TOTAL_BOOKS_AVAILABLE_VALUE}</td></tr>
            <tr><td>{TOTAL_PRICE_AVAILABLE}</td><td> : {TOTAL_PRICE_AVAILABLE_VALUE} €</td></tr>
            <tr><td>{AVERAGE_PRICE_AVAILABLE}</td><td> : {AVERAGE_PRICE_AVAILABLE_VALUE} € / {BOOK}</td></tr>
        </table>
        </center>
    </div>