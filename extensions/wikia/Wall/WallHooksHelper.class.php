<?php

class WallHooksHelper {
	const RC_WALL_COMMENTS_MAX_LEN = 50;
	const RC_WALL_SECURENAME_PREFIX = 'WallMessage_';
	static private $rcWallActionTypes = array('wall_remove', 'wall_restore', 'wall_admindelete', 'wall_archive', 'wall_reopen');

	static public function onBlockIpCompleteWatch($name, $title ) {
		$app = F::App();
		$watchTitle = Title::makeTitle( NS_USER_WALL, $name );
		$app->wg->User->addWatch( $watchTitle );
		return true;
	}

	static public function onUserIsBlockedFrom($user, $title, &$blocked, &$allowUsertalk) {

		if ( !$user->mHideName && $allowUsertalk && $title->getNamespace() == NS_USER_WALL_MESSAGE ) {
			$wm =  WallMessage::newFromTitle($title);
			if($wm->getWallOwner()->getName() === $user->getName()){
				$blocked = false;
				wfDebug( __METHOD__ . ": self-user wall page, ignoring any blocks\n" );
			}
		}

		return true;
	}

	static public function onArticleViewHeader(&$article, &$outputDone, &$useParserCache) {
		wfProfileIn(__METHOD__);

		$app = F::App();
		$helper = new WallHelper();
		$title = $article->getTitle();

		if( $title->getNamespace() === NS_USER_WALL_MESSAGE
				&& intval($title->getText()) > 0
		) {
			//message wall index - brick page
			$outputDone = true;

			$mainTitle = Title::newFromId($title->getText());
			if(empty($mainTitle)) {
				$dbkey = null;
				$fromDeleted = true;
			} else {
				$dbkey = $mainTitle->getDBkey();
				$fromDeleted = false;
			}

			if(empty($dbkey)) {
				// try master
				$mainTitle = Title::newFromId($title->getText(), Title::GAID_FOR_UPDATE);
				if(!empty($mainTitle)) {
					$dbkey = $mainTitle->getDBkey();
					$fromDeleted = false;
				}
			}

			if(empty($dbkey) || !$helper->isDbkeyFromWall($dbkey) ) {
				// no dbkey or not from wall, redirect to wall
				$app->wg->Out->redirect(static::getWallTitle()->getFullUrl(), 301);

				wfProfileOut(__METHOD__);
				return true;
			} else {
				// article exists or existed
				if($fromDeleted) {
					$app->wg->SuppressPageHeader = true;
					$app->wg->Out->addHTML($app->renderView('WallController', 'messageDeleted', array( 'title' =>wfMsg( 'wall-deleted-msg-pagetitle' ) ) ));
					$app->wg->Out->setPageTitle( wfMsg( 'wall-deleted-msg-pagetitle' ) );
					$app->wg->Out->setHTMLTitle( wfMsg( 'errorpagetitle' ) );
				} else {
					$wallMessage = WallMessage::newFromTitle($mainTitle);
					$app->wg->SuppressPageHeader = true;
					if( $wallMessage->isVisible($app->wg->User) ||
							($wallMessage->canViewDeletedMessage($app->wg->User) && $app->wg->Request->getVal('show') == '1')
					) {
						if(wfRunHooks('WallBeforeRenderThread', array($mainTitle, $wallMessage))) {
							$app->wg->Out->addHTML($app->renderView('WallController', 'thread',  array('id' => $title->getText(),  'title' => $wallMessage->getWallTitle() )));
						}
					} else {
						$app->wg->Out->addHTML($app->renderView('WallController', 'messageDeleted', array( 'title' =>wfMsg( 'wall-deleted-msg-pagetitle' ) ) ));
					}
				}
			}

			wfProfileOut(__METHOD__);
			return true;
		}

		if( empty( $app->wg->EnableWallExt ) ) {
			wfProfileOut(__METHOD__);
			return true;
		}


		if( $title->getNamespace() === NS_USER_WALL && !$title->isSubpage()
		) {
			//message wall index
			$outputDone = true;
			$action = $app->wg->request->getVal('action');
			$app->wg->Out->addHTML($app->renderView('WallController', 'index', array( 'title' => $article->getTitle() ) ));
		}

		if( $title->getNamespace() === NS_USER_TALK
				&& !$title->isSubpage()
		) {
			$title = static::getWallTitle();
			if ( empty($title) ) {
				wfProfileOut(__METHOD__);
				return true;
			}
			//user talk page -> redirect to message wall
			$outputDone = true;
			$app->wg->Out->redirect($title->getFullUrl(), 301);

			wfProfileOut(__METHOD__);
			return true;
		}

		$parts = explode('/', $title->getText());

		if( $title->getNamespace() === NS_USER_TALK
				&& $title->isSubpage()
				&& !empty($parts[0])
				&& !empty($parts[1])
		) {
			//user talk subpage -> redirects to message wall namespace subpage
			$outputDone = true;

			$title = Title::newFromText($parts[0].'/'.$parts[1], NS_USER_WALL);
			$app->wg->Out->redirect($title->getFullUrl(), 301);

			wfProfileOut(__METHOD__);
			return true;
		}

		if( $title->getNamespace() === NS_USER_WALL
				&& $title->isSubpage()
				&& !empty($app->wg->EnableWallExt)
				&& !empty($parts[1])
				&& mb_strtolower(str_replace(' ', '_', $parts[1])) === mb_strtolower($helper->getArchiveSubPageText())
		) {
			//user talk archive
			$outputDone = true;

			$app->wg->Out->addHTML($app->renderView('WallController', 'renderOldUserTalkPage', array('wallUrl' => static::getWallTitle()->getFullUrl())));
		} else if( $title->getNamespace() === NS_USER_WALL && $title->isSubpage() ) {
			//message wall subpage (sometimes there are old user talk subpages)
			$outputDone = true;

			$app->wg->Out->addHTML($app->renderView('WallController', 'renderOldUserTalkSubpage', array('subpage' => $parts[1], 'wallUrl' => static::getWallTitle()->getFullUrl()) ));

			wfProfileOut(__METHOD__);
			return true;
		}

		wfProfileOut(__METHOD__);
		return true;
	}

	/**
	 * @brief Hook to change tabs on user wall page
	 *
	 * @param $template
	 * @param $contentActions
	 * @return bool
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	static public function onSkinTemplateTabs($template, &$contentActions) {
		$app = F::App();

		if( !empty($app->wg->EnableWallExt) ) {
			$helper = new WallHelper();
			$title = $app->wg->Title;

			if( $title->getNamespace() === NS_USER ) {
				if( !empty($contentActions['namespaces']) && !empty($contentActions['namespaces']['user_talk']) ) {

					$contentActions['namespaces']['user_talk']['text'] = wfMsg('wall-message-wall');

					$userWallTitle = static::getWallTitle();

					if( $userWallTitle instanceof Title ) {
						$contentActions['namespaces']['user_talk']['href'] = $userWallTitle->getLocalUrl();
					}

					// BugId:23000 Remove the class="new" to prevent the link from being displayed as a redlink in monobook.
					if ( $app->wg->User->getSkin() instanceof SkinMonoBook ) {
						unset( $contentActions['namespaces']['user_talk']['class'] );
					}
				}
			}

			if( $title->getNamespace() === NS_USER_WALL || $title->getNamespace() === NS_USER_WALL_MESSAGE ) {
				if( $title->getNamespace() === NS_USER_WALL_MESSAGE ) {
					$text = $title->getText();
					$id = intval($text);

					if( $id > 0 ) {
						$wm = WallMessage::newFromId($id);
					} else {
						//sometimes (I found it on a revision diff page) $id here isn't a number from (in example) Thread:1234 link
						//it's a text similar to this: AndLuk/@comment-38.127.199.123-20120111182821
						//then we need to use WallMessage constructor method
						$wm = new WallMessage($title);
					}

					if( empty($wm) ) {
						//FB#19394

						return true;
					}

					/* @var $wm WallMessage */
					$wall = $wm->getWall();
					$user = $wall->getUser();
				} else {
					$wall = Wall::newFromTitle($title);
					$user = $wall->getUser();
				}

				$contentActions['namespaces'] = array();

				if( $user instanceof User ) {
					$contentActions['namespaces']['user-profile'] = array(
							'class' => false,
							'href' => $user->getUserPage()->getFullUrl(),
							'text' => wfMsg('nstab-user'),
					);
				}

				$contentActions['namespaces']['message-wall'] = array(
						'class' => 'selected',
						'href' => $wall->getUrl(),
						'text' => wfMsg('wall-message-wall'),
				);
			}

			if( $title->getNamespace() === NS_USER_WALL && $title->isSubpage() ) {
				$userTalkPageTitle = $helper->getTitle(NS_USER_TALK);
				$contentActions = array();
				$contentActions['namespaces'] = array();

				$contentActions['namespaces']['view-source'] = array(
						'class' => false,
						'href' => $userTalkPageTitle->getLocalUrl(array('action' => 'edit')),
						'text' => wfMsg('user-action-menu-view-source'),
				);

				$contentActions['namespaces']['history'] = array(
						'class' => false,
						'href' => $userTalkPageTitle->getLocalUrl(array('action' => 'history')),
						'text' => wfMsg('user-action-menu-history'),
				);
			}
		}

		return true;
	}

	/**
	 * @brief Redirects any attempts of editing anything in NS_USER_WALL namespace
	 *
	 * @param $editPage
	 * @return true
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	static public function onAlternateEdit($editPage) {
		static::doSelfRedirect();

		return true;
	}

	/**
	 * @brief Redirects any attempts of viewing history of any page in NS_USER_WALL namespace
	 *
	 * @param $article
	 * @return true
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 */

	static public function onBeforePageHistory( &$article ) {
		$title = $article->getTitle();
		$app = F::App();
		$page = $app->wg->Request->getVal('page', 1);

		if( !empty( $title ) ) {
			if(  WallHelper::isWallNamespace( $title->getNamespace() )  && !$title->isTalkPage() && !$title->isSubpage() ) {
				$app->wg->SuppressPageHeader = true;
				$app->wg->Out->addHTML( $app->renderView( 'WallHistoryController', 'index', array( 'title' => $title, 'page' => $page) ) );
				return false;
			}

			if(  WallHelper::isWallNamespace( $title->getNamespace() ) && $title->isTalkPage() ) {
				$app->wg->SuppressPageHeader = true;
				$app->wg->Out->addHTML( $app->renderView( 'WallHistoryController', 'index', array( 'title' => $title, 'page' => $page, 'threadLevelHistory' => true ) ) );
				return false;
			}
		}

		static::doSelfRedirect();
		return true;
	}

	/**
	 * @brief Overrides descrpiton of history page
	 *
	 * @param $description
	 * @return true
	 *
	 * @author Jakub Olek
	 */

	static public function onGetHistoryDescription( &$description ){
		$app = F::app();

		if( WallHelper::isWallNamespace( $app->wg->Title->getNamespace() ) ) {
			$description = '';
		}

		return true;
	}

	/**
	 * @brief add history to wall toolbar
	 *
	 * @param $items
	 *
	 * @return bool
	 */
	static public function onBeforeToolbarMenu( &$items ) {
		$app = F::app();
		if( empty( $app->wg->EnableWallExt ) ){
			return true;
		}

		$title = $app->wg->Title;
		$action = $app->wg->Request->getText('action');

		if ($title instanceof Title && $title->isTalkPage()  &&  WallHelper::isWallNamespace( $title->getNamespace() ) ){
			if ( is_array($items) ) {
				foreach($items as $k=>$value) {
					if( $value['type'] == 'follow' ) {
						unset($items[$k]);
						break;
					}
				}

			}
		}

		if( $title instanceof Title &&  WallHelper::isWallNamespace( $title->getNamespace() )  && !$title->isSubpage() && empty($action) ) {
			$item = array(
					'type' => 'html',
					'html' => Xml::element('a', array('href' => $title->getFullUrl('action=history')), wfMsg('wall-toolbar-history') )
			);

			if( is_array($items) ) {
				$inserted = false;
				$itemsout = array();

				foreach($items as $value) {
					$itemsout[] = $value;

					if( $value['type'] == 'follow' ) {
						$itemsout[] = $item;
						$inserted = true;
					}
				}

				if( !$inserted ) {
					array_unshift($items, $item);
				} else {
					$items = $itemsout;
				}
			} else {
				$items = array($item);
			}
		}

		return true;
	}

	/**
	 * @brief Redirects any attempts of protecting any page in NS_USER_WALL namespace
	 *
	 * @param $article
	 * @return true
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	static public function onBeforePageProtect(&$article) {
		static::doSelfRedirect();

		return true;
	}

	/**
	 * @brief Redirects any attempts of unprotecting any page in NS_USER_WALL namespace
	 *
	 * @param $article
	 * @return true
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	static public function onBeforePageUnprotect(&$article) {
		static::doSelfRedirect();

		return true;
	}

	/**
	 * @brief Redirects any attempts of deleting any page in NS_USER_WALL namespace
	 *
	 * @param $article
	 * @return true
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	static public function onBeforePageDelete(&$article) {
		static::doSelfRedirect();

		return true;
	}

	/**
	 * @brief Changes "My talk" to "Message wall" in the user links.
	 *
	 * @param $personalUrls
	 * @param $title
	 * @return true
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 * @author Piotrek Bablok
	 */
	static public function onPersonalUrls(&$personalUrls, &$title) {
		$app = F::App();

		if(empty($app->wg->EnableWallExt)) {
			return true;
		}

		$user = $app->wg->User;
		JSMessages::enqueuePackage('Wall', JSMessages::EXTERNAL);

		if( $user instanceof User && $user->isLoggedIn() ) {
			$userWallTitle = static::getWallTitle(null, $user);
			if( $userWallTitle instanceof Title ) {
				$personalUrls['mytalk']['href'] = $userWallTitle->getLocalUrl();
			}
			$personalUrls['mytalk']['text'] = wfMsg('wall-message-wall');

			if(!empty($personalUrls['mytalk']['class'])){
				unset($personalUrls['mytalk']['class']);
			}
		}

		return true;
	}

	/**
	 * @brief Changes "My talk" to "Message wall" in Oasis (in the tabs on the User page).
	 *
	 * @param $tabs
	 * @param $namespace
	 * @param $userName
	 * @return true
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	static public function onUserPagesHeaderModuleAfterGetTabs(&$tabs, $namespace, $userName) {
		$app = F::App();

		if(!empty($app->wg->EnableWallExt)) {
			foreach($tabs as $key => $tab) {
				if( !empty($tab['data-id']) && $tab['data-id'] === 'talk' ) {
					$userWallTitle = static::getWallTitle();

					if( $userWallTitle instanceof Title ) {
						$tabs[$key]['link'] = '<a href="'.$userWallTitle->getLocalUrl().'" title="'. $userWallTitle->getPrefixedText() .'">'.wfMsg('wall-message-wall').'</a>';
						$tabs[$key]['data-id'] = 'wall';

						if( $namespace === NS_USER_WALL ) {
							$tabs[$key]['selected'] = true;
						}
					}

					break;
				}
			}
		}
		return true;
	}

	/**
	 * @brief Remove Message Wall:: from back link
	 *
	 * @param $title
	 * @param $ptext
	 * @param $cssClass
	 * @return bool
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	static public function onSkinSubPageSubtitleAfterTitle($title, &$ptext, &$cssClass) {
		if( !empty($title) && $title->getNamespace() == NS_USER_WALL) {
			$ptext = $title->getText();
			$cssClass = 'back-user-wall';
		}

		return true;
	}

	/**
	 * @brief Adds an action button on user talk archive page
	 *
	 * @param $response
	 * @param $ns
	 * @param $skin
	 * @return bool
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	static public function onPageHeaderIndexAfterActionButtonPrepared($response, $ns, $skin) {
		$app = F::App();
		$helper = new WallHelper();

		if( !empty($app->wg->EnableWallExt) ) {
			$title = $app->wg->Title;
			$parts = explode( '/', $title->getText() );
			$action = $response->getVal('action');
			$dropdown = $response->getVal('dropdown');
			$canEdit = $app->wg->User->isAllowed('editwallarchivedpages');

			if( $ns === NS_USER_WALL
				&& $title->isSubpage()
				&& !empty($parts[1])
				&& mb_strtolower(str_replace(' ', '_', $parts[1])) === mb_strtolower($helper->getArchiveSubPageText())
			) {
				//user talk archive
				$userTalkPageTitle = $helper->getTitle(NS_USER_TALK);

				$action = array(
						'class' => '',
						'text' => wfMsg('viewsource'),
						'href' => $userTalkPageTitle->getLocalUrl(array('action' => 'edit')),
				);

				$dropdown = array(
						'history' => array(
								'href' => $userTalkPageTitle->getLocalUrl(array('action' => 'history')),
								'text' => wfMsg('history_short'),
						),
				);

				if( $canEdit ) {
					$action['text'] = wfMsg('edit');
					$action['id'] = 'talkArchiveEditButton';
				}

				$response->setVal('action', $action);
				$response->setVal('dropdown', $dropdown);
			}

			if( $title->getNamespace() === NS_USER_WALL
					&& $title->isSubpage()
					&& !empty($parts[1])
					&& mb_strtolower(str_replace(' ', '_', $parts[1])) !== mb_strtolower($helper->getArchiveSubPageText())
			) {
				//subpage
				$userTalkPageTitle = $helper->getTitle(NS_USER_TALK, $parts[1]);

				$action = array(
						'class' => '',
						'text' => wfMsg('viewsource'),
						'href' => $userTalkPageTitle->getLocalUrl(array('action' => 'edit')),
				);

				$dropdown = array(
						'history' => array(
								'href' => $userTalkPageTitle->getLocalUrl(array('action' => 'history')),
								'text' => wfMsg('history_short'),
						),
				);

				if( $canEdit ) {
					$action['text'] = wfMsg('edit');
					$action['id'] = 'talkArchiveEditButton';
				}

				$response->setVal('action', $action);
				$response->setVal('dropdown', $dropdown);
			}
			// update the response object with any changes
			$response->setVal('action', $action);
			$response->setVal('dropdown', $dropdown);
		}

		return true;
	}

	/**
	 * @brief Redirects to current title if it is in NS_USER_WALL namespace
	 *
	 * @return void
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	static protected function doSelfRedirect() {
		$app = F::App();
		$title = $app->wg->Title;

		if($app->wg->Request->getVal('action') == 'history' || $app->wg->Request->getVal('action') == 'historysubmit') {
			return true;
		}

		if( $title->getNamespace() === NS_USER_WALL ) {
			$app->wg->Out->redirect($title->getLocalUrl(), 301);
			$app->wg->Out->enableRedirects(false);
		}

		if( $title->getNamespace() === NS_USER_WALL_MESSAGE ) {
			$parts = explode( '/', $title->getText() );

			$title = Title::newFromText($parts[0], NS_USER_WALL);
			$app->wg->Out->redirect($title->getFullUrl(), 301);
			$app->wg->Out->enableRedirects(false);
		}
	}

	/**
	 * @brief Returns message wall title if any
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 *
	 * @param null $subpage
	 * @param null $user
	 * @return Title | null
	 */
	static protected function getWallTitle($subpage = null, $user = null) {
		$helper = new WallHelper();

		return $helper->getTitle(NS_USER_WALL, $subpage, $user);
	}

	/**
	 * clean history after delete
	 * @param $self
	 * @param $user
	 * @param $reason
	 * @param $id
	 * @return bool
	 */
	static public function onArticleDeleteComplete( &$self, &$user, $reason, $id) {
		$title = $self->getTitle();
		$app = F::app();
		if($title instanceof Title && $title->getNamespace() == NS_USER_WALL_MESSAGE) {
			$wh = new WallHistory($app->wg->CityId);
			$wh->remove( $id );
		}
		return true;
	}

	static public function onArticleDelete( $article, &$user, &$reason, &$error ){
		$title = $article->getTitle();
		if($title instanceof Title && $title->getNamespace() == NS_USER_WALL_MESSAGE) {
			$wallMessage = WallMessage::newFromTitle($title);
			return $wallMessage->canDelete($user);
		}
		return true;
	}

	static public function onRecentChangeSave( $recentChange ){
		wfProfileIn( __METHOD__ );
		// notifications
		$app = F::app();

		if(  MWNamespace::isTalk( $recentChange->getAttribute('rc_namespace') ) && in_array( MWNamespace::getSubject($recentChange->getAttribute('rc_namespace')), $app->wg->WallNS ) ) {
			$rcType = $recentChange->getAttribute('rc_type');

			//FIXME: WallMessage::remove() creates a new RC but somehow there is no rc_this_oldid
			$revOldId = $recentChange->getAttribute('rc_this_oldid');
			if( $rcType == RC_EDIT && !empty($revOldId) ) {
				$helper = new WallHelper();
				$helper->sendNotification($revOldId, $rcType);
			}
		}

		wfProfileOut( __METHOD__ );
		return true;
	}

	static public function onArticleCommentBeforeWatchlistAdd($comment) {
		$commentTitle = $comment->getTitle();
		$app = F::app();
		if ($commentTitle instanceof Title &&
			in_array(MWNamespace::getSubject( $commentTitle->getNamespace() ), $app->wg->WallNS) ) {
			$parentTitle = $comment->getTopParentObj();

			if (!($comment->mUser instanceof User)) {
				// force load from cache
				$comment->load(true);
			}

			if (!($comment->mUser instanceof User)) {
				// comment in master has no valid User
				// log error
				$logmessage = 'WallHooksHelper.class.php, ' . __METHOD__ . ' ';
				$logmessage .= 'ArticleId: ' . $commentTitle->getArticleID();

				Wikia::log(__METHOD__, false, $logmessage);

				// parse following hooks
				return true;
			}

			if (!empty($parentTitle)) {
				$comment->mUser->addWatch($parentTitle->getTitle());
			} else {
				$comment->mUser->addWatch($comment->getTitle());
			}

			return false;
		}

		return true;
	}


	/**
	 * @brief Allows to edit or not archived talk pages and its subpages
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 *
	 * @param $permErrors
	 * @param $title
	 * @param $removeArray
	 * @return boolean true -- because it's a hook
	 */
	static public function onAfterEditPermissionErrors(&$permErrors, $title, $removeArray) {
		$app = F::App();
		$canEdit = $app->wg->User->isAllowed('editwallarchivedpages');

		if( !empty($app->wg->EnableWallExt)
				&& defined('NS_USER_TALK')
				&& $title->getNamespace() == NS_USER_TALK
				&& !$canEdit
		) {
			$permErrors[] = array(
					0 => 'protectedpagetext',
					1 => 'archived'
			);
		}

		return true;
	}

	/**
	 * @brief Just adjusting links and removing history from brick pages (My Tools bar)
	 *
	 * @param array $contentActions passed by reference array with anchors elements
	 *
	 * @return true because this is a hook
	 */
	static public function onSkinTemplateContentActions(&$contentActions) {
		$app = F::app();

		$title = null;
		if( !empty($app->wg->EnableWallExt) && $app->wg->Title instanceof Title ) {
			$title = $app->wg->Title;
			$parts = explode( '/', $title->getText() );
			$helper = new WallHelper();
		}

		if( $title instanceof Title
				&& $title->getNamespace() == NS_USER_WALL
				&& $title->isSubpage() === true
				&& mb_strtolower(str_replace(' ', '_', $parts[1])) !== mb_strtolower($helper->getArchiveSubPageText())
		) {
			//remove "History" and "View source" tabs in Monobook & don't show history in "My Tools" in Oasis
			//because it leads to Message Wall (redirected) and a user could get confused
			if( isset($contentActions['history']['href']) ) {
				unset($contentActions['history']);
			}

			if( isset($contentActions['view-source']['href']) ) {
				unset($contentActions['view-source']);
			}
		}

		return true;
	}

	/**
	 * @brief Adjusting recent changes for Wall
	 *
	 * @desc This method doesn't let display flags for message wall replies (they are displayed only for messages from message wall)
	 *
	 * @param ChangesList $list
	 * @param string $flags
	 * @param RecentChange $rc
	 *
	 * @return true because this is a hook
	 *
	 * @author Andrzej 'nAndy' Lukaszewski
	 */
	static public function onChangesListInsertFlags($list, &$flags, $rc) {
		if( $rc->getAttribute('rc_type') == RC_NEW && $rc->getAttribute('rc_namespace') == NS_USER_WALL_MESSAGE ) {
			//we don't need flags if this is a reply on a message wall

			$rcTitle = $rc->getTitle();

			if( !($rcTitle instanceof Title) ) {
				//it can be media wiki deletion of an article -- we ignore them
				Wikia::log(__METHOD__, false, "WALL_NOTITLE_FROM_RC " . print_r($rc, true));
				return true;
			}

			$wm = new WallMessage($rcTitle);
			$wm->load();

			if( !$wm->isMain() ) {
				$flags = '';
			}
		}

		return true;
	}

	/**
	 * @brief Adjusting recent changes for Wall
	 *
	 * @desc This method shows link to message wall thread page
	 *
	 * @param ChangesList $list
	 * @param string $articleLink
	 * @param string $s
	 * @param RecentChange $rc
	 * @param boolean $unpatrolled
	 * @param boolean $watched
	 *
	 * @return true because this is a hook
	 *
	 * @author Andrzej 'nAndy' Lukaszewski
	 */
	static public function onChangesListInsertArticleLink($list, &$articleLink, &$s, $rc, $unpatrolled, $watched) {
		$rcType = $rc->getAttribute('rc_type');
		$app = F::app();
		if( in_array($rcType, array(RC_NEW, RC_EDIT, RC_LOG)) && in_array(MWNamespace::getSubject($rc->getAttribute('rc_namespace')), $app->wg->WallNS) ) {

			if( in_array($rc->getAttribute('rc_log_action'), static::$rcWallActionTypes) ) {
				$articleLink = '';

				return true;
			} else {
				$rcTitle = $rc->getTitle();

				if( !($rcTitle instanceof Title) ) {
					//it can be media wiki deletion of an article -- we ignore them
					Wikia::log(__METHOD__, false, "WALL_NOTITLE_FROM_RC " . print_r($rc, true));
					return true;
				}

				if(!$rcTitle->isTalkPage()) {
					return true;
				}



				$wm = new WallMessage($rcTitle);
				$wm->load();

				if( !$wm->isMain() ) {
					$link = $wm->getMessagePageUrl();
					$wm = $wm->getTopParentObj();
					if( is_null($wm) ) {
						Wikia::log(__METHOD__, false, "WALL_NO_PARENT_MSG_OBJECT " . print_r($rc, true));
						return true;
					} else {
						$wm->load();
					}
				} else {
					$link = $wm->getMessagePageUrl();
				}

				$title = $wm->getMetaTitle();
				$wallUrl = $wm->getWallPageUrl();
				$wallOwner = $wm->getWallOwnerName();
				$class = '';

				$articleLink = ' <a href="'.$link.'" class="'.$class.'" >'.$title.'</a> '.wfMsg(static::getMessagePrefix($rc->getAttribute('rc_namespace')) . '-new-message', array($wallUrl, $wallOwner));
				# Bolden pages watched by this user
				if( $watched ) {
					$articleLink = '<strong class="mw-watched">'.$articleLink.'</strong>';
				}
			}

			# RTL/LTR marker
			$articleLink .= $app->wg->ContLang->getDirMark();
		}

		return true;
	}

	/**
	 * @brief Adjusting recent changes for Wall
	 *
	 * @desc This method doesn't let display diff history links
	 *
	 * @param ChangesList $list
	 * @param $diffLink
	 * @param $historyLink
	 * @param string $s
	 * @param RecentChange $rc
	 * @param boolean $unpatrolled
	 *
	 * @internal param string $articleLink
	 * @return true because this is a hook
	 *
	 * @author Andrzej 'nAndy' Lukaszewski
	 */
	static public function onChangesListInsertDiffHist($list, &$diffLink, &$historyLink, &$s, $rc, $unpatrolled) {
		wfProfileIn(__METHOD__);

		$app = F::app();
		if( in_array(MWNamespace::getSubject(intval($rc->getAttribute('rc_namespace'))), $app->wg->WallNS) ) {
			$rcTitle = $rc->getTitle();

			if( !($rcTitle instanceof Title) ) {
				//it can be media wiki deletion of an article -- we ignore them
				Wikia::log(__METHOD__, false, "WALL_NOTITLE_FOR_DIFF_HIST " . print_r(array($rc), true));
				wfProfileOut(__METHOD__);
				return true;
			}

			if( in_array($rc->getAttribute('rc_log_action'), static::$rcWallActionTypes) ) {
				//delete, remove, restore
				$parts = explode('/@', $rcTitle->getText());
				$isThread = ( count($parts) === 2 ) ? true : false;

				if( $isThread ) {
					$wallTitleObj = Title::newFromText($parts[0], NS_USER_WALL);
					$historyLink = ( !empty($parts[0]) && $wallTitleObj instanceof Title) ? $wallTitleObj->getFullURL(array('action' => 'history')) : '#';
					$historyLink = Xml::element('a', array('href' => $historyLink), wfMsg(static::getMessagePrefix($rc->getAttribute('rc_namespace')) . '-history-link'));
				} else {
					$wallMessage = new WallMessage($rcTitle);
					$historyLink = $wallMessage->getMessagePageUrl(true).'?action=history';
					$historyLink = Xml::element('a', array('href' => $historyLink), wfMsg(static::getMessagePrefix($rc->getAttribute('rc_namespace')) . '-thread-history-link'));
				}

				$s = '(' . $historyLink . ')';
			} else {
				//new, edit
				if( $rc->mAttribs['rc_type'] == RC_NEW || $rc->mAttribs['rc_type'] == RC_LOG ) {
					$diffLink = wfMsg('diff');
				} else if( !ChangesList::userCan($rc, Revision::DELETED_TEXT) ) {
					$diffLink = wfMsg('diff');
				} else {
					$query = array(
							'curid' => $rc->mAttribs['rc_cur_id'],
							'diff'  => $rc->mAttribs['rc_this_oldid'],
							'oldid' => $rc->mAttribs['rc_last_oldid']
					);

					if( $unpatrolled ) {
						$query['rcid'] = $rc->mAttribs['rc_id'];
					}

					$diffLink = Xml::element('a', array(
							'href' => $rcTitle->getLocalUrl($query),
							'tabindex' => $rc->counter,
							'class' => 'known noclasses',
					), wfMsg('diff'));
				}

				$wallMessage = new WallMessage($rcTitle);
				$historyLink = $wallMessage->getMessagePageUrl(true).'?action=history';
				$historyLink = Xml::element('a', array('href' => $historyLink), wfMsg('hist'));
				$s = '('. $diffLink . wfMsg('pipe-separator') . $historyLink . ') . . ';
			}

		}

		wfProfileOut(__METHOD__);
		return true;
	}

	/**
	 * @brief Adjusting recent changes for Wall
	 *
	 * @desc This method doesn't let display rollback link for message wall inputs
	 *
	 * @param ChangesList $list
	 * @param string $s
	 * @param string $rollbackLink
	 * @param RecentChange $rc
	 *
	 * @return true because this is a hook
	 *
	 * @author Andrzej 'nAndy' Lukaszewski
	 */
	static public function onChangesListInsertRollback($list, &$s, &$rollbackLink, $rc) {
		if( !empty($rc->mAttribs['rc_namespace']) && $rc->mAttribs['rc_namespace'] == NS_USER_WALL_MESSAGE ) {
			$rollbackLink = '';
		}

		return true;
	}

	/**
	 * @brief Adjusting recent changes for Wall
	 *
	 * @desc This method creates comment to a recent change line
	 *
	 * @param ChangesList $list
	 * @param RecentChange $rc
	 * @param string $comment
	 * @internal param string $s
	 * @return true because this is a hook
	 *
	 * @author Andrzej 'nAndy' Lukaszewski
	 */
	static public function onChangesListInsertComment($list, $rc, &$comment) {
		$rcType = $rc->getAttribute('rc_type');
		$app = F::app();
		if( in_array($rcType, array(RC_NEW, RC_EDIT, RC_LOG)) && in_array(MWNamespace::getSubject($rc->getAttribute('rc_namespace')), $app->wg->WallNS) ) {

			if( $rcType == RC_EDIT ) {
				$comment = ' ';

				$summary = $rc->mAttribs['rc_comment'];

				if(empty($summary)) {
					$msg = wfMsgForContent( static::getMessagePrefix($rc->getAttribute('rc_namespace')).'-edit' );
				} else {
					$msg = wfMsgForContent( 'wall-recentchanges-summary', $summary );
				}

				$comment .= Xml::element( 'span', array('class' => 'comment'), $msg );
			} else if( $rcType == RC_LOG && in_array($rc->getAttribute('rc_log_action'), static::$rcWallActionTypes) ) {
				//this will be deletion/removal/restore summary
				$text = $rc->getAttribute('rc_comment');

				if( !empty($text) ) $comment = Xml::element('span', array('class' => 'comment'), ' ('.$text.')');
				else $comment = '';
			} else {
				$comment = '';
			}
		}

		return true;
	}

	/**
	 * @brief Adjusting recent changes for Wall
	 *
	 * @desc This method creates comment about revision deletion of a message on message wall
	 *
	 * @param ChangesList $list
	 * @param RecentChange $rc
	 * @param String $s
	 * @param Formatter $formatter
	 * @param string $mark
	 *
	 * @return true because this is a hook
	 *
	 * @author Andrzej 'nAndy' Lukaszewski
	 */
	static public function onChangesListInsertLogEntry($list, $rc, &$s, $formatter, &$mark) {
		$app = F::app();
		if( $rc->getAttribute('rc_type') == RC_LOG
				&& in_array(MWNamespace::getSubject($rc->getAttribute('rc_namespace')), $app->wg->WallNS)
				&& in_array($rc->getAttribute('rc_log_action'), static::$rcWallActionTypes) ) {

			$actionText = '';
			$wfMsgOptsBase = static::getMessageOptions($rc);

			$wfMsgOpts = array(
				$wfMsgOptsBase['articleUrl'],
				$wfMsgOptsBase['articleTitleTxt'],
				$wfMsgOptsBase['wallPageUrl'],
				$wfMsgOptsBase['wallPageName'],
				$wfMsgOptsBase['actionUser']);

			$msgType = ($wfMsgOptsBase['isThread']) ? 'thread' : 'reply';

			//created in WallHooksHelper::getMessageOptions()
			//and there is not needed to be passed to wfMsg()
			unset($wfMsgOpts['isThread'], $wfMsgOpts['isNew']);

			switch($rc->getAttribute('rc_log_action')) {
				case 'wall_remove':
					$actionText = wfMsgExt(static::getMessagePrefix($rc->getAttribute('rc_namespace')) . '-removed-'.$msgType, array('parseinline'), $wfMsgOpts);
					break;
				case 'wall_restore':
					$actionText = wfMsgExt(static::getMessagePrefix($rc->getAttribute('rc_namespace')) . '-restored-'.$msgType, array('parseinline'), $wfMsgOpts);
					break;
				case 'wall_admindelete':
					$actionText = wfMsgExt(static::getMessagePrefix($rc->getAttribute('rc_namespace')) . '-deleted-'.$msgType, array('parseinline'), $wfMsgOpts);
					break;
				case 'wall_archive':
					$actionText = wfMsgExt(static::getMessagePrefix($rc->getAttribute('rc_namespace')) . '-closed-thread', array('parseinline'), $wfMsgOpts);
					break;
				case 'wall_reopen':
					$actionText = wfMsgExt(static::getMessagePrefix($rc->getAttribute('rc_namespace')) . '-reopened-thread', array('parseinline'), $wfMsgOpts);
					break;
				default:
					$actionText = wfMsg(static::getMessagePrefix($rc->getAttribute('rc_namespace')) . '-unrecognized-log-action', $wfMsgOpts);
					break;
			}

			$s = '';
			$list->insertUserRelatedLinks($s, $rc);
			$s .= ' '.$actionText.' '.$list->insertComment($rc);
		}

		return true;
	}

	/**
	 * @brief Adjusting recent changes for Wall
	 *
	 * @desc This method clears or leaves as it was the text which is being send as a content of <li /> elements in RC page
	 *
	 * @param $changelist
	 * @param string $s
	 * @param RecentChange $rc
	 *
	 * @internal param \ChangesList $list
	 * @return true because this is a hook
	 *
	 * @author Andrzej 'nAndy' Lukaszewski
	 */
	static public function onOldChangesListRecentChangesLine($changelist, &$s, $rc) {
		if( $rc->getAttribute('rc_namespace') == NS_USER_WALL_MESSAGE ) {
			wfProfileIn( __METHOD__ );
			$rcTitle = $rc->getTitle();

			if( !($rcTitle instanceof Title) ) {
				//it can be media wiki deletion of an article -- we ignore them
				Wikia::log(__METHOD__, false, "WALL_NOTITLE_FROM_RC " . print_r($rc, true));
				wfProfileOut( __METHOD__ );
				return true;
			}

			$wm = new WallMessage($rcTitle);
			$wm->load();
			if( !$wm->isMain() ) {
				$wm = $wm->getTopParentObj();

				if( is_null($wm) ) {
					Wikia::log(__METHOD__, false, "WALL_NO_PARENT_MSG_OBJECT " . print_r($rc, true));
					wfProfileOut( __METHOD__ );
					return true;
				} else {
					$wm->load();
				}
			}

			if( $wm->isAdminDelete() && $rc->getAttribute('rc_log_action') != 'wall_admindelete' ) {
				wfProfileOut( __METHOD__ );
				return false;
			}
		}

		return true;
	}

	/**
	 * @brief Adjusting recent changes for Wall
	 *
	 * @desc This method decides rather put a log information about deletion or not
	 *
	 * @param WikiPage $wikipage a referance to WikiPage instance
	 * @param string $logType a referance to string with type of log
	 * @param Title $title
	 * @param string $reason
	 * @param boolean $hookAddedLogEntry set it to true if you don't want Article::doDeleteArticle() to add a log entry
	 *
	 * @return true because this is a hook
	 *
	 * @author Andrzej 'nAndy' Lukaszewski
	 */
	static public function onArticleDoDeleteArticleBeforeLogEntry(&$wikipage, &$logType, $title, $reason, &$hookAddedLogEntry) {
		if( $title instanceof Title && $title->getNamespace() == NS_USER_WALL_MESSAGE ) {
			$wm = new WallMessage($title);
			$parentObj = $wm->getTopParentObj();
			$reason = ''; //we don't want any comment
			$log = new LogPage( $logType );

			if( empty($parentObj) ) {
				//thread message
				$log->addEntry( 'delete', $title, $reason, array() );
			} else {
				//reply
				$result = $parentObj->load(true);

				if( $result ) {
					//if its parent still exists only this reply is being deleted, so log about it
					$log->addEntry( 'delete', $title, $reason, array() );
				}
			}

			$hookAddedLogEntry = true;
		}

		return true;
	}

	/**
	 * @brief Adjusting recent changes for Wall
	 *
	 * @desc This method decides rather put a log information about restored article or not
	 *
	 * @param PageArchive $pageArchive a referance to Article instance
	 * @param LogPage $logPage a referance to LogPage instance
	 * @param Title $title a referance to Title instance
	 * @param string $reason
	 * @param boolean $hookAddedLogEntry set it to true if you don't want Article::doDeleteArticle() to add a log entry
	 *
	 * @return true because this is a hook
	 *
	 * @author Andrzej 'nAndy' Lukaszewski
	 */
	static public function onPageArchiveUndeleteBeforeLogEntry(&$pageArchive, &$logPage, &$title, $reason, &$hookAddedLogEntry) {
		if( $title instanceof Title && $title->getNamespace() == NS_USER_WALL_MESSAGE ) {
			$wm = new WallMessage($title);
			$parentObj = $wm->getTopParentObj();
			$reason = ''; //we don't want any comment

			if( empty($parentObj) ) {
				//thread message
				$logPage->addEntry( 'restore', $title, $reason, array() );
			} else {
				//reply
				$result = $parentObj->load(true);

				if( $result ) {
					//if its parent still exists only this reply is being restored, so log about it
					$logPage->addEntry( 'restore', $title, $reason, array() );
				}
			}

			$hookAddedLogEntry = true;
		}

		return true;
	}

	/**
	 * @brief Adjusting select box with namespaces on RecentChanges page
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	static public function onXmlNamespaceSelectorAfterGetFormattedNamespaces( &$namespaces ) {
		if( defined('NS_USER_WALL') && defined('NS_USER_WALL_MESSAGE') ) {
			if( isset($namespaces[NS_USER_WALL]) && isset($namespaces[NS_USER_WALL_MESSAGE]) ) {
				unset($namespaces[NS_USER_WALL], $namespaces[NS_USER_WALL_MESSAGE]);
				$namespaces[NS_USER_WALL_MESSAGE] = wfMsg(static::getMessagePrefix(NS_USER_WALL) . '-namespace-selector-message-wall');
			}
		}

		return true;
	}

	/**
	 * @brief Adjusting title of a block group on RecentChanges page
	 *
	 * @param ChangesList $oChangeList
	 * @param string $r
	 * @param array $oRCCacheEntryArray an array of RCCacheEntry instances
	 * @param boolean $changeRecentChangesHeader a flag saying Wikia's hook if we want to change header or not
	 * @param $oTitle
	 * @param string $headerTitle string which will be put as a header for RecentChanges block
	 *
	 * @return bool
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	static public function onWikiaRecentChangesBlockHandlerChangeHeaderBlockGroup($oChangeList, $r, $oRCCacheEntryArray, &$changeRecentChangesHeader, $oTitle, &$headerTitle) {
		wfProfileIn(__METHOD__);

		$namespace = MWNamespace::getSubject($oTitle->getNamespace());

		if( WallHelper::isWallNamespace($namespace) ) {
			$changeRecentChangesHeader = true;

			$wm = new WallMessage($oTitle);
			$wallMsgUrl = $wm->getMessagePageUrl();
			$wallUrl = $wm->getWallUrl();
			$wallOwnerName = $wm->getWallOwnerName();
			$parent = $wm->getTopParentObj();
			$isMain = is_null($parent);

			if( !$isMain ) {
				$wm = $parent;
				unset($parent);
			}

			$wm->load();
			$wallMsgTitle = $wm->getMetaTitle();
			$headerTitle = wfMsg(static::getMessagePrefix($namespace).'-thread-group', array(Xml::element('a', array('href' => $wallMsgUrl), $wallMsgTitle), $wallUrl, $wallOwnerName));
		}

		wfProfileOut(__METHOD__);
		return true;
	}

	/**
	 * @brief get prefixed message name for recent changes, helpful for using wall on others namesapces
	 *
	 * @param int $namespace
	 * @return string
	 * @internal param string $message
	 */

	static protected function getMessagePrefix($namespace) {
		$namespace = MWNamespace::getSubject($namespace);
		$prefix = '';
		if(!wfRunHooks('WallRecentchangesMessagePrefix', array($namespace, &$prefix))) {
			return $prefix;
		}

		return 'wall-recentchanges';

	}

	/**
	 * @brief Adjusting blocks on Enhanced Recent Changes page
	 *
	 * @desc Changes $secureName which is an array key in RC cache by which blocks on enchance RC page are displayed
	 *
	 * @param ChangesList $changesList
	 * @param string $secureName
	 * @param RecentChange $rc
	 *
	 * @return bool
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	static public function onChangesListMakeSecureName($changesList, &$secureName, $rc) {
		if( WallHelper::isWallNamespace(intval($rc->getAttribute('rc_namespace'))) ) {
			$oTitle = $rc->getTitle();

			if( $oTitle instanceof Title ) {
				$wm = new WallMessage($oTitle);
				$parent = $wm->getTopParentObj();
				$isMain = is_null($parent);

				if( !$isMain ) {
					$wm = $parent;
					unset($parent);
				}

				$secureName = self::RC_WALL_SECURENAME_PREFIX.$wm->getArticleId();
			}
		}

		return true;
	}

	/**
	 * @brief Changing all links to Message Wall to blue links
	 *
	 * @param $skin
	 * @param $target
	 * @param $text
	 * @param $customAttribs
	 * @param $query
	 * @param $options
	 * @param $ret
	 * @internal param \Title $title
	 * @internal param bool $result
	 *
	 * @return true -- because it's a hook
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	static public function onLinkBegin($skin, $target, &$text, &$customAttribs, &$query, &$options, &$ret) {
		// paranoia
		if( !($target instanceof Title) ) {
			return true;
		}

		$namespace = $target->getNamespace();
		if( WallHelper::isWallNamespace($namespace) ) {

			// remove "broken" assumption/override
			$brokenKey = array_search('broken', $options);
			if ( $brokenKey !== false ) {
				unset($options[$brokenKey]);
			}

			// make the link "blue"
			$options[] = 'known';
		}

		return true;
	}

	/**
	 * getUserPermissionsErrors -  control access to articles in the namespace NS_USER_WALL_MESSAGE_GREETING
	 *
	 * @param $title
	 * @param $user
	 * @param $action
	 * @param $result
	 * @return bool
	 *
	 * @author Tomek Odrobny
	 *
	 * @access public
	 */
	static public function onGetUserPermissionsErrors( &$title, &$user, $action, &$result ) {

		if( $title->getNamespace() == NS_USER_WALL_MESSAGE_GREETING ) {
			$result = array();

			$parts = explode('/', $title->getText());
			$username = empty($parts[0]) ? '':$parts[0];

			if( $user->isAllowed('walledit') || $user->getName() == $username ) {
				$result = null;
				return true;
			} else {
				$result = array('badaccess-group0');
				return false;
			}
		}
		$result = null;
		return true;
	}

	static public function onComposeCommonBodyMail($title, &$keys, &$body, $editor) {
		return true;
	}

	static public function onArticleSaveComplete(&$article, &$user, $text, $summary, $minoredit, $watchthis, $sectionanchor, &$flags, $revision, &$status, $baseRevId) {
		$app = F::app();
		$title = $article->getTitle();

		if( !empty($app->wg->EnableWallExt)
				&& $title instanceof Title
				&& $title->getNamespace() === NS_USER_TALK
				&& !$title->isSubpage() )
		{
			//user talk page was edited -> redirect to user talk archive
			$helper = new WallHelper();

			$app->wg->Out->redirect(static::getWallTitle()->getFullUrl().'/'.$helper->getArchiveSubPageText(), 301);
			$app->wg->Out->enableRedirects(false);
		}

		return true;
	}

	static public function onAllowNotifyOnPageChange( $editor, $title ) {
		$app = F::app();
		if( in_array(MWNamespace::getSubject( $title->getNamespace() ), $app->wg->WallNS) || $title->getNamespace() == NS_USER_WALL_MESSAGE_GREETING){
			return false;
		}
		return true;
	}

	static public function onWatchArticle(&$user, &$article) {
		$app = F::app();
		$title = $article->getTitle();

		if( !empty($app->wg->EnableWallExt) && static::isWallMainPage($title) ) {
			static::processActionOnWatchlist($user, $title->getText(), 'add');
		}

		return true;
	}

	static public function onUnwatchArticle(&$user, &$article) {
		$app = F::app();
		$title = $article->getTitle();

		if( !empty($app->wg->EnableWallExt) && static::isWallMainPage($title) ) {
			static::processActionOnWatchlist($user, $title->getText(), 'remove');
		}

		return true;
	}

	static private function isWallMainPage($title) {
		if( $title->getNamespace() == NS_USER_WALL && strpos($title->getText(), '/') === false ) {
			return true;
		}

		return false;
	}

	static private function processActionOnWatchlist($user, $followedUserName, $action) {
		wfProfileIn(__METHOD__);

		$watchTitle = Title::newFromText($followedUserName, NS_USER);

		if( $watchTitle instanceof Title ) {
			$wl = new WatchedItem;
			$wl->mTitle = $watchTitle;
			$wl->id = $user->getId();
			$wl->ns = $watchTitle->getNamespace();
			$wl->ti = $watchTitle->getDBkey();

			if( $action === 'add' ) {
				$wl->addWatch();
			} elseif( $action === 'remove' ) {
				$wl->removeWatch();
			}
		} else {
			//just-in-case -- it shouldn't happen but if it does we want to know about it
			Wikia::log( __METHOD__, false, 'WALL_HOOK_ERROR: No title instance while syncing follows. User name: '.$followedUserName);
		}

		wfProfileOut(__METHOD__);
	}

	static public function onGetPreferences( $user, &$preferences ) {
		$app = F::app();

		if( $user->isLoggedIn() ) {
			if ($app->wg->EnableUserPreferencesV2Ext) {
				$message = 'wallshowsource-toggle-v2';
				$section = 'under-the-hood/advanced-displayv2';
			}
			else {
				$message = 'wallshowsource-toggle';
				$section = 'misc/wall';
			}
			$preferences['wallshowsource'] = array(
					'type' => 'toggle',
					'label-message' => $message, // a system message
					'section' => $section
			);

			if($user->isAllowed('walldelete')) {
				$preferences['walldelete'] = array(
						'type' => 'toggle',
						'label-message' => 'walldelete-toggle', // a system message
						'section' => $section
				);
			}
		}

		return true;
	}

	/**
	 * @brief Adjusting Special:Contributions
	 *
	 * @param ContribsPager $contribsPager
	 * @param String $ret string passed to wgOutput
	 * @param Object $row Std Object with values from database table
	 *
	 * @return true
	 */
	static public function onContributionsLineEnding(&$contribsPager, &$ret, $row) {

		if( isset( $row->page_namespace ) && in_array( MWNamespace::getSubject($row->page_namespace), array(NS_USER_WALL) ) ) {
			return static::contributionsLineEndingProcess( $contribsPager, $ret, $row );
		}
		return true;
	}

	static public function contributionsLineEndingProcess( &$contribsPager, &$ret, $row ) {

		wfProfileIn(__METHOD__);

		$app = F::app();

		$rev = new Revision($row);
		$page = $rev->getTitle();
		$page->resetArticleId($row->rev_page);
		$skin = $app->wg->User->getSkin();

		$wfMsgOptsBase = self::getMessageOptions(null, $row);

		$isThread = $wfMsgOptsBase['isThread'];
		$isNew = $wfMsgOptsBase['isNew'];

		$wfMsgOptsBase['createdAt'] = Xml::element('a', array('href' => $wfMsgOptsBase['articleFullUrl']), $app->wg->Lang->timeanddate( wfTimestamp(TS_MW, $row->rev_timestamp), true) );

		if( $isNew ) {
			$wfMsgOptsBase['DiffLink'] = wfMsg('diff');
		} else {
			$query = array(
				'diff' => 'prev',
				'oldid' => $row->rev_id,
			);

			$wfMsgOptsBase['DiffLink'] = Xml::element('a', array(
				'href' => $rev->getTitle()->getLocalUrl($query),
			), wfMsg('diff'));
		}

		$wallMessage = new WallMessage($page);
		$historyLink = $wallMessage->getMessagePageUrl(true).'?action=history';
		$wfMsgOptsBase['historyLink'] = Xml::element('a', array('href' => $historyLink), wfMsg('hist'));

		// Don't show useless link to people who cannot hide revisions
		$canHide = $app->wg->User->isAllowed('deleterevision');
		if( $canHide || ($rev->getVisibility() && $app->wg->User->isAllowed('deletedhistory')) ) {
			if( !$rev->userCan(Revision::DELETED_RESTRICTED) ) {
				$del = $skin->revDeleteLinkDisabled($canHide); // revision was hidden from sysops
			} else {
				$query = array(
					'type'		=> 'revision',
					'target'	=> $page->getPrefixedDbkey(),
					'ids'		=> $rev->getId()
				);
				$del = $skin->revDeleteLink($query, $rev->isDeleted(Revision::DELETED_RESTRICTED), $canHide);
			}
			$del .= ' ';
		} else {
			$del = '';
		}

		$ret = $del;
		if(wfRunHooks('WallContributionsLine', array(MWNamespace::getSubject($row->page_namespace), $wallMessage, $wfMsgOptsBase, &$ret) )) {
			$wfMsgOpts = array(
				$wfMsgOptsBase['articleFullUrl'],
				$wfMsgOptsBase['articleTitleTxt'],
				$wfMsgOptsBase['wallPageUrl'],
				$wfMsgOptsBase['wallPageName'],
				$wfMsgOptsBase['createdAt'],
				$wfMsgOptsBase['DiffLink'],
				$wfMsgOptsBase['historyLink']
			);

			if( $isThread && $isNew ) {
				$wfMsgOpts[7] = Xml::element('strong', array(), wfMsg('newpageletter').' ');
			} else {
				$wfMsgOpts[7] = '';
			}

			$ret .= wfMsg('wall-contributions-wall-line', $wfMsgOpts);

		}

		if( !$isNew ) {
			$summary = $rev->getComment();

			if(empty($summary)) {
				$msg = wfMsgForContent( static::getMessagePrefix($row->page_namespace).'-edit' );
			} else {
				$msg = wfMsgForContent( 'wall-recentchanges-summary', $summary );
			}

			$ret .= ' ' . Xml::openElement('span', array('class' => 'comment')) . $msg . Xml::closeElement('span');
		}

		wfProfileOut(__METHOD__);

		return true;
	}


	/**
	 * @brief Collects data basing on RC object or std object
	 * @desc Those lines of code were used a lot in this class. Better keep them in one place.
	 *
	 * @param RecentChanges $rc
	 * @param Object $row
	 *
	 * @return Array
	 */
	static public function getMessageOptions($rc = null, $row = null) {
		return WallHelper::getWallTitleData( $rc, $row );
	}


	static public function onFilePageImageUsageSingleLink(&$link, &$element) {

		if ( $element->page_namespace == NS_USER_WALL_MESSAGE ) {

			$titleData = WallHelper::getWallTitleData( null, $element );
			$a = '<a href="'.$titleData['articleFullUrl'].'">'.$titleData['articleTitleTxt'].'</a> ';
			$link = wfMsg( 'wall-recentchanges-thread-group', array( $a, $titleData['wallPageFullUrl'], $titleData['wallPageName'] ) );
		}
		return true;
	}

	/**
	 * @brief Adjusting Special:Whatlinkshere
	 *
	 * @param Object $row
	 * @param Integer $level
	 * @param Boolean $defaultRendering
	 *
	 * @return Boolean
	 */
	static public function onRenderWhatLinksHereRow(&$row, &$level, &$defaultRendering) {
		wfProfileIn(__METHOD__);

		if( isset($row->page_namespace) && in_array( intval($row->page_namespace), array( NS_USER_WALL_MESSAGE, NS_WIKIA_FORUM_BOARD_THREAD )) ) {
			$defaultRendering = false;

			$app = F::app();
			$wlhTitle = SpecialPage::getTitleFor( 'Whatlinkshere' );
			$wfMsgOptsBase = self::getMessageOptions(null, $row);

			$wfMsgOpts = array(
				$wfMsgOptsBase['articleFullUrl'],
				$wfMsgOptsBase['articleTitleTxt'],
				$wfMsgOptsBase['wallPageFullUrl'],
				$wfMsgOptsBase['wallPageName'],
				$wfMsgOptsBase['actionUser'],
				$wfMsgOptsBase['isThread'],
				$wfMsgOptsBase['isNew']
			);

			$app->wg->Out->addHtml(
					Xml::openElement('li') .
					wfMsg('wall-whatlinkshere-wall-line', $wfMsgOpts) .
					' (' .
					Xml::element('a', array(
							'href' => $wlhTitle->getFullUrl(array('target' => $wfMsgOptsBase['articleUrl'])),
					), wfMsg('whatlinkshere-links') ) .
					')' .
					Xml::closeElement('li')
			);
		}

		wfProfileOut(__METHOD__);
		return true;
	}

	/**
	 * @desc Changes fields in a DifferenceEngine instance to display correct content in <title /> tag
	 *
	 * @param DifferenceEngine $differenceEngine
	 * @param Revivion $oldRev
	 * @param Revivion $newRev
	 *
	 * @return true
	 */
	static public function onDiffViewHeader($differenceEngine, $oldRev, $newRev) {
		wfProfileIn(__METHOD__);

		$app = F::App();

		if( $app->wg->Title instanceof Title && WallHelper::isWallNamespace($app->wg->Title->getNamespace()) ) {
			$metaTitle = static::getMetatitleFromTitleObject($app->wg->Title);
			$differenceEngine->mOldPage->mPrefixedText = $metaTitle;
			$differenceEngine->mNewPage->mPrefixedText = $metaTitle;
		}

		wfProfileOut(__METHOD__);
		return true;
	}

	/**
	 * @desc Changes fields in a PageHeaderModule instance to display correct content in <h1 /> and <h2 /> tags
	 *
	 * @param PageHeaderModule $pageHeaderModule
	 * @param int $ns
	 * @param Boolean $isPreview
	 * @param Boolean $isShowChanges
	 * @param Boolean $isDiff
	 * @param Boolean $isEdit
	 * @param Boolean $isHistory
	 *
	 * @return true
	 */
	static public function onPageHeaderEditPage($pageHeaderModule, $ns, $isPreview, $isShowChanges, $isDiff, $isEdit, $isHistory) {
		if(  WallHelper::isWallNamespace($ns) && $isDiff ) {
			$app = F::App();

			$app->wg->Out->addExtensionStyle(AssetsManager::getInstance()->getSassCommonURL('extensions/wikia/Wall/css/WallDiffPage.scss'));

			$wmRef = '';
			$meta = static::getMetatitleFromTitleObject($app->wg->Title, $wmRef);
			$pageHeaderModule->title = wfMsg('oasis-page-header-diff', $meta );
			$pageHeaderModule->subtitle = Xml::element('a', array('href' => $wmRef->getMessagePageUrl()), wfMsg('oasis-page-header-back-to-article'));
		}

		return true;
	}

	/**
	 * @desc Helper method which gets meta title from an WallMessage instance; used in WallHooksHelper::onDiffViewHeader() and WallHooksHelper::onPageHeaderEditPage()
	 * @param Title $title
	 * @param mixed $wmRef a variable which value will be created WallMessage instance
	 *
	 * @return String
	 */
	static private function getMetatitleFromTitleObject($title, &$wmRef = null) {
		wfProfileIn(__METHOD__);

		$wm = new WallMessage($title);

		if( $wm instanceof WallMessage ) {
			$wm->load();
			$metaTitle = $wm->getMetaTitle();
			if( empty($metaTitle) ) {
			//if wall message is a reply
				$wmParent = $wm->getTopParentObj();
				if( $wmParent instanceof WallMessage ) {
					$wmParent->load();
					if( !is_null($wmRef) ) {
						$wmRef = $wmParent;
					}

					wfProfileOut(__METHOD__);
					return $wmParent->getMetaTitle();
				}
			}

			if( !is_null($wmRef) ) {
				$wmRef = $wm;
			}

			wfProfileOut(__METHOD__);
			return $metaTitle;
		}

		wfProfileOut(__METHOD__);
		return '';
	}

	/**
	 * @desc Changes link from User_talk: page to Message_wall: page of the user
	 *
	 * @param int $id id of user who's contributions page is displayed
	 * @param Title $nt instance of Title object of the page
	 * @param Array $tools a reference to an array with links in the header of Special:Contributions page
	 *
	 * @return true
	 */
	static public function onContributionsToolLinks($id, $nt, &$tools) {
		wfProfileIn(__METHOD__);

		$app = F::app();

		if( !empty($app->wg->EnableWallExt) && !empty($tools[0]) && $nt instanceof Title ) {
			//tools[0] is the first link in subheading of Special:Contributions which is "User talk" page
			$wallTitle = Title::newFromText($nt->getText(), NS_USER_WALL);

			if( $wallTitle instanceof Title ) {
				$tools[0] = Xml::element('a', array(
						'href' => $wallTitle->getFullUrl(),
						'title' => $wallTitle->getPrefixedText(),
				), wfMsg('wall-message-wall-shorten'));
			}
		}

		wfProfileOut(__METHOD__);
		return true;
	}

	/**
	 * @desc Changes user talk page link to user's message wall link added during MW1.19 migration
	 *
	 * @param integer $userId
	 * @param string $userText
	 * @param $userTalkLink
	 *
	 * @return true
	 */
	static public function onLinkerUserTalkLinkAfter($userId, $userText, &$userTalkLink) {
		wfProfileIn(__METHOD__);

		$app = F::app();
		static $cache = array();

		if( !empty($app->wg->EnableWallExt) ) {
			if(empty($cache[$userText])) {
				$messageWallPage = Title::makeTitle(NS_USER_WALL, $userText);
				$userTalkLink = Linker::link(
					$messageWallPage,
					wfMsgHtml('wall-message-wall-shorten'),
					array(),
					array(),
					array('known', 'noclasses')
				);
				$cache[$userText] = $userTalkLink;
			} else {
				$userTalkLink = $cache[$userText];
			}
		}

		wfProfileOut(__METHOD__);
		return true;
	}


	static public function onArticleBeforeVote(&$user_id, &$page, $vote) {
		$app = F::app();
		$title = Title::newFromId($page);

		if( ($title instanceof Title) && in_array(MWNamespace::getSubject( $title->getNamespace()  ), $app->wg->WallNS) ) {
			return false;
		}

		return true;
	}

	/**
	 * @static
	 * @param Block $block
	 * @param $user
	 * @return bool
	 */
	static public function onBlockIpComplete( $block, $user ) {
		$blockTarget = $block->getTarget();
		if ( $blockTarget instanceof User && $blockTarget->isLoggedIn() ) {
			$vote = new VoteHelper($block->getTarget(), null);
			$vote->invalidateUser();
		}
		return true;
	}

	static public function onBeforeCategoryData( &$extraConds ) {
		$app = F::App();

		$excludedNS = $app->wg->WallNS;
		foreach($app->wg->WallNS as $ns) {
			$excludedNS[] = MWNamespace::getTalk( $ns );
		}

		$extraConds[] = 'page_namespace not in('.implode(',', $excludedNS).')';
		return true;
	}

	static public function onGetRailModuleSpecialPageList( &$railModuleList ) {
		$app = F::App();

		$namespace = $app->wg->Title->getNamespace();
		$diff = $app->wg->Request->getVal('diff', false);

		$isDiff = !empty($diff) &&  $app->wg->Request->getVal('oldid', false);

		if ( $isDiff&& WallHelper::isWallNamespace( $namespace )) {
			//SuppressRail
			$railModuleList = array();
		}

		return true;
	}

	static public function onSpecialWikiActivityExecute( $out, $user ) {
		$app = F::App();
		$out->addScript("<script type=\"{$app->wg->JsMimeType}\" src=\"{$app->wg->ExtensionsPath}/wikia/Wall/js/WallWikiActivity.js\"></script>\n");
		$out->addExtensionStyle(AssetsManager::getInstance()->getSassCommonURL('extensions/wikia/Wall/css/WallWikiActivity.scss'));

		return true;
	}

	static protected function getQueryNS() {
		$app = F::App();
		$ns = array();

		foreach($app->wg->WallNS as $val) {
			$ns[] = $val;
			$ns[] = MWNamespace::getTalk($val);
		}
		return implode(',', $ns);
	}

	static public function onListredirectsPageGetQueryInfo( &$self, &$query ) {
		wfProfileIn(__METHOD__);

		$query['conds'][] = 'p1.page_namespace not in ('. static::getQueryNS() . ')';

		wfProfileOut(__METHOD__);
		return true;
	}

	static public function onWantedPagesGetQueryInfo( &$self, &$query ) {
		wfProfileIn(__METHOD__);

		$query['conds'][] = 'pl_namespace not in ('. static::getQueryNS() . ')';

		wfProfileOut(__METHOD__);
		return true;
	}

	static public function onBeforeInitialize( $title, $unused, $output, $user, $request, $mediawiki ) {
		global $wgHooks;
		if( !empty($title) && $title->isSpecial('Allpages') ) {
			$wgHooks['AfterLanguageGetNamespaces'][] = 'WallHooksHelper::onAfterLanguageGetNamespaces';
		}
		return true;
	}

	static public function onAfterLanguageGetNamespaces( &$namespaces ) {
		wfProfileIn(__METHOD__);
		$app = F::App();
		$title = $app->wg->Title;

		if(empty($title) || !$title->isSpecial('Allpages') ) {
			wfProfileOut(__METHOD__);
			return true;
		}

		foreach($app->wg->WallNS as $val) {
			$ns = MWNamespace::getTalk($val);
			if(!empty($namespaces[$ns])) {
				unset($namespaces[$ns]);
			}
		}
		wfProfileOut(__METHOD__);
		return true;
	}

	/**
	 * create needed tables
	 */
	public static function onAfterToggleFeature($name, $val) {
		global $IP;
		if($name == 'wgEnableWallExt' || $name == 'wgEnableForumExt') {
			$db = wfGetDB(DB_MASTER);
			if(!$db->tableExists('wall_history')) {
				$db->sourceFile($IP."/extensions/wikia/Wall/sql/wall_history_local.sql");
			}

			if(!$db->tableExists('wall_related_pages')) {
				$db->sourceFile($IP."/extensions/wikia/Wall/sql/wall_related_pages.sql");
			}

			$nm = new NavigationModel();
			$nm->clearMemc( NavigationModel::WIKIA_GLOBAL_VARIABLE );
		}
		return true;
	}

	//TODO: implement this :)
	static public function onDiffLoadText( $self, &$oldtext, &$newtext ) {
		/*

		$oldtext = ArticleComment::removeMetadataTag($oldtext);
		$newtext = ArticleComment::removeMetadataTag($newtext);; */
		return true;
	}

	static public function onAdvancedBoxSearchableNamespaces(&$namespace) {
		$namespace = WallHelper::clearNamespaceList($namespace);
		return true;
	}

	static public function onArticleRobotPolicy( &$policy, Title $title ) {
		$ns = $title->getNamespace();
		if ( $ns == NS_USER_WALL_MESSAGE ) {
			$policy = array(
				'index'  => 'index',
				'follow' => 'follow'
			);
		}
		elseif ( $ns == NS_USER_WALL ) {
			$policy = array(
				'index'  => 'noindex',
				'follow' => 'nofollow'
			);
		}
		return true;
	}

	/**
	 * HAWelcome
	 *
	 * @param $prefixedText
	 * @param Title $title
	 *
	 * @internal param $String $$prefixedText
	 * @access public
	 * @author Tomek
	 *
	 * @return boolean
	 */

	static public function onHAWelcomeGetPrefixText( &$prefixedText, $title ) {

		if ( $title->exists() && WallHelper::isWallNamespace( $title->getNamespace() ) ) {
			$wallMessage = WallMessage::newFromId( $title->getArticleID() );
			$wallMessageParent = $wallMessage->getTopParentObj();
			if ( empty( $wallMessageParent ) ) {
				$wallMessageParent = $wallMessage;
			}
			$wallMessage->load();
			$wallMessageParent->load();
			$pageId = $wallMessage->getMessagePageId();
			$postFix = $wallMessage->getPageUrlPostFix();
			$threadTitle = Title::newFromText( $pageId, NS_USER_WALL_MESSAGE );
			$prefixedText = $threadTitle->getPrefixedText() . ( empty( $postFix ) ? '' : "#{$postFix}" ) . '|' . $wallMessageParent->getMetaTitle();
		}

		return true;
	}


	static public function onChangesListItemGroupRegular(&$link, &$rcObj) {
		if( WallHelper::isWallNamespace(intval($rcObj->getAttribute('rc_namespace'))) ){

			$wallMsg = WallMessage::newFromId($rcObj->getAttribute('rc_cur_id'));
			if(!empty($wallMsg)) {
				/* @var $wallMsg Wall */

				$url = $wallMsg->getMessagePageUrl();
				$link = '<a href="'.$url.'">'.$rcObj->timestamp.'</a>';
				$rcObj->curlink = '<a href="'.$url.'">'.wfMsg('cur').'</a>';
			}
		}
		return true;
	}

	static public function onAccountNavigationModuleAfterDropdownItems( &$possibleItems, &$personal_urls ) {
		$personal_urls[ 'mytalk' ][ 'class' ] = 'message-wall';
		return true;
	}

	/**
	 * Add user links to toolbar in Monobook for Message Wall
	 *
	 * @access public
	 * @author Sactage
	 *
	 * @param SkinTemplate $monobook
	 * @return boolean
	 */
	static public function onBuildMonobookToolbox( &$monobook ) {
		$app = F::app();
		$title = $app->wg->Title;
		$curUser = $app->wg->User;
		if ($title->getNamespace() === NS_USER_WALL) {
			$user = User::newFromName($title->getText(), false);
		} else {
			return true;
		}
		echo '<li id="t-contributions">' . Linker::link(SpecialPage::getSafeTitleFor('Contributions', $user->getName()), wfMsgHtml('contributions')) . '</li>';
		if ($curUser->isAllowed('block')) {
			echo '<li id="t-blockip">' . Linker::link(SpecialPage::getSafeTitleFor('Block', $user->getName()), wfMsgHtml('block')) . '</li>';
		}
		if ( $monobook->getSkin()->showEmailUser( $user ) ) {
			echo '<li id="t-emailuser">' . Linker::link(SpecialPage::getSafeTitleFor('EmailUser', $user->getName()), wfMsgHtml('emailuser')) . '</li>';
		}
		echo '<li id="t-log">' . Linker::link(SpecialPage::getTitleFor('Log'), wfMsgHtml('log'), array(), array('user' => $user->getName())) . '</li>';
		return true;
	}

	/**
	 * Fills the $info parameter with a human readable article title and a URL that links directly to
	 * a wall or forum post
	 *
	 * @param $info - Associative array to hold the result
	 * @param $title - The article title of the wall/forum post
	 * @param $ns - The namespace of the wall/forum post
	 * @return bool - The status of the hook
	 */
	public static function onFormatForumLinks( &$info, $title, $ns ) {

		// Handle message wall and forum board links
		if ( isset($ns) && in_array($ns, array(NS_USER_WALL_MESSAGE, NS_WIKIA_FORUM_BOARD_THREAD)) ) {
			// The method expects a DB result row. Set the data and then pass it as an object
			$row['page_namespace'] = $ns;
			$row['page_title'] = $title;
			$opts = WallHelper::getWallTitleData( null, (object)$row );

			// Set the human readable title and a link
			$info['titleText'] = $opts['articleTitleTxt'];
			$info['url'] = $opts['articleFullUrl'];
		}

		return true;
	}
}
