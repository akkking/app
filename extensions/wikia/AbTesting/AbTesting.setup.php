<?php
/**
 * @author Sean Colombo
 * @author Piotr Bablok
 * @author Władysław Bodzek
 *
 * Extension which helps with running A/B tests or Split Tests (can actually be a/b/c/d/etc. as needed).
 *
 * This is the new system which is powered by our data warehouse.
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	echo "This file is part of MediaWiki, it is not a valid entry point.\n";
	exit( 1 );
}

$dir = dirname( __FILE__ );

/**
 * info
 */
$wgExtensionCredits['other'][] =
	array(
		'name' => 'A/B Testing',
		'author' => array(
			'[http://www.seancolombo.com Sean Colombo]',
			'Władysław Bodzek',
			'Kyle Florence',
			'Piotr Bablok'
		),
		'descriptionmsg' => 'abtesting-desc',
		'version' => '1.0',
	);

/**
 * classes
 */
$wgAutoloadClasses['AbTesting'] = "{$dir}/AbTesting.class.php";
$wgAutoloadClasses['AbExperiment'] = "{$dir}/AbTesting.class.php";
$wgAutoloadClasses['AbTestingData'] = "{$dir}/AbTestingData.class.php";
$wgAutoloadClasses['ResourceLoaderAbTestingModule'] = "{$dir}/ResourceLoaderAbTestingModule.class.php";
$wgAutoloadClasses['SpecialAbTestingController'] = "{$dir}/SpecialAbTestingController.class.php";
$wgAutoloadClasses['SpecialAbTesting2Controller'] = "{$dir}/SpecialAbTesting2Controller.class.php";
$wgAutoloadClasses['AbTestingController'] = "{$dir}/AbTestingController.class.php";
$wgAutoloadClasses['AbTestingHooks'] = "{$dir}/AbTestingHooks.class.php";
$wgAutoloadClasses['AbTestingConfig'] = "{$dir}/AbTestingConfig.class.php";
$wgAutoloadClasses['AbTest'] = "{$dir}/AbTest.class.php";

/**
 * message files
 */
$wgExtensionMessagesFiles['AbTesting'] = "{$dir}/AbTesting.i18n.php";

// Embed the experiment/treatment config in the head scripts.
$wgHooks['WikiaSkinTopScripts'][] =  'AbTestingHooks::onWikiaSkinTopScripts';
$wgHooks['WikiaMobileAssetsPackages'][] = 'AbTestingHooks::onWikiaMobileAssetsPackages';
// Add js code in Oasis
$wgHooks['OasisSkinAssetGroupsBlocking'][] = 'AbTestingHooks::onOasisSkinAssetGroupsBlocking';

// Register Resource Loader module
$wgResourceModules['wikia.ext.abtesting'] = array(
	'class' => 'ResourceLoaderAbTestingModule',
);

$wgResourceModules['wikia.ext.abtesting.edit.styles'] = array(
	'styles' => array(
		'extensions/wikia/AbTesting/css/AbTestEditor.scss',
		'resources/jquery.ui/themes/default/jquery.ui.core.css',
		'resources/jquery.ui/themes/default/jquery.ui.datepicker.css',
		'resources/jquery.ui/themes/default/jquery.ui.slider.css',
		'resources/jquery.ui/themes/default/jquery.ui.theme.css',
		'resources/wikia/libraries/jquery-ui/themes/default/jquery.ui.timepicker.css',
	),
);

$wgResourceModules['wikia.ext.abtesting.edit'] = array(
	'scripts' => array(
		'extensions/wikia/AbTesting/js/AbTestEditor.js',
		'resources/jquery.ui/jquery.ui.core.js',
		'resources/jquery.ui/jquery.ui.widget.js',
		'resources/jquery.ui/jquery.ui.datepicker.js',
		'resources/jquery.ui/jquery.ui.mouse.js',
		'resources/jquery.ui/jquery.ui.slider.js',
		'resources/wikia/libraries/jquery-ui/jquery.ui.timepicker.js',
	),
	'messages' => array(
		'abtesting-add-experiment-title',
		'abtesting-edit-experiment-title'
	)
);


$wgResourceModules['wikia.ext.abtesting.edit2'] = array(
	 'scripts' => array(
		 'extensions/wikia/AbTesting/js/AbTestEditor2.js',
		 'extensions/wikia/AbTesting/js/ba-linkify.js',
	 ),
);

$wgResourceModules['wikia.ext.abtesting.edit2.styles'] = array(
	 'styles' => array(
		 'extensions/wikia/AbTesting/css/AbTestEditor2.scss',
	 ),
);


$wgSpecialPages['AbTesting'] = 'SpecialAbTestingController';
$wgSpecialPages['AbTesting2'] = 'SpecialAbTesting2Controller';


/*
 * permissions setup
 */
$wgGroupPermissions['*']['abtestpanel'] = false;
$wgGroupPermissions['staff']['abtestpanel'] = true;
