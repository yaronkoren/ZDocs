<?php

class ZDocsProduct extends ZDocsPage {

	static function getPageTypeValue() {
		return 'Product';
	}

	static function checkPageEligibility( $parentPageName, $thisPageName ) {
		global $wgZDocsProductPages;

		if ( !in_array( $thisPageName, $wgZDocsProductPages ) ) {
			return 'Error: This page must first be registered in LocalSettings.php as a product page.';
		}

		return null;
	}

	/**
	 * Not currently used, but could get called in the future.
	 */
	function getActualName() {
		return $this->mTitle->getText();
	}

	function inheritsPageContents() {
		return false;
	}

	function getInheritedPage() {
		return null;
	}

	function inheritsParams() {
		return false;
	}

	public function userIsAdmin( $user ) {
		return ZDocsUtils::titleHasPagePropValue( $this->mTitle, 'ZDocsProductAdmin', $user->getName() );
	}

	public function userIsEditor( $user ) {
		return ZDocsUtils::titleHasPagePropValue( $this->mTitle, 'ZDocsProductEditor', $user->getName() );
	}

	public function userIsPreviewer( $user ) {
		return ZDocsUtils::titleHasPagePropValue( $this->mTitle, 'ZDocsProductPreviewer', $user->getName() );
	}

	public function userCanView( $user ) {
		// Everyone can view a product page.
		return true;
	}

	public function userCanEdit( $user ) {
		// In order to prevent users from adding themselves to
		// any of the special permissions groups, we have to disallow
		// editing of this page to non-ZDocs admins.
		if ( $user->isAllowed( 'zdocs-administer' ) ) {
			return true;
		}
		if ( $this->userIsAdmin( $user ) ) {
			return true;
		}

		return false;
	}

	function getHeader() {
		$versionListText = wfMessage( 'zdocs-product-versionlist' )->text() . "\n";
		$versionListText .= "<ul>\n";

		$versionsAndTheirPages = $this->getVersions();
		foreach( $versionsAndTheirPages as $versionString => $version ) {
			$versionListText .= '<li>' . Linker::link( $version->getTitle(), $versionString ) . "</li>\n";
		}
		$versionListText .= "</ul>\n";
		$versionListText = Html::rawElement( 'div', array( 'class' => 'ZDocsVersionList' ), $versionListText );

		return $versionListText;
	}

	/**
	 *
	 */
	function getVersions() {
		$versionPages = $this->getChildrenPages();
		$versionsAndTheirPages = array();
		// Store these values, so function doesn't have to be called unnecessarily.
		$userCanViewVersionStatus = array();
		foreach ( $versionPages as $versionPage ) {
			$zdVersionPage = new ZDocsVersion( $versionPage );
			$status = $zdVersionPage->getStatus();
			if ( !array_key_exists( $status, $userCanViewVersionStatus ) ) {
				$userCanViewVersionStatus[$status] = $this->userCanView( $status );
			}
			if ( !$userCanViewVersionStatus[$status] ) {
				continue;
			}
			$versionString = $zdVersionPage->getDisplayName();
			$versionsAndTheirPages[$versionString] = $zdVersionPage;
		}

		// Sort based on the version numbers contained in the keys.
		uksort( $versionsAndTheirPages, 'version_compare' );

		return $versionsAndTheirPages;
	}

	/**
	 * Returns an output similar to getVersions(), but only for versions
	 * before the specified one, and starting with the most recent one.
	 */
	public function getVersionsBefore( $curVersionString ) {
		$versionsAndTheirPagesBeforeThisOne = array();
		$versionsAndTheirPages = $this->getVersions();
		$versionStrings = array_keys( $versionsAndTheirPages );
		// Go in reverse order.
		$reachedThisVersion = false;
		for ( $i = count( $versionStrings ) - 1; $i >= 0; $i-- ) {
			$versionString = $versionStrings[$i];
			// Skip all the versions ahead of this one.
			if ( !$reachedThisVersion ) {
				if ( $versionString == $curVersionString ) {
					$reachedThisVersion = true;
				}
				continue;
			}
			$versionsAndTheirPagesBeforeThisOne[$versionString] =
				$versionsAndTheirPages[$versionString];
		}
		return $versionsAndTheirPagesBeforeThisOne;
	}

}
