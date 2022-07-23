// AJAX Call Template
Pace.restart();
$.ajax({
    type: 'POST',
    url: index + '',
    data: {

    },
    success: function(data) {
        var response = JSON.parse(data);
        if(response.error != '') {
            showMessageDialog(response.error, '', function () {
                window.location.reload();
            });
        }
        else {
            if(response.success.message != '')
                showMessageDialog(response.success.message, response.success.sub_message);
            else {

            }
        }
        Pace.stop();
    },
    error: function(data) {
        alert("[ERROR_MESSAGE]\n\nError " + data.status + " (" + data.statusText + ")");
        Pace.stop();
    }
});