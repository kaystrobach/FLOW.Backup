<?php

namespace KayStrobach\Backup\Driver\Database;

use TYPO3\Flow\Annotations as Flow;

abstract class AbtractDriver {
	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * contains the DB related settings e.g. credentials
	 *
	 * @var array
	 */
	protected $settings = array();

	/**
	 * @var string
	 */
	protected $backupPath = '';

	/**
	 * defines the name on which this driver should react is stored in
	 * TYPO3.Flow.persistence.backendOptions.driver
	 *
	 * @var string
	 */
	protected $drivername = '';

	/**
	 * inits some of the needed internal variables and triggers the backup if needed
	 */
	public function catchBackupSignal($path) {
		$this->backupPath = $path;
		$this->fetchSettings();
		if($this->drivername === $this->settings['driver']) {
				$this->backup();
		}
	}

	/**
	 * inits some of the needed internal variables and triggers the restore if needed
	 */
	public function catchRestoreSignal($path) {
		$this->backupPath = $path;
		$this->fetchSettings();
		if($this->drivername === $this->settings['driver']) {
			$this->restore();
		}
	}

	/**
	 * provide the settings needed to configure the driver
	 */
	protected function fetchSettings() {
		$this->settings = $this->configurationManager->getConfiguration(
			\TYPO3\FLOW\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
			'TYPO3.Flow.persistence.backendOptions'
		);
	}

	/**
	 * execute a shell command
	 * @param $command
	 * @return array
	 */
	protected function executeCommand($command) {
		$returnedOutput = '';
		$fp = popen($command, 'r');
		while (($line = fgets($fp)) !== FALSE) {
			$returnedOutput .= $line;
		}
		$exitCode = pclose($fp);
		return array($exitCode, trim($returnedOutput));
	}

	/**
	 * execute the backup
	 */
	protected abstract function backup();

	/**
	 * execute the restore
	 */
	protected abstract function restore();
}