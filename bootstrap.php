<?php
declare(strict_types=1);
/*
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice, contribtuions, and original author information.
 * @author Kevork Aghazarian
 * @website https://github.com/Kovo/ListMerge
 */
require_once 'Psr4Autoloader.php';

$loader = new Psr4Autoloader();
$loader->register();
$loader->addNamespace('ListMerge\\', 'ListMerge/');