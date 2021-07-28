<?php

/***************************************************************************
// *                                                                       *
// * Chatwoot WHMCS Addon (v2.0.0).                                        *
// * This addon modules enables you integrate Chatwoot with your WHMCS     *
//   and leverage its powerful features.                                   *
// * Tested on WHMCS Version: 7.10.3                                       *
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

use WHMCS\Database\Capsule;

if (!Capsule::schema()->hasTable('mod_chatwoot')) {
    try {
        Capsule::schema()->create(
            'mod_chatwoot', function ($table) {
                $table->increments('id')->unique();
                $table->string('setting', 100)->unique();
                $table->string('value', 55250)->nullable();
            }
        );
    } catch (\Exception $e) {
        return [
            "status"      => "error",
            "description" => "There was an error activating AutoCredit for WHMCS - Unable to create mod_chatwoot table: {$e->getMessage()}",
        ];
        logActivity("AutoCredit: there was an error activating the addon - Unable to create mod_chatwoot table: {$e->getMessage()}");
    }
}

if (!Capsule::table('mod_chatwoot')->where('setting', 'signing_hash')->first()) {
    try {
        Capsule::table('mod_chatwoot')->insert(['setting' => 'signing_hash', 'value' => uniqid(true)]);
    } catch (\Exception $e) {
        return ["status" => "error", "description" => "There was an error activating Chatwoot for WHMCS - Unable to create mod_chatwoot table: {$e->getMessage()}"];
    }
}

if (!Capsule::table('mod_chatwoot')->where('setting', 'signing_hash')->first()) {
    Capsule::table('mod_chatwoot')->insert(['setting' => 'signing_hash', 'value' => uniqid(true)]);
}

function chatwoot_config()
{
    return array(
        "name"        => "Chatwoot",
        "description" => "Chatwoot is a customer support tool for instant messaging channels which can help businesses to provide exceptional customer support. WHMCS module contributed by: <a href='https://wevrlabs.net/?utm_source=addon_link' target='_blank'>WevrLabs Hosting</a>",
        "version"     => "1.0",
        "author"      => "<a href='https://github.com/WevrLabs-Group/Chatwoot-WHMCS' target='_blank'><img alt='' width='100px' src='' /><br />contributed by WevrLabs Hosting</a>",
        "fields"      => array(
            'chatwoot_enable'           => [
                'FriendlyName' => 'Enable Chatwoot',
                'Type'         => 'yesno',
                'Size'         => '55',
                'Default'      => 'yes',
                'Description'  => 'Check to activate the chat box.',
            ],
            'chatwoot_jscode'           => [
                'FriendlyName' => 'Website JS Code',
                'Type'         => 'textarea',
                'Rows'         => '15',
                'Cols'         => '100',
                'Default'      => '',
                'Description'  => 'Paste your website widget JS code in this field. You can obtain it from your Chatwoot Dashboard > Inboxes > Website > Settings.<br /> For help, visit <a href="https://github.com/WevrLabs-Group/Chatwoot-WHMCS" target="_blank">https://github.com/WevrLabs-Group/Chatwoot-WHMCS</a>',
            ],
            'chatwoot_verhash'          => [
                'FriendlyName' => 'Verification Hash (Required)',
                'Type'         => 'text',
                'Size'         => '',
                'Default'      => '',
                'Description'  => 'To make sure the conversations between the customers and the support agents are private and to disallow impersonation, you can setup identity validation in Chatwoot. <br />The key used to generate HMAC hash is unique for each webwidget and you can copy it from Inboxes -> Website Settings -> Configuration -> Identity Validation -> Copy the token shown there<br />To learn more about this, visit <a href="https://www.chatwoot.com/docs/product/channels/live-chat/sdk/identity-validation" target="_blank">Chatwoot Docs</a>',
            ],
            'chatwoot_position'         => [
                'FriendlyName' => 'Chat Box Position',
                'Type'         => 'radio',
                'Options'      => 'right,left',
                'Default'      => 'right',
                'Description'  => 'Set your chat box position, whether to be left or right in the page.',
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
        ),
    );

    return $configarray;
}

function chatwoot_activate()
{

    if (!Capsule::table('mod_chatwoot')->where('setting', 'signing_hash')->first()) {
        try {
            Capsule::table('mod_chatwoot')->insert(['setting' => 'signing_hash', 'value' => uniqid(true)]);
        } catch (\Exception $e) {
            return ["status" => "error", "description" => "There was an error activating Chatwoot for WHMCS - Unable to create mod_chatwoot table: {$e->getMessage()}"];
        }
    }

    return ['status' => 'success', 'description' => "Chatwoot for WHMCS has been successfully activated! Don't forget to configure the settings below!"];

}
