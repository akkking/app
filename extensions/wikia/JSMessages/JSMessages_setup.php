<?php

/**
 * Adds support for MW messages in JS code
 *
 * Provides a way to register and use packages of messages in JavaScript via $.msg() function
 *
 * @see https://internal.wikia-inc.com/wiki/JSMessages
 * @author macbre
 */

$wgExtensionCredits['other'][] = array(
	'name' => 'JSMessages',
	'version' => '1.1',
	'author' => 'Maciej Brencz',
	'description' => 'Adds support for MW messages in JS code',
);

$dir = dirname(__FILE__);

// classes
$wgAutoloadClasses['JSMessages'] =  $dir . '/JSMessages.class.php';
$wgAutoloadClasses['JSMessagesHelper'] =  $dir . '/JSMessagesHelper.class.php';
$wgAutoloadClasses['JSMessagesController'] =  $dir . '/JSMessagesController.class.php';

// hooks
$wgHooks['WikiaSkinTopScripts'][] = 'JSMessages::onWikiaSkinTopScripts';
$wgHooks['MessageCacheReplace'][] = 'JSMessagesHelper::onMessageCacheReplace';

