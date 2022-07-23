/************************************************************************************************
 * @file search.js
 * @function Quickview.__search
 * @description Perform data searching on the Quickview element
 *
 * @author Arvic S. Babol
 * @support Kulot Systems (kulotsystems@gmail.com)
 *
 */
Quickview.prototype.__search = function() {
    // Quickview.__search() :: Cache objects for future reference
    var quickview = this;
    var system    = quickview.system;
    var menuItem  = system.sidebar.menu.getActiveMenuItem();
    var tab       = menuItem.getActiveTab();
    var tabPane   = system.content.body.contentFormWizard.body.tabPanes[tab.index];
    var itemData  = tabPane.pane.paneRight.itemData;

    var searchQuery = quickview.body.txtSearch.el.val().trim();
    if((searchQuery != '' || quickview.options.mode=='search_dropdown') && !quickview.body.btnSearch.el.prop('disabled')) {

        // Quickview.search() :: Prepare variables
        var item    = '';
        var model   = '';
        var ref_model = '';
        var ref_key   = 0;
        if(quickview.options.mode == 'search_list') {
            item       = quickview.options.target.pane.pane.formWizardTabPane.navItem.tab.title;
            model      = quickview.options.target.pane.pane.formWizardTabPane.navItem.tab.model;
        }
        else if(quickview.options.mode == 'search_item' || quickview.options.mode == 'search_dropdown') {
            item  = quickview.options.target.find('label').text();
            model = quickview.options.target.attr('data-model');

            // Quickview.search() :: Get 'ref_model' and 'ref_key' when search mode = 'dropdown'
            if(quickview.options.mode=='search_dropdown') {
                if(quickview.options.target.attr('data-ref') != undefined) {
                    var refEl = itemData.body.el.find('[data-href="' + quickview.options.target.attr('data-ref') + '"]');
                    if(refEl.length > 0) {
                        if(refEl.attr('data-model') != undefined) {
                            if(refEl.attr('data-model') != '') {
                                ref_model = refEl.attr('data-model');
                                ref_key   = parseInt(refEl.attr('data-key'));
                            }
                        }
                    }
                }
            }
        }

        // Quickview.search() :: Prepare AJAX data
        var ajaxData = {};
        var url      = quickview.system.root + quickview.system.models + model + '.php';
        ajaxData['search_' + model.toLowerCase()] = searchQuery;
        ajaxData['ref_model'] = ref_model;
        ajaxData['ref_key']   = ref_key;

        // Quickview.search() :: Show search loading and initiate AJAX request!
        Pace.restart();
        quickview.body.btnSearch.disable({showSpinner: true});
        quickview.body.txtSearch.disable();
        quickview.body.showSearchLoading();
        $.ajax({
            type : 'POST',
            url  : url,
            data : ajaxData,
            success: function (data) {
                var response = JSON.parse(data);
                if (response.error != '') {
                    quickview.system.messageDialog.show(response.error, '', function() {
                        window.location.reload();
                    });
                }
                else {
                    // Quickview.search() :: Hide search loading
                    quickview.body.btnSearch.enable();
                    quickview.body.txtSearch.enable();
                    quickview.body.hideSearchLoading();

                    // Quickview.search() :: Process server response.success
                    if (response.success.message != '')
                        quickview.system.messageDialog.show(response.success.message, response.success.sub_message);
                    else {
                        if(window.innerWidth >= 768)
                            quickview.body.txtSearch.focus();

                        // Quickview.search() :: Process search results
                        var searchResults = response.success.data;
                        if(searchResults.length <= 0) {
                            var h = "";
                                h += "<div style='padding: 2px'>";
                                    h += "<div class='alert alert-danger'>";
                                    h += "No " + item + " found";
                                    if(searchQuery != '')
                                        h += " for <b><i>" + searchQuery + "</i></b>";
                                    if(quickview.options.mode == 'search_dropdown') {
                                        var parent = itemData.body.el.find('[data-href="' + quickview.options.target.attr('data-ref') + '"]');
                                        if(parent.length > 0) {
                                            if(parseInt(parent.attr('data-key')) <= 0)
                                                h += ". Please search for " + parent.find('label').text() + " first";
                                            else
                                                h += " under [" + parent.find('input').val() + "] " + parent.find('label').text();
                                        }
                                    }
                                    h += ".";
                                h += "</div>";
                            h += "</div>";
                            quickview.body.writeContentBody(h);
                        }
                        else {
                            for(var i=0; i<searchResults.length; i++) {
                                if(quickview.options.mode == 'search_list')
                                    quickview.body.appendSearchResult(new ListItem(quickview.options.target.body, searchResults[i], {isForLogs:false}, i));
                                else if(quickview.options.mode == 'search_item' || quickview.options.mode == 'search_dropdown')
                                    quickview.body.appendSearchResult(new ListItem(quickview.body, searchResults[i], {isForLogs:false}, i));
                            }
                        }
                    }
                }
            },
            error: function(data) {
                Pace.stop();
                system.messageDialog.show(
                    '<span class="text-danger">ERROR ' + data.status + ' ' + '(' + (data.status == 0 ? 'NO CONNECTION' : data.statusText) + ')' + '</span>',
                    '<b>UNABLE TO SEARCH <span class="text-info">' + item + '</span> FROM </b>' + '<span class="text-primary">' + url + '</span>',
                    function() {
                        window.location.reload();
                    }
                );
            }
        });
    }
};