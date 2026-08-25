<div class="book-save-status" data-book-save-status>{BOOK_AUTOSAVE_STATUS}</div>

<form
  name='bookForm'
  method='POST'
  action='{BOOKS_FORM}'
  enctype='multipart/form-data'
  class='book-form'
  data-book-form-mode='{BOOK_FORM_MODE}'
  data-book-autosave-enabled='{BOOK_AUTOSAVE_ENABLED}'
  data-book-api-url='{BOOK_API_URL}'
  data-book-isbn-lookup-url='{BOOK_ISBN_LOOKUP_URL}'
  data-book-search-autocomplete-enabled='{BOOK_SEARCH_AUTOCOMPLETE_ENABLED}'
  data-book-search-suggest-url='{BOOK_SEARCH_SUGGEST_URL}'
  data-book-search-suggest-min-chars='{BOOK_SEARCH_SUGGEST_MIN_CHARS}'>
  <fieldset class='inputs'>
    <div class='book-form-layout'>
      <section class='book-main'>
        <div class='book-main-grid'>
          <div class='book-field book-field--full'>
            <div class='book-top-inline'>
              <div class='book-mini-field'>
                <span>{BOOKS_ID}</span>
                <input id='book-id' class='book-id-input' type='text' size='6' name='id' value='{ID_V}' placeholder='{BOOK_ID_PLACEHOLDER}' {BOOK_ID_READONLY}>
              </div>
              <div class='book-mini-field'>
                <span>{BOOKS_ISBN}</span>
                <input id='book-isbn' type='text' size='18' name='isbn' value='{ISBN_V}' data-autosave-field='isbn' {BOOK_ISBN_AUTOFOCUS}>
              </div>
              <div class='book-mini-field'>
                <span>{BOOKS_COLLECTION}</span>
                <input id='book-asin' type='text' size='18' name='collection' value='{COLLECTION_V}' data-autosave-field='collection'>
              </div>
            </div>
            <div class='book-isbn-status-wrap'>
              <div class='book-isbn-status' data-book-isbn-status aria-live='polite'></div>
              <div class='book-isbn-duplicate' data-book-isbn-duplicate hidden>
                <p class='book-isbn-duplicate__text' data-book-isbn-duplicate-text></p>
                <a class='submit submit--secondary' data-book-isbn-duplicate-link href='#' target='_self'>Ouvrir la fiche existante</a>
              </div>
            </div>
          </div>

          <div class='book-field book-field--full'>
            <div class='book-field-label-row'>
              <label for='book-author'>{BOOKS_AUTHOR}:</label>
              <span class='book-char-count' data-char-count-for='book-author'></span>
            </div>
            <div class='book-suggest-box'>
              <input id='book-author' type='text' size='29' name='author' value='{AUTHOR_V}' data-autosave-field='author' autocomplete='off' {BOOK_AUTHOR_AUTOFOCUS}>
              <div class='book-suggest-list' data-book-suggest-list='author' hidden></div>
            </div>
          </div>

          <div class='book-field book-field--full'>
            <div class='book-field-label-row'>
              <label for='book-title'>{BOOKS_TITLE}:</label>
              <span class='book-char-count' data-char-count-for='book-title'></span>
            </div>
            <div class='book-suggest-box'>
              <textarea id='book-title' class='book-title-input' cols='30' rows='2' name='title' data-autosave-field='title'>{TITLE_V}</textarea>
              <div class='book-suggest-list' data-book-suggest-list='title' hidden></div>
            </div>
          </div>

          <div class='book-field book-field--full'>
            <div class='book-field-label-row'>
              <label for='book-subtitle'>{BOOKS_SUBTITLE}:</label>
              <span class='book-char-count' data-char-count-for='book-subtitle'></span>
            </div>
            <input id='book-subtitle' type='text' size='30' name='subtitle' value='{SUBTITLE_V}' data-autosave-field='subtitle'>
          </div>

          <div class='book-field book-field--full book-field--description'>
            <div class='book-field-label-row'>
              <label for='book-description'>{BOOKS_DESCRIPTION}:</label>
              <span class='book-char-count' data-char-count-for='book-description'></span>
            </div>
            <div class='book-description-editor' data-book-description-editor='1'>
              <div class='book-description-editor__toolbar' data-book-description-toolbar></div>
              <div class='book-description-editor__surface' data-book-description-surface></div>
              <textarea id='book-description' class='book-description-source' cols='30' rows='6' name='description' data-autosave-field='description'>{DESCRIPTION_V}</textarea>
            </div>
          </div>

          <div class='book-field book-field--full'>
            <label>{BOOKS_PRICE}:</label>
            <div class='book-price-grid'>
              <div class='book-mini-field'>
                <span>{BOOKS_PRICE}</span>
                <input type='text' size='1' name='price' value='{PRICE_V}' data-autosave-field='price'>
              </div>
              <div class='book-mini-field'>
                <span>{BOOKS_PRICE_EBAY}</span>
                <input type='text' size='1' name='price_ebay' value='{PRICE_EBAY_V}' data-autosave-field='price_ebay'>
              </div>
              <div class='book-mini-field'>
                <span>{BOOKS_PRICE_AMAZON}</span>
                <input type='text' size='1' name='price_amazon' value='{PRICE_AMAZON_V}' data-autosave-field='price_amazon'>
              </div>
            </div>
          </div>

          <div class='book-field book-field--full'>
            <label>{BOOKS_QUANTITY}:</label>
            <div class='book-qty-row'>
              <a class='book-qty-btn' href='{BOOKS_FORM}&qtyDel=1' title='-' data-book-action='qty_delta' data-delta='-1'><img src='{TEMPLATE_FOLDER}/images/icon-minus.svg' alt='-'></a>
              <input class='book-qty-input' type='text' size='1' name='quantity' value='{QUANTITY_V}' readonly>
              <a class='book-qty-btn' href='{BOOKS_FORM}&qtyAdd=1' title='+' data-book-action='qty_delta' data-delta='1'><img src='{TEMPLATE_FOLDER}/images/icon-plus.svg' alt='+'></a>
              <span class='book-qty-extra'>{QUANTITY_NOT_NULL}</span>
              <label class='book-inline-label' for='book-location'>{BOOKS_LOCATION}:</label>
              <input id='book-location' class='book-location-input' type='text' size='3' name='location' value='{LOCATION_V}' data-autosave-field='location' onChange='javascript:this.value=this.value.toUpperCase();'>
              <span class='book-inline-label book-inline-label--compact'>Diffusion:</span>
              <div class='book-toggle-row book-toggle-row--inline'>
              <label class='book-inline-check' for='checkbox_on_internet'>
                <input type='checkbox' name='internet' value='1' {INTERNET_V} id='checkbox_on_internet' data-autosave-field='internet'/>
                <span>{BOOKS_ON_INTERNET}</span>
              </label>
              <span>{INTERNET_SEARCH}</span>
              <label class='book-inline-check' for='checkbox_on_ebay'>
                <input type='checkbox' name='onEbay' value='1' {ONEBAY_V} id='checkbox_on_ebay' data-autosave-field='onEbay'/>
                <span>{BOOKS_ON_EBAY}</span>
              </label>
              </div>
            </div>
          </div>

          <div class='book-field'>
            <label>{BOOKS_CONDITION}:</label>
            {CONDITION_V}
          </div>

          <div class='book-field'>
            <label>{BOOKS_CATEGORY}:</label>
            <div id='book-category-block' class='book-rich-block'>{CATEGORY_V}</div>
          </div>

          <div class='book-field book-field--full'>
            <div class='book-editor-publication-row'>
              <div class='book-mini-field'>
                <span>{BOOKS_EDITOR}</span>
                <input id='book-editor' type='text' size='18' name='editor' value='{EDITOR_V}' data-autosave-field='editor'>
              </div>
              <div class='book-mini-field'>
                <span>{BOOKS_PUBLISHDATE}</span>
                <input id='book-publish-date' type='text' size='3' name='publishDate' value='{PUBLISHDATE_V}' data-autosave-field='publishDate'>
              </div>
            </div>
          </div>

          <div class='book-field book-field--full'>
            <div class='book-inline-pack'>
              <div class='book-mini-field'>
                <span>{BOOKS_FORMAT}</span>
                {FORMAT_V}
              </div>
              <div class='book-mini-field'>
                <span>{BOOKS_PAGENUMBER}</span>
                <input id='book-page-number' type='text' size='3' name='pageNumber' value='{PAGENUMBER_V}' data-autosave-field='pageNumber'>
              </div>
              <div class='book-mini-field'>
                <span>{BOOKS_WEIGHT_G}</span>
                <input id='book-weight-g' type='text' size='5' name='weight_g' value='{WEIGHT_G_V}' data-autosave-field='weight_g'>
              </div>
              <div class='book-mini-field'>
                <span>{BOOKS_BINDING}</span>
                {BINDING_V}
              </div>
              <div class='book-mini-field'>
                <span>{BOOKS_LANGUAGE}</span>
                {LANGUAGE_V}
              </div>
              <div class='book-mini-field'>
                <span>{BOOKS_PUBLISHLOCATION}</span>
                <input id='book-publish-location' type='text' size='10' name='publishLocation' value='{PUBLISHLOCATION_V}' data-autosave-field='publishLocation'>
              </div>
            </div>
          </div>

          <div class='book-field book-field--full'>
            <label for='book-notes'>{BOOKS_NOTES}:</label>
            <textarea id='book-notes' cols='30' rows='2' name='notes' data-autosave-field='notes'>{NOTES_V}</textarea>
          </div>

          <div class='book-field book-field--full'>
            <label>Modification de la fiche</label>
            <div data-book-modify-date>{MODIFYDATE_V}</div>
          </div>

          <div class='book-field book-field--full'>
            <label>{BOOKS_IN_CATALOG}</label>
            <div id='book-catalog-block' class='book-rich-block'>{IN_CATALOG_V}</div>
          </div>
        </div>
      </section>

      <aside class='book-photos-pane'>
        <div class='book-photos-panel'>
          <div
            class='photo-manager'
            data-photo-enabled='{PHOTO_MANAGER_ENABLED}'
            data-photo-mode='{PHOTO_MANAGER_MODE}'
            data-photo-api-url='{PHOTO_API_URL}'
            data-photo-max-files='{PHOTO_MAX_FILES}'
            data-photo-max-size-mb='{PHOTO_MAX_SIZE_MB}'
            data-template-folder='{TEMPLATE_FOLDER}'
            data-icon-rotate-left='{TEMPLATE_FOLDER}/images/icon-photo-rotate-left.svg'
            data-icon-rotate-right='{TEMPLATE_FOLDER}/images/icon-photo-rotate-right.svg'
            data-icon-trash='{TEMPLATE_FOLDER}/images/icon-photo-trash.svg'
            data-icon-zoom='{TEMPLATE_FOLDER}/images/icon-photo-zoom.svg'
            data-icon-drag='{TEMPLATE_FOLDER}/images/icon-photo-drag.svg'>

            <div class='photo-uploader'>
              <div class='photo-uploader__dropzone'>
                <p class='photo-uploader__title'>Galerie photo</p>
                <p class='photo-uploader__hint'>{PHOTO_HELP_TEXT}</p>
                <div class='photo-uploader__actions'>
                  <button
                    type='button'
                    class='photo-uploader__browse'>Ajouter des photos</button>
                  <span class='photo-uploader__constraints'>{PHOTO_MAX_FILES} fichiers max - {PHOTO_MAX_SIZE_MB} Mo/fichier</span>
                </div>
                <input type='hidden' name='MAX_FILE_SIZE' value='10485760'>
                <input
                  type='file'
                  class='photo-uploader__input'
                  name='photos[]'
                  accept='image/jpeg,image/png,image/gif'
                  multiple
                  onchange="if('{PHOTO_MANAGER_ENABLED}'==='1' && '{PHOTO_MANAGER_MODE}'!=='create' && !window.BookPhotoManagerActive){if(this.form&&this.form.photoUploadOnly){this.form.photoUploadOnly.value='1';}this.form.submit();}">
              </div>
              <div class='photo-status' aria-live='polite'></div>
              <div class='photo-errors' aria-live='polite'></div>
              <div class='photo-gallery' aria-live='polite'>
                <!-- BEGIN bookPhoto -->
                <article class='photo-card' data-photo-id='{bookPhoto.ID}'>
                  <div class='photo-card__header'>
                    <span class='photo-card__primary'>{bookPhoto.ORDER_LABEL}</span>
                    <span class='photo-btn photo-btn--drag' aria-hidden='true'>
                      <img class='photo-btn__icon' src='{TEMPLATE_FOLDER}/images/icon-photo-drag.svg' alt=''>
                    </span>
                  </div>
                  <button
                    type='button'
                    class='photo-card__preview'
                    data-photo-open='{bookPhoto.ID}'
                    data-photo-original='{bookPhoto.FILE}'
                    data-photo-medium='{bookPhoto.FILE}'
                    data-photo-description='{bookPhoto.DESCRIPTION}'>
                    <img src='{bookPhoto.FILEMINIATURE}' alt='{bookPhoto.DESCRIPTION}'>
                  </button>
                  <div class='photo-card__toolbar'>
                    <button type='button' class='photo-btn' data-photo-action='rotate_left' data-photo-id='{bookPhoto.ID}' title='Rotation gauche'><img class='photo-btn__icon' src='{TEMPLATE_FOLDER}/images/icon-photo-rotate-left.svg' alt='Rotation gauche'></button>
                    <button type='button' class='photo-btn' data-photo-open='{bookPhoto.ID}' data-photo-original='{bookPhoto.FILE}' data-photo-medium='{bookPhoto.FILE}' data-photo-description='{bookPhoto.DESCRIPTION}' title='Voir en grand'><img class='photo-btn__icon' src='{TEMPLATE_FOLDER}/images/icon-photo-zoom.svg' alt='Voir en grand'></button>
                    <button type='button' class='photo-btn photo-btn--danger' data-photo-action='delete' data-photo-id='{bookPhoto.ID}' title='Suppression'><img class='photo-btn__icon' src='{TEMPLATE_FOLDER}/images/icon-photo-trash.svg' alt='Suppression'></button>
                    <button type='button' class='photo-btn' data-photo-action='rotate_right' data-photo-id='{bookPhoto.ID}' title='Rotation droite'><img class='photo-btn__icon' src='{TEMPLATE_FOLDER}/images/icon-photo-rotate-right.svg' alt='Rotation droite'></button>
                  </div>
                </article>
                <!-- END bookPhoto -->
              </div>
            </div>

            <div class='photo-modal' hidden>
              <div class='photo-modal__backdrop' data-photo-close='1'></div>
              <div class='photo-modal__dialog' role='dialog' aria-modal='true' aria-label='Visualisation photo'>
                <button type='button' class='photo-modal__close' data-photo-close='1' aria-label='Fermer'>x</button>
                <img class='photo-modal__image' src='' alt=''>
                <div class='photo-modal__caption'></div>
              </div>
            </div>
          </div>
        </div>

        <details class='book-advanced book-advanced--sidebar' open>
          <summary>Détails avancés (eBay)</summary>
          <div class='book-advanced-grid'>
            <div class='book-field'>
              <label>Mode policies eBay:</label>
              {EBAY_POLICY_MODE_V}
            </div>
            <div class='book-field'>
              <label>Payment policy override:</label>
              {EBAY_POLICY_PAYMENT_OVERRIDE_V}
            </div>
            <div class='book-field'>
              <label>Fulfillment policy override:</label>
              {EBAY_POLICY_FULFILLMENT_OVERRIDE_V}
            </div>
            <div class='book-field'>
              <label>Return policy override:</label>
              {EBAY_POLICY_RETURN_OVERRIDE_V}
            </div>
            <div class='book-field book-field--full'>
              <label class='book-inline-check'>
                <input type='checkbox' name='ebayUseDescriptionTemplate' value='1' {EBAY_USE_DESCRIPTION_TEMPLATE_V} data-autosave-field='ebayUseDescriptionTemplate'>
                <span>Appliquer template description eBay (header/footer groupe)</span>
              </label>
            </div>
            <div class='book-field book-field--full'>
              <label>{BOOKS_EBAYITEMID}:</label>
              <div class='book-ebay-row'>
                <a class='book-ebay-item-link{EBAYITEMID_LINK_CLASS}' data-book-ebay-item-link href='{EBAYITEMID_LINK_HREF}' target='{EBAYITEMID_LINK_TARGET}'>{EBAYITEMID_V}</a>
                <span class='book-ebay-actions' data-book-ebay-actions>{EBAYADD_V}{EBAYDEL_V}{EBAYUPDATE_V}</span>
                <span class='book-ebay-inline-status' data-book-ebay-inline-status></span>
              </div>
            </div>
            <div class='book-field'>
              <label>{BOOKS_EBAYLASTSYNC}</label>
              <div class='book-ebay-sync-row'>
                <div data-book-ebay-last-sync data-book-ebay-last-sync-ts='{EBAYLASTSYNC_TS_V}'>{EBAYLASTSYNC_V}</div>
                <span class='book-ebay-sync-needed' data-book-ebay-sync-needed {EBAY_SYNC_NEEDED_HIDDEN}>Mise à jour eBay nécessaire</span>
              </div>
            </div>
            <!-- BEGIN showEbaySales -->
            <div class='book-field book-field--full'>
              <label>Historique ventes eBay</label>
              <div class='book-ebay-sales'>
                <table class='bordered'>
                  <tr>
                    <th>Order ID</th>
                    <th>Date</th>
                    <th>Prix</th>
                    <th>Acheteur</th>
                    <th>Paiement</th>
                    <th>Fulfillment</th>
                    <th>Validée</th>
                    <th>eBay Item ID</th>
                  </tr>
                  <!-- BEGIN bookEbaySale -->
                  <tr>
                    <td>{showEbaySales.bookEbaySale.ORDER_ID_HTML}</td>
                    <td>{showEbaySales.bookEbaySale.ORDER_DATE}</td>
                    <td>{showEbaySales.bookEbaySale.PRICE}</td>
                    <td>{showEbaySales.bookEbaySale.BUYER}</td>
                    <td>{showEbaySales.bookEbaySale.PAYMENT_STATUS}</td>
                    <td>{showEbaySales.bookEbaySale.FULFILLMENT_STATUS}</td>
                    <td>{showEbaySales.bookEbaySale.VALIDATED}</td>
                    <td>{showEbaySales.bookEbaySale.EBAY_ITEM_ID}</td>
                  </tr>
                  <!-- END bookEbaySale -->
                </table>
                <div class='book-ebay-sales-empty'>{EBAY_SALES_EMPTY}</div>
              </div>
            </div>
            <!-- END showEbaySales -->
          </div>
        </details>
      </aside>
    </div>
  </fieldset>
  <fieldset class='actions'>
    <input type='submit' class='submit submit--manual' data-book-manual-submit='1' value='{BOOKS_SUBMIT}' />
    <input type='hidden' name='sub' value='ok' />
    <input type='hidden' name='photoUploadOnly' value='0' />
    <input type='hidden' name='imageFromUrl' value='{IMAGEFROMURL_V}' />
    <input type='hidden' name='opt1' value='{OPT1_V}' />
    <input type='hidden' name='opt2' value='{OPT2_V}' />
    <input type='hidden' name='clientModifyDate' value='{CLIENT_MODIFY_DATE_V}' />
  </fieldset>
</form>

<div class='book-confirm-modal' data-book-confirm-modal hidden>
  <div class='book-confirm-modal__backdrop' data-book-confirm-cancel='1'></div>
  <div class='book-confirm-modal__dialog' role='dialog' aria-modal='true' aria-label='Confirmation'>
    <p class='book-confirm-modal__message' data-book-confirm-message>Confirmer la suppression ?</p>
    <div class='book-confirm-modal__actions'>
      <button type='button' class='submit submit--secondary' data-book-confirm-cancel='1'>Annuler</button>
      <button type='button' class='submit' data-book-confirm-ok='1'>Supprimer</button>
    </div>
  </div>
</div>

<script type='text/javascript' src='{TEMPLATE_FOLDER}/js/sortable.min.js'></script>
<script type='text/javascript' src='{TEMPLATE_FOLDER}/js/photo-manager.js'></script>
<script type='text/javascript' src='{TEMPLATE_FOLDER}/js/book-description-editor.js'></script>
<script type='text/javascript' src='{TEMPLATE_FOLDER}/js/book-autosave.js'></script>
<script type='text/javascript' src='{TEMPLATE_FOLDER}/js/book-search-suggest.js'></script>
<script type='text/javascript' src='{TEMPLATE_FOLDER}/js/book-create-isbn.js'></script>
