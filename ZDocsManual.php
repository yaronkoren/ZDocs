<?php

class ZDocsManual extends ZDocsPage {
	
	private $mTOC = null;
	private $mOrderedTopics = null;
	
	static function getPageTypeValue() {
		return 'Manual';
	}

	public function hasPagination() {
		// @TODO - change null to false
		return $this->getPossiblyInheritedParam( 'ZDocsPagination' );
	}

	function getHeader() {
		list( $product, $version ) = $this->getProductAndVersion();
		$manualDescText = wfMessage( 'zdocs-manual-desc', $version->getLink(), $product->getLink() )->text();
		$text = Html::rawElement( 'div', array( 'class' => 'ZDocsManualDesc' ), $manualDescText );

		$equivsInOtherVersions = $this->getEquivalentsInOtherVersions( $product, $version->getActualName() );
		if ( count( $equivsInOtherVersions ) > 0 ) {
			$otherVersionsText = wfMessage( 'zdocs-manual-otherversions' )->text() . "\n";
			$otherVersionsText .= "<ul>\n";
			foreach ( $equivsInOtherVersions as $versionName => $manualPage ) {
				$otherVersionsText .= "<li>" . Linker::link( $manualPage, $versionName ) . "</li>\n";
			}
			$otherVersionsText .= "</ul>\n";
			$text .= Html::rawElement( 'div', array( 'class' => 'ZDocsOtherManualVersions' ), $otherVersionsText );
		}

		$contentsText = wfMessage( 'zdocs-manual-contents' )->text() . "\n" . $this->getTableOfContents( true );
		$text .= Html::rawElement( 'div', array( 'class' => 'ZDocsManualTOC' ), $contentsText );

		return $text;
	}

	function getAllTopics() {
		$topicPages = $this->getChildrenPages();
		$topics = array();
		foreach ( $topicPages as $topicPage ) {
			$topics[] = new ZDocsTopic( $topicPage );
		}

		return $topics;
	}

	private function generateTableOfContents( $showErrors ) {
		global $wgParser;

		$tocOrPageName = $this->getPossiblyInheritedParam( 'ZDocsTopicsList' );
		// Decide whether this is a table of contents or a page name
		// based on whether or not the string starts with a '*' -
		// hopefully that's a good enough check.
		if ( substr( $tocOrPageName, 0, 1 ) == '*' ) {
			$toc = $tocOrPageName;
		} else {
			$title = Title::newFromText( $tocOrPageName );
			$wikiPage = new WikiPage( $title );
			$content = $wikiPage->getContent();
			if ( $content !== null ) {
				$toc = $content->getNativeData();
			} else {
				$toc = null;
			}
		}
		$topics = $this->getAllTopics();
		$this->mOrderedTopics = array();
		foreach ( $topics as $i => $topic ) {
			$topicActualName = $topic->getActualName();
			$tocBeforeReplace = $toc;
			$toc = preg_replace( "/(\*+)\s*$topicActualName\s*$/m",
				'$1' . $topic->getTOCLink(), $toc );
			if ( $toc != $tocBeforeReplace ) {
				// Replacement was succesful.
				$this->mOrderedTopics[] = $topicActualName;
				unset( $topics[$i] );
			}
		}

		// Handle standalone topics - prepended with a "!".
		$toc = preg_replace_callback(
			"/(\*+)\s*!\s*(.*)\s*$/m",
			function( $matches ) {
				$standaloneTopicTitle = Title::newFromText( $matches[2] );
				$standaloneTopic = ZDocsTopic::newStandalone( $standaloneTopicTitle, $this );
				if ( $standaloneTopic == null ) {
					return $matches[1] . $matches[2];
				}
				return $matches[1] . $standaloneTopic->getTocLink();
			},
			$toc
		);

		// doBlockLevels() takes care of just parsing '*' into
		// bulleted lists, which is all we need.
		$this->mTOC = $wgParser->doBlockLevels( $toc, true );

		if ( $showErrors && count( $topics ) > 0 ) {
			// Display error
			global $wgOut;
			$topicLinks = array();
			foreach ( $topics as $topic ) {
				$topicLinks[] = $topic->getTOCLink();
			}
			$errorMsg = wfMessage( 'zdocs-manual-extratopics', implode( ', ', $topicLinks ) )->text();
			$wgOut->addHTML( Html::rawElement( 'div', array( 'class' => 'warningbox' ), $errorMsg ) );
		}
	}
	
	function getTableOfContents( $showErrors ) {
		if ( $this->mTOC == null ) {
			$this->generateTableOfContents( $showErrors );
		}
		return $this->mTOC;
	}
	
	function getPreviousAndNextTopics( $topic, $showErrors ) {
		if ( $this->mOrderedTopics == null ) {
			$this->generateTableOfContents( $showErrors );
		}
		$topicActualName = $topic->getActualName();
		$prevTopic = null;
		$nextTopic = null;

		foreach( $this->mOrderedTopics as $i => $curTopicActualName ) {
			if ( $topicActualName == $curTopicActualName ) {
				// It's wasteful to have to create the ZDocsTopic objects
				// again, but there are only two of them, so it seems
				// easier to do it this way than to store lots of unneeded
				// objects.
				$manualPageName = $this->mTitle->getText();
				if ( $i == 0 ) {
					$prevTopic = null;
				} else {
					$prevTopicActualName = $this->mOrderedTopics[$i - 1];
					$prevTopicPageName = $manualPageName . '/' . $prevTopicActualName;
					$prevTopicPage = Title::newFromText( $prevTopicPageName );
					$prevTopic = new ZDocsTopic( $prevTopicPage );
				}
				if ( $i == count( $this->mOrderedTopics ) - 1 ) {
					$nextTopic = null;
				} else {
					$nextTopicActualName = $this->mOrderedTopics[$i + 1];
					$nextTopicPageName = $manualPageName . '/' . $nextTopicActualName;
					$nextTopicPage = Title::newFromText( $nextTopicPageName );
					$nextTopic = new ZDocsTopic( $nextTopicPage );
				}
			}
		}
		return array( $prevTopic, $nextTopic );
	}

	function getEquivalentPageNameForVersion( $version ) {
		$versionPageName = $version->getTitle()->getText();
		return $versionPageName . '/' . $this->getActualName();
	}

}
