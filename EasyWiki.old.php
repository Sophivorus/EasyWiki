<?php

class EasyWiki {

	private $user;
	private $pass;

    public function __construct( string $user = 'EasyWiki', string $pass = '' ) {
        $this->user = $user;
    }

    /**
     * Create a page
	 * @param string $title Title of the page to create
	 * @param string $text Wikitext of the page to create
	 */
    public function create( $title, $text, $options = [] ) {
        $User = User::newSystemUser( $this->user );
        $timestamp = wfTimestampNow();
        $Title = Title::newFromText( $title );
        $Config = HashConfig::newInstance();
        $Revision = new WikiRevision( $Config );
        $Revision->setTitle( $Title );
        $Revision->setModel( 'wikitext' );
        $Revision->setText( $text );
        $Revision->setUserObj( $User );
        $Revision->setTimestamp( $timestamp );
        $Revision->importOldRevision();
    }

    /**
     * Edit a page
	 * @param string $title Title of the page to edit
	 * @param string $text New wikitext of the page
	 */
    public function edit( $title, $text, $options = [] ) {
        $summary = $options['summary'] ?? '';
        $Title = Title::newFromText( $title );
		$Content = ContentHandler::makeContent( $text, $Title );
        $Page = WikiPage::factory( $Title );
		$User = User::newSystemUser( $this->user );
		$Comment = CommentStoreComment::newUnsavedComment( $summary );
		$Updater = $Page->newPageUpdater( $User );
		$Updater->setContent( 'main', $Content );
		$Updater->saveRevision( $Comment );
    }

    /**
     * Move (rename) a page
	 * @param string $title Old page name
	 * @param string $title New page name
	 */
    public function move( $title, $target, $options = [] ) {
        $summary = $options['summary'] ?? '';
        $redirect = $options['redirect'] ?? true;
		$User = User::newSystemUser( $this->user );
		$Title = Title::newFromName( $title );
		$Target = Title::newFromName( $target );
		$Move = MediaWikiServices::getInstance()->getMovePageFactory()->newMovePage( $title, $target );
		$Move->move( $User, $summary, $redirect );
    }

    /**
     * Delete a page
	 * @param string $title Name of the page to delete
	 */
    public function delete( $title, $options = [] ) {
        $summary = $options['summary'] ?? '';
        $Title = $this->getTitle( $title );
		$Page = WikiPage::factory( $Title );
		$Page->doDeleteArticle( $comment );
    }

    /**
     * Parse wikitext
	 * @param string $text Wikitext to parse
	 * @param string $title Title to resolve things like {{PAGENAME}}
	 */
    public function parse( $text, $title = 'Foo', $options = [] ) {
        $Title = Title::newFromText( $title );
		$Parser = new Parser;
		$Options = new ParserOptions;
		$Output = $Parser->parse( $text, $Title, $Options );
		$html = $Output->getText();
		return $html;
    }

    /**
     * Get the wikitext of a page
	 * @param string $title Title of the page
	 */
    public function getWikitext( $title, $options = [] ) {
        $Title = Title::newFromText( $title );
        $Page = WikiPage::factory( $Title );
        $Revision = $Page->getRevision();
        $Content = $Revision->getContent( Revision::RAW );
        $text = ContentHandler::getContentText( $Content );
        return $text;
    }
}