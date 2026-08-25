<section class="module-home">
    <h1>{ORDER_EBAY_TITLE}</h1>
    <hr>

    <form method="get" action="{ORDER_EBAY_FILTER_FORM}" class="table-tools">
        <input type="hidden" name="m" value="or">
        <input type="hidden" name="a" value="eb">
        <label>Date début <input type="date" name="date_from" value="{ORDER_EBAY_DATE_FROM}"></label>
        <label>Date fin <input type="date" name="date_to" value="{ORDER_EBAY_DATE_TO}"></label>
        <label>Statut
            <select name="status">
                <option value=""{ORDER_EBAY_STATUS_ALL_SELECTED}>Tous</option>
                <option value="NOT_STARTED"{ORDER_EBAY_STATUS_NOT_STARTED_SELECTED}>NOT_STARTED</option>
                <option value="IN_PROGRESS"{ORDER_EBAY_STATUS_IN_PROGRESS_SELECTED}>IN_PROGRESS</option>
                <option value="FULFILLED"{ORDER_EBAY_STATUS_FULFILLED_SELECTED}>FULFILLED</option>
                <option value="CANCELLED"{ORDER_EBAY_STATUS_CANCELLED_SELECTED}>CANCELLED</option>
            </select>
        </label>
        <label>Validation
            <select name="validated">
                <option value="0"{ORDER_EBAY_VALIDATED_ALL_SELECTED}>Toutes</option>
                <option value="1"{ORDER_EBAY_VALIDATED_ONLY_SELECTED}>Ventes validées uniquement</option>
            </select>
        </label>
        <label>Nb/page <input type="number" min="1" max="200" name="limit" value="{ORDER_EBAY_LIMIT}"></label>
        <input type="submit" class="submit" value="Filtrer">
        <a href="?m=or&a=eb&order_sync=1&status={ORDER_EBAY_STATUS}&validated={ORDER_EBAY_VALIDATED}&limit={ORDER_EBAY_LIMIT}&date_from={ORDER_EBAY_DATE_FROM}&date_to={ORDER_EBAY_DATE_TO}" class="submit">Synchroniser commandes eBay</a>
        <a href="{ORDER_EBAY_SELLER_HUB_URL}" target="_blank" class="submit submit--secondary">Ouvrir Seller Hub</a>
    </form>

    <p>Dernière synchro: {ORDER_EBAY_LAST_SYNC} | Statut: {ORDER_EBAY_LAST_SYNC_STATUS} {ORDER_EBAY_LAST_SYNC_STATS}</p>
    <div class="success">{ORDER_EBAY_SYNC_MESSAGE}</div>
    <p>Total commandes eBay: {ORDER_EBAY_TOTAL}</p>
    <div class="err">{ORDER_EBAY_ERROR}</div>

    <table class="bordered">
        <tr>
            <th>Order ID</th>
            <th>Date</th>
            <th>Acheteur</th>
            <th>Montant</th>
            <th>Validée</th>
            <th>Paiement</th>
            <th>Fulfillment</th>
            <th>Nb articles</th>
            <th>Détails</th>
        </tr>
        <!-- BEGIN orderEbayRow -->
        <tr>
            <td>{orderEbayRow.ORDER_ID}</td>
            <td>{orderEbayRow.ORDER_DATE}</td>
            <td>{orderEbayRow.ORDER_BUYER}</td>
            <td>{orderEbayRow.ORDER_TOTAL}</td>
            <td>{orderEbayRow.ORDER_VALIDATED}</td>
            <td>{orderEbayRow.ORDER_PAYMENT_STATUS}</td>
            <td>{orderEbayRow.ORDER_FULFILLMENT_STATUS}</td>
            <td>{orderEbayRow.ORDER_LINE_ITEMS_NB}</td>
            <td>{orderEbayRow.ORDER_DETAILS_HTML}</td>
        </tr>
        <!-- END orderEbayRow -->
    </table>

    <div class="result-nav" style="margin-top:12px;">
        <span class="result-nav__meta">Page {ORDER_EBAY_PAGE}</span>
        <span class="result-nav__links">
            {ORDER_EBAY_PREV_HTML}
            {ORDER_EBAY_NEXT_HTML}
        </span>
    </div>
</section>
