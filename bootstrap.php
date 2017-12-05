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

/*
 * Example
 */
$Merge = new \ListMerge\Merge(\ListMerge\Merge::CONFIDENCE_MEDIUM);
$Merge
	->addItem('Brand')
	->addItem('Brands')
	->addItem('brand')
	->addItem('brANd')
	->addItem('Color')
	->addItem('colour')
	->addItem('COLOR')
	->addItem('Colors')
	->addItem(
		'Red',
		array(
			\ListMerge\Merge::META_DATA_CLASS => array('Color')
		)
	)
	->addItem(
		'Blue',
		array(
			\ListMerge\Merge::META_DATA_CLASS => array('Color')
		)
	)
	->addItem(
		'Green',
		array(
			\ListMerge\Merge::META_DATA_CLASS => array('Color')
		)
	)
	->addItem(
		'Gren',
		array(
			\ListMerge\Merge::META_DATA_CLASS => array('Color')
		)
	)
	->addItem(
		'GREEN',
		array(
			\ListMerge\Merge::META_DATA_CLASS => array('Color')
		)
	)
	->addItem('Blue')
	->addItem('laptops')
	->addItem(
		'Computers',
		array(
			\ListMerge\Merge::META_DATA_CLASS => array('Electronics'),
			\ListMerge\Merge::META_DATA_SYNONYM => array('Laptops', 'Computers & Laptops')
		)
	)
	->addItem('computers');
echo '<pre>';
$merged_list = $Merge->process();
echo json_encode($merged_list);
echo '</pre>';