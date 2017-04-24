$(function() {

    $("input,textarea").jqBootstrapValidation({
        preventSubmit: true,
        submitError: function($form, event, errors) {
            // additional error messages or events
        },
        submitSuccess: function($form, event) {
            event.preventDefault(); // prevent default submit behaviour
            var name = $("input#name").val();
            var email = $("input#email").val();
            var phone = $("input#phone").val();
            var message = $("textarea#message").val();
            var token = $("input[name=token]").val();
            
            var firstName = name; // For Success/Failure Message
            // Check for white space in name for Success/Fail message
            if (firstName.indexOf(' ') >= 0) {
                firstName = name.split(' ').slice(0, -1).join(' ');
            }
            
            $.ajax({
			  url: "/contact",
			  type: "POST",
                data: {
                    name: name,
                    phone: phone,
                    email: email,
                    message: message,
                    _token: token
                },
                cache: false,
                beforeSend: function( xhr ) {
					$('#success').html("").show();
					$('.js-submit-message').html('<div class="spinner"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div></div>');
				  }
              })
			  .done(function( data ) {
				// Success message
				$('#success').html("<div class='alert alert-success'>");
				$('#success > .alert-success').html("<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;")
					.append("</button>");
				$('#success > .alert-success')
					.append("<strong>Your message has been sent. </strong>");
				$('#success > .alert-success')
					.append('</div>');
					
				// Reset Submit Button
				$('.js-submit-message').html("Send Message");

				//clear all fields
				$('#contactForm').trigger("reset");
			  })
			  .fail(function(jqXHR, textStatus, errorThrown )
			  {
				// Fail message
				$('#success').html("<div class='alert alert-danger'>");
				$('#success > .alert-danger').html("<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;")
					.append("</button>");
					
				if (jqXHR.responseJSON && jqXHR.responseJSON[0])
				{
					$('#success > .alert-danger').append("<strong>Sorry, it seems that there is the following error with your mail request: "+jqXHR.responseJSON[0]);
				}
				else
				{
					$('#success > .alert-danger').append("<strong>Sorry, it seems that my mail server is not responding. Please try again later!");
				}
				
				$('#success > .alert-danger').append('</div>');
				
				// Reset Submit Button
				$('.js-submit-message').html("Send Message");
				
				setTimeout(function () {
                    $('#success').slideUp(800);
                  }, 8000);
				
			  });
        },
        filter: function() {
            return $(this).is(":visible");
        },
    });

    $("a[data-toggle=\"tab\"]").click(function(e) {
        e.preventDefault();
        $(this).tab("show");
    });
});


/*When clicking on Full hide fail/success boxes */
$('#name').focus(function() {
    $('#success').html('');
});
