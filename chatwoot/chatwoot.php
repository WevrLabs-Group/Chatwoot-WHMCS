<?php

/***************************************************************************
// *                                                                       *
// * Chatwoot WHMCS Addon (v2.0.4).                                        *
// * This addon module enables you to integrate Chatwoot with your WHMCS    *
//   and leverage its powerful features.                                   *
// * Tested on WHMCS Version: v8.12.                                       *
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

if (!Capsule::schema()->hasTable('mod_chatwoot')) {
    try {
        Capsule::schema()->create(
            'mod_chatwoot',
            function ($table) {
                $table->increments('id')->unique();
                $table->string('setting', 100)->unique();
                $table->string('value', 55250)->nullable();
            }
        );
    } catch (\Exception $e) {
        return [
            "status"      => "error",
            "description" => "There was an error activating Chatwoot for WHMCS - Unable to create mod_chatwoot table: {$e->getMessage()}",
        ];
        logActivity("Chatwoot: there was an error activating the addon - Unable to create mod_chatwoot table: {$e->getMessage()}");
    }
}

if (!Capsule::table('mod_chatwoot')->where('setting', 'signing_hash')->first()) {
    Capsule::table('mod_chatwoot')->insert(['setting' => 'signing_hash', 'value' => md5(time())]);
}

function chatwoot_config()
{
    return [
        "name"        => "Chatwoot",
        "description" => "Chatwoot is a customer support tool for instant messaging channels that can help businesses provide exceptional customer support. WHMCS module contributed by: <a href='https://wevrlabs.net/?utm_source=addon_link' target='_blank'>WevrLabs Hosting</a>",
        "version"     => "2.0.4",
        "author"      => "<a href='https://github.com/WevrLabs-Group/Chatwoot-WHMCS' target='_blank'><img src='https://dash.wevrlabs.net/logo.svg' alt='Contributed by WevrLabs Hosting' width='135px' /></a>",
        "fields"      => [
            'chatwoot_enable'           => [
                'FriendlyName' => 'Enable Chatwoot',
                'Type'         => 'yesno',
                'Size'         => '55',
                'Default'      => 'yes',
                'Description'  => 'Check to activate the chat box.',
            ],
            'chatwoot_url'              => [
                'FriendlyName' => 'Chatwoot URL',
                'Type'         => 'text',
                'Rows'         => '',
                'Cols'         => '',
                'Default'      => '',
                'Description'  => 'Enter Chatwoot URL. Example: https://www.chatwoot.com',
            ],
            'chatwoot_token'            => [
                'FriendlyName' => 'Website Widget Token',
                'Type'         => 'text',
                'Rows'         => '',
                'Cols'         => '',
                'Default'      => '',
                'Description'  => 'Enter your website widget Token in this field. You can obtain it from your Chatwoot Dashboard > Inboxes > Website > Settings.<br /> For help, visit <a href="https://www.chatwoot.com/hc/user-guide/articles/1677669989-how-to-install-live-chat-on-a-word_press-website" target="_blank">Chatwoot Docs</a>',
            ],
            'chatwoot_verhash'          => [
                'FriendlyName' => 'Secret Key (Required)',
                'Type'         => 'text',
                'Size'         => '',
                'Default'      => '',
                'Description'  => 'To make sure the conversations between the customers and the support agents are private and to disallow impersonation, you can setup identity validation in Chatwoot. <br />The key used to generate HMAC hash is unique for each webwidget and you can copy it from Inboxes -> Widget Settings -> Configuration -> Identity Validation -> Copy the token shown there<br />To learn more about this, visit <a href="https://www.chatwoot.com/hc/user-guide/articles/1677587479-how-to-enable-identity-validation-in-chatwoot" target="_blank">Chatwoot Docs</a>',
            ],
            'chatwoot_position'         => [
                'FriendlyName' => 'Chat Box Position',
                'Type'         => 'radio',
                'Options'      => 'right,left',
                'Default'      => 'right',
                'Description'  => 'Set your chat box position, whether to be left or right in the page.',
            ],
            'chatwoot_bubble'           => [
                'FriendlyName' => 'Chat Box Bubble',
                'Type'         => 'radio',
                'Options'      => 'Standard,Expanded Bubble',
                'Default'      => 'Standard',
                'Description'  => 'Set the chat box bubble design. Read more at <a href="https://www.chatwoot.com/hc/user-guide/articles/1677587234-how-to-send-additional-user-information-to-chatwoot-using-sdk" target="_blank">Chatwoot Docs</a>.',
            ],
            'chatwoot_launcherTitle'    => [
                'FriendlyName' => 'Bubble Launcher Title',
                'Type'         => 'text',
                'Size'         => '',
                'Default'      => '',
                'Description'  => 'Set the chat box bubble design. Read more at <a href="https://www.chatwoot.com/hc/user-guide/articles/1677587234-how-to-send-additional-user-information-to-chatwoot-using-sdk" target="_blank">Chatwoot Docs</a>.',
            ],
            'chatwoot_dark'             => [
                'FriendlyName' => 'Enable Dark Mode on Widget',
                'Type'         => 'yesno',
                'Size'         => '55',
                'Default'      => 'no',
                'Description'  => 'Check to activate dark mode on the chat box.',
            ],
            'chatwoot_lang'             => [
                'FriendlyName' => 'Dynamic Language',
                'Type'         => 'yesno',
                'Size'         => '55',
                'Default'      => 'no',
                'Description'  => 'check this box to set the chat box language according to current WHMCS clientarea langauge preference.',
            ],
            'chatwoot_setlabel'         => [
                'FriendlyName' => 'Default non-logged In Conversation Label',
                'Type'         => 'text',
                'Size'         => '15',
                'Default'      => '',
                'Description'  => 'Set the default label for conversations for non-logged in visitors.<br /> The Label must already be present in your Chatwoot Dashboard > Labels',
            ],
            'chatwoot_setlabelloggedin' => [
                'FriendlyName' => 'Default Logged In Conversation Label',
                'Type'         => 'text',
                'Size'         => '15',
                'Default'      => '',
                'Description'  => 'Set the default label for conversations for logged in clients.<br /> The Label must already be present in your Chatwoot Dashboard > Labels',
            ],
            'chatwoot_enableonadmin'    => [
                'FriendlyName' => 'Enable on Login as Client',
                'Type'         => 'yesno',
                'Size'         => '55',
                'Default'      => 'no',
                'Description'  => 'check this box to enable the chat box when admin is logged in as client (not recommended, as it may mess up real users\' sessions, so enable this option only for debugging purposes and make sure to logout of the user account to trigger session reset.',
            ],
        ],
    ];
}

function chatwoot_activate()
{

    if (!Capsule::table('mod_chatwoot')->where('setting', 'signing_hash')->first()) {
        try {
            Capsule::table('mod_chatwoot')->insert(['setting' => 'signing_hash', 'value' => md5(time())]);
        } catch (\Exception $e) {
            return ["status" => "error", "description" => "There was an error activating Chatwoot for WHMCS - Unable to create mod_chatwoot table: {$e->getMessage()}"];
        }
    }

    return ['status' => 'success', 'description' => "Chatwoot for WHMCS has been successfully activated! Don't forget to configure the settings below!"];
}
