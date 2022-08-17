<?php

namespace MediaWiki\Extension\CocktailSearch;

use Html;
use OutputPage;
use Skin;

class SpecialCocktailSearch extends \SpecialPage
{
    function __construct()
    {
        parent::__construct('CocktailSearch');
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
    		$res = $this->getCocktails( $ings, $output );
			$output->addHTML( json_encode( $res ) );
		} else {
			$output->addHTML(Html::openElement('form'));
			$output->addElement('input', ["type" => "hidden", "name" => "ing1", "id" => "ingredient1H"]);
			$output->addElement('input', ["type" => "hidden", "name" => "ing2", "id" => "ingredient2H"]);
			$output->addElement('input', ["type" => "hidden", "name" => "ing3", "id" => "ingredient3H"]);
			$output->addElement('input', ["type" => "search", "id" => "ingredient1"]);
			$output->addElement('input', ["type" => "search", "id" => "ingredient2"]);
			$output->addElement('input', ["type" => "search", "id" => "ingredient3"]);
			$output->addElement('input', ["type" => "submit"]);
			$output->addHTML(Html::closeElement('form'));
		}
    }

	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
    	$out->addModules( 'ext.CocktailSearch' );
	}

	private function getCocktails( $ings, OutputPage $outpage ) {
		$endPoint = 'http://wdqs:9999/bigdata/namespace/wdq/sparql';

		$sparql = "SELECT DISTINCT ?a ?aLabel WHERE {
  			?a wdt:P3 wd:$ings[0] .
  			?a wdt:P3 wd:$ings[1] .
  			SERVICE wikibase:label { bd:serviceParam wikibase:language \"[AUTO_LANGUAGE],en\". }
		}";

		$params = [
			'query' => $sparql,
			'format' => 'json',
		];

		$url = $endPoint . "?" . http_build_query( $params );
		$ch = curl_init( $url );

		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_USERAGENT,
			'TimelineGenerator/0.1 (https://gitlab.wikimedia.org/ihurbain/timeline-generator; ' .
			'ihurbainpalatin@wikimedia.org)' );
		$output = curl_exec( $ch );
		curl_close( $ch );
		$data = json_decode( $output, true );
		return $data ?? [];

	}
}
