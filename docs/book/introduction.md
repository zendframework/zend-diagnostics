# Introduction

zenddiagnostics provides diagnostic tests for real-world PHP applications.

It currently ships with the following diagnostic checks:

- [ApcFragmentation](diagnostics.md#apcfragmentation) - check if APC memory fragmentation is below given threshold,
- [ApcMemory](diagnostics.md#apcmemory) - check available APC memory,
- [Callback](diagnostics.md#callback) - call a user-defined diagnostic function,
- [ClassExists](diagnostics.md#classexists) - make sure class exists in current environment,
- [CouchDBCheck](diagnostics.md#couchdbcheck) - check if connection is possible,
- [CpuPerformance](diagnostics.md#cpuperformance) - check server CPU performance is above baseline,
- [DirReadable](diagnostics.md#dirreadable) - make sure given path is readable,
- [DirWritable](diagnostics.md#dirwritable) - make sure given path is writable,
- [DiskFree](diagnostics.md#diskfree) - check there's enough free space on given path,
- [DiskUsage](diagnostics.md#diskusage) - check if the disk usage is below warning/critical percent thresholds,
- [DoctrineMigration](diagnostics.md#doctrinemigration) - make sure all migrations are applied.
- [ExtensionLoaded](diagnostics.md#extensionloaded) - make sure extension is loaded,
- [GuzzleHttpService](diagnostics.md#guzzlehttpservice) - check if given http host is responding using Guzzle,
- [HttpService](diagnostics.md#httpservice) - check if given http host is responding,
- [Memcache](diagnostics.md#memcache) - check if memcache extension is loaded and given server is reachable,
- [Mongo](diagnostics.md#mongodb) - check if connection to MongoDb is possible,
- [OpCacheMemory](diagnostics.md#opcachememory) - check if the OpCache memory usage is below warning/critical thresholds,
- [PDOCheck](diagnostics.md#pdocheck) - check if connection is possible,
- [PhpVersion](diagnostics.md#phpversion) - make sure that PHP version matches constraint,
- [PhpFlag](diagnostics.md#phpflag) - make sure that given PHP flag (feature) is turned on or off.
- [ProcessRunning](diagnostics.md#processrunning) - check if a process with given name or ID is currently running,
- [RabbitMQ](diagnostics.md#rabbitmq) - Validate that a RabbitMQ service is running,
- [Redis](diagnostics.md#redis) - Validate that a Redis service is running,
- [SecurityAdvisory](diagnostics.md#securityadvisory) - check installed composer dependencies against SensioLabs SA database,
- [StreamWrapperExists](diagnostics.md#streamwrapperexists) - make sure given stream wrapper is available.

It also provides the following file validation checks:

- [IniFile](file-validation.md#inifile) - check if given INI file is available and valid,
- [JsonFile](file-validation.md#jsonfile) - check if given JSON file is available and valid,
- [XmlFile](file-validation.md#xmlfile) - check if given XML file is available and valid,
- [YamlFile](file-validation.md#yamlfile) - check if given YAML file is available and valid
