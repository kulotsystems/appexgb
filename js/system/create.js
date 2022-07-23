/************************************************************************************************
 * @file create.js
 * @function ListBody.__create
 * @description Create new listItem on the listBody
 *
 * @author Arvic S. Babol
 * @support Kulot Systems (kulotsystems@gmail.com)
 *
 */
ListBody.prototype.__create = function() {
    // ListBody.__create() :: Cache objects for future reference
    var listBody = this;
    var tabPane  = listBody.list.pane.pane.formWizardTabPane;
    var navItem  = tabPane.navItem;
    var tab      = navItem.tab;
    var menuItem = tab.sidebarMenuItem;
    var system   = menuItem.sidebarMenu.sidebar.system;
    var model    = system.root + system.models + tab.model + '.php';

    // ListBody.__create() :: Prepare $.ajax.data
    var ajaxData = {};
    ajaxData['create_' + tab.model.toLowerCase()] = true;

    console.log(ajaxData);

    // ListBody.__create() :: Disable listHeader buttons and nitiate AJAX request!
    Pace.restart();
    listBody.list.header.btnCreate.disable({showSpinner: true});
    listBody.list.header.disableButtons();
    $.ajax({
        type : 'POST',
        url  : model,
        data : ajaxData,
        success: function (data) {
            var response = JSON.parse(data);
            if (response.error != '') {
                system.messageDialog.show(response.error, '', function() {
                    window.location.reload();
                });
            }
            else {
                // ListBody.__create() :: Enable listHeader buttons
                listBody.list.header.enableButtons();

                // ListBody.__create() :: Process server response.success
                if (response.success.message != '')
                    system.messageDialog.show(response.success.message, response.success.sub_message);
                else {
                    listBody.emptyItems();
                    setTimeout(function() {
                        tab.activeItem = parseInt(response.success.data);
                        navItem.options.isTabClicked = true;
                        listBody.__list('append');
                    }, 1);
                }
            }
        },
        error: function(data) {
            Pace.stop();
            system.messageDialog.show(
                '<span class="text-danger">ERROR ' + data.status + ' ' + '(' + (data.status == 0 ? 'NO CONNECTION' : data.statusText) + ')' + '</span>',
                '<b>UNABLE TO CREATE <span class="text-info">' + tab.title + '</span> FROM </b>' + '<span class="text-primary">' + model + '</span>',
                function() {
                    window.location.reload();
                }
            );
        }
    });
};


