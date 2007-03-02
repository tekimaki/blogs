<?php

global $gBitSystem, $gUpgradeFrom, $gUpgradeTo, $gBitDb;

require_once( BLOGS_PKG_PATH.'BitBlogPost.php' );

$upgrades = array(

	'BWR1' => array(
		'BWR2' => array(
array( 'DATADICT' => array(
	array( 'RENAMECOLUMN' => array(
		'tiki_blogs' => array(
			'`public`' => '`is_public` C(1)',
		),
	)),
	array( 'ALTER' => array(
		'tiki_blogs' => array(
			'content_id' => array( '`content_id`', 'I4' ), // , 'NOTNULL' ),
		),
		'tiki_blog_posts' => array(
			'publish_date' => array( '`publish_date`', 'I4' ),
			'expire_date' => array( '`expire_date`', 'I4' ),
		),
	)),
	// de-tikify tables
	array( 'RENAMETABLE' => array(
		'tiki_blogs' => 'blogs',
		'tiki_blog_activity' => 'blog_activity',
		'tiki_blog_posts' => 'blog_posts',
	)),
	array( 'RENAMESEQUENCE' => array(
		"tiki_blog_posts_post_id_seq" => "blog_posts_post_id_seq",
	)),
)),


// update blogs with content_id's
array( 'PHP' => '
	global $gBitSystem;
	$query = "SELECT * FROM `'.BIT_DB_PREFIX.'blogs` b";
	if( $rs = $gBitSystem->mDb->query( $query ) ) {
		while( !$rs->EOF ) {
			$contentHash = array();
			$blogId = $rs->fields["blog_id"];
			$conId = $gBitDb->GenID( "liberty_content_id_seq" );
error_log( $conId."->".$blogId );			
			$contentHash["content_id"] = $conId;
			$contentHash["content_type_guid"] = BITBLOG_CONTENT_TYPE_GUID;
			$contentHash["user_id"] = $rs->fields["user_id"];
			$contentHash["format_guid"] = PLUGIN_GUID_TIKIWIKI;
			$contentHash["data"] = $rs->fields["description"];
			$contentHash["title"] = substr( $rs->fields["title"], 0, 160 );
			$contentHash["created"] = $rs->fields["created"];
			$contentHash["last_modified"] = $rs->fields["last_modified"];
			$gBitSystem->mDb->associateInsert( "liberty_content", $contentHash );
			$hitsHash = array();
			$hitsHash["hits"] = $rs->fields["hits"];
			$hitsHash["content_id"] = $conId;
			$gBitSystem->mDb->associateInsert( "liberty_content_hits", $hitsHash );
			$gBitSystem->mDb->query( "UPDATE `'.BIT_DB_PREFIX.'blogs` SET `content_id`=? WHERE `blog_id`=? ", array( $conId, $blogId ) );
			$rs->MoveNext();
		}
	}
' ),

// Drop moved columns
array( 'DATADICT' => array(
	array( 'DROPCOLUMN' => array(
		'blogs' => array( '`user_id`', '`description`', '`created`', '`last_modified`', '`hits`', '`title`', '`heading`' ),
	)),
)),


		)
	),

'TIKIWIKI19' => array (
	'TIKIWIKI18' => array (
/* Sliced and diced TW 1.9 upgrade scripts that did actual schema alterations

ALTER TABLE `tiki_blog_posts` ADD `priv` VARCHAR( 1 );
ALTER TABLE `tiki_blogs` ADD `show_avatar` char(1) default NULL;
ALTER TABLE tiki_blog_posts MODIFY data_size int(11) unsigned NOT NULL default '0';

*/
	)
),

'BONNIE' => array(
	'BWR1' => array(

// STEP 1
array( 'DATADICT' => array(
array( 'RENAMECOLUMN' => array(
	'tiki_blog_activity' => array( '`blogId`' => '`blog_id` I4' ),
	'tiki_blog_posts' => array( '`postId`' => '`post_id` I4 AUTO' ,
								'`blogId`' => '`blog_id` I4' ),
	'tiki_blogs' => array( '`blogId`' => '`blog_id` I4 AUTO',
						   '`lastModif`' => '`last_modified` I8',
						   '`maxPosts`' => '`max_posts` I4' ),
)),


array( 'ALTER' => array(
	'tiki_blog_posts' => array(
		'user_id' => array( '`user_id`', 'I4' ), // , 'NOTNULL' ),
		'content_id' => array( '`content_id`', 'I4' ), // , 'NOTNULL' ),
	),
	'tiki_blogs' => array(
		'user_id' => array( '`user_id`', 'I4' ), // , 'NOTNULL' ),
	),
)),
/*
blog_posts` SET user_id=(SELECT uu.user_id FROM users_users uu WHERE tiki_blog_posts.`user`=uu.login)",
"UPDATE `".BIT_DB_PREFIX."tiki_blogs` SET `user_id`=(SELECT `user_id` FROM `".BIT_DB_PREFIX."users_users` WHERE `user`=login)",
"ALTER TABLE `".BIT_DB_PREFIX."tiki_blogs` DROP `user`"
		)
*/
)),

// STEP 2
array( 'QUERY' =>
	array( 'SQL92' => array(
	"UPDATE `".BIT_DB_PREFIX."tiki_blogs` SET `user_id`=(SELECT `user_id` FROM `".BIT_DB_PREFIX."users_users` WHERE `".BIT_DB_PREFIX."users_users`.`login`=`".BIT_DB_PREFIX."tiki_blogs`.`user`)",
	"UPDATE `".BIT_DB_PREFIX."tiki_blog_posts` SET `user_id`=(SELECT `user_id` FROM `".BIT_DB_PREFIX."users_users` WHERE `".BIT_DB_PREFIX."users_users`.`login`=`".BIT_DB_PREFIX."tiki_blog_posts`.`user`)",
	"UPDATE `".BIT_DB_PREFIX."tiki_preferences` set `value`='last_modified_desc' where `name`='blog_list_order'",
	"UPDATE `".BIT_DB_PREFIX."tiki_categorized_objects` SET `object_type`='".BITBLOGPOST_CONTENT_TYPE_GUID."' WHERE `object_type`='blogpost'",
	),
)),


// STEP 3
array( 'PHP' => '
	global $gBitSystem;
	$startPost = $gBitSystem->mDb->getOne( "SELECT MAX(`post_id`) FROM `'.BIT_DB_PREFIX.'tiki_blog_posts`" );
	$gBitSystem->mDb->CreateSequence( "tiki_blog_posts_post_id_seq", $startPost + 1 );
	$query = "SELECT tbp.`post_id`, uu.`user_id`, uu.`user_id` AS modifier_user_id, tbp.`created`, tbp.`created` AS last_modified, tbp.`data`, tbp.`title`
			  FROM `'.BIT_DB_PREFIX.'tiki_blog_posts` tbp INNER JOIN `'.BIT_DB_PREFIX.'users_users` uu ON( tbp.`user`=uu.`login` )";
	if( $rs = $gBitSystem->mDb->query( $query ) ) {
		while( !$rs->EOF ) {
			$postId = $rs->fields["post_id"];
			unset( $rs->fields["post_id"] );
			$conId = $gBitDb->GenID( "tiki_content_id_seq" );
			$rs->fields["content_id"] = $conId;
			$rs->fields["content_type_guid"] = BITBLOGPOST_CONTENT_TYPE_GUID;
			$rs->fields["format_guid"] = PLUGIN_GUID_TIKIWIKI;
			$gBitSystem->mDb->associateInsert( "tiki_content", $rs->fields );
			$gBitSystem->mDb->query( "UPDATE `'.BIT_DB_PREFIX.'tiki_blog_posts` SET `content_id`=? WHERE `post_id`=? ", array( $conId, $postId ) );
			$rs->MoveNext();
		}
	}
' ),


// STEP 4
array( 'QUERY' =>
	array( 'SQL92' => array(
	// Update Blog Post comments - we have no Blog level comments in BWR1
	"UPDATE `".BIT_DB_PREFIX."tiki_comments` SET `objectType`='".BITBLOGPOST_CONTENT_TYPE_GUID."' WHERE `objectType`='post'",
	"UPDATE `".BIT_DB_PREFIX."tiki_comments` SET `parent_id`=(SELECT `content_id` FROM `".BIT_DB_PREFIX."tiki_blog_posts` WHERE `post_id`=`".BIT_DB_PREFIX."tiki_comments`.`object`) WHERE `parent_id`=0 AND `objectType`='".BITBLOGPOST_CONTENT_TYPE_GUID."'",
	"INSERT INTO `".BIT_DB_PREFIX."tiki_preferences`( `name`, `value`, `package` ) VALUES( 'blog_posts_description_length', '500', '".ARTICLES_PKG_NAME."' )",	
	),
)),


// STEP 5
array( 'DATADICT' => array(
	array( 'DROPCOLUMN' => array(
		'tiki_blog_posts' => array( '`user`', '`created`', '`data`', '`title`' ),
		'tiki_blogs' => array( '`user`' ),
	)),
)),


	)
)

);

if( isset( $upgrades[$gUpgradeFrom][$gUpgradeTo] ) ) {
	$gBitSystem->registerUpgrade( BLOGS_PKG_NAME, $upgrades[$gUpgradeFrom][$gUpgradeTo] );
}


?>
