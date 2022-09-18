<?php

namespace MediaWiki\Extension\CocktailSearch;

use Config;
use Html;
use MediaWiki\MediaWikiServices;
use OutputPage;
use Skin;

class SpecialCocktailSearch extends \SpecialPage
{
	/** @var Config */
	var Config $config;

    function __construct()
    {
        parent::__construct('CocktailSearch');
        $this->config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'CocktailSearch' );
    }

    function execute( $subPage ) {
		$output = $this->getOutput();
		$this->setHeaders();

    	if ( !empty($_GET['ing1'] ) || !empty( $_GET['ing2'] || !empty( $_GET['ing3'] ) ) ) {
    		$ings = [];
    		for ( $i = 1; $i <= 3; $i++ ) {
				if (!empty($_GET['ing' . $i])) {
					$ings[] = $_GET['ing' . $i];
				}
			}
    		$res = $this->getCocktails( $ings );
    		$output->addHTML( Html::element( "a", [ "href" => './Special:CocktailSearch' ], "back to search") );
    		$output->addHTML( Html::element( "br") );
			$output->addHTML( Html::element( "span", [], implode(", ",  array_filter([$_GET["ing1label"] ?? '', $_GET["ing2label"] ?? '', $_GET["ing3label"] ?? '']) )));
			$this->displayResults( $res, $output );
		} else {
			$output->addHTML(Html::openElement('form'));
			$output->addElement('input', ["type" => "hidden", "name" => "ing1", "id" => "ingredient1H"]);
			$output->addElement('input', ["type" => "hidden", "name" => "ing2", "id" => "ingredient2H"]);
			$output->addElement('input', ["type" => "hidden", "name" => "ing3", "id" => "ingredient3H"]);
			$output->addElement('input', ["type" => "search", "name" => "ing1label", "id" => "ingredient1"]);
			$output->addElement('input', ["type" => "search", "name" => "ing2label", "id" => "ingredient2"]);
			$output->addElement('input', ["type" => "search", "name" => "ing3label", "id" => "ingredient3"]);
			// yes, this is ugly, but I actually don't want any cache there. should probably set up "no cache", but oh well
			$output->addElement( 'input', ["type" => 'hidden', "name" => "action", "value" => "purge"]);
			$output->addElement('input', ["type" => "submit"]);
			$output->addHTML(Html::closeElement('form'));
		}
    }

	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
    	$out->addModules( 'ext.CocktailSearch' );
	}

	private function displayResults( array $res, OutputPage $outputPage ) {
    	$table = Html::openElement( 'table', [ 'class' => 'mw-datatable' ] );
    	$table .= Html::openElement( 'tr' );
    	$table .= Html::element( 'th', [], 'Cocktail name' );
		$table .= Html::element( 'th', [], 'Ingredients' );
		$table .= Html::element( 'th', [], 'Source' );
		$table .= Html::element( 'th', [], 'Page' );
		$table .= Html::closeElement( 'td');

		$data = $res['results']['bindings'] ?? [];
		foreach ( $data as $row ) {
			$table .= Html::openElement( 'tr' );
			$table .= Html::openElement( 'td' );
			$table .= Html::element('a', [ "href" => $row['cocktail']['value'] ], $row['cocktailLabel']['value'] );
			$table .= Html::closeElement( 'td' );
			$table .= Html::element( 'td', [], $row['ingList']['value'] );
			$table .= Html::element( 'td', [], $row['bookLabel']['value'] );
			$table .= Html::element( 'td', [], $row['page']['value'] );
			$table .= Html::closeElement( 'tr' );
		}

		$table .= Html::closeElement( 'table' );
		$outputPage->addHTML( $table );

	}

	private function getCocktails( array $ings, bool $exact = false) {
		$endPoint = 'http://wdqs:9999/bigdata/namespace/wdq/sparql';

		$propInstanceOf = $this->config->get( 'CocktailSearchInstanceOf' );
		$propSubclass = $this->config->get( 'CocktailSearchSubclassOf' );
		$propHasIng = $this->config->get( 'CocktailSearchHasIngredient' );
		$propSubFor = $this->config->get( 'CocktailSearchSubstituteFor' );
		$propBook = $this->config->get( 'CocktailSearchBook' );
		$propPage = $this->config->get( 'CocktailSearchPage' );
		$propSuchAs = $this->config->get( 'CocktailSearchSuchAs' );
		$itemCocktailRecipe = $this->config->get( 'CocktailSearchCocktailRecipe' );


		$ingConstraints = '';
		foreach ( $ings as $ing ) {
			if ( $exact ) {
				$ingConstraints .= "?cocktail wdt:$propHasIng wd:$ing . ";
			} else {
				$ingConstraints .=   "{ select ?sub$ing where {
	{ { ?sub$ing (wdt:$propSubFor|wdt:$propInstanceOf|wdt:$propSubclass)+ wd:$ing }
		union { wd:$ing (wdt:$propInstanceOf|wdt:$propSubclass)+ ?sub$ing } }
	} }
	{ {?cocktail wdt:$propHasIng ?sub$ing }
		union { ?cocktail p:$propHasIng [ pq:$propSubFor|pq:$propSuchAs ?sub$ing ] }
		union { ?cocktail wdt:$propHasIng wd:$ing }}";
			}
		}

		$sparql = "SELECT DISTINCT ?cocktail ?cocktailLabel (GROUP_CONCAT(DISTINCT ?ingLabel; SEPARATOR=\", \") AS ?ingList) ?bookLabel ?page WHERE {
			$ingConstraints
            ?cocktail wdt:$propHasIng ?ing .
            ?cocktail wdt:$propInstanceOf wd:$itemCocktailRecipe .
            ?ing rdfs:label ?ingLabel .
			?cocktail p:$propBook ?ref  .
            ?ref pq:$propPage ?page .
            ?ref ps:$propBook ?book
				SERVICE wikibase:label { bd:serviceParam wikibase:language \"[AUTO_LANGUAGE],en\". }
		}
		group by ?cocktail ?cocktailLabel ?bookLabel ?page";

		$params = [
			'query' => $sparql,
			'format' => 'json',
		];

		$url = $endPoint . "?" . http_build_query( $params );
		$ch = curl_init( $url );

		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_USERAGENT,
			'CocktailSearch/0.1 (no-url-yet; isabelle@palatin.fr)' );
		$output = curl_exec( $ch );
		curl_close( $ch );
		$data = json_decode( $output, true );
		return $data ?? [];

	}
}
