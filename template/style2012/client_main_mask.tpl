<form name=clientForm method=POST action='{CLIENTS_FORM}'>
  <fieldset class="inputs">
  <table>
  <tr>
    <td>
      <table>
	<tr><td>{CLIENTS_CIVILITY} : </td><td><input type='text' size='10' name='civility' value="{CIVILITY_V}"></td></tr>
	<tr><td>{CLIENTS_NAME} : </td><td><input type='text' size='35' name='name' value="{NAME_V}" onChange="javascript:this.value=this.value.toUpperCase();" autofocus></td></tr>
	<tr><td>{CLIENTS_FIRSTNAME} : </td><td><input type='text' size='35' name='firstname' value="{FIRSTNAME_V}"></td></tr>
	<tr><td>{CLIENTS_ORGANISATION} : </td><td><input type='text' size='35' name='organisation' value="{ORGANISATION_V}"></td></tr>
	<tr><td>{CLIENTS_ADDRESS} : </td><td><textarea cols='26' rows='3' name='address'>{ADDRESS_V}</textarea></td></tr>
	<tr><td>{CLIENTS_POSTCODE} : </td><td><input type='text' size='8' name='postCode' value="{POSTCODE_V}"></td></tr>
	<tr><td>{CLIENTS_CITY} : </td><td><input type='text' size='35' name='city' value="{CITY_V}" onChange="javascript:this.value=this.value.toUpperCase();"></td></tr>
	<tr><td>{CLIENTS_COUNTRY} : </td><td><input type='text' size='35' name='country' value="{COUNTRY_V}" onChange="javascript:this.value=this.value.toUpperCase();"></td></tr>
      </table>
    </td>
    <td>
      <table>
	<tr><td>{CLIENTS_NOTES} : </td><td><textarea cols='26' rows='2' name='notes'>{NOTES_V}</textarea></td></tr>
	<tr><td>{CLIENTS_TELEPHONE} : </td><td><input type='text' size='15' name='telephone' value="{TELEPHONE_V}"></td></tr>
	<tr><td>{CLIENTS_MOBILE} : </td><td><input type='text' size='15' name='mobile' value="{MOBILE_V}"></td></tr>
	<tr><td>{CLIENTS_FAX} : </td><td><input type='text' size='15' name='fax' value="{FAX_V}"></td></tr>
	<tr><td>{CLIENTS_EMAIL} : </td><td><input type='email' size='35' name='email' value="{EMAIL_V}"></td></tr>
      </table>
    </td>
    <td>
      <table>
      	<tr><td>{CLIENTS_ENTRYDATE} : </td><td>{ENTRYDATE_V}</td></tr>
	<tr><td>{CLIENTS_ACTIVE} : </td><td><input type="checkbox" name='active' {ACTIVE_V}></td></tr>
	<tr><td>{CLIENTS_ORDER} : </td><td>{ORDER_V}</td></tr>
      </table>
    </td>
  </tr>
</table>
</fieldset>

<fieldset class="actions">
  <input type=hidden name=sub value='ok'>
  <input type=hidden name=opt1 value='OPT1_V'>
  <input type=hidden name=opt2 value='OPT2_V'>
  <input type=submit class="submit" value='{CLIENTS_SUBMIT}'>
</fieldset>
</form>


</div>