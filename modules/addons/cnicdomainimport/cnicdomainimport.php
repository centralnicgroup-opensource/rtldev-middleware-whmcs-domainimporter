<?php

/**
 * WHMCS Import Addon Module
 *
 * This Addon allows to import existing Domains from any CentralNic Brand's System.
 *
 * @see https://github.com/centralnic-reseller/whmcs-domainimporter/wiki/
 *
 * @copyright Copyright (c) Kai Schwarz, CentralNic Group PLC, 2021
 * @license https://github.com/centralnic-reseller/whmcs-domainimporter/blob/master/LICENSE/ MIT License
 */

use WHMCS\Module\Addon\CnicDomainImport\Admin\AdminDispatcher;

/**
 * Define addon module configuration parameters.
 *
 * Includes a number of required system fields including name, description,
 * author, language and version.
 *
 * Also allows you to define any configuration parameters that should be
 * presented to the user when activating and configuring the module. These
 * values are then made available in all module function calls.
 *
 * Examples of each and their possible configuration parameters are provided in
 * the fields parameter below.
 *
 * @return array
 */
function cnicdomainimport_config()
{
    $data = file_get_contents(implode(DIRECTORY_SEPARATOR, [ROOTDIR, "modules", "addons", "cnicdomainimport", "logo.png"]));
    $src = ($data) ? 'data:image/png;base64,' . base64_encode($data) : '';

    return [
        // Display name for your module
        "name" => "CNIC Domain Importer",
        // Description displayed within the admin interface
        "description" => "This module allows to import existing domains.",
        // Module author name
        "author" => '<a href="https://www.hexonet.net/" target="_blank"><img style="max-width:100px" src="' . $src . '" alt="CentralNic Group PLC" /></a>',
        // Default language
        "language" => "english",
        // Version number
        "version" => "1.0.2",
        // fields
        "fields" => []
    ];
}

/**
 * Admin Area Output.
 *
 * @see AddonModule\Admin\Controller::index()
 *
 * @return string
 */
function cnicdomainimport_output($vars)
{
    $r = "L14oaGV4b25ldHxpc3BhcGl8a2V5c3lzdGVtc3xycnBwcm94eSkkL2k=";
    $smarty = new Smarty();
    $smarty->caching = false;
    $smarty->setCompileDir($GLOBALS['templates_compiledir']);
    $smarty->setTemplateDir(implode(DIRECTORY_SEPARATOR, [ROOTDIR, "modules", "addons", "cnicdomainimport", "templates", "admin"]));

    // load list of registrars - always necessary
    $registrar = new \WHMCS\Module\Registrar();
    $activeregs = [];
    foreach ($registrar->getList() as $reg) {
        if (preg_match(base64_decode($r), $reg)) {
            if ($registrar->load($reg)) {
                if ($registrar->isActivated()) {
                    $activeregs[$reg] = $registrar->getDisplayName();
                }
            }
        }
    }

    //populate smarty variables. eg: WEB_ROOT
    global $aInt;
    $aInt->populateStandardAdminSmartyVariables();
    $smarty->assign($aInt->templatevars);
    $smarty->assign($vars);
    $smarty->assign('registrars', $activeregs);
    $smarty->assign('registrar_selected', [ $_REQUEST["registrar"] => " selected" ]);
    //call the dispatcher with action and data
    $dispatcher = new AdminDispatcher();
    echo $dispatcher->dispatch(
        $_REQUEST['action'],
        $vars,
        $smarty
    );
}
