</table>
<div class="result-nav-wrap">{NAV}</div>
<a href="" onclick="setCheckboxes('selection', true); return false;">{SELECT_ALL}</a>
&nbsp;/&nbsp;
<a href="" onclick="setCheckboxes('selection', false); return false;">{UNSELECT_ALL}</a>
<br/>
{EXTRA_FORM}
<br/>
<select name="submit_mult" dir="ltr" onchange="this.form.submit();">    
            <option value="" selected="selected">{FOR_SELECTION} :</option>
            <option value="{ACTION1_VALUE}">{ACTION1}</option>
            <option value="{ACTION2_VALUE}">{ACTION2}</option>
            <option value="{ACTION3_VALUE}">{ACTION3}</option>
</select>
                    <script type="text/javascript" language="javascript">
                    <!--
                    // Fake js to allow the use of the <noscript> tag
                    //-->
                    </script>
                    <noscript>
                        <input type="submit" value="ExÈcuter" />
                    </noscript>
</form>
<script type="text/javascript" src="{TEMPLATE_FOLDER}/js/book-search-location-inline.js"></script>
<script type="text/javascript" src="{TEMPLATE_FOLDER}/js/book-search-row-open.js"></script>
<script type="text/javascript" src="{TEMPLATE_FOLDER}/js/book-search-delete-modal.js"></script>
<div class="book-confirm-modal" data-book-search-confirm-modal hidden>
  <div class="book-confirm-modal__backdrop" data-book-search-confirm-cancel="1"></div>
  <div class="book-confirm-modal__dialog" role="dialog" aria-modal="true" aria-label="Confirmation">
    <p class="book-confirm-modal__message" data-book-search-confirm-message>Confirmer la suppression ?</p>
    <div class="book-confirm-modal__actions">
      <button type="button" class="submit submit--secondary" data-book-search-confirm-cancel="1">Annuler</button>
      <button type="button" class="submit" data-book-search-confirm-ok="1">Supprimer</button>
    </div>
  </div>
</div>
