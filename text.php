<?php

echo extension_loaded('pdo_mysql')
    ? '✅ PDO MySQL is enabled'
    : '❌ PDO MySQL is NOT enabled';
echo '<pre>';

echo "PHP Version: " . PHP_VERSION . PHP_EOL;
echo "PDO: " . (extension_loaded('pdo') ? 'YES' : 'NO') . PHP_EOL;
echo "PDO MySQL: " . (extension_loaded('pdo_mysql') ? 'YES' : 'NO') . PHP_EOL;

print_r(PDO::getAvailableDrivers());
