<?php
declare(strict_types=1);

namespace ListMerge;

/**
 * Class Merge
 * @package ListMerge
 */
class Merge
{
	/**
	 * @var int
	 */
	const META_DATA_CLASS = 1;

	/**
	 * @var int
	 */
	const META_DATA_SYNONYM = 2;

	/**
	 * @var int
	 */
	const CLASSIFIED = 3;

	/**
	 * @var int
	 */
	const UNCLASSIFIED = 4;

	/**
	 * @var int
	 */
	const TERM = 5;

	/**
	 * @var int
	 */
	const META_DATA = 6;

	/**
	 * @var int
	 */
	const CONFIDENCE_VERY_LOW = 7;

	/**
	 * @var int
	 */
	const CONFIDENCE_LOW = 8;

	/**
	 * @var int
	 */
	const CONFIDENCE_MEDIUM = 9;

	/**
	 * @var int
	 */
	const CONFIDENCE_HIGH = 10;

	/**
	 * @var int
	 */
	const CONFIDENCE_VERY_HIGH = 11;

	/**
	 * @var null|int
	 */
	protected $_confidence_threshold = null;

	/**
	 * @var array
	 */
	protected $_items = array();

	/**
	 * @var array
	 */
	protected $_items_processed = array();

	/**
	 * @var null|float
	 */
	protected $_score_threshold = null;

	/**
	 * @var null|float
	 */
	protected $_levenshtein_threshold = null;

	/**
	 * @var null|float
	 */
	protected $_similartext_threshold = null;

	/**
	 * @var null|float
	 */
	protected $_meta_data_class_threshold = null;

	/**
	 * @var null|float
	 */
	protected $_meta_data_synonym_threshold = null;

	/**
	 * Merge constructor.
	 * @param int $confidence_threshold
	 */
	function __construct(int $confidence_threshold = self::CONFIDENCE_HIGH)
	{
		$this->_confidence_threshold = $confidence_threshold;

		$this->setThresholds($this->_confidence_threshold);
	}

	/**
	 * @param int $confidence_level
	 * @return Merge
	 * @throws \Exception
	 */
	public function setThresholds(int $confidence_level): self
	{
		switch($confidence_level)
		{
			case self::CONFIDENCE_VERY_LOW:
				$this->_score_threshold = 25;
				$this->_levenshtein_threshold = 3;
				$this->_similartext_threshold = 50;
				$this->_meta_data_class_threshold = 30;
				$this->_meta_data_synonym_threshold = 20;
				break;
			case self::CONFIDENCE_LOW:
				$this->_score_threshold = 35;
				$this->_levenshtein_threshold = 3;
				$this->_similartext_threshold = 60;
				$this->_meta_data_class_threshold = 40;
				$this->_meta_data_synonym_threshold = 20;
				break;
			case self::CONFIDENCE_MEDIUM:
				$this->_score_threshold = 45;
				$this->_levenshtein_threshold = 2;
				$this->_similartext_threshold = 70;
				$this->_meta_data_class_threshold = 50;
				$this->_meta_data_synonym_threshold = 25;
				break;
			case self::CONFIDENCE_HIGH:
				$this->_score_threshold = 55;
				$this->_levenshtein_threshold = 2;
				$this->_similartext_threshold = 80;
				$this->_meta_data_class_threshold = 60;
				$this->_meta_data_synonym_threshold = 50;
				break;
			case self::CONFIDENCE_VERY_HIGH:
				$this->_score_threshold = 65;
				$this->_levenshtein_threshold = 1;
				$this->_similartext_threshold = 90;
				$this->_meta_data_class_threshold = 70;
				$this->_meta_data_synonym_threshold = 50;
				break;
			default:
				throw new \Exception('Unknown confidence level detected.');
		}

		return $this;
	}

	/**
	 * @param string $term
	 * @param array $meta_data
	 * @return Merge
	 * @throws \Exception
	 */
	public function addItem(string $term, array $meta_data = array()): self
	{
		if(empty($term))
		{
			throw new \Exception('Term cannot be empty.');
		}

		$this->_items[] = array(
			self::TERM => $term,
			self::META_DATA => $meta_data
		);

		return $this;
	}

	/**
	 * @param int $passes
	 * @return array
	 * @throws \Exception
	 */
	public function process(int $passes = 2): array
	{
		$this->_items_processed = $this->_items;

		for($x=0;$x<$passes;$x++)
		{
			error_log('pass '.$x);
			$this->_pass();
		}

		return $this->_items_processed;
	}

	/**
	 * @throws \Exception
	 */
	protected function _pass(): void
	{
		$new_list = array();

		if(empty($this->_items_processed))
		{
			throw new \Exception('No items provided to ListMerge.');
		}

		$reordered_items = array();

		foreach($this->_items_processed as $item)
		{
			if(empty($item[self::META_DATA]))
			{
				$reordered_items[] = $item;
			}
			else
			{
				array_unshift($reordered_items, $item);
			}
		}

		foreach(array_keys($reordered_items) as $key)
		{
			if(!isset($reordered_items[$key]))
			{
				continue;
			}

			$candidates = array();

			foreach($reordered_items as $key2 => $item)
			{
				if($key2 === $key)
				{
					continue;
				}

				$result = $this->_confidenceAchieved($reordered_items[$key], $item);

				if(!$result[0])
				{
					continue;
				}

				$candidates[] = $item;

				unset($reordered_items[$key2]);
			}

			if(!empty($candidates))
			{
				$new_array = $reordered_items[$key];

				foreach($candidates as $item2s)
				{error_log('merge '.$new_array[self::TERM].' '.$item2s[self::TERM]);
					$new_array = array(
						self::TERM => $this->_betterTerm($new_array[self::TERM], $item2s[self::TERM]),
						self::META_DATA => $this->_mergeMetaData($new_array[self::META_DATA], $item2s[self::META_DATA])
					);
				}

				$new_list[] = $new_array;

				$new_array = null;
				$candidates = null;
				unset($new_array,$candidates,$reordered_items[$key]);
			}
		}

		$this->_items_processed = array_merge($new_list,array_values($reordered_items));

		return;
	}

	/**
	 * @param array $meta_data_1
	 * @param array $meta_data_2
	 * @return array
	 */
	protected function _mergeMetaData(array $meta_data_1, array $meta_data_2): array
	{
		$new_meta_data = array();

		if(empty($meta_data_1[self::META_DATA_CLASS]) && !empty($meta_data_2[self::META_DATA_CLASS]))
		{
			$new_meta_data[self::META_DATA_CLASS] = $meta_data_2[self::META_DATA_CLASS];
		}
		elseif(!empty($meta_data_1[self::META_DATA_CLASS]) && empty($meta_data_2[self::META_DATA_CLASS]))
		{
			$new_meta_data[self::META_DATA_CLASS] = $meta_data_1[self::META_DATA_CLASS];
		}
		elseif(!empty($meta_data_1[self::META_DATA_CLASS]) && !empty($meta_data_2[self::META_DATA_CLASS]))
		{
			$new_meta_data[self::META_DATA_CLASS] = $meta_data_1[self::META_DATA_CLASS];

			foreach($meta_data_2[self::META_DATA_CLASS] as $class)
			{
				if(!in_array($class, $new_meta_data[self::META_DATA_CLASS]))
				{
					$new_meta_data[self::META_DATA_CLASS][] = $class;
				}
			}
		}

		if(empty($meta_data_1[self::META_DATA_SYNONYM]) && !empty($meta_data_2[self::META_DATA_SYNONYM]))
		{
			$new_meta_data[self::META_DATA_SYNONYM] = $meta_data_2[self::META_DATA_SYNONYM];
		}
		elseif(!empty($meta_data_1[self::META_DATA_SYNONYM]) && empty($meta_data_2[self::META_DATA_SYNONYM]))
		{
			$new_meta_data[self::META_DATA_SYNONYM] = $meta_data_1[self::META_DATA_SYNONYM];
		}
		elseif(!empty($meta_data_1[self::META_DATA_SYNONYM]) && !empty($meta_data_2[self::META_DATA_SYNONYM]))
		{
			$new_meta_data[self::META_DATA_SYNONYM] = $meta_data_1[self::META_DATA_SYNONYM];

			foreach($meta_data_2[self::META_DATA_SYNONYM] as $synonym)
			{
				if(!in_array($synonym, $new_meta_data[self::META_DATA_SYNONYM]))
				{
					$new_meta_data[self::META_DATA_SYNONYM][] = $synonym;
				}
			}
		}

		return $new_meta_data;
	}

	/**
	 * @param string $term_1
	 * @param string $term_2
	 * @return string
	 */
	protected function _betterTerm(string $term_1, string $term_2): string
	{
		if($term_1 == $term_2)
		{
			return $term_1;
		}

		$term_1_score = 0;
		$term_2_score = 0;

		if(mb_strtoupper($term_1, 'UTF-8') !== $term_1)
		{
			$term_1_score++;
		}

		if(mb_strtoupper($term_2, 'UTF-8') !== $term_2)
		{
			$term_2_score++;
		}

		if(ucwords($term_1) === $term_1)
		{
			$term_1_score++;
		}

		if(ucwords($term_2) === $term_2)
		{
			$term_2_score++;
		}

		if(strlen($term_1) > strlen($term_2))
		{
			$term_1_score += 2;
		}
		elseif(strlen($term_2) > strlen($term_1))
		{
			$term_2_score += 2;
		}

		if($term_1_score > $term_2_score)
		{
			return $term_1;
		}
		elseif($term_2_score > $term_1_score)
		{
			return $term_2;
		}
		else
		{
			return $term_1;
		}
	}

	/**
	 * @param array $item_1
	 * @param array $item_2
	 * @return array
	 */
	protected function _confidenceAchieved(array $item_1, array $item_2): array
	{
		if($item_1 === $item_2)
		{
			return array(
				true,
				100
			);
		}
		else
		{
			$points = 0;
			$tpoints = 0;
			$term_1 = mb_strtolower($item_1[self::TERM], 'UTF-8');
			$term_2 = mb_strtolower($item_2[self::TERM], 'UTF-8');

			$tpoints += 2;
			if(substr($term_1,0,1) === substr($term_2,0, 1))
			{
				$points += 2;
			}

			$tpoints += 2;
			if($term_1 === $term_2)
			{
				$points += 2;
			}

			$tpoints += 1;
			if(levenshtein($term_1, $term_2) <= $this->_levenshtein_threshold)
			{
				$points += 1;
			}

			$percent = 0;
			$percent_2 = 0;
			similar_text($term_1, $term_2, $percent);
			similar_text($term_2, $term_1, $percent_2);
			$average_percentage = ($percent+$percent_2)/2;

			$tpoints += 1;
			if($average_percentage >= $this->_similartext_threshold)
			{
				$points += 1;
			}

			if(!empty($item_1[self::META_DATA][self::META_DATA_CLASS]) && !empty($item_2[self::META_DATA][self::META_DATA_CLASS]))
			{
				$tpoints += 2;

				if(
					($item_1[self::META_DATA][self::META_DATA_CLASS] === $item_2[self::META_DATA][self::META_DATA_CLASS]) ||
					$this->_arraySimilarity(
						$item_1[self::META_DATA][self::META_DATA_CLASS],
						$item_2[self::META_DATA][self::META_DATA_CLASS]
					) >= $this->_meta_data_class_threshold
				)
				{
					$points += 2;
				}
			}

			if(!empty($item_1[self::META_DATA][self::META_DATA_SYNONYM]) && !empty($item_2[self::META_DATA][self::META_DATA_SYNONYM]))
			{
				$tpoints += 2;

				if(
					($item_1[self::META_DATA][self::META_DATA_SYNONYM] === $item_2[self::META_DATA][self::META_DATA_SYNONYM]) ||
					$this->_arraySimilarity(
						$item_1[self::META_DATA][self::META_DATA_SYNONYM],
						$item_2[self::META_DATA][self::META_DATA_SYNONYM]
					) >= $this->_meta_data_synonym_threshold
				)
				{
					$points += 2;
				}
			}

			if($points/$tpoints*100 >= $this->_score_threshold)
			{
				return array(
					true,
					$points/$tpoints*100
				);
			}
		}

		return array(
			false
		);
	}

	/**
	 * @param array $array_1
	 * @param array $array_2
	 * @return float
	 */
	protected function _arraySimilarity(array $array_1, array $array_2): float
	{
		$array_1_size = count($array_1);
		$array_2_size = count($array_2);

		$array_1_matches = 0;

		foreach($array_1 as $item)
		{
			foreach($array_2 as $item2)
			{
				if($item === $item2)
				{
					$array_1_matches++;
				}
			}
		}

		return $array_1_matches/max($array_1_size,$array_2_size)*100;
	}
}