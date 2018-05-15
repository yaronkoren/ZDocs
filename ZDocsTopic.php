<?php

class ZDocsTopic extends ZDocsPage {
	private $mManual;

	static function getPageTypeValue() {
		return 'Topic';
	}

	function getHeader() {
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
		return Linker::link( $this->mTitle, $displayName );
	}

	function getFooter() {
		$manual = $this->getManual();
		$header = '<p>' . $manual->getDisplayName() . '</p>';
		$toc = $manual->getTableOfContents( false );
		return Html::rawElement( 'div', array( 'class' => 'ZDocsTopicTOC' ), $header . $toc );
	}

	function getSidebarText() {
		$manual = $this->getManual();
		$toc = $manual->getTableOfContents( false );
		return array( $manual->getDisplayName(), $toc );
	}

	function getChildrenPages() {
		return array();
	}

	function getManual() {
		if ( $this->mManual == null ) {
			$this->mManual = new ZDocsManual( $this->getParentPage() );
		}
		return $this->mManual;
	}

	function getEquivalentPageNameForVersion( $version ) {
		$versionPageName = $version->getTitle()->getText();
		return $versionPageName . '/' . $this->getManual()->getActualName() . '/' . $this->getActualName();
	}

}
