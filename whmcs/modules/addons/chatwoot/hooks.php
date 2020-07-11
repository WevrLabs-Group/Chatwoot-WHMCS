
<?php

if(!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

function hook_chatwoot_footer_output($vars) {
    
    $chatwoot_jscode = Capsule::table('tbladdonmodules')->where('module', 'chatwoot')->where('setting', 'chatwoot_jscode')->value('value');
    $chatwoot_position = Capsule::table('tbladdonmodules')->where('module', 'chatwoot')->where('setting', 'chatwoot_position')->value('value');
    $chatwoot_setlabel = Capsule::table('tbladdonmodules')->where('module', 'chatwoot')->where('setting', 'chatwoot_setlabel')->value('value');
    $chatwoot_setlabelloggedin = Capsule::table('tbladdonmodules')->where('module', 'chatwoot')->where('setting', 'chatwoot_setlabelloggedin')->value('value');
    $isenabled =  Capsule::table('tbladdonmodules')->select('value')-> WHERE('module', '=' , 'chatwoot')->WHERE('setting' , '=', 'chatwoot_enable')->WHERE('value' , 'on')->count();   
	
	// Disable or Enable Chatwoot
	if (empty($isenabled)) {
        return;
    }
    
    if(!$chatwoot_jscode) {
        return;
    }
	
    
	// Fetch labels
    $client = Menu::context('client');
    if (!is_null($client)){
        $chatwoot_label = $chatwoot_setlabelloggedin;
        } else {
        $chatwoot_label = $chatwoot_setlabel;
    }

    // Get client ID
    if ($vars['clientsdetails']['id']) {
        $hmac = hash_hmac("sha256", $id, "nQ1ayoG5bu580LZkSxMJiO2");
        $clientid = $hmac;
        
    }

    // Get client email
    if ($vars['clientsdetails']['email']) {
        $clientemail = $vars['clientsdetails']['email'];
    }

    // Get First and Last name
    if ($vars['clientsdetails']['firstname']) {
        $clientname = $vars['clientsdetails']['firstname'] . " " . $vars['clientsdetails']['lastname'];
    }

    // Fetch client avatar if any
    $rating = (isset($params['rating']) ? $params['rating'] : 'G');
    $default = (isset($params['default']) ? $params['default'] : 'mp');
    $size = (isset($params['size']) ? $params['size'] : '150'); 
    $gravatarurl = "https://www.gravatar.com/avatar/".md5($clientemail) . "?r=".$rating . "&d=".$default . "&s=".$size; 

    
    $chatwoot_output = "$chatwoot_jscode
                        <script>
                            window.onload = (event) => {
                                window.\$chatwoot.setUser('$clientid', {
                                    email: '$clientemail',
                                    name: '$clientname',
                                    avatar_url: '$gravatarurl',
                                })
                                window.\$chatwoot.setLabel('$chatwoot_label')
                            }
                            window.chatwootSettings = {
                                    position: '$chatwoot_position',
                                    locale: '$chatwoot_lang',
                            }
                        </script>
                        ";

     echo $chatwoot_output;
}


function hook_chatwoot_logout_footer_output($vars) {
    $chatwoot_logoutJS = "<script>
                            document.addEventListener('readystatechange', event => {
                                window.\$chatwoot.reset()
                            });
                          </script>
                          ";

     echo $chatwoot_logoutJS;
}   

add_hook('ClientAreaFooterOutput', 1, 'hook_chatwoot_footer_output');

add_hook('ClientLogout', 1, 'hook_chatwoot_logout_footer_output');
