Backup for TYPO3.Flow
=====================

This tool allows you to backup the most important files in your FLOW installation.

Currently the Backup includes:

* Configuration Directory
* Database dump (MySQL)
* composer.json and composer.lock

Planned features:
* Copy the Persistent Directory
* Compress the Backup after generation

```
PACKAGE "KAYSTROBACH.BACKUP":
-------------------------------------------------------------------------------
  backup:create                            create a backup of your flow
                                           installation
  backup:restore                           restore a backup of your flow
                                           installation
  backup:list                              lists all stored backups

```

