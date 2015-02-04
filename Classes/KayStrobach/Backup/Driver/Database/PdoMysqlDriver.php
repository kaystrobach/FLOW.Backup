<?php
/**
 * Created by PhpStorm.
 * User: kay
 * Date: 04.02.15
 * Time: 08:18
 */

namespace KayStrobach\Backup\Driver\Database;


class PdoMysqlDriver extends AbtractDriver {
	/**
	 * defines the name on which this driver should react is stored in
	 * TYPO3.Flow.persistence.backendOptions.driver
	 *
	 * @var string
	 */
	protected $drivername = 'pdo_mysql';

	/**
	 * execute the backup
	 */
	protected function backup() {
		$this->executeCommand(
			$this->buildMySqlDumpCommand(
				$this->settings['user'],
				$this->settings['password'],
				$this->settings['dbname'],
				$this->settings['host'],
				$this->settings['charset'],
				$this->backupPath . 'mysql.sql'
			)
		);
	}

	protected function restore() {
		$this->executeCommand(
			$this->buildMySqlImportCommand(
				$this->settings['user'],
				$this->settings['password'],
				$this->settings['dbname'],
				$this->settings['host'],
				$this->settings['charset'],
				$this->backupPath . 'mysql.sql'
			)
		);
	}

	protected function buildMySqlDumpCommand($username, $password, $database, $host, $charset, $dumpFilename) {
		$command = 'mysqldump --user=' . $username . ' --password=' . $password . ' --host=' . $host .
			' -c -e --default-character-set=' . $charset .
			' --single-transaction --skip-set-charset ' . $database . '> ' . $dumpFilename;
		return $command;
	}

	protected function buildMysqlImportCommand($username, $password, $database, $host, $charset, $dumpFilename) {
		$command = 'mysql --user=' . $username .' --password=' . $password . ' --host=' . $host .
			' --default-character-set=' . $charset . '  ' . $database . ' < ' . $dumpFilename;
		return $command;
	}
}