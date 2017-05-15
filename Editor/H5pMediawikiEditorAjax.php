<?php
/**
 * Created by PhpStorm.
 * User: server
 * Date: 5/15/17
 * Time: 7:54 PM
 */
class H5pMediawikiEditorAjax implements H5PEditorAjaxInterface {
	/**
	 * Gets latest library versions that exists locally
	 *
	 * @return array Latest version of all local libraries
	 */
	public function getLatestLibraryVersions() {
		// TODO: Implement getLatestLibraryVersions() method.
	}

	/**
	 * Get locally stored Content Type Cache. If machine name is provided
	 * it will only get the given content type from the cache
	 *
	 * @param $machineName
	 *
	 * @return array|object|null Returns results from querying the database
	 */
	public function getContentTypeCache( $machineName = null ) {
		// TODO: Implement getContentTypeCache() method.
	}

	/**
	 * Gets recently used libraries for the current author
	 *
	 * @return array machine names. The first element in the array is the
	 * most recently used.
	 */
	public function getAuthorsRecentlyUsedLibraries() {
		// TODO: Implement getAuthorsRecentlyUsedLibraries() method.
	}

	/**
	 * Checks if the provided token is valid for this endpoint
	 *
	 * @param string $token The token that will be validated for.
	 *
	 * @return bool True if successful validation
	 */
	public function validateEditorToken( $token ) {
		// TODO: Implement validateEditorToken() method.
	}

}