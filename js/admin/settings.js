/* global WpWeixin */
(function($) {
	$.fn.currencyFormat = function() {
		this.each(function() {
			var split = this.value.split('.');

			if (split[1] && split[1].length > 2) {
				split[1] 	= split[1].substring(0, 2);
				this.value 	= parseFloat(split.join('.'));
			} else {
				this.value 	= this.value;
			}
		});

		return this;
    };
})(jQuery);

jQuery(document).ready(function($) {

	var sections = {
			responder: {
				handle: $('.wp_weixin-responder-section.wp_weixin-responder-field input'),
				content: $('.wp_weixin-responder-section:not(.wp_weixin-responder-field)')
			},
			proxy: {
				handle: $('.wp_weixin-proxy-section.wp_weixin-proxy-field input'),
				content: $('.wp_weixin-proxy-section:not(.wp_weixin-proxy-field)')
			}
		};

	$.each(sections, function(idx, section) {
		
		if (section.handle.prop('checked')) {
			section.content.show();
		} else {
			section.content.hide();
		}

		section.handle.on('change', function() {
			var handle = $(this);

			if (handle.prop('checked')) {
				section.content.show();
			} else {
				section.content.hide();
			}
		});
	});

	$('#wp_weixin_qr_amount').on('keyup', function(e) {
		e.preventDefault();
		$(this).currencyFormat();
	});

	$('.qr-button').on('click', function(e) {
		e.preventDefault();

		var button 	= $(this),
			img 	= $('#' + button.data('img')),
			url 	= $('#qr_url').val(),
			amount 	= $('#wp_weixin_qr_amount').val(),
			fixed 	= $('#wp_weixin_qr_amount_fixed').prop('checked'),
			data;

		if (button.hasClass('qr-payment-button')) {
			data = {
				amount 	: amount,
				fixed	: fixed,
				url 	: img.data('default_url')
			};
		} else {
			data = {
				url : url
			};
		}

		data.action = 'wp_weixin_get_qr';

		$.ajax({
			url: WpWeixin.ajax_url,
			type: 'POST',
			data: data
		}).done(function(response) {

			if (response.success) {
				img.attr('src', img.data('base_url') + response.data);
				img.css('visibility', 'visible');
				img.parent().children('span').hide();
			} else {
				img.css('visibility', 'hidden');
				img.parent().children('span').show();
			}
		}).fail(function(qXHR, textStatus) {
			WpWeixin.debug && window.console.log(textStatus);
		});
	});

});