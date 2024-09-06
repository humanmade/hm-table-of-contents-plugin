import { useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: ({ attributes, context }) => {
		const blockProps = useBlockProps();
		return (
			<div { ...blockProps }>
				<ServerSideRender
					block={ metadata.name }
					attributes={ {
						postId: context.postId,
						...attributes
					 } }
				/>
			</div>
		);
	},
});
