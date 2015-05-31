<?php
namespace KayStrobach\Backup\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "KayStrobach.Backup".    *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Utility\Files;

/**
 * @Flow\Scope("singleton")
 */
class BackupCommandController extends CommandController {

	/**
	 * Folder where Backups will be stored
	 * @var string
	 */
	protected $backupFolder = '';

	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * @param string $name
	 */
	public function setBackupFolder($name = NULL) {
		if($name === NULL) {
			$name = date('Ymdhis') . '/';
		}
		if(substr($name, -1, 1) !== '/') {
			$name = $name . '/';
		}
		$this->backupFolder = FLOW_PATH_DATA . 'Backups/' . $name;
	}

	/**
	 * create a backup of your flow installation
	 *
	 * @param bool $database
	 * @param bool $settings
	 * @param bool $composer
	 * @param string $preset
	 * @throws \TYPO3\Flow\Utility\Exception
	 */
	public function createCommand($database = TRUE, $settings = TRUE, $composer = TRUE, $preset = 'default') {
		$this->setBackupFolder();
		Files::createDirectoryRecursively($this->backupFolder);
		if($composer) {
			if(file_exists(FLOW_PATH_ROOT . '/composer.json')) {
				copy(FLOW_PATH_ROOT . '/composer.json', $this->backupFolder . 'composer.json');
			}
			if(file_exists(FLOW_PATH_ROOT . '/composer.lock')) {
				copy(FLOW_PATH_ROOT . '/composer.lock', $this->backupFolder . 'composer.lock');
			}
		}
		if($settings) {
			if(file_exists(FLOW_PATH_CONFIGURATION)) {
				Files::createDirectoryRecursively($this->backupFolder . 'Configuration');
				Files::copyDirectoryRecursively(FLOW_PATH_CONFIGURATION, $this->backupFolder . 'Configuration/');
			}
		}
		if($database) {
			Files::createDirectoryRecursively($this->backupFolder . 'Database');
			$this->emitCreateDbBackup($this->backupFolder . 'Database/', $preset);
		}
		$this->emitBeforeCompression($this->backupFolder, $preset);
	}

	/**
	 * restore a backup of your flow installation
	 *
	 * @param string $name
	 * @param bool $database
	 * @param bool $settings
	 * @param bool $composer
	 */
	public function restoreCommand($name, $database = TRUE, $settings = TRUE, $composer = TRUE) {
		$this->setBackupFolder($name);
		if(!is_dir($this->backupFolder)) {
			$this->outputLine('The folder ' . $this->backupFolder . ' does not exist');
			$this->forward('list');
			return;
		}
		if($database) {
			$this->emitRestoreDbBackup($this->backupFolder . 'Database/');
		}
		if($settings && is_dir($this->backupFolder . 'Configuration')) {
			Files::removeDirectoryRecursively(FLOW_PATH_CONFIGURATION);
			Files::createDirectoryRecursively(FLOW_PATH_CONFIGURATION);
			Files::copyDirectoryRecursively($this->backupFolder . 'Configuration/', FLOW_PATH_CONFIGURATION);
		}
		if($composer) {
			if(file_exists($this->backupFolder . 'composer.json')) {
				copy($this->backupFolder . 'composer.json', FLOW_PATH_ROOT . '/composer.json');
			}
			if(file_exists($this->backupFolder . 'composer.lock')) {
				copy($this->backupFolder . 'composer.lock', FLOW_PATH_ROOT . '/composer.lock');
			}
		}
	}

	/**
	 * lists all stored backups
	 */
	public function listCommand() {
		$this->outputLine('Available Backups');
		$this->setBackupFolder('');
		$folders = scandir($this->backupFolder);
		foreach($folders as $folder) {
			if(($folder !== '.') && ($folder !== '..') && (is_dir($this->backupFolder . $folder))) {
				$this->outputLine(' - ' . $folder);
			}
		}
	}

	/**
	 * @return void
	 * @Flow\Signal
	 */
	protected function emitCreateDbBackup($backupPath, $exportName = 'default') {}

	/**
	 * @return void
	 * @Flow\Signal
	 */
	protected function emitRestoreDbBackup($backupPath, $exportName = 'default') {}

	/**
	 * @return void
	 * @Flow\Signal
	 */
	protected function emitBeforeCompression($backupPath, $exportName = 'default') {}

	/**
	 * @return void
	 * @Flow\Signal
	 */
	protected function emitAfterDecompression($backupPath, $exportName = 'default') {}
}