/************************************************************************************************
 * @file delete.js
 * @function ListItem.__delete
 * @description Delete the listItem
 *
 * @author Arvic S. Babol
 * @support Kulot Systems (kulotsystems@gmail.com)
 *
 */
ListItem.prototype.__delete = function() {
    // ListItem.__delete() :: Cache objects for future reference
    var listItem = this;
    var list     = this.listBody.list;
    var pane     = list.pane.pane;
    var navItem  = pane.formWizardTabPane.navItem;
    var tab      = navItem.tab;
    var menuItem = tab.sidebarMenuItem;
    var itemData = pane.paneRight.itemData;
    var system   = tab.sidebarMenuItem.sidebarMenu.sidebar.system;
    var model    = system.root + system.models + tab.model + '.php';

    // ListBody.__delete() :: Prepare $.ajax.data
    var ajaxData = {};
    ajaxData['delete_' + tab.model.toLowerCase()] = listItem.id;

    // ListItem.__delete() :: Disable confirmDialog buttons and initiate AJAX request!
    Pace.restart();
    system.confirmDialog.btnYes.disable({showSpinner: true});
    system.confirmDialog.btnNo.disable({showSpinner: false});
    var itemActionLabel = itemData.body.el.find('.item-action-label');
    if(itemActionLabel.length > 0)
        itemActionLabel.text('Deleting');
    $.ajax({
        type : 'POST',
        url  : model,
        data : ajaxData,
        success: function (data) {
            system.confirmDialog.hide(function() {
                var response = JSON.parse(data);
                if (response.error != '') {
                    system.messageDialog.show(response.error, '', function() {
                        window.location.reload();
                    });
                }
                else {
                    if (response.success.message != '')
                        system.messageDialog.show(response.success.message, response.success.sub_message);
                    else {
                        // ListItem.__delete() :: Refresh listBody
                        listItem.listBody.__list('refresh');

                        // ListItem.__delete() :: Mobile view interaction
                        if(window.innerWidth <= 767) {
                            pane.hidePane('right');
                            pane.showPane('left');
                            pane.formWizardTabPane.slide('right');
                        }
                    }
                }
            });
        },
        error: function(data) {
            Pace.stop();
            system.confirmDialog.hide(function() {
                system.messageDialog.show(
                    '<span class="text-danger">ERROR ' + data.status + ' ' + '(' + (data.status == 0 ? 'NO CONNECTION' : data.statusText) + ')' + '</span>',
                    '<b>UNABLE TO DELETE <span class="text-info">' + tab.title + ' (' + listItem.maintitle + ')' + '</span> FROM </b>' + '<span class="text-primary">' + model + '</span>',
                    function() {
                        window.location.reload();
                    }
                );
            });
        }
    });
};