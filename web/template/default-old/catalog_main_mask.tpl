<form method=POST action='{CATALOG_FORM}'>

<table width='100%'>
	<tr><td>{CATALOG_NAME} : </td><td><input type='text' size='35' name='name' value="{NAME_V}"></td></tr>
	<tr><td>{CATALOG_DESCRIPTION} : </td><td><textarea cols='40' rows='10' name='description'>{DESCRIPTION_V}</textarea></td></tr>
	<tr><td>{CATALOG_BYPASSNOQUANTITY} : </td><td><input type='text' size='2' name='bypassnoquantity' value="{BYPASSNOQUANTITY_V}"></td></tr>
</table>

<input type=hidden name=sub value='ok'>
<input type=submit value='{CATALOG_SUBMIT}'>
</form>