<h1>{BOOK_ISBN_ONLINE_SEARCH}</h1>
<hr>
<div class="err">{ISBN_MSG}</div>

<form name=isbnSearch method=POST action='{ISBN_FORM}'>
  <fieldset class="inputs">
    <table>
      <tr>
	<td>{ISBN}: </td>
	<td><input type='text' size='18' name='isbn' value="{ISBN_V}" autofocus></td>
      </tr>
    </table>
  </fieldset>
  <fieldset class="actions">
    <input type=submit class="submit" value='{SEARCH_SUBMIT}' />
  </fieldset>
</form>

<!-- BEGIN isbnResult -->
<table>
  <tr>
    <td>{ISBN}</td>
    <td>{isbnResult.ISBN_V}</td>
  </tr>
  <tr>
    <td>{AUTHOR}</td>
    <td>{isbnResult.AUTHOR_V}</td>
  </tr>
  <tr>
    <td>{TITLE}</td>
    <td>{isbnResult.TITLE_V}</td>
  </tr>
  <tr>
    <td>{EDITOR}</td>
    <td>{isbnResult.EDITOR_V}</td>
  </tr>
  <tr>
    <td>{PUBLISHDATE}</td>
    <td>{isbnResult.PUBLISHDATE_V}</td>
  </tr>
  <tr>
    <td>{PAGENUMBER}</td>
    <td>{isbnResult.PAGENUMBER_V}</td>
  </tr>
  <tr>
    <td>{DESCRIPTION}</td>
    <td>{isbnResult.DESCRIPTION_V}</td>
  </tr>
  <tr>
    <td>{IMAGE}</td>
    <td><img src="{isbnResult.IMAGE_V}"></td>
  </tr>
  <tr>
    <td>{INFOLINK}</td>
    <td><a href="{isbnResult.INFOLINK_V}" target="_BLANK">{isbnResult.INFOLINK_V}</a></td>
  </tr>
</table>
<!-- END isbnResult -->
