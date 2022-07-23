/************************************************************************************************
 * @file list.js
 * @function ListBody.__list
 * @description Get items from the model and write them in the listBody
 * @param {string} mode ['refresh', 'prepend', or 'append']
 * @param {function} [callback] [optional: the function to be executed after this operation]
 * @param {string} [jumpto] [optional: 'first' or 'last']
 *
 * @author Arvic S. Babol
 * @support Kulot Systems (kulotsystems@gmail.com)
 *
 */
ListBody.prototype.__list = function(mode, callback, jumpto) {
    // ListBody.__list() :: Cache objects for future reference
    var listBody = this;
    var tabPane  = listBody.list.pane.pane.formWizardTabPane;
    var navItem  = tabPane.navItem;
    var tab      = navItem.tab;
    var menuItem = tab.sidebarMenuItem;
    var system   = menuItem.sidebarMenu.sidebar.system;
    var model    = system.root + system.models + tab.model + '.php';

    // ListBody.__list() :: Configure jumpto
    if(jumpto == undefined)
        jumpto = '';

    // ListBody.__list() :: Reset system.ajaxTimer
    if(system.ajaxTimer != null)
        clearTimeout(system.ajaxTimer);

    // ListBody.__list() :: Prepare $.ajax.data
    var ajaxData = {};

    ajaxData['list_' + tab.model.toLowerCase()] = mode;
    ajaxData.limit = listBody.limit;
    ajaxData.first_item = (listBody.firstItem < 0) ? tab.activeItem : listBody.firstItem;
    ajaxData.last_item  = listBody.lastItem;
    ajaxData.jump_to    = jumpto;

    // ListBody.__list() :: Force mode to 'append' when listBody.firstItem is -1
    if(listBody.firstItem < 0)
        mode = 'append';

    // ListBody.__list() :: Set listBody flag properties to avoid repeated call to this method on scroll
    if(mode == 'refresh')
        listBody.isRefreshing = true;
    else if(mode == 'prepend')
        listBody.isPrepending = true;
    else if(mode == 'append')
        listBody.isAppending  = true;

    // ListBody.__list() :: Show loading of itemDataBody on refresh
    if(mode == 'refresh')
        tabPane.pane.paneRight.itemData.body.showLoading();

    // ListBody.__list() :: Show loading message on listBody and initiate AJAX request!
    listBody.showLoading(mode);
    $.ajax({
        type : 'POST',
        url  : model,
        data : ajaxData,
        success: function (data) {
            var response = JSON.parse(data);
            if (response.error != '') {
                system.messageDialog.show(response.error, '', function() {
                    window.location.reload();
                })
            }
            else {
                // ListBody.__list() :: Hide loading message on listBody
                listBody.hideLoading(mode);

                // ListBody.__list() :: Process server response.success
                if (response.success.message != '')
                    system.messageDialog.show(response.success.message, response.success.sub_message);
                else {
                    if(!listBody.isFirstItemFetched)
                        listBody.isFirstItemFetched = response.success.data.is_first_record_reached;
                    if(!listBody.isLastItemFetched)
                        listBody.isLastItemFetched  = response.success.data.is_last_record_reached;
                    var items = response.success.data.items;
                    listBody.list.header.el.find('.list-item-total').html("<span class='fa fa-layer-group fa-fw' style='opacity: 0.6'></span> <span class='font-1em text-bold text-montserrat'>" + system.parseCurrency(response.success.data.total, true) + "</span>");

                    // ListBody.__list() :: Refresh list items
                    if(mode == 'refresh') {
                        // ListBody.__list() :: Store scrollTop and empty listBody items
                        var temp = listBody.scrollTop;
                        listBody.emptyItems();

                        // ListBody.__list() :: Append newly fetched items
                        for(var i=0; i<items.length; i++) {
                            listBody.scrollTop = listBody.el.scrollTop();
                            listBody.addItem(
                                new ListItem(listBody, items[i].item, {isForLogs: false}, items[i].index),
                                mode
                            );
                        }
                        listBody.scrollTop    = temp;
                        listBody.isRefreshing = false;

                        // ListBody.__list() :: Force append if total items is less than the limit
                        if(listBody.itemIDs.length < listBody.limit - 1) {
                            listBody.__list('append');
                            listBody.hideLoading('append');
                        }
                    }

                    // ListBody.__list() :: Prepend list items
                    else if(mode == 'prepend') {
                        // prepend newly fetched items
                        for(var i=items.length-1; i>=0; i--) {
                            listBody.addItem(
                                new ListItem(listBody, items[i].item, {isForLogs: false}, items[i].index),
                                mode
                            );
                        }
                        listBody.isPrepending = false;
                    }

                    // ListBody.__list() :: Append list items
                    else if(mode == 'append') {
                        // configure scrollTop during first fetch
                        if(ajaxData.last_item < 0) {
                            listBody.scrollTop = listBody.itemHeight;
                            if (response.success.data.is_first_record_reached)
                                listBody.scrollTop = 0;
                            if (response.success.data.is_last_record_reached && tab.activeItem == items[items.length-1].id)
                                listBody.scrollTop = listBody.el[0].scrollHeight + listBody.itemHeight;
                        }

                        // append newly fetched items
                        for(var i=0; i<items.length; i++) {
                            listBody.addItem(
                                new ListItem(listBody, items[i].item, {sForLogs: false}, items[i].index),
                                mode
                            );
                        }
                        listBody.isAppending = false;
                    }

                    // ListBody.__list() :: Scroll the listBody to its scrollTop property
                    listBody.scroll();

                    // ListBody.__list() :: Activate the $('.list-item.active') on first call to this method
                    if(ajaxData.last_item < 0 || mode == 'refresh')
                        listBody.activateCurrentListItem(mode);

                    // ListBody.__list() :: Change arrow of listHeader.btn-jumpto-item
                    if(jumpto != '') {
                        var btnJumpto  = listBody.list.header.btnJumpto;
                        var jumptoIcon = btnJumpto.el.find('.fas');
                        if(jumpto == 'first') {
                            if (jumptoIcon.hasClass('fa-arrow-up')) {
                                jumptoIcon.removeClass('fa-arrow-up');
                                jumptoIcon.addClass('fa-arrow-down');
                                btnJumpto.icon = 'fa-arrow-down';
                            }
                        }
                        else if(jumpto == 'last') {
                            if (jumptoIcon.hasClass('fa-arrow-down')) {
                                jumptoIcon.removeClass('fa-arrow-down');
                                jumptoIcon.addClass('fa-arrow-up');
                                btnJumpto.icon = 'fa-arrow-up';
                            }
                        }
                    }

                    // ListBody.__list() :: Execute the callback function
                    if(callback != null)
                        callback();
                }
            }
        },
        error: function(data) {
            system.messageDialog.show(
                '<span class="text-danger">ERROR ' + data.status + ' ' + '(' + (data.status == 0 ? 'NO CONNECTION' : data.statusText) + ')' + '</span>',
                '<b>UNABLE TO LIST <span class="text-info">' + tab.title + '</span> FROM </b>' + '<span class="text-primary">' + model + '</span>',
                function() {
                    window.location.reload();
                }
            );
        }
    });
};


