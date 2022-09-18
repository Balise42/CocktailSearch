# CocktailSearch
A Mediawiki/Wikibase extension for searching a cocktail recipe index.

## Important / Disclaimer
This code is not considered secure; I wouldn't run it on a publicly accessible machine. It shouldn't break
databases by itself (although no guarantees is made there either), but I'm not excessively confident in my ability to
avoid blatant security holes.

## General principles

This extension requires a Wikibase installation, as documented for example in
https://www.mediawiki.org/wiki/Wikibase/Docker. It also requires as SPARQL endpoint to be configured and functional
for the search to work.

CocktailSearch relies on the following facts for the underlying data:
* Every recipe item is an "instance of" the "cocktail recipe" item.
* Every recipe item is linked by "has ingredient" property to ingredient items. 
* Every recipe has at least one "book" source, associated to a "page" qualifier.
* Ingredients can be "instance of", "subclass of", "substitute for" or "such as' other ingredients: these properties
  are used for the non-exact search.
  
### Example

My entries for a Daiquiri cocktail recipe look roughly as follow, with properties in italic and items in bold:
* rum
  * *subclass of* **spirit** 
  	* *subclass of* **ingredient**
* **Plantation Pineapple**, **Caña Brava**, **La Favorite Cœur de Canne**, **Appleton Estate Reserve Blend**
  * **instances of rum**
* **simple syrup**
  * *instance of* **syrup**
  	* *subclass of* **ingredient**
* **lime juice**
  * *instance of* **citrus juice**
	* *subclass* of **ingredient**
  * *substitute for* **lemon juice**
* **Daiquiri**
  * *instance of* **cocktail recipe**
  * *has ingredients*
	* *rum*, *such as*
		* **Plantation Pineapple**, **Caña Brava**, **La Favorite Cœur de Canne**, **Appleton Estate Reserve Blend**
	* **lime juice**
	* **simple syrup**
  * *book*
	* **Cocktail Codex**, *pages* 103, 113, 119

CocktailSearch will return Daiquiri on the following ingredients: rum, lime juice, simple syrup, Plantation Pineapple,
syrup, citrus juice, lemon juice; as well as for all ingredients that as marked as substituables or subclasses of
the ingredients indicated here.

## Configuration

The following properties and items should exist in Wikibase and their IDs must be configured in the `config` section
of `extension.json`:
* `instance of` property
* `has ingredient` property
* `subclass of` property
* `substitute for` property
* `book` property
* `page` property (used as qualifier)
* `such as` property (used as qualifier)
* `cocktail recipe` item

Additionally, the SPARQL endpoint URL must also be added to `extension.json` in the `CocktailSearchSparqlEndpoint`
setting of the `config` section.

## TODO

* The PHP is horrifyingly ugly and should be refactored.
* The UI is less than minimalistic and should be updated; more options (exact/fuzzy search, exclusions) should be added.
