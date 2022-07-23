/************************************************************************************************
 * @file update.js
 * @function ListItem.__update
 * @description Save changes to a listItem
 * @param {function} callback
 *
 * @author Arvic S. Babol
 * @support Kulot Systems (kulotsystems@gmail.com)
 *
 */
ListItem.prototype.__update = function(callback) {
    // ListItem.__update() :: Cache objects for future reference
    var listItem = this;
    var list     = this.listBody.list;
    var pane     = list.pane.pane;
    var navItem  = pane.formWizardTabPane.navItem;
    var tab      = navItem.tab;
    var menuItem = tab.sidebarMenuItem;
    var itemData = pane.paneRight.itemData;
    var system   = tab.sidebarMenuItem.sidebarMenu.sidebar.system;
    var model    = system.root + system.models + tab.model + '.php';

    var formData = itemData.body.getFormData();
    if(formData.state != itemData.body.formState) {
        // ListItem.__update() :: Proceed with update :: prepare $.ajax.data
        var ajaxData = {};
        ajaxData['update_' + tab.model.toLowerCase()] = listItem.id;
        ajaxData['data'] = formData.forms;

        // ListItem.__update() :: Disable itemDataHeader buttons and initiate AJAX request!
        Pace.restart();
        itemData.header.btnSave.disable({showSpinner: true});
        itemData.header.disableButtons();
        $.ajax({
            type : 'POST',
            url  : model,
            data : ajaxData,
            success: function (data) {
                // ListItem.__update() :: Enable itemDataHeader buttons
                itemData.header.enableButtons();

                // ListItem.__update() :: Process server response.success
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
                        // ListItem.__update() :: Refresh listItem
                        var newListItem = response.success.data;
                        listItem.id        = newListItem.item_id;
                        listItem.avatar    = newListItem.item_avatar;
                        listItem.maintitle = newListItem.item_maintitle;
                        listItem.subtitle  = newListItem.item_subtitle;
                        listItem.date      = newListItem.item_update_date;
                        listItem.href      = tab.href + '-' + newListItem.item_id.toString();
                        listItem.el.html(listItem.getHTML({isForDialog: false}));

                        // ListItem.__update() :: Store itemDataBody new formState
                        itemData.body.formState = formData.state;

                        // ListItem.__update() :: Show check icon in btnSave
                        itemData.header.btnSave.disable({showSpinner: true});
                        itemData.header.btnSave.enable('fa-check');

                        // ListItem.__update() :: Update document title or history
                        if(tab.activeItem == newListItem.item_id)
                            menuItem.updateDocTitle();
                        else {
                            tab.activeItem   = newListItem.item_id;
                            menuItem.history = listItem.href;
                            menuItem.updateHistory();
                        }

                        // ListItem.__update() :: Execute callbacks
                        setTimeout(function() {
                            itemData.body.el.find('div.callback').each(function() {
                                itemData.body.executeCallback($(this).attr('data-param'));
                            });
                        }, 1);

                        // ListItem.__update() :: Execute passed callback
                        if(callback !== undefined)
                            callback();
                    }
                }
            },
            error: function(data) {
                Pace.stop();
                system.messageDialog.show(
                    '<span class="text-danger">ERROR ' + data.status + ' ' + '(' + (data.status == 0 ? 'NO CONNECTION' : data.statusText) + ')' + '</span>',
                    '<b>UNABLE TO UPDATE <span class="text-info">' + tab.title + ' (' + listItem.maintitle + ')' + '</span> TO </b>' + '<span class="text-primary">' + model + '</span>',
                    function() {
                        window.location.reload();
                    }
                );
            }
        });
    }
    else {
        // ListItem.__update() :: Execute passed callback
        if(callback !== undefined)
            callback();
    }
};
