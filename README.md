# [SilverStripe composer update checker](https://github.com/spekulatius/silverstripe-composer-update-checker) <br />[![Build Status](https://api.travis-ci.org/spekulatius/silverstripe-composer-update-checker.svg?branch=master)](https://travis-ci.org/spekulatius/silverstripe-composer-update-checker) [![Latest Stable Version](https://poser.pugx.org/spekulatius/silverstripe-composer-update-checker/version.svg)](https://github.com/spekulatius/silverstripe-composer-update-checker/releases) [![Latest Unstable Version](https://poser.pugx.org/spekulatius/silverstripe-composer-update-checker/v/unstable.svg)](https://packagist.org/packages/spekulatius/silverstripe-composer-update-checker) [![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/spekulatius/silverstripe-composer-update-checker.svg)](https://scrutinizer-ci.com/g/spekulatius/silverstripe-composer-update-checker?branch=master) [![Total Downloads](https://poser.pugx.org/spekulatius/silverstripe-composer-update-checker/downloads.svg)](https://packagist.org/packages/spekulatius/silverstripe-composer-update-checker) [![License](https://poser.pugx.org/spekulatius/silverstripe-composer-update-checker/license.svg)](https://github.com/spekulatius/silverstripe-composer-update-checker/blob/master/license.md)

Checks if any of your composer dependencies needs to be updated.

*So far this module only runs the actual checks and saves the information into a DataObject ("ComposerUpdate") - you need to take care of processing this information somehow! If you are considering to use this the [SilverStripe Maintenance module](https://github.com/FriendsOfSilverStripe/silverstripe-maintenance) might be worth a look.*

### Requirements

* SilverStripe Framework ^3.0
* SilverStripe QueuedJobs *

### Installation

The following installation commands includes schedulding a queuedjob to populate the data. Run the following command to install this package:

```
composer require spekulatius/silverstripe-composer-update-checker
php ./framework/cli-script.php dev/build
php ./framework/cli-script.php dev/tasks/ProcessJobQueueTask
```

## MISC: [Future ideas/development, issues](https://github.com/spekulatius/silverstripe-composer-update-checker/issues), [Contributing](https://github.com/spekulatius/silverstripe-composer-update-checker/blob/master/CONTRIBUTING.md), [License](https://github.com/spekulatius/silverstripe-composer-update-checker/blob/master/license.md)
