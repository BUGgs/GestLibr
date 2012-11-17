<h1>{INTERNET}</h1>
<hr>
<h2>{SYNCHRO_LAUNCH}</h2>

<div class="err">{MSG}</div>

<FORM ACTION='?m=in&a=sy' METHOD='POST' NAME='change_internet'>
    <fieldset class="inputs">
        <SELECT NAME='id' onChange='this.form.submit()'>
            {OPTION_LIST_SELECTED}
            <OPTION VALUE=''>------</option>
            {OPTION_LIST}
        </SELECT>
        <INPUT TYPE='submit' class="submit" VALUE='{CHANGE}'>
    </fieldset>
</FORM>

<p>{AVAILABLE_TOTAL_BOOKS}: {NB_BOOKS}</p>
<p>&nbsp;<br />
<A HREF='?m=in&a=sy&sync={ID}'>{SYNCHRO_NOW}</A>
</p>
