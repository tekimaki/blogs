{* $Header: /cvsroot/bitweaver/_bit_blogs/templates/blog_post.tpl,v 1.8 2005/10/29 17:51:57 squareing Exp $ *}
{literal}
<script type="text/javascript">
function confirmDelete(fileName, location) {
	if (confirm("Are you sure you want to delete" + fileName + "?")) {
		document.location = location;
	}
}
</script>
{/literal}

{strip}
<div class="edit blogs">
	<div class="header">
		<h1>{tr}Edit Post{/tr}</h1>
	</div>

	<div class="body">
		{if $preview eq 'y'}
			<h2>Preview {$title}</h2>
			<div class="preview">
				{include file="bitpackage:blogs/view_blog_post.tpl"}
			</div>
		{/if}

		{form enctype="multipart/form-data" name="blogpost" id="editpageform"}
			<input type="hidden" name="post_id" value="{$post_id|escape}" />
			<input type="hidden" name="blog_id" value="{$blog_id|escape}" />
			<input type="hidden" name="rows" value="{$rows}"/>
			<input type="hidden" name="cols" value="{$cols}"/>
			<input type="hidden" name="MAX_FILE_SIZE" value="1000000000" />

			{jstabs}
				{jstab title="Create Blog Post"}
					{legend legend="Post to a Blog"}
						{if $blogs}
							<div class="row">
								{formlabel label="Blog" for="blog_id"}
								{forminput}
									<select name="blog_id" id="blog_id">
										{section name=ix loop=$blogs}
											<option value="{$blogs[ix].blog_id|escape}" {if $blogs[ix].blog_id eq $blog_id}selected="selected"{/if}>{$blogs[ix].title}</option>
										{/section}
									</select>
								{/forminput}
							</div>
						{/if}

						{if !$blog_data.use_title OR $blog_data.use_title eq 'y'}
							<div class="row">
								{formlabel label="Title" for="title"}
								{forminput}
									<input type="text" size="50" name="title" id="title" value="{$title|escape}" />
								{/forminput}
							</div>
						{/if}

						{include file="bitpackage:liberty/edit_format.tpl"}

						{if $gBitSystem->isPackageActive( 'smileys' )}
							{include file="bitpackage:smileys/smileys_full.tpl"}
						{/if}

						{if $gBitSystem->isPackageActive( 'quicktags' ) eq 'y'}
							{include file="bitpackage:quicktags/quicktags_full.tpl"}
						{/if}

						<div class="row">
							{forminput}
								<textarea id="{$textarea_id}" name="edit" rows="{$rows|default:20}" cols="{$cols|default:50}">{$data}</textarea>
							{/forminput}
						</div>

						{include file="bitpackage:liberty/edit_services_inc.tpl serviceFile=content_edit_mini_tpl}

						{include file="bitpackage:liberty/edit_storage_list.tpl"}

						<div class="row submit">
							<input type="submit" name="preview" value="{tr}Preview{/tr}" />&nbsp;
							<input type="submit" name="save_post_exit" value="{tr}Save{/tr}" />
						</div>
					{/legend}
				{/jstab}

				{include file="bitpackage:liberty/edit_services_inc.tpl serviceFile=content_edit_tab_tpl}

				{jstab title="Attachments"}
					{legend legend="Attachments"}
						{include file="bitpackage:liberty/edit_storage.tpl"}
					{/legend}
				{/jstab}

				{jstab title="Advanced Options"}
					{legend legend="Advanced Options"}
						<div class="row">
							{formlabel label="Send trackback pings" for="trackback"}
							{forminput}
								<textarea name="trackback" id="trackback" rows="3" cols="50">{section name=ix loop=$trackbacks_to}{if not $smarty.section.ix.first},{/if}{$trackbacks_to[ix]}{/section}</textarea>
								{formhelp note="Insert a comma separated list of URIs to send blogs."}
							{/forminput}
						</div>
					{/legend}
				{/jstab}
			{/jstabs}
		{/form}

		<br /><br />
		{include file="bitpackage:liberty/edit_help_inc.tpl"}

	</div><!-- end .body -->
</div><!-- end .blogs -->
{/strip}
