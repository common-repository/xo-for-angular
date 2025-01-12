<?php

/**
 * Tab adding options related to the routing of posts to the Xo General Settings screen.
 *
 * @since 1.0.0
 */
class XoOptionsTabPosts extends XoOptionsAbstractSettingsTab
{
	/**
	 * Add the various settings sections for the Posts tab.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function Init() {
		$this->InitPostPageSection();
		$this->InitPostTemplateSection();
	}

	/**
	 * Settings section for configuring Post type roots.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function InitPostPageSection() {
		$this->AddSettingsSection(
			'post_page_section',
			__('Post Pages', 'xo'),
			__('Used to set the root page of a post type.', 'xo'),
			function ($section) {
				global $wp_post_types;

				foreach ($wp_post_types as $post_type => $post_type_config) {
					if ((!$post_type_config->public) || ($post_type == 'page'))
						continue;

					$this->AddPostPageSettingsField($section, $post_type, $post_type_config);
				}
			}
		);
	}

	/**
	 * Settings field for Post Page.
	 * Used when generating nested routes based on a page serving as the root of a custom post type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $section Name of the section.
	 * @param string $post_type Post type slug.
	 * @param WP_Post_Type $post_type_config Post type object.
	 * @return void
	 */
	private function AddPostPageSettingsField($section, $post_type, $post_type_config) {
		$this->AddSettingsField(
			$section,
			'xo_' . $post_type . '_page_id',
			sprintf(__('%s Page', 'xo'), $post_type_config->label),
			function ($option, $states, $value) {
				wp_dropdown_pages(
					array(
						'name' => $option,
						'show_option_none' => __('&mdash; None &mdash;', 'xo'),
						'option_none_value' => 0,
						'selected' => $value
					)
				);
			}
		);
	}

	/**
	 * Settings section for configuring Post type templates.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function InitPostTemplateSection() {
		$this->AddSettingsSection(
			'post_template_section',
			__('Post Templates', 'xo'),
			__('Used to set the default template for a post type.', 'xo'),
			function ($section) {
				global $wp_post_types;

				foreach ($wp_post_types as $post_type => $post_type_config) {
					if ((!$post_type_config->public) || ($post_type == 'page'))
						continue;

					$this->AddPostTemplateSettingsField($section, $post_type, $post_type_config);
				}
			}
		);
	}

	/**
	 * Settings field for Post Template.
	 * Used to set the template used for a single custom post type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $section Name of the section.
	 * @param string $post_type Post type slug.
	 * @param WP_Post_Type $post_type_config Post type object.
	 * @return void
	 */
	private function AddPostTemplateSettingsField($section, $post_type, $post_type_config) {
		$this->AddSettingsField(
			$section,
			'xo_' . $post_type . '_template',
			sprintf(__('%s Template', 'xo'), $post_type_config->label),
			function ($option, $states, $value) {
				$templates = array();
				$annotatedTemplates = $this->Xo->Services->TemplateReader->GetAnnotatedTemplates();

				foreach ($annotatedTemplates as $template => $attrs)
					$templates[$template] = $attrs['pageTemplate'];

				$this->GenerateSelectField(
					$option, array(), $templates,
					array(
						'value' => '',
						'name' =>  __('&mdash; None &mdash;', 'xo')
					),
					$value
				);
			}
		);
	}
}