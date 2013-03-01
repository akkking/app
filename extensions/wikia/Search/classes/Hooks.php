<?php
/**
 * Class definition for Wikia\Search\Hooks
 */
namespace Wikia\Search;

class Hooks
{
	/**
	 * Sends delete request to article if it gets deleted
	 * @param WikiPage $article
	 * @param User $user
	 * @param integer $reason
	 * @param integer $id
	 */
	public function onArticleDeleteComplete( &$article, User &$user, $reason, $id ) {
		return $this->indexer->deleteArticle( $id );
	}
	
	/**
	 * Reindexes the page
	 * @param WikiPage $article
	 * @param User $user
	 * @param string $text
	 * @param string $summary
	 * @param bool $minoredit
	 * @param bool $watchthis
	 * @param string $sectionanchor
	 * @param array $flags
	 * @param Revision $revision
	 * @param int $status
	 * @param int $baseRevId
	 */
	public function onArticleSaveComplete( &$article, &$user, $text, $summary,
	        $minoredit, $watchthis, $sectionanchor, &$flags, $revision, &$status, $baseRevId ) {
		return $this->indexer->reindexBatch( array( $article->getTitle()->getArticleID() ) );
	}
	
	/**
	 * Reindexes page on undelete
	 * @param Title $title
	 * @param int $create
	 */
	public function onArticleUndelete( $title, $create ) {
		return $this->indexer->reindexBatch( array( $title->getArticleID() ) );
	}
	
	/**
	 * Issues a reindex event or deletes all docs, depending on whether a wiki is being closed or reopened
	 * @see    WikiaSearchIndexerTest::testOnWikiFactoryPublicStatusChangeClosed
	 * @see    WikiaSearchIndexerTest::testOnWikiFactoryPublicStatusChangeOpened
	 * @param  int    $city_public
	 * @param  int    $city_id
	 * @param  string $reason
	 * @return bool
	 */
	public function onWikiFactoryPublicStatusChange( &$city_public, &$city_id, $reason ) {
		return ( $city_public < 1 ) 
		    ? $this->indexer->deleteWikiDocs( $city_id )
		    : $this->indexer->reindexWiki( $city_id );
	}
	
	/**
	 * Used to configure the user preference pane settings for search. 
	 * This is a registered hook function of the samme name.
	 * 
	 * @param User $user
	 * @param array $defaultPreferences
	 */ 
	public static function onGetPreferences($user, &$defaultPreferences) {
		wfProfileIn( __METHOD__ );

		// removes core mw search prefs
		$defunctPreferences = array(
			'searchlimit',
			'contextlines',
			'contextchars',
			'disablesuggest',
			'searcheverything',
			'searchnamespaces',
		);

		foreach ( $defunctPreferences as $goAway ) {
			unset( $defaultPreferences[$goAway] );
		}

		$defaultPreferences["enableGoSearch"] = array(
			'type'			=> 'toggle',
			'label-message'	=> array('wikiasearch2-enable-go-search'),
			'section'		=> 'under-the-hood/advanced-displayv2',
		);

		$defaultPreferences["searchAllNamespaces"] = array(
			'type'			=> 'toggle',
			'label-message'	=> array('wikiasearch2-search-all-namespaces'),
			'section'		=> 'under-the-hood/advanced-displayv2',
		);

		wfProfileOut( __METHOD__ );
		return true;
	}
	
	/**
	 * WikiaMobile hook to add assets so they are minified and concatenated
	 * @see    WikiaSearchControllerTest::testOnWikiaMobileAssetsPackages
	 * @param  array $jsHeadPackages
	 * @param  array $jsBodyPackages
	 * @param  array $scssPackages
	 * @return boolean
	 */
	public function onWikiaMobileAssetsPackages( &$jsHeadPackages, &$jsBodyPackages, &$scssPackages){
		if( \F::app()->wg->Title->isSpecial( 'Search' ) ) {
			$jsBodyPackages[] = 'wikiasearch_js_wikiamobile';
			$scssPackages[] = 'wikiasearch_scss_wikiamobile';
		}
		return true;
	}
}