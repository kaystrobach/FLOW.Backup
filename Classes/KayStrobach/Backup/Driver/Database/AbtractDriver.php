<?php

namespace KayStrobach\Backup\Driver\Database;

use Neos\Flow\Annotations as Flow;

abstract class AbtractDriver {
	/**
	 * @Flow\Inject
	 * @var \Neos\Flow\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * contains the DB related settings e.g. credentials
	 *
	 * @var array
	 */
	protected $settings = array();

	/**
	 * @var array
	 */
	protected $processingSettings = array();

	/**
	 * @var string
	 */
	protected $backupPath = '';

	/**
	 * defines the name on which this driver should react is stored in
	 * Neos.Flow.persistence.backendOptions.driver
	 *
	 * @var string
	 */
	protected $drivername = '';

	/**
	 * inits some of the needed internal variables and triggers the backup if needed
	 *
	 * @param string $path
	 * @param string $exportName
	 */
	public function catchBackupSignal($path, $exportName = 'default') {
		$this->backupPath = $path;
		$this->fetchSettings($exportName);
		if($this->drivername === $this->settings['driver']) {
				$this->backup();
		}
	}

	/**
	 * inits some of the needed internal variables and triggers the restore if needed
	 *
	 * @param string $path
	 * @param string $exportName
	 */
	public function catchRestoreSignal($path, $exportName = 'default') {
		$this->backupPath = $path;
		$this->fetchSettings($exportName);
		if($this->drivername === $this->settings['driver']) {
			$this->restore();
		}
	}

	/**
	 * provide the settings needed to configure the driver
	 *
	 * @param string $exportName
	 */
	protected function fetchSettings($exportName = 'default') {
		$this->settings = $this->configurationManager->getConfiguration(
			\Neos\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
			'Neos.Flow.persistence.backendOptions'
		);
		$this->processingSettings = $this->configurationManager->getConfiguration(
			'KayStrobach.Backup',
			'Backup.' . $exportName
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