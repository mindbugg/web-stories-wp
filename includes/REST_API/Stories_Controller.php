<?php
/**
 * Class Stories_Controller
 *
 * @package   Google\Web_Stories
 * @copyright 2020 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link      https://github.com/google/web-stories-wp
 */

/**
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\Web_Stories\REST_API;

use Google\Web_Stories\KSES;
use Google\Web_Stories\Settings;
use Google\Web_Stories\Story_Post_Type;
use Google\Web_Stories\Traits\Publisher;
use WP_Query;
use WP_Error;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Stories_Controller class.
 */
class Stories_Controller extends Stories_Base_Controller {
	use Publisher;
	/**
	 * Default style presets to pass if not set.
	 */
	const EMPTY_STYLE_PRESETS = [
		'colors'     => [],
		'textStyles' => [],
	];

	/**
	 * Prepares a single story output for response. Add post_content_filtered field to output.
	 *
	 * @param WP_Post         $post Post object.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $post, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		// $_GET param is available when the response iss preloaded in edit-story.php
		if ( isset( $_GET['web-stories-demo'] ) && 'edit' === $context && 'auto-draft' === $post->post_status ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$demo_content = $this->get_demo_content();
			if ( ! empty( $demo_content ) ) {
				$post->post_title            = __( 'Tips to make the most of Web Stories', 'web-stories' );
				$post->post_content_filtered = $demo_content;
			}
		}

		$response = parent::prepare_item_for_response( $post, $request );
		$fields   = $this->get_fields_for_response( $request );
		$data     = $response->get_data();

		if ( in_array( 'publisher_logo_url', $fields, true ) ) {
			$data['publisher_logo_url'] = $this->get_publisher_logo();
		}

		if ( in_array( 'style_presets', $fields, true ) ) {
			$style_presets         = get_option( Story_Post_Type::STYLE_PRESETS_OPTION, self::EMPTY_STYLE_PRESETS );
			$data['style_presets'] = is_array( $style_presets ) ? $style_presets : self::EMPTY_STYLE_PRESETS;
		}


		$data  = $this->filter_response_by_context( $data, $context );
		$links = $response->get_links();

		$response = new WP_REST_Response( $data );
		foreach ( $links as $rel => $rel_links ) {
			foreach ( $rel_links as $link ) {
				$response->add_link( $rel, $link['href'], $link['attributes'] );
			}
		}

		/**
		 * Filters the post data for a response.
		 *
		 * The dynamic portion of the hook name, `$this->post_type`, refers to the post type slug.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WP_Post $post Post object.
		 * @param WP_REST_Request $request Request object.
		 */
		return apply_filters( "rest_prepare_{$this->post_type}", $response, $post, $request );
	}

	/**
	 * Pre-fills story with demo content.
	 *
	 * @return string Pre-filled post content if applicable, or the default content otherwise.
	 */
	private function get_demo_content() {
		$file = WEBSTORIES_PLUGIN_DIR_PATH . 'includes/data/stories/demo.json';

		if ( ! is_readable( $file ) ) {
			return '';
		}

		$file_content = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown

		if ( ! $file_content ) {
			return '';
		}

		// Make everything localizable.

		// TODO: Replace image URLs, and clean up demo story.

		$kses = new KSES();
		$kses->init();

		// Page 1.

		$file_content = str_replace(
			'L10N_PLACEHOLDER_1_1',
			addslashes(
				wp_kses(
					/* translators: demo content used in the "Get Started" story */
					_x( '<span style="font-weight: 700; color: #fff">Tips </span><span style="font-weight: 100; color: #fff">to make the most of</span>', 'demo content', 'web-stories' ),
					[
						'span' => [ 'style' => [] ],
					]
				)
			),
			$file_content
		);

		// Page 2.

		$file_content = str_replace(
			'L10N_PLACEHOLDER_2_1',
			/* translators: demo content used in the "Get Started" story */
			esc_html_x( 'SET A PAGE BACKGROUND', 'demo content', 'web-stories' ),
			$file_content
		);

		$file_content = str_replace(
			'L10N_PLACEHOLDER_2_2',
			/* translators: demo content used in the "Get Started" story */
			esc_html_x( 'Drag your image or video to the edge of the page to set as page background.', 'demo content', 'web-stories' ),
			$file_content
		);

		// Page 3.

		$file_content = str_replace(
			'L10N_PLACEHOLDER_3_1',
			/* translators: demo content used in the "Get Started" story */
			esc_html_x( 'BACKGROUND OVERLAY', 'demo content', 'web-stories' ),
			$file_content
		);

		$file_content = str_replace(
			'L10N_PLACEHOLDER_3_2',
			/* translators: demo content used in the "Get Started" story */
			esc_html_x( 'Once you\'ve set a page background, add a solid, linear or radial gradient overlay to increase text contrast or add visual styles.', 'demo content', 'web-stories' ),
			$file_content
		);

		// Page 4.

		$file_content = str_replace(
			'L10N_PLACEHOLDER_4_1',
			/* translators: demo content used in the "Get Started" story */
			esc_html_x( 'SAFE ZONE', 'demo content', 'web-stories' ),
			$file_content
		);

		$file_content = str_replace(
			'L10N_PLACEHOLDER_4_2',
			/* translators: demo content used in the "Get Started" story */
			esc_html_x( 'Add your designs to the page, keeping crucial elements inside the safe zone (tick marks) to ensure they are visible across most devices.', 'demo content', 'web-stories' ),
			$file_content
		);

		// Page 5.

		$file_content = str_replace(
			'L10N_PLACEHOLDER_5_1',
			/* translators: demo content used in the "Get Started" story */
			esc_html_x( 'STORY SYSTEM LAYER', 'demo content', 'web-stories' ),
			$file_content
		);

		$file_content = str_replace(
			'L10N_PLACEHOLDER_5_2',
			addslashes(
				wp_kses(
				/* translators: demo content used in the "Get Started" story */
					_x( '<span style="font-weight: 200; color: #fff">The story system layer is docked at the top. </span><span style="font-weight: 500; color: #fff">Preview your story</span><span style="font-weight: 200; color: #fff"> to ensure system layer icons are not blocking crucial elements.</span>', 'demo content', 'web-stories' ),
					[
						'span' => [ 'style' => [] ],
					]
				)
			),
			$file_content
		);

		// Page 6.

		$file_content = str_replace(
			'L10N_PLACEHOLDER_6_1',
			/* translators: demo content used in the "Get Started" story */
			esc_html_x( 'SHAPE + MASKING', 'demo content', 'web-stories' ),
			$file_content
		);

		$file_content = str_replace(
			'L10N_PLACEHOLDER_6_2',
			/* translators: demo content used in the "Get Started" story */
			esc_html_x( 'Our shapes are quite basic for now but they act as masks. Drag an image or video into the mask to place.', 'demo content', 'web-stories' ),
			$file_content
		);

		// Page 7.

		$file_content = str_replace(
			'L10N_PLACEHOLDER_7_1',
			/* translators: demo content used in the "Get Started" story */
			esc_html_x( 'EMBED VISUAL STORIES', 'demo content', 'web-stories' ),
			$file_content
		);

		$file_content = str_replace(
			'L10N_PLACEHOLDER_7_2',
			/* translators: demo content used in the "Get Started" story */
			esc_html_x( 'Embed stories into your blog post. Open the block menu & select the Web Stories block. Insert the story link to embed your story. That\'s it!', 'demo content', 'web-stories' ),
			$file_content
		);

		// Page 8.

		$file_content = str_replace(
			'L10N_PLACEHOLDER_8_1',
			/* translators: demo content used in the "Get Started" story */
			esc_html_x( 'READ ABOUT BEST PRACTICES FOR CREATING A SUCCESSFUL WEB STORY', 'demo content', 'web-stories' ),
			$file_content
		);

		$file_content = str_replace(
			'L10N_PLACEHOLDER_8_2',
			/* translators: demo content used in the "Get Started" story */
			esc_html_x( 'https://amp.dev/documentation/guides-and-tutorials/start/create_successful_stories/', 'demo content', 'web-stories' ),
			$file_content
		);

		$file_content = str_replace(
			'L10N_PLACEHOLDER_8_3',
			/* translators: demo content used in the "Get Started" story */
			esc_html_x( 'Best practices for creating a successful Web Story', 'demo content', 'web-stories' ),
			$file_content
		);

		$kses->remove_filters();

		return $file_content;
	}

	/**
	 * Updates a single post.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {
		$response = parent::update_item( $request );

		if ( is_wp_error( $response ) ) {
			return rest_ensure_response( $response );
		}

		// If publisher logo is set, let's assign that.
		$publisher_logo_id = $request->get_param( 'publisher_logo' );
		if ( $publisher_logo_id ) {
			$all_publisher_logos   = get_option( Settings::SETTING_NAME_PUBLISHER_LOGOS );
			$all_publisher_logos[] = $publisher_logo_id;

			update_option( Settings::SETTING_NAME_PUBLISHER_LOGOS, array_unique( $all_publisher_logos ) );
			update_option( Settings::SETTING_NAME_ACTIVE_PUBLISHER_LOGO, $publisher_logo_id );
		}

		// If style presets are set.
		$style_presets = $request->get_param( 'style_presets' );
		if ( is_array( $style_presets ) ) {
			update_option( Story_Post_Type::STYLE_PRESETS_OPTION, $style_presets );
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Retrieves the story's schema, conforming to JSON Schema.
	 *
	 * @return array Item schema as an array.
	 */
	public function get_item_schema() {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$schema = parent::get_item_schema();

		$schema['properties']['publisher_logo_url'] = [
			'description' => __( 'Publisher logo URL.', 'web-stories' ),
			'type'        => 'string',
			'context'     => [ 'views', 'edit' ],
			'format'      => 'uri',
			'default'     => '',
		];

		$schema['properties']['style_presets'] = [
			'description' => __( 'Style presets used by all stories', 'web-stories' ),
			'type'        => 'object',
			'context'     => [ 'view', 'edit' ],
		];

		$schema['properties']['status']['enum'][] = 'auto-draft';

		$this->schema = $schema;

		return $this->add_additional_fields_schema( $this->schema );
	}

	/**
	 * Filters query clauses to sort posts by the author's display name.
	 *
	 * @param string[] $clauses Associative array of the clauses for the query.
	 * @param WP_Query $query   The WP_Query instance.
	 *
	 * @return array Filtered query clauses.
	 */
	public function filter_posts_clauses( $clauses, $query ) {
		global $wpdb;

		if ( Story_Post_Type::POST_TYPE_SLUG !== $query->get( 'post_type' ) ) {
			return $clauses;
		}
		if ( 'story_author' !== $query->get( 'orderby' ) ) {
			return $clauses;
		}

		// phpcs:disable WordPressVIPMinimum.Variables.RestrictedVariables.user_meta__wpdb__users
		$order              = $query->get( 'order' );
		$clauses['join']   .= " LEFT JOIN {$wpdb->users} ON {$wpdb->posts}.post_author={$wpdb->users}.ID";
		$clauses['orderby'] = "{$wpdb->users}.display_name $order, " . $clauses['orderby'];
		// phpcs:enable WordPressVIPMinimum.Variables.RestrictedVariables.user_meta__wpdb__users

		return $clauses;
	}

	/**
	 * Retrieves a collection of web stories.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		add_filter( 'posts_clauses', [ $this, 'filter_posts_clauses' ], 10, 2 );
		$response = parent::get_items( $request );
		remove_filter( 'posts_clauses', [ $this, 'filter_posts_clauses' ], 10 );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( 'edit' !== $request['context'] ) {
			return $response;
		}

		// Retrieve the list of registered collection query parameters.
		$registered = $this->get_collection_params();
		$args       = [];

		/*
		 * This array defines mappings between public API query parameters whose
		 * values are accepted as-passed, and their internal WP_Query parameter
		 * name equivalents (some are the same). Only values which are also
		 * present in $registered will be set.
		 */
		$parameter_mappings = [
			'author'         => 'author__in',
			'author_exclude' => 'author__not_in',
			'exclude'        => 'post__not_in',
			'include'        => 'post__in',
			'menu_order'     => 'menu_order',
			'offset'         => 'offset',
			'order'          => 'order',
			'orderby'        => 'orderby',
			'page'           => 'paged',
			'parent'         => 'post_parent__in',
			'parent_exclude' => 'post_parent__not_in',
			'search'         => 's',
			'slug'           => 'post_name__in',
			'status'         => 'post_status',
		];

		/*
		 * For each known parameter which is both registered and present in the request,
		 * set the parameter's value on the query $args.
		 */
		foreach ( $parameter_mappings as $api_param => $wp_param ) {
			if ( isset( $registered[ $api_param ], $request[ $api_param ] ) ) {
				$args[ $wp_param ] = $request[ $api_param ];
			}
		}

		// Check for & assign any parameters which require special handling or setting.
		$args['date_query'] = [];

		// Set before into date query. Date query must be specified as an array of an array.
		if ( isset( $registered['before'], $request['before'] ) ) {
			$args['date_query'][0]['before'] = $request['before'];
		}

		// Set after into date query. Date query must be specified as an array of an array.
		if ( isset( $registered['after'], $request['after'] ) ) {
			$args['date_query'][0]['after'] = $request['after'];
		}

		// Ensure our per_page parameter overrides any provided posts_per_page filter.
		if ( isset( $registered['per_page'] ) ) {
			$args['posts_per_page'] = $request['per_page'];
		}

		// Force the post_type argument, since it's not a user input variable.
		$args['post_type'] = $this->post_type;

		/**
		 * Filters the query arguments for a request.
		 *
		 * Enables adding extra arguments or setting defaults for a post collection request.
		 *
		 * @link https://developer.wordpress.org/reference/classes/wp_query/
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request used.
		 */
		$args       = apply_filters( "rest_{$this->post_type}_query", $args, $request );
		$query_args = $this->prepare_items_query( $args, $request );

		$taxonomies = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), [ 'show_in_rest' => true ] );

		if ( ! empty( $request['tax_relation'] ) ) {
			$query_args['tax_query'] = [ 'relation' => $request['tax_relation'] ]; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		foreach ( $taxonomies as $taxonomy ) {
			$base        = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;
			$tax_exclude = $base . '_exclude';

			if ( ! empty( $request[ $base ] ) ) {
				$query_args['tax_query'][] = [
					'taxonomy'         => $taxonomy->name,
					'field'            => 'term_id',
					'terms'            => $request[ $base ],
					'include_children' => false,
				];
			}

			if ( ! empty( $request[ $tax_exclude ] ) ) {
				$query_args['tax_query'][] = [
					'taxonomy'         => $taxonomy->name,
					'field'            => 'term_id',
					'terms'            => $request[ $tax_exclude ],
					'include_children' => false,
					'operator'         => 'NOT IN',
				];
			}
		}

		// Add counts for other statuses.
		$statuses = [
			'all'     => [ 'publish', 'draft', 'future' ],
			'publish' => 'publish',
			'future'  => 'future',
			'draft'   => 'draft',
		];

		$statuses_count = [];

		// Strip down query for speed.
		$query_args['fields']                 = 'ids';
		$query_args['posts_per_page']         = 1;
		$query_args['update_post_meta_cache'] = false;
		$query_args['update_post_term_cache'] = false;

		foreach ( $statuses as $key => $status ) {
			$posts_query               = new WP_Query();
			$query_args['post_status'] = $status;
			$posts_query->query( $query_args );
			$statuses_count[ $key ] = absint( $posts_query->found_posts );
		}

		// Encode the array as headers do not support passing an array.
		$encoded_statuses = wp_json_encode( $statuses_count );
		if ( $encoded_statuses ) {
			$response->header( 'X-WP-TotalByStatus', $encoded_statuses );
		}
		if ( $request['_web_stories_envelope'] ) {
			$response = rest_get_server()->envelope_response( $response, false );
		}
		return $response;
	}

	/**
	 * Retrieves the query params for the posts collection.
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();

		$query_params['_web_stories_envelope'] = [
			'description' => __( 'Envelope request for preloading.', 'web-stories' ),
			'type'        => 'boolean',
			'default'     => false,
		];

		return $query_params;
	}
}
