# File Validation Checks

zendframework/zenddiagnostics ships with the following file validation checks.

## IniFile

Read an INI-file from the given path and try to parse it.

```php
<?php
use ZendDiagnostics\Check\IniFile;

$checkIniFile = new IniFile('/my/path/to/file.ini');
$checkIniFile = new IniFile(['file1.ini', 'file2.ini', '...']);
```

## JsonFile

Read a JSON-file from the given path and try to decode it.

```php
<?php
use ZendDiagnostics\Check\JsonFile;

$checkJsonFile = new JsonFile('/my/path/to/file.json');
$checkJsonFile = new JsonFile(['file1.json', 'file2.json', '...']);
```

## XmlFile

Read an XML-file from the given path, try to parse it, and attempt to validate
it agaist its DTD schema if possible.

```php
<?php
use ZendDiagnostics\Check\XmlFile;

$checkXmlFile = new XmlFile('/my/path/to/file.xml');
$checkXmlFile = new XmlFile(['file1.xml', 'file2.xml', '...']);
```

## YamlFile

Read a YAML-file from the given path and try to parse it.

```php
<?php
use ZendDiagnostics\Check\YamlFile;

$checkYamlFile = new YamlFile('/my/path/to/file.yml');
$checkYamlFile = new YamlFile(['file1.yml', 'file2.yml', '...']);
```
