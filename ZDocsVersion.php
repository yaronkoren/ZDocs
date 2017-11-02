<?php

class ZDocsVersion extends ZDocsPage {

	const RELEASED_STATUS = 'Released';
	const UNRELEASED_STATUS = 'Unreleased';
	const CLOSED_STATUS = 'Closed';
	
	static function getPageTypeValue() {
		return 'Version';
	}
	
	function getDisplayName() {
		// No special display name for versions.
		return $this->getActualName();
	}
	
	function getStatus() {
		return $this->getPageProp( 'ZDocsStatus' );
	}

	/**
	 * For version pages, parameters can be inherited, but
	 * page contents cannot.
	 */
	function inheritsPageContents() {
		return false;
	}

	function getInheritedPage() {
		return null;
	}

	function getHeader() {
		$product = new ZDocsProduct( $this->getParentPage() );
		$versionDescText = wfMessage( 'zdocs-version-desc', $this->getDisplayName(), $product->getLink() )->text();
		$text = Html::rawElement( 'div', array( 'class' => 'ZDocsVersionDesc' ), $versionDescText );

		$manualsListText = wfMessage( 'zdocs-version-manuallist' ) . "\n";
		$manualsListText .= "<ul>\n";

		$manualsAndTheirRealNames = $this->getAllManuals();
		$manualsListStr = $this->getPossiblyInheritedParam( 'ZDocsManualsList' );
		$manualsList = explode( ',', $manualsListStr );
		foreach ( $manualsList as $manualName ) {
			if ( array_key_exists( $manualName, $manualsAndTheirRealNames ) ) {
				$manual = $manualsAndTheirRealNames[$manualName];
				$manualsListText .= "<li>" . $manual->getLink() . "</li>\n";
				unset( $manualsAndTheirRealNames[$manualName] );
			} else {
				// Display anyway, so people know something's wrong.
				$manualsListText .= "<li>$manualName</li>\n";
			}
		}
		$manualsListText .= "</ul>\n";
		
		if ( count( $manualsAndTheirRealNames ) > 0 ) {
			// Display error
			$errorMsg = wfMessage( 'zdocs-version-extramanuals', implode( ', ', array_keys( $manualsAndTheirRealNames ) ) );
			$text .= Html::rawElement( 'div', array( 'class' => 'warningbox' ), $errorMsg );
		}
		
		$text .= Html::rawElement( 'div', array( 'class' => 'ZDocsManualList' ), $manualsListText );

		return $text;
	}

	function getEquivalentPageForVersion( $version ) {
		return $version->getTitle();
	}

	function getAllManuals() {
		$manualPages = $this->getChildrenPages();
		$manualsAndTheirRealNames = array();
		foreach ( $manualPages as $manualPage ) {
			$zdManualPage = new ZDocsManual( $manualPage );
			$actualName = $zdManualPage->getActualName();
			$manualsAndTheirRealNames[$actualName] = $zdManualPage;
		}

		return $manualsAndTheirRealNames;
	}

	public function getProductAndVersion() {
		$product = new ZDocsProduct( $this->getParentPage() );
		return array( $product, $this );
	}

}
