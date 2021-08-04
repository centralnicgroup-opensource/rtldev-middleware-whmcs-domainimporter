<link rel="stylesheet" type="text/css" href="{$WEB_ROOT}/modules/addons/cnicdomainimport/assets/styles.css?ts={mktime()}"/>
<table class="table table-condensed small scrollable">
    <thead>
        <tr>
            <th>{$_lang['col.domain']}</th>
            <th></th>
            <th>{$_lang['col.importresult']}</th>
            <th></th>
        </tr>
    </thead>
    <tbody id="importresults"></tbody>
    <tfoot>
        <tr>
            <th colspan="4">
                <div class="progress">
                    <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="counterleft">0%</div>
                </div>
                <div id="inprogress"></div>
            </th>
        </tr>
    </tfoot>
</table>

<form method="POST" id="backform">
    <input type="hidden" name="gateway" value="{$smarty.request.gateway}" />
    <input type="hidden" name="noemail" value="{$smarty.request.noemail}" />
    <input type="hidden" name="marketingoptin" value="{$smarty.request.marketingoptin}" />
    <input type="hidden" name="registrar" value="{$smarty.request.registrar}" />
    <input type="hidden" name="currency" value="{$smarty.request.currency}" />
    <input type="hidden" name="domains" value="{$smarty.request.domains}" />
    <input type="hidden" name="toClientImport" value="{$smarty.request.toClientImport}" />
    <input type="hidden" name="clientid" value="{$smarty.request.clientid}" />
    <input type="hidden" name="action" value="index" />
    <input type="submit" value="{$_lang["bttn.back"]}" class="btn btn-default" />
</form>
<script type="text/javascript">
    const translations = {json_encode($_lang)};
</script>
<script type="text/javascript" src="{$WEB_ROOT}/modules/addons/cnicdomainimport/assets/import.js?ts={mktime()}"></script>
<script type="text/javascript" src="{$WEB_ROOT}/modules/addons/cnicdomainimport/assets/uts46bundle.min.js?ts={mktime()}"></script>