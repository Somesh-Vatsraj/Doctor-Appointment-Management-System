<?php
declare(strict_types=1);

echo '<pre>';

echo "PHP Version: " . PHP_VERSION . PHP_EOL;
echo "PDO: " . (extension_loaded('pdo') ? 'YES' : 'NO') . PHP_EOL;
echo "PDO MySQL: " . (extension_loaded('pdo_mysql') ? 'YES' : 'NO') . PHP_EOL;

echo PHP_EOL . "Available PDO Drivers:" . PHP_EOL;
print_r(PDO::getAvailableDrivers());

echo PHP_EOL . "Extension Directory:" . PHP_EOL;
echo ini_get('extension_dir') . PHP_EOL;

echo PHP_EOL . "Loaded php.ini:" . PHP_EOL;
echo php_ini_loaded_file() ?: 'NONE';

echo '</pre>';
