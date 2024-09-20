<?php

namespace HM\TOC;

$post_id = isset( $attributes['postId'] ) ? $attributes['postId'] : ( $block->context['postId'] ?? null );
$post = get_post( $post_id );

if ( ! $post ) {
	return;
}

$hierarchy = get_header_hierarchy( $post );

if ( empty( $hierarchy ) ) {
	return;
}

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'hm-table-of-contents' ) );

printf(
	'<div %s>',
	$wrapper_attributes, // @codingStandardsIgnoreLine WordPress.Security.EscapeOutput.OutputNotEscaped
);

printf( '<h2>%s</h2>', esc_html__( 'Table of contents', 'hm-table-of-contents' ) );
the_heading_list( $hierarchy, $attributes['maxLevel'] ?? 3 );

echo '</div>';
