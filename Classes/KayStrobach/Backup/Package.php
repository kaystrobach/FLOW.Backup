<?php
/**
 * Created by PhpStorm.
 * User: kay
 * Date: 04.02.15
 * Time: 08:42
 */

namespace KayStrobach\Backup;

use Neos\Flow\Package\Package as BasePackage;


class Package extends BasePackage{
	/**
	 * Invokes custom PHP code directly after the package manager has been initialized.
	 *
	 * @param \Neos\Flow\Core\Bootstrap $bootstrap The current bootstrap
	 * @return void
	 */
	public function boot(\Neos\Flow\Core\Bootstrap $bootstrap) {
		$dispatcher = $bootstrap->getSignalSlotDispatcher();
		$dispatcher->connect(
			'KayStrobach\Backup\Command\BackupCommandController', 'createDbBackup',
			'KayStrobach\Backup\Driver\Database\PdoMysqlDriver', 'catchBackupSignal'
		);
		$dispatcher->connect(
			'KayStrobach\Backup\Command\BackupCommandController', 'restoreDbBackup',
			'KayStrobach\Backup\Driver\Database\PdoMysqlDriver', 'catchRestoreSignal'
		);

		//register Configuration Type Menu
		$dispatcher = $bootstrap->getSignalSlotDispatcher();
		$dispatcher->connect('Neos\Flow\Configuration\ConfigurationManager', 'configurationManagerReady',
			function ($configurationManager) {
				$configurationManager->registerConfigurationType('KayStrobach.Backup');
			}
		);

	}
}