<?php
/**
 * @version $Header: /cvsroot/bitweaver/_bit_blogs/view_post_image.php,v 1.1.1.1.2.2 2005/06/27 10:08:40 lsces Exp $

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

if (!isset($_REQUEST["image_id"])) {
	$smarty->assign('msg', tra("No image id given"));
	$gBitSystem->display( 'error.tpl' );
	die;
}

$imageInfo = $gBlog->getStorageFileInfo($_REQUEST["image_id"]);
$smarty->assign( 'imageInfo' , $imageInfo );
$gBitSystem->display( 'bitpackage:blogs/view_post_image.tpl' );
?>
