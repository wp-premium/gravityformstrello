( function( $ ) {
	
	$( document ).ready( function() {
	
		/* Hide save plugin settings button. */
		$( '#tab_gravityformstrello #gform-settings-save' ).hide();
	
		/* Authorize Trello */
		$( '#gform_trello_auth_button' ).on( 'click', function( e ){
		
			e.preventDefault();
			
			if ( Trello !== undefined ) {
				
				Trello.authorize( {
					
					'expiration':  'never',
					'interactive': true,
					'name':        'Gravity Forms Trello Add-On',
					'persist':     false,
					'scope':       {
						'read':    'allowRead',
						'write':   'allowWrite',
						'account': 'allowAccount'
					},
					'type':        'popup',
					'error': function() {
						changeAuthToken( '' );
					},
					'success':     function() {
						changeAuthToken( Trello.token() );
					}

				} );
				
			}
		
		} );

		/* De-Authorize Trello. */
		$( '#gform_trello_deauth_button' ).on( 'click', function( e ){
		
			e.preventDefault();
			changeAuthToken( '' );
		
		} );

		function changeAuthToken( token ) {
			$( 'input#authToken' ).val( token );
			$( '#gform-settings #gform-settings-save' ).trigger( 'click' );
		}
		
	} );
	
} )( jQuery );