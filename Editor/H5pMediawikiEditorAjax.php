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
		global $dbr;
		$dbr=wfGetDB(DB_REPLICA);

		$major_versions_sql=$dbr->select(
			array('hl' => 'mw_h5p_libraries'),
			array('hl.name','major_version'=> 'MAX(hl.major_version)'),
			'hl.runnable = 1',
			__METHOD__,
			'GROUP By hl.name'
		);

		$minor_versions_sql=$dbr->select(
			array('hl1' => $major_versions_sql,'hl2'=>'mw_h5p_libraries'),
			array('hl2.name','hl2.major_version','minor_version'=>'MAX(hl2.minor_version)'),
			'',
			__METHOD__,
			array('GROUP BY hl2.name, hl2.major_version'),
			array('hl2'=> array('JOIN',array('hl1.name = hl2.name','hl1.major_version = hl2.major_version')))
		);

		return $dbr->select(
			array('hl3'=> $minor_versions_sql,'hl4'=>'mw_h5p_libraries'),
			array('hl4.id', 'machine_name'=>'hl4.name','hl4.title','hl4.major_version', 'hl4.minor_version','hl4.patch_version','hl4.restricted','hl4.has_icon'),
			'',
			__METHOD__,
			'GROUP BY hl4.name, hl4.major_version, hl4.minor_version',
			array('hl4'=> array('JOIN',array('hl3.name = hl4.name','hl3.major_version = hl4.major_version','hl3.minor_version = hl4.minor_version')))
		);
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
		global $dbr;
		$dbr=wfGetDB(DB_REPLICA);

		// Return info of only the content type with the given machine name
		if ($machineName) {
			return $dbr->fetchRow($dbr->select(
				'mw_h5p_libraries_hub_cache',
				array('id','is_recommended'),
				array('machine_name'=>$machineName),
				'',
				''
			));
		}

		return $dbr->select(
			'mw_h5p_libraries_hub_cache',
			'*',
			'',
			'',
			''
		);
	}

	/**
	 * Gets recently used libraries for the current author
	 *
	 * @return array machine names. The first element in the array is the
	 * most recently used.
	 */
	public function getAuthorsRecentlyUsedLibraries() {

		global $dbr;
		$dbr=wfGetDB(DB_REPLICA);
		$userID = RequestContext::getMain()->getUser()->getId();

		$result=$dbr->query("SELECT library_name, max(created_at) AS max_created_at
         FROM mw_h5p_events
        WHERE type='content' AND sub_type = 'create' AND user_id = $userID 
     GROUP BY library_name
     ORDER BY max_created_at DESC"
			);

		$recently_used = array();

		foreach ($result as $row) {
			$recently_used[] = $row->library_name;
		}

		return $recently_used;
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
		return true;
	}

}