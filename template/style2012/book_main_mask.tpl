<center>{NAV}</center>

<form name=bookForm method=POST action='{BOOKS_FORM}' enctype="multipart/form-data">
  <fieldset class="inputs">
    <table>
      <tr>
	<td>
	  <table>
	    <tr><td>{BOOKS_ID}: </td><td><input type='text' size='6' name='id' value="{ID_V}"></td></tr>
	    <tr><td>{BOOKS_AUTHOR}: </td><td><input type='text' size='40' name='author' value="{AUTHOR_V}" autofocus></td></tr>
	    <tr><td>{BOOKS_TITLE}: </td><td><textarea cols='30' rows='2' name='title'>{TITLE_V}</textarea></td></tr>
	    <tr><td>{BOOKS_DESCRIPTION}: </td><td><textarea cols='30' rows='6' name='description'>{DESCRIPTION_V}</textarea></td></tr>
	    <tr><td>{BOOKS_PRICE}: </td><td><input type='text' size='1' name='price' value="{PRICE_V}"> &nbsp;
              {BOOKS_PRICE_EBAY}: <input type='text' size='1' name='price_ebay' value="{PRICE_EBAY_V}"> &nbsp;
              {BOOKS_PRICE_AMAZON}: <input type='text' size='1' name='price_amazon' value="{PRICE_AMAZON_V}">              
              </td></tr>
	    <tr><td>{BOOKS_QUANTITY}: </td><td><a href='{BOOKS_FORM}&qtyDel=1'><img src='images/action_remove.gif' border=0></a><input type='text' size='1' name='quantity' value="{QUANTITY_V}" READONLY> <a href='{BOOKS_FORM}&qtyAdd=1'><img src='images/action_add.gif' border=0></a>{QUANTITY_NOT_NULL}&nbsp;
	      {BOOKS_LOCATION}: <input type='text' size='3' name='location' value="{LOCATION_V}" onChange="javascript:this.value=this.value.toUpperCase();">
	      </td></tr>
	    <tr><td>&nbsp;</td><td><input type='checkbox' name='internet' value='1' {INTERNET_V} id='checkbox_on_internet'/>
	      <label for='checkbox_on_internet'> {BOOKS_ON_INTERNET}</label>{INTERNET_SEARCH} | <input type='checkbox' name='onEbay' value='1' {ONEBAY_V} id='checkbox_on_ebay'/>
	      <label for='checkbox_on_ebay'> {BOOKS_ON_EBAY}</label>
	      </td></tr>
	    <tr><td>{BOOKS_CONDITION}: </td><td>{CONDITION_V}</td></tr>
	  </table>
	</td>
	<td>
	  <table>
	    <tr><td>{BOOKS_ISBN}: </td><td><input type='text' size='18' name='isbn' value="{ISBN_V}"></td></tr>
	    <tr><td>{BOOKS_COLLECTION}: </td><td><input type='text' size='18' name='collection' value="{COLLECTION_V}"></td></tr>
	    <tr><td>{BOOKS_EDITOR}: </td><td><input type='text' size='18' name='editor' value="{EDITOR_V}"></td></tr>
	    <tr><td>{BOOKS_PUBLISHDATE}: </td><td><input type='text' size='3' name='publishDate' value="{PUBLISHDATE_V}"> {BOOKS_PUBLISHLOCATION}: <input type='text' size='10' name='publishLocation' value="{PUBLISHLOCATION_V}"></td></tr>
	    <tr><td>{BOOKS_FORMAT}: </td><td>{FORMAT_V} {BOOKS_PAGENUMBER}: <input type='text' size='3' name='pageNumber' value="{PAGENUMBER_V}"></td></tr>
	    <tr><td>{BOOKS_BINDING}: </td><td>{BINDING_V} {BOOKS_LANGUAGE}: {LANGUAGE_V}</td></tr>
	    <tr><td>{BOOKS_MODIFYDATE} </td><td>{MODIFYDATE_V}</td></tr>
	    <tr><td>{BOOKS_EBAYCATEGORY}: </td><td>{EBAYCATEGORY_V}</td></tr>
	    <tr><td>{BOOKS_EBAYCATEGORYSECONDARY}: </td><td>{EBAYCATEGORYSECONDARY_V}</td></tr>
	    <tr><td>{BOOKS_EBAYSHIPPINGCOST}: </td><td><input type='text' size='2' name='ebayShippingCost' value="{EBAYSHIPPINGCOST_V}"> Eur&nbsp;&nbsp;{BOOKS_EBAYSHIPPINGCOSTINTERNATIONAL}: <input type='text' size='2' name='ebayShippingCostInternational' value="{EBAYSHIPPINGCOSTINTERNATIONAL_V}"> Eur</td></tr>
	    <tr><td>{BOOKS_EBAYITEMID}: </td><td><a href='http://cgi.ebay.fr/ws/eBayISAPI.dll?ViewItem&item={EBAYITEMID_V}' target='_blank'>{EBAYITEMID_V}</a> {EBAYADD_V} {EBAYDEL_V} {EBAYUPDATE_V}</td></tr>
	    <tr><td>{BOOKS_EBAYLASTSYNC} </td><td>{EBAYLASTSYNC_V}</td></tr>
	  </table>
	</td>
	<td>
	  <table>
	    <tr><td>{BOOKS_CATEGORY}: </td><td>{CATEGORY_V}</td></tr>
	    <tr><td>{BOOKS_PHOTOS}: </td><td>
	      <input  type="hidden" name="MAX_FILE_SIZE" value="10000000" />
	      <input type='file' name='photo' />
	    <table>
	      <tr>
	    <!-- BEGIN bookPhoto -->
	    <td><a href='{bookPhoto.FILE}' target='_blank'><img src='{bookPhoto.FILEMINIATURE}' alt='{bookPhoto.DESCRIPTION}'></a>
	    <br /><a href='{bookPhoto.DELETEURL}' onclick="return confirmLink(this, '{CONFIRM_DELETE}')"><img src='{TEMPLATE_FOLDER}/images/delete.png' alt='delete'></a></td>
	    <!-- END bookPhoto -->
	      </tr>
	    </table>
	    </td></tr>
	    <tr><td>{BOOKS_NOTES}: </td><td><textarea cols='25' rows='2' name='notes'>{NOTES_V}</textarea></td></tr>
	    <tr><td>{BOOKS_IN_CATALOG} </td><td>{IN_CATALOG_V}</td></tr>
	  </table>
	</td>
      </tr>
    </table>
  </fieldset>
  <fieldset class="actions">
    <input type=submit class="submit" value='{BOOKS_SUBMIT}' />
    <input type=hidden name=sub value='ok' />
    <input type=hidden name=imageFromUrl value='{IMAGEFROMURL_V}' />
    <input type=hidden name=opt1 value='{OPT1_V}' />
    <input type=hidden name=opt2 value='{OPT2_V}' />
  </fieldset>
</form>
