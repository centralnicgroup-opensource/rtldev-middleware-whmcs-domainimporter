<form class="form-horizontal" method="POST" id="importform">
    <input type="hidden" name="action" value="import" />

    <!-- DOMAINS -->
    <fieldset>
        <legend>Domains for Import</legend>
        <div class="form-group">
            <div class="col-sm-2" style="text-align:right;">
                <label for="domains" id="labeldomains" class="control-label">
                    {$_lang['label.domains']}
                </label>
                <br />
                <small>({$_lang['descr.domains']})</small>
            </div>
            <div class="col-sm-10">
                <textarea name="domains" id="domains" rows="10"
                    class="form-control">{$smarty.request.domains}</textarea>
            </div>
        </div>

        <!-- IMPORT USING FILE -->
        <div class="form-group">
            <label for="registrar" class="control-label col-sm-2">
                {$_lang['bttn.uploaddomainlist']}
            </label>
            <div class="col-sm-10">
                <input type="file" class="form-control-file" name="importUsingFile" id="importUsingFile">
                <div style="color:red" class="hide" role="alert" id="uploadNotes" style="margin-top:5px"></div>
                <small><a
                        href="{$WEB_ROOT}/modules/addons/cnicdomainimport/assets/domains-sample.csv">{$_lang['label.sampleimportfile']}</a></small>
                <div id="progress-upload" class="progress hide">
                    <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="0"
                        aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>

        <!-- REGISTRAR -->
        <div class="form-group">
            <label for="registrar" class="control-label col-sm-2">{$_lang['label.registrar']}</label>
            <div class="col-sm-10">
                <select id="registrar" name="registrar" class="form-control">
                    <option value="">{$_lang['option.choose']}</option>
                    {foreach from=$registrars key=regid item=name}
                        <option value="{$regid}" {$registrar_selected[$regid]}>{$name}</option>
                    {/foreach}
                </select>
            </div>
        </div>
    </fieldset>
    <!-- IMPORT TO -->
    <fieldset>
        <legend>Direct Import to Client</legend>
        <div class="form-group">
            <label class="control-label col-sm-2" for="toClientImport">
                <input type="hidden" value="0" name="toClientImport" />
                <input class="form-check-input" type="checkbox" value="1" name="toClientImport" id="toClientImport"
                    {if $smarty.request.toClientImport} checked{/if} /> Import to
            </label>
            <div class="col-sm-5">
                <input type="text" class="form-control" id="clientid" name="clientid" value="{$smarty.request.clientid}"
                    placeholder="{$_lang['ph.clientid']}" {if !$smarty.request.toClientImport}disabled {/if} />
                <small>({$_lang['descr.importto']})</small>
            </div>
            <div class="col-sm-5" id="clientdetailscont">
            </div>
        </div>
    </fieldset>
    <!-- PAYMENT METHOD -->
    <fieldset>
        <legend>Otherwise: Auto-Create Clients</legend>
        <div class="form-group">
            <label for="noemail" class="control-label col-sm-2">{$_lang['label.noemail']}</label>
            <div class="col-sm-10">
                <input type="hidden" name="noemail" value="1" />
                <input class="form-check-input" type="checkbox" value="0" name="noemail" />
            </div>
        </div>
        <div class="form-group">
            <label for="marketingoptin" class="control-label col-sm-2">{$_lang['label.marketingoptin']}</label>
            <div class="col-sm-10">
                <input type="hidden" name="marketingoptin" value="0" />
                <input class="form-check-input" type="checkbox" value="1" name="marketingoptin" />
            </div>
        </div>
        <div class="form-group">
            <label for="gateway" class="control-label col-sm-2">{$_lang['label.gateway']}</label>
            <div class="col-sm-10">
                <select id="gateway" name="gateway" class="form-control">
                    {foreach from=$gateways key=gateway item=name}
                        <option value="{$gateway}" {$gateway_selected[$gateway]}>{$name}</option>
                    {/foreach}
                </select>
            </div>
        </div>
        <!-- CURRENCY -->
        <div class="form-group">
            <label for="currency" class="control-label col-sm-2">{$_lang['label.currency']}</label>
            <div class="col-sm-10">
                <select id="currency" name="currency" class="form-control">
                    {foreach from=$currencies key=currency item=item}
                        <option value="{$item.id}" {$currency_selected[$item.id]}>{$currency}</option>
                    {/foreach}
                </select>
            </div>
        </div>
    </fieldset>

    <div class="form-group">
        <div class="col-sm-12">
            <button type="submit" class="btn btn-success actionBttn">{$_lang['bttn.importdomainlist']}</button>
        </div>
    </div>
</form>
<script type="text/javascript" src="{$WEB_ROOT}/modules/addons/cnicdomainimport/assets/form.js?ts={mktime()}"></script>