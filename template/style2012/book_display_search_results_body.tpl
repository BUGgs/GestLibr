<tr>
    <td>{BOOKS_RESULT_SELECT}</td>
    <td><a href="{BOOKS_RESULT_MODIFY}">{BOOKS_MODIFY}</a></td>
    <td><a href="{BOOKS_RESULT_ERASE}" onclick="return confirmLink(this, '{BOOK_CONFIRM_ERASE}')">{BOOKS_ERASE}</a></td>
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
</tr>