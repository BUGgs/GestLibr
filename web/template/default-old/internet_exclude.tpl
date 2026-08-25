<center>
<p><h2>{INTERNET}<br/>
{EXCLUSION}</h2></p>
<hr>
<FONT COLOR='RED'>{MSG}</FONT>
</center>
<P>
<A HREF='?m=in&a=ex&resetoninternet=1'>{RESET_ON_INTERNET_CATALOG}</A>
</P>

<P>{CHOOSE_CATALOG_TO_DISABLE} :
<FORM ACTION='?m=in&a=ex' METHOD='POST' NAME='remove_catalog_on_internet'>
<SELECT NAME='removecatalogoninternet' onChange='this.form.submit()'>
<OPTION VALUE=''>------</option>
{OPTION_LIST_CATALOG}
</SELECT>
<INPUT TYPE='submit' VALUE='{CHANGE}'>
</FORM>

<P>{EXCLUDED_CATALOG}</P>
{EXCLUDED_LIST}
