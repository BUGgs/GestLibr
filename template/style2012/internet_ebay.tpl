<h1>{EBAY}</h1>
<hr>
<fieldset class="inputs">
<TABLE class="bordered">
    <TR>
        <TD>{ADD_BOOK_FROM_ID} </TD>
        <TD><FORM ACTION='?m=in&a=eb' METHOD='GET' NAME='add'><INPUT TYPE='hidden' NAME='m' value='in'><INPUT TYPE='hidden' NAME='a' value='eb'><INPUT TYPE='text' SIZE='5' NAME='addIdBook'><input type='submit' value='{ADD}' /></FORM></TD>
    </TR>
    <TR>
        <TD>{DEL_BOOK_FROM_ID} </TD>
        <TD><FORM ACTION='?m=in&a=eb' METHOD='GET' NAME='del'><INPUT TYPE='hidden' NAME='m' value='in'><INPUT TYPE='hidden' NAME='a' value='eb'><INPUT TYPE='text' SIZE='5' NAME='delIdBook'><input type='submit' value='{DEL}' /></FORM></TD>
    </TR>
    <TR>
        <TD>{UPDATE_BOOK_FROM_ID} </TD>
        <TD><FORM ACTION='?m=in&a=eb' METHOD='GET' NAME='update'><INPUT TYPE='hidden' NAME='m' value='in'><INPUT TYPE='hidden' NAME='a' value='eb'><INPUT TYPE='text' SIZE='5' NAME='update'><input type='submit' value='{UPDATE}' /></FORM></TD>
    </TR>
    <TR>
        <TD>{SYNC_ALL_BOOKS_ON_EBAY} </TD>
        <TD><FORM ACTION='?m=in&a=eb' METHOD='GET' NAME='update'><INPUT TYPE='hidden' NAME='m' value='in'><INPUT TYPE='hidden' NAME='a' value='eb'><INPUT TYPE='hidden' NAME='updateAll' value='1'><input type='submit' value='{UPDATE}' /></FORM></TD>
    </TR>
    <TR>
        <TD>{GET_EBAY_ORDERS} </TD>
        <TD><FORM ACTION='?m=in&a=eb' METHOD='GET' NAME='update'><INPUT TYPE='hidden' NAME='m' value='in'><INPUT TYPE='hidden' NAME='a' value='eb'><INPUT TYPE='hidden' NAME='getOrders' value='1'><input type='submit' value='{UPDATE}' /></FORM></TD>
    </TR>
</TABLE>
</fieldset>
<div class="err">{MSG}</div>
