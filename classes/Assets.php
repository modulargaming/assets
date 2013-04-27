<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Abstract base view for normal purposes.
 *
 * @package    Assets
 * @author     Oscar Hinton
 * @copyright  (c) 2013 Oscar Hinton
 * @license    BSD http://modulargaming.com/license
 */
class Assets {

	/**
	 * @var array|Kohana_Config_Group
	 */
	private $_config;

	/**
	 * @var string
	 */
	private $_group;

	/**
	 * @var array
	 */
	private $_assets = array();

	/**
	 * @param array $config
	 */
	public function __construct($config = null)
	{
		$this->_config = ($config) ? $config : Kohana::$config->load('assets');
	}

	/**
	 * Load the group assets and order it.
	 *
	 * @param $name
	 */
	public function group($group)
	{
		$this->_group = $group;

		// Abort if group is not found
		if ($group == NULL OR ! isset($this->_config[$group]))
		{
			return;
		}

		$assets = $this->_config[$group];

		// Sort the assets, first iterate sections, then types (script/style).
		foreach ($assets as $section => $value) {
			foreach (array_keys($value) as $type) {
				usort($assets[$section][$type], array($this, '_sort_assets'));
			}
		}

		$this->_assets = $assets;
	}

	/**
	 * Get the assets for the section.
	 *
	 * @param $section
	 * @return array Assets for section
	 */
	public function get($section)
	{
		// Abort if section is not found.
		if ( ! isset($this->_assets[$section]))
		{
			return array();
		}

		$return = array();

		foreach ($this->_assets[$section] as $type => $assets)
		{
			foreach ($assets as $asset)
			{
				$wrapper = isset($asset['wrapper']) ? $asset['wrapper'] : array('', '');
				$return[] = $wrapper[0].$this->_format_asset($asset, $type).$wrapper[1];
			}
		}

		return $return;
	}

	/**
	 * USort function for sorting by weight.
	 * @param $a
	 * @param $b
	 * @return int
	 */
	private function _sort_assets($a, $b)
	{
		// Set weight to 0 if it's missing.
		(! isset($a['weight'])) AND $a['weight'] = 0;
		(! isset($b['weight'])) AND $b['weight'] = 0;

		return $a['weight'] - $b['weight'];
	}

	/**
	 * Generate the html for the asset, depending on if it is a script or style.
	 * @param array $asset
	 * @param string $type
	 * @return string
	 */
	private function _format_asset(array $asset, $type)
	{
		if ($type == 'style')
		{
			return HTML::style($asset['file']);
		}
		else if ($type == 'script')
		{
			return HTML::script($asset['file']);
		}
	}

}
