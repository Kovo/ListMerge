# ListMerge

The ListMerge algorithm is meant to consolidate a given list of items to as few as possible based on item similarity. Traditional list merging algorithms use established string matching criteria (such as Levenshtein distance). However the ListMerge algorithm takes advantage of both Levenshtein and Ian Oliver string matching algorithms, as well as a custom word matching algorithm. ListMerge can use synonym, and class sets as part of its decision making process, to allow for a more refined and accurate consolidation.

# Limitations

- ListMerge will not combine cross-class terms. Laptops will never merge with Computers. This is by design, though the algorithm can be tweaked to achieve this result.
- ListMerge analyzes input data in a vacuum. That is to say, it does not consult remote sources for guidance on its merging decisions. Again, this is by design. Anyone can expand on ListMerge to allow for this kind of interaction.

# Sample Usage

```php
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
		'GREEN'
	)
	->addItem('Blue')
	->addItem(
		'Laptop',
		array(
			\ListMerge\Merge::META_DATA_CLASS => array('Electronics'),
		)
	)
	->addItem('Laptops')
	->addItem(
		'laptops',
		array(
			\ListMerge\Merge::META_DATA_SYNONYM => array('Laptops', 'Computers & Laptops')
		)
	)
	->addItem(
		'Computers',
		array(
			\ListMerge\Merge::META_DATA_CLASS => array('Electronics'),
			\ListMerge\Merge::META_DATA_SYNONYM => array('Laptops', 'Computers & Laptops')
		)
	)
	->addItem('computers');

$merged_list = $Merge->process();

echo json_encode($merged_list);
```
### Result
```json
[{
	"5": "Red",
	"6": {
		"1": ["Color"]
	}
}, {
	"5": "Blue",
	"6": {
		"1": ["Color"]
	}
}, {
	"5": "Green",
	"6": {
		"1": ["Color"]
	}
}, {
	"5": "Laptops",
	"6": {
		"1": ["Electronics"],
		"2": ["Laptops", "Computers & Laptops"]
	}
}, {
	"5": "Computers",
	"6": {
		"1": ["Electronics"],
		"2": ["Laptops", "Computers & Laptops"]
	}
}, {
	"5": "Brands",
	"6": []
}, {
	"5": "Colors",
	"6": []
}]
```