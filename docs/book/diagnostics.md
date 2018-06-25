# Diagnostic Checks

zendframework/zenddiagnostics provides several "just add water" checks you can
use straight away.

The following built-in tests are currently available:

## ApcFragmentation

Make sure that [APC memory fragmentation level](www.php.net/apc/) is below a
given threshold:

```php
<?php
use ZendDiagnostics\Check\ApcFragmentation;

// Display a warning with fragmentation > 50% and failure when above 90%
$fragmentation = new ApcFragmentation(50, 90);
```

## ApcMemory

Check [APC memory usage percent](www.php.net/apc/) and make sure it's below a
given threshold.

```php
<?php
use ZendDiagnostics\Check\ApcMemory;

// Display a warning with memory usage is above 70% and a failure above 90%
$checkFreeMemory = new ApcMemory(70, 90);
```

## Callback

Run a function (callback) and use its return value as the result:

```php
<?php
use ZendDiagnostics\Check\Callback;
use ZendDiagnostics\Result\Success;
use ZendDiagnostics\Result\Failure;

$checkDbFile = new Callback(function() {
    $path = __DIR__ . '/data/db.sqlite';
    if (is_file($path) && is_readable($path) && filesize($path)) {
        return new Success('Db file is ok');
    }

		return new Failure('There is something wrong with the db file');
});
```

> ### Callback signature
> 
> The callback must return either a `boolean` (true for success, false for
> failure), or a valid instance of
> [ResultInterface](https://github.com/zendframework/zend-diagnostics/tree/master/src/Result/ResultInterface.php).
> All other objects will result in an exception, and scalars (i.e. a string) will
> be interpreted as warnings.

## ClassExists

Check if a class (or an array of classes) exists. For example:

```php
<?php
use ZendDiagnostics\Check\ClassExists;

$checkLuaClass = new ClassExists('Lua');
$checkRbacClasses = new ClassExists([
    'ZfcRbac\Module',
    'ZfcRbac\Controller\Plugin\IsGranted'
]);
```

## CpuPerformance

Benchmark CPU performance and return failure if it is below the given ratio. The
baseline for performance calculation is the speed of an Amazon EC2 Micro Instance
(Q1 2013). You can specify the expected performance for the test, where a ratio
of `1.0` (one) means at least the speed of EC2 Micro Instance. A ratio of `2`
would mean "at least double the performance of EC2 Micro Instance" and a
fraction of `0.5` means "at least half the performance of Micro Instance".

The following check will test if current server has at least half the CPU power
of EC2 Micro Instance:

```php
<?php
use ZendDiagnostics\Check\CpuPerformance;

$checkMinCPUSpeed = new CpuPerformance(0.5); // at least 50% of EC2 micro instance
```

## DirReadable

Check if a given path (or array of paths) points to a directory and it is
readable.

```php
<?php
use ZendDiagnostics\Check\DirReadable;

$checkPublic = new DirReadable('public/');
$checkAssets = new DirReadable([
    __DIR__ . '/assets/img',
    __DIR__ . '/assets/js',
]);
```

## DirWritable

Check if a given path (or array of paths) points to a directory and if it can be
written to.

```php
<?php
use ZendDiagnostics\Check\DirWritable;

$checkTemporary = new DirWritable('/tmp');
$checkAssets    = new DirWritable([
    __DIR__ . '/assets/customImages',
    __DIR__ . '/assets/customJs',
    __DIR__ . '/assets/uploads',
]);
```

## DiskFree

Check if there is enough remaining free disk space.

The first parameter is the minimum disk space, which can be supplied as an
integer (in bytes, e.g. `1024`) or as a string with a multiplier (IEC, SI or
Jedec; e.g.  `"150MB"`). The second parameter is the path to check; on \*NIX
systems it is an ordinary path (e.g. `/home`), while on Windows systems it is a
drive letter (e.g.  `"C:"`).

```php
<?php
use ZendDiagnostics\Check\DiskFree;

$tempHasAtLeast100Megs  = new DiskFree('100MB', '/tmp');
$homeHasAtLeast1TB      = new DiskFree('1TiB',  '/home');
$dataHasAtLeast900Bytes = new DiskFree(900, __DIR__ . '/data/');
```

### ExtensionLoaded

Check if a PHP extension (or an array of extensions) is currently loaded.

```php
<?php
use ZendDiagnostics\Check\ExtensionLoaded;

$checkMbstring    = new ExtensionLoaded('mbstring');
$checkCompression = new ExtensionLoaded([
    'rar',
    'bzip2',
    'zip',
]);
```

## HttpService

Attempt connection to a given HTTP host or IP address and try to load a web
page. The check also supports checking response codes and page contents.

```php
<?php
use ZendDiagnostics\Check\HttpService;

// Try to connect to google.com
$checkGoogle = new HttpService('www.google.com');

// Check port 8080 on localhost
$checkLocal = new HttpService('127.0.0.1', 8080);

// Check that the page exists (response code must equal 200)
$checkPage = new HttpService('www.example.com', 80, '/some/page.html', 200);

// Check page content
$checkPageContent = new HttpService(
    'www.example.com',
    80,
    '/some/page.html',
    200,
    '<title>Hello World</title>'
);
```

## GuzzleHttpService

Attempt connection to a given HTTP host or IP address and try to load a web page
using [Guzzle](http://guzzle3.readthedocs.org/en/latest/). The check also
supports checking response codes and page contents.

```php
<?php
use ZendDiagnostics\Check\GuzzleHttpService;

// Try to connect to google.com
$checkGoogle = new GuzzleHttpService('www.google.com');

// Check port 8080 on localhost
$checkLocal = new GuzzleHttpService('127.0.0.1:8080');

// Check that the page exists (response code must equal 200)
$checkPage = new GuzzleHttpService('www.example.com/some/page.html');

// Check page content
$checkPageContent = new GuzzleHttpService(
    'www.example.com/some/page.html',
    [],
    [],
    200,
    '<title>Hello World</title>'
);

// Check that the post request returns the content
$checkPageContent = new GuzzleHttpService(
    'www.example.com/user/update',
    [],
    [],
    200,
    '{"status":"success"}',
    'POST',
    ['post_field' => 'post_value']
);
```

## Memcache

Attempt to connect to given Memcache server.

```php
<?php
use ZendDiagnostics\Check\Memcache;

$checkLocal  = new Memcache('127.0.0.1'); // default port
$checkBackup = new Memcache('10.0.30.40', 11212);
```
   
## Memcached

Attempt to connect to the given Memcached server.

```php
<?php
use ZendDiagnostics\Check\Memcached;

$checkLocal  = new Memcached('127.0.0.1'); // default port
$checkBackup = new Memcached('10.0.30.40', 11212);
```

### MongoDb
Check if connection to MongoDb is possible

````php
<?php
use ZendDiagnostics\Check\Mongo;

$mongoCheck = new Mongo('mongodb://127.0.0.1:27017');
// and with user/password
$mongoCheck = new Mongo('mongodb://user:password@127.0.0.1:27017');
````



## MongoDb

Check if a connection to a given MongoDb server is possible.

```php
<?php
use ZendDiagnostics\Check\Mongo;

$mongoCheck = new Mongo('mongodb://127.0.0.1:27017');
// and with user/password
$mongoCheck = new Mongo('mongodb://user:password@127.0.0.1:27017');
```

## PhpVersion

Check if the current PHP version matches the given requirement. The test accepts
2 parameters: baseline version and optional
[comparison operator](http://www.php.net/manual/en/function.version-compare.php).

```php
<?php
use ZendDiagnostics\Check\PhpVersion;

$require545orNewer  = new PhpVersion('5.4.5');
$rejectBetaVersions = new PhpVersion('5.5.0', '<');
```

## PhpFlag

Make sure that the provided PHP flag(s) is enabled or disabled (as defined in
`php.ini`). You can use this test to alert the user about unsafe or
behavior-changing PHP settings.

```php
<?php
use ZendDiagnostics\Check\PhpFlag;

// This check will fail if use_only_cookies is not enabled
$sessionOnlyUsesCookies = new PhpFlag('session.use_only_cookies', true);

// This check will fail if safe_mode has been enabled
$noSafeMode = new PhpFlag('safe_mode', false);

// The following will fail if any of the flags is enabled
$check = new PhpFlag([
    'expose_php',
    'ignore_user_abort',
    'html_errors',
], false);
```

## ProcessRunning

Check if a given unix process is running. This check supports PIDs and process
names.

```php
<?php
use ZendDiagnostics\Check\ProcessRunning;

$checkApache = new ProcessRunning('httpd');
$checkProcess1000 = new ProcessRunning(1000);
```

## RabbitMQ

Validate that a RabbitMQ service is running.

```php
<?php
use ZendDiagnostics\Check\RabbitMQ;

$rabbitMQCheck = new RabbitMQ('localhost', 5672, 'guest', 'guest', '/');
```

## Redis

Validate that a Redis service is running.

```php
<?php
use ZendDiagnostics\Check\Redis;

$redisCheck = new Redis('localhost', 6379, 'secret');
```

## SecurityAdvisory

Run a security check of libraries locally installed by
[Composer](http://getcomposer.org) against [SensioLabs Security Advisory
database](https://security.sensiolabs.org/database), and warn about potential
security vulnerabilities.

```php
<?php
use ZendDiagnostics\Check\SecurityAdvisory;

// Warn about any packages that might have security vulnerabilities
// and require updating
$security = new SecurityAdvisory();

// Check another composer.lock
$security = new SecurityAdvisory('/var/www/project/composer.lock');
```

## StreamWrapperExists

Check if a given stream wrapper (or an array of wrappers) is available. For
example:

```php
<?php
use ZendDiagnostics\Check\StreamWrapperExists;

$checkOGGStream   = new StreamWrapperExists('ogg');
$checkCompression = new StreamWrapperExists([
    'zlib',
    'bzip2',
    'zip',
]);
```

## DoctrineMigration

Make sure all migrations are applied:

```php
<?php
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\ORM\EntityManager;
use ZendDiagnostics\Check\DoctrineMigration;

$em = EntityManager::create(/* config */);
$migrationConfig = new Configuration($em);
$check = new DoctrineMigration($migrationConfig);
```
