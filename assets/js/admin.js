jQuery(document).ready(function($) {

  function WCeBizBlockMetabox(button, color) {
    button.block({
      message: null,
      overlayCSS: {
        background: color+' url(' + wc_ebiz_params.loading + ') no-repeat center',
        backgroundSize: '16px 16px',
        opacity: 0.6
      }
    });
  }

  function WCeBizGenerateResponse(responseText, button) {
    //Remove old messages
    $('.wc-ebiz-message').remove();

    //Generate the error/success messages
    if (responseText.data.error) {
      button.before('<div class="wc-ebiz-error error wc-ebiz-message"></div>');
    } else {
      button.before('<div class="wc-ebiz-success updated wc-ebiz-message"></div>');
    }

    //Get the error messages
    var ul = $('<ul>');
    $.each(responseText.data.messages, function(i, value) {
      var li = $('<li>')
      li.append(value);
      ul.append(li);
    });
    $('.wc-ebiz-message').append(ul);
  }

  $('#wc_ebiz_generate').click(function(e) {
    e.preventDefault();
    var r = confirm("Biztosan létrehozod a számlát?");
    if (r != true) {
      return false;
    }
    var nonce = $(this).data('nonce');
    var order = $(this).data('order');
    var button = $('#wc-ebiz-generate-button');
    var note = $('#wc_ebiz_invoice_note').val();
    var deadline = $('#wc_ebiz_invoice_deadline').val();
    var completed = $('#wc_ebiz_invoice_completed').val();
    var request = $('#wc_ebiz_invoice_request').is(':checked');
    if (request) {
      request = 'on';
    } else {
      request = 'off';
    }

    var data = {
      action: 'wc_ebiz_generate_invoice',
      nonce: nonce,
      order: order,
      note: note,
      deadline: deadline,
      completed: completed,
      request: request
    };

    WCeBizBlockMetabox(button, '#fff');

    $.post(ajaxurl, data, function(response) {
      var responseText = response;

      WCeBizGenerateResponse(responseText, button);

      //If success, hide the button
      if (!responseText.data.error) {
        button.slideUp();
        button.before(responseText.data.link);
        if(responseText.data.link_delivery) {
          button.before(responseText.data.link_delivery);
        }
      }

      button.unblock();

    });
  });

  $('#wc_ebiz_options').click(function() {
    $('#wc_ebiz_options_form').slideToggle();
    return false;
  });

  $('#wc_ebiz_already').click(function(e) {
    e.preventDefault();
    var note = prompt("Számlakészítés kikapcsolása. Mi az indok?", "Ehhez a rendeléshez nem kell számla.");
    if (!note) {
      return false;
    }

    var nonce = $(this).data('nonce');
    var order = $(this).data('order');
    var button = $('#wc-ebiz-generate-button');

    var data = {
      action: 'wc_ebiz_already',
      nonce: nonce,
      order: order,
      note: note
    };

    WCeBizBlockMetabox(button, '#fff');

    $.post(ajaxurl, data, function(response) {
      var responseText = response;

      WCeBizGenerateResponse(responseText, button);

      //If success, hide the button
      if (!responseText.data.error) {
        button.slideUp();
        button.before(responseText.data.link);
      }

      button.unblock();


    });
  });

  $('#wc_ebiz_already_back').click(function(e) {
    e.preventDefault();
    var r = confirm("Biztosan visszakapcsolod a számlakészítés ennél a rendelésnél?");
    if (r != true) {
      return false;
    }

    var nonce = $(this).data('nonce');
    var order = $(this).data('order');
    var button = $('#wc-ebiz-generate-button');

    var data = {
      action: 'wc_ebiz_already_back',
      nonce: nonce,
      order: order
    };

    WCeBizBlockMetabox($('#ebiz_already_div'), '#fff');

    $.post(ajaxurl, data, function(response) {
      var responseText = response;
      WCeBizGenerateResponse(responseText, button);

      //If success, show the button
      if (!responseText.data.error) {
        button.slideDown();
      }

      $('#ebiz_already_div').unblock().slideUp();

    });
  });

  //Teljesítettnek jelölés
  $('#wc_ebiz_generate_sztorno').click(function(e) {
    e.preventDefault();
    var r = confirm("Biztosan sztornózva lesz?");
    if (r != true) {
      return false;
    }

    var nonce = $(this).data('nonce');
    var order = $(this).data('order');
    var form = $('#wc-ebiz-generate-button');
    var button = $(this);

    var data = {
      action: 'wc_ebiz_sztorno',
      nonce: nonce,
      order: order
    };

    WCeBizBlockMetabox(form, '#fff');

    $.post(ajaxurl, data, function(response) {
      var responseText = response;
      WCeBizGenerateResponse(responseText, button);

      //If success, hide the button
      if (!responseText.data.error) {
        button.slideUp();
        button.before(responseText.data.link);
        $('#wc-ebiz-generated-data').slideUp();
      }

      form.unblock();

    });
  });

  //Teljesítettnek jelölés
  $('#wc_ebiz_generate_complete').click(function(e) {
    e.preventDefault();
    var r = confirm("Biztosan teljesítve lett?");
    if (r != true) {
      return false;
    }

    var nonce = $(this).data('nonce');
    var order = $(this).data('order');
    var form = $('#wc-ebiz-generate-button');
    var button = $(this);

    var data = {
      action: 'wc_ebiz_complete',
      nonce: nonce,
      order: order
    };

    WCeBizBlockMetabox(form, '#fff');

    $.post(ajaxurl, data, function(response) {

      var responseText = response;
      WCeBizGenerateResponse(responseText, button);

      //If success, hide the button
      if (!responseText.data.error) {
        button.slideUp();
        button.before(responseText.data.link);
      }

      form.unblock();
    });
  });

  $('#woocommerce_wc_ebiz_pro_email').keypress(function (e) {
    if (e.which == 13) {
      $(this).parent().find('button').click();
      return false;
    }
  });

  $('#woocommerce_wc_ebiz_pro_key_submit').click(function(e){
    e.preventDefault();

    var key = $('#woocommerce_wc_ebiz_pro_key').val();
    var email = $('#woocommerce_wc_ebiz_pro_email').val();
    var button = $(this);
    var form = button.parents('.wc-ebiz-section-pro');

    var data = {
      action: 'wc_ebiz_pro_check',
      key: key,
      email: email
    };

    form.block({
      message: null,
      overlayCSS: {
        background: '#ffffff url(' + wc_ebiz_params.loading + ') no-repeat center',
        backgroundSize: '16px 16px',
        opacity: 0.6
      }
    });

    form.find('.notice').hide();

    $.post(ajaxurl, data, function(response) {
      //Remove old messages
      if(response.success) {
        window.location.reload();
        return;
      } else {
        form.find('.notice p').html(response.data.message);
        form.find('.notice').show();
      }
      form.unblock();
    });

  });

  $('#woocommerce_wc_ebiz_pro_key-deactivate').click(function(e){
    e.preventDefault();

    var button = $(this);
    var form = button.parents('.wc-ebiz-section-pro');

    var data = {
      action: 'wc_ebiz_pro_deactivate'
    };

    form.block({
      message: null,
      overlayCSS: {
        background: '#ffffff url(' + wc_ebiz_params.loading + ') no-repeat center',
        backgroundSize: '16px 16px',
        opacity: 0.6
      }
    });

    form.find('.notice').hide();

    $.post(ajaxurl, data, function(response) {
      //Remove old messages
      if(response.success) {
        window.location.reload();
        return;
      } else {
        form.find('.notice p').html(response.data.message);
        form.find('.notice').show();
      }
      form.unblock();
    });

  });

  // Hide notice
	$( '.wc-ebiz-notice .wc-ebiz-hide-notice').on('click', function(e) {
		e.preventDefault();
		var el = $(this).closest('.wc-ebiz-notice');
		$(el).find('.wc-ebiz-wait').remove();
		$(el).append('<div class="wc-ebiz-wait"></div>');
		if ( $('.wc-ebiz-notice.updating').length > 0 ) {
			var button = $(this);
			setTimeout(function(){
				button.triggerHandler( 'click' );
			}, 100);
			return false;
		}
		$(el).addClass('updating');
		$.post( ajaxurl, {
				action: 	'wc_ebiz_hide_notice',
				security: 	$(this).data('nonce'),
				notice: 	$(this).data('notice'),
				remind: 	$(this).hasClass( 'remind-later' ) ? 'yes' : 'no'
		}, function(){
			$(el).removeClass('updating');
			$(el).fadeOut(100);
		});
	});


});
