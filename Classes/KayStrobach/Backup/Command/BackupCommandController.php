<?php
namespace KayStrobach\Backup\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "KayStrobach.Backup".    *
 *                                                                        *
 *                                                                        */

use Doctrine\ORM\Query;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\Doctrine\Service as DoctrineService;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Utility\Files;


/**
 * @Flow\Scope("singleton")
 */
class BackupCommandController extends CommandController {
	/**
	 * @Flow\Inject
	 * @var \Doctrine\Common\Persistence\ObjectManager
	 */
	protected $entityManager;

	/**
	 * Folder where Backups will be stored
	 * @var string
	 */
	protected $backupFolder = '';

	/**
	 * Internal name of the backup
	 * @var string
	 */
	protected $backupName = '';

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
			$name = date('Ymdhis');
		}
		$this->backupName = $name;
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
			Files::createDirectoryRecursively($this->backupFolder . 'Data/Persistent');
			Files::copyDirectoryRecursively(FLOW_PATH_DATA . 'Persistent/', $this->backupFolder . 'Data/Persistent/');
		}
		$this->emitBeforeCompression($this->backupFolder, $preset);

		$this->outputFormatted('Created Backup <b>' . $this->backupName . '</b>');
		$this->outputFormatted('Backup is in Folder: <b>' . $this->backupFolder . '</b>');
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
			Files::removeDirectoryRecursively(FLOW_PATH_DATA . 'Persistent/');
			Files::copyDirectoryRecursively($this->backupFolder . 'Data/Persistent/', FLOW_PATH_DATA . 'Persistent/');
		}
		if($settings && is_dir($this->backupFolder . 'Configuration')) {
			Files::removeDirectoryRecursively(FLOW_PATH_CONFIGURATION);
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
		$folders = $this->getAvailableBackups();
		foreach($folders as $folder) {
			$this->outputLine(' - ' . $folder);
		}
	}

	protected function getAvailableBackups() {
		$foundBackups = array();
		$folders = scandir(FLOW_PATH_DATA . 'Backups/');
		foreach($folders as $folder) {
			if(($folder !== '.') && ($folder !== '..') && (is_dir($this->backupFolder . $folder))) {
				$foundBackups = $folder;
			}
		}
		return $foundBackups;
	}

	/**
	 * gives you an example config for a given table, to help you modifying the output
	 *
	 * @param string $table
	 */
	public function exampleconfigCommand($table) {
		/** @var \Doctrine\DBAL\Driver\Statement $result*/
		/** @var \Doctrine\DBAL\Connection $sqlConnection */
		$sqlConnection = $this->entityManager->getConnection();
		$result = $sqlConnection->executeQuery(
			'
				SELECT DISTINCT COLUMN_NAME
				FROM INFORMATION_SCHEMA.COLUMNS
				WHERE TABLE_NAME = :table AND TABLE_SCHEMA=:database
			',
			array(
				'table' => $table,
				'database' => $sqlConnection->getDatabase()
			)
		);

		$columns = array();
		foreach($result->fetchAll(Query::HYDRATE_ARRAY) as $column) {
			$columns[] = $column['COLUMN_NAME'];
		}

		$this->outputLine('Example configuration for the given table ' . $table . PHP_EOL);

		$output = 'Backup:' . PHP_EOL;
		$output .= '  default:' . PHP_EOL;
		$output .= '    database:' . PHP_EOL;
		$output .= '      tables:' . PHP_EOL;
		$output .= '        ' . $table . ':' . PHP_EOL;
		$output .= '          mysqldump:' . PHP_EOL;
		$output .= '            where: "0=1 UNION SELECT ' . implode(', ', $columns) . ' FROM ' . $table . '"' . PHP_EOL;

		$this->output($output);
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