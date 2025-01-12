<?php

/**
 * Service class providing a repository of defaults and abstraction for option requests used throughout Xo.
 *
 * @since 1.0.0
 */
class XoServiceOptions
{
	/**
	 * @var Xo
	 */
	protected $Xo;

	/**
	 * Collection of options which override defaults or database configurations.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $overrides = array();

	public function __construct(Xo $Xo) {
		$this->Xo = $Xo;
		add_action('init', array($this, 'Init'), 10, 0);
	}

	public function Init() {
		$this->SetOverrides();
	}

	/**
	 * Set overrides from XO_SETTINGS if defined.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function SetOverrides() {
		if (!defined('XO_SETTINGS'))
		    return;

		if (!$settings = json_decode(XO_SETTINGS, true))
			return;

		if (!empty($settings['overrides']))
			$this->overrides = $settings['overrides'];
	}

	/**
	 * Get an option value using get_option filtered by xo/options/get/{{option_name}}.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Name of the option to get.
	 * @param mixed $value Default value if the option was not found.
	 * @return mixed Return value of the option.
	 */
	public function GetOption($name, $value = false) {
		if (isset($this->overrides[$name])) {
			$value = $this->overrides[$name];
		} else {
			$value = get_option($name, $value);
		}

		$value = apply_filters('xo/options/get/' . $name, $value);

		return $value;
	}

	/**
	 * Set an option using update_option filtered by xo/options/set/{{option_name}}.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Name of the option to set.
	 * @param mixed $value Value to set for the given option.
	 * @return bool Whether the option was updated.
	 */
	public function SetOption($name, $value = false) {
		$value = apply_filters('xo/options/set/' . $name, $value);

		return update_option($name, $value);
	}

	/**
	 * Get the default settings for Xo.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	protected function GetDefaultSettings() {
		$defaults = array(
			// Index Tab
			'xo_index_src' => '/src/index.html',
			'xo_index_dist' => '/dist/index.html',
			'xo_index_redirect_mode' => 'default',
			'xo_index_live_header' => false,
			'xo_index_live_footer' => false,
			'xo_index_live_config' => true,

			// Api Tab
			'xo_api_enabled' => true,
			'xo_api_endpoint' => '/xo-api',
			'xo_api_access_control_mode' => 'default',
			'xo_access_control_allowed_hosts' => 'localhost:4200',

			// Routing Tab
			'xo_routing_previews_enabled' => true,

			// Templates Tab
			'xo_templates_reader_enabled' => true,
			'xo_templates_cache_enabled' => true,
			'xo_templates_cache' => array(),
			'xo_templates_path' => '/src/app',

			// ACF Tab
			'xo_acf_allowed_groups' => array()
		);

		return $defaults;
	}

	/**
	 * Get all current settings in order: defaults, database, overrides.
	 *
	 * @since 1.0.0
	 *
	 * @return array All current settings.
	 */
	public function GetCurrentSettings() {
		$settings = array();
		$defaults = $this->GetDefaults();

		foreach ($defaults as $option => $value)
			$settings[$option] = $this->GetOption($option, $value);

		$settings = apply_filters('xo/options/settings', $settings);

		return $settings;
	}

	/**
	 * Get the defaults for Xo filtered by xo/options/defaults.
	 *
	 * @since 1.0.0
	 *
	 * @return mixed The defaults filtered by xo/options/defaults.
	 */
	public function GetDefaults() {
		$defaults = $this->GetDefaultSettings();

		if ($config = $this->GetOptionsFromJson())
			$defaults = array_merge($defaults, $config);

		$defaults = apply_filters('xo/options/defaults', $defaults);

		return $defaults;
	}

	/**
	 * Set the default options for Xo based on the current internal defaults.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether any options were set.
	 */
	public function SetDefaults() {
		$defaults = $this->GetDefaults();

		$setDefaults = false;
		foreach ($defaults as $option => $value)
			if (add_option($option, $value, '', true))
				$setDefaults = true;

		return $setDefaults;
	}

	/**
	 * Reset all options for Xo based on the current internal defaults.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether any options were set.
	 */
	public function ResetDefaults() {
		$defaults = $this->GetDefaults();

		$setOptions = false;
		foreach ($defaults as $option => $value)
			if (update_option($option, $value, true))
				$setOptions = true;

		return $setOptions;
	}

	/**
	 * Get the states of a given option filtered by xo/options/states/{{option_name}}.
	 *
	 * @since 1.0.0
	 *
	 * @param string $option Name of the option.
	 * @return array States of the given option.
	 */
	public function GetStates($option) {
		$states = array();

		if (isset($this->overrides[$option]))
			array_push($states, 'override');

		$states = apply_filters('xo/options/states/' . $option, $states);

		return $states;
	}

	/**
	 * Get options which may override the defaults by reading the angular.json file.
	 *
	 * @since 1.0.0
	 *
	 * @return bool|string[]
	 */
	protected function GetOptionsFromJson() {
		if (!$jsons = $this->Xo->Services->AngularJson->ParseConfig())
			return false;

		foreach ($jsons as $json) {
			$config = array();

			if (!empty($json['index'])) {
				$config['xo_index_src'] = '/' . ltrim($json['index']);

				if ((!empty($json['sourceRoot'])) && (!empty($json['outputPath']))) {
					$pos = strpos($json['index'], $json['sourceRoot']);
					if ($pos === 0)
						$config['xo_index_dist'] = '/' . ltrim($json['outputPath'] . substr($json['index'], strlen($json['sourceRoot'])));
				}
			}

			if (!empty($json['sourceRoot']))
				$config['xo_templates_path'] = '/' . ltrim($json['sourceRoot'], '/')
					. ((!empty($json['prefix'])) ? '/' . $json['prefix'] : '');

			if ($config)
				return $config;
		}

		return false;
	}
}