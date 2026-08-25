<h1>{WEBSITE}</h1>
<hr>
<fieldset class="inputs">
<TABLE class="bordered">
    <TR>
        <TD>{LIST_WEBSITE_CATEGORIES} </TD>
        <TD><FORM ACTION='?m=in&a=ws' METHOD='GET' NAME='listCategories'><INPUT TYPE='hidden' NAME='m' value='in'><INPUT TYPE='hidden' NAME='a' value='ws'><INPUT TYPE='hidden' NAME='listCategories' value='1'><input type='submit' value='{VIEW}' /></FORM></TD>
    </TR>
    <TR>
        <TD>{UPDATE_WEBSITE_CATEGORIES} </TD>
        <TD><FORM ACTION='?m=in&a=ws' METHOD='GET' NAME='syncAllCategories'><INPUT TYPE='hidden' NAME='m' value='in'><INPUT TYPE='hidden' NAME='a' value='ws'><INPUT TYPE='hidden' NAME='syncAllCategories' value='1'><input type='submit' value='{UPDATE}' /></FORM></TD>
    </TR>
    <TR>
        <TD>{ADD_WEBSITE_CATEGORY_FROM_ID} </TD>
        <TD><FORM ACTION='?m=in&a=ws' METHOD='GET' NAME='addCategory'><INPUT TYPE='hidden' NAME='m' value='in'><INPUT TYPE='hidden' NAME='a' value='ws'><INPUT TYPE='text' SIZE='5' NAME='addCategory'><input type='submit' value='{ADD}' /></FORM></TD>
    </TR>
    <TR>
        <TD>{DEL_WEBSITE_CATEGORY_FROM_ID} </TD>
        <TD><FORM ACTION='?m=in&a=ws' METHOD='GET' NAME='delCategory'><INPUT TYPE='hidden' NAME='m' value='in'><INPUT TYPE='hidden' NAME='a' value='ws'><INPUT TYPE='text' SIZE='5' NAME='delCategory'><input type='submit' value='{DEL}' /></FORM></TD>
    </TR>
    <TR>
        <TD>{UPDATE_WEBSITE_CATEGORY_FROM_ID} </TD>
        <TD><FORM ACTION='?m=in&a=ws' METHOD='GET' NAME='updateCategory'><INPUT TYPE='hidden' NAME='m' value='in'><INPUT TYPE='hidden' NAME='a' value='ws'><INPUT TYPE='text' SIZE='5' NAME='updateCategory'><input type='submit' value='{UPDATE}' /></FORM></TD>
    </TR>

    <TR>
        <TD>{LIST_WEBSITE_BOOKS} </TD>
        <TD><FORM ACTION='?m=in&a=ws' METHOD='GET' NAME='listBooks'><INPUT TYPE='hidden' NAME='m' value='in'><INPUT TYPE='hidden' NAME='a' value='ws'><INPUT TYPE='hidden' NAME='listBooks' value='1'><input type='submit' value='{VIEW}' /></FORM></TD>
    </TR>
    <TR>
        <TD>{UPDATE_WEBSITE_BOOKS} </TD>
        <TD><FORM ACTION='?m=in&a=ws' METHOD='GET' NAME='syncAllBooks'><INPUT TYPE='hidden' NAME='m' value='in'><INPUT TYPE='hidden' NAME='a' value='ws'><INPUT TYPE='hidden' NAME='syncAllBooks' value='1'><input type='submit' value='{UPDATE}' /></FORM></TD>
    </TR>
    <TR>
        <TD>{ADD_WEBSITE_BOOK_FROM_ID} </TD>
        <TD><FORM ACTION='?m=in&a=ws' METHOD='GET' NAME='addBook'><INPUT TYPE='hidden' NAME='m' value='in'><INPUT TYPE='hidden' NAME='a' value='ws'><INPUT TYPE='text' SIZE='5' NAME='addBook'><input type='submit' value='{ADD}' /></FORM></TD>
    </TR>
    <TR>
        <TD>{DEL_WEBSITE_BOOK_FROM_ID} </TD>
        <TD><FORM ACTION='?m=in&a=ws' METHOD='GET' NAME='delBook'><INPUT TYPE='hidden' NAME='m' value='in'><INPUT TYPE='hidden' NAME='a' value='ws'><INPUT TYPE='text' SIZE='5' NAME='delBook'><input type='submit' value='{DEL}' /></FORM></TD>
    </TR>
    <TR>
        <TD>{UPDATE_WEBSITE_BOOK_FROM_ID} </TD>
        <TD><FORM ACTION='?m=in&a=ws' METHOD='GET' NAME='updateBook'><INPUT TYPE='hidden' NAME='m' value='in'><INPUT TYPE='hidden' NAME='a' value='ws'><INPUT TYPE='text' SIZE='5' NAME='updateBook'><input type='submit' value='{UPDATE}' /></FORM></TD>
    </TR>

            
    <TR>
        <TD>{GET_WEBSITE_ORDERS} </TD>
        <TD><FORM ACTION='?m=in&a=ws' METHOD='GET' NAME='update'><INPUT TYPE='hidden' NAME='m' value='in'><INPUT TYPE='hidden' NAME='a' value='ws'><INPUT TYPE='hidden' NAME='getOrders' value='1'><input type='submit' value='{VIEW}' /></FORM></TD>
    </TR>
</TABLE>
</fieldset>
<div class="err">{MSG}</div>
