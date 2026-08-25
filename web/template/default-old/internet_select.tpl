<center>
<p><h2>{INTERNET}<br/>
{SELECTION}</h2></p>
<hr>
</center>
<FORM ACTION='?m=in&a=se' METHOD='POST' NAME='change_internet'>
<SELECT NAME='id' onChange='this.form.submit()'>
{OPTION_LIST_SELECTED}
<OPTION VALUE=''>------</option>
{OPTION_LIST}
</SELECT>
<INPUT TYPE='submit' VALUE='{CHANGE}'>
</FORM>

<TABLE BORDER='0'>
<tr>
  <td valign='top'>
  <TABLE border='1'>
  <TR><TD VALIGN='top'>
    {AVAILABLE_CATEGORY}<br />
    {AVAILABLE_LIST}
    </TD>
    <TD VALIGN='top'>
    {SELECTED_CATEGORY}<br />
    {SELECTED_LIST}
    </TD>
  </TR>
  </TABLE>
  </td>
  <td valign='top'>
    <TABLE border='1'>
    <TR><TD VALIGN='top'>
    {EXCLUDE_CATALOG_LIST}<br />
    {EXCLUDE_LIST}
    </TD>
    <TD VALIGN='top'>
    {EXCLUDED_CATALOG_LIST}<br />
    {EXCLUDED_LIST}
    </TD></TR>
    </TABLE>
  </td>
</tr>
</table>
