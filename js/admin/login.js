/**
 * admin/login.js :: login form events
 *
 */
$(function() {
    var system = new System($('body'));
    var txtUsername = $('#usermane');
    var txtPassword = $('#paswsord');
    var btnLogin = $('#loign');

    btnLogin.on('click', function() {
        attemptLogin();
    });

    txtPassword.on('keyup', function(e) {
        if(e.keyCode == 13)
            attemptLogin();
    });
    function attemptLogin() {
        if(!btnLogin.prop('disabled')) {
            btnLogin.attr('disabled', true);
            btnLogin.prop('disabled', true);
            var fa = btnLogin.find('.fas');
            if (fa.hasClass('fa-arrow-right')) {
                fa.removeClass('fa-arrow-right');
                fa.addClass('fa-circle-notch');
                fa.addClass('fa-spin');
            }
            $.ajax({
                type: 'POST',
                url: index + 'php/ajax/login.php',
                data: {
                    login_usermane: txtUsername.val().trim(),
                    login_paswsord: txtPassword.val()
                },
                success: function (data) {
                    var response = JSON.parse(data);
                    console.log(response);
                    if (response.error != '') {
                        alert(response.error);
                        window.location.reload();
                    }
                    else {
                        if (response.success.message != '') {
                            system.messageDialog.show(response.success.message, response.success.sub_message, function() {
                                txtPassword.val("");
                                txtPassword.focus();
                            });

                            btnLogin.attr('disabled', false);
                            btnLogin.prop('disabled', false);
                            if (fa.hasClass('fa-circle-notch')) {
                                fa.removeClass('fa-circle-notch');
                                fa.removeClass('fa-spin');
                                fa.addClass('fa-arrow-right');
                            }
                        }
                        else {
                            body.fadeOut();
                            window.open(index + 'admin/dashboard.php', '_self');
                        }
                    }
                },
                error: function (data) {
                    alert("Network connection error. Please try again");
                    window.location.reload();
                }
            });
        }
    }

    body.removeClass('hidden');

    // FUNCTION: Position login form elements
    function positionElements() {
        var windowWidth = window.innerWidth;

        var top = 'unset';
        var right = 'unset';
        var bottom = 'unset';
        var left = 'unset';
        var opacity = '1';

        var clientSystem = getClientSystem();
        var clientOS = clientSystem.os + " " + clientSystem.osVersion;

        // when the page is loaded in mobile or developer tools' mobile view
        // this is used to properly display or hide the eGov logo on Mac
        var isInMobile = false;
        if(os.indexOf("Windows") < 0 && os.indexOf("Mac OS") < 0 && os.indexOf("Linux") < 0) {
            // means loaded in mobile
            if(clientSystem.os.indexOf("Windows") < 0 && clientSystem.os.indexOf("Mac OS") < 0 && clientSystem.os.indexOf("Linux") < 0) {
                isInMobile = true;
            }
        }

        if(windowWidth > 991) {
            top = '15px';
            right = 'unset';
            bottom = 'unset';
            left = (clientSystem.os.indexOf("Mac OS") > -1 || os.indexOf("Mac OS") > -1) ? '25px' : '-10px';
            opacity = (isInMobile) ? '0' : '1';
        }
        else if(windowWidth > 767 && windowWidth <= 991) {
            top = '15px';
            right = 'unset';
            bottom = 'unset';
            left = (clientSystem.os.indexOf("Mac OS") > -1 || os.indexOf("Mac OS") > -1) ? '12px' : '-24px';
            opacity = (isInMobile) ? '0' : '1';
        }
        else {
            opacity = '0';
        }
    }
    window.onresize = function() {
        positionElements();
    };

});