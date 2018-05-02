<?php

abstract class ZDocsPage {

	protected $mTitle = null;

	/**
	 * See if the specified page can set to this type.
	 */
	public static function checkPageEligibility( $parentPageName, $thisPageName ) {		
		// Check if it has a parent page.
		if ( $parentPageName == null ) {
			// @TODO - add i18n for page type.
			$pageType = static::getPageTypeValue();
			return wfMessage( "zdocs-noparentpage", $pageType )->parse();
		}

		// Check if its parent page belongs to the right class.
		$parentPageTitle = Title::newFromText( $parentPageName );
		$pageLevel = array_search( get_called_class(), ZDocsUtils::$pageClassesInOrder );
		$parentClass = ZDocsUtils::$pageClassesInOrder[$pageLevel - 1];
		$parentPageType = $parentClass::getPageTypeValue();
		if ( ZDocsUtils::getPageType( $parentPageTitle ) != $parentPageType ) {
			// @TODO - add i18n for page type.
			return wfMessage( "zdocs-invalidparentpage", $parentPageName, $parentPageType )->parse();
		}
		
		return null;
	}

	public function __construct( $title ) {
		$this->mTitle = $title;
	}

	public function getTitle() {
		return $this->mTitle;
	}

	public function getPageProp( $propName ) {
		return ZDocsUtils::getPagePropForTitle( $this->mTitle, $propName );
	}

	function getChildrenPages() {
		$childrenPages = array();

		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select( 'page_props',
			array(
				'pp_page'
			),
			array(
				'pp_value' => $this->mTitle->getText(),
				'pp_propname' => 'ZDocsParentPage'
			)
		);

		while ( $row = $dbr->fetchRow( $res ) ) {
			$childrenPages[] = Title::newFromID( $row[0] );
		}

		return $childrenPages;
	}

	static function getPageTypeValue() {
	}

	public function inheritsPageContents() {
		// @TODO - change null to false
		return $this->getPageProp( 'ZDocsInherit' );
	}

	public function inheritsParams() {
		// @TODO - change null to false
		return $this->getPageProp( 'ZDocsInherit' );
	}

	/**
	 * Should not be called for Product pages.
	 */
	public function getProductAndVersionStrings() {
		$pageName = $this->mTitle->getText();
		$pageNameParts = explode( '/', $pageName );
		// The product page name may have some slashes in it.
		$pageLevel = array_search( get_class( $this ), ZDocsUtils::$pageClassesInOrder ) + 1;
		$numProductNameParts = count( $pageNameParts ) - $pageLevel + 1;
		$productNameParts = array_slice( $pageNameParts, 0, $numProductNameParts );
		$productName = implode( '/', $productNameParts );

		$versionString = $pageNameParts[$numProductNameParts];

		return array( $productName, $versionString );
	}

	public function getProductAndVersion() {
		list( $productName, $versionString ) = $this->getProductAndVersionStrings();
		$productPage = Title::newFromText( $productName );
		$product = new ZDocsProduct( $productPage );
		$versionPage = Title::newFromText( $productName . '/' . $versionString );
		$version = new ZDocsVersion( $versionPage );
		return array( $product, $version );
	}

	public function getEquivalentPagesForPreviousVersions() {
		list( $productName, $versionString ) = $this->getProductAndVersionStrings();
		$productPage = Title::newFromText( $productName );
		$product = new ZDocsProduct( $productPage );

		$equivalentPages = array();
		$versionsAndTheirPages = $product->getVersionsBefore( $versionString );
		foreach ( $versionsAndTheirPages as $curVersionString => $curVersion ) {
			$curEquivalentPage = $this->getEquivalentPageForVersion( $curVersion );
			if ( $curEquivalentPage != null ) {
				$equivalentPages[] = $curEquivalentPage;
			}
		}

		return $equivalentPages;
	}

	public function getInheritedPage() {
		// Quick escape.
		if ( !$this->inheritsPageContents() ) {
			return null;
		}

		$equivalentPages = $this->getEquivalentPagesForPreviousVersions();
		$className = get_called_class();

		foreach ( $equivalentPages as $equivalentPage ) {
			$zdEquivalentPage = new $className( $equivalentPage );
			if ( !$zdEquivalentPage->inheritsPageContents() ) {
				return $zdEquivalentPage;
			}
		}
		throw new MWException( "There is no version from which to inherit!" );
	}

	/**
	 * Inheritance of parser function params (like "topics list=") works
	 * slightly differently from inheritance of page free text: in both
	 * cases it's triggered when the "inherit" parameter is used, but for
	 * free text the code automatically goes back to the last version that
	 * doesn't include "inherit", while for parameters the code stops with
	 * the last version that has that parameter defined (which could even
	 * be the current version).
	 */
	public function getPossiblyInheritedParam( $pagePropName ) {
		$paramValue = $this->getPageProp( $pagePropName );
		if ( $paramValue != null ) {
			return $paramValue;
		}
		if ( !$this->inheritsParams() ) {
			return null;
		}

		return $this->getInheritedParam( $pagePropName );
	}

	public function getInheritedParam( $pagePropName ) {
		$equivalentPages = $this->getEquivalentPagesForPreviousVersions();

		foreach ( $equivalentPages as $equivalentPage ) {
			$paramValue = ZDocsUtils::getPagePropForTitle( $equivalentPage, $pagePropName );
			if ( $paramValue !== null ) {
				return $paramValue;
			}
			$inherits = ZDocsUtils::getPagePropForTitle( $equivalentPage, 'ZDocsInherit' );
			if ( !$inherits ) {
				return null;
			}
		}
		return null;
	}

	abstract function getHeader();

	function getFooter() {
		return null;
	}

	function getSidebarText() {
		return null;
	}
	
	function getActualName() {
		$pageName = $this->mTitle->getText();
		$lastSlashPos = strrpos( $pageName, '/' );
		return substr( $pageName, $lastSlashPos + 1 );
	}

	function getDisplayName() {
		return $this->getPageProp( 'displaytitle' );
	}

	public function getParentPage() {
		$pageName = $this->mTitle->getText();
		$lastSlashPos = strrpos( $pageName, '/' );
		$parentPageName = substr( $pageName, 0, $lastSlashPos );
		return Title::newFromText( $parentPageName );
	}

	public function getLink() {
		return Linker::link( $this->mTitle, $this->getDisplayName() );
	}

	/**
	 * Used only for Manual and Topic.
	 */
	function getEquivalentsInOtherVersions( $product, $thisVersionString ) {
		$equivalents = array();
		$versionsAndTheirPages = $product->getVersions();
		foreach ( $versionsAndTheirPages as $versionString => $version ) {
			if ( $versionString == $thisVersionString ) {
				continue;
			}
			$equivPage = $this->getEquivalentPageForVersion( $version );
			if ( $equivPage != null ) {
				$equivalents[$versionString] = $equivPage;
			}
		}
		return $equivalents;
	}

	/**
	 * Used only for Manual and Topic.
	 */
	function getEquivalentPageForVersion( $version ) {
		$equivPageName = $this->getEquivalentPageNameForVersion( $version );
		$equivPage = Title::newFromText( $equivPageName );
		if ( $equivPage->exists() && ZDocsUtils::getPageType( $equivPage ) == $this->getPageTypeValue() ) {
			return $equivPage;
		}
		return null;
	}

	function getEquivalentPageNameForVersion( $version ) {
		return null;
	}

	public function userCanView( $user ) {
		list( $product, $version ) = $this->getProductAndVersion();
		$versionStatus = $version->getStatus();

		if ( $versionStatus == ZDocsVersion::RELEASED_STATUS ) {
			// Everyone can view this.
			return true;
		} elseif ( $versionStatus == ZDocsVersion::UNRELEASED_STATUS ) {
			if ( $user->isAllowed( 'zdocs-administer' ) ||
				$user->isAllowed( 'zdocs-edit' ) ||
				$user->isAllowed( 'zdocs-preview' ) ) {
				return true;
			}
			if ( $product->userIsAdmin( $user ) ||
				$product->userIsEditor( $user ) ||
				$product->userIsPreviewer( $user ) ) {
				return true;
			}
			return false;
		} elseif ( $versionStatus == ZDocsVersion::CLOSED_STATUS ) {
			if ( $user->isAllowed( 'zdocs-administer' ) ) {
				return true;
			}
			if ( $product->userIsAdmin( $user ) ) {
				return true;
			}
			return false;
		}

		// If it's some other text, or blank, let everyone view it.
		return true;
	}

	public function userCanEdit( $user ) {
		list( $product, $version ) = $this->getProductAndVersion();
		$versionStatus = $version->getStatus();

		if ( $versionStatus == ZDocsVersion::RELEASED_STATUS ) {
			// Everyone can edit this, as far as ZDocs is concerned.
			return true;
		} elseif ( $versionStatus == ZDocsVersion::UNRELEASED_STATUS ) {
			if ( $user->isAllowed( 'zdocs-administer' ) ||
				$user->isAllowed( 'zdocs-edit' ) ) {
				return true;
			}
			if ( $product->userIsAdmin( $user ) ||
				$product->userIsEditor( $user ) ) {
				return true;
			}
			return false;
		} elseif ( $versionStatus == ZDocsVersion::CLOSED_STATUS ) {
			if ( $user->isAllowed( 'zdocs-administer' ) ) {
				return true;
			}
			if ( $product->userIsAdmin( $user ) ) {
				return true;
			}
			return false;
		}

		// If the status is some other value, or blank, let everyone view it.
		return true;
	}

}
