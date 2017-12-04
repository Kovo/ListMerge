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
	private $_confidence_threshold = null;

	/**
	 * @var array
	 */
	private $_items = array();

	/**
	 * @var null|float
	 */
	private $_score_threshold = null;

	/**
	 * @var null|float
	 */
	private $_levenshtein_threshold = null;

	/**
	 * @var null|float
	 */
	private $_similartext_threshold = null;

	/**
	 * @var null|float
	 */
	private $_meta_data_class_threshold = null;

	/**
	 * @var null|float
	 */
	private $_meta_data_synonym_threshold = null;

	/**
	 * Merge constructor.
	 * @param int $confidence_threshold
	 */
	function __construct(int $confidence_threshold = self::CONFIDENCE_MEDIUM)
	{
		$this->_confidence_threshold = $confidence_threshold;

		$this->setThresholds($this->_confidence_threshold);
	}

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

		$this->_items[$term] = array(
			self::TERM => $term,
			self::META_DATA => $meta_data
		);

		return $this;
	}

	/**
	 * @return array
	 * @throws \Exception
	 */
	public function process(): array
	{
		$new_list = array(
			self::CLASSIFIED => array(),
			self::UNCLASSIFIED => array()
		);

		if(empty($this->_items))
		{
			throw new \Exception('No items provided to ListMerge.');
		}

		$reordered_items = array();

		foreach($this->_items as $item)
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

				if(!$this->_confidenceAchieved($reordered_items[$key], $item))
				{
					continue;
				}

				$candidates[$key][] = $key2;
			}
		}

		return $new_list;
	}

	/**
	 * @param array $item_1
	 * @param array $item_2
	 * @return bool
	 */
	private function _confidenceAchieved(array $item_1, array $item_2): bool
	{
		if($item_1 === $item_2)
		{
			return true;
		}
		else
		{
			$points = 0;
			$tpoints = 0;
			$term_1 = mb_strtolower($item_1[self::TERM], 'UTF-8');
			$term_2 = mb_strtolower($item_2[self::TERM], 'UTF-8');

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
			}

			if(!empty($item_1[self::META_DATA][self::META_DATA_SYNONYM]) && !empty($item_2[self::META_DATA][self::META_DATA_SYNONYM]))
			{
				$tpoints += 2;
			}

			if($points/$tpoints >= $this->_score_threshold)
			{
				return true;
			}
		}

		return false;
	}
}