<?php

/**
 * Provide endpoints for retrieving, searching, and listing various post types.
 *
 * @since 1.0.0
 */
class XoApiControllerPosts extends XoApiAbstractIndexController
{
	/**
	 * Get a post using either the relative URL or postId.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $params Request object
	 * @return XoApiAbstractPostsGetResponse
	 */
	public function Get($params) {
		// Return an error if the url is missing
		if ((empty($params['postId'])) && (empty($params['url'])))
			return new XoApiAbstractPostsGetResponse(false, __('Missing post id or url.', 'xo'));

		$post = false;

		if (!empty($params['url'])) {
			$postId = 0;

			// Check if the url is for the home page
			if ($params['url'] == '/')
				$postId = intval($this->Xo->Services->Options->GetOption('page_on_front', 0));

			// Translate the url to a post id
			if (!$postId)
				$postId = url_to_postid($params['url']);

			// Get the wordpress post object if the post was found
			if ($postId)
				$post = get_post($postId);

			// Attempt to get the page by the url
			if (!$post)
				$post = get_page_by_path($params['url']);
		} else {
			$post = get_post($params['postId']);
		}

		// Return an error if the post was not found
		if ((!$post) || (is_wp_error($post)))
			return new XoApiAbstractPostsGetResponse(false, __('Unable to locate post.', 'xo'));

		// Return an error if the post is not published
		if ($post->post_status != 'publish')
			return new XoApiAbstractPostsGetResponse(false, __('The selected post is not published.', 'xo'));

		// Return success and the fully formed post object
		return new XoApiAbstractPostsGetResponse(
			true, __('Successfully located post.', 'xo'),
			new XoApiAbstractPost($post, true, true, true)
		);
	}

	/**
	 * Get a post draft or preview only if user logged in and can view.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $params Request object
	 * @return XoApiAbstractPostsGetResponse
	 */
	public function GetDraftOrPreview($params) {
		// Return an error if the user is not logged in or not an editor
		if (!current_user_can('edit_others_pages'))
		    return new XoApiAbstractPostsGetResponse(false, __('Current user is not an editor.', 'xo'));

		// Return an error if the postId is missing
		if (empty($params['postId']))
			return new XoApiAbstractPostsGetResponse(false, __('Missing post id.', 'xo'));

		// Return an error if the post could not be found
		if ((!$post = get_post($params['postId'])) || (is_wp_error($post)))
			return new XoApiAbstractPostsGetResponse(false, __('Unable to locate post.', 'xo'));

		// Return an error if the post does not have an autosave
		if (($post->post_status == 'publish') &&
			(!$post = wp_get_post_autosave($params['postId'], get_current_user_id())))
			return new XoApiAbstractPostsGetResponse(false, __('Unable to locate post autosave.', 'xo'));

		// Return success and the fully formed post object
		return new XoApiAbstractPostsGetResponse(
			true, __('Successfully located post.', 'xo'),
			new XoApiAbstractPost($post, true, true, true)
		);
	}

	/**
	 * Filter, search, or list posts by various properties similar to get_posts().
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $params Request object
	 * @return XoApiAbstractPostsFilterResponse
	 */
	public function Filter($params) {
		// Collect search vars
		$search = ((!empty($params['search'])) ? $params['search'] : null);
		$postType = ((!empty($params['postType']))
			? $params['postType']
			: (($search) ? get_post_types(array('public' => true)) : 'post'));
		$curPage = ((!empty($params['currentPage'])) ? $params['currentPage'] : 1);
		$perPage = intval(((!empty($params['postsPerPage']))
			? $params['postsPerPage']
			: $this->Xo->Services->Options->GetOption('posts_per_page')));
		$offset = (($perPage) ? (($curPage - 1) * $perPage) : 0);
		$orderBy = ((!empty($params['orderby'])) ? $params['orderby'] : '');

		// Construct base arguments for get posts$default_posts_per_page = get_option( 'posts_per_page' );
		$baseargs = array(
			'post_status' => 'publish',
			'post_type' => $postType,
			'posts_per_page' => -1,
			'order' => ((!empty($params['order'])) ? $params['order'] : 'DESC'),
			'orderby' => (($orderBy != 'weight') ? $orderBy : ''),
			'fields' => 'ids'
		);

		// Add included post to start of collection
		$postids = array();
		if (!empty($params['include']))
			$postids = get_posts(array_merge($baseargs, array(
				'post_name__in' => ((is_array($params['include'])) ? $params['include'] : array($params['include']))
			)));

		// Get collection of excluded posts
		$excludeids = $postids;
		if (!empty($params['exclude']))
			$excludeids = array_merge($excludeids, get_posts(array_merge($baseargs, array(
				'post_name__in' => ((is_array($params['exclude'])) ? $params['exclude'] : array($params['exclude']))
			))));

		// Add name query to base arguments
		$baseargs['name'] = ((!empty($params['name'])) ? $params['name'] : null);

		// Add taxonomy query to base arguments
		$baseargs['tax_query'] = ((!empty($params['taxQuery'])) ? $params['taxQuery'] : null);

		// Add meta query to base arguments
		$baseargs['meta_query'] = ((!empty($params['metaQuery'])) ? $params['metaQuery'] : null);

		// Get posts from search
		if ($search) {
			$keywords = explode(' ', trim($search));

			if (count($keywords)) {
				for ($i = (count($keywords) - 1); $i >= 0; $i--) {
					$postids = array_merge($postids, get_posts(array_merge($baseargs, array(
						's' => $keywords[$i],
						'post__not_in' => array_merge($excludeids, $postids)
					))));
				}
			}

		// Otherwise get all posts for the current parameters
		} else {
			$postids = array_merge($postids, get_posts(array_merge($baseargs, array(
			    'post__not_in' => array_merge($excludeids, $postids)
			))));
		}

		// Return an error if no posts were found
		if (empty($postids))
			return new XoApiAbstractPostsFilterResponse(false, __('Unable to locate posts.', 'xo'));

		// Get the wordpress post objects for the collected post ids
		$posts = get_posts(array(
			'post_type' => $postType,
			'posts_per_page' => $perPage,
			'offset' => $offset,
			'post__in' => $postids,
			'orderby' => 'post__in'
		));

		// Iterate through posts and get the fully formed post objects
		$results = array();
		foreach ($posts as $post)
			$results[] = new XoApiAbstractPost($post, true, true, true);

		// Get a count of the total available posts
		$total = 0;
		if (is_array($postType)) {
			// Iterate through post types and add count to total
			foreach ($postType as $type) {
				$count = wp_count_posts($type);
				$total += $count->publish;
			}
		} else {
			// Get the count of a singular post type
			$count = wp_count_posts($postType);
			$total = $count->publish;
		}

		// Return success and collection of fully formed post objects
		return new XoApiAbstractPostsFilterResponse(
			true, __('Successfully located posts.', 'xo'),
			$results,
			count($results),
			$total
		);
	}

	/**
	 * Retrieve the config for a given post type.
	 *
	 * @since 1.0.4
	 *
	 * @param mixed $params Request object
	 * @return XoApiAbstractPostsConfigResponse
	 */
	public function Config($params) {
		// Return an error if the post type is missing
		if (empty($params['postType']))
			return new XoApiAbstractPostsConfigResponse(false, __('Missing post id.', 'xo'));

		// Get the post type config object
		$postTypeConfig = get_post_type_object($params['postType']);

		// Return an error if the post type is not found
		if (empty($postTypeConfig))
			return new XoApiAbstractPostsConfigResponse(false, __('Post type not found.', 'xo'));

		// Return an error if the post type is not public
		if (!$postTypeConfig->public)
			return new XoApiAbstractPostsConfigResponse(false, __('Post type not found.', 'xo'));

		// Return success and the post type config
		return new XoApiAbstractPostsConfigResponse(
			true, __('Successfully located post type config.', 'xo'),
			$postTypeConfig
		);
	}
}