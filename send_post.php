<?php
/**
 * @version $Header: /cvsroot/bitweaver/_bit_blogs/send_post.php,v 1.1.1.1.2.3 2005/06/30 18:14:37 squareing Exp $

 * @package blogs
 * @subpackage functions
 */

// Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * required setup
 */
require_once( '../bit_setup_inc.php' );

include_once( BLOGS_PKG_PATH.'BitBlog.php' );

$gBitSystem->verifyPermission( 'bit_p_read_blog' );

if (!isset($_REQUEST["post_id"])) {
	$gBitSystem->fatalError( 'No post indicated' );
}

include_once( BLOGS_PKG_PATH.'lookup_post_inc.php' );
$smarty->assign('post_info', $gContent->mInfo );

//Build absolute URI for this
$parts = parse_url($_SERVER['REQUEST_URI']);
$uri = httpPrefix(). $parts['path'] . '?blog_id=' . $gContent->mInfo['blog_id'] . '&post_id=' . $gContent->mInfo['post_id'];
$uri2 = httpPrefix(). $parts['path'] . '/' . $gContent->mInfo['blog_id'] . '/' . $gContent->mInfo['post_id'];
$smarty->assign('uri', $uri);
$smarty->assign('uri2', $uri2);

$smarty->assign( 'parsed_data', $gContent->parseData() );

$smarty->assign('individual', 'n');

if ($gBitUser->object_has_one_permission($gContent->mInfo["blog_id"], 'blog')) {
	$smarty->assign('individual', 'y');

	if (!$gBitUser->isAdmin()) {
		// Now get all the permissions that are set for this content type
		$perms = $gBitUser->getPermissions('', 'blogs');
		foreach( array_keys( $perms ) as $permName ) {
			if ($gBitUser->object_has_permission( $user, $_REQUEST["blog_id"], 'blog', $permName ) ) {
				$$permName = 'y';
				$smarty->assign( $permName, 'y');
			} else {
				$$permName = 'n';
				$smarty->assign( $permName, 'n');
			}
		}
	}
}

if ($gBitUser->hasPermission( 'bit_p_blog_admin' )) {
	$bit_p_create_blogs = 'y';

	$smarty->assign('bit_p_create_blogs', 'y');
	$bit_p_blog_post = 'y';
	$smarty->assign('bit_p_blog_post', 'y');
	$bit_p_read_blog = 'y';
	$smarty->assign('bit_p_read_blog', 'y');
}

$smarty->assign('ownsblog', $gContent->isBlogOwner() );

if ($feature_blogposts_comments == 'y') {
	$comments_vars = array(
		'post_id',
		'offset',
		'find',
		'sort_mode'
	);

	$comments_prefix_var = 'post:';
	$comments_object_var = 'post_id';
	include_once ( LIBERTY_PKG_PATH.'comments_inc.php' );
}

$section = 'blogs';

if ($feature_theme_control == 'y') {
	$cat_type = 'blog';

	$cat_objid = $_REQUEST['blog_id'];
	include( THEMES_PKG_PATH.'tc_inc.php' );
}

if (!isset($_REQUEST['addresses'])) {
	$_REQUEST['addresses'] = '';
}

$smarty->assign('addresses', $_REQUEST['addresses']);
$smarty->assign('sent', 'n');

if (isset($_REQUEST['send'])) {
	
	$emails = explode(',', $_REQUEST['addresses']);

	$foo = parse_url($_SERVER["REQUEST_URI"]);
	$machine = httpPrefix(). $gContent->getDisplayLink();

	foreach ($emails as $email) {
		$smarty->assign('mail_site', $_SERVER["SERVER_NAME"]);

		$smarty->assign('mail_user', $gBitUser->getDisplayName() );
		$smarty->assign('mail_title', $gContent->mInfo['title'] ? $gContent->mInfo['title'] : date("d/m/Y [h:i]", $gContent->mInfo['created']));
		$smarty->assign('mail_machine', $machine);
		$mail_data = $smarty->fetch('bitpackage:blogs/blogs_send_link.tpl');
		@mail($email, tra('Post recommendation at'). ' ' . $_SERVER["SERVER_NAME"], $mail_data,
			"From: ".$gBitSystem->getPreference( 'sender_email' )."\r\nContent-type: text/plain;charset=utf-8\r\n");
	}

	$smarty->assign('sent', 'y');
}
$gBitSystem->setBrowserTitle("Send Blog Post: ".$gContent->mInfo['title']);

// Display the template
$gBitSystem->display( 'bitpackage:blogs/send_blog_post.tpl');

?>
