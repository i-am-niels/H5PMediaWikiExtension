<?php
/**
 * Created by PhpStorm.
 * User: iamNiels
 * Date: 3/13/17
 * Time: 7:57 PM
 */


if( !defined( 'MEDIAWIKI' ) ) {
    echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
    die( 1 );
}

ini_set('error_reporting', E_ALL);
ini_set('error_log','/tmp/error_log.txt');

class H5pExtension
{

    // Register any render callbacks with the parser
    public static function onParserSetup(Parser $parser) //pass parser by reference -> not working if you take the code directly from the mediawiki extensions guide
    {
        // When the parser sees the <sample> tag, it executes renderTagSample (see below)
        $parser->setHook('h5p', 'H5P_Extension::renderH5p');
    }

}
