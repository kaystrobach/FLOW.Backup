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
		$tables = $this->processingSettings['database']['tables'];
		$ignoreTables = '';
		$specialCommands = '';
		if(is_array($tables)) {
			foreach($tables as $table => $options) {
				$ignoreTables .= ' --ignore-table=' . $this->settings['dbname'] . '.' . $table . ' ';
				$specialCommands .= 'MYSQL_PWD=' . $password . ' mysqldump --user=' . $username . ' --host=' . $host .
					' -c -e --default-character-set=' . $charset .
					' --single-transaction --skip-set-charset --where="' . $options['mysqldump']['where'] . '" ' . $database . ' ' . $table . ' >> ' . $dumpFilename . ';';
			}
		}

		$command = 'MYSQL_PWD=' . $password . ' mysqldump --user=' . $username . ' --host=' . $host .
			' -c -e --default-character-set=' . $charset .
			' --single-transaction --skip-set-charset ' . $ignoreTables . $database . '> ' . $dumpFilename . ';';

		return $command . $specialCommands;
	}

	protected function buildMysqlImportCommand($username, $password, $database, $host, $charset, $dumpFilename) {
		$command = 'MYSQL_PWD=' . $password . ' mysql --user=' . $username . ' --host=' . $host .
			' --default-character-set=' . $charset . '  ' . $database . ' < ' . $dumpFilename;
		return $command;
	}
}