<?php
class ArticleService extends WikiaObject {
	const MAX_LENGTH = 500;
	const CACHE_VERSION = 6;
	const SUFFIX = '...';

	private $article = null;
	private $tags = array(
			'script',
			'style',
			'noscript',
			'table',
			'figure',
			'figcaption',
			'aside',
			'details',
			'h1',
			'h2',
			'h3',
			'h4',
			'h5',
			'h6'
	);
	private $patterns = array(
		//strip decimal entities
		'/&#\d{2,5};/ue' => '',
		//strip hex entities
		'/&#x[a-fA-F0-7]{2,8};/ue' => '',
		//this should be always the last
		'/\s+/' => ' '
	);
	private static $localCache = array();

	public function __construct( $articleOrId = null ) {
		parent::__construct();

		if ( !is_null( $articleOrId ) ) {
			if ( is_numeric( $articleOrId ) ) {
				$this->setArticleById( $articleOrId );
			} elseif ( $articleOrId instanceof Article ) {
				$this->setArticle( $articleOrId );
			}
		}
	}

	public function setArticle( Article $article ) {
		$this->article = $article;
	}

	public function setArticleById( $articleId ) {
		$this->article = F::build('Article',array($articleId), 'newFromID');
	}

	/**
	 * get text snippet of article content
	 *
	 * @param int $articleId article id
	 * @param int $length snippet length
	 * @return string
	 */
	public function getTextSnippet( $length = 100 ) {
		//don't allow more than the maximum to avoid flooding Memcached
		if ( $length > self::MAX_LENGTH ) {
			throw new WikiaException( 'Maximum allowed length is ' . self::MAX_LENGTH );
		}

		$suffixLen = strlen( self::SUFFIX );

		// it may sometimes happen that the aricle is just not there
		if ( is_null( $this->article ) || $length <= $suffixLen ) {
			return '';
		}

		$this->wf->profileIn( __METHOD__ );

		$id = $this->article->getID();

		//memoize to avoid Memcache access overhead
		//when the same article needs to be processed
		//more than once in the same process
		if ( array_key_exists( $id, self::$localCache ) ) {
			$text = self::$localCache[$id];
		} else {
			$key = self::getCacheKey( $id );
			$app = $this->app;
			$article = $this->article;
			$tags = $this->tags;
			$pats = $this->patterns;
			$text = self::$localCache[$id] = WikiaDataAccess::cache(
				$key,
				86400 /*24h*/,
				function() use ( $app, $article, $tags, $pats, $length ){
					$app->wf->profileIn( __METHOD__ . '::CacheMiss' );

					//get standard parser cache for anons,
					//99% of the times it will be available but
					//generate it in case is not
					$page = $article->getPage();
					$opts = $page->makeParserOptions( new User() );
					$content = $page->getParserOutput( $opts )->getText();

					//Run hook to allow wikis to modify the content (ie: customize their snippets) before the stripping and length limitations are done.
					wfRunHooks( 'ArticleService::getTextSnippet::beforeStripping', array( &$article, &$content, $length ) );

					if ( mb_strlen( $content ) > 0 ) {
						//remove all unwanted tag pairs and their contents
						foreach ( $tags as $tag ) {
							$content = preg_replace( "/<{$tag}\b[^>]*>.*<\/{$tag}>/imsU", '', $content );
						}

						//cleanup remaining tags
						$content = strip_tags( $content );

						//apply some replacements
						foreach ( $pats as $reg => $rep ) {
							$content = preg_replace( $reg, $rep, $content );
						}

						//decode entities
						$content = html_entity_decode( $content );
						$content = trim( $content );

						if ( mb_strlen( $content ) > ArticleService::MAX_LENGTH ) {
							$content = mb_substr( $content, 0, ArticleService::MAX_LENGTH );
						}
					}

					$app->wf->profileOut( __METHOD__ . '::CacheMiss' );
					return $content;
				}
			);
		}

		$textLen = mb_strlen( $text );

		if ( $textLen > $length ) {
			$maxLen = $length - $suffixLen;
			$cutPos = mb_strrpos( $text, ' ',  -( $textLen - $maxLen ) );

			if ( $cutPos === false || $cutPos > $maxLen ) {
				$cutPos = $maxLen;
			}

			$snippet = mb_substr( $text, 0, $cutPos );
			$snippet = preg_replace( '/\W$/', '', $snippet ) . self::SUFFIX;
		} else {
			$snippet = $text;
		}

		$this->wf->profileOut( __METHOD__ );
		return $snippet;
	}

	static public function getCacheKey( $articleId ) {
		return F::app()->wf->MemcKey(
			__CLASS__,
			self::CACHE_VERSION,
			$articleId
		);
	}

	/**
	 * Clear the snippet cache when the page is purged
	 */
	static public function onArticlePurge( WikiPage $page ) {
		/**
		 * @var $service ArticleService
		 */
		if ( $page->exists() ) {
			$id = $page->getId();

			F::app()->wg->Memc->delete( self::getCacheKey( $id ) );

			if ( array_key_exists( $id, self::$localCache ) ) {
				unset( self::$localCache[$id] );
			}
		}

		return true;
 	}

	/**
	 * Clear the cache when the page is edited
	 */
	static public function onArticleSaveComplete( WikiPage &$page, &$user, $text, $summary, $minoredit, $watchthis, $sectionanchor, &$flags, $revision, &$status, $baseRevId ) {
		/**
		 * @var $service ArticleService
		 */
		if ( $page->exists() ) {
			$id = $page->getId();

			F::app()->wg->Memc->delete( self::getCacheKey( $id ) );

			if ( array_key_exists( $id, self::$localCache ) ) {
				unset( self::$localCache[$id] );
			}
		}

		return true;
	}
}