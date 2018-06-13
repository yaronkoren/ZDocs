<?php

use MediaWiki\MediaWikiServices;

/**
 * Parser functions for ZDocs.
 *
 * @file
 * @ingroup ZDocs
 *
 * The following parser functions are defined: #zdocs_product,
 * #zdocs_version, #zdocs_manual, #zdocs_topic and #zdocs_link.
 *
 * '#zdocs_product' is called as:
 * {{#zdocs_product:display name=|admins=|editors=|previewers=}}
 *
 * This function defines a product page.
 *
 * '#zdocs_version' is called as:
 * {{#zdocs_version:status=|manuals list=}}
 *
 * This function defines a version page.
 *
 * '#zdocs_manual' is called as:
 * {{#zdocs_manual:display name=|topics list=|pagination|inherit}}
 *
 * "topics list=" holds a bulleted hierarchy of topic names. Names that
 * begin with a "!" are considered "standalone topics" - these are topic
 * pages that are not defined as being part of this manual, and their full
 * page name must be specified.
 *
 * This function defines a manual page.
 *
 * '#zdocs_topic' is called as:
 * {{#zdocs_topic:display name=|toc name=|inherit}}
 *
 * This function defines a topic page.
 *
 * '#zdocs_link' is called as:
 * {{#zdocs_link:product=|version=|manual=|topic=}}
 *
 * This function displays a link to another page in the ZDocs system.
 */

class ZDocsParserFunctions {

	/**
	 * #zdocs_product
	 */
	static function renderProduct( &$parser ) {
		list( $parentPageName, $thisPageName ) = ZDocsUtils::getPageParts( $parser->getTitle() );
		$returnMsg = ZDocsProduct::checkPageEligibility( $parentPageName, $thisPageName );
		if ( $returnMsg != null ) {
			return $returnMsg;
		}

		$displayTitle = null;

		$params = func_get_args();
		array_shift( $params ); // We don't need the parser.
		$processedParams = self::processParams( $parser, $params );

		$parserOutput = $parser->getOutput();
		$parserOutput->addModules( 'ext.zdocs.main' );

		$parserOutput->setProperty( 'ZDocsPageType', 'Product' );

		foreach ( $processedParams as $paramName => $value ) {
			if ( $paramName == 'display name' ) {
				$displayTitle = $value;
			} elseif ( $paramName == 'admins' ) {
				$usernames = explode( ',', $value );
				foreach ( $usernames as $username ) {
					$username = trim( str_replace( '_', ' ', $username ) );
					$parserOutput->setProperty( 'ZDocsProductAdmin', $username );
				}
			} elseif ( $paramName == 'editors' ) {
				$usernames = explode( ',', $value );
				foreach ( $usernames as $username ) {
					$username = trim( str_replace( '_', ' ', $username ) );
					$parserOutput->setProperty( 'ZDocsProductEditor', $username );
				}
			} elseif ( $paramName == 'previewers' ) {
				$usernames = explode( ',', $value );
				foreach ( $usernames as $username ) {
					$username = trim( str_replace( '_', ' ', $username ) );
					$parserOutput->setProperty( 'ZDocsProductPreviewer', $username );
				}
			}
		}

		if ( $displayTitle == null ) {
			$displayTitle = $thisPageName;
		}
		$parserOutput->setDisplayTitle( $displayTitle );
	}

	static function renderVersion( &$parser ) {
		list( $parentPageName, $thisPageName ) = ZDocsUtils::getPageParts( $parser->getTitle() );
		$returnMsg = ZDocsVersion::checkPageEligibility( $parentPageName, $thisPageName );
		if ( $returnMsg != null ) {
			return $returnMsg;
		}

		$params = func_get_args();
		array_shift( $params ); // We don't need the parser.
		$processedParams = self::processParams( $parser, $params );

		$parserOutput = $parser->getOutput();
		$parserOutput->addModules( 'ext.zdocs.main' );

		$parserOutput->setProperty( 'ZDocsPageType', 'Version' );
		$parserOutput->setProperty( 'ZDocsParentPage', $parentPageName );

		foreach( $processedParams as $paramName => $value ) {
			if ( $paramName == 'inherit' && $value == null ) {
				$parserOutput->setProperty( 'ZDocsInherit', true );
			} elseif ( $paramName == 'status' ) {
				// @TODO - put in check here for values.
				$parserOutput->setProperty( 'ZDocsStatus', $value );
			} elseif ( $paramName == 'manuals list' ) {
				$parserOutput->setProperty( 'ZDocsManualsList', $value );
			}
		}
	}

	static function renderManual( &$parser ) {
		list( $parentPageName, $thisPageName ) = ZDocsUtils::getPageParts( $parser->getTitle() );
		$returnMsg = ZDocsManual::checkPageEligibility( $parentPageName, $thisPageName );
		if ( $returnMsg != null ) {
			return $returnMsg;
		}

		$displayTitle = null;
		$inherits = false;

		$params = func_get_args();
		array_shift( $params ); // We don't need the parser.
		$processedParams = self::processParams( $parser, $params );

		$parserOutput = $parser->getOutput();
		$parserOutput->addModules( 'ext.zdocs.main' );

		$parserOutput->setProperty( 'ZDocsPageType', 'Manual' );
		$parserOutput->setProperty( 'ZDocsParentPage', $parentPageName );

		foreach( $processedParams as $paramName => $value ) {
			if ( $paramName == 'display name' ) {
				$parserOutput->setProperty( 'ZDocsDisplayName', $value );
				$displayTitle = $value;
			} elseif ( $paramName == 'inherit' && $value == null ) {
				$parserOutput->setProperty( 'ZDocsInherit', true );
				$inherits = true;
			} elseif ( $paramName == 'topics list' ) {
				$parserOutput->setProperty( 'ZDocsTopicsList', $value );
			} elseif ( $paramName == 'pagination' && $value == null ) {
				$parserOutput->setProperty( 'ZDocsPagination', true );
			}
		}

		if ( $displayTitle == null && $inherits ) {
			$manual = new ZDocsManual( $parser->getTitle() );
			$inheritedDisplayName = $manual->getInheritedParam( 'ZDocsDisplayName' );
			if ( $inheritedDisplayName != null ) {
				$displayTitle = $inheritedDisplayName;
			}
		}
		if ( $displayTitle == null ) {
			$displayTitle = $thisPageName;
		}

		$parserOutput->setDisplayTitle( $displayTitle );
	}

	static function renderTopic( &$parser ) {
		//if ($parser->getTitle() == null ) return;
		list( $parentPageName, $thisPageName ) = ZDocsUtils::getPageParts( $parser->getTitle() );
		$returnMsg = ZDocsTopic::checkPageEligibility( $parentPageName, $thisPageName );
		if ( $returnMsg != null ) {
			// It's an "invalid" topic.
			$parentPageName = null;
			$thisPageName = $parser->getTitle()->getFullText();
		}

		$displayTitle = null;
		$tocDisplayTitle = null;
		$inherits = false;

		$params = func_get_args();
		array_shift( $params ); // We don't need the parser.
		$processedParams = self::processParams( $parser, $params );

		$parserOutput = $parser->getOutput();
		$parserOutput->addModules( 'ext.zdocs.main' );

		$parserOutput->setProperty( 'ZDocsPageType', 'Topic' );
		$parserOutput->setProperty( 'ZDocsParentPage', $parentPageName );

		foreach( $processedParams as $paramName => $value ) {
			if ( $paramName == 'display name' ) {
				$parserOutput->setProperty( 'ZDocsDisplayName', $value );
				$displayTitle = $value;
			} elseif ( $paramName == 'toc name' ) {
				$tocDisplayTitle = $value;
			} elseif ( $paramName == 'inherit' && $value == null ) {
				$parserOutput->setProperty( 'ZDocsInherit', true );
				$inherits = true;
			}
		}

		if ( $displayTitle == null && $inherits ) {
			$topic = new ZDocsTopic( $parser->getTitle() );
			$inheritedDisplayName = $topic->getInheritedParam( 'ZDocsDisplayName' );
			if ( $inheritedDisplayName != null ) {
				$displayTitle = $inheritedDisplayName;
			}
		}
		if ( $displayTitle == null ) {
			$displayTitle = $thisPageName;
		}
		$parserOutput->setDisplayTitle( $displayTitle );

		if ( $tocDisplayTitle == null && $inherits ) {
			$topic = new ZDocsTopic( $parser->getTitle() );
			$inheritedTOCName = $topic->getInheritedParam( 'ZDocsTOCName' );
			if ( $inheritedTOCName != null ) {
				$tocDisplayTitle = $inheritedTOCName;
			}
		}

		if ( $tocDisplayTitle == null ) {
			$tocDisplayTitle = $displayTitle;
		}
		$parserOutput->setProperty( 'ZDocsTOCName', $tocDisplayTitle );
	}

	static function renderLink( &$parser ) {
		$zdPage = ZDocsUtils::pageFactory( $parser->getTitle() );
		if ( $zdPage == null ) {
			return true;
		}

		$params = func_get_args();
		array_shift( $params ); // We don't need the parser.
		$processedParams = self::processParams( $parser, $params );

		$product = $version = $manual = $topic = null;
		foreach( $processedParams as $paramName => $value ) {
			if ( $paramName == 'product' ) {
				$product = $value;
			} elseif ( $paramName == 'version' ) {
				$version = $value;
			} elseif ( $paramName == 'manual' ) {
				$manual = $value;
			} elseif ( $paramName == 'topic' ) {
				$topic = $value;
			}
		}

		if ( $topic != null ) {
			$linkedPageType = 'topic';
		} elseif ( $manual != null ) {
			$linkedPageType = 'manual';
		} elseif ( $version != null ) {
			$linkedPageType = 'version';
		} elseif ( $product != null ) {
			$linkedPageType = 'product';
		} else {
			return "<div class=\"error\">At least one of product, version, manual and topic must be specified.</div>";
		}

		if ( $linkedPageType == 'product' || $linkedPageType == 'version' ) {
			if ( $topic != null && $manual == null ) {
				return "<div class=\"error\">A 'manual' value must be specified in this case.</div>";
			}
		}

		if ( $linkedPageType == 'product' ) {
			if ( $manual != null && $version == null ) {
				return "<div class=\"error\">A 'version' value must be specified in this case.</div>";
			}
		}
		// If it's a topic, there's a chance that it's "standalone",
		// meaning that it gets its data from the query string.
		// Unfortunately, we need to disable the cache in order to see
		// the query string, so we have to do that regardless of
		// whether it's standalone or not.
		if ( get_class( $zdPage ) == 'ZDocsTopic' ) {
			$parser->disableCache();
			global $wgRequest;
			$curProduct = $wgRequest->getVal( 'product' );
			$curVersion = $wgRequest->getVal( 'version' );
			$curManual = $wgRequest->getVal( 'manual' );
		}

		// Get this page's own product, and possibly version and manual.
		if ( get_class( $zdPage ) == 'ZDocsProduct' ) {
			$curProduct = $zdPage->getActualName();
		} elseif ( $curProduct != null && $curVersion != null && $curManual != null ) {
			// NO need to do anything; the values have laready been
			// set.
		} else {
			list( $curProduct, $curVersion ) = $zdPage->getProductAndVersionStrings();
			$curManual = $zdPage->getManual()->getActualName();
		}

		if ( $product != null ) {
			$linkedPageName = $product;
		} else {
			$linkedPageName = $curProduct;
		}
		if ( $linkedPageType == 'version' || $linkedPageType == 'manual' || $linkedPageType == 'topic' ) {
			if ( $version != null ) {
				$linkedPageName .= '/' . $version;
			} else {
				$linkedPageName .= '/' . $curVersion;
			}
		}
		if ( $linkedPageType == 'manual' || $linkedPageType == 'topic' ) {
			if ( $manual != null ) {
				$linkedPageName .= '/' . $manual;
			} else {
				$linkedPageName .= '/' . $curManual;
			}
		}
		if ( $linkedPageType == 'topic' ) {
			$linkedPageName .= '/' . $topic;
		}

		$linkedTitle = Title::newFromText( $linkedPageName );

		$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();
		$linkStr = $linkRenderer->makeLink( $linkedTitle );

		return array( $linkStr, 'noparse' => true, 'isHTML' => true );
	}

	static function processParams( $parser, $params ) {
		$processedParams = array();

		// Assign params.
		foreach ( $params as $i => $param ) {
			$elements = explode( '=', $param, 2 );

			// Set param name and value.
			if ( count( $elements ) > 1 ) {
				$paramName = trim( $elements[0] );
				// Parse (and sanitize) parameter values.
				// We call recursivePreprocess() and not
				// recursiveTagParse() so that URL values will
				// not be turned into links.
				//$value = trim( $parser->recursivePreprocess( $elements[1] ) );
				$value = $elements[1];
			} else {
				$paramName = trim( $param );
				$value = null;
			}
			$processedParams[$paramName] = $value;
		}

		return $processedParams;
	}
}

