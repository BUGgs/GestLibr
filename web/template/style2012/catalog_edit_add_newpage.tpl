<p>&nbsp;</p>
<form name=catalogForm method=POST action='{CATALOG_FORM}'>
    <fieldset class="inputs">
    <table>
        <tr>
            <td>{NAME} :</td>
            <td><input type='text' name='name' size='30' value="{NAME_V}"></td>
        </tr>
        <tr>
            <td>{DESCRIPTION} :</td>
            <td><textarea id='description-catalog' name='description' cols='90' rows='12'>{DESCRIPTION_V}</textarea></td>
        </tr>
    </table>
    </fieldset>
    <fieldset class="actions">
        <td><input type='submit' name='submit' class="submit" value="{SUBMIT}"></td>
    </fieldset>
</form>



