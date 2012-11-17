<form method=POST action='{CATEGORIES_FORM}'>

<table width='100%'>
	<tr><td>{CATEGORIES_NAME} : </td><td><input type='text' size='35' name='name' value="{NAME_V}"></td></tr>
	<tr><td>{CATEGORIES_DESCRIPTION} : </td><td><textarea cols='40' rows='10' name='description'>{DESCRIPTION_V}</textarea></td></tr>
</table>

<input type=hidden name=sub value='ok'>
<input type=hidden name=opt1 value='OPT1_V'>
<input type=hidden name=opt2 value='OPT2_V'>
<input type=submit value='{CATEGORIES_SUBMIT}'>
</form>