/* global WP_WeixinScanHeartBeat */
jQuery( function( $ ) {
	window.scanListenerStartEvent = new CustomEvent( 'scanlistenerstart' );
	window.scanListenerStopEvent  = new CustomEvent( 'scanlistenerstop' );
	window.scanScannerBeat        = false;

	window.addEventListener( 'scanlistenerstart', function() {
		window.scanScannerBeat = true;
		_startScannerListen();
	} );

	window.addEventListener( 'scanlistenerstop', function() {
		window.scanScannerBeat = false;
		_stopScannerListen();
	} );

	function _startScannerListen() {

		function scannerListen() {

			if ( window.scanScannerBeat ) {

				window.scanListener = setTimeout( function() {
						var hash      = $( '#wp_weixin_hash' ).val(),
							activated = hash.length > 0,
							data      = {
								'action': WP_WeixinScanHeartBeat.action,
								'hash': hash
							};

						if ( activated ) {
							$.post( WP_WeixinScanHeartBeat.ajax_url, data, function( response ) {

								if ( ! response.success ) {
									var params = { 'error':response.data };

									params[ WP_WeixinScanHeartBeat.dataIndex ] = false;

									window.scanFailureTrigger( params );
								} else if ( response.data && ! response.data[ WP_WeixinScanHeartBeat.dataIndex ] ) {
									window.scanFailureTrigger( response.data );
								} else if ( ! response.data ) {
									window.beatSuccessTrigger( response.data );
									scannerListen();
								} else {
									window.scanSuccessTrigger( response.data );
								}
							} ).fail( function( jqXHR, textStatus ){
								window.beatFailureTrigger( textStatus );
								scannerListen();
							} );
						}
				}, WP_WeixinScanHeartBeat.heartbeatFreq );
			}
		}
		scannerListen();
	}

	function _stopScannerListen() {
		clearTimeout( window.scanListener );
	}

	window.scanListenerStart = function() {
		window.dispatchEvent( window.scanListenerStartEvent );
	};

	window.scanListenerStop = function() {
		window.dispatchEvent( window.scanListenerStopEvent );
	};

	window.scanSuccessTrigger = function( data ) {
		window.scanSuccess = new CustomEvent( 'scansuccess', {'detail': data} );
		window.dispatchEvent( window.scanSuccess );
	};

	window.scanFailureTrigger = function( data ) {
		window.scanFailure = new CustomEvent( 'scanfailure', {'detail' : data} );
		window.dispatchEvent( window.scanFailure );
	};

	window.beatSuccessTrigger = function( data ) {
		window.beatSuccess = new CustomEvent( 'beatscansuccess', {'detail' : data} );
		window.dispatchEvent( window.beatSuccess );
	};

	window.beatFailureTrigger = function( data ) {
		window.beatFailure = new CustomEvent( 'beatscanfailure', {'detail' : data} );
		window.dispatchEvent( window.beatFailure );
	};

	window.registerScanSuccessListener = function( callback ) {
		window.addEventListener( 'scansuccess', function ( e ) {

			if ( 'function' === typeof callback ) {
				callback( e.detail );
			}
		} );
	};

	window.registerScanFailureListener = function( callback ) {
		window.addEventListener( 'scanfailure', function ( e ) {

			if ( 'function' === typeof callback ) {
				callback( e.detail );
			}
		} );
	};

	window.registerBeatSuccessListener = function( callback ) {
		window.addEventListener( 'beatscansuccess', function ( e ) {

			if ( 'function' === typeof callback ) {
				callback( e.detail );
			}
		} );
	};

	window.registerBeatFailureListener = function( callback ) {
		window.addEventListener( 'beatscanfailure', function ( e ) {

			if ( 'function' === typeof callback ) {
				callback( e.detail );
			}
		} );
	};

} );