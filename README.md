# [SilverStripe composer update checker](https://github.com/bringyourownideas/silverstripe-composer-update-checker)

[![Build Status](https://api.travis-ci.org/bringyourownideas/silverstripe-composer-update-checker.svg?branch=master)](https://travis-ci.org/bringyourownideas/silverstripe-composer-update-checker)
[![Latest Stable Version](https://poser.pugx.org/bringyourownideas/silverstripe-composer-update-checker/version.svg)](https://github.com/bringyourownideas/silverstripe-composer-update-checker/releases)
[![Latest Unstable Version](https://poser.pugx.org/bringyourownideas/silverstripe-composer-update-checker/v/unstable.svg)](https://packagist.org/packages/bringyourownideas/silverstripe-composer-update-checker)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/bringyourownideas/silverstripe-composer-update-checker.svg)](https://scrutinizer-ci.com/g/bringyourownideas/silverstripe-composer-update-checker?branch=master)
[![Total Downloads](https://poser.pugx.org/bringyourownideas/silverstripe-composer-update-checker/downloads.svg)](https://packagist.org/packages/bringyourownideas/silverstripe-composer-update-checker)
[![License](https://poser.pugx.org/bringyourownideas/silverstripe-composer-update-checker/license.svg)](https://github.com/bringyourownideas/silverstripe-composer-update-checker/blob/master/license.md)

Checks if any of your Composer dependencies needs to be updated, and tracks the available versions that can be updated
to.

This module only runs the actual checks and saves the information into fields on the Package DataObject. The fields
are also added to the report that the [SilverStripe Maintenance module](https://github.com/bringyourownideas/silverstripe-maintenance)
provides.

### Requirements

* bringyourownideas/silverstripe-maintenance ^1
* composer/composer ^1
* silverstripe/framework ^3.2

### Installation

Run the following command to install this package:

```
composer require bringyourownideas/silverstripe-composer-update-checker ^1
php ./framework/cli-script.php dev/build flush=1
php ./framework/cli-script.php dev/tasks/ProcessJobQueueTask
```

## Contributing

Please see [the contributing guide](CONTRIBUTING.md).
