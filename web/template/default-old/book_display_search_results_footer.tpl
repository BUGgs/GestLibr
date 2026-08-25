</table>
<center>{NAV}</center>
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
                        <input type="submit" value="Exécuter" />
                    </noscript>
</form>