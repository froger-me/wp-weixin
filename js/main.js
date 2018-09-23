/* global wx, WP_Weixin, console */
jQuery(function($) {

    $(document).ready(function() {
        if ( $('.wechat-auth').length ) {
            get_auth_qr_code();
        
            $('.refresh').on('click', function(e) {
                e.preventDefault();
                get_auth_qr_code();
            });

            window.registerAuthScanSuccessListener(handleScanSuccess);
            window.registerAuthScanFailureListener(handleScanFailure);
            window.registerAuthBeatSuccessListener(handleBeatSuccess);
            window.registerAuthBeatFailureListener(handleBeatFailure);
        }

        function get_auth_qr_code() {
            var data      = {
                    nonce: $('#auth_qr_nonce').val(),
                    action: 'wp_weixin_get_auth_qr'
                },
                img       = $('.auth-qr-code'),
                hashField = $('#auth_hash');

            $.ajax({
                url: WP_Weixin.ajax_url,
                type: 'POST',
                data: data
            }).done(function(response) {

                if (response.success) {
                    img.attr('src', response.data.qrSrc);
                    img.css('visibility', 'visible');
                    hashField.val(response.data.hash);
                    $('.desktop-qr-content .message').show();
                    $('.waiting').show();
                    window.authListenerStart();
                } else {
                    img.css('visibility', 'hidden');
                    img.attr('src', '');
                    hashField.val('');
                    window.authListenerStop();
                }
            }).fail(function(qXHR, textStatus) {
                WP_Weixin.debug && window.console.log(textStatus);
            });
        }

        function handleScanSuccess(data) {
            $('.waiting').hide();
            $('.success').show();
            WP_Weixin.debug && console.log(data);

            if (data.redirect) {
                $('.success .redirect-message').show();
                setTimeout(function() {
                    window.location = data.redirect;
                }, 4000);
            }
        }

        function handleScanFailure(data) {
            $('.waiting').hide();
            $('.failure').show();
            $('.error').show();
            $('.auth-qr-code').css('visibility', 'hidden');
            $.each(data.error, function(idx, value) {
                $('.error').append('<br/>' + value + '<br/>');
            });
            WP_Weixin.debug && console.log(data);

            if (data.redirect) {
                $('.failure .redirect-message').show();
                setTimeout(function() {
                    window.location = data.redirect;
                }, 4000);
            }
        }

        function handleBeatSuccess(data) {
            $('.network-on').show();
            $('.network-off').hide();
            WP_Weixin.debug && console.log(data);
        }

        function handleBeatFailure(data) {
            $('.network-on').hide();
            $('.network-off').show();
            WP_Weixin.debug && console.log(data);
        }
    });

    window.wpWeixinShareTimelineSuccessTrigger = function(data) {
        window.wpWeixinShareTimelineSuccess = new CustomEvent('wpWeixinShareTimelineSuccess', {'detail' : data});
        window.dispatchEvent(window.wpWeixinShareTimelineSuccess);
    };
  
    window.wpWeixinShareTimelineFailureTrigger = function(data) {
        window.wpWeixinShareTimelineFailure = new CustomEvent('wpWeixinShareTimelineFailure', {'detail' : data});
        window.dispatchEvent(window.wpWeixinShareTimelineFailure);
    };
  
    window.wpWeixinShareAppMessageSuccessTrigger = function(data) {
        window.wpWeixinShareAppMessageSuccess = new CustomEvent('wpWeixinShareAppMessageSuccess', {'detail' : data});
        window.dispatchEvent(window.wpWeixinShareAppMessageSuccess);
    };
  
    window.wpWeixinShareAppMessageFailureTrigger = function(data) {
        window.wpWeixinShareAppMessageFailure = new CustomEvent('wpWeixinShareAppMessageFailure', {'detail' : data});
        window.dispatchEvent(window.wpWeixinShareAppMessageFailure);
    };

    window.wpWeixinShareTimelineSuccessListener = function(callback) {
        window.addEventListener('wpWeixinShareTimelineSuccess', function (e) {

            if (typeof callback === 'function') {
                callback(e.detail);
            }
        });
    };

    window.wpWeixinShareTimelineFailureListener = function(callback) {
        window.addEventListener('wpWeixinShareTimelineFailure', function (e) {

            if (typeof callback === 'function') {
                callback(e.detail);
            }
        });
    };

    window.wpWeixinShareAppMessageSuccessListener = function(callback) {
        window.addEventListener('wpWeixinShareAppMessageSuccess', function (e) {

            if (typeof callback === 'function') {
                callback(e.detail);
            }
        });
    };

    window.wpWeixinShareAppMessageFailureListener = function(callback) {
        window.addEventListener('wpWeixinShareAppMessageFailure', function (e) {

            if (typeof callback === 'function') {
                callback(e.detail);
            }
        });
    };

    wx.config({
        debug:      WP_Weixin.debug,
        appId:      WP_Weixin.weixin.appid,
        timestamp:  WP_Weixin.weixin.timestamp,
        nonceStr:   WP_Weixin.weixin.nonceStr,
        signature:  WP_Weixin.weixin.signature,
        jsApiList:  [
            'onMenuShareTimeline',
            'onMenuShareAppMessage',
            'startRecord',
            'stopRecord',
            'onVoiceRecordEnd',
            'playVoice',
            'pauseVoice',
            'stopVoice',
            'onVoicePlayEnd',
            'uploadVoice',
            'downloadVoice',
            'chooseImage',
            'previewImage',
            'uploadImage',
            'downloadImage',
            'translateVoice',
            'getNetworkType',
            'openLocation',
            'getLocation',
            'hideOptionMenu',
            'showOptionMenu',
            'hideMenuItems',
            'showMenuItems',
            'hideAllNonBaseMenuItem',
            'showAllNonBaseMenuItem',
            'closeWindow',
            'scanQRCode',
            'addCard',
            'chooseCard',
            'openCard'
        ]
    });

    wx.ready(function() {

        $('.wechat-close').on('click', function(e) {
            e.preventDefault();
            wx.closeWindow();
        });

        if (WP_Weixin.share) {
            wx.onMenuShareTimeline({
                title: WP_Weixin.share.title,
                link: WP_Weixin.share.link,
                imgUrl: WP_Weixin.share.imgUrl,
                success: function () {
                    window.wpWeixinShareTimelineSuccessTrigger(WP_Weixin.share);
                },
                cancel: function () {
                    window.wpWeixinShareTimelineFailureTrigger(WP_Weixin.share);
                }
            });

            wx.onMenuShareAppMessage({
                title: WP_Weixin.share.title,
                desc: WP_Weixin.share.desc,
                link: WP_Weixin.share.link,
                imgUrl: WP_Weixin.share.imgUrl,
                success: function () {
                    window.wpWeixinShareAppMessageSuccessTrigger(WP_Weixin.share);
                },
                cancel: function () {
                    window.wpWeixinShareAppMessageFailureTrigger(WP_Weixin.share);
                }
            });
        }
    });
});
