silverstripe-composerupdates
============================

Send email notifications when updates are available to Composer packages

Installation
------------

Add to your `composer.json` configuration

```
"require": {
  "xplore/composerupdates": "~0.1"
}
```

Configuration
-------------

Configure `_ss_environment.php` so that command line requests don't fail when sending emails http://doc.silverstripe.com/framework/en/topics/commandline#configuration

```
<?php
  global $_FILE_TO_URL_MAPPING;
  $_FILE_TO_URL_MAPPING['/path/to/site'] = 'http://my.site.com';
```

Set the from address in `mysite/_config/config.yml` http://doc.silverstripe.com/framework/en/topics/email#administrator-emails

```
Email:
  admin_email: 'admin@my.site.com'
```

Define who will receive the notification emails in `mysite/_config/config.yml`

```
ComposerUpdates:
  notify:
    - 'user1@domain.com'
    - 'user2@domain.com'
```

Define a cron task as often as you require which executes

```
/path/to/site/framework/sake CheckComposerUpdates
```
