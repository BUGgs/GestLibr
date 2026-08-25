<center>
<p><h2>{INTERNET}<br/>
{SYNCHRO_LAUNCH}</h2></p>
<hr>
</center>
<FONT COLOR='RED'><b>{MSG}</b></FONT>
<FORM ACTION='?m=in&a=sy' METHOD='POST' NAME='change_internet'>
<SELECT NAME='id' onChange='this.form.submit()'>
{OPTION_LIST_SELECTED}
<OPTION VALUE=''>------</option>
{OPTION_LIST}
</SELECT>
<INPUT TYPE='submit' VALUE='{CHANGE}'>
</FORM>

<p>{AVAILABLE_TOTAL_BOOKS}: {NB_BOOKS}</p>
<p>&nbsp;<br />
<A HREF='?m=in&a=sy&sync={ID}'>{SYNCHRO_NOW}</A>
</p>
