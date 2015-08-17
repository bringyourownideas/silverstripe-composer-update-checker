<?php
/**
 * Describes an available update to an installed Composer Package
 *
 * Originally from https://github.com/XploreNet/silverstripe-composerupdates
 */
class ComposerUpdate extends DataObject {
	/**
	 * @var array
	 */
	private static $db = array(
		'Name' => 'Varchar(255)',
		'Installed' => 'Varchar(255)',
		'Available' => 'Varchar(255)',
	);
}
