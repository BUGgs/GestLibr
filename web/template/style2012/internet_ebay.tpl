<h1>{EBAY}</h1>
<hr>
<div class="ebay-group-target-wrap">{EBAY_TARGET_GROUP_HTML}</div>

<fieldset class="inputs">
    <h2>Actions rapides</h2>
    <div class="quick-grid">
        {EBAY_QUICK_ACTIONS_HTML}
    </div>

    <details class="ebay-compact-block">
        <summary>Actions manuelles par idBook</summary>
        <div>{EBAY_MANUAL_ACTIONS_HTML}</div>
    </details>
</fieldset>

<fieldset class="inputs">
    <h2>{OAUTH_TITLE}</h2>
    <table class="bordered">
        <tr>
            <td>&nbsp;</td>
            <td>
                <form action='?m=in&a=eb' method='get' name='oauth_start' style='display:inline;'>
                    <input type='hidden' name='m' value='in'>
                    <input type='hidden' name='a' value='eb'>
                    <input type='hidden' name='target_group_id' value='{TARGET_GROUP_ID}'>
                    <input type='hidden' name='oauth_start' value='1'>
                    <input class='submit submit--secondary' type='submit' value='{OAUTH_START}' />
                </form>
                <form action='?m=in&a=eb' method='get' name='oauth_status' style='display:inline;margin-left:10px;'>
                    <input type='hidden' name='m' value='in'>
                    <input type='hidden' name='a' value='eb'>
                    <input type='hidden' name='target_group_id' value='{TARGET_GROUP_ID}'>
                    <input type='hidden' name='oauth_status' value='1'>
                    <input class='submit submit--secondary' type='submit' value='{OAUTH_STATUS_BTN}' />
                </form>
            </td>
        </tr>
        <tr>
            <td>{OAUTH_MANUAL_TOKEN_LABEL}</td>
            <td>
                <form action='?m=in&a=eb' method='post' name='oauth_manual_token' style='display:inline;'>
                    <input type='hidden' name='m' value='in'>
                    <input type='hidden' name='a' value='eb'>
                    <input type='hidden' name='target_group_id' value='{TARGET_GROUP_ID}'>
                    <input type='text' name='oauth_refresh_token' size='80' placeholder='{OAUTH_MANUAL_TOKEN_PLACEHOLDER}' />
                    <input class='submit submit--secondary' type='submit' name='oauth_save_refresh_token' value='{OAUTH_MANUAL_TOKEN_SAVE}' />
                    <input class='submit submit--secondary' type='submit' name='oauth_clear_refresh_token' value='{OAUTH_MANUAL_TOKEN_CLEAR}' />
                </form>
            </td>
        </tr>
        <tr><td>{OAUTH_PROVIDER_LABEL}</td><td>{OAUTH_PROVIDER_VALUE}</td></tr>
        <tr><td>{OAUTH_ENV_LABEL}</td><td>{OAUTH_ENV_VALUE}</td></tr>
        <tr><td>{OAUTH_TOKEN_LABEL}</td><td>{OAUTH_TOKEN_VALUE}</td></tr>
        <tr><td>{OAUTH_SELLER_READY_LABEL}</td><td>{OAUTH_SELLER_READY_VALUE}</td></tr>
        <tr><td>{OAUTH_OFFER_CONFIG_LABEL}</td><td>{OAUTH_OFFER_CONFIG_VALUE}</td></tr>
        <tr><td>{OAUTH_TOKEN_SOURCE_LABEL}</td><td>{OAUTH_TOKEN_SOURCE_VALUE}</td></tr>
        <tr><td>{OAUTH_MESSAGE_LABEL}</td><td>{OAUTH_MESSAGE_VALUE}</td></tr>
    </table>
</fieldset>

<fieldset class="inputs">
    <h2>{SETUP_TITLE}</h2>
    <p>{SETUP_HELP}</p>

    <form action='?m=in&a=eb' method='post' name='setup_save'>
        <input type='hidden' name='m' value='in'>
        <input type='hidden' name='a' value='eb'>
        <input type='hidden' name='target_group_id' value='{TARGET_GROUP_ID}'>
        <table class="bordered">
            <tr>
                <td colspan="3">
                    <div class="ebay-setup-actions">
                        <a href='?m=in&a=eb&target_group_id={TARGET_GROUP_ID}&ebay_setup_fetch=1' class='submit submit--secondary'>{SETUP_FETCH}</a>
                        <a href='?m=in&a=eb&target_group_id={TARGET_GROUP_ID}&ebay_setup_autofill=1' class='submit submit--secondary'>{SETUP_AUTOFILL}</a>
                        <a href='?m=in&a=eb&target_group_id={TARGET_GROUP_ID}&ebay_setup_sync=1' class='submit submit--secondary'>{SETUP_SYNC_POLICIES}</a>
                        <a href='?m=in&a=eb&target_group_id={TARGET_GROUP_ID}&ebay_setup_optin_business_policies=1' class='submit submit--secondary'>{SETUP_OPTIN_BUSINESS_POLICIES}</a>
                    </div>
                </td>
            </tr>
            <tr><td>{SETUP_BUSINESS_POLICY_STATUS_LABEL}</td><td>{SETUP_BUSINESS_POLICY_STATUS_VALUE}</td><td>&nbsp;</td></tr>
            <tr><td>{SETUP_PROGRAMS_LABEL}</td><td>{SETUP_PROGRAMS_VALUE}</td><td>&nbsp;</td></tr>
            <tr>
                <td>{SETUP_MARKETPLACE_LABEL}</td>
                <td><input type='text' name='setup_marketplace_id' size='20' value='{SETUP_MARKETPLACE_VALUE}' /></td>
                <td>{SETUP_MARKETPLACE_SOURCE}</td>
            </tr>
            <tr>
                <td>{SETUP_MERCHANT_LOCATION_LABEL}</td>
                <td>{SETUP_LOCATION_SELECT}</td>
                <td>{SETUP_MERCHANT_SOURCE}</td>
            </tr>
            <tr>
                <td>{SETUP_FIXED_CATEGORY_LABEL}</td>
                <td><input type='text' name='setup_fixed_category_id' size='20' value='{SETUP_FIXED_CATEGORY_VALUE}' /></td>
                <td>{SETUP_FIXED_CATEGORY_SOURCE}</td>
            </tr>
            <tr>
                <td>{SETUP_PAYMENT_POLICY_LABEL}</td>
                <td>{SETUP_PAYMENT_POLICY_SELECT}</td>
                <td>{SETUP_PAYMENT_SOURCE}</td>
            </tr>
            <tr>
                <td>{SETUP_FULFILLMENT_POLICY_LABEL}</td>
                <td>{SETUP_FULFILLMENT_POLICY_SELECT}</td>
                <td>{SETUP_FULFILLMENT_SOURCE}</td>
            </tr>
            <tr>
                <td>{SETUP_RETURN_POLICY_LABEL}</td>
                <td>{SETUP_RETURN_POLICY_SELECT}</td>
                <td>{SETUP_RETURN_SOURCE}</td>
            </tr>
            <tr>
                <td>{SETUP_DESCRIPTION_HEADER_LABEL}</td>
                <td>
                    <div class='ebay-setup-template-editor' data-ebay-template-editor='header'>
                        <div class='ebay-setup-template-editor__toolbar' data-ebay-template-toolbar='header'></div>
                        <div class='ebay-setup-template-editor__surface' data-ebay-template-surface='header'></div>
                        <textarea name='setup_description_header_html' rows='6' cols='100' data-ebay-template-source='header'>{SETUP_DESCRIPTION_HEADER_VALUE}</textarea>
                    </div>
                </td>
                <td>{SETUP_DESCRIPTION_HEADER_SOURCE}</td>
            </tr>
            <tr>
                <td>{SETUP_DESCRIPTION_FOOTER_LABEL}</td>
                <td>
                    <div class='ebay-setup-template-editor' data-ebay-template-editor='footer'>
                        <div class='ebay-setup-template-editor__toolbar' data-ebay-template-toolbar='footer'></div>
                        <div class='ebay-setup-template-editor__surface' data-ebay-template-surface='footer'></div>
                        <textarea name='setup_description_footer_html' rows='6' cols='100' data-ebay-template-source='footer'>{SETUP_DESCRIPTION_FOOTER_VALUE}</textarea>
                    </div>
                </td>
                <td>{SETUP_DESCRIPTION_FOOTER_SOURCE}</td>
            </tr>
            <tr>
                <td>{SETUP_SOURCE_LABEL}</td>
                <td colspan="2"><input class='submit' type='submit' name='ebay_setup_save' value='{SETUP_SAVE}' /></td>
            </tr>
        </table>
    </form>
    <p>{SETUP_DESCRIPTION_HELP}</p>

    <details class="ebay-compact-block">
        <summary>{SETUP_CREATE_LOCATION}</summary>
        <form action='?m=in&a=eb' method='post' name='setup_location'>
            <input type='hidden' name='m' value='in'>
            <input type='hidden' name='a' value='eb'>
            <input type='hidden' name='target_group_id' value='{TARGET_GROUP_ID}'>
            <table class="bordered">
                <tr>
                    <td>{SETUP_MERCHANT_LOCATION_LABEL}</td>
                    <td><input type='text' name='setup_merchant_location_key' size='25' value='{SETUP_MERCHANT_LOCATION_VALUE}' /></td>
                </tr>
                <tr>
                    <td>{SETUP_LOCATION_NAME_LABEL}</td>
                    <td><input type='text' name='setup_location_name' size='25' value='{SETUP_LOCATION_NAME_VALUE}' /></td>
                </tr>
                <tr>
                    <td>{SETUP_LOCATION_ADDRESS_LABEL}</td>
                    <td><input type='text' name='setup_location_address1' size='40' value='{SETUP_LOCATION_ADDRESS_VALUE}' /></td>
                </tr>
                <tr>
                    <td>{SETUP_LOCATION_CITY_LABEL}</td>
                    <td>
                        <input type='text' name='setup_location_city' size='20' value='{SETUP_LOCATION_CITY_VALUE}' />
                        {SETUP_LOCATION_POSTAL_LABEL} <input type='text' name='setup_location_postal_code' size='8' value='{SETUP_LOCATION_POSTAL_VALUE}' />
                        {SETUP_LOCATION_COUNTRY_LABEL} <input type='text' name='setup_location_country' size='4' value='{SETUP_LOCATION_COUNTRY_VALUE}' />
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td><input class='submit submit--secondary' type='submit' name='ebay_setup_create_location' value='{SETUP_CREATE_LOCATION}' /></td>
                </tr>
            </table>
        </form>
    </details>

    <details class="ebay-compact-block">
        <summary>{SETUP_POLICY_CREATE_TITLE}</summary>
        <form action='?m=in&a=eb' method='post' name='setup_policy_create'>
            <input type='hidden' name='m' value='in'>
            <input type='hidden' name='a' value='eb'>
            <input type='hidden' name='target_group_id' value='{TARGET_GROUP_ID}'>
            <table class="bordered">
                <tr>
                    <td>{SETUP_PAYMENT_PAYLOAD_LABEL}</td>
                    <td>
                        <textarea name='setup_payment_policy_payload' rows='8' cols='110'>{SETUP_PAYMENT_PAYLOAD_VALUE}</textarea><br>
                        <input class='submit submit--secondary' type='submit' name='ebay_setup_create_payment_policy' value='{SETUP_CREATE_PAYMENT_POLICY}' />
                    </td>
                </tr>
                <tr>
                    <td>{SETUP_FULFILLMENT_PAYLOAD_LABEL}</td>
                    <td>
                        <textarea name='setup_fulfillment_policy_payload' rows='12' cols='110'>{SETUP_FULFILLMENT_PAYLOAD_VALUE}</textarea><br>
                        <input class='submit submit--secondary' type='submit' name='ebay_setup_create_fulfillment_policy' value='{SETUP_CREATE_FULFILLMENT_POLICY}' />
                    </td>
                </tr>
                <tr>
                    <td>{SETUP_RETURN_PAYLOAD_LABEL}</td>
                    <td>
                        <textarea name='setup_return_policy_payload' rows='10' cols='110'>{SETUP_RETURN_PAYLOAD_VALUE}</textarea><br>
                        <input class='submit submit--secondary' type='submit' name='ebay_setup_create_return_policy' value='{SETUP_CREATE_RETURN_POLICY}' />
                    </td>
                </tr>
            </table>
        </form>
    </details>

    <div>{SETUP_ASSETS_HTML}</div>
    <div class="err">{SETUP_MESSAGE}</div>
</fieldset>

<fieldset class="inputs">
    <h2>Audit & synchro</h2>
    <div class="info">{EBAY_SYNC_RUN_SUMMARY}</div>
    <div>{EBAY_SYNC_RUN_HISTORY_HTML}</div>
    <div>{EBAY_SYNC_RUN_DETAILS_HTML}</div>

    <h3>Audit de cohérence eBay</h3>
    <div class="info">{EBAY_RECONCILE_SUMMARY}</div>
    <div>{EBAY_RECONCILE_RUNS_HTML}</div>
    <div>{EBAY_RECONCILE_DETAILS_HTML}</div>
</fieldset>

<div class="err">{MSG}</div>

<script type="text/javascript" src="{TEMPLATE_FOLDER}/js/ebay-setup-template-editor.js"></script>
<script type="text/javascript" src="{TEMPLATE_FOLDER}/js/ebay-reconcile.js"></script>
