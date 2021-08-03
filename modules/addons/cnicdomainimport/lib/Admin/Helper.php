<?php

namespace WHMCS\Module\Addon\CnicDomainImport\Admin;

use WHMCS\Config\Setting as Setting;
use Illuminate\Database\Capsule\Manager as DB;

if (defined("ROOTDIR")) {
    require_once(implode(DIRECTORY_SEPARATOR, [ROOTDIR,"includes","registrarfunctions.php"]));
}

/**
 * PHP Helper Class
 *
 * @copyright  2018 HEXONET GmbH, MIT License
 */
class Helper
{
    /**
     * Return list of available Payment Gateways
     *
     * @return array list of payment gateways
     */
    public static function getPaymentMethods()
    {
        static $paymentmethods = null;
        if (is_null($paymentmethods)) {
            $paymentmethods = [];
            $r = localAPI("GetPaymentMethods", []);
            if ($r["result"]) {
                foreach ($r["paymentmethods"]["paymentmethod"] as $pm) {
                    $paymentmethods[$pm["module"]] = $pm["displayname"];
                }
            }
        }
        return $paymentmethods;
    }

    /**
     * Get client details by given email address
     * @param string $email the client's email address
     * @return array the client details or false if not found
     */
    public static function getClientsDetailsByEmail($email)
    {
        $r = localAPI("GetClientsDetails", [
            "email" => $email,
            "stats" => false
        ]);
        if ($r["result"] === "success") {
            return [ //HM-711
                "success" => true,
                "id" => $r["id"],
                "currency" => $r["currency"]
            ];
        }
        return [
            "success" => false,
            "errormsg" => $r["message"]
        ];
    }

    /**
     * Get client details by given client id
     * @param int $clientid the client id
     * @return array the client details or false if not found
     */
    public static function getClientsDetailsById($clientid)
    {
        $r = localAPI("GetClientsDetails", [
            "clientid" => $clientid,
            "stats" => false
        ]);
        if ($r["result"] === "success") {
            return [
                "success" => true,
                "id" => $r["id"],
                "currency" => $r["currency"]
            ];
        }
        return [
            "success" => false,
            "errormsg" => $r["message"]
        ];
    }

    /**
     * Create a new client by given API contact data and return the client id.
     *
     * @param array $contact StatusContact PROPERTY data from API
     * @param string $currency currency id
     * @param string $password generate password string
     * @param string $taxid the client tax id
     *
     * @return array
     */
    public static function addClient($contact, $currencyid, $password, $taxid = "")
    {
        $fmap = [
            "First Name" => "firstname",
            "Last Name" => "lastname",
            "Company Name" => "companyname",
            "Address" => "address1",
            "Address 2" => "address2",
            "City" => "city",
            "State" => "state",
            "Postcode" => "postcode",
            "Country" => "country",
            "Phone" => "phonenumber",
            "Email" => "email"
        ];
        $request = [
            "password2" => $password,
            "currency" => $currencyid,
            "language" => "english"
        ];
        foreach ($fmap as $ckey => $dbkey) {
            $request[$dbkey] = $contact[$ckey];
        }
        if (empty($registrant["postcode"])) {
            $registrant["postcode"] = "N/A";
        }
        $request["phonenumber"] = preg_replace("/[^0-9 ]/", "", $request["phonenumber"]);//only numbers and spaces allowed
        $request["postcode"] = preg_replace("/[^0-9a-zA-Z ]/", "", $request["postcode"]);
        $request["country"] = strtoupper($request["country"]);
        $request["tax_id"] = $taxid;

        $r = localAPI("AddClient", $request);

        if ($r["result"] === "success") {
            return Helper::getClientsDetailsByEmail($request["email"]);
        }
        return [
            "success" => false,
            "errormsg" => $r["message"]
        ];
    }

    /**
     * Check if a domain already exists in WHMCS database
     * @param string $domain domain name
     * @return boolean check result
     */
    public static function checkDomainExists($domain)
    {
        $r = localAPI("GetClientsDomains", [
            "domain" => $domain,
            "limitnum" => 1
        ]);
        return (
            ($r["result"] === "success")
            && ($r["totalresults"] > 0)
        );
    }

    /**
     * Create a domain by given data
     *
     * @param \WHMCS\Domain\Registrar\Domain $domain domain object
     * @param string $registrar Registrar ID
     * @param array $clientdetails client details e.g. id, currency
     * @param string $gateway payment gateway
     * @param array $pricing tld pricing
     * @param bool $isPremium flag if the domain is premium or not
     * @param array $premiumpricing premium pricing details
     * @return array
     */
    public static function createDomain($domain, $registrar, $clientdetails, $gateway, $pricing, $isPremium, $premiumpricing)
    {
        if (!isset($pricing["renew"][1])) {
            return [
                "success" => false,
                "msgid" => "tldrenewalpriceerror",
                "allowretry" => true
            ];
        }

        if ($isPremium) {
            $currErr = [
                "success" => false,
                "msgid" => "currencynotdefinedforpremium",
                "msgdata" => [
                    "currencycode" => "N/A"
                ],
                "allowretry" => true
            ];
            if (!isset($premiumpricing["CurrencyCode"])) {
                return $currErr;
            }
            $registrarCurrencyId = DB::table("tblcurrencies")->where("code", "=", $premiumpricing["CurrencyCode"])->value("id");
            if (!$registrarCurrencyId) {
                $currErr["msgdata"]["currencycode"] = $premiumpricing["CurrencyCode"];
                return $currErr;
            }
        }

        // add prices for addons, taxes, etc.
        $pricing = Helper::addFeesTaxes($domain, $client, $pricing);

        // expirydate, nextduedate, nextinvoicedate
        $ndd = $nid = $expirydate = $domain->getExpiryDate();
        $ndddays = (int) Setting::getValue("DomainSyncNextDueDateDays");
        if (
            Setting::getValue("DomainSyncNextDueDate")
            && $ndddays
        ) {
            $newduedate = explode("-", $ndd);
            $newduedate = date("Y-m-d", mktime(0, 0, 0, $newduedate[1], $newduedate[2] - $ndddays, $newduedate[0]));
            if ($newduedate !== $ndd) {
                $ndd = $newduedate;
                $nid = $newduedate;
            }
        }

        // build data for sql insert
        try {
            $id = DB::table('tbldomains')->insertGetId([
                "userid" => $clientdetails["id"],
                "orderid" => 0,
                "type" => "Register",
                "registrationdate" => $domain->registrarData["createdDate"],
                "domain" => strtolower($domain->getDomain()),
                "firstpaymentamount" => $pricing["register"], // price plus tax, addons, markup
                "recurringamount" => $pricing["renew"],// price plus tax, addons, markup
                "registrar" => $registrar,
                "registrationperiod" => $pricing["period"], // 1
                "expirydate" => $expirydate,
                "promoid" => 0,
                "status" => "Active",
                "nextduedate" => $ndd,
                "nextinvoicedate" => $nid,
                "paymentmethod" => $gateway,
                "dnsmanagement" => (int) $domain->getDnsManagementStatus(),
                "emailforwarding" => (int) $domain->getEmailForwardingStatus(),
                "idprotection" => (int) $domain->getIdProtectionStatus(),
                "is_premium" => (int) !empty($premiumpricing),
                "donotrenew" => 0,
                "synced" => 0
            ]);
        } catch (Exception $e) {
            return [
                "success" => false,
                "msgid" => "domaincreateerror",
                "allowretry" => true
            ];
        }

        if (!empty($premiumpricing)) {
            $addcurrency = false;
            if (array_key_exists("transfer", $premiumpricing)) {
                //"register" is not available for registered domains
                //hint: checkdomains
                $extraDetails = \WHMCS\Domain\Extra::firstOrNew([
                    "domain_id" => $id,
                    "name" => "registrarCostPrice"
                ]);
                $extraDetails->value = $premiumpricing["transfer"];
                $extraDetails->save();
                $addcurrency = true;
            }
            if (array_key_exists("renew", $premiumpricing)) {
                $extraDetails = \WHMCS\Domain\Extra::firstOrNew([
                    "domain_id" => $id,
                    "name" => "registrarRenewalCostPrice"
                ]);
                $extraDetails->value = $premiumpricing["renew"];
                $extraDetails->save();
                $addcurrency = true;
            }
            if ($addcurrency && isset($premiumpricing["CurrencyCode"])) {
                $extraDetails = \WHMCS\Domain\Extra::firstOrNew([
                    "domain_id" => $id,
                    "name" => "registrarCurrency"
                ]);
                $extraDetails->value = $registrarCurrencyId; // prefilled above
                $extraDetails->save();
            }
            // TODO idnLanguage
        }

        // save additional domain fields to DB
        // returned by GetDomainInformation
        $addflds = $domain->registrarData["domainfields"];
        $addflds->saveToDatabase($id);

        return [
            "success" => true,
            "msgid" => "ok"
        ];
    }

    public static function addFeesTaxes($domain, $clientdata, $pricing)
    {
        $addons = $pricing["addons"];
        $typeprice = $pricing["register"][1];
        $renewprice = $pricing["renew"][1];

        // TODO: important to preconfigure whmcs before starting with import therefore!
        //--- consider add-on prices when configured in WHMCS and active on domain level
        if ($addons["idprotect"] || $addons["email"] || $addons["dns"]) {
            $addonsPricing = DB::table("tblpricing")
                ->where("type", "domainaddons")
                ->where("currency", $clientdata["currency"])
                ->where("relid", 0)->first([
                    "ssetupfee", // id protection
                    "msetupfee", // dns management
                    "qsetupfee" // email forwarding
                ]);
            if ($addons["idprotect"] && $domain && $domain->getIdProtectionStatus()) {
                $typeprice += $addonsPricing->ssetupfee; // * $regperiod here: 1
                $renewprice += $addonsPricing->ssetupfee; // * $regperiod here: 1
            }
            if ($addons["dns"] && $domain && $domain->getDnsManagementStatus()) {
                $typeprice += $addonsPricing->msetupfee; // * $regperiod here: 1
                $renewprice += $addonsPricing->msetupfee; // * $regperiod here: 1
            }
            if ($addons["email"] && $domain && $domain->getEmailForwardingStatus()) {
                $typeprice += $addonsPricing->qsetupfee; // * $regperiod here: 1
                $renewprice += $addonsPricing->qsetupfee; // * $regperiod here: 1
            }
        }

        //--- consider taxes
        if (
            \WHMCS\Config\Setting::getValue("TaxEnabled")
            && \WHMCS\Config\Setting::getValue("TaxInclusiveDeduct")
        ) {
            $excltaxrate = 1;
            $taxdata = getTaxRate(1, $client["state"], $client["country"]);
            $taxrate = $taxdata["rate"] / 100;
            $taxdata = getTaxRate(2, $client["state"], $client["country"]);
            $taxrate2 = $taxdata["rate"] / 100;
            if (
                \WHMCS\Config\Setting::getValue("TaxType") === "Inclusive"
                && (!$taxrate && !$taxrate2 || $clientdata["taxexempt"])
            ) {
                $systemFirstTaxRate = \WHMCS\Database\Capsule::table("tbltax")->value("taxrate");
                if ($systemFirstTaxRate) {
                    $excltaxrate = 1 + $systemFirstTaxRate / 100;
                }
            }
            $typeprice = round($typeprice / $excltaxrate, 2);
            $renewprice = round($renewprice / $excltaxrate, 2);
        }

        return [
            "period" => 1,
            "register" => $typeprice,
            "renew" => $renewprice
        ];
    }

    /**
     * import an existing domain from HEXONET API.
     *
     * @param string $domainidn idn
     * @param string $domainpc punycode variant
     * @param string $registrar registrar id
     * @param string $gateway payment gateway
     * @param string $currency currency
     * @param string $password the default password we set for newly created customers
     * @param array  $directImport (optional, to be downward compatible)
     *
     * @return array where property "success" (boolean) identifies the import result and property "msgid" the translation/language key
     */
    public static function importDomain($domainidn, $domainpc, $registrar, $gateway, $currency, $password, $directImport = ["toClientImport" => 0])
    {
        if (!preg_match("/\.(.*)$/i", $domainidn)) {
            return [
                "success" => false,
                "msgid" => "domainnameinvaliderror"
            ];
        }
        if (Helper::checkDomainExists($domainidn)) {
            return [
                "success" => false,
                "msgid" => "alreadyexistingerror"
            ];
        }
        // direct import to client: check if client exists
        $client = null;
        if (
            isset($directImport["toClientImport"])
            && ($directImport["toClientImport"] === 1)
            && isset($directImport["clientid"])
            && ($directImport["clientid"] >= 0)
        ) {
            // client by given client id
            $client = Helper::getClientsDetailsById($directImport["clientid"]);
            if (!$client["success"]) {
                return [
                    "success" => false,
                    "msgid" => "unabletoloadclient",
                    "msgdata" => $directImport
                ];
            }
        }

        // load registrar module and check if used functions exist
        $reg = new \WHMCS\Module\Registrar();
        $reg->load($registrar);
        $fns = [
            $registrar . "_GetDomainInformation",
            $registrar . "_CheckAvailability",
            $registrar . "_GetContactDetails"
        ];
        foreach ($fns as $fn) {
            if (!function_exists($fn)) {
                return [
                    "success" => false,
                    "msgid" => "registrarnotsupported"
                ];
            }
        }

        // build params
        $params = $reg->getSettings();
        $params["domain"] = $domainidn;
        list($params["sld"], $params["tld"]) = explode(".", $domainidn, 2);
        $params["registrar"] = $registrar;
        $params["status"] = "Active";

        // domain status
        try {
            $fn = $registrar . "_GetDomainInformation";
            $domainObj = $fn($params);
        } catch (\Exception $e) {
            return [
                "success" => false,
                "msgid" => "domainnotfound"
            ];
        }
        if (!property_exists($domainObj, 'registrarData')) {
            return [
                "success" => false,
                "msgid" => "registrarnotsupported"
            ];
        }
        // TODO add fields data Notice (validate vatid, additional fields after import)
        // orgs without vatid for example

        // no direct client import
        if (is_null($client)) {
            $fn = $registrar . "_GetContactDetails";
            $r = $fn($params);

            $registrant = $r["Registrant"];
            if (empty($registrant["Email"])) {
                return [
                    "success" => false,
                    "msgid" => "registrantcreateerrornoemail",
                    "allowretry" => true
                ];
            }
            if (empty($registrant["Phone"])) {
                return [
                    "success" => false,
                    "msgid" => "registrantcreateerrornophone",
                    "allowretry" => true
                ];
            }
            $client = Helper::getClientsDetailsByEmail($registrant["Email"]);
            if (!$client) {
                $tax = $domainObj->registrarData["registrantTaxId"];
                $client = Helper::addClient($registrant, $currency, $password, $taxid);
                if (!$client["success"]) {
                    return [
                        "success" => false,
                        "msgid" => $client["errormsg"]
                    ];
                }
            }
        }

        // check if this is a premium domain, but having
        // Premium Domains not activated and configured in WHMCS
        $addData = $domainObj->registrarData;
        // get premium domain data
        $isPremium = $addData["is_premium"];
        $premiumpricing = [];
        if ($isPremium) {
            $fn = $registrar . "_GetPremiumPrice";
            if (!function_exists($fn)) {
                return [
                    "success" => false,
                    "msgid" => "premiumnotsupported"
                ];
            }
            $isPremiumSupported = (bool) (int) Setting::getValue("PremiumDomains");
            if (!$isPremiumSupported) {
                return [
                    "success" => false,
                    "msgid" => "premiumnotactive",
                    "allowretry" => true
                ];
            }
            $premiumpricing = $fn($params);
        }

        // load tld prices
        $domainprices = localAPI("GetTLDPricing", [
            "currencyid" => $client["currency"]
        ]);
        if ($domainprices["result"] !== "success") {
            return [
                "success" => false,
                "msgid" => "tldrenewalpriceerror",
                "allowretry" => true
            ];
        }
        $prices = $domainprices["pricing"][$params["tld"]];

        // create the domain in DB
        return Helper::createDomain(
            $domainObj,
            $registrar,
            $client,
            $gateway,
            $prices,
            $isPremium,
            $premiumpricing
        );
    }

    public static function generateRandomString($length = 10)
    {
        static $stringCharset = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $charactersLength = strlen($stringCharset);
        $randomString = "";
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $stringCharset[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}