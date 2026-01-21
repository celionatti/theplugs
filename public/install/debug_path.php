<?php
define('INSTALL_PATH', __DIR__ . '/');
define('ROOT_PATH', dirname(__DIR__, 2) . '/');

echo "Current Dir: " . __DIR__ . PHP_EOL;
echo "ROOT_PATH: " . ROOT_PATH . PHP_EOL;
echo "Is Writable: " . (is_writable(ROOT_PATH) ? 'YES' : 'NO') . PHP_EOL;
echo "Plugs Lock Path: " . ROOT_PATH . 'plugs.lock' . PHP_EOL;
