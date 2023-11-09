<?php
require_once 'vendor/autoload.php';

use carry0987\I18n\I18n;

$config = array(
    'langFilePath' => 'lang',
    'cachePath' => 'cache',
    'defaultLang' => 'en_US',
    'separator' => '_',
    'autoSearch' => true,
    'countryCodeUpperCase' => true
);

try {
    $i18n = new I18n($config);
    $i18n->initialize('zh_TW');
    $i18n->setLangAlias(array('en_US' => 'English', 'zh_TW' => '繁體中文'));
    echo '<h2>Fetch \'hello\' from \'greeting.json\'</h2>';
    echo $i18n->fetch('greeting.hello');
    echo '<h2>Fetch List</h2>';
    echo "\n", '<pre>';
    var_export($i18n->fetchList());
    echo '</pre>';
    echo '<h2>Fetch Lang List</h2>';
    echo "\n", '<pre>';
    var_export($i18n->fetchLangList());
    echo '</pre>';
} catch (\carry0987\I18n\Exception\IOException $e) {
    echo "IO Exception: " . $e->getMessage();
} catch (\carry0987\I18n\Exception\InitializationException $e) {
    echo "Initialization Exception: " . $e->getMessage();
} catch (\carry0987\I18n\Exception\InvalidLanguageCodeException $e) {
    echo "Invalid Language Code Exception: " . $e->getMessage();
}
