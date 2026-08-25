<tr class="book-result-row-click" data-edit-url="{BOOKS_RESULT_MODIFY}">
    <td class="book-row-no-open">{BOOKS_RESULT_SELECT}</td>
    <td class="action-cell"><a class="table-action table-action--edit" href="{BOOKS_RESULT_MODIFY}">{BOOKS_MODIFY}</a></td>
    <td class="action-cell book-row-no-open"><a class="table-action table-action--delete book-search-delete-link" href="{BOOKS_RESULT_ERASE}" data-confirm-text="{BOOK_CONFIRM_ERASE}" data-no-page-loader="1">{BOOKS_ERASE}</a></td>
    <td>
	    <!-- BEGIN bookPhoto -->
        <img src="{bookPhoto.FILEMINIATURE}" alt="{bookPhoto.DESCRIPTION}">
	    <!-- END bookPhoto -->
    </td>
    <td><a href="{BOOKS_RESULT_MODIFY}">{BOOKS_RESULT_AUTHOR}&nbsp;</a></td>
    <td>{BOOKS_RESULT_TITLE}&nbsp;</td>
    <td>{BOOKS_RESULT_DESCRIPTION}&nbsp;</td>
    <td>{BOOKS_RESULT_PRICE}&nbsp;</td>
    <td>{BOOKS_RESULT_QUANTITY}&nbsp;</td>
    <td class="book-location-cell book-row-no-open">
        <div class="book-location-inline" data-book-id="{BOOKS_RESULT_ID}" data-api-url="{BOOKS_RESULT_LOCATION_SAVE_URL}">
            <button type="button" class="book-location-inline__value" title="Cliquer pour modifier le rayon">{BOOKS_RESULT_LOCATION_LABEL}</button>
            <input type="text" class="book-location-inline__input" value="{BOOKS_RESULT_LOCATION}" maxlength="40" aria-label="Rayon">
            <span class="book-location-inline__status" aria-hidden="true"></span>
        </div>
    </td>
</tr>
