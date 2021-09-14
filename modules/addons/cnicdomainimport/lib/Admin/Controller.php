<?php

namespace WHMCS\Module\Addon\CnicDomainImport\Admin;

/**
 * Admin Area Controller
 */
class Controller
{
    /**
     * Index action. Display the Form.
     *
     * @param array $vars Module configuration parameters
     * @param \Smarty $smarty Smarty template instance
     *
     * @return string html code
     */
    public function index($vars, $smarty)
    {
        // Load Payment Methods
        $gateways = Helper::getPaymentMethods();
        if (empty($gateways)) {
            $smarty->assign("error", $vars["_lang"]["nogatewayerror"]);
            return $smarty->fetch("error.tpl");
        }
        // Load Currencies
        $currencies = [];
        $results = localAPI("GetCurrencies", []);
        if ($results["result"] === "success") {
            foreach ($results["currencies"]["currency"] as $idx => $d) {
                $currencies[$d["code"]] = $d;
            }
        }

        // assign vars to smarty
        $smarty->assign("noemail", $_REQUEST["noemail"]);
        $smarty->assign("marketingoptin", $_REQUEST["marketingoptin"]);
        $smarty->assign("gateways", $gateways);
        $smarty->assign("gateway_selected", [ $_REQUEST["gateway"] => " selected" ]);
        $smarty->assign("currencies", $currencies);
        $smarty->assign("currency_selected", [ $_REQUEST["currency"] => " selected" ]);
        return $smarty->fetch("index.tpl");
    }

    /**
     * pull action. Fetch the domain list using the provided domain name filter.
     *
     * @param array $vars Module configuration parameters
     * @param Smarty $smarty Smarty template instance
     *
     * @return string html code
     */
    public function getclientdetails($vars, $smarty)
    {
        header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
        header("Content-type: application/json; charset=utf-8");

        $json = [
            "success" => false
        ];

        if ($_REQUEST["clientid"] !== "") {
            $result = localAPI("GetClientsDetails", [
                "clientid" => $_REQUEST["clientid"],
                "stats" => false
            ]);
            $json["success"] = ($result["result"] === "success");
            if ($json["success"]) {
                $json["clientdetails"] = <<<HTML
                    {$result["client"]["fullname"]}<br/>
                    {$result["client"]["companyname"]}<br/>
                    {$result["client"]["email"]}<br/>
                    {$result["client"]["phonenumberformatted"]}<br/>
                    {$result["client"]["address1"]}<br/>
HTML;
                if (!empty($result["client"]["address2"])) {
                    $json["clientdetails"] .= <<<HTML
                        {$result["client"]["address2"]}<br/>
HTML;
                }
                $json["clientdetails"] .= <<<HTML
                    {$result["client"]["postcode"]} {$result["client"]["city"]}<br/>
                    {$result["client"]["state"]}, {$result["client"]["country"]}<br/>
HTML;
            } else {
                $json["msg"] = $vars["_lang"]["error.clientnotfound"];
            }
        }

        die(json_encode($json));
    }

    /**
     * import action. trigger import of domain list through javascript.
     *
     * @param array $vars Module configuration parameters
     * @param Smarty $smarty Smarty template instance
     *
     * @return string html code
     */
    public function import($vars, $smarty)
    {
        // import logic done on jscript-side
        return $smarty->fetch("import.tpl");
    }

    /**
     * importsingle action. import a signle domain.
     *
     * @param array $vars Module configuration parameters
     * @param Smarty $smarty Smarty template instance
     *
     * @return string html code
     */
    public function importsingle($vars, $smarty)
    {
        header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
        header("Content-type: application/json; charset=utf-8");

        $result = Helper::importDomain(
            $_REQUEST["idn"],
            $_REQUEST["pc"],
            $_REQUEST["registrar"],
            $_REQUEST["gateway"],
            [
                "currency" => $_REQUEST["currency"],
                "noemail" => (bool)$_REQUEST["noemail"],
                "marketingoptin" => (bool)$_REQUEST["marketingoptin"],
                "password2" => Helper::generateRandomString(),
                "language" => "english" // TODO
            ],
            [
                "toClientImport" => (int) $_REQUEST["toClientImport"],
                "clientid" => (int) $_REQUEST["clientid"]
            ]
        );
        if ($result["msgid"]) {
            $result["msg"] = $vars["_lang"][$result["msgid"]];
        }
        //if custom translation does not exist for "msgid" in the module
        if (!$result["msg"]) {
            $result["msg"] = \Lang::trans($result["msgid"]);
        }
        if (isset($result["msgdata"])) {
            foreach ($result["msgdata"] as $key => $val) {
                $result["msg"] = str_replace(":" . $key, $val, $result["msg"]);
            }
        }

        die(json_encode($result));
    }
}
