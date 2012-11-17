<h1>{INTERNET}</h1>
<hr>
<h2>{EXCLUSION}</h2>
<div class="err">{MSG}</div>

<P><A HREF='?m=in&a=ex&resetoninternet=1'>{RESET_ON_INTERNET_CATALOG}</A></P>

<P>{CHOOSE_CATALOG_TO_DISABLE} :
<FORM ACTION='?m=in&a=ex' METHOD='POST' NAME='remove_catalog_on_internet'>
    <fieldset class="inputs">
        <SELECT NAME='removecatalogoninternet' onChange='this.form.submit()'>
            <OPTION VALUE=''>------</option>
            {OPTION_LIST_CATALOG}
        </SELECT>
        <INPUT TYPE='submit' class="submit" VALUE='{CHANGE}'>
    </fieldset>
</FORM>

<P>{EXCLUDED_CATALOG}</P>
{EXCLUDED_LIST}
