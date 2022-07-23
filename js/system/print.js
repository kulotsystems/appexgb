/************************************************************************************************
 * @file print.js
 * @function ListItem.__print
 * @description Print listItem data
 *
 * @author Arvic S. Babol
 * @support Kulot Systems (kulotsystems@gmail.com)
 *
 */
ListItem.prototype.__print = function() {
    // ListItem.__print() :: Cache objects for future reference
    var listItem = this;
    var list     = this.listBody.list;
    var pane     = list.pane.pane;
    var navItem  = pane.formWizardTabPane.navItem;
    var tab      = navItem.tab;
    var menuItem = tab.sidebarMenuItem;
    var itemData = pane.paneRight.itemData;
    var system   = tab.sidebarMenuItem.sidebarMenu.sidebar.system;
    var model    = system.root + system.models + tab.model + '.php';

    function proceed() {
        // ListItem.__print() :: Perform printing
        /*itemData.header.disableButtons();
        list.header.disableButtons();
        itemData.header.btnPrint.enable();
        itemData.header.btnPrint.disable({showSpinner: true});
        var iFrame = $('#iF');
        iFrame.on('load', function() {
            itemData.header.enableButtons();
            list.header.enableButtons();
        });
        var formData = itemData.body.getFormData(true);
        window.open(model + '?print&id=' + listItem.id + '&form=' + formData.state, 'iF');*/

        var formData = itemData.body.getFormData(true);
        window.open(model + '?print&id=' + listItem.id + '&form=' + formData.state, '_blank');
    }

    // ListItem.__print() :: Save before printing
    if(tab.hasControl('update')) {
        listItem.__update(function() {
            proceed();
        });
    }
    else
        proceed();

};
