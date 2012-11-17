<center>
<p><h2>{INTERNET}<br/>
{CONFIGURE}</h2></p>
<hr>
</center>
<FORM ACTION='?m=in&a=co' METHOD='POST' NAME='change_internet'>
<SELECT NAME='id' onChange='this.form.submit()'>
{OPTION_LIST_SELECTED}
<OPTION VALUE=''>------</option>
<OPTION VALUE='-1'>{NEW}</option>
{OPTION_LIST}
</SELECT>
<INPUT TYPE='submit' VALUE='{CHANGE}'>
</FORM>
<FORM ACTION='?m=in&a=co' METHOD='POST' NAME='modify'>
<TABLE>
<INPUT TYPE='hidden' NAME='id' VALUE='{ID_VALUE}'>

<TR><TD>{NAME}: </TD><TD><INPUT TYPE='text' SIZE='20' NAME='name' VALUE='{NAME_VALUE}'></TD></TR>

<TR><TD>{DESCRIPTION}: </TD><TD><TEXTAREA COLS='50' ROWS='2' NAME='description'>{DESCRIPTION_VALUE}</TEXTAREA></TD></TR>

<TR><TD>{METHOD}: </TD><TD><SELECT NAME='method'>
{METHOD_SELECTED}
<OPTION VALUE=''>------</option>
<OPTION VALUE='ftp'>FTP</option>
<OPTION VALUE='ebay'>Ebay</option>
<OPTION VALUE='amazon'>Amazon</option>
<OPTION VALUE='oscommerce'>oscommerce</option>
</SELECT></TD></TR>

<TR><TD>{PARAM}: </TD><TD><INPUT TYPE='text' SIZE='40' NAME='param' VALUE='{PARAM_VALUE}'></TD></TR>

<TR><TD>{TEMPLATE}: </TD><TD><TEXTAREA COLS='70' ROWS='2' name='template'>{TEMPLATE_VALUE}</TEXTAREA></TD></TR>

<TR><TD>{HEADER}: </TD><TD><TEXTAREA COLS='70' ROWS='2' name='header'>{HEADER_VALUE}</TEXTAREA></TD></TR>

<TR><TD>&nbsp;</TD><TD><INPUT TYPE='submit' VALUE='{SAVE}'></TD></TR>

</FORM>
</TABLE>
