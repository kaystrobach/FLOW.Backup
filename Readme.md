Backup for Neos.Flow
=====================

This tool allows you to backup the most important files in your FLOW installation.

Currently the Backup includes:

* Create a Database dump (MySQL)
* Copy the composer.json and composer.lock files
* Copy the Data/Configuration Directory
* Copy the Data/Persistent Directory

Additionally it's possible to keep some of the backup and restore the one you like directly from the commandline.

Planned features:

* Compress the Backup after generation

```
PACKAGE "KAYSTROBACH.BACKUP":
-------------------------------------------------------------------------------
  backup:create                            create a backup of your flow
                                           installation
  backup:restore                           restore a backup of your flow
                                           installation
  backup:list                              lists all stored backups
  backup:exampleconfig                     gives you an example config for a
                                           given table, to help you modifying
                                           the output
```

Additionally you can now create presets for exports, this presets allow you f.e.
to anonymize date from the database during export.

This allows you to export prodution data to a development system very easy,
without exposing sensitive production data to all of your developers.
