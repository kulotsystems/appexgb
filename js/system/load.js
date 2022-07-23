/************************************************************************************************
 * @file load.js
 * @function SidebarMenuItem.load
 * @description Write the initial user interface for a SidebarMenuItem to $('.content')
 *
 * @author Arvic S. Babol
 * @support Kulot Systems (kulotsystems@gmail.com)
 *
 */
SidebarMenuItem.prototype.__load = function() {
    var menuItem = this;
    var system   = menuItem.sidebarMenu.sidebar.system;

    // SidebarMenuItem.__load() :: ContentHeader
    var h = "";
    h += "<div class='content-header jumbotron no-margin' data-pages='parallax'>";
        h += "<div class='container-fixed-lg sm-p-l-0 sm-p-r-0'>";
            h += "<div class='inner'>";
                h += "<ol class='breadcrumb no-selection'>";
                    h += "<li class='breadcrumb-item cursor-pointer toggle-sidebar' data-toggle='sidebar'><a>" + system.dir + "</a></li>";
                    h += "<li class='breadcrumb-item active cursor-pointer toggle-sidebar' data-toggle='sidebar'>" + menuItem.title + "</li>";
                    h += "<li class='breadcrumb-item no-before float-right p-r-10'>";
                        // RESERVED FOR BREADCRUMB BUTTONS
                    h += "</li>";
                h += "</ol>";
            h += "</div>";
        h += "</div>";
    h += "</div>";

    // SidebarMenuItem.__load() :: ContentBody
    h += "<div class='content-body main-content bg-white overflow-hidden'>";

        // SidebarMenuItem.__load() :: ContentFormWizard
        h += "<div class='content-form-wizard'>";

            // SidebarMenuItem.__load() :: ContentFormWizardHeader
            h += "<ul class='nav nav-tabs nav-tabs-separator nav-stack-sm hidden-sm-down' role='tablist' data-init-reponsive-tabs='dropdownfx'>";
            for(var i=0; i<menuItem.tabs.length; i++) {
                h += "<li class='nav-item' data-n='" + (i+1).toString() + "'>";
                    h += "<a class='content-tab text-center' data-toggle='tab' href='" + menuItem.tabs[i].href + "' role='tab' aria-expanded='false'>";
                        h += "<i class='" + menuItem.tabs[i].icon + " tab-icon'></i>";
                        h += "<span class='tab-title'>" + menuItem.tabs[i].title + "</span>";
                    h += "</a>";
                h += "</li>";
            }
            h += "</ul>";

            // SidebarMenuItem.__load() :: ContentFormWizardBody
            h += "<div class='tab-content no-top-padding no-bottom-padding'>";
                for(i=0; i<menuItem.tabs.length; i++) {
                    h += "<div class='tab-pane content-tab-pane' data-n='" + (i+1).toString() + "' aria-expanded='true'>";
                        h += "<div class='pane row'>";
                            if (menuItem.tabs[i].hasControl('list')) {

                                // SidebarMenuItem.__load() :: Pane.paneleft
                                h += "<div class='col-md-3 no-padding pane-left'>";
                                    h += "<div class='module-block'>";
                                        h += "<div class='card card-default no-margin h-100 list'>";
                                            h += "<div class='card-header separator list-header'>";
                                                h += "<div class='card-controls card-controls-left w-100'>";
                                                    h += "<div class='btn-group float-left'>";
                                                        h += "<button class='btn btn-link btn-xs m-l-10 cursor-pointer font-1em' data-toggle='' style='padding-top: 4px !important;'>";
                                                            h += "<span class='icon-title text-success text-lowercase'><span class='list-item-total'></span></span>";
                                                        h += "</button>";
                                                    h += "</div>";
                                                    h += "<div class='btn-group float-right'>";
                                                        if (menuItem.tabs[i].hasControl('create')) {
                                                            h += "<button class='btn btn-success btn-xs m-l-10 cursor-pointer font-1em btn-create-item' data-toggle=''>";
                                                                h += "<span class='fas fa-plus-circle fa-fw'></span><span class='lbl'> New</span>";
                                                            h += "</button>";
                                                        }
                                                        if (menuItem.tabs[i].hasControl('list')) {
                                                            h += "<button class='btn btn-default btn-xs m-l-10 cursor-pointer font-1em btn-jumpto-item text-success' data-toggle=''>";
                                                                h += "<span class='fas fa-arrow-up fa-fw'></span><span class='lbl'></span>";
                                                            h += "</button>";
                                                        }
                                                        if (menuItem.tabs[i].hasControl('search')) {
                                                            h += "<button class='btn btn-default btn-xs m-l-10 cursor-pointer font-1em btn-search-item text-success' data-toggle=''>";
                                                                h += "<span class='fas fa-search fa-fw'></span><span class='lbl'></span>";
                                                            h += "</button>";
                                                        }
                                                    h += "</div>";
                                                h += "</div>";
                                            h += "</div>";
                                            h += "<div class='card-block overflow-y-auto no-padding list-body'></div>";
                                        h += "</div>";
                                    h += "</div>";
                                h += "</div>";

                                // SidebarMenuItem.__load() :: Pane.paneRight
                                h += "<div class='col-md-9 no-padding pane-right'>";
                                    h += "<div class='module-block'>";
                                        h += "<div class='card card-default no-margin h-100 item-data'>";
                                            h += "<div class='card-header separator no-padding padding-left-15 padding-right-15 item-data-header'>";
                                                h += "<div class='card-controls card-controls-right w-100'>";
                                                    h += "<div class='btn-group float-left'>";
                                                        if (menuItem.tabs[i].hasControl('select')) {
                                                            h += "<button class='btn btn-default btn-xs m-l-10 cursor-pointer btn-back-to-items font-1em' data-toggle='' style='border-radius: 3px'>";
                                                                h += "<span class='fas fa-arrow-left'></span><span class='lbl'> Back</span>";
                                                            h += "</button>";
                                                        }
                                                        h += "<input type='text' class='btn btn-xs m-l-10 font-1em txt-find-in-page' data-toggle='' placeholder='Find...' style='text-align: left; cursor: auto; border: 0; background: transparent'>";
                                                    h += "</div>";

                                                    h += "<div class='btn-group float-right item-controls'>";
                                                        if (menuItem.tabs[i].hasControl('update')) {
                                                            h += "<button class='btn btn-success btn-xs m-l-10 cursor-pointer font-1em btn-save-item hidden' data-toggle=''>";
                                                                h += "<span class='fas fa-save fa-fw'></span><span class='lbl'> Save</span>";
                                                            h += "</button>";
                                                        }
                                                        if (menuItem.tabs[i].hasControl('print')) {
                                                            h += "<button class='btn btn-light btn-xs m-l-10 cursor-pointer font-1em text-success btn-print-item hidden' data-toggle=''>";
                                                                h += "<span class='fas fa-print fa-fw'></span><span class='lbl'> Print</span>";
                                                            h += "</button>";
                                                        }
                                                        if (menuItem.tabs[i].hasControl('delete')) {
                                                            h += "<button class='btn btn-default btn-xs m-l-10 cursor-pointer font-1em btn-delete-item hidden' data-toggle=''>";
                                                                h += "<span class='fas fa-trash fa-fw'></span><span class='lbl'></span>";
                                                            h += "</button>";
                                                        }
                                                    h += "</div>";
                                                h += "</div>";
                                            h += "</div>";
                                            h += "<div class='card-block overflow-y-auto item-data-body padding-15'></div>";
                                        h += "</div>";
                                    h += "</div>";
                                h += "</div>";
                            }
                            else {
                                h += "<div class='col-md-12 no-padding pane-right overflow-y-auto tab-pane-scrollable'>";
                                    h += "<div class='card card-default no-margin item-data'>";
                                        h += "<div class='card-block overflow-y-auto item-data-body padding-15'>";
                                            if(menuItem.tabs[i].model === 'Dashboard')
                                                h += system.generateDashboard();
                                        h += "</div>";
                                    h += "</div>";
                                h += "</div>";
                            }
                        h += "</div>";
                    h += "</div>";
                }
            h += "</div>";
        h += "</div>";
    h += "</div>";

    // SidebarMenuItem.__load() :: Quickview
    h += "<div class='quickview-wrapper' data-pages='quickview'>";
        h += "<ul class='nav nav-tabs no-padding quickview-header'>";
            h += "<li class='text-white' data-target='' data-toggle='tab'>";
                h += "<a href='#' class='text-white quickview-title'></a>";
            h += "</li>";
        h += "</ul>";
        h += "<a class='btn-link quickview-toggle cursor-pointer' style='right: 5px'>";
            h += "<span class='fa fa-times-circle text-white'></span>";
        h += "</a>";
        h += "<div class='quickview-body'>";
            h += "<div class='quickview-content-header'></div>";
            h += "<div class='quickview-content-body overflow-y-auto'></div>";
        h += "</div>";
    h += "</div>";

    // SidebarMenuItem.__load() :: Write the html
    system.content.el.html(h);

    // SidebarMenuItem.__load() :: Housekeeping for generated user interface
    system.content.header = new ContentHeader(system.content, '.content-header');
    system.content.body   = new ContentBody(system.content, '.content-body');
    system.quickview      = new Quickview(system, '.quickview-wrapper[data-pages="quickview"]');
    var T = setInterval(function() {
        if(system.quickview.isConstructed()) {
            clearInterval(T);
            system.content.body.contentFormWizard.header.activateCurrentNavItem();
            system.positionElements({});
        }
    }, 1);
};
