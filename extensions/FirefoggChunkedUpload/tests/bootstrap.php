<?php

/**
 * Set up the MediaWiki environment when running tests with "phpunit" command
 *
 * Warning: this file is not included from global scope!
 * @file
 */

global $wgCommandLineMode, $IP, $optionsWithArgs;
$IP = dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . "/phase3";
define( 'MW_PHPUNIT_TEST', true );

require_once( "$IP/maintenance/commandLine.inc" );

if( !version_compare(PHPUnit_Runner_Version::id(), "3.4.1", ">") ) {
  echo <<<EOF
************************************************************

These tests run best with version PHPUnit 3.4.2 or better.
Earlier versions may show failures because earlier versions
of PHPUnit do not properly implement dependencies.

************************************************************

EOF;
}