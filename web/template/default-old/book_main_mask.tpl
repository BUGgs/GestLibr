<center>{NAV}</center>
<form name=bookForm method=POST action='{BOOKS_FORM}'>

<table width='100%'>
<tr>
  <td valign='top'>
    <table>
		  <tr><td>{BOOKS_ID}: </td><td><input type='text' size='5' name='id' value="{ID_V}"></td></tr>
		  <tr><td>{BOOKS_AUTHOR}: </td><td><input type='text' size='35' name='author' value="{AUTHOR_V}"></td></tr>
		  <tr><td>{BOOKS_TITLE}: </td><td><textarea cols='37' rows='2' name='title'>{TITLE_V}</textarea></td></tr>
		  <tr><td>{BOOKS_DESCRIPTION}: </td><td><textarea cols='37' rows='6' name='description'>{DESCRIPTION_V}</textarea></td></tr>
		  <tr><td>{BOOKS_PRICE}: </td><td><input type='text' size='1' name='price' value="{PRICE_V}"> Eur &nbsp;&nbsp;
              {BOOKS_PRICE_EBAY}: <input type='text' size='1' name='price_ebay' value="{PRICE_EBAY_V}"> Eur &nbsp;&nbsp;
              {BOOKS_PRICE_AMAZON}: <input type='text' size='1' name='price_amazon' value="{PRICE_AMAZON_V}"> Eur &nbsp;&nbsp;              
              </td></tr>
		  <tr><td>{BOOKS_QUANTITY}: </td><td><a href='{BOOKS_FORM}&qtyDel=1'><img src='images/action_remove.gif' border=0></a><input type='text' size='1' name='quantity' value="{QUANTITY_V}" READONLY> <a href='{BOOKS_FORM}&qtyAdd=1'><img src='images/action_add.gif' border=0></a>{QUANTITY_NOT_NULL}&nbsp;
      {BOOKS_LOCATION}: <input type='text' size='1' name='location' value="{LOCATION_V}" onChange="javascript:this.value=this.value.toUpperCase();">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <input type=submit value='{BOOKS_SUBMIT}'>
      </td></tr>
      		<tr><td>&nbsp;</td><td>
      <input type='checkbox' name='internet' value='1' {INTERNET_V} id='checkbox_on_internet'/><label for='checkbox_on_internet'> {BOOKS_ON_INTERNET}</label>{INTERNET_SEARCH} | <input type='checkbox' name='onEbay' value='1' {ONEBAY_V} id='checkbox_on_ebay'/><label for='checkbox_on_ebay'> {BOOKS_ON_EBAY}</label>
      </td></tr>
    </table>
  </td>
  <td valign='top'>
    <table>
		  <tr><td>{BOOKS_ISBN}: </td><td><input type='text' size='15' name='isbn' value="{ISBN_V}"></td></tr>
		  <tr><td>{BOOKS_COLLECTION}: </td><td><input type='text' size='15' name='collection' value="{COLLECTION_V}"></td></tr>
		  <tr><td>{BOOKS_EDITOR}: </td><td><input type='text' size='15' name='editor' value="{EDITOR_V}"></td></tr>
		  <tr><td>{BOOKS_PUBLISHDATE}: </td><td><input type='text' size='3' name='publishDate' value="{PUBLISHDATE_V}">&nbsp;&nbsp;{BOOKS_PUBLISHLOCATION}: <input type='text' size='10' name='publishLocation' value="{PUBLISHLOCATION_V}"></td></tr>
		  <tr><td>{BOOKS_FORMAT}: </td><td>{FORMAT_V}&nbsp;&nbsp;{BOOKS_PAGENUMBER}: <input type='text' size='3' name='pageNumber' value="{PAGENUMBER_V}"></td></tr>
		  <tr><td>{BOOKS_BINDING}: </td><td>{BINDING_V}&nbsp;&nbsp;{BOOKS_LANGUAGE}: {LANGUAGE_V}</td></tr>
		  <tr><td>{BOOKS_CATEGORY}: </td><td>{CATEGORY_V}</td></tr>
		  <tr><td>{BOOKS_EBAYCATEGORY}: </td><td>{EBAYCATEGORY_V}</td></tr>		  
		  <tr><td>{BOOKS_EBAYCATEGORYSECONDARY}: </td><td>{EBAYCATEGORYSECONDARY_V}</td></tr>		  
		  <tr><td>{BOOKS_EBAYSHIPPINGCOST}: </td><td><input type='text' size='1' name='ebayShippingCost' value="{EBAYSHIPPINGCOST_V}"> Eur&nbsp;&nbsp;{BOOKS_EBAYSHIPPINGCOSTINTERNATIONAL}: <input type='text' size='1' name='ebayShippingCostInternational' value="{EBAYSHIPPINGCOSTINTERNATIONAL_V}"> Eur</td></tr>		  
		  <tr><td>{BOOKS_EBAYITEMID} </td><td><a href='http://cgi.ebay.fr/ws/eBayISAPI.dll?ViewItem&item={EBAYITEMID_V}' target='_blank'>{EBAYITEMID_V}</a> {EBAYADD_V} {EBAYDEL_V} {EBAYUPDATE_V}</td></tr>		  
		  <tr><td>{BOOKS_EBAYLASTSYNC} </td><td>{EBAYLASTSYNC_V}</td></tr>		  
		  <tr><td>{BOOKS_MODIFYDATE} </td><td>{MODIFYDATE_V}</td></tr>
		  <tr><td>{BOOKS_IN_CATALOG} </td><td>{IN_CATALOG_V}</td></tr>
		  <tr><td>{BOOKS_NOTES}: </td><td><textarea cols='25' rows='2' name='notes'>{NOTES_V}</textarea></td></tr>
    </table>
  </td>
</tr>
</table>

<input type=hidden name=sub value='ok'>
<input type=hidden name=opt1 value='OPT1_V'>
<input type=hidden name=opt2 value='OPT2_V'>
</form>
 <script language="JavaScript">
<!--
document.bookForm.author.focus()
// -->
</script>
