<?php
/**
 * Created by PhpStorm.
 * User: server
 * Date: 5/8/17
 * Time: 6:24 PM
 */

class SpecialH5pExtension extends SpecialPage{
	function __construct( $name = '', $restriction = '', $listed = true, $function = false, $file = '', $includable = false ) {
		parent::__construct('H5pExtension');
	}

	function execute( $subPage ) {
		$request = $this->getRequest();
		$output = $this -> getOutput();
		$this->setHeaders();

		//$param = $request->getText( 'param');

		# Do stuff

		//TODO: initiate
		include ('Editor/H5pExtensionAdmin.php');


		$wikitext = 'H5P! <br />'.PHP_EOL;
		$wikitext .= '<h5p>1</h5p><br />'.PHP_EOL;
		$wikitext .= 'Testseite ist testierbar<br />'.PHP_EOL;
		$wikitext .= 'UserID: '.$request->getSession()->getUser()->getId();



		$output->addWikiText($wikitext);
		$output->addModules('ext.H5pExtension.editor');
	}
}
