<?php

/***************************************************************************
// *                                                                       *
// * Chatwoot WHMCS Addon                                                  *
// * This addon module enables you to integrate Chatwoot with your WHMCS   *
//   and leverage its powerful features.                                   *
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

use WHMCS\Authentication\CurrentUser;
use WHMCS\Database\Capsule;

function hook_chatwoot_output($vars)
{

    $isenabled              = Capsule::table('tbladdonmodules')->select('value')->where('module', '=', 'chatwoot')->where('setting', '=', 'chatwoot_enable')->where('value', 'on')->count();
    $chatwoot_url           = Capsule::table('tbladdonmodules')->where('module', 'chatwoot')->where('setting', 'chatwoot_url')->value('value');
    $chatwoot_token         = Capsule::table('tbladdonmodules')->where('module', 'chatwoot')->where('setting', 'chatwoot_token')->value('value');
    $verification_hash      = Capsule::table('tbladdonmodules')->where('module', 'chatwoot')->where('setting', 'chatwoot_verhash')->value('value');
    $chatwoot_position      = Capsule::table('tbladdonmodules')->where('module', 'chatwoot')->where('setting', 'chatwoot_position')->value('value');
    $chatwoot_bubble        = Capsule::table('tbladdonmodules')->where('module', 'chatwoot')->where('setting', 'chatwoot_bubble')->value('value');
    $chatwoot_launcherTitle = Capsule::table('tbladdonmodules')->where('module', 'chatwoot')->where('setting', 'chatwoot_launcherTitle')->value('value');
    $chatwoot_dark          = Capsule::table('tbladdonmodules')->where('module', 'chatwoot')->where('setting', 'chatwoot_dark')->value('value');
    $chatwoot_lang_setting  = Capsule::table('tbladdonmodules')->where('module', 'chatwoot')->where('setting', 'chatwoot_lang')->value('value');

    $chatwoot_setlabel         = Capsule::table('tbladdonmodules')->where('module', 'chatwoot')->where('setting', 'chatwoot_setlabel')->value('value');
    $chatwoot_setlabelloggedin = Capsule::table('tbladdonmodules')->where('module', 'chatwoot')->where('setting', 'chatwoot_setlabelloggedin')->value('value');

    $chatwoot_admin = Capsule::table('tbladdonmodules')->where('module', 'chatwoot')->where('setting', 'chatwoot_enableonadmin')->value('value');

    # ignore if admin
    if (empty($chatwoot_admin) && isset($_SESSION['adminid'])) {
        return;
    }

    # Disable or Enable Chatwoot
    if (empty($isenabled)) {
        return;
    }

    # bubble design
    if ($chatwoot_bubble == 'Standard') {
        $chatwoot_bubble = 'standard';
    } elseif ($chatwoot_bubble == 'Expanded Bubble') {
        $chatwoot_bubble = 'expanded_bubble';
    }

    # dark mode
    if ($chatwoot_dark == 'on') {
        $chatwoot_dark = 'auto';
    }

    # widget lang
    if ($chatwoot_lang_setting) {
        $chatwoot_lang = cw_langCode(ucfirst($vars['language']));
    }

    # user basic info
    $currentUser  = new CurrentUser;
    $client       = $currentUser->client();
    $user         = $currentUser->user();
    $currentpage  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

    # Fetch labels
    if (!is_null($user)) {
        $chatwoot_label = $chatwoot_setlabelloggedin;
    } else {
        $chatwoot_label = $chatwoot_setlabel;
    }

    # Get client ID and set contact ID
    if ($user && $client && $user->isOwner($client)) {
        $ClientID = $client->id;
    } elseif ($user) {
        //$ownedClients = $user->ownedClients()->all();
        //$ClientID     = $ownedClients[0]['id'];
        $ClientID = $client->id;
    }

    if (!is_null($user)) {
        $clientChatID    = hash_hmac("sha256", $ClientID, $verification_hash);
        $identifier_hash = hash_hmac("sha256", $clientChatID, $verification_hash);
    }

    # build contact info
    if (!is_null($user)) {

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
        $clienttickets          = $apiResults['stats']['numactivetickets'];
        $clientcredit           = $apiResults['stats']['creditbalance'];
        $clientrevenue          = $apiResults['stats']['income'];
        $clientunpaid           = $apiResults['stats']['numunpaidinvoices'];
        $clientunpaidtotal      = $apiResults['stats']['unpaidinvoicesamount'];
        $clientoverdue          = $apiResults['stats']['numoverdueinvoices'];
        $clientoverduetotal     = $apiResults['stats']['overdueinvoicesbalance'];
        $isClientAffiliate      = $apiResults["stats"]["isAffiliate"];
        $clientemailstatus      = $apiResults["email_verified"];

        # Is Email Verified?
        if ($clientemailstatus) {
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

    # prepare widget code
    $chatwoot_jscode = "
            <script>
  window.chatwootSettings = {'position':'$chatwoot_position','type':'$chatwoot_bubble','launcherTitle':'$chatwoot_launcherTitle','darkMode': '$chatwoot_dark'};
  (function(d,t) {
    var BASE_URL='$chatwoot_url';
    var g=d.createElement(t),s=d.getElementsByTagName(t)[0];
    g.src=BASE_URL+'/packs/js/sdk.js';
    g.defer = true;
    g.async = true;
    s.parentNode.insertBefore(g,s);
    g.onload=function(){
      window.chatwootSDK.run({
        websiteToken: '$chatwoot_token',
        baseUrl: BASE_URL
      })
    }
  })(document,'script');
</script>
";

    # Now let's set the final output

    if (!is_null($user)) {

        $chatwoot_output =
            "$chatwoot_jscode
            <script>
              window.addEventListener('chatwoot:ready', function () {
                window.\$chatwoot.setUser('$clientChatID', {
                  email: '$clientemail',
                  name: '$clientname',
                  identifier_hash: '$identifier_hash',
                  company_name: '$clientcompany',
                });
                window.\$chatwoot.setCustomAttributes({
                  'ID': '$ClientID',
                  'Phone': '$clientphone',
                  'Language': '$clientlang',
                  'City': '$clientcity',
                  'State': '$clientstate',
                  'Post Code': '$clientpostcode',
                  'Country': '$clientcountry',
                  'Active Tickets': '$clienttickets',
                  'Credit Balance': '$clientcredit',
                  'Revenue': '$clientrevenue',
                  'Unpaid Invoices': '$clientunpaid',
                  'Account Unpaid': '$clientunpaidtotal',
                  'Overdue Invoices': '$clientoverdue',
                  'Account Overdue': '$clientoverduetotal',
                  'Email Status': '$clientemailver',
                  'Is Affiliate': '$clientaffiliate',
                  'Current Page': '$currentpage',
                });
                window.\$chatwoot.deleteCustomAttribute('Test Attribute');
                window.\$chatwoot.setLabel('$chatwoot_setlabelloggedin');
                window.\$chatwoot.removeLabel('$chatwoot_setlabel');
                window.\$chatwoot.setLocale('$chatwoot_lang');
              });
            </script>";
    } else {
        $chatwoot_output = "
            $chatwoot_jscode
            <script>
              window.addEventListener('chatwoot:ready', function () {
                window.\$chatwoot.setLabel('$chatwoot_label');
                window.\$chatwoot.setLocale('$chatwoot_lang');
                window.\$chatwoot.setCustomAttributes({
                  'Current Page': '$currentpage',
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

$whmcsver = cwoot_whmcs_version();

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

$whmcsver   = cwoot_whmcs_version();
$LogoutHook = ($whmcsver > 7) ? 'UserLogout' : 'ClientLogout';

function ViewClientSwitchAccount($vars) {

    $url = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

    if (str_contains($url, '/user/accounts') || str_contains($url, '/login')) {

        $output .= <<<HTML
<!-- Chatwoot Logout Code -->
<script>
    document.addEventListener('readystatechange', event => {
        window.\$chatwoot.reset()
    });
</script>
<!-- Chatwoot End Logout Code -->
HTML;

        return $output;
    }
}

add_hook("ClientAreaHeaderOutput", 1, "ViewClientSwitchAccount");
add_hook('ClientAreaFooterOutput', 1, 'hook_chatwoot_output');
add_hook($LogoutHook, 1, 'hook_chatwoot_logout_output');

# meta
function cwoot_whmcs_version() {
    $whmcsversion = Capsule::table('tblconfiguration')->where('setting', 'Version')->value('value');
    return substr($whmcsversion, 0, 1);
}

# to deal with langs
function cw_langCode($name)
{
    $languageCodes = array(
        "aa" => "Afar",
        "ab" => "Abkhazian",
        "ae" => "Avestan",
        "af" => "Afrikaans",
        "ak" => "Akan",
        "am" => "Amharic",
        "an" => "Aragonese",
        "ar" => "Arabic",
        "as" => "Assamese",
        "av" => "Avaric",
        "ay" => "Aymara",
        "az" => "Azerbaijani",
        "ba" => "Bashkir",
        "be" => "Belarusian",
        "bg" => "Bulgarian",
        "bh" => "Bihari",
        "bi" => "Bislama",
        "bm" => "Bambara",
        "bn" => "Bengali",
        "bo" => "Tibetan",
        "br" => "Breton",
        "bs" => "Bosnian",
        "ca" => "Catalan",
        "ce" => "Chechen",
        "ch" => "Chamorro",
        "co" => "Corsican",
        "cr" => "Cree",
        "cs" => "Czech",
        "cu" => "Church Slavic",
        "cv" => "Chuvash",
        "cy" => "Welsh",
        "da" => "Danish",
        "de" => "German",
        "dv" => "Divehi",
        "dz" => "Dzongkha",
        "ee" => "Ewe",
        "el" => "Greek",
        "en" => "English",
        "eo" => "Esperanto",
        "es" => "Spanish",
        "et" => "Estonian",
        "eu" => "Basque",
        "fa" => "Persian",
        "ff" => "Fulah",
        "fi" => "Finnish",
        "fj" => "Fijian",
        "fo" => "Faroese",
        "fr" => "French",
        "fy" => "Western Frisian",
        "ga" => "Irish",
        "gd" => "Scottish Gaelic",
        "gl" => "Galician",
        "gn" => "Guarani",
        "gu" => "Gujarati",
        "gv" => "Manx",
        "ha" => "Hausa",
        "he" => "Hebrew",
        "hi" => "Hindi",
        "ho" => "Hiri Motu",
        "hr" => "Croatian",
        "ht" => "Haitian",
        "hu" => "Hungarian",
        "hy" => "Armenian",
        "hz" => "Herero",
        "ia" => "Interlingua (International Auxiliary Language Association)",
        "id" => "Indonesian",
        "ie" => "Interlingue",
        "ig" => "Igbo",
        "ii" => "Sichuan Yi",
        "ik" => "Inupiaq",
        "io" => "Ido",
        "is" => "Icelandic",
        "it" => "Italian",
        "iu" => "Inuktitut",
        "ja" => "Japanese",
        "jv" => "Javanese",
        "ka" => "Georgian",
        "kg" => "Kongo",
        "ki" => "Kikuyu",
        "kj" => "Kwanyama",
        "kk" => "Kazakh",
        "kl" => "Kalaallisut",
        "km" => "Khmer",
        "kn" => "Kannada",
        "ko" => "Korean",
        "kr" => "Kanuri",
        "ks" => "Kashmiri",
        "ku" => "Kurdish",
        "kv" => "Komi",
        "kw" => "Cornish",
        "ky" => "Kirghiz",
        "la" => "Latin",
        "lb" => "Luxembourgish",
        "lg" => "Ganda",
        "li" => "Limburgish",
        "ln" => "Lingala",
        "lo" => "Lao",
        "lt" => "Lithuanian",
        "lu" => "Luba-Katanga",
        "lv" => "Latvian",
        "mg" => "Malagasy",
        "mh" => "Marshallese",
        "mi" => "Maori",
        "mk" => "Macedonian",
        "ml" => "Malayalam",
        "mn" => "Mongolian",
        "mr" => "Marathi",
        "ms" => "Malay",
        "mt" => "Maltese",
        "my" => "Burmese",
        "na" => "Nauru",
        "nb" => "Norwegian Bokmal",
        "nd" => "North Ndebele",
        "ne" => "Nepali",
        "ng" => "Ndonga",
        "nl" => "Dutch",
        "nn" => "Norwegian Nynorsk",
        "no" => "Norwegian",
        "nr" => "South Ndebele",
        "nv" => "Navajo",
        "ny" => "Chichewa",
        "oc" => "Occitan",
        "oj" => "Ojibwa",
        "om" => "Oromo",
        "or" => "Oriya",
        "os" => "Ossetian",
        "pa" => "Panjabi",
        "pi" => "Pali",
        "pl" => "Polish",
        "ps" => "Pashto",
        "pt" => "Portuguese",
        "qu" => "Quechua",
        "rm" => "Raeto-Romance",
        "rn" => "Kirundi",
        "ro" => "Romanian",
        "ru" => "Russian",
        "rw" => "Kinyarwanda",
        "sa" => "Sanskrit",
        "sc" => "Sardinian",
        "sd" => "Sindhi",
        "se" => "Northern Sami",
        "sg" => "Sango",
        "si" => "Sinhala",
        "sk" => "Slovak",
        "sl" => "Slovenian",
        "sm" => "Samoan",
        "sn" => "Shona",
        "so" => "Somali",
        "sq" => "Albanian",
        "sr" => "Serbian",
        "ss" => "Swati",
        "st" => "Southern Sotho",
        "su" => "Sundanese",
        "sv" => "Swedish",
        "sw" => "Swahili",
        "ta" => "Tamil",
        "te" => "Telugu",
        "tg" => "Tajik",
        "th" => "Thai",
        "ti" => "Tigrinya",
        "tk" => "Turkmen",
        "tl" => "Tagalog",
        "tn" => "Tswana",
        "to" => "Tonga",
        "tr" => "Turkish",
        "ts" => "Tsonga",
        "tt" => "Tatar",
        "tw" => "Twi",
        "ty" => "Tahitian",
        "ug" => "Uighur",
        "uk" => "Ukrainian",
        "ur" => "Urdu",
        "uz" => "Uzbek",
        "ve" => "Venda",
        "vi" => "Vietnamese",
        "vo" => "Volapuk",
        "wa" => "Walloon",
        "wo" => "Wolof",
        "xh" => "Xhosa",
        "yi" => "Yiddish",
        "yo" => "Yoruba",
        "za" => "Zhuang",
        "zh" => "Chinese",
        "zu" => "Zulu",
    );
    return array_search($name, $languageCodes);
}
