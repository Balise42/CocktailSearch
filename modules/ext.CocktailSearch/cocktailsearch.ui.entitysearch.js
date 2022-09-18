( function () {
	'use strict';
	$( function () {
		for (const field of ['#ingredient1', '#ingredient2', '#ingredient3']) {
			console.log( field );
			let input = $( field );
			let hidden = $( field + 'H');
			console.log( input );

			input
				.entityselector( {
					url: mw.config.get( 'wgServer' ) + mw.config.get( 'wgScriptPath' ) + '/api.php',
					type: 'item',
					selectOnAutocomplete: true,
					selected: function( ev, data ) {
						hidden.val(data);
					}
				} );
		}
	} );
}() );
