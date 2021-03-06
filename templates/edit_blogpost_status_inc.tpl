{if $gBitSystem->isFeatureActive( 'liberty_display_status' ) && $gBitSystem->isFeatureActive( 'liberty_display_status_menu' ) && ($gBitUser->hasPermission('p_liberty_edit_content_status') || $gBitUser->hasPermission('p_liberty_edit_all_status'))}
	<div class="row">
		{formlabel label="Publish Status" for="content_status_id"}
		{forminput}
			{html_options name="content_status_id" options=$gContent->getAvailableContentStatuses() selected=$gContent->getField('content_status_id',$smarty.const.BIT_CONTENT_DEFAULT_STATUS)}
			{formhelp note="Select the appropriate status for your posting. To automatically publish your posting in the future, choose a publicly visible Publish Status and set the publish date ahead (see Advanded Options)."}
		{/forminput}
	</div>
{/if}
