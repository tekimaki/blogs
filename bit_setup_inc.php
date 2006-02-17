<?php
/**
 * @package blogs
 */

global $gBitSystem, $gBitUser, $gBitSmarty;

$registerHash = array(
	'package_name' => 'blogs',
	'package_path' => dirname( __FILE__ ).'/',
);
$gBitSystem->registerPackage( $registerHash );

if( $gBitSystem->isPackageActive( 'blogs' ) ) {
	if( $gBitUser->hasPermission( 'bit_p_blog_admin' ) ) {
		$gBitUser->setPreference( 'bit_p_create_blogs', TRUE );
		$gBitUser->setPreference( 'bit_p_blog_post', TRUE );
		$gBitUser->setPreference( 'bit_p_read_blog', TRUE );
	}

	if ($gBitUser->hasPermission( 'bit_p_read_blog' )) {
		$gBitSystem->registerAppMenu( BLOGS_PKG_NAME, ucfirst( BLOGS_PKG_DIR ), BLOGS_PKG_URL.'index.php', 'bitpackage:blogs/menu_blogs.tpl', BLOGS_PKG_NAME );
	}

	$gBitSystem->registerNotifyEvent( array( "blog_post" => tra("An entry is posted to a blog") ) );

	$gBitSmarty->assign('home_blog', 0);
	$gBitSmarty->assign('blog_spellcheck', 'n');
	$gBitSmarty->assign('blog_list_order', 'created_desc');
	$gBitSmarty->assign('blog_list_title', 'y');
	$gBitSmarty->assign('blog_list_description', 'y');
	$gBitSmarty->assign('blog_list_created', 'y');
	$gBitSmarty->assign('blog_list_lastmodif', 'y');
	$gBitSmarty->assign('blog_list_user', 'y');
	$gBitSmarty->assign('blog_list_posts', 'y');
	$gBitSmarty->assign('blog_list_visits', 'y');
	$gBitSmarty->assign('blog_list_activity', 'y');
	$gBitSmarty->assign('blog_list_user', 'text');

}

?>
