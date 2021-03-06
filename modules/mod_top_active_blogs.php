<?php
/**
 * @version $Header$
 * @package blogs
 * @subpackage modules
 */

/**
 * required setup
 */
include_once( BLOGS_PKG_PATH.'BitBlog.php' );
require_once( USERS_PKG_PATH.'BitUser.php' );

global $gQueryUserId, $gBitThemes, $module_rows, $module_params;

$listHash['max_records'] = $module_rows;
$listHash['sort_mode'] = 'activity_desc';
$listHash['user_id'] = $gQueryUserId;
$listHash['is_active'] = TRUE;
if( @BitBase::verifyId( $module_params['group_id'] ) ) {
	$listHash['group_id'] = $module_params['group_id'];
}
$blog = new BitBlog();
$ranking = $blog->getList( $listHash );
if( !empty( $ranking['data'] ) ) {
	$gBitSmarty->assign('modTopActiveBlogs', $ranking["data"]);
}
?>
