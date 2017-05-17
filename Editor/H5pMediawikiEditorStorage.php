<?php
/**
 * Created by PhpStorm.
 * User: server
 * Date: 5/15/17
 * Time: 7:55 PM
 */
class H5pMediawikiEditorStorage implements H5peditorStorage {
	/**
	 * Load language file(JSON) from database.
	 * This is used to translate the editor fields(title, description etc.)
	 *
	 * @param string $machineName The machine readable name of the library(content type)
	 * @param int $majorVersion part of version number
	 * @param int $minorVersion part of version number
	 * @param string $language Language code
	 *
	 * @return string Translation in JSON format
	 */
	public function getLanguage( $machineName, $majorVersion, $minorVersion, $language ) {

		global $dbr;
		$dbr=wfGetDB(DB_REPLICA);

		return $dbr->select(
			array('hlt'=>'h5p_libraries_languages','hl'=>'h5p_libraries'),
			'hlt.translation',
			array('hl.name'=> $machineName ,'hl.major_version'=> $majorVersion, 'hl.minor_version'=> $minorVersion, 'hlt.language_code'=>$language),
			__METHOD__,
			'',
			array('hl'=> array('JOIN',array('hl.id = hlt.library_id')))
		);
	}

	/**
	 * "Callback" for mark the given file as a permanent file.
	 * Used when saving content that has new uploaded files.
	 *
	 * @param int $fileId
	 */
	public function keepFile( $fileId ) {
		global $dbw;
		$dbw = wfGetDB(DB_MASTER);
		$dbw -> delete(
			'mw_h5p_tmpfiles',
			array('path' => $fileId),
			__METHOD__
		);
	}

	/**
	 * Decides which content types the editor should have.
	 *
	 * Two usecases:
	 * 1. No input, will list all the available content types.
	 * 2. Libraries supported are specified, load additional data and verify
	 * that the content types are available. Used by e.g. the Presentation Tool
	 * Editor that already knows which content types are supported in its
	 * slides.
	 *
	 * @param array $libraries List of library names + version to load info for
	 *
	 * @return array List of all libraries loaded
	 */
	public function getLibraries( $libraries = null ) {
		global $dbr;
		$dbr = wfGetDB(DB_REPLICA);
		//$superUser = RequestContext::getMain()->getUser()->isAllowed('manage_h5p_libraries');
		$superUser = true;


		if ($libraries !== NULL) {
			// Get details for the specified libraries only.
			$librariesWithDetails = array();
			foreach ($libraries as $library) {
				// Look for library

				$details = $dbr -> selectRow(
					'mw_h5p_libraries',
					'title, runnable, restricted, tutorial_url',
					array('name' => $library->name, 'major_version' => $library->majorVersion, 'minor_version' => $library->minorVersion, 'semantics IS NOT NULL'),
						__METHOD__,
						'');

				if ($details) {
					// Library found, add details to list
					$library->tutorialUrl = $details->tutorial_url;
					$library->title = $details->title;
					$library->runnable = $details->runnable;
					$library->restricted = $superUser ? FALSE : ($details->restricted === '1' ? TRUE : FALSE);
					$librariesWithDetails[] = $library;
				}
			}

			// Done, return list with library details
			return $librariesWithDetails;
		}

		// Load all libraries
		$libraries = array();

		$libraries_result = $dbr->select(
			'mw_h5p_libraries',
			array('name','title','majorVersion'=>'major_version','minorVersion'=>'minor_version','tutorialUrl'=>'tutorial_url','restricted'),
			array('runnable = 1','semantics IS NOT NULL'),
			__METHOD__,
			'ORDER BY title'
		);

		foreach ($libraries_result as $library) {
			// Make sure we only display the newest version of a library.
			foreach ($libraries as $key => $existingLibrary) {
				if ($library->name === $existingLibrary->name) {

					// Found library with same name, check versions
					if ( ( $library->majorVersion === $existingLibrary->majorVersion &&
					       $library->minorVersion > $existingLibrary->minorVersion ) ||
					     ( $library->majorVersion > $existingLibrary->majorVersion ) ) {
						// This is a newer version
						$existingLibrary->isOld = TRUE;
					}
					else {
						// This is an older version
						$library->isOld = TRUE;
					}
				}
			}

			// Check to see if content type should be restricted
			$library->restricted = $superUser ? FALSE : ($library->restricted === '1' ? TRUE : FALSE);

			// Add new library
			$libraries[] = $library;
		}
		return $libraries;
	}

	/**
	 * Alter styles and scripts
	 *
	 * @param array $files
	 *  List of files as objects with path and version as properties
	 * @param array $libraries
	 *  List of libraries indexed by machineName with objects as values. The objects
	 *  have majorVersion and minorVersion as properties.
	 */
	public function alterLibraryFiles( &$files, $libraries ) {
		$plugin = H5p_Extension::get_instance();
		$plugin->alter_assets($files, $libraries, 'editor');
	}

	/**
	 * Saves a file or moves it temporarily. This is often necessary in order to
	 * validate and store uploaded or fetched H5Ps.
	 *
	 * @param string $data Uri of data that should be saved as a temporary file
	 * @param boolean $move_file Can be set to TRUE to move the data instead of saving it
	 *
	 * @return bool|object Returns false if saving failed or the path to the file
	 *  if saving succeeded
	 */
	public static function saveFileTemporarily( $data, $move_file ) {
		// Get temporary path
		$plugin = H5p_Extension::get_instance();
		$interface = $plugin->get_h5p_instance('interface');

		$path = $interface->getUploadedH5pPath();

		if ($move_file) {
			// Move so core can validate the file extension.
			rename($data, $path);
		}
		else {
			// Create file from data
			file_put_contents($path, $data);
		}

		return (object) array (
			'dir' => dirname($path),
			'fileName' => basename($path)
		);
	}

	/**
	 * Marks a file for later cleanup, useful when files are not instantly cleaned
	 * up. E.g. for files that are uploaded through the editor.
	 *
	 * @param $file H5peditorFile
	 * @param $content_id
	 */
	public static function markFileForCleanup( $file, $content_id ) {

		global $dbw;
		$dbw =wfGetDB(DB_MASTER);

		$plugin = H5p_Extension::get_instance();
		$path   = $plugin->get_h5p_path();

		if (empty($content_id)) {
			// Should be in editor tmp folder
			$path .= '/editor';
		}
		else {
			// Should be in content folder
			$path .= '/content/' . $content_id;
		}

		// Add file type to path
		$path .= '/' . $file->getType() . 's';

		// Add filename to path
		$path .= '/' . $file->getName();

		// Keep track of temporary files so they can be cleaned up later.
		$dbw->insert(
		'mw_h5p_tmpfiles',
			array('path'=> $path, 'created_at' => time()),
		__METHOD__,
		''
		);

		// TODO: take a look at delete_transient()
		// Clear cached value for dirsize.
		//delete_transient('dirsize_cache');
	}

	/**
	 * Clean up temporary files
	 *
	 * @param string $filePath Path to file or directory
	 */
	public static function removeTemporarilySavedFiles( $filePath ) {
		if (is_dir($filePath)) {
			H5PCore::deleteFileTree($filePath);
		}
		else {
			unlink($filePath);
		}
	}
}