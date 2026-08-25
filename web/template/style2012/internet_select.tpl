<h1>{INTERNET}</h1>
<hr>
<h2>{SELECTION}</h2>

<FORM ACTION='?m=in&a=se' METHOD='POST' NAME='change_internet'>
  <fieldset class="inputs">
    <SELECT NAME='id' onChange='this.form.submit()'>
      {OPTION_LIST_SELECTED}
      <OPTION VALUE=''>------</option>
      {OPTION_LIST}
    </SELECT>
    <INPUT TYPE='submit' VALUE='{CHANGE}'>
  </fieldset>
</FORM>

<TABLE>
  <tr>
    <td valign="top">
      <TABLE class="bordered">
        <tr>
          <th>{AVAILABLE_CATEGORY}</th>
          <th>{SELECTED_CATEGORY}</th>
        </tr>
        <TR>
          <TD>
              {AVAILABLE_LIST}
          </TD>
          <TD>
              {SELECTED_LIST}
          </TD>
        </TR>
      </TABLE>
    </td>
    <td valign="top">
      <TABLE class="bordered">
        <tr>
          <th>{EXCLUDE_CATALOG_LIST}</th>
          <th>{EXCLUDED_CATALOG_LIST}</th>
        </tr>
        <TR>
          <TD>
            {EXCLUDE_LIST}
          </TD>
          <TD>
            {EXCLUDED_LIST}
          </TD>
        </TR>
      </TABLE>
    </td>
  </tr>
</table>
