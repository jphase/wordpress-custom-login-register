jQuery( document ).ready( function( $ ) {

    if ( $('#registerform').length ) {
        /*
         *  Register form
         */

        // ReCAPTCHA
        if ( $('#recaptcha_response_field').length ) {
            $('#recaptcha_response_field').on( 'keydown', function ( e ) {
                var key = e.charCode || e.keyCode || 0;
                if ( key == 9 && ! e.shiftKey ) {
                    e.preventDefault();
                    $('#wp-submit').focus();
                }
            });
        }

        // Check username on load if POST values are autofilled
        if ( $.trim( $('#username').val() ).length ) {
            check_user( $('#username'), $('#username-check'), $('#wp-submit'), $('#username_validate').val(), validate_registration_form( true ) );
        }

        // Add AJAX username availability checking with a typing timer so it doesn't fire excessively
        var typing;
        var uname = $('#username');
        var username = $('#username').val();
        $('#registerform #username').on( 'keydown', function ( e ) {
            username = $(this).val();
            clearTimeout( typing );
        }).on( 'keyup', function ( e ) {
            clearTimeout( typing );
            typing = setTimeout( function() {
                if ( username != uname.val() ) {
                    username = uname.val();
                    check_user( uname, $('#username-check'), $('#wp-submit'), $('#username_validate').val() );
                }
            }, 500 );
        }).on( 'blur', function( e ) {
            clearTimeout( typing );
            check_user( uname, $('#username-check'), $('#wp-submit'), $('#username_validate').val() );
        });

        // Add password strength meter
        $('#password').on( 'keyup', function( e ) {
            check_pass( $('#password'), $('#password-strength'), $('#wp-submit'), [] );
        });

        // Add front-end validation to registration form
        $('#registerform').on( 'submit', function( e ) {
            return validate_registration_form();
        });

    } else if ( $('#lostpasswordform').length ) {
        /*
         *  Forgot password form styling (this can also be done with WordPress filters but I'm too lazy to do this right now)
         */
         $('#login h1').css({
            textAlign: 'center',
            fontSize: '34px',
            margin: '25px 0 20px'
         })
    }

});

// E-mail address validation
function is_valid_email( email ) {
    var pattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);
    return pattern.test( email );
};

// reCAPTCHA options
var RecaptchaOptions = {
    theme: 'custom',
    custom_theme_widget: 'recaptcha_widget'
}

// Password strength function
function check_pass( pass, meter, submit_btn, blacklist, added_check ) {
    var pass = pass.val();
    var pass2 = pass;

    if ( pass.length > 4 ) {
        // Reset the form & meter
        // submit_btn.attr( 'disabled', 'disabled' ).addClass( 'disabled' );
        meter.removeClass( 'short bad good strong' );

        // Extend our blacklist array with those from the inputs & site data
        blacklist = blacklist.concat( wp.passwordStrength.userInputBlacklist() )

        // Get the password strength
        var strength = wp.passwordStrength.meter( pass, blacklist, pass2 );

        // Add the strength meter results
        switch ( strength ) {
            case 2:
                meter.addClass( 'bad' ).html( pwsL10n.bad );
                break;
            case 3:
                meter.addClass( 'good' ).html( pwsL10n.good );
                break;
            case 4:
                meter.addClass( 'strong' ).html( pwsL10n.strong );
                break;
            case 5:
                meter.addClass( 'short' ).html( pwsL10n.mismatch );
                break;
            default:
                meter.addClass( 'short' ).html( pwsL10n.short );
        }

        // Remove disabled attribute from the submit button when the strength meets the requirements
        if ( 4 === strength && '' !== pass.trim() && added_check ) {
            submit_btn.removeAttr( 'disabled' ).removeClass( 'disabled' );
        }

    } else {
        meter.removeClass( 'bad good strong' ).addClass( 'short' ).html( 'Too short' );
        strength = 1;
    }

    return strength;
}

// AJAX username availability checking
function check_user( user, notify, submit_btn, nonce, added_check ) {
    var username = user.val();
    // Reset the form & notify field
    // submit_btn.attr( 'disabled', 'disabled' ).addClass( 'disabled' );
    notify.removeClass( 'strong bad' ).text( '' );

    if ( username.length > 4 ) {
        user.addClass( 'loading' );
        // Send our username for checking
        jQuery.post( '/wp-admin/admin-ajax.php', {
            action: 'username_availability',
            nonce: nonce,
            username: username
        }, function( response ) {
            if ( response.success ) {
                notify.addClass( 'strong' ).text( response.data + ' is available!' );
                // validate_registration_form( false );
                if ( added_check ) submit_btn.removeAttr( 'disabled' ).removeClass( 'disabled' );
            } else {
                notify.addClass( 'short' ).text( response.data );
                // submit_btn.attr( 'disabled', 'disabled' ).addClass( 'disabled' );
            }
            user.removeClass( 'loading' );
        });
    } else {
        notify.removeClass( 'strong bad' ).addClass( 'bad' ).text( 'Username is too short' );
    }
}

// Validate registration form
function validate_registration_form( scroll_focus ) {
    // Initialize
    var $ = jQuery;
    if ( typeof scroll_focus == 'undefined' ) {
        scroll_focus = true;
    }

    // Validate text inputs
    $('#registerform input[type="text"], #registerform input[type="password"]').each( function() {
        if ( ! $.trim( $(this).val() ).length ) {
            if ( $(this).attr('id') == 'recaptcha_response_field' ) {
                $('.recaptcha_only_if_image').addClass('error');
            } else {
                $(this).closest('label').addClass('error');
            }
        } else {
            if ( $(this).attr('id') == 'recaptcha_response_field' ) {
                $('.recaptcha_only_if_image').removeClass('error');
            } else {
                $(this).closest('label').removeClass('error');
            }
        }
    });

    // Validate email fields
    $('#registerform input[type="email"]').each( function() {
        if ( ! is_valid_email( $(this).val() ) ) {
            $(this).closest('label').addClass('error');
        } else {
            $(this).closest('label').removeClass('error');
        }
    });

    // Validate email confirmation
    if ( $.trim( $('#email').val() ).length ) {
        if ( $('#email').val() != $('#confirm_email').val() ) {
            $('#confirm_email').closest('label').addClass('error');
        } else {
            $('#confirm_email').closest('label').removeClass('error');
        }
    }

    // Validate username
    if ( ! $('#username-check').hasClass('strong') && ! $('#username-check').hasClass('good') ) {
        $('#username-check').closest('label').addClass('error');
    } else {
        $('#username-check').closest('label').removeClass('error');
    }

    // Validate password strength
    if ( ! $('#password-strength').hasClass('strong') && ! $('#password-strength').hasClass('good') ) {
        $('#password-strength').closest('label').addClass('error');
    } else {
        $('#password-strength').closest('label').removeClass('error');
    }

    // Validate TOS and privacy policy
    if ( ! $('#terms_of_service').is(':checked') ) {
        $('#terms_of_service').closest('label').addClass('error');
    } else {
        $('#terms_of_service').closest('label').removeClass('error');
    }

    // Check validation
    if ( $('#registerform .error').length ) {
        if ( scroll_focus ) {
            $('html, body').stop().animate({ scrollTop: $('#registerform .error:first').offset().top - 30 }, 900);
            $('#registerform .error:first').focus();
        }
        return false;
    }

    return true;
}
