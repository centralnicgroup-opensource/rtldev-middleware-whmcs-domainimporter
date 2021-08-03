<?php

/**
 * addon module language file.
 * Language: English
 */

// results / errors
$_ADDONLANG['ok'] = "Imported";
$_ADDONLANG['actionerror'] = "Invalid action requested. Please go back and try again.";
$_ADDONLANG['domaincreateerror'] = "Unable to create domain.";
$_ADDONLANG['tldrenewalpriceerror'] = "Unable to determinate domain renewal price.";
$_ADDONLANG['registrantcreateerror'] = "Unable to create client.";
$_ADDONLANG['registrantcreateerrornophone'] = "Unable to create client (no phone number).";
$_ADDONLANG['registrantfetcherror'] = "Unable to load registrant data.";
$_ADDONLANG['registrantmissingerror'] = "Missing Registrant in domain configuration.";
$_ADDONLANG['alreadyexistingerror'] = "Domain name already exists.";
$_ADDONLANG['domainnameinvaliderror'] = "Invalid domain name.";
$_ADDONLANG['nogatewayerror'] = "No Payment Gateway configured.";
$_ADDONLANG['domainlistfetcherror'] = "Failed to load list of domains.";
$_ADDONLANG['nodomainsfounderror'] = "The query did not return any domains names.";
$_ADDONLANG['domainsfound'] = "The query returned the below domain names.";
$_ADDONLANG['nothingtoimporterror'] = "Nothing to import.";
$_ADDONLANG['premiumnotactive'] = "Unable to import: Turn on & configure Premium Domain Support.";
$_ADDONLANG['unabletoloadclient'] = "Unable to load client details for Client #:clientid.";
$_ADDONLANG['registrantcreateerrornoemail'] = "Registrant has no Email Address assigned.";
$_ADDONLANG['currencynotdefinedforpremium'] = "Currency not defined: :currencycode";
$_ADDONLANG['domainnotfound'] = "Domain not found.";
$_ADDONLANG['registrarnotsupported'] = "Registrar not supported";

// labels
$_ADDONLANG['label.registrar'] = "from Registrar";
$_ADDONLANG['label.domain'] = "Domain";
$_ADDONLANG['label.domains'] = "Domains";
$_ADDONLANG['label.gateway'] = "Payment Method";
$_ADDONLANG['label.currency'] = "Currency";

// options
$_ADDONLANG['option.choose'] = "Please choose ...";

// placeholders
$_ADDONLANG['ph.domainfilter'] = "Enter Domain Name Filter";
$_ADDONLANG['ph.clientid'] = "Activate the checkbox and enter the ID of the client account (optional).";

// buttons
$_ADDONLANG['bttn.importdomainlist'] = "Start Import";
$_ADDONLANG['bttn.uploaddomainlist'] = "Upload from File";
$_ADDONLANG['bttn.retryimport'] = "Retry";
$_ADDONLANG['bttn.previewacc'] = "Show Data";
$_ADDONLANG['bttn.back'] = "Back";

// columns
$_ADDONLANG['col.domain'] = "Domain";
$_ADDONLANG['col.importresult'] = "Import Result";
$_ADDONLANG['col.left'] = "Left";

// status
$_ADDONLANG['status.importing'] = "Importing";
$_ADDONLANG['status.importdone'] = "Import done";

// descr
$_ADDONLANG['descr.importto'] = "Otherwise we will automatically create new clients in WHMCS based on the registrant data per domain and with the below settings.";
$_ADDONLANG['descr.domains'] = "separated by newline";

// error
$_ADDONLANG['error.clientnotfound'] = "Failed to load client data ...";
