# PHP-I18n
[![Packgist](https://img.shields.io/packagist/v/carry0987/i18n.svg?style=flat-square)](https://packagist.org/packages/carry0987/i18n)  
A modern internationalization system featuring JSON-format language files and efficient PHP-based caching. Supports dynamic language switching and real-time cache updates, ideal for rapid development and deployment of multilingual websites and applications.

## Features
- Supports multiple language files
- Automatic caching of translation data for improved performance
- Supports organization of translation keys by namespaces
- Configurable translation file directory and cache directory

## Requirements
- Composer

## File Structure
Here's an example of the expected file structure:
```
.
├── cache
├── lang
│   ├── en_US
│   │   ├── general.json
│   │   └── greeting.json
│   └── zh_TW
│       ├── general.json
│       └── greeting.json
├── src
│   ├── /...
│   └── I18n.php
└── vendor
```

## Installation
Use Composer to install the I18n class library into your project:

``` bash
composer require carry0987/i18n
```

## Usage
After installation, you can include the `I18n` class in your project with Composer's autoloading:
```php
require_once 'vendor/autoload.php';

use carry0987\I18n\I18n;

$i18n = new I18n([
    'useAutoDetect' => true, // By default, the language is automatically detected
    'langFilePath' => 'path/to/lang', 
    'cachePath' => 'path/to/cache'
]);
```

Fetch translations:
```php
// Use file name and key to get the translation value
echo $i18n->fetch('greeting.hello'); // Outputs: "Hello"
```

Fetch multiple translations:
```php
// Get multiple translations in the current language
$translations = $i18n->fetchList(); // Empty parameter returns all translations
```

## Note
When accessing translations, the keys used must follow the format: `filename.key`, which ensures that each translation value is extracted from the specified file.

## Advanced Usage
If set `useAutoDetect` to `true`, the language will be automatically detected from the browser's `Accept-Language` header. If the language is not supported, the default language will be used. Also, if you want to change cookie settings, you can set the `cookie` option:
```php
$config = array(
    'useAutoDetect' => true,
    //...
    'cookie' => array(
        'name' => 'lang',
        'expire' => time()+864000,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true
    )
);
```

You can set the language code manually:
```php
$i18n = new I18n([
    'useAutoDetect' => false, // Set useAutoDetect to false to disable automatic language detection
    //...
]);
// Initialize the language settings
$i18n->initialize('en_US'); // 'en_US' is the language code
```

Load specific language files:
```php
$i18n = new I18n([
    'allowedFiles' => ['general', 'greeting'], // Set the list of language files to be loaded
    //...
]);
```

Set language aliases:
```php
// Define aliases for languages
$i18n->setLangAlias(array('en_US' => 'English', 'zh_TW' => '繁體中文'));

// Fetch the list of languages with their aliases
$aliasedLangList = $i18n->fetchLangList();
// Outputs: array('en_US' => 'English', 'zh_TW' => '繁體中文')
```
