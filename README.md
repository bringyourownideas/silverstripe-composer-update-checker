SilverStripe composer update checker
============================

Checks if your composer dependencies need to be updated.

*So far this module only runs the actual checks and saves the information into a DataObject - you need to take care of processing this information somehow!* One suggested way is using the silverstripe-maintenance module.

Installation
------------

```
composer require spekulatius/silverstripe-composer-update-checker
```

Future development / Ideas
--------------------------

* Output information via the dev task (plain text, JSON, XML?)
