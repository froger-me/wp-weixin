/* global WP_WeixinAuthHeartBeat */
jQuery(function($) {
  window.authListenerStartEvent = new CustomEvent('authlistenerstart');
  window.authListenerStopEvent  = new CustomEvent('authlistenerstop');
  window.authScannerBeat        = false;

  window.addEventListener('authlistenerstart', function() {
    window.authScannerBeat = true;
    _startScannerListen();
  });

  window.addEventListener('authlistenerstop', function() {
    window.authScannerBeat = false;
    _stopScannerListen();
  });

  function _startScannerListen() {

    function scannerListen() {

      if (window.authScannerBeat) {

        window.authListener = setTimeout(function() {
            var hash      = $('#auth_hash').val(),
                activated = hash.length > 0,
                data      = {
                  'action': 'wp_weixin_auth_heartbeat_pulse',
                  'hash': hash
                };

            if (activated) {
              $.post(WP_WeixinAuthHeartBeat.ajax_url, data, function(response) {
                if (!response.success) {
                  window.scanAuthFailureTrigger({'auth':false, 'error':response.data});
                } else if (response.data && !response.data.auth) {
                  window.scanAuthFailureTrigger(response.data);
                } else if (!response.data) {
                  window.beatAuthSuccessTrigger(response.data);
                  scannerListen();
                } else {
                  window.scanAuthSuccessTrigger(response.data);
                }
              }).fail(function(jqXHR, textStatus){
                window.beatAuthFailureTrigger(textStatus);
                scannerListen();
              });
            }
        }, WP_WeixinAuthHeartBeat.heartbeatFreq);
      }
    }
    scannerListen();
  }

  function _stopScannerListen() {
    clearTimeout(window.authListener);
  }

  window.authListenerStart = function() {
    window.dispatchEvent(window.authListenerStartEvent);
  };

  window.authListenerStop = function() {
    window.dispatchEvent(window.authListenerStopEvent);
  };

  window.scanAuthSuccessTrigger = function(data) {
    window.scanSuccess = new CustomEvent('authsuccess', {'detail': data});
    window.dispatchEvent(window.scanSuccess);
  };

  window.scanAuthFailureTrigger = function(data) {
    window.scanFailure = new CustomEvent('authfailure', {'detail' : data});
    window.dispatchEvent(window.scanFailure);
  };

  window.beatAuthSuccessTrigger = function(data) {
    window.beatSuccess = new CustomEvent('beatauthsuccess', {'detail' : data});
    window.dispatchEvent(window.beatSuccess);
  };

  window.beatAuthFailureTrigger = function(data) {
    window.beatFailure = new CustomEvent('beatauthfailure', {'detail' : data});
    window.dispatchEvent(window.beatFailure);
  };

  window.registerAuthScanSuccessListener = function(callback) {
    window.addEventListener('authsuccess', function (e) {

      if (typeof callback === 'function') {
        callback(e.detail);
      }
    });
  };

  window.registerAuthScanFailureListener = function(callback) {
    window.addEventListener('authfailure', function (e) {

      if (typeof callback === 'function') {
        callback(e.detail);
      }
    });
  };

  window.registerAuthBeatSuccessListener = function(callback) {
    window.addEventListener('beatauthsuccess', function (e) {

      if (typeof callback === 'function') {
        callback(e.detail);
      }
    });
  };

  window.registerAuthBeatFailureListener = function(callback) {
    window.addEventListener('beatauthfailure', function (e) {

      if (typeof callback === 'function') {
        callback(e.detail);
      }
    });
  };

});