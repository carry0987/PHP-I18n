# PHP-I18n
[![Packgist](https://img.shields.io/packagist/v/carry0987/i18n.svg?style=flat-square)](https://packagist.org/packages/carry0987/i18n)  
A modern internationalization system featuring JSON-format language files and efficient PHP-based caching. Supports dynamic language switching and real-time cache updates, ideal for rapid development and deployment of multilingual websites and applications.

## Features
- Supports multiple language files
- Automatic caching of translation data for improved performance
- Supports organization of translation keys by namespaces
- Configurable translation file directory and cache directory

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
│   ├── Exception
│   │   ├── IOException.php
│   │   ├── InitializationException.php
│   │   └── InvalidLanguageCodeException.php
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
    'langFilePath' => 'path/to/lang', 
    'cachePath' => 'path/to/cache'
]);

// Initialize the language settings
$i18n->initialize('en_US'); // 'en_US' is the language code
```

Fetch translations:

```php
// Use file name and key to get the translation value
echo $i18n->fetch('greeting.hello'); // Outputs: "Hello"
```

## Note
When accessing translations, the keys used must follow the format: `filename.key`, which ensures that each translation value is extracted from the specified file.

## Advanced Usage
Load specific language files:

```php
// Set the list of language files to be loaded
$i18n->setFileList(['general', 'greeting']);
```
