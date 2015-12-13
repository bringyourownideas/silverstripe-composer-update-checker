SilverStripe composer update checker
============================

[![Build Status](https://api.travis-ci.org/spekulatius/silverstripe-composer-update-checker.svg?branch=master)](https://travis-ci.org/spekulatius/silverstripe-composer-update-checker)
[![Latest Stable Version](https://poser.pugx.org/spekulatius/silverstripe-composer-update-checker/version.svg)](http://www.silverstripe.org/stable-download/)
[![Latest Unstable Version](https://poser.pugx.org/spekulatius/silverstripe-composer-update-checker/v/unstable.svg)](https://packagist.org/packages/spekulatius/silverstripe-composer-update-checker)
[![Total Downloads](https://poser.pugx.org/spekulatius/silverstripe-composer-update-checker/downloads.svg)](https://packagist.org/packages/spekulatius/silverstripe-composer-update-checker)
[![License](https://poser.pugx.org/spekulatius/silverstripe-composer-update-checker/license.svg)](https://github.com/spekulatius/silverstripe-composer-update-checker#license)

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
