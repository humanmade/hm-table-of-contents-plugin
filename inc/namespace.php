<?php
/**
 * Add anchors to headings in post content to generate table of contents.
 *
 * @package hm-toc
 * @since   0.1
 */

namespace HM\TOC;

use WP_Post;

/**
 * Register actions and filters.
 */
function bootstrap() {
	add_action( 'init', __NAMESPACE__ . '\\register_block' );
	add_action( 'rest_api_init', __NAMESPACE__ . '\\register_api_field' );
	add_filter( 'the_content', __NAMESPACE__ . '\\add_ids_to_content', -10 );
}

/**
 * Register block
 *
 * @return void
 */
function register_block(): void {
	$block_build_dir = dirname( __FILE__, 2 ) . '/block/build';
	if ( is_readable( $block_build_dir . '/block.json' ) ) {
		register_block_type( $block_build_dir );
	}
}

/**
 * Render heading list
 *
 * @param array $hierarchy Heading hierarchy.
 * @param int $max_level The maximum heading level to output.
 * @param int $current_level The current level being output.
 * @return void
 */
function the_heading_list( $hierarchy, $max_level = 3, $current_level = 1 ) {
	echo '<ul>';

	foreach ( $hierarchy as $heading ) {
		echo '<li>';

		if ( ! empty( $heading->href ) ) {
			printf( '<a href="%s">', $heading->href );
		}

		echo $heading->title;

		if ( $heading->href ) {
			echo '</a>';
		}

		if ( $heading->items && $max_level > $current_level ) {
			the_heading_list( $heading->items, $max_level, $current_level + 1 );
		}

		echo '</li>';
	}

	echo '</ul>';
}

/**
 * Register the `contents` REST field for pages.
 */
function register_api_field() {
	register_rest_field( 'page', 'contents', [
		'get_callback' => function ( $data ) {
			$post = get_post( $data['id'] );
			return get_header_hierarchy( $post );
		},
	] );
}

/**
 * Get the header hierarchy for a post.
 *
 * @param WP_Post $post Post to get hierarchy for.
 * @return stdClass[] List of top-level headings, with children attached.
 */
function get_header_hierarchy( WP_Post $post ) {
	$items = get_header_tags( $post->post_content );
	if ( empty( $items ) ) {
		return [];
	}

	$roll_up = function ( $level ) use ( &$levels ) {
		$cur_level = 4;
		while ( $cur_level > $level ) {
			if ( empty( $levels[ $cur_level ] ) ) {
				$cur_level--;
				continue;
			}

			$last = end( $levels[ $cur_level - 1 ] );
			if ( empty( $last ) ) {
				$last = (object) [
					'title' => '',
					'href'  => null,
					'items' => [],
				];
				$levels[ $cur_level - 1 ][] = $last;
			}

			array_push(
				$last->items,
				...$levels[ $cur_level ]
			);
			$levels[ $cur_level ] = [];
			$cur_level--;
		}
	};

	$root = (object) [
		'items' => [],
	];
	$levels = [
		0 => [ $root ],
		1 => [],
		2 => [],
		3 => [],
		4 => [],
	];

	$last_level = 0;
	foreach ( $items as $item ) {
		$prepared = (object) [
			'title' => wp_strip_all_tags( $item->title ),
			'href'  => '#' . $item->id,
			'items' => [],
		];
		if ( $item->level < $last_level ) {
			$roll_up( $item->level );
		}
		$last_level = $item->level;
		$levels[ $item->level ][] = $prepared;
	}

	// Roll up.
	$roll_up( 0 );

	// Remove empty top-levels if they're the only item.
	while ( count( $root->items ) === 1 && empty( $root->items[0]->href ) ) {
		$root->items = $root->items[0]->items;
	}

	return $root->items;
}

/**
 * Add IDs to post content.
 *
 * Adds IDs to headings, and inserts anchor links.
 *
 * @param string $content HTML content to add IDs to.
 * @return string Content with IDs and anchors added.
 */
function add_ids_to_content( $content ) {
	$items = get_header_tags( $content );
	if ( empty( $items ) ) {
		return $content;
	}

	// Reverse-sort by offset to aid replacement.
	usort( $items, function ( $a, $b ) {
		return $b->offset - $a->offset;
	} );

	$format = '<%1$s %2$s tabindex="-1">%3$s %4$s</%1$s>';

	foreach ( $items as $item ) {
		$tag = 'h' . $item->level;
		$class = trim( $item->class . ' toc-heading' );
		$id = $item->id;

		$anchor = sprintf( '<a href="#%1$s" class="anchor">#</a>', $id );

		/**
		 * Filter the anchor HTML added to each heading.
		 *
		 * @param string $anchor Generated anchor HTML.
		 * @param stdClass $item Header Item.
		 * @param string $content HTML content containing the header.
		 */
		$anchor = apply_filters( 'hm_toc.contents.anchor_html', $anchor, $item, $content );

		// Build attributes array.
		$attributes = [];
		foreach ( [ 'id', 'class', 'style' ] as $attribute ) {
			if ( ! empty( $item->$attribute ) ) {
				$attributes[] = sprintf( '%s="%s"', esc_attr( $attribute ), esc_attr( $item->$attribute ) );
			}
		}

		$replacement = sprintf(
			$format,
			$tag,
			implode( ' ', $attributes ),
			wp_kses_post( $item->title ),
			$anchor
		);

		/**
		 * Filter the HTML replacing the heading.
		 *
		 * @param string $replacement Generated replacement HTML.
		 * @param stdClass $item Header item.
		 * @param string $content HTML content containing the header.
		 * @param string $anchor HTML for jump anchor.
		 */
		$replacement = apply_filters( 'hm_toc.contents.replacement_html', $replacement, $item, $content, $anchor );

		$content = substr_replace( $content, $replacement, $item->offset, strlen( $item->html ) );
	}

	return $content;
}

/**
 * Get header tags from the content.
 *
 * Matches H1, H2, H3, and H4 tags from the provided HTML content.
 *
 * @param string $content HTML content to parse.
 * @return stdClass[] List of items (objects with `html`, `level`, `title`, `class`,
 *                    and `offset` keys).
 */
function get_header_tags( $content ) {
	preg_match_all( '/<h([1-4])[^>]*>(.*)<\/h([1-4])>/U', $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE );

	if ( empty( $matches ) ) {
		return [];
	}

	// Build titles and IDs.
	$items = [];
	$ids = [];
	foreach ( $matches as $raw_item ) {
		$item = (object) [
			'html'   => $raw_item[0][0],
			'level'  => (int) $raw_item[1][0],
			'title'  => $raw_item[2][0],
			'class'  => '',
			'offset' => $raw_item[0][1],
			'style'  => '',
		];

		$id = '';

		// If the item already has an ID, use that.
		if ( preg_match( '/id="([^"]*)"/', $item->html, $id_matches ) ) {
			$id = $id_matches[1];
		}

		// If an editor has specified a menu title, use that.
		if ( preg_match( '/data-menu-title="([^"]*)"/', $item->html, $menu_text_matches ) ) {
			$item->title = trim( $menu_text_matches[1] ?: $item->title );
		}

		// Ignore empty headings.
		if ( empty( $item->title ) ) {
			continue;
		}

		if ( empty( $id ) ) {
			// Build ID, and deduplicate.
			$id = $orig_id = sanitize_title_with_dashes( $item->title );
			$counter = 0;
			while ( isset( $ids[ $id ] ) && $counter < 100 ) {
				$counter ++;
				$id = sprintf( '%s-%d', $orig_id, $counter );
			}
			$ids[ $id ] = true;
		}

		$item->id = $id;

		if ( preg_match( '/class="([^"]*)"/', $item->html, $class_matches ) ) {
			$item->class = $class_matches[1];
		}

		if ( preg_match( '/style="([^"]*)"/', $item->html, $style_matches ) ) {
			$item->style = $style_matches[1];
		}

		$items[] = $item;
	}

	return $items;
}
