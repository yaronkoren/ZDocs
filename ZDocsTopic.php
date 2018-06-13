<?php

class ZDocsTopic extends ZDocsPage {
	private $mManual = null;
	private $mIsStandalone = false;

	public function __construct( $title ) {
		global $wgRequest;

		$this->mTitle = $title;

		// Determine whether this is an invalid and/or standalone
		// topic, and also get the corresponding manual.
		// This is a little confusing: "invalid" is a permanent aspect
		// of a topic, indicating that its URL does not conform to the
		// "product/version/manual/topic" syntax, while "standalone" is
		// a temporary aspect, indicating that it is currently being
		// viewed with product, version and manual specified in the
		// query string.
		// "invalid" and "standalone" are independent of onne another;
		// a topic can be valid but still used as a standalone topic
		// within another manual.
		$manualFromQueryString = null;
		if ( $wgRequest->getCheck( 'product' ) &&
			$wgRequest->getCheck( 'version' ) &&
			$wgRequest->getCheck( 'manual' ) ) {
			$manualPageName = $wgRequest->getVal( 'product' ) . '/' .
				$wgRequest->getVal( 'version' ) . '/' .
				$wgRequest->getVal( 'manual' );
			$manualTitle = Title::newFromText( $manualPageName );
			$manualFromQueryString = new ZDocsManual( $manualTitle );
			if ( $manualFromQueryString->getPageProp( 'ZDocsPageType' ) == 'Manual' ) {
				$this->mIsStandalone = true;
			}
		}

		$parentPage = $this->getParentPage();
		if ( $parentPage == null ) {
			$this->mIsInvalid = true;
			return null;
		}
		$manualFromPageName = new ZDocsManual( $parentPage );
		if ( $manualFromPageName->getPageProp( 'ZDocsPageType' ) != 'Manual' ) {
			$this->mIsInvalid = true;
		}

		if ( $manualFromQueryString != null ) {
			$this->mManual = $manualFromQueryString;
		} elseif ( $manualFromPageName != null ) {
			$this->mManual = $manualFromPageName;
		}
	}

	static function getPageTypeValue() {
		return 'Topic';
	}

	static function newStandalone( $title, $manual ) {
		$topic = new ZDocsTopic( $title );
		// Make sure this page calls #zdocs_topic.
		$pageType = $topic->getPageProp( 'ZDocsPageType' );
		if ( $pageType != 'Topic' ) {
			return null;
		}

		$topic->mManual = $manual;
		$topic->mIsStandalone = true;
		return $topic;
	}

	function getHeader() {
		if ( $this->mIsInvalid ) {
			return;
		}

		$manual = $this->getManual();
		list( $product, $version ) = $manual->getProductAndVersion();

		$topicDescText = wfMessage( 'zdocs-topic-desc', $manual->getLink(), $version->getLink(), $product->getLink() )->text();
		$text = Html::rawElement( 'div', array( 'class' => 'ZDocsTopicDesc' ), $topicDescText );

		$equivsInOtherVersions = $this->getEquivalentsInOtherVersions( $product, $version->getActualName() );
		if ( count( $equivsInOtherVersions ) > 0 ) {
			$otherVersionsText = wfMessage( 'zdocs-topic-otherversions' )->text() . "\n";
			$otherVersionsText .= "<ul>\n";
			foreach ( $equivsInOtherVersions as $versionName => $topicPage ) {
				$otherVersionsText .= "<li>" . Linker::link( $topicPage, $versionName ) . "</li>\n";
			}
			$otherVersionsText .= "</ul>\n";
			$text .= Html::rawElement( 'div', array( 'class' => 'ZDocsOtherManualVersions' ), $otherVersionsText );
		}

		if ( $manual->hasPagination() ) {
			list( $prevTopic, $nextTopic ) = $manual->getPreviousAndNextTopics( $this, false );
			if ( $prevTopic ) {
				$text .= Html::rawElement( 'div', array( 'class' => 'ZDocsPrevTopicLink' ), '&larr;<br />' . $prevTopic->getLink() );
			}
			if ( $nextTopic ) {
				$text .= Html::rawElement( 'div', array( 'class' => 'ZDocsNextTopicLink' ), '&rarr;<br />' . $nextTopic->getLink() );
			}
		}

		return $text;
	}

	function getTOCLink() {
		$displayName = $this->getPageProp( 'ZDocsTOCName' );
		// Is this necessary?
		if ( $displayName == null ) {
			$displayName = $this->getDisplayName();
		}
		$query = array();
		if ( $this->mIsStandalone ) {
			$manual = $this->getManual();
			list( $product, $version ) = $manual->getProductAndVersion();
			$query['product'] = $product->getActualName();
			$query['version'] = $version->getActualName();
			$query['manual'] = $manual->getActualName();
		}
		return Linker::link( $this->mTitle, $displayName, array(), $query );
	}

	function getFooter() {
		if ( $this->mIsInvalid ) {
			return null;
		}

		$manual = $this->getManual();

		$header = '<p>' . $manual->getDisplayName() . '</p>';
		$toc = $manual->getTableOfContents( false );
		return Html::rawElement( 'div', array( 'class' => 'ZDocsTopicTOC' ), $header . $toc );
	}

	function getSidebarText() {
		if ( $this->mIsInvalid ) {
			return null;
		}

		$manual = $this->getManual();
		$toc = $manual->getTableOfContents( false );
		return array( $manual->getDisplayName(), $toc );
	}

	function getChildrenPages() {
		return array();
	}

	function getManual() {
		return $this->mManual;
	}

	function getEquivalentPageNameForVersion( $version ) {
		$versionPageName = $version->getTitle()->getText();
		return $versionPageName . '/' . $this->getManual()->getActualName() . '/' . $this->getActualName();
	}

	function isStandalone() {
		return $this->mIsStandalone;
	}
}
