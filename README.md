# SilverStripe Composer update checker

[![Build Status](https://api.travis-ci.org/bringyourownideas/silverstripe-composer-update-checker.svg?branch=master)](https://travis-ci.org/bringyourownideas/silverstripe-composer-update-checker)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/bringyourownideas/silverstripe-composer-update-checker.svg)](https://scrutinizer-ci.com/g/bringyourownideas/silverstripe-composer-update-checker?branch=master)
[![codecov](https://codecov.io/gh/bringyourownideas/silverstripe-composer-update-checker/branch/master/graph/badge.svg)](https://codecov.io/gh/bringyourownideas/silverstripe-composer-update-checker)
[![SilverStripe supported module](https://img.shields.io/badge/silverstripe-supported-0071C4.svg)](https://www.silverstripe.org/software/addons/silverstripe-commercially-supported-module-list/)
[![Latest Stable Version](https://poser.pugx.org/bringyourownideas/silverstripe-composer-update-checker/version.svg)](https://github.com/bringyourownideas/silverstripe-composer-update-checker/releases)
[![Latest Unstable Version](https://poser.pugx.org/bringyourownideas/silverstripe-composer-update-checker/v/unstable.svg)](https://packagist.org/packages/bringyourownideas/silverstripe-composer-update-checker)
[![Total Downloads](https://poser.pugx.org/bringyourownideas/silverstripe-composer-update-checker/downloads.svg)](https://packagist.org/packages/bringyourownideas/silverstripe-composer-update-checker)
[![License](https://poser.pugx.org/bringyourownideas/silverstripe-composer-update-checker/license.svg)](https://github.com/bringyourownideas/silverstripe-composer-update-checker/blob/master/license.md)

Checks if any of your Composer dependencies needs to be updated, and tracks the available and latest versions that can
be updated to.

This module only runs the actual checks and saves the information into fields on the Package DataObject. The fields
are also added to the report that the [SilverStripe Maintenance module](https://github.com/bringyourownideas/silverstripe-maintenance)
provides.

The bulk of the heavy lifting here is done using the Composer PHP API, which mimics the behaviour of using Composer on
the command line to install or update PHP packages.

### Requirements

* bringyourownideas/silverstripe-maintenance ^2
* composer/composer ^1
* silverstripe/framework ^4

#### Compatibility

The 1.x release line of this module is compatible with SilverStripe ^3.2, and the 2.x release line is compatible with
SilverStripe ^4.0.

### Installation

Run the following command to install this package:

```
composer require bringyourownideas/silverstripe-composer-update-checker ^2

vendor/bin/sake dev/build flush=1
vendor/bin/sake dev/tasks/UpdatePackageInfoTask
```

## Note for private repositories

Please note that if your project has modules that are stored in private repositories, the server running the BuildTask
will need to have the necessary permissions to access the private VCS repositories in order for the report to include
update information about necessary updates to the module.

If the process looking for available updates fails (for example, due to an authentication failure against a private
repository) the process will fail gracefully and allow the rest of the report generation to continue.

Users on the [Common Web Platform](https://cwp.govt.nz) will currently not be able to retrieve information about
updates to private repositories.

## Documentation

Please see the user guide section of the [SilverStripe Maintenance module](https://github.com/bringyourownideas/silverstripe-maintenance/tree/master/docs/en/userguide).

### Terminology

The "Available" version will show the latest available version for a Package that can be installed, given the package's
[semver constraint](https://semver.org). If the version is the same as that which is already installed, the column will
be empty in the report.

The "Latest" version is the latest available version regardless of the package's semver constraint.

When tracking available and latest versions, the current, available and latest version hashes are also stored against
the Package. This is to help with showing whether updates are available within a branch alias (for example: 1.x-dev).

## Contributing

Please see [the contributing guide](CONTRIBUTING.md).
