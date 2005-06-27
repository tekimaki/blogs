<?php
/**
 * $Header: /cvsroot/bitweaver/_bit_blogs/BitBlogPost.php,v 1.1.1.1.2.2 2005/06/27 10:08:40 lsces Exp $
 *
 * Copyright (c) 2004 bitweaver.org
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details
 *
 * $Id: BitBlogPost.php,v 1.1.1.1.2.2 2005/06/27 10:08:40 lsces Exp $
 *
 * Virtual base class (as much as one can have such things in PHP) for all
 * derived tikiwiki classes that require database access.
 * @package blogs
 *
 * created 2004/10/20
 *
 * @author drewslater <andrew@andrewslater.com>, spiderr <spider@steelsun.com>
 *
 * @version $Revision: 1.1.1.1.2.2 $ $Date: 2005/06/27 10:08:40 $ $Author: lsces $
 */

/**
 * required setup
 */
require_once( LIBERTY_PKG_PATH.'LibertyComment.php');
require_once( LIBERTY_PKG_PATH.'LibertyAttachable.php');
require_once( BLOGS_PKG_PATH.'BitBlog.php');

define( 'BITBLOGPOST_CONTENT_TYPE_GUID', 'bitblogpost' );

/**
 * @package blogs
 * @subpackage BitBlogPost
 */
class BitBlogPost extends LibertyAttachable {
	var $mPostId;
	function BitBlogPost( $pPostId=NULL, $pContentId=NULL ) {
		LibertyAttachable::LibertyAttachable();
		$this->registerContentType( BITBLOGPOST_CONTENT_TYPE_GUID, array(
				'content_type_guid' => BITBLOGPOST_CONTENT_TYPE_GUID,
				'content_description' => 'Blog Post',
				'handler_class' => 'BitBlogPost',
				'handler_package' => 'blogs',
				'handler_file' => 'BitBlogPost.php',
				'maintainer_url' => 'http://www.bitweaver.org'
			) );
		$this->mPostId = $pPostId;
		$this->mContentId = $pContentId;
	}

	function load( $pLoadComments = TRUE ) {
		if( !empty( $this->mPostId ) || !empty( $this->mContentId ) ) {
			global $gBitSystem;
			$lookupColumn = !empty( $this->mPostId )? 'post_id' : 'content_id';
			$lookupId = !empty( $this->mPostId )? $this->mPostId : $this->mContentId;
			$query = "SELECT tbp.*, tc.*, tb.`title` as `blogtitle`, tb.`allow_comments`,tb.`allow_comments`, tb.`use_title`, tb.`user_id` AS `blog_user_id`, uu.`login` as `user`, uu.`real_name`, tf.`storage_path` as avatar, tup.`value` AS `blog_style`
				  FROM `".BIT_DB_PREFIX."tiki_blog_posts` tbp
					INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON (tc.`content_id` = tbp.`content_id`)
					INNER JOIN `".BIT_DB_PREFIX."tiki_blogs` tb ON (tb.`blog_id` = tbp.`blog_id`)
				  	INNER JOIN `".BIT_DB_PREFIX."users_users` uu ON( uu.`user_id` = tc.`user_id` )
				  	LEFT OUTER JOIN `".BIT_DB_PREFIX."tiki_attachments` ta ON (uu.`user_id` = ta.`user_id` AND uu.`avatar_attachment_id`=ta.`attachment_id`)
					LEFT OUTER JOIN `".BIT_DB_PREFIX."tiki_files` tf ON (tf.`file_id` = ta.`foreign_id`)
					LEFT OUTER JOIN `".BIT_DB_PREFIX."tiki_user_preferences` tup ON ( uu.`user_id`=tup.`user_id` AND tup.`pref_name`='theme' )
					WHERE tbp.`$lookupColumn`=? ";

			$result = $this->query($query,array((int) $lookupId));

			if ($result->numRows()) {
				$this->mInfo = $result->fetchRow();
				$this->mPostId = $this->mInfo['post_id'];
				$this->mContentId = $this->mInfo['content_id'];
				$this->mInfo['blog_url'] = BitBlog::getBlogUrl( $this->mInfo['blog_id'] );

				if ($pLoadComments) {
					$comment = new LibertyComment();
					$this->mInfo['num_comments'] = $comment->getNumComments($this->mInfo['content_id']);
					// Get the comments associated with this post
					$this->mInfo['comments'] = $comment->getComments($this->mInfo['content_id'], $gBitSystem->getPreference( 'blog_comments_per_page', 10 ) );
				}

				if (!$this->mInfo['trackbacks_from'] || $this->mInfo['trackbacks_from']===null)
					$this->mInfo['trackbacks_from'] = serialize(array());

				if (!$this->mInfo['trackbacks_to'] || $this->mInfo['trackbacks_to']===null)
					$this->mInfo['trackbacks_to'] = serialize(array());

				$this->mInfo['trackbacks_from_count'] = count(array_keys(unserialize($this->mInfo['trackbacks_from'])));
				$this->mInfo['trackbacks_from'] = unserialize($this->mInfo['trackbacks_from']);
				$this->mInfo['trackbacks_to'] = unserialize($this->mInfo['trackbacks_to']);
				if ( $gBitSystem->isPackageActive( 'categories' ) ) {
					global $categlib;
					$this->mInfo['categs'] = $categlib->get_object_categories( BITBLOGPOST_CONTENT_TYPE_GUID, $this->mContentId );
				}
				$this->mInfo['trackbacks_to_count'] = count($this->mInfo['trackbacks_to']);
				LibertyAttachable::load();
				if( $this->mStorage ) {
					foreach( array_keys( $this->mStorage ) as $key ) {
						$this->mStorage[$key]['wiki_plugin_link'] = '{ATTACHMENT(id=>'.$key.')}{ATTACHMENT}';
					}
				}
			}
		}
		return( count( $this->mInfo ) );
	}

	function verify( &$pParamHash ) {
		global $gBitUser;
		if( !empty( $pParamHash['post_id'] ) && empty( $this->mContentId ) && empty( $pParamHash['content_id'] ) ) {
			$this->mPostId = $pParamHash['post_id'];
			$this->load();
		}
		if (empty($pParamHash['user_id'])) {
			$pParamHash['user_id'] = $gBitUser->mUserId;
		}
		$pParamHash['content_type_guid'] = BITBLOGPOST_CONTENT_TYPE_GUID;
		return( count( $this->mErrors ) == 0 );
	}

	function isValid() {
		return( !empty( $this->mPostId ) && is_numeric( $this->mPostId ) && $this->mPostId > 0 );
	}

	function isBlogOwner( $pUserId=NULL ) {
		$ret = FALSE;
		global $gBitUser;
		if( empty( $pUserId ) && $gBitUser->isValid() ) {
			$pUserId = $gBitUser->mUserId;
		}
		if( $this->isValid() && ($pUserId == $this->mInfo["blog_user_id"]) ) {
			$ret = 'y';
		}
		return $ret;
	}

	function store( &$pParamHash ) {
		global $gBlog, $gBitSystem;
		$pParamHash['upload']['thumbnail'] = TRUE;

		$this->mDb->StartTrans();
		if( $this->verify( $pParamHash ) && LibertyAttachable::store( $pParamHash ) ) {
			if( !empty( $pParamHash['attachment_id'] ) ) {
				// we just shoved an attachment onto the blog so we will concat the new link for usability.
				// THis is a bit hackish to do here. I imagine we will allow for this down in Liberty eventually,
				// but right now there is a chicken-n-egg situation storing 'data' before the attachments. - spiderr
				$pParamHash['edit'] .= '{ATTACHMENT(id=>'.$pParamHash['attachment_id'].')}{ATTACHMENT}';
				LibertyContent::store( $pParamHash );
			}
			if (empty($pParamHash['post_id'])) {
				global $smarty;
				global $feature_user_watches;
				$tracks = serialize(explode(',', $pParamHash['trackback']));
				$pParamHash['edit'] = strip_tags($pParamHash['edit'], '<a><b><i><h1><h2><h3><h4><h5><h6><ul><li><ol><br><p><table><tr><td><img><pre>');
				$now = date("U");
				if (empty($pParamHash['post_date']))
					$pParamHash['post_date'] = (int)$now;
				$pParamHash['post_id'] = $this->GenID('tiki_blog_posts_post_id_seq');

				$query = "insert into `".BIT_DB_PREFIX."tiki_blog_posts`(`blog_id`, `post_id`, `content_id`, `trackbacks_from`,`trackbacks_to`) values(?,?,?,?,?)";
				$result = $this->query($query,array($pParamHash['blog_id'],$pParamHash['post_id'],$pParamHash['content_store']['content_id'],serialize(array()),serialize(array())));
				$this->mPostId = $pParamHash['post_id'];

				// Send trackbacks recovering only successful trackbacks
				$trackbacks = serialize( $this->sendTrackbacks( $pParamHash['trackback'] ) );
				// Update post with trackbacks successfully sent
				$query = "update `".BIT_DB_PREFIX."tiki_blog_posts` set `trackbacks_from`=?, `trackbacks_to` = ? where `post_id`=?";
				$this->query($query,array(serialize(array()),$trackbacks,(int) $pParamHash['post_id']));
				$query = "update `".BIT_DB_PREFIX."tiki_blogs` set `last_modified`=?,`posts`=`posts`+1 where `blog_id`=?";
				$result = $this->query($query,array((int)$now,(int) $pParamHash['blog_id']));
				$gBlog->add_blog_activity($pParamHash['blog_id']);

				// let's reload to get a full mInfo hash which is needed below
				$this->load();

				if( $gBitSystem->isFeatureActive( 'feature_user_watches' ) ) {
					global $gBitUser;
					if( $nots = $gBitUser->getEventWatches( 'blog_post', $this->mInfo['blog_id'] ) ) {
						foreach ($nots as $not) {
							$smarty->assign('mail_site', $_SERVER["SERVER_NAME"]);

							$smarty->assign('mail_title', $this->mInfo['title']);
							$smarty->assign('mail_blogid', $this->mInfo['blog_id']);
							$smarty->assign('mail_postid', $this->mPostId);
							$smarty->assign('mail_date', date("U"));
							$smarty->assign('mail_user', $this->mInfo['user']);
							$smarty->assign('mail_data', $this->mInfo['data']);
							$smarty->assign('mail_hash', $not['hash']);
							$foo = parse_url($_SERVER["REQUEST_URI"]);
							$machine = httpPrefix(). $foo["path"];
							$smarty->assign('mail_machine', $machine);
							$parts = explode('/', $foo['path']);

							if (count($parts) > 1)
								unset ($parts[count($parts) - 1]);

							$smarty->assign('mail_machine_raw', httpPrefix(). implode('/', $parts));
							$mail_data = $smarty->fetch('bitpackage:blogs/user_watch_blog_post.tpl');
							@mail($not['email'], tra('Blog post'). ' ' . $title, $mail_data, "From: ".$gBitSystem->getPrefence( 'sender_email' )."\r\nContent-type: text/plain;charset=utf-8\r\n");
						}
					}
				}
			}

			if( !empty( $pParamHash['trackbacks'] ) ) {
				$trackbacks = serialize($gBlog->send_trackbacks($post_id, $trackbacks));
				$query = "update `".BIT_DB_PREFIX."tiki_blog_posts` set `trackbacks_to`=? where `post_id`=?";
				$result = $this->query($query,array($trackbacks,$user_id,$post_id));
			}
		}
		$this->mDb->CompleteTrans();
		return( count( $this->mErrors ) == 0 );
	}


	function expunge() {
		$this->mDb->StartTrans();
		// let's force a full load to make sure everything is loaded.
		$this->load();
		if( $this->isValid() ) {
			// First kill any comments which belong to this post
			foreach($this->mInfo['comments'] as $comment) {
				$tmpComment = new LibertyComment($comment['comment_id']);
				$tmpComment->deleteComment();
			}

			$query = "delete from `".BIT_DB_PREFIX."tiki_blog_posts` where `post_id`=?";
			$result = $this->query( $query, array( (int) $this->mPostId ) );

			$query = "update `".BIT_DB_PREFIX."tiki_blogs` set `posts`=`posts`-1 where `blog_id`=?";
			$result = $this->query( $query, array( (int)$this->mInfo['blog_id'] ) );
			// Do this last so foreign keys won't complain (not the we have them... yet ;-)
			LibertyAttachable::expunge();
		}
		$this->mDb->CompleteTrans();

		return true;
	}

	function getDisplayUrl( $pPostId=NULL ) {
		if( empty( $pPostId ) ) {
			$pPostId = $this->mPostId;
		}
		global $gBitSystem;
		$ret = NULL;
		if( $gBitSystem->isFeatureActive( 'pretty_urls' ) ) {
			$ret = BLOGS_PKG_URL."post/$pPostId";
		} else {
			$ret = BLOGS_PKG_URL.'view_post.php?post_id='.$pPostId;
		}
		return $ret;
	}


	function getDisplayLink( $pPostId=NULL, $pMixed=NULL ) {
		$ret = NULL;
		if( empty( $pPostId ) ) {
			$pPostId = $this->mPostId;
		}
		global $gBitSystem;
		if( is_numeric( $pPostId ) ) {
			if( $gBitSystem->isFeatureActive( 'pretty_urls' ) ) {
				$ret = BLOGS_PKG_URL.'post/'.$pPostId;
			} else {
				$ret = BLOGS_PKG_URL.'view_post.php?post_id='.$pPostId;
			}
		}
		return $ret;
	}

    /**
    * Returns include file that will
    * @return the fully specified path to file to be included
    */
	function getRenderFile() {
		return( BLOGS_PKG_PATH.'display_bitblogpost_inc.php' );
	}

	function sendTrackbacks( $pTrackbacks ) {
		$ret = array();
		if( $this->isValid() && !empty( $pTrackbacks ) ) {
			// Split to get each URI
			$tracks = explode(',', $pTrackbacks);

			//Build uri for post
			$parts = parse_url($_SERVER['REQUEST_URI']);
			$uri = httpPrefix(). str_replace('post',
				'view_post', $parts['path']). '?post_id=' . $this->mPostId . '&amp;blog_id=' . $this->mInfo['blog_id'];
			include_once ( UTIL_PKG_PATH.'Snoopy.class.inc' );
			$snoopy = new Snoopy;

			foreach ($tracks as $track) {
				@$fp = fopen($track, 'r');

				if ($fp) {
					$data = '';

					while (!feof($fp)) {
						$data .= fread($fp, 32767);
					}

					fclose ($fp);
					preg_match("/trackback:ping=(\"|\'|\s*)(.+)(\"|\'\s)/", $data, $reqs);

					if (!isset($reqs[2]))
						return $ret;

					@$fp = fopen($reqs[2], 'r');

					if ($fp) {
						fclose ($fp);

						$submit_url = $reqs[2];
						$submit_vars["url"] = $uri;
						$submit_vars["blog_name"] = $this->mInfo['blogtitle'];
						$submit_vars["title"] = $this->mInfo['title'] ? $this->mInfo['title'] : date("d/m/Y [h:i]", $this->mInfo['created']);
						$submit_vars["title"] .= ' ' . tra('by'). ' ' . BitUser::getDisplayName( FALSE, $this->mInfo );
						$submit_vars["excerpt"] = substr($post_info['data'], 0, 200);
						$snoopy->submit($submit_url, $submit_vars);
						$back = $snoopy->results;

						if (!strstr('<error>1</error>', $back)) {
							$ret[] = $track;
						}
					}
				}
			}
		}
		return $ret;
	}

	function getList( &$pListHash ) {
		global $gBitUser, $gBitSystem;

		if( empty( $pListHash['sort_mode'] ) ) {
			$pListHash['sort_mode'] = 'created_desc';
		}

		$this->prepGetList( $pListHash );

		$whereSql = '';
		$bindvars = array();
		if( !empty( $pListHash['blog_id'] ) ) {
			array_push( $bindvars, (int)$pListHash['blog_id'] );
			$whereSql = ' AND tbp.`blog_id` = ? ';
		}

		if( !empty( $pListHash['user_id'] ) ) {
			array_push( $bindvars, (int)$pListHash['user_id'] );
			$whereSql = ' AND tc.`user_id` = ? ';
		}


		if( $pListHash['find'] ) {
			$findesc = '%' . strtoupper( $pListHash['find'] ) . '%';
			$mid = "AND (UPPER(`data`) like ?) ";
			$bindvars[] =$findesc;
		} else {
			$mid = "";
		}

		if( is_numeric( $pListHash['date'] ) ) {
			$mid .= " AND  tc.`created`<=? ";
			$bindvars[]=(int) $pListHash['date'];
		}

		$query = "SELECT tbp.*, tc.*, tb.`title` AS `blogtitle`, tb.`allow_comments`, uu.`email`, uu.`login` as `user`, uu.`real_name`, tf.`storage_path` as avatar
				  FROM `".BIT_DB_PREFIX."tiki_blogs` tb, `".BIT_DB_PREFIX."tiki_blog_posts` tbp
				  	INNER JOIN `".BIT_DB_PREFIX."tiki_content` tc ON (tc.`content_id` = tbp.`content_id`)
				  	INNER JOIN `".BIT_DB_PREFIX."users_users` uu ON (uu.`user_id` = tc.`user_id`)
					LEFT OUTER JOIN `".BIT_DB_PREFIX."tiki_attachments` ta ON (uu.`user_id` = ta.`user_id` AND ta.`attachment_id` = uu.`avatar_attachment_id`)
					LEFT OUTER JOIN `".BIT_DB_PREFIX."tiki_files` tf ON (tf.`file_id` = ta.`foreign_id`)
				  WHERE tb.`blog_id` = tbp.`blog_id` $whereSql $mid order by tc.".$this->convert_sortmode( $pListHash['sort_mode'] );
		$query_cant = "SELECT COUNT(tbp.`post_id`) FROM `".BIT_DB_PREFIX."tiki_blog_posts` tbp, `".BIT_DB_PREFIX."tiki_content` tc WHERE tc.`content_id` = tbp.`content_id` $whereSql $mid";
		$result = $this->query($query,$bindvars,$pListHash['max_records'],$pListHash['offset']);
		$cant = $this->getOne($query_cant,$bindvars);
		$ret = array();

		while ($res = $result->fetchRow()) {
			$res['avatar'] = (!empty($res['avatar']) ? BIT_ROOT_URL.$res['avatar'] : NULL);
			if ( $pListHash['load_num_comments'] || $pListHash['load_comments'] ) {
				$comment = new LibertyComment();
				$res['num_comments'] = $comment->getNumComments($res['content_id']);
				if( $pListHash['load_comments'] ) {
					// Get the comments associated with this post
					$res['comments'] = $comment->getComments($res['content_id'], $gBitSystem->getPreference( 'blog_comments_per_page', 10 ) );
				}
			} else {
				$res['comments'] = NULL;
			}

			$res['post_url'] = BitBlogPost::getDisplayLink( $res['post_id'] );
			$res['display_url'] = $res['post_url'];
			$res['blog_url'] = BitBlog::getBlogUrl( $res['blog_id'] );

			if($res['trackbacks_from']!=null)
				$res['trackbacks_from'] = unserialize($res['trackbacks_from']);

			if (!is_array($res['trackbacks_from']))
				$res['trackbacks_from'] = array();

			$res['trackbacks_from_count'] = count(array_keys($res['trackbacks_from']));
			if($res['trackbacks_to']!=null)
				$res['trackbacks_to'] = unserialize($res['trackbacks_to']);
			if ($res['user_id'] == $gBitUser->mUserId) {
				$res['ownsblog'] = 'y';
			} else {
				$res['ownsblog'] = 'n';
			}
			$res['trackbacks_to_count'] = count($res['trackbacks_to']);
			$res['pages'] = $this->getNumberOfPages( $res['data'] );

			if ( $gBitSystem->isPackageActive( 'categories' ) ) {
				global $categlib;
				require_once( CATEGORIES_PKG_PATH.'categ_lib.php' );
				$res['categs'] = $categlib->get_object_categories( BITBLOGPOST_CONTENT_TYPE_GUID, $res["content_id"] );
			}

			if( $pListHash['parse_data'] ) {
				$res["parsed_data"] = $this->parseData($res["data"], $res['format_guid']);
			}

			$ret[] = $res;
		}

		$retval = array();
		$retval["data"] = $ret;
		$retval["cant"] = $cant;
		return $retval;
	}


	function addTrackbackFrom( $url, $title = '', $excerpt = '', $blog_name = '') {
		if( $this->isValid() ) {
			$tbs = $this->getTrackbacksFrom( $this->mPostId );
			$aux = array(
				'title' => $title,
				'excerpt' => $excerpt,
				'blog_name' => $blog_name
			);

			$tbs[$url] = $aux;
			$st = serialize($tbs);
			$query = "update `".BIT_DB_PREFIX."tiki_blog_posts` set `trackbacks_from`=? where `post_id`=?";
			$this->query( $query, array( $st, $this->mPostId ) );
			return true;
		}
	}

	function getTrackbacksFrom() {
		if( $this->isValid() ) {
			$st = $this->mDb->getOne("select `trackbacks_from` from `".BIT_DB_PREFIX."tiki_blog_posts` where `post_id`=?",array( $this->mPostId ) );
			return unserialize($st);
		}
	}

	function getTrackbacksTo() {
		if( $this->isValid() ) {
			$st = $this->mDb->getOne("select `trackbacks_to` from `".BIT_DB_PREFIX."tiki_blog_posts` where `post_id`=?", array( $this->mPostId ) );
			return unserialize($st);
		}
	}

	function clearTrackbacksFrom() {
		if( $this->isValid() ) {
			$empty = serialize(array());
			$query = "update `".BIT_DB_PREFIX."tiki_blog_posts` set `trackbacks_from` = ? where `post_id`=?";
			$this->query( $query, array( $empty, $this->mPostId ) );
		}
	}

	function clearTrackbacksTo() {
		if( $this->isValid() ) {
			$empty = serialize(array());
			$query = "update `".BIT_DB_PREFIX."tiki_blog_posts` set `trackbacks_to` = ? where `post_id`=?";
			$this->query( $query, array( $empty, $this->mPostId ) );
		}
	}



}
