PHP Cron Expression Parser
==========================

[![Latest Stable Version](https://poser.pugx.org/mtdowling/cron-expression/v/stable.png)](https://packagist.org/packages/mtdowling/cron-expression) [![Total Downloads](https://poser.pugx.org/mtdowling/cron-expression/downloads.png)](https://packagist.org/packages/mtdowling/cron-expression) [![Build Status](https://secure.travis-ci.org/mtdowling/cron-expression.png)](http://travis-ci.org/mtdowling/cron-expression)

The PHP cron expression parser can parse a CRON expression, determine if it is
due to run, calculate the next run date of the expression, and calculate the previous
run date of the expression.  You can calculate dates far into the future or past by
skipping n number of matching dates.

The parser can handle increments of ranges (e.g. */12, 2-59/3), intervals (e.g. 0-9),
lists (e.g. 1,2,3), W to find the nearest weekday for a given day of the month, L to
find the last day of the month, L to find the last given weekday of a month, and hash
(#) to find the nth weekday of a given month.

Credits
==========

Created by Micheal Dowling. Ported to PHP 5.2 by Flightless, Inc.
Based on version 1.0.3: https://github.com/mtdowling/cron-expression/tree/v1.0.3

Installing
==========

Add the following to your project's composer.json:

```javascript
{
    "require": {
        "mtdowling/cron-expression": "1.0.*"
    }
}
```

Usage
=====
```php
<?php

require_once '/vendor/autoload.php';

// Works with predefined scheduling definitions
$cron = Cron\CronExpression::factory('@daily');
$cron->isDue();
echo $cron->getNextRunDate()->format('Y-m-d H:i:s');
echo $cron->getPreviousRunDate()->format('Y-m-d H:i:s');

// Works with complex expressions
$cron = Cron\CronExpression::factory('3-59/15 2,6-12 */15 1 2-5');
echo $cron->getNextRunDate()->format('Y-m-d H:i:s');

// Calculate a run date two iterations into the future
$cron = Cron\CronExpression::factory('@daily');
echo $cron->getNextRunDate(null, 2)->format('Y-m-d H:i:s');

// Calculate a run date relative to a specific time
$cron = Cron\CronExpression::factory('@monthly');
echo $cron->getNextRunDate('2010-01-12 00:00:00')->format('Y-m-d H:i:s');
```

CRON Expressions
================

A CRON expression is a string representing the schedule for a particular command to execute.  The parts of a CRON schedule are as follows:

    *    *    *    *    *    *
    -    -    -    -    -    -
    |    |    |    |    |    |
    |    |    |    |    |    + year [optional]
    |    |    |    |    +----- day of week (0 - 7) (Sunday=0 or 7)
    |    |    |    +---------- month (1 - 12)
    |    |    +--------------- day of month (1 - 31)
    |    +-------------------- hour (0 - 23)
    +------------------------- min (0 - 59)

Requirements
============

- PHP 5.3+
- PHPUnit is required to run the unit tests
- Composer is required to run the unit tests

CHANGELOG
=========

1.0.3 (2013-11-23)
------------------

* Only set default timezone if the given $currentTime is not a DateTime instance (#34)
* Fixes issue #28 where PHP increments of ranges were failing due to PHP casting hyphens to 0
* Now supports expressions with any number of extra spaces, tabs, or newlines
* Using static instead of self in `CronExpression::factory`
