<?php

namespace MediaWiki\Extension\CocktailSearch;
class SpecialCocktailSearch extends \SpecialPage
{
    function __construct()
    {
        parent::__construct('CocktailSearch');
    }

    function execute( $subPage ) {
		$output = $this->getOutput();
		$this->setHeaders();

		$wikitext = 'Hello world!';
		$output->addWikiTextAsInterface( $wikitext );
    }
}
