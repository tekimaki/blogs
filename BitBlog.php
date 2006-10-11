<?php
/**
 * @package blogs
 */

/**
 * required setup
 */
require_once( BLOGS_PKG_PATH.'BitBlogPost.php');
require_once( LIBERTY_PKG_PATH.'LibertyComment.php');

define( 'BITBLOG_CONTENT_TYPE_GUID', 'bitblog' );

/**
 * @package blogs
 */
class BitBlog extends LIbertyContent {
	var $mBlogId;
	
	function BitBlog( $pBlogId, $pContentId=NULL ) {
		$this->mBlogId = @$this->verifyId( $pBlogId ) ? $pBlogId : NULL;
		parent::LibertyContent( $pContentId );
		$this->registerContentType( BITBLOG_CONTENT_TYPE_GUID, array(
			'content_description' => 'Blog',
			'handler_class' => 'BitBlog',
			'handler_package' => 'blogs',
			'handler_file' => 'BitBlog.php',
			'maintainer_url' => 'http://www.bitweaver.org'
		) );
		$this->mContentId = $pContentId;
		$this->mContentTypeGuid = BITBLOG_CONTENT_TYPE_GUID;
	}

	function get_num_user_blogs($user_id) {
		$ret = NULL;
		if ($user_id) {
			$sql = "SELECT COUNT(*) FROM `".BIT_DB_PREFIX."blogs` WHERE `user_id` = ?";
			$ret = $this->mDb->getOne($sql, array( $user_id ));
		}
		return $ret;
	}

	function getBlogUrl( $pBlogId ) {
		global $gBitSystem;
		$ret = NULL;
		if ( $this->verifyId( $pBlogId ) ) {
			if( $gBitSystem->isFeatureActive( 'pretty_urls' ) ) {
				$ret = BLOGS_PKG_URL.$pBlogId;
			} else {
				$ret = BLOGS_PKG_URL.'view.php?blog_id='.$pBlogId;
			}
		}
		return $ret;
	}


	/**
	* Check if there is an article loaded
	* @return bool TRUE on success, FALSE on failure
	* @access public
	**/
	function isValid() {
		return( $this->verifyId( $this->mBlogId ) && $this->verifyId( $this->mContentId ) );
	}

	function load() {
		$this->mInfo = $this->getBlog( $this->mBlogId, $this->mContentId );
		$this->mContentId = $this->getField( 'content_id' );
	}


	/*shared*/
	function getBlog( $pBlogId, $pContentId = NULL ) {
		global $gBitSystem;
		$ret = NULL;
		
		$lookupId = (!empty( $pBlogId ) ? $pBlogId : $pContentId);
		$lookupColumn = (!empty( $pBlogId ) ? 'blog_id' : 'content_id');
		
		if ( BitBase::verifyId( $lookupId ) ) {
			$query = "SELECT b.*, lc.*, uu.`login`, uu.`login`, uu.`user_id`, uu.`real_name`, lf.`storage_path` as avatar
				  	  FROM `".BIT_DB_PREFIX."blogs` b 
						INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON (lc.`content_id` = b.`content_id`)
					  	INNER JOIN `".BIT_DB_PREFIX."users_users` uu ON (uu.`user_id` = lc.`user_id`)
			  			LEFT OUTER JOIN `".BIT_DB_PREFIX."liberty_content_hits` lch ON (lc.`content_id` = lch.`content_id`)
			  			LEFT OUTER JOIN `".BIT_DB_PREFIX."liberty_attachments` a ON (uu.`user_id` = a.`user_id` AND uu.`avatar_attachment_id`=a.`attachment_id`)
						LEFT OUTER JOIN `".BIT_DB_PREFIX."liberty_files` lf ON (lf.`file_id` = a.`foreign_id`)
			  		  WHERE b.`blog_id`= ?";
					  // this was the last line in the query - tiki_user_preferences is DEAD DEAD DEAD!!!
//						LEFT OUTER JOIN `".BIT_DB_PREFIX."tiki_user_preferences` tup ON ( uu.`user_id`=tup.`user_id` AND tup.`pref_name`='theme' )

			$result = $this->mDb->query($query,array((int)$pBlogId));
			$ret = $result->fetchRow();
			if ($ret) {
				$ret['avatar'] = (!empty($res['avatar']) ? BIT_ROOT_URL.$res['avatar'] : NULL);
				if ( $gBitSystem->isPackageActive( 'categories' ) ) {
					global $categlib;
					$ret['categs'] = $categlib->get_object_categories( BITBLOG_CONTENT_TYPE_GUID, $pBlogId );
				}

				if( empty( $ret['max_posts'] ) || !is_numeric( $ret['max_posts'] ) ) {
					$ret['max_posts'] = 10; // spiderr hack to hardcode fail safe
				}
			}
		}
		return $ret;
	}

	function verify( &$pParamHash ) {
		global $gBitUser;
	
		$pParamHash['blog_store']['use_title'] = isset( $pParamHash['use_title'] ) ? 'y' : 'n';
		$pParamHash['blog_store']['allow_comments'] = isset( $pParamHash['allow_comments'] ) ? 'y' : 'n';
		$pParamHash['blog_store']['use_find'] = isset( $pParamHash['use_find'] ) ? 'y' : 'n';
		$pParamHash['blog_store']['heading'] = isset( $pParamHash['heading'] ) ? $pParamHash['heading'] : NULL;

		$pParamHash['blog_store']['is_public'] = $gBitUser->hasPermission('p_blogs_create_is_public') ? isset( $pParamHash['public'] ) ? $pParamHash['public'] : NULL : NULL;
		return( count( $this->mErrors ) == 0 );
	}

	function store( &$pParamHash ) { //$title, $description, $user_id, $public, $max_posts, $blog_id, $heading, $use_title, $use_find,
		global $gBitSystem;
		if( $this->verify( $pParamHash ) && parent::store( $pParamHash ) ) {
			$table = BIT_DB_PREFIX."blogs";
			$this->mDb->StartTrans();
			if( $this->isValid() ) {
				$result = $this->mDb->associateUpdate( $table, $pParamHash['blog_store'], array( "blog_id" => $pParamHash['blog_id'] ) );
			} else {
				$pParamHash['blog_store']['content_id'] = $pParamHash['content_id'];
				if( isset( $pParamHash['blog_id'] )&& is_numeric( $pParamHash['blog_id'] ) ) {
					// if pParamHash['blog_id'] is set, someone is requesting a particular blog_id. Use with caution!
					$pParamHash['blog_store']['blog_id'] = $pParamHash['blog_id'];
				} else {
					$pParamHash['blog_store']['blog_id'] = $this->mDb->GenID( 'blogs_blog_id_seq' );
				}
				$this->mBlogId = $pParamHash['blog_store']['blog_id'];
				$result = $this->mDb->associateInsert( $table, $pParamHash['blog_store'] );
			}
		}
		return( count( $this->mErrors ) == 0 );
	}

	function expunge() {
		$ret = FALSE;
		if ( $this->isValid() ) {
			$this->mDb->StartTrans();
			
			$query = "SELECT `content_id` from `".BIT_DB_PREFIX."blog_posts` where `blog_id`=?";
			if( $posts = $this->mDb->getAll($query,array( (int)$this->mBlogId ) ) ) {
				foreach( $posts as $postContentId ) {
					$delPost = new BitBlogPost( NULL, $postContentId );
					$delPost->load();
					$delPost->expunge();
				}
			}
			
			$query = "DELETE from `".BIT_DB_PREFIX."blogs` where `blog_id`=?";
			$result = $this->mDb->query( $query, array( (int)$this->mBlogId ) );

			if( parent::expunge() ) {
				$ret = TRUE;
				$this->mDb->CompleteTrans();
			} else {
				$this->mDb->RollbackTrans();
			}
			$this->mDb->CompleteTrans();
		}
		return $ret;
	}

	function get_random_blog_post($blog_id = NULL, $load_comments = FALSE) {
		$ret = NULL;
		$bindVars = array();

		if ( $this->verifyId( $blog_id ) ) {
			$sql = "SELECT `post_id` FROM `".BIT_DB_PREFIX."blog_posts` WHERE `blog_id` = ?";
			$rs = $this->mDb->query($sql, array($blog_id));
			$rows = $rs->getRows();
			$numPosts = count($rows);
			if ($numPosts > 0) {
				$post_id = $rows[rand(0, $numPosts-1)]['post_id'];	// Get a random post_id array index
				$blogPost = new BitBlogPost( $post_id );
				$blogPost->load($load_comments);
				$ret = $blogPost;
			}
		}
		return $ret;
	}

	// BLOG METHODS ////
	function getList( &$pParamHash ) {
		global $gBitSystem;

		LibertyContent::prepGetList( $pParamHash );
		
		$selectSql = '';
		$joinSql = '';
		$whereSql = '';
		$bindVars = array();
		array_push( $bindVars, $this->mContentTypeGuid );
		$this->getServicesSql( 'content_list_sql_function', $selectSql, $joinSql, $whereSql, $bindVars );

		$find = $pParamHash['find'];
		if ($find) {
			$findesc = '%' . strtoupper( $find ) . '%';
			$whereSql = " AND (UPPER(`title`) like ? or UPPER(`data`) like ?) ";
			$bindVars=array($findesc,$findesc);
			if( !empty( $pParamHash['user_id'] ) ) {
				$whereSql .= " AND `user_id` = ?";
				$bindVars[] = $pParamHash['user_id'];
			}
		} elseif( @$this->verifyId( $pParamHash['user_id'] ) ) {
			$whereSql .= " AND uu.`user_id` = ? ";
			$bindVars=array( $pParamHash['user_id'] );
		} else {
			$whereSql .= '';
			$bindVars=array();
		}
		
		if( !empty( $pParamHash['is_active'] ) ) {
			$whereSql = " AND b.`activity` IS NOT NULL";
		}
		
		if( !empty( $pParamHash['is_hit'] ) ) {
			$whereSql = " AND lch.`hits` IS NOT NULL";
		}
/*
		if ($add_sql) {
			if (strlen($mid) > 1) {
				$mid .= ' AND '.$add_sql.' ';
			} else {
				$mid = "WHERE $add_sql ";
			}
		}
*/  
		if( !empty( $whereSql ) ) {
			$whereSql = preg_replace( '/^[\s]*AND/', ' WHERE ', $whereSql );
		}

		$query = "SELECT b.*, uu.`login`, uu.`real_name`, lc.*, lch.hits $selectSql
				  FROM `".BIT_DB_PREFIX."blogs` b 
					  INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON (lc.`content_id` = b.`content_id`)
					  INNER JOIN `".BIT_DB_PREFIX."users_users` uu ON (uu.`user_id` = lc.`user_id`)
					  $joinSql
			  		  LEFT OUTER JOIN `".BIT_DB_PREFIX."liberty_content_hits` lch ON (lc.`content_id` = lch.`content_id`)
				  $whereSql order by ".$this->mDb->convert_sortmode($pParamHash['sort_mode']);

		$result = $this->mDb->query( $query, $bindVars, $pParamHash['max_records'], $pParamHash['offset'] );

		$ret = array();

		while ($res = $result->fetchRow()) {
			if ( $gBitSystem->isPackageActive( 'categories' ) ) {
				global $categlib;
				$res['categs'] = $categlib->get_object_categories( BITBLOG_CONTENT_TYPE_GUID, $res['blog_id'] );
			}
			$res['blog_url'] = $this->getBlogUrl( $res['blog_id'] );
			// deal with the parsing
			$parseHash['format_guid']   = $res['format_guid'];
			$parseHash['content_id']    = $res['content_id'];
			$parseHash['data'] 			= $res['data'];
			$res['parsed'] = $this->parseData( $parseHash );
			$pParamHash["data"][] = $res;
		}
		
		$query_cant = "SELECT COUNT(b.`blog_id`)
					   FROM `".BIT_DB_PREFIX."blogs` b 
						INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON (lc.`content_id` = b.`content_id`)
						INNER JOIN `".BIT_DB_PREFIX."users_users` uu ON (uu.`user_id` = lc.`user_id`)
					   $whereSql";
		$pParamHash["cant"] = $this->mDb->getOne( $query_cant, $bindVars );

		LibertyContent::postGetList( $pParamHash );
		
		return $pParamHash;
	}

	function viewerCanPostIntoBlog() {
		global $gBitUser;
		return ($this->getField('user_id') == $gBitUser->mUserId || $gBitUser->isAdmin() || $this->getField('is_public') == 'y' );
	}

	function viewerHasPermission($pPermName = NULL) {
		global $gBitUser;
		$ret = FALSE;
		if ($gBitUser->mUserId && $pPermName) {
			$ret = $gBitUser->object_has_permission( $gBitUser->mUserId, $this->mInfo['blog_id'], $this->getContentType(), $pPermName );
		}
		return $ret;
	}
}

global $gBlog, $gBitSmarty;
$blogId = @BitBase::verifyId( $_REQUEST["blog_id"] ) ? $_REQUEST["blog_id"] : NULL;
$gBlog = new BitBlog( $blogId );
if( $blogId ) {
	$gBlog->load();
	$gBitSmarty->assign_by_ref( 'gBlog', $gBlog );
}

?>
