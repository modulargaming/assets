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
		foreach ($assets as $section => $types)
		{
			foreach (array_keys($types) as $type)
			{
				usort($assets[$section][$type], array($this, '_sort_assets'));
			}
		}

		if (KOHANA::$environment >= KOHANA::TESTING)
		{
			$assets = $this->_minify_assets($assets);
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
		return Arr::get($a, 'weight', 0) - Arr::get($b, 'weight', 0);
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

	/**
	 * Minifies assets.
	 *
	 * @param $assets
	 * @return mixed
	 */
	private function _minify_assets($assets)
	{
		$benchmark = Profiler::start('Assets', __FUNCTION__);

		foreach ($assets as $section => &$types)
		{


			foreach ($types as $type => &$assets2)
			{
				$array = array();
				$minify = array();
				$last_key = NULL;

				foreach ($assets2 as $key => $asset)
				{

					// Should the asset be minified?
					if (Arr::get($asset, 'minify', FALSE) === TRUE)
					{
						$minify[] = $asset;
					}
					else
					{
						// Do we have a pending minify?
						if ( ! empty($minify))
						{
							$array[] = $this->_generate_minified($type, $minify);
							$minify = array();
						}

						$array[] = $asset;
					}
				}

				// Do we have a pending minify?
				if ( ! empty($minify))
				{
					$array[] = $this->_generate_minified($type, $minify);
				}

				$assets2 = $array;
			}
		}

		Profiler::stop($benchmark);

		return $assets;
	}

	private function _generate_minified($type, array $assets)
	{
		$content = '';
		$filename = '';

		$ext = '.js';
		if ($type == 'style')
		{
			$ext = '.css';
		}

		foreach ($assets as $asset)
		{
			$filename .= $asset['file'];
			$f = Kohana::find_file(NULL, $asset['file'], FALSE);

			if (file_exists($f))
			{
				$content .= file_get_contents($f);
			}

			/* TODO: Do we want to support external resource minification?
			// This can be quite dangerous as relative paths would break.
			$filename .= $asset['file'];
			$request = Request::factory(URL::site($asset['file'], TRUE));

			$response = $request->execute();

			if ($response->status() == 200)
			{
				$content .= $response->body();
			}
			*/
		}

		$filename = sha1($filename).$ext;

		$dir = DOCROOT.'assets/css'.DIRECTORY_SEPARATOR;
		file_put_contents($dir.$filename, $content, LOCK_EX);

		return array(
			'file' => 'assets/css/'.$filename
		);
	}

}

// Load Minify
// require Kohana::find_file('vendor/minify', 'min/lib/Minify/Loader');
