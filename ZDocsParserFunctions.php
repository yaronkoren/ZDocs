<?php

/**
 * Parser functions for ZDocs.
 *
 * @file
 * @ingroup ZDocs
 *
 * The following parser functions are defined: #zdocs_product,
 * #zdocs_version, #zdocs_manual and #zdocs_topic.
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
 * This function defines a manual page.
 *
 * '#zdocs_topic' is called as:
 * {{#zdocs_topic:display name=|toc name=|inherit}}
 *
 * This function defines a topic page.
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
			return $returnMsg;
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

