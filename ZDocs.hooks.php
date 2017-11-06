<?php

class ZDocsHooks {

	public static function registerParserFunctions( &$parser ) {
		$parser->setFunctionHook( 'zdocs_product', array( 'ZDocsParserFunctions', 'renderProduct' ) );
		$parser->setFunctionHook( 'zdocs_version', array( 'ZDocsParserFunctions', 'renderVersion' ) );
		$parser->setFunctionHook( 'zdocs_manual', array( 'ZDocsParserFunctions', 'renderManual' ) );
		$parser->setFunctionHook( 'zdocs_topic', array( 'ZDocsParserFunctions', 'renderTopic' ) );
		return true;
	}

	static public function checkPermissions( &$title, &$user, $action, &$result ) {
		$zdPage = ZDocsUtils::pageFactory( $title );
		if ( $zdPage == null ) {
			return true;
		}
		if ( $action == 'edit' || $action == 'formedit' ) {
			if ( !$zdPage->userCanEdit( $user ) ) {
				// For some reason this also needs to return
				// false... $result will get overridden
				// otherwise?
				$result = false;
				return false;
			}
		} elseif ( $action == 'read' ) {
			if ( !$zdPage->userCanView( $user ) ) {
				// For some reason this also needs to return
				// false... $result will get overridden
				// otherwise?
				$result = false;
				return false;
			}
		}
		return true;
	}

	static public function addTextToPage( &$out, &$text ) {
		$action = Action::getActionName( $out->getContext() );
		if ( $action != 'view' ) {
			return true;
		}
		$title = $out->getTitle();
		$zdPage = ZDocsUtils::pageFactory( $title );
		if ( $zdPage == null ) {
			return true;
		}
		$inheritedPage = $zdPage->getInheritedPage();
		if ( $inheritedPage !== null ) {
			$revision = Revision::newFromTitle( $inheritedPage->getTitle() );
			$inheritedPageText = $revision->getContent()->getNativeData();
			global $wgParser;
			$text .= $wgParser->parse( $inheritedPageText, $title, new ParserOptions() )->getText();
		}
		$text = $zdPage->getHeader() . $text . $zdPage->getFooter();
		return true;
	}

	/**
	 * Based on function of the same name in ApprovedRevs.hook.php, from
	 * the Approved Revs extension.
	 */
	static public function setSearchText( $article, $user, $content,
		$summary, $isMinor, $isWatch, $section, $flags, $revision,
		$status, $baseRevId, $undidRevId ) {

		if ( is_null( $revision ) ) {
			return true;
		}

		$title = $article->getTitle();
		$zdPage = ZDocsUtils::pageFactory( $title );

		if ( $zdPage == null ) {
			return true;
		}

		if ( !$zdPage->inheritsPageContents() ) {
			return true;
		}

		// @TODO - does the template call need to be added/removed/etc.?
		//$newSearchText = $zdPage->getPageContents();

		//DeferredUpdates::addUpdate( new SearchUpdate( $title->getArticleID(), $title->getText(), $newSearchText ) );

		return true;
	}

	/**
	 * Register wiki markup words associated with MAG_NIFTYVAR as a variable
	 *
	 * @param array $customVariableIDs
	 * @return boolean
	 */
	public static function declareVarIDs( &$customVariableIDs ) {
		$customVariableIDs[] = 'MAG_ZDOCSPRODUCT';
		$customVariableIDs[] = 'MAG_ZDOCSVERSION';
		$customVariableIDs[] = 'MAG_ZDOCSMANUAL';

		return true;
	}

	/**
	 * Assign a value to our variable
	 *
	 * @param Parser $parser
	 * @param array $cache
	 * @param string $magicWordId
	 * @param string $ret
	 * @return boolean
	 */
	public static function assignAValue(&$parser, &$cache, &$magicWordId, &$ret) {
		$handledIDs = array( 'MAG_ZDOCSPRODUCT', 'MAG_ZDOCSVERSION', 'MAG_ZDOCSMANUAL' );
		if ( !in_array( $magicWordId, $handledIDs ) ) {
			return true;
		}
		$title = $parser->getTitle();
		$zdPage = ZDocsUtils::pageFactory( $title );
		if ( $zdPage == null ) {
			return true;
		}
		$className = get_class( $zdPage );
		switch ( $magicWordId ) {
			case 'MAG_ZDOCSPRODUCT':
				if ( $className == 'ZDocsProduct' ) {
					return true;
				}
				list( $product, $version ) = $zdPage->getProductAndVersion();
				$ret = $product->getDisplayName();
				break;
			case 'MAG_ZDOCSVERSION':
				if ( $className == 'ZDocsProduct' || $className == 'ZDocsVersion' ) {
					return true;
				}
				list( $productName, $versionString ) = $zdPage->getProductAndVersionStrings();
				$ret = $versionString;
				break;
			case 'MAG_ZDOCSMANUAL':
				if ( $className == 'ZDocsProduct' || $className == 'ZDocsVersion' || $className == 'ZDocsManual' ) {
					return true;
				}
				$manual = $zdPage->getManual();
				$ret = $manual->getDisplayName();
				break;
			default:
				break;
		}
		return true;
	}

}
