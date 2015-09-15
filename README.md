SilverStripe composer update checker
============================

Checks if your composer dependencies need to be updated.

*So far this module only runs the actual checks and saves the information into a DataObject ("ComposerUpdate") - you need to take care of processing this information somehow! If you are considering to use this the [SilverStripe Maintenance module](https://github.com/FriendsOfSilverStripe/silverstripe-maintenance) might be worth a look.*

Installation
------------

```
composer require spekulatius/silverstripe-composer-update-checker
```

Please run dev/build after the installation of the package.


Future development / Ideas
--------------------------

* Output information via the dev task (plain text, JSON, XML?)
