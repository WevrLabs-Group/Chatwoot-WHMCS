<?php

/***************************************************************************
// *                                                                       *
// * Chatwoot WHMCS Addon (v2.0.0).                                        *
// * This addon modules enables you integrate Chatwoot with your WHMCS     *
//   and leverage its powerful features.                                   *
// * Tested on WHMCS Version: 7.10.3 up to v8.2.0                          *
// * For assistance on how to use and setup Chatwoot, visit                *
//   https://www.chatwoot.com/docs/channels/website                        *
// *                                                                       *
// *************************************************************************
// *                                                                       *
// * Contributed by: WevrLabs Hosting                                      *
// * Email: hello@wevrlabs.net                                             *
// * Website: https://wevrlabs.net                                         *
// *                                                                       *
// *************************************************************************/

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

function hook_chatwoot_output($vars)
{

    # ignore if admin
    if (isset($_SESSION['adminid'])) {
        return;
    }

    $chatwoot_jscode = Capsule::table('tbladdonmodules')->where('module', 'chatwoot')->where('setting', 'chatwoot_jscode')->value('value');

    $signing_hash = Capsule::table('mod_chatwoot')->where('setting', 'signing_hash')->value('value');

    $verification_hash = Capsule::table('tbladdonmodules')->where('module', 'chatwoot')->where('setting', 'chatwoot_verhash')->value('value');

    $chatwoot_position = Capsule::table('tbladdonmodules')->where('module', 'chatwoot')->where('setting', 'chatwoot_position')->value('value');

    $chatwoot_setlabel         = Capsule::table('tbladdonmodules')->where('module', 'chatwoot')->where('setting', 'chatwoot_setlabel')->value('value');
    $chatwoot_setlabelloggedin = Capsule::table('tbladdonmodules')->where('module', 'chatwoot')->where('setting', 'chatwoot_setlabelloggedin')->value('value');

    $isenabled = Capsule::table('tbladdonmodules')->select('value')->where('module', '=', 'chatwoot')->where('setting', '=', 'chatwoot_enable')->where('value', 'on')->count();

    # Disable or Enable Chatwoot
    if (empty($isenabled)) {
        return;
    }

    $client = Menu::context('client');

    $ipaddress = $_SERVER['REMOTE_ADDR'];
    $ip        = gethostbyaddr($ipaddress);

    # Fetch labels
    if (!is_null($client)) {
        $chatwoot_label = $chatwoot_setlabelloggedin;
    }

    # Get client ID and set chat ID
    if (!is_null($client)) {
        if ($vars['clientsdetails']['id']) {
            $ClientID = $vars['clientsdetails']['id'];
        }
    }

    if (!is_null($client)) {
        $ClientChatID    = hash_hmac("sha256", $ClientID, $signing_hash);
        $identifier_hash = hash_hmac("sha256", $ClientChatID, $verification_hash);
    }

    # getting Client Info
    if (!is_null($client)) {

        $apiPostData = array('clientid' => $ClientID, 'stats' => true);
        $apiResults  = localAPI('GetClientsDetails', $apiPostData);

        # Client Info
        $clientemail    = $apiResults['client']['email'];
        $clientname     = $apiResults['client']['fullname'];
        $clientphone    = $apiResults['client']['phonenumberformatted'];
        $clientcompany  = $apiResults['client']['companyname'];
        $clientcountry  = $apiResults['client']['countryname'];
        $clientcity     = $apiResults['client']['city'];
        $clientstate    = $apiResults['client']['fullstate'];
        $clientpostcode = $apiResults['client']['postcode'];
        $clientlang     = $apiResults['client']['language'];

        # Extra Meta
        $clienttickets = $apiResults['stats']['numactivetickets'];
        $clientcredit  = $apiResults['stats']['creditbalance'];
        $clientrevenue = $apiResults['stats']['income'];
        $clientunpaid  = $apiResults['stats']['numunpaidinvoices'];
        # $clientunpaidtotal = $apiResults['stats']['unpaidinvoicesamount'];
        $clientoverdue = $apiResults['stats']['numoverdueinvoices'];
        # $clientoverduetotal = $apiResults['stats']['overdueinvoicesbalance'];
        $isClientAffiliate = $apiResults["stats"]["isAffiliate"];
        $clientemailstatus = $apiResults["email_verified"];

        # Is Email Verified?
        if ($clientemailstatus == true) {
            $clientemailver = 'Verified';
        } else {
            $clientemailver = 'Not Verified';
        }

        # Is Client an Affiliate?
        if ($isClientAffiliate == 1) {
            $clientaffiliate = 'Yes';
        } else {
            $clientaffiliate = 'No';
        }
    }

    # Now let's prepare our code for final output

    if (!is_null($client)) {

        $chatwoot_output =
            "$chatwoot_jscode
			<script>
			  window.addEventListener('chatwoot:ready', function () {
			    window.\$chatwoot.setUser('$ClientChatID', {
			      email: '$clientemail',
			      name: '$clientname',
			      identifier_hash: '$identifier_hash'
			    });
			    window.\$chatwoot.setCustomAttributes({
			      ID: '$ClientID',
			      Phone: '$clientphone',
			      Language: '$clientlang',
			      City: '$clientcity',
			      State: '$clientstate',
			      'Post Code': '$clientpostcode',
			      Country: '$clientcountry',
			      Company: '$clientcompany',
			      'Active Tickets': '$clienttickets',
			      'Credit Balance': '$clientcredit',
			      'Revenue': '$clientrevenue',
			      'Unpaid Invoices': '$clientunpaid',
			      'Account Unpaid': '$clientunpaidtotal',
			      'Overdue Invoices': '$clientoverdue',
			      'Account Overdue': '$clientoverduetotal',
			      'Email Status': '$clientemailver',
			      'Is Affiliate': '$clientaffiliate',
			      'IP Address': '$ip',
			    });
			    window.\$chatwoot.setLabel('$chatwoot_label')
			    window.\$chatwoot.deleteCustomAttribute('Test Attribute')
			    window.chatwootSettings = {
			      position: '$chatwoot_position',
			      locale: '$chatwoot_lang',
			    }
			  });
			</script>";
    } else {
        $chatwoot_output = "
			$chatwoot_jscode
			<script>
			  window.addEventListener('chatwoot:ready', function () {
			    window.\$chatwoot.setLabel('$chatwoot_label')
			    window.chatwootSettings = {
			      position: '$chatwoot_position',
			      locale: '$chatwoot_lang',
			    };
			    window.\$chatwoot.setCustomAttributes({
			      'IP Address': '$ip',
			    });
			  });
			</script>";
    }
    return $chatwoot_output;
}

function hook_chatwoot_logout_output($vars)
{
    $chatwoot_logoutJS = "
		<script>
		  document.addEventListener('readystatechange', event => {
		    window.\$chatwoot.reset()
		  });
		</script>";
    echo $chatwoot_logoutJS;
}

$whmcsver = whmcs_version();

# for WHMCS 8 and later
if ($whmcsver > 7) {
    add_hook('UserLogout', 1, function ($vars) {
        $chatwoot_logoutJS = "<!-- Chatwoot Logout Code -->
            <script>
                document.addEventListener('readystatechange', event => {
                    window.\$chatwoot.reset()
                });
            </script>
            <!-- Chatwoot End Logout Code -->";
        $_SESSION['chatwoot_logoutJS'] = $chatwoot_logoutJS;
    });

    add_hook('ClientAreaPageLogin', 1, function ($vars) {
        if ($_SESSION['chatwoot_logoutJS']) {
            echo $_SESSION['chatwoot_logoutJS'];
            unset($_SESSION['chatwoot_logoutJS']);
        }
    });
}

function whmcs_version()
{
    $whmcsversion = Capsule::table('tblconfiguration')->where('setting', 'Version')->value('value');
    $whmcsver     = substr($whmcsversion, 0, 1);
    return $whmcsver;
}

$LogoutHook = ($whmcsver > 7) ? 'UserLogout' : 'ClientLogout';

add_hook('ClientAreaFooterOutput', 1, 'hook_chatwoot_output');
add_hook($LogoutHook, 1, 'hook_chatwoot_logout_output');
