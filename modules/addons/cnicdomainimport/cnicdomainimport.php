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
    $imgpath = implode(DIRECTORY_SEPARATOR, [
        ROOTDIR, "modules", "addons", "cnicdomainimport", "logo.png"
    ]);
    $imgdata = file_get_contents($imgpath);
    $imgsrc = "";
    if ($imgdata) {
        $imgsrc = "data:image/png;base64," . base64_encode($imgdata);
    }

    return [
        // Display name for your module
        "name" => "CNIC Domain Importer",
        // Description displayed within the admin interface
        "description" => "This module allows to import existing domains.",
        // Module author name
        "author" => <<<HTML
            <a href="https://www.hexonet.net/" target="_blank">
                <img style="max-width:100px" src="$imgsrc" alt="CentralNic Group PLC" />
            </a>
    HTML,
        // Default language
        "language" => "english",
        // Version number
        "version" => "1.4.1",
        // fields
        "fields" => []
    ];
}

/**
 * Admin Area Output.
 *
 * @see AddonModule\Admin\Controller::index()
 *
 * @param array $vars
 * @return string
 */
function cnicdomainimport_output($vars)
{
    $r = "L14oaGV4b25ldHxpc3BhcGl8a2V5c3lzdGVtc3xycnBwcm94eSkkL2k=";
    $templatePath = implode(DIRECTORY_SEPARATOR, [
        ROOTDIR, "modules", "addons", "cnicdomainimport", "templates", "admin"
    ]);
    // If any files upload request has been made merge with arguments array so it can be accessible
    if (isset($_FILES)) {
        $vars = array_merge($vars, $_FILES);
    }

    $smarty = new Smarty();
    $smarty->caching = false;
    $smarty->setCompileDir($GLOBALS['templates_compiledir']);
    $smarty->setTemplateDir($templatePath);

    // load list of registrars - always necessary
    $registrar = new \WHMCS\Module\Registrar();
    $activeRegistrars = [];
    foreach ($registrar->getList() as $reg) {
        if (
            preg_match(base64_decode($r), $reg)
            && $registrar->load($reg)
            && $registrar->isActivated()
        ) {
            $activeRegistrars[$reg] = $registrar->getDisplayName();
        }
    }

    //populate smarty variables. eg: WEB_ROOT
    global $aInt;
    $aInt->populateStandardAdminSmartyVariables();
    $smarty->assign($aInt->templatevars);
    $smarty->assign($vars);
    $smarty->assign('registrars', $activeRegistrars);
    $smarty->assign('registrar_selected', [
        $_REQUEST["registrar"] => " selected"
    ]);
    //call the dispatcher with action and data
    $dispatcher = new AdminDispatcher();
    echo $dispatcher->dispatch(
        $_REQUEST['action'],
        $vars,
        $smarty
    );
}
