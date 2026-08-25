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
    <input type=hidden name="createfromisbn" value='1' />
    <input type=submit class="submit" value='{SEARCH_SUBMIT}' />
  </fieldset>
</form>

