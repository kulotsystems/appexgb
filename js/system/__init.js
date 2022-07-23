/****************************************************************************************************
 * @file __init.js
 * @description System template using Object Oriented Programming approach
 * @author Arvic S. Babol
 * @support Kulot Systems (kulotsystems@gmail.com)


 ****************************************************************************************************
 * @constructor System
 * @param {object} body [the body html element]
 *
 */
function System(body) {
    var system = this;
    system.cl  = 'system';
    system.el  = body;
    system.selector = 'body';
    system.models   = 'php/models/';

    system.messageDialog = new MessageDialog(system, '#message-dialog');
    system.confirmDialog = new ConfirmDialog(system, '#confirm-dialog');
    system.header    = new Header(system, '.header');
    system.banner1   = new Banner(system, '#banner1');
    system.banner2   = new Banner(system, '#banner2');
    system.sidebar   = new Sidebar(system, '.page-sidebar');
    system.content   = new Content(system, '.content');
    system.cover     = new El(system, '#main-opac');
    system.ajaxTimer = null;

    // System :: Properties that will be assigned on SidebarMenuItem.load()
    system.quickview = null;

    // System :: Properties from body
    system.index = body.attr('data-index');
    system.root  = body.attr('data-root');
    system.app   = body.attr('data-app');
    system.page  = body.attr('data-page');
    system.dir   = body.attr('data-dir');


    // System :: Show body
    setTimeout(function() {
        body.hide();
        body.removeClass('hidden');
        body.fadeIn(480);
    }, 1);


    // System :: Key events
    body.on('keyup', function(e) {
        // ESC
        if(e.keyCode == 27) {
            if(system.messageDialog.isShown)
                system.messageDialog.el.find('#btnMessageOK').click();
            else if(system.confirmDialog.isShown)
                system.confirmDialog.el.find('#btnConfirmNo').click();
            else if(system.quickview.el.hasClass('open'))
                system.quickview.el.find('.quickview-toggle').click();
        }
        // ENTER
        else if(e.keyCode == 13) {
            if(system.messageDialog.isShown)
                system.messageDialog.el.find('#btnMessageOK').click();
            else if(system.confirmDialog.isShown)
                system.confirmDialog.el.find('#btnConfirmYes').click();
        }
    });
}


    /************************************************************************************************
     * @function System.positionElements
     * @description Arrange the system elements in their proper position
     *
     */
    System.prototype.positionElements = function() {
        var windowWidth  = window.innerWidth;
        var windowHeight = window.innerHeight;

        // System.positionElements() :: Adjust header and content
        var header  = this.header;
        var content = this.content;
        var headerHeight        = header.getHeight();
        var contentHeaderHeight = content.header.getHeight();
        content.setPaddingTop(headerHeight);
        content.body.setHeight(windowHeight - (contentHeaderHeight + headerHeight));

        // System.positionElements :: Adjust banner and sidebar toggles
        if(windowWidth >= 992) {
            this.banner1.show();
            this.sidebar.removeToggle();
        }
        else {
            this.banner1.hide();
            this.sidebar.applyToggle();
        }

        // System.positionElements() :: Adjust contentFormWizard
        var contentFormWizard = this.content.body.contentFormWizard;
        var formWizardHeaderHeight = (windowWidth >= 768) ? contentFormWizard.header.getHeight() : contentFormWizard.dropdown.getHeight();
        var formWizardBodyHeight   = content.body.getHeight() - formWizardHeaderHeight - 1;
        contentFormWizard.body.setHeight(formWizardBodyHeight);
        var isQuickviewAdjusted = false;
        for(var i=0; i<contentFormWizard.body.tabPanes.length; i++) {
            var tabPane = contentFormWizard.body.tabPanes[i];
            tabPane.setHeight(formWizardBodyHeight);

            var pane = tabPane.pane;
            pane.paneLeft.setHeight(formWizardBodyHeight);
            pane.paneRight.setHeight(formWizardBodyHeight);

            // System.positionElements() :: Adjust list
            if(pane.paneLeft.hasProperty('list')) {
                var list = pane.paneLeft.list;
                var listHeaderHeight = list.header.getHeight();
                if(listHeaderHeight <= 0)
                    listHeaderHeight = 45;

                var listBodyHeight = formWizardBodyHeight - listHeaderHeight;
                list.body.setHeight(listBodyHeight);
                list.body.limit = parseInt(listBodyHeight / list.body.itemHeight) + 2;
                if(list.body.limit <= 0)
                    list.body.limit = 50;

                // System.positionElements() :: Show or Hide left/right panes
                if(windowWidth <= 767) {
                    var activeListItem = list.body.getActiveListItem();
                    if(activeListItem) {
                        if(activeListItem.options.isItemClicked) {
                            pane.hidePane('left');
                            pane.showPane('right');
                        }
                        else {
                            pane.showPane('left');
                            pane.hidePane('right');
                        }
                    }
                    else {
                        pane.showPane('left');
                        pane.hidePane('right');
                    }
                }
                else {
                    pane.showPane('left');
                    pane.showPane('right');
                }

                // System.positionElements() :: Adjust quickview
                if(!isQuickviewAdjusted) {
                    isQuickviewAdjusted = true;
                    var top   = headerHeight + contentHeaderHeight + formWizardHeaderHeight + 2;
                    var width = 0;
                    if (windowWidth <= 256)
                        width = windowWidth;
                    else if (windowWidth <= 480)
                        width = windowWidth * 0.60;
                    else if (windowWidth <= 767)
                        width = windowWidth * 0.45;
                    else if (windowWidth >= 768 && windowWidth <= 1199)
                        width = windowWidth * 0.25;
                    else if (windowWidth >= 1200)
                        width = (windowWidth - this.sidebar.getWidth()) * 0.25;
                    this.quickview.setPositionTop(top);
                    this.quickview.setWidth(width);
                    this.quickview.setHeight(windowHeight - top);
                    this.quickview.setPositionRight(width * -1);
                }
            }

            // System.positionElements() :: Adjust itemData
            if(pane.paneRight.hasProperty('itemData')) {
                var itemData = pane.paneRight.itemData;
                var itemDataHeaderHeight = itemData.header.getHeight();
                if(itemDataHeaderHeight <= 0)
                    itemDataHeaderHeight = 45;
                itemData.body.setHeight(formWizardBodyHeight - listHeaderHeight);
            }
        }
    };


    /************************************************************************************************
     * @function System.parseCurrency
     * @description Format an amount value to currency (ex. 123,000.00 or 123,000 [isInt = true])
     * @param {string} amount [the amount value]
     * @param {bool} isInt [to return an integer value or not]
     * @return {string}
     */
    System.prototype.parseCurrency = function(amount, isInt) {
        amount = this.parseAmount(amount).toString();
        if(isInt == undefined)
            isInt = false;
        if(isInt) {
            var i = parseInt(amount);
            if (i == undefined || i != i)
                i = 0;
            return i.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        else {
            var f = parseFloat(amount);
            if (f == undefined || f != f)
                f = 0;
            return f.toFixed(2).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
    };


    /************************************************************************************************
     * @function System.parseAmount
     * @description Get the numeric value from an amount value
     * @param {string} amount [the amount value]
     * @param {bool} [isInt] [to return an integer value or not]
     * @return {int | float}
     */
    System.prototype.parseAmount = function(amount, isInt) {
        amount = amount.toString();
        if(isInt == undefined)
            isInt = false;

        var str = '';
        for(var i=0; i<amount.length; i++) {
            var c = amount.substr(i, 1);
            if(c != ',')
                str += c;
        }

        var n;
        if(isInt)
            n = parseInt(str);
        else
            n = parseFloat(str);

        if(n == undefined || n != n)
            n = 0;
        return n;
    };


    /************************************************************************************************
     * @function System.isTextSelected
     * @description Determine if text is selected in an input element
     * @param {obj} input [the input element]
     * @return {bool}
     * https://stackoverflow.com/questions/5001608/how-to-know-if-the-text-in-a-textbox-is-selected/41671156
     */
    System.prototype.isTextSelected = function(input) {
        if (typeof input.selectionStart == 'number') {
            return input.selectionStart == 0 && input.selectionEnd == input.value.length;
        }
        else if (typeof document.selection != 'undefined') {
            input.focus();
            return document.selection.createRange().text == input.value;
        }
    };


    /************************************************************************************************
     * @function System.askToSave
     * @description Ask user to save changes made to item data
     * @params {function} callback
     */
    System.prototype.askToSave = function(callback) {
        var system = this;
        var activeTabPane = system.content.body.contentFormWizard.body.getActiveTabPane();
        if(activeTabPane) {
            var listBody = null;
            try {
                listBody = activeTabPane.pane.paneLeft.list.body;
            } catch (e) { }

            if(listBody != null) {
                var listItem = listBody.getActiveListItem();
                if(listItem) {
                    var pane     = listItem.listBody.list.pane.pane;
                    var itemData = pane.paneRight.itemData;
                    var navItem  = pane.formWizardTabPane.navItem;
                    var tab      = navItem.tab;

                    if(tab.hasControl('update')) {
                        if(!itemData.header.btnSave.el.prop('disabled')) {
                            var formData = itemData.body.getFormData();
                            if(formData.state !== itemData.body.formState) {
                                system.confirmDialog.show('<span class="text-success">SAVE CHANGES TO ' + tab.title.toUpperCase() + '</span>', 'Do you want to SAVE the changes you made to the following item from ' + tab.title + '?' + listItem.getHTML({isForDialog: true}), function() {
                                    system.confirmDialog.hide();
                                    listItem.__update(function() {
                                        callback();
                                    });
                                });
                                system.confirmDialog.btnNo.el.off();
                                system.confirmDialog.btnNo.el.on('click', function() {
                                    callback();
                                    system.confirmDialog.btnNo.el.off();
                                });

                                // overwrite active .nav-item.content-tab on DOM
                                setTimeout(function() {
                                    navItem.formWizardHeader.el.find('.content-tab.active').removeClass('active');
                                    navItem.el.find('.content-tab').addClass('active');
                                }, 30);
                            }
                            else
                                callback();
                        }
                        else
                            callback();
                    }
                    else
                        callback();
                }
                else
                    callback();
            }
            else
                callback();
        }
        else
            callback();
    };



/****************************************************************************************************
 * @constructor El
 * @description Defines properties of a System UI Element
 * @param {System} parent [the parent object]
 * @param {string} selector [the element selector]
 *
 */
function El(parent, selector) {
    var el = parent.el.find(selector);
    if(el.length > 0) {
        this[parent.cl]  = parent;
        this.el          = el;
        this.selector    = selector;
    }
    else {
        if(selector != '.pane-left')
            console.log('UIElement $(' + parent.selector + ' ' + selector + ') not found!');
    }
}

    /************************************************************************************************
     * @function El.isConstructed
     * @description Determine if UIElement object is contructed properly
     * @return {bool}
     *
     */
    El.prototype.isConstructed = function() {
        return (this.selector != undefined);
    };


    /************************************************************************************************
     * @function El.hasProperty
     * @description Determine if UIElement object has the specified property that is not undefined
     * @param {string} property [the property identifier]
     * @return {bool}
     *
     */
    El.prototype.hasProperty = function(property) {
        if(this[property] == null)
            return false;
        else
            return this[property].el != undefined;
    };


    /************************************************************************************************
     * @function El.getHeight
     * @description Get the height of a UIElement
     * @return {int}
     *
     */
    El.prototype.getHeight = function() {
        if(this.el != undefined)
            return parseInt(this.el.css('height'));
        else
            return 0;
    };


    /************************************************************************************************
     * @function El.getWidth
     * @description Get the width of a UIElement
     * @return {int}
     *
     */
    El.prototype.getWidth = function() {
        if(this.el != undefined)
            return parseInt(this.el.css('width'));
        else
            return 0;
    };


    /************************************************************************************************
     * @function El.setHeight
     * @description Set the height of a UIElement
     * @param {int} height [the desired height in pixels]
     *
     */
    El.prototype.setHeight = function(height) {
        if(this.el != undefined)
            this.el.css({'height' : height.toString() + 'px'});
    };


    /************************************************************************************************
     * @function El.setWidth
     * @description Set the width of a UIElement
     * @param {int} width [the desired width in pixels]
     *
     */
    El.prototype.setWidth = function(width) {
        if(this.el != undefined)
            this.el.css({'width' : width.toString() + 'px'});
    };


    /************************************************************************************************
     * @function El.setPositionTop
     * @description Set the position top of a UIElement
     * @param {int} top [the desired position in pixels]
     *
     */
    El.prototype.setPositionTop = function(top) {
        if(this.el != undefined)
            this.el.css({'top' : top.toString() + 'px'});
    };


    /************************************************************************************************
     * @function El.setPositionLeft
     * @description Set the position left of a UIElement
     * @param {int} left [the desired position in pixels]
     *
     */
    El.prototype.setPositionLeft = function(left) {
        if(this.el != undefined)
            this.el.css({'left' : right.toString() + 'px'});
    };


    /************************************************************************************************
     * @function El.setPositionRight
     * @description Set the position right of a UIElement
     * @param {int} right [the desired position in pixels]
     *
     */
    El.prototype.setPositionRight = function(right) {
        if(this.el != undefined)
            this.el.css({'right' : right.toString() + 'px'});
    };


    /************************************************************************************************
     * @function El.show
     * @description Remove the '.hidden' class from the UIElement
     *
     */
    El.prototype.show = function() {
        if(this.el != undefined) {
            if(this.el.hasClass('hidden'))
                this.el.removeClass('hidden');
        }
    };


    /************************************************************************************************
     * @function El.hide
     * @description Add the '.hidden' class to the UIElement
     *
     */
    El.prototype.hide = function() {
        if(this.el != undefined) {
            if (!this.el.hasClass('hidden')) {
                if(this.cl == 'banner') {
                    if(this.system.el.hasClass('sidebar-open'))
                        this.el.addClass('hidden');
                }
                else
                    this.el.addClass('hidden');
            }
        }
    };


    /************************************************************************************************
     * @function El.stay
     * @description Remove any slide animation from the UIElement
     *
     */
    El.prototype.stay = function() {
        if(this.el != undefined) {
            this.el.css({
                '-webkit-transition': 'all 0s ease',
                'transition': 'all 0s ease'
            });
            this.el.removeClass('slide-left');
            this.el.removeClass('slide-right');
        }
    };


    /************************************************************************************************
     * @function El.slide
     * @description Add a slide animation to the UIElement
     * @param {string} direction [the slide direction : 'left' or 'right']
     *
     */
    El.prototype.slide = function(direction) {
        if(this.el != undefined) {
            var el = this;
            el.stay();
            el.el.addClass('slide-' + direction);
            el.el.addClass('sliding');
            setTimeout(function () {
                el.el.css({
                    '-webkit-transition': 'all 0.3s ease',
                    'transition': 'all 0.3s ease'
                });
                el.el.removeClass("sliding");
            }, 100);
        }
    };



/****************************************************************************************************
 * @constructor Button extends El
 * @param {object} parent [the parent object]
 * @param {string} selector [the element selector]
 * @param {string} cl [class identifier]
 */
function Button(parent, selector, cl) {
    this.cl = cl;
    El.apply(this, arguments);
    if(this.isConstructed()) {
        var fas = this.el.find('.fas');
        if(fas.length > 0) {
            this.fas  = fas;
            this.icon = fas[0].className.split(' ')[1];
        }
        var lbl = this.el.find('.lbl');
        if(lbl.length > 0) {
            this.lbl   = lbl;
            this.label = lbl.html();
        }
    }
} Button.prototype = Object.create(El.prototype);


    /************************************************************************************************
     * @function Button.disable
     * @description Disable the Button element and show loading icon
     * @param {object} options [{ {bool} showSpinner }]
     * @param {string} [label] [the temporary label while the button is disabled]
     *
     */
    Button.prototype.disable = function(options, label) {
        if(!this.el.prop('disabled') || !this.el.attr('disabled')) {
            this.el.prop('disabled', true);
            this.el.attr('disabled', true);
            if(this.fas != undefined) {
                if(options.showSpinner) {
                    this.fas.removeClass(this.icon);
                    this.fas.addClass('fa-circle-notch');
                    this.fas.addClass('fa-spin');
                }
            }
            if(this.lbl != undefined && label != null)
                this.lbl.html(' ' + label);
        }
    };


    /************************************************************************************************
     * @function Button.enable
     * @description Enable the Button element and hide loading icon
     * @param {string} [icon] [the temporary icon right after the button is enabled]
     *
     */
    Button.prototype.enable = function(icon) {
        if(this.el.prop('disabled') || this.el.attr('disabled')) {
            var button = this;
            button.el.prop('disabled', false);
            button.el.attr('disabled', false);
            if(button.fas != undefined) {
                button.fas.removeClass('fa-spin');
                button.fas.removeClass('fa-circle-notch');
                if(icon != undefined) {
                    button.el.prop('disabled', true);
                    button.el.attr('disabled', true);
                    button.fas.addClass(icon);
                    setTimeout(function() {
                        button.fas.removeClass(icon);
                        button.fas.addClass(button.icon);
                        button.el.prop('disabled', false);
                        button.el.attr('disabled', false);
                    }, 800);
                }
                else
                    this.fas.addClass(this.icon);
            }
            if(this.lbl != undefined)
                this.lbl.html(this.label);
        }
    };



/****************************************************************************************************
 * @constructor Textbox extends El
 * @param {object} parent [the parent object]
 * @param {string} selector [the element selector]
 * @param {string} cl [class identifier]
 *
 */
function TextBox(parent, selector, cl) {
    this.cl = cl;
    El.apply(this, arguments);
} TextBox.prototype = Object.create(El.prototype);


    /************************************************************************************************
     * @function Textbox.disable
     * @description Disable the Textbox element
     *
     */
    TextBox.prototype.disable = function() {
        if(!this.el.prop('disabled')) {
            this.el.prop('disabled', true);
            this.el.attr('disabled', true);
            this.el.prop('readonly', true);
            this.el.attr('readonly', true);
        }
    };


    /************************************************************************************************
     * @function Textbox.enable
     * @description Enable the Textbox element
     *
     */
    TextBox.prototype.enable = function() {
        if(this.el.prop('disabled')) {
            this.el.prop('disabled', false);
            this.el.attr('disabled', false);
            this.el.prop('readonly', false);
            this.el.attr('readonly', false);
        }
    };


    /************************************************************************************************
     * @function Textbox.focus
     * @description Focus at the end of the Textbox element
     * @param {bool} [highlight] [select the existing value or not]
     *
     */
    TextBox.prototype.focus = function(highlight) {
        if(highlight == undefined)
            highlight = false;
        this.enable();
        if(window.innerWidth > 767) {
            this.el[0].selectionStart = this.el[0].selectionEnd = this.el[0].value.length;
            this.el.focus();
            if(highlight)
                this.el.select();
        }
    };


/****************************************************************************************************
 * @constructor FileBox extends El
 * @param {object} parent [the parent object]
 * @param {string} selector [the element selector]
 * @param {string} cl [class identifier]
 *
 */
function FileBox(parent, selector, cl) {
    this.cl = cl;
    El.apply(this, arguments);
} FileBox.prototype = Object.create(El.prototype);



/****************************************************************************************************
 * @constructor MessageDialog extends El
 * @param {System} system [the parent System object]
 * @param {string} selector [the element selector]
 *
 */
function MessageDialog(system, selector) {
    this.cl = 'messageDialog';
    El.apply(this, arguments);
    if(this.isConstructed()) {
        var messageDialog      = this;
        messageDialog.isShown  = false;
        messageDialog.callback = null;

        // MessageDialog :: Events :: Shown
        this.el.on('shown.bs.modal', function() {
            messageDialog.isShown = true;
        });
        // MessageDialog :: Events :: Hidden
        this.el.on('hidden.bs.modal', function() {
            if(messageDialog.callback != null)
                messageDialog.callback();
            messageDialog.isShown  = false;
            messageDialog.callback = null;
        });
    }
} MessageDialog.prototype = Object.create(El.prototype);


    /************************************************************************************************
     * @function MessageDialog.show
     * @description Show the message dialog that displays information to the user
     * @param {string} title [the header title of the message dialog]
     * @param {string} message [the body message of the message dialog]
     * @param {function} [callback] [the function to execute when the message dialog is hidden]
     *
     */
    MessageDialog.prototype.show = function(title, message, callback) {
        this.el.find('.dialog-title').html(title);
        this.el.find('.dialog-message').html(message);
        this.el.modal('show');
        if(callback != null)
            this.callback = callback;
    };


    /************************************************************************************************
     * @function MessageDialog.hide
     * @description Hide the message dialog that displayed information to the user
     * @param {function} [callback] [the function to execute when the message dialog is hidden]
     */
    MessageDialog.prototype.hide = function(callback) {
        this.el.modal('hide');
        if(callback != null)
            this.callback = callback;
    };



/****************************************************************************************************
 * @constructor ConfirmDialog extends El
 * @param {System} system [the parent System object]
 * @param {string} selector [the element selector]
 *
 */
function ConfirmDialog(system, selector) {
    this.cl = 'confirmDialog';
    El.apply(this, arguments);
    if(this.isConstructed()) {
        var confirmDialog      = this;
        confirmDialog.isShown  = false;
        confirmDialog.callback = null;
        confirmDialog.btnYes   = new Button(this, '#btnConfirmYes', 'btn-yes');
        confirmDialog.btnNo    = new Button(this, '#btnConfirmNo', 'btn-no');

        // ConfirmDialog :: Events :: Shown
        this.el.on('shown.bs.modal', function() {
            confirmDialog.isShown = true;
        });
        // ConfirmDialog :: Events :: Hidden
        this.el.on('hidden.bs.modal', function() {
            if(confirmDialog.callback != null)
                confirmDialog.callback();
            confirmDialog.isShown = false;
            confirmDialog.callback = null;
        });
    }
} ConfirmDialog.prototype = Object.create(El.prototype);


    /************************************************************************************************
     * @function ConfirmDialog.show
     * @description Show the confirm dialog that displays a YES or NO question to the user
     * @param {string} title [the header title of the confirm dialog]
     * @param {string} message [the body message of the confirm dialog]
     * @param {function} action [the function to execute when .btnYes button is clicked]
     * @param {function} [callback] [the function to execute when the confirm dialog is hidden]
     *
     */
    ConfirmDialog.prototype.show = function(title, message, action, callback) {
        var self = this;
        self.el.find('.dialog-title').html(title);
        self.el.find('.dialog-message').html(message);
        self.btnYes.enable();
        self.btnNo.enable();
        self.el.modal('show');
        if(action != null) {
            self.btnYes.el.off();
            this.btnYes.el.on('click', function() {
                self.btnYes.disable({showSpinner: true});
                self.btnNo.disable({showSpinner: false});
                action();
            });
        }
        if(callback != null)
            self.callback = callback;
    };


    /************************************************************************************************
     * @function ConfirmDialog.hide
     * @description Hide the confirm dialog that displayed a YES or NO question to the user
     * @param {function} [callback] [the function to execute when the confirm dialog is hidden]
     */
    ConfirmDialog.prototype.hide = function(callback) {
        this.el.modal('hide');
        this.btnYes.el.off();
        if(callback != null)
            this.callback = callback;
    };



/****************************************************************************************************
 * @constructor Sidebar extends El
 * @param {System} system [the parent System object]
 * @param {string} selector [the element selector]
 *
 */
function Sidebar(system, selector) {
    this.cl = 'sidebar';
    El.apply(this, arguments);
    if(this.isConstructed()) {
        this.header = new SidebarHeader(this, '.sidebar-header');
        this.menu = new SidebarMenu(this, '.sidebar-menu');
    }
} Sidebar.prototype = Object.create(El.prototype);


    /************************************************************************************************
     * @function Sidebar.applyToggle
     * @description Apply toggle into the sidebar element
     *
     */
    Sidebar.prototype.applyToggle = function() {
        this.system.el.find('.toggle-sidebar').each(function() {
            $(this).attr('data-toggle', 'sidebar');
            if(!$(this).hasClass('cursor-pointer'))
                $(this).addClass('cursor-pointer');
        });
    };


    /************************************************************************************************
     * @function Sidebar.removeToggle
     * @description Remove toggle into the sidebar element
     *
     */
    Sidebar.prototype.removeToggle = function() {
        this.system.el.find('.toggle-sidebar').each(function() {
            $(this).attr('data-toggle', '');
            if($(this).hasClass('cursor-pointer'))
                $(this).removeClass('cursor-pointer');
        });
    };



/****************************************************************************************************
 * @constructor SidebarHeader extends El
 * @param {Sidebar} sidebar [the parent Sidebar object]
 * @param {string} selector [the element selector]
 *
 */
function SidebarHeader(sidebar, selector) {
    this.cl = 'sidebarHeader';
    El.apply(this, arguments);
} SidebarHeader.prototype = Object.create(El.prototype);



/****************************************************************************************************
 * @constructor SidebarMenu extends El
 * @param {Sidebar} sidebar [the parent Sidebar object]
 * @param {string} selector [the element selector]
 *
 */
function SidebarMenu(sidebar, selector) {
    this.cl = 'sidebarMenu';
    El.apply(this, arguments);
    if(this.isConstructed()) {
        var sidebarMenu = this;
        sidebarMenu.menuItems = [];
        this.el.find('.menu-items > li.sidebar-menu-item').each(function() {
            sidebarMenu.menuItems.push(new SidebarMenuItem(sidebarMenu, '.sidebar-menu-item[data-href="' + $(this).attr('data-href') + '"]'));
        });
    }
} SidebarMenu.prototype = Object.create(El.prototype);


    /************************************************************************************************
     * @function SidebarMenu.getActiveMenuItem
     * @description Get the $('.active') SidebarMenuItem, deactivate others
     * @return {bool, SidebarMenuItem}
     *
     */
    SidebarMenu.prototype.getActiveMenuItem = function() {
        var menuItem = false;
        for(var i=0; i<this.menuItems.length; i++) {
            if(this.menuItems[i].isActive()) {
                if(!menuItem)
                    menuItem = this.menuItems[i];
                else
                    this.menuItems[i].deactivate();
            }
        }
        return menuItem;
    };


    /************************************************************************************************
     * @function SidebarMenu.activateCurrentMenuItem
     * @description Activate the $('.active') SidebarMenuItem
     *
     */
    SidebarMenu.prototype.activateCurrentMenuItem = function() {
        var activeMenuItem = this.getActiveMenuItem();
        if(activeMenuItem)
            activeMenuItem.activate({isMenuClicked: false});
        else {
            // SidebarMenu.activateCurrentMenuItem() :: Force activate first menuItem
            if (this.menuItems.length > 0)
                this.menuItems[0].activate({isMenuClicked: false});
            else {
                this.sidebar.system.messageDialog.show('<span class="text-danger">Access denied!</span>', 'Your account <b class="text-primary">UserType</b> does not have any system menu access.<br>Please contact the system administrator regarding this.', function() {
                    window.location.reload();
                });
            }
        }
    };


    /************************************************************************************************
     * @function SidebarMenu.searchMenuItem
     * @description Search for a SidebarMenuItem by the given selector.
     * @param {string} href [the element href]
     * @return {bool}
     *
     */
    SidebarMenu.prototype.searchMenuItem = function(href) {
        var menuItem = false;
        for(var i=0; i<this.menuItems.length; i++) {
            if(this.menuItems[i].href == href)
                menuItem = this.menuItems[i];
        }
        return menuItem;
    };


    /************************************************************************************************
     * @function SidebarMenu.generateItemLink
     * @description Generate item link given its model and id
     * @param {string} model [the item model]
     * @param {int} id [the item id]
     * @return {string}
     *
     */
    SidebarMenu.prototype.generateItemLink = function(model, id) {
        var link = '#';
        if(id == id) {
            for(var i=0; i<this.menuItems.length; i++) {
                var menuItem = this.menuItems[i];
                for(var j=0; j<menuItem.tabs.length; j++) {
                    var tab = menuItem.tabs[j];
                    if(tab.model == model) {
                        link = tab.href + '-' + id.toString()
                        break;
                    }
                }
                if(link != '#')
                    break;
            }
        }
        return link;
    };



/****************************************************************************************************
 * @constructor SidebarMenuItem extends El
 * @param {SidebarMenu} sidebarMenu [the parent SidebarMenu object]
 * @param {string} selector [the element selector]
 *
 */
function SidebarMenuItem(sidebarMenu, selector) {
    this.cl = 'sidebarMenuItem';
    El.apply(this, arguments);
    if(this.isConstructed()) {
        var sidebarMenuItem   = this;
        var a = this.el.find('.sidebar-menu-item-title');
        sidebarMenuItem.title   = a.text();
        sidebarMenuItem.href    = a.attr('href');
        sidebarMenuItem.history = sidebarMenuItem.href;
        sidebarMenuItem.tabs    = [];
        var dataTabs = JSON.parse(this.el.attr('data-tabs'));
        for(var i=0; i<dataTabs.length; i++) {
            sidebarMenuItem.tabs.push(new Tab(sidebarMenuItem, dataTabs[i], i));
        }

        // SidebarMenuItem :: Events :: Click
        sidebarMenuItem.el.on('click', function() {
            sidebarMenuItem.sidebarMenu.sidebar.system.askToSave(function() {
                if(window.innerWidth <= 991)
                    sidebarMenu.sidebar.el.data('pg.sidebar').toggleSidebar();
                if(!sidebarMenuItem.isActive())
                    sidebarMenuItem.activate({isMenuClicked: true});
            });
        });
    }
} SidebarMenuItem.prototype = Object.create(El.prototype);


    /************************************************************************************************
     * @function SidebarMenuItem.isActive
     * @description Determine if SidebarMenuItem is active or not
     * @return {bool}
     *
     */
    SidebarMenuItem.prototype.isActive = function() {
        return (this.el.hasClass('active'));
    };


    /************************************************************************************************
     * @function SidebarMenuItem.activate
     * @description Add $('.active') to SidebarMenuItem
     * @param {object} options [{ {bool} isMenuClicked }]
     *
     */
    SidebarMenuItem.prototype.activate = function(options) {
        // SidebarMenuItem.activate() :: Get current active sidebarMenuItem, deactivate it
        var currentActiveMenuItem = this.sidebarMenu.getActiveMenuItem();
        if(currentActiveMenuItem)
            currentActiveMenuItem.deactivate();

        // SidebarMenuItem.activate() :: Update sidebarMenuItem active status
        if (!this.el.hasClass('active')) {
            this.el.addClass('active');
            this.el.find('.icon-thumbnail').addClass('bg-success');
        }

        // SidebarMenuItem.activate() :: Call SidebarMenuItem.__load() on load.js
        this.options = options;
        this.history = this.href;
        this.__load();
    };


    /************************************************************************************************
     * @function SidebarMenuItem.deactivate
     * @description Remove $('.active') from SidebarMenuItem
     *
     */
    SidebarMenuItem.prototype.deactivate = function() {
        if(this.el.hasClass('active')) {
            this.el.removeClass('active');
            this.el.find('.icon-thumbnail').removeClass('bg-success');
        }
    };


    /************************************************************************************************
     * @function SidebarMenuItem.getActiveTab
     * @description Get the 'isActive' Tab of this SidebarMenuItem, deactivate others
     * @return {bool, Tab}
     *
     */
    SidebarMenuItem.prototype.getActiveTab = function() {
        var tab = false;
        for(var i=0; i<this.tabs.length; i++) {
            if(this.tabs[i].isActive) {
                if(!tab)
                    tab = this.tabs[i];
                else
                    this.tabs[i].isActive = false;
            }
        }
        return tab;
    };


    /************************************************************************************************
     * @function SidebarMenuItem.searchTab
     * @description Search for a Tab by the given index.
     * @param {int} n [the element index]
     * @return {bool, Tab}
     *
     */
    SidebarMenuItem.prototype.searchTab = function(n) {
        var tab = false;
        for(var i=0; i<this.tabs.length; i++) {
            if(i == n-1)
                tab = this.tabs[i];
        }
        return tab;
    };


    /************************************************************************************************
     * @function SidebarMenuItem.updateHistory
     * @description Modify the application URL based on SidebarMenuItem.history property
     *
     */
    SidebarMenuItem.prototype.updateHistory = function() {
        window.history.pushState(null, null, this.history);
        this.updateDocTitle();
    };


    /************************************************************************************************
     * @function SidebarMenuItem.updateDocTitle
     * @description Modify the application Title
     *
     */
    SidebarMenuItem.prototype.updateDocTitle = function() {
        var system = this.sidebarMenu.sidebar.system;
        var title  = system.app + ' (' + this.title;
        var activeTab = this.getActiveTab();
        if(activeTab) {
            title += ' | ' + $('<p>' + activeTab.title + '</p>').text();
            var contentBody = system.content.body;
            if(contentBody != null) {
                var tabPane = contentBody.contentFormWizard.body.tabPanes[activeTab.index];
                if(tabPane != undefined) {
                    var pane = tabPane.pane;
                    if(pane.hasProperty('paneLeft')) {
                        var activeItem = pane.paneLeft.list.body.getActiveListItem();
                        if(activeItem)
                            title = $('<p>' + activeItem.maintitle + '</p>').text() + ' | ' + title;
                    }
                }
            }
        }
        title += ')';
        document.title = title;
    };



/****************************************************************************************************
 * @constructor Tab
 * @param {SidebarMenuItem} menuItem [the parent SidebarMenuItem object]
 * @param {object} options [tab configuration]
 * @param {int} index [the array index of the tab]
 *
 */
function Tab(menuItem, options, index) {
    this[menuItem.cl] = menuItem;
    this.icon         = options.icon;
    this.title        = options.title;
    this.model        = options.model;
    this.controls     = options.controls;
    this.activeItem   = parseInt(options.active_item);
    this.isActive     = options.is_active;
    this.index        = index;
    this.href         = menuItem.href + '-' + (index + 1).toString()
}

    /************************************************************************************************
     * @function Tab.hasControl
     * @description Check if a tab has the specified control
     * @param {string} control [the control identifier]
     *
     */
    Tab.prototype.hasControl = function(control) {
        return (this.controls.indexOf(control) > -1);
    };



/****************************************************************************************************
 * @constructor Header extends El
 * @param {System} system [the parent System object]
 * @param {string} selector [the element selector]
 *
 */
function Header(system, selector) {
    this.cl = 'header';
    El.apply(this, arguments);
} Header.prototype = Object.create(El.prototype);



/****************************************************************************************************
 * @constructor Banner extends El
 * @param {System} system [the parent System object]
 * @param {string} selector [the element selector]
 *
 */
function Banner(system, selector) {
    this.cl = 'banner';
    El.apply(this, arguments);
} Banner.prototype = Object.create(El.prototype);



/****************************************************************************************************
 * @constructor Content extends El
 * @param {System} system [the parent System object]
 * @param {string} selector [the element selector]
 *
 */
function Content(system, selector) {
    this.cl = 'content';
    El.apply(this, arguments);
    if(this.isConstructed()) {
        this.header = null;
        this.body   = null;
    }
} Content.prototype = Object.create(El.prototype);


    /************************************************************************************************
     * @function Content.setPaddingTop
     * @description Set the padding top of the content element
     * @param {int} paddingTop [the height value in pixels]
     *
     */
    Content.prototype.setPaddingTop = function(paddingTop) {
        this.el.css({
            'padding-top' : paddingTop.toString() + 'px'
        });
    };



/****************************************************************************************************
 * @constructor ContentHeader extends El
 * @param {Content} content  [the parent Content object]
 * @param {string}  selector [the element selector]
 *
 */
function ContentHeader(content, selector) {
    this.cl = 'contentHeader';
    El.apply(this, arguments);
    if(this.isConstructed()) {
        this.sidebarMenuItem = content.system.sidebar.menu.getActiveMenuItem();
    }
} ContentHeader.prototype = Object.create(El.prototype);



/****************************************************************************************************
 * @constructor ContentBody extends El
 * @param {Content} content [the parent Content object]
 * @param {string} selector [the element selector]
 *
 */
function ContentBody(content, selector) {
    this.cl = 'contentBody';
    El.apply(this, arguments);
    if(this.isConstructed()) {
        this.contentFormWizard = new ContentFormWizard(this, '.content-form-wizard');
    }
} ContentBody.prototype = Object.create(El.prototype);



/****************************************************************************************************
 * @constructor ContentFormWizard extends El
 * @param {ContentBody} contentBody [the parent ContentBody object]
 * @param {string} selector [the element selector]
 *
 */
function ContentFormWizard(contentBody, selector) {
    this.cl = 'formWizard';
    El.apply(this, arguments);
    if(this.isConstructed()) {
        this.dropdown = null;
        this.header   = new ContentFormWizardHeader(this, '.nav-tabs');
        this.body     = new ContentFormWizardBody(this, '.tab-content');

        // ContentFormWizard :: fix on not able to trigger .cs-select.change for the first time
        var csSelect = this.dropdown.csSelect;
        if(!contentBody.content.system.sidebar.menu.getActiveMenuItem().options.isMenuClicked) {
            csSelect._toggleSelect();
            setTimeout(function() {
                csSelect._toggleSelect();
            }, 1);
        }
    }
} ContentFormWizard.prototype = Object.create(El.prototype);



/****************************************************************************************************
 * @constructor ContentFormWizardHeader extends El
 * @param {ContentFormWizard} formWizard [the parent ContentFormWizard object]
 * @param {string} selector [the element selector]
 *
 */
function ContentFormWizardHeader(formWizard, selector) {
    this.cl = 'formWizardHeader';
    El.apply(this, arguments);
    if(this.isConstructed()) {
        var formWizardHeader = this;
        formWizardHeader.navItems = [];
        formWizardHeader.el.find('.nav-item').each(function(i) {
            formWizardHeader.navItems.push(new ContentFormWizardNavItem(formWizardHeader, '.nav-item[data-n="' + $(this).attr('data-n') + '"]', i));
        });

        // ContentFormWizardHeader :: render responsive dropdown tabs
        var drop = formWizardHeader.el;
        drop.addClass('hidden-sm-down');
        drop.find('.nav-tab-dropdown').each(function() {
            $(this).remove();
        });
        var h = '';
        h += '<select class="cs-select cs-skin-slide full-width" data-init-plugin="cs-select" data-form-wizard-class="' + formWizardHeader.formWizard.el.attr('class') + '">';
        for (var i=1; i <= drop.children("li").length; i++) {
            var li = drop.children("li:nth-child(" + i + ")");
            var tabRef = li.children('a').attr('href');
            if (tabRef == '#' || '')
                tabRef = li.children('a').attr('data-target');
            h += '<option value="' + i.toString() + '"' + (li.children('a').hasClass('active') ? ' selected' : '') + ' data-icon="' + formWizardHeader.navItems[i-1].tab.icon + '">' + li.children('a').text() + '</option>';
        }
        h += '</select>';
        drop.after(h);
        var select = drop.next()[0];
        $(select).wrap('<div class="nav-tab-dropdown cs-wrapper full-width hidden-md-up"></div>');

        // ContentFormWizardHeader :: update ContentFormWizard.dropdown
        formWizard.dropdown = new ContentFormWizardDropdown(formWizard, '.nav-tab-dropdown');
        formWizard.dropdown.csSelect = new SelectFx(select);
    }
} ContentFormWizardHeader.prototype = Object.create(El.prototype);


    /************************************************************************************************
     * @function ContentFormWizardHeader.getActiveNavItem
     * @description Get the ContentFormWizardNavItem with active status, deactivate others
     * @return {bool, ContentFormWizardNavItem}
     *
     */
    ContentFormWizardHeader.prototype.getActiveNavItem = function() {
        var navItem = false;
        for(var i=0; i<this.navItems.length; i++) {
            if(this.navItems[i].tab.isActive) {
                if(!navItem)
                    navItem = this.navItems[i];
                else
                    this.navItems[i].deactivate();
            }
        }
        return navItem;
    };


    /************************************************************************************************
     * @function ContentFormWizardHeader.activateCurrentNavItem
     * @description Activate the active ContentFormWizardNavItem
     *
     */
    ContentFormWizardHeader.prototype.activateCurrentNavItem = function() {
        var activeNavItem = this.getActiveNavItem();
        if(activeNavItem) // proceed to activate tab
            activeNavItem.activate({isTabClicked: false});
        else {            // just update the history or document title
            var menuItem = this.formWizard.contentBody.content.system.sidebar.menu.getActiveMenuItem();
            if(menuItem.isMenuClicked)
                menuItem.updateHistory();
            else
                menuItem.updateDocTitle();
        }
    };



/****************************************************************************************************
 * @constructor ContentFormWizardDropdown extends El
 * @param {ContentFormWizard} formWizard [the parent ContentFormWizard object]
 * @param {string} selector [the element selector]
 *
 */
function ContentFormWizardDropdown(formWizard, selector) {
    this.cl = 'formWizardDropdown';
    El.apply(this, arguments);
    if(this.isConstructed()) {
        var dropdown = this;

        // ContentFormWizardDropdown :: Events :: Change
        dropdown.el.find('select.cs-select').on('change', function() {
            var n = parseInt($(this).val());
            if(n != n) // if n = NaN
                n = 0;
            if(n > 0)
                dropdown.formWizard.header.navItems[n-1].activate({isTabClicked: true});
        });
    }
} ContentFormWizardDropdown.prototype = Object.create(El.prototype);



/****************************************************************************************************
 * @constructor ContentFormWizardNavItem extends El
 * @param {ContentFormWizardHeader} formWizardHeader [the parent ContentFormWizardHeader object]
 * @param {string} selector [the element selector]
 * @param {int} index [the SidebarMenuItem Tab index]
 *
 */
function ContentFormWizardNavItem(formWizardHeader, selector, index) {
    this.cl = 'formWizardNavItem';
    El.apply(this, arguments);
    if(this.isConstructed()) {
        var navItem   = this;
        navItem.index = index;
        var system    = formWizardHeader.formWizard.contentBody.content.system;

        // ContentFormWizardNavItem :: Point navItem to corresponding SidebarMenuItem.tabs object
        var activeSidebarMenuItem = formWizardHeader.formWizard.contentBody.content.system.sidebar.menu.getActiveMenuItem();
        navItem.tab  = activeSidebarMenuItem.tabs[index];

        // ContentFormWizardNavItem :: Events :: Click
        navItem.el.find('a[data-toggle="tab"]').on('click', function(e) {
            if(!navItem.tab.isActive) {
                system.askToSave(function() {
                    navItem.activate({isTabClicked: true});
                });
            }
        });
    }
} ContentFormWizardNavItem.prototype = Object.create(El.prototype);


    /************************************************************************************************
     * @function ContentFormWizardNavItem.activate
     * @description Add active status to navItem element
     * @param {object} options [{ {bool} isTabClicked }]
     *
     */
    ContentFormWizardNavItem.prototype.activate = function(options) {
        // ContentFormWizardNavItem.activate() :: Get corresponding tabPane object
        var tabPane = this.formWizardHeader.formWizard.body.tabPanes[this.tab.index];

        // ContentFormWizardNavItem.activate() :: Get current active navItem, deactivate it
        if(options.isTabClicked) {
            var currentActiveNavItem = this.formWizardHeader.getActiveNavItem();
            if (currentActiveNavItem) {
                currentActiveNavItem.deactivate();

                // ContentFormWizardNavItem.activate() :: Manage tabPane sliding effect
                tabPane.slide(this.tab.index > currentActiveNavItem.tab.index ? 'left' : 'right');
            }
        }

        // ContentFormWizardNavItem.activate() :: Update Tab active status
        this.tab.isActive = true;
        var a = this.el.find('a[data-toggle="tab"]');
        if(!a.hasClass('active'))
            a.addClass('active');

        // ContentFormWizardNavItem.activate() :: Update formWizard dropdown placeholder
        this.formWizardHeader.formWizard.dropdown.el.find('.cs-select .cs-placeholder').html("<i class='" + this.tab.icon + "'></i> " + this.tab.title);

        // ContentFormWizardNavItem.activate() :: Activate the tabPane
        this.options = options;
        tabPane.activate();
    };


    /************************************************************************************************
     * @function ContentFormWizardNavItem.deactivate
     * @description Remove active status from navItem element
     *
     */
    ContentFormWizardNavItem.prototype.deactivate = function() {
        this.tab.isActive = false;
        var a = this.el.find('a[data-toggle="tab"]');
        if(a.hasClass('active'))
            a.removeClass('active');
    };



/****************************************************************************************************
 * @constructor ContentFormWizardBody extends El
 * @param {ContentFormWizard} formWizard [the parent ContentFormWizard object]
 * @param {string} selector [the element selector]
 *
 */
function ContentFormWizardBody(formWizard, selector) {
    this.cl = 'formWizardBody';
    El.apply(this, arguments);
    if(this.isConstructed()) {
        var formWizardBody = this;
        formWizardBody.tabPanes = [];
        formWizardBody.el.find('.tab-pane').each(function(i) {
            formWizardBody.tabPanes.push(new ContentFormWizardTabPane(formWizardBody, '.tab-pane[data-n="' + (i+1).toString() + '"]', i));
        });
    }
} ContentFormWizardBody.prototype = Object.create(El.prototype);


    /************************************************************************************************
     * @function ContentFormWizardBody.getActiveTabPane
     * @description Get the $('.active') ContentFormWizardTabPane, deactivate others
     * @return {bool, ContentFormWizardTabPane}
     *
     */
    ContentFormWizardBody.prototype.getActiveTabPane = function() {
        var tabPane = false;
        for(var i=0; i<this.tabPanes.length; i++) {
            if(this.tabPanes[i].isActive()) {
                if(!tabPane)
                    tabPane = this.tabPanes[i];
                else
                    this.tabPanes[i].deactivate();
            }
        }
        return tabPane;
    };



/****************************************************************************************************
 * @constructor ContentFormWizardTabPane extends El
 * @param {ContentFormWizardBody} formWizardBody [the parent ContentFormWizardBody object]
 * @param {string} selector [the element selector]
 * @param {int} index [the ContentFormWizardTabPane index]
 *
 */
function ContentFormWizardTabPane(formWizardBody, selector, index) {
    this.cl = 'formWizardTabPane';
    El.apply(this, arguments);
    if(this.isConstructed()) {
        this.navItem = formWizardBody.formWizard.header.navItems[index];
        this.pane    = new Pane(this, '.pane.row');
    }
} ContentFormWizardTabPane.prototype = Object.create(El.prototype);


    /************************************************************************************************
     * @function ContentFormWizardTabPane.isActive
     * @description Determine if ContentFormWizardTabPane is active or not
     * @return {bool}
     *
     */
    ContentFormWizardTabPane.prototype.isActive = function() {
        return (this.el.hasClass('active'));
    };


    /************************************************************************************************
     * @function ContentFormWizardTabPane.activate
     * @description Add $('.active') to ContentFormWizardTabPane
     *
     */
    ContentFormWizardTabPane.prototype.activate = function() {
        // ContentFormWizardTabPane.activate() :: Get current active tabPane, deactivate it
        var currentActiveTabPane = this.formWizardBody.getActiveTabPane();
        if(currentActiveTabPane)
            currentActiveTabPane.deactivate();

        // ContentFormWizardTabPane.activate() :: Set tabPane active status
        if (!this.el.hasClass('active'))
            this.el.addClass('active');

        // ContentFormWizardTabPane.activate() :: Call ListBody.__list() on list.js
        if(this.pane.hasProperty('paneLeft')) {
            var navItem  = this.navItem;
            var listBody = this.pane.paneLeft.list.body;
            var T = setInterval(function() {
                if(listBody.limit > -1) {
                    clearInterval(T);
                    listBody.__list((navItem.options.isTabClicked && listBody.items.length > 0) ? 'refresh' : 'append');
                }
            }, 1);
        }
        // ContentFormWizardTabPane.activate() :: Just update history or document title
        else {
            var menuItem = this.navItem.tab.sidebarMenuItem;
            if(menuItem.options.isMenuClicked)
                menuItem.updateHistory();
            else
                menuItem.updateDocTitle();
        }
    };


    /************************************************************************************************
     * @function ContentFormWizardTabPane.deactivate
     * @description Remove $('.active') from ContentFormWizardTabPane
     *
     */
    ContentFormWizardTabPane.prototype.deactivate = function() {
        if(this.el.hasClass('active'))
            this.el.removeClass('active');
    };



/****************************************************************************************************
 * @constructor Pane extends El
 * @param {object} parent [the parent object]
 * @param {string} selector [the element selector]
 *
 */
function Pane(parent, selector) {
    this.cl = 'pane';
    El.apply(this, arguments);
    if(this.isConstructed()) {
        if(selector == '.pane.row') {
            this.paneLeft  = new Pane(this, '.pane-left');
            this.paneRight = new Pane(this, '.pane-right');
        }
        else if(selector == '.pane-left')
            this.list = new List(this, '.list');
        else if(selector == '.pane-right')
            this.itemData = new ItemData(this, '.item-data');
    }
} Pane.prototype = Object.create(El.prototype);


    /************************************************************************************************
     * @function Pane.showPane
     * @description Show the specified child pane element of the parent Pane
     * @param {string} side ['left' or 'right']
     *
     */
    Pane.prototype.showPane = function(side) {
        var pane = (side == 'left') ? 'paneLeft' : 'paneRight';
        if(this.hasProperty(pane))
            this[pane].show();
    };


    /************************************************************************************************
     * @function Pane.hidePane
     * @description Hide the specified child pane element of the parent Pane
     * @param {string} side ['left' or 'right']
     *
     */
    Pane.prototype.hidePane = function(side) {
        var pane = (side == 'left') ? 'paneLeft' : 'paneRight';
        if(this.hasProperty(pane))
            this[pane].hide();
    };



/****************************************************************************************************
 * @constructor List extends El
 * @param {Pane} pane [the parent Pane object]
 * @param {string} selector [the element selector]
 *
 */
function List(pane, selector) {
    this.cl = 'list';
    El.apply(this, arguments);
    if(this.isConstructed()) {
        this.header = new ListHeader(this, '.list-header');
        this.body   = new ListBody(this, '.list-body');
    }
} List.prototype = Object.create(El.prototype);



/****************************************************************************************************
 * @constructor ListHeader extends El
 * @param {List} list [the parent List object]
 * @param {string} selector [the element selector]
 *
 */
function ListHeader(list, selector) {
    this.cl = 'listHeader';
    El.apply(this, arguments);
    if(this.isConstructed()) {
        var system     = list.pane.pane.formWizardTabPane.formWizardBody.formWizard.contentBody.content.system;
        var listHeader = this;
        var tab = list.pane.pane.formWizardTabPane.navItem.tab;
        listHeader.buttons = [];
        if(tab.hasControl('list')) {
            listHeader.btnJumpto = new Button(listHeader, '.btn-jumpto-item', 'btn-jumpto');
            if(listHeader.btnJumpto.isConstructed()) {
                listHeader.buttons.push(listHeader.btnJumpto);
                listHeader.btnJumpto.el.on('click', function () {
                    system.askToSave(function() {
                        var fas = listHeader.btnJumpto.el.find('.fas');
                        if(fas.hasClass('fa-arrow-up')) {
                            // list and jump to first item
                            listHeader.list.body.emptyItems();
                            setTimeout(function() {
                                listHeader.list.body.__list('append', null, 'first');
                            }, 1);
                        }
                        else if(fas.hasClass('fa-arrow-down')) {
                            // list and jump to last item
                            listHeader.list.body.emptyItems();
                            setTimeout(function() {
                                listHeader.list.body.__list('append', null, 'last');
                            }, 1);
                        }
                    });
                });
            }
        }
        if(tab.hasControl('create')) {
            listHeader.btnCreate = new Button(listHeader, '.btn-create-item', 'btn-create');
            if(listHeader.btnCreate.isConstructed()) {
                listHeader.buttons.push(listHeader.btnCreate);
                listHeader.btnCreate.el.on('click', function () {
                    system.askToSave(function() {
                        list.body.__create();
                    });
                });
            }
        }
        if(tab.hasControl('search')) {
            listHeader.btnSearch = new Button(listHeader, '.btn-search-item', 'btn-search');
            if(listHeader.btnSearch.isConstructed()) {
                listHeader.buttons.push(listHeader.btnSearch);
                listHeader.btnSearch.el.on('click', function () {
                    tab.sidebarMenuItem.sidebarMenu.sidebar.system.quickview.open({
                        mode: 'search_list',
                        target: list
                    });
                });
            }
        }
    }
} ListHeader.prototype = Object.create(El.prototype);


    /************************************************************************************************
     * @function ListHeader.disableButtons
     * @description Disable all buttons in ListHeader object
     *
     */
    ListHeader.prototype.disableButtons = function() {
        for(var i=0; i<this.buttons.length; i++) {
            this.buttons[i].disable({showSpinner: false});
        }
    };


    /************************************************************************************************
     * @function ListHeader.enableButtons
     * @description Enable all buttons in ListHeader object
     *
     */
    ListHeader.prototype.enableButtons = function() {
        for(var i=0; i<this.buttons.length; i++) {
            this.buttons[i].enable();
        }
    };


/****************************************************************************************************
 * @constructor ListBody extends El
 * @param {List} list [the parent List object]
 * @param {string} selector [the element selector]
 *
 */
function ListBody(list, selector) {
    this.cl = 'listBody';
    El.apply(this, arguments);
    if(this.isConstructed()) {
        var listBody = this;
        this.itemIDs = [];
        this.items   = [];
        this.firstItem    = -1;
        this.lastItem     = -1;
        this.limit        = -1; // to be set on System.prototype.positionElements
        this.itemHeight   = 60; // $('.list-item') height in pixels
        this.scrollTop    = 0;
        this.isPrepending = false;
        this.isAppending  = false;
        this.isRefreshing = false;
        this.isFirstItemFetched = false;
        this.isLastItemFetched  = false;
        this.selectCTR = 0;

        // ListBody :: Events :: SCROLL
        this.el.on('scroll', function() {
            listBody.scrollTop = $(this).scrollTop();
            if(listBody.itemIDs.length > 0 && !listBody.isFirstItemFetched && !listBody.isPrepending && !listBody.isRefreshing && listBody.scrollTop <= 0)
                listBody.__list('prepend');
            else if(listBody.itemIDs.length > 0 && !listBody.isLastItemFetched && !listBody.isAppending && !listBody.isRefreshing && listBody.scrollTop + listBody.getHeight() >= $(this)[0].scrollHeight)
                listBody.__list('append');
        });
    }
} ListBody.prototype = Object.create(El.prototype);


    /************************************************************************************************
     * @function ListBody.showLoading
     * @description Show the loading message before fetching items
     * @param {string} mode ['append' or 'prepend']
     *
     */
    ListBody.prototype.showLoading = function(mode) {
        // ListBody.showLoading :: Disable list buttons
        this.list.header.disableButtons();

        // ListBody.showLoading :: Append / Prepend loading message
        var tab = this.list.pane.pane.formWizardTabPane.navItem.tab;
        if(tab.sidebarMenuItem.isActive()) {
            if(this.el.find('.card-wait-' + mode).length <= 0) {
                var h = "";
                h += "<div class='card social-card share no-margin no-border w-100 card-wait-" + mode + "'>";
                    h += "<div class='card-header clearfix no-border no-border-radius overflow-hidden'>";
                        h += "<h5 class='text-montserrat no-wrap'><span class='fas fa-circle-notch fa-spin'></span> FETCHING <span class='text-info'>" + tab.title.split('_').join(' ') + "</span></h5>";
                        h += "<h6 class='no-wrap text-uppercase sub-title'>Please wait...</h6>";
                    h += "</div>";
                h += "</div>";
                if(mode == 'prepend')
                    this.el.prepend(h);
                else if(mode == 'append')
                    this.el.append(h);
            }
        }
    };


    /************************************************************************************************
     * @function ListBody.hideLoading
     * @description Hide the loading message after fetching items
     * @param {string} mode ['append' or 'prepend']
     *
     */
    ListBody.prototype.hideLoading = function(mode) {
        // ListBody.hideLoading :: Enable list buttons
        this.list.header.enableButtons();

        // ListBody.hideLoading :: Remove loading message
        var listBody = this;
        var tab      = listBody.list.pane.pane.formWizardTabPane.navItem.tab;
        if(tab.sidebarMenuItem.isActive()) {
            this.el.find('.card-wait-' + mode).each(function() {
                $(this).remove();
                if(mode == 'prepend')
                    listBody.scrollTop -= listBody.itemHeight;
                else if(mode == 'append')
                    listBody.scrollTop += listBody.itemHeight;
            });
        }
    };


    /************************************************************************************************
     * @function ListBody.addItem
     * @description Add an item on ListBody.items
     * @param {ListItem} listItem [the ListItem object to be added]
     * @param {string} mode ['append' or 'prepend']
     *
     */
    ListBody.prototype.addItem = function(listItem, mode) {
        if(this.itemIDs.indexOf(listItem.id) < 0) {
            if(mode == 'prepend') {
                this.itemIDs.unshift(listItem.id);
                this.items.unshift(listItem);
                this.scrollTop += this.itemHeight;
                if(this.list.pane.pane.formWizardTabPane.navItem.tab.sidebarMenuItem.isActive())
                    this.el.prepend(listItem.getHTML({isForDialog: false}));

            }
            else if(mode == 'append' || mode == 'refresh') {
                this.itemIDs.push(listItem.id);
                this.items.push(listItem);
                if(this.list.pane.pane.formWizardTabPane.navItem.tab.sidebarMenuItem.isActive())
                    this.el.append(listItem.getHTML({isForDialog: false}));
            }
            this.firstItem = this.items[0].id;
            this.lastItem  = this.items[this.items.length-1].id;

            listItem.el = this.el.find(listItem.selector);
            listItem.declareEvents();
        }
    };


    /************************************************************************************************
     * @function ListBody.emptyItems
     * @description Remove all ListBody.items
     *
     */
    ListBody.prototype.emptyItems = function() {
        this.itemIDs   = [];
        this.items     = [];
        this.firstItem = -1;
        this.lastItem  = -1;
        this.isFirstItemFetched = false;
        this.isLastItemFetched  = false;
        this.isRefreshing = true;
        var pane = this.list.pane.pane;
        if(pane.formWizardTabPane.navItem.tab.sidebarMenuItem.isActive()) {
            this.el.html('');
            pane.paneRight.itemData.body.el.html('');
        }
        this.isRefreshing = false;
    };


    /************************************************************************************************
     * @function ListBody.getActiveListItem
     * @description Get the $('.active') ListItem
     * @return {bool, ListItem}
     *
     */
    ListBody.prototype.getActiveListItem = function() {
        var listItem   = false;
        var activeItem = this.list.pane.pane.formWizardTabPane.navItem.tab.activeItem;
        var index      = this.itemIDs.indexOf(activeItem);
        if(index > -1)
            listItem = this.items[index];
        return listItem;
    };


    /************************************************************************************************
     * @function ListBody.activateCurrentListItem
     * @description Activate the $('.active') ListItem
     *
     */
    ListBody.prototype.activateCurrentListItem = function() {
        var activeListItem = this.getActiveListItem();
        if(activeListItem) // activate list item
            activeListItem.activate({isItemClicked: false});
        else {             // just update the history or document title
            var navItem = this.list.pane.pane.formWizardTabPane.navItem;
            navItem.tab.sidebarMenuItem.history = navItem.tab.href;
            if(navItem.options.isTabClicked || navItem.tab.sidebarMenuItem.options.isMenuClicked)
                navItem.tab.sidebarMenuItem.updateHistory();
            else
                navItem.tab.sidebarMenuItem.updateDocTitle();
        }
    };


    /************************************************************************************************
     * @function ListBody.scroll
     * @description Scroll to scrollTop property value
     *
     */
    ListBody.prototype.scroll = function() {
        try {
            this.el.scrollTop(this.scrollTop);
        } catch(e) {}
    };



/****************************************************************************************************
 * @constructor ListItem (extends El.prototype)
 * @param {ListBody} listBody [the parent ListBody object]
 * @param {object} obj [{item_id, item_avatar, item_maintitle, item_subtitle, item_update_date}]
 * @param {object} options [{isForLogs}]
 * @param {int} index [the array index]
 *
 */
function ListItem(listBody, obj, options, index) {
    this.cl = 'listItem';
    this[listBody.cl] = listBody;

    this.id          = (obj.item_id          != undefined) ? parseInt(obj.item_id) : 0;
    this.avatar      = (obj.item_avatar      != undefined) ? obj.item_avatar       : '';
    this.maintitle   = (obj.item_maintitle   != undefined) ? obj.item_maintitle    : '';
    this.subtitle    = (obj.item_subtitle    != undefined) ? obj.item_subtitle     : '';
    this.date        = (obj.item_update_date != undefined) ? obj.item_update_date  : '';
    this.searchtitle = (obj.item_searchtitle != undefined) ? obj.item_searchtitle  : '';
    this.options     = options;
    this.index       = index;

    this.selector = '.list-item[data-id="' + this.id.toString() + '"]';
    this.el       = null;
    this.href     = listBody.list != undefined ? listBody.list.pane.pane.formWizardTabPane.navItem.tab.href + '-' + this.id.toString() : '';

} ListItem.prototype = Object.create(El.prototype);


    /************************************************************************************************
     * @function ListItem.declareEvents
     * @description Declare the events for ListItem.el HTML element
     *
     */
    ListItem.prototype.declareEvents = function() {
        if(this.el != null) {
            var listItem = this;
            var listBody = listItem.listBody;
            var system   = listBody.list.pane.pane.formWizardTabPane.formWizardBody.formWizard.contentBody.content.system;

            // ListItem :: Events :: Click
            listItem.el.on('click', function(e) {
                e.preventDefault();
                system.askToSave(function() {
                    listItem.activate({isItemClicked: true});
                });
            });
        }
    };


    /************************************************************************************************
     * @function ListItem.getHTML
     * @description Generate the HTML code for a ListItem object
     *
     * @param {object} options - [{ {bool} isForDialog }]
     *
     * @return {string}
     *
     */
    ListItem.prototype.getHTML = function(options) {
        var system = null;
        if(this.listBody != null)
            system = this.listBody.list.pane.pane.formWizardTabPane.navItem.tab.sidebarMenuItem.sidebarMenu.sidebar.system;
        else if(this.quickviewBody != null)
            system = this.quickviewBody.quickview.system;

        var cardClass = ' list-item';
        if(this.options.isForLogs || options.isForDialog)
            cardClass = ' active';

        var tag =  options.isForDialog ? 'div' : 'a';
        var h   = "";
        h += "<" + tag + " class='card social-card" + cardClass + " share no-margin no-border w-100' href='" + this.href + "' data-id='" + this.id + "'>";
            h += "<div class='card-header clearfix no-border no-border-radius overflow-hidden'>";
                // avatar
                if (this.avatar != '') {
                    h += "<div class='item-avatar user-pic'>";
                        h += "<img alt='(img)' width='33' height='33' src='" + this.avatar + "'>";
                    h += "</div>";
                }
                // maintitle
                h += "<h5 class='no-wrap text-montserrat'>";
                    if(!this.options.isForLogs)
                        h += "<small class='item-number'>" + (this.index+1).toString() + "</small>. ";
                    h += "<span class='main-title'>" + this.maintitle + "</span>";
                h += "</h5>";

                // subtitle
                if(this.subtitle == '')
                    this.subtitle = '&nbsp;';
                h += "<h6 class='no-wrap sub-title'>" + this.subtitle + "</h6>";

                // date
                h += "<div class='icon-title label-hidden-bottom-right item-date'>" + this.date + "</div>";
            h += "</div>";
        h += "</" + tag + ">";
        return h;
    };


    /************************************************************************************************
     * @function ListItem.activate
     * @description Add $('.active') to ListItem
     * @param {object} options [{ {bool} isItemClicked }]
     *
     */
    ListItem.prototype.activate = function(options) {
        // ListItem.activate() :: Get current active listItem, deactivate it
        var currentActiveListItem = this.listBody.getActiveListItem();
        if(currentActiveListItem)
            currentActiveListItem.deactivate();

        // ListItem.activate() :: Update listItem active status
        var navItem = this.listBody.list.pane.pane.formWizardTabPane.navItem;
        navItem.tab.activeItem = parseInt(this.id);
        if (!this.el.hasClass('active'))
            this.el.addClass('active');

        // ListItem.activate() :: Update document history and title
        navItem.tab.sidebarMenuItem.history = this.href;
        if(options.isItemClicked || navItem.options.isTabClicked || navItem.tab.sidebarMenuItem.options.isMenuClicked)
            navItem.tab.sidebarMenuItem.updateHistory();
        else
            navItem.tab.sidebarMenuItem.updateDocTitle();

        // ListItem.activate() :: Call ListItem.__select() on select.js
        this.options = options;
        this.__select();
    };


    /************************************************************************************************
     * @function ListItem.deactivate
     * @description Remove $('.active') from ListItem
     *
     */
    ListItem.prototype.deactivate = function() {
        this.listBody.list.pane.pane.formWizardTabPane.navItem.tab.activeItem = -1;
        if(this.el.hasClass('active'))
            this.el.removeClass('active');
    };



/****************************************************************************************************
 * @constructor ItemData extends El
 * @param {Pane} pane [the parent Pane object]
 * @param {string} selector [the element selector]
 *
 */
function ItemData(pane, selector) {
    this.cl = 'itemData';
    El.apply(this, arguments);
    if(this.isConstructed()) {
        this.header = new ItemDataHeader(this, '.item-data-header');
        this.body   = new ItemDataBody(this, '.item-data-body');
    }
} ItemData.prototype = Object.create(El.prototype);



/****************************************************************************************************
 * @constructor ItemDataHeader extends El
 * @param {ItemData} itemData [the parent ItemData object]
 * @param {string} selector [the element selector]
 *
 */
function ItemDataHeader(itemData, selector) {
    this.cl = 'itemDataHeader';
    El.apply(this, arguments);
    if(this.isConstructed()) {
        var itemDataHeader = this;
        var tab = itemData.pane.pane.formWizardTabPane.navItem.tab;
        itemDataHeader.buttons = [];
        if(tab.hasControl('select')) {
            itemDataHeader.btnBack = new Button(itemDataHeader, '.btn-back-to-items', 'btn-back');
            if(itemDataHeader.btnBack.isConstructed()) {
                itemDataHeader.btnBack.el.on('click', function () {
                    var pane = itemData.pane.pane;
                    pane.hidePane('right');
                    pane.showPane('left');
                    pane.formWizardTabPane.slide('right');
                    pane.paneLeft.list.body.getActiveListItem().options.isItemClicked = false;
                });
            }
        }
        if(tab.hasControl('update')) {
            itemDataHeader.btnSave = new Button(itemDataHeader, '.btn-save-item', 'btn-save');
            if(itemDataHeader.btnSave.isConstructed()) {
                itemDataHeader.buttons.push(itemDataHeader.btnSave);
                itemDataHeader.btnSave.el.on('click', function() {
                    var activeListItem = itemData.pane.pane.paneLeft.list.body.getActiveListItem();
                    if(activeListItem)
                        activeListItem.__update();
                    else
                        tab.sidebarMenuItem.sidebarMenu.sidebar.system.messageDialog.show('NOTHING TO SAVE FROM ' + tab.title.toUpperCase() + '', 'Please select the item you wish to save from the list first.');
                });
            }
        }
        if(tab.hasControl('print')) {
            itemDataHeader.btnPrint = new Button(itemDataHeader, '.btn-print-item', 'btn-print');
            if(itemDataHeader.btnPrint.isConstructed()) {
                itemDataHeader.buttons.push(itemDataHeader.btnPrint);
                itemDataHeader.btnPrint.el.on('click', function() {
                    var activeListItem = itemData.pane.pane.paneLeft.list.body.getActiveListItem();
                    if(activeListItem)
                        activeListItem.__print();
                    else
                        tab.sidebarMenuItem.sidebarMenu.sidebar.system.messageDialog.show('NOTHING TO PRINT ' + tab.title.toUpperCase() + '', 'Please select the item you wish to print from the list first.');
                });
            }
        }
        if(tab.hasControl('delete')) {
            itemDataHeader.btnDelete = new Button(itemDataHeader, '.btn-delete-item', 'btn-delete');
            if(itemDataHeader.btnDelete.isConstructed()) {
                itemDataHeader.buttons.push(itemDataHeader.btnDelete);
                itemDataHeader.btnDelete.el.on('click', function() {
                    var activeListItem = itemData.pane.pane.paneLeft.list.body.getActiveListItem();
                    if(activeListItem)
                        tab.sidebarMenuItem.sidebarMenu.sidebar.system.confirmDialog.show('<span class="text-danger">CONFIRM TO DELETE FROM ' + tab.title.toUpperCase() + '</span>', 'Are you sure you want to delete the following item from ' + tab.title + '?' + activeListItem.getHTML({isForDialog: true}), function() {
                            activeListItem.__delete();
                        });
                    else
                        tab.sidebarMenuItem.sidebarMenu.sidebar.system.messageDialog.show('NOTHING TO DELETE FROM ' + tab.title.toUpperCase() + '', 'Please select the item you wish to delete from the list first.');
                });
            }
        }
        itemDataHeader.txtFindInPage = new TextBox(itemDataHeader, '.txt-find-in-page', 'txt-find');
        if(itemDataHeader.txtFindInPage.isConstructed()) {
            itemDataHeader.txtFindInPage.el.on('keyup', function(e) {
                var str = $(this).val();
                if(e.keyCode == 40) {      // Arrow Down
                    itemData.body.highlightNext();
                    itemDataHeader.txtFindInPage.focus();
                }
                else if(e.keyCode == 38) { // Up Arrow
                    itemData.body.highlightPrev();
                    itemDataHeader.txtFindInPage.focus();
                }
                else if(e.keyCode == 13) { // Enter
                    if(itemData.body.el.find('.' + itemData.body.foundClass).length > 0)
                        itemData.body.highlightNext();
                    else
                        itemData.body.find(str);
                    itemDataHeader.txtFindInPage.focus();
                }
                else {                     // Alphanumeric
                    if (itemData.body.tmrFind != null)
                        clearTimeout(itemData.body.tmrFind);

                    itemData.body.tmrFind = setTimeout(function () {
                        itemData.body.find(str);
                    }, 320);
                }
            });
        }
    }
} ItemDataHeader.prototype = Object.create(El.prototype);


    /************************************************************************************************
     * @function ItemDataHeader.disableButtons
     * @description Disable all buttons in ItemDataHeader object
     *
     */
    ItemDataHeader.prototype.disableButtons = function() {
        for(var i=0; i<this.buttons.length; i++) {
            this.buttons[i].disable({showSpinner: false});
        }
    };


    /************************************************************************************************
     * @function ItemDataHeader.enableButtons
     * @description Enable all buttons in ItemDataHeader object
     *
     */
    ItemDataHeader.prototype.enableButtons = function() {
        for(var i=0; i<this.buttons.length; i++) {
            this.buttons[i].enable();
        }
    };


    /************************************************************************************************
     * @function ItemDataHeader.hideButtons
     * @description Hide all buttons in ItemDataHeader object
     *
     */
    ItemDataHeader.prototype.hideButtons = function() {
        for(var i=0; i<this.buttons.length; i++) {
            this.buttons[i].hide();
        }
    };


    /************************************************************************************************
     * @function ItemDataHeader.showButtons
     * @description Hide all buttons in ItemDataHeader object
     *
     */
    ItemDataHeader.prototype.showButtons = function() {
        for(var i=0; i<this.buttons.length; i++) {
            this.buttons[i].show();
        }
    };


/****************************************************************************************************
 * @constructor ItemDataBody extends El
 * @param {ItemData} itemData [the parent ItemData object]
 * @param {string} selector [the element selector]
 *
 */
function ItemDataBody(itemData, selector) {
    this.cl = 'itemDataBody';
    El.apply(this, arguments);
    if(this.isConstructed()) {
        this.callbacks = [];
        var system = itemData.pane.pane.formWizardTabPane.navItem.tab.sidebarMenuItem.sidebarMenu.sidebar.system;

        // ItemDataBody :: Properties
        this.formState       = '';

        // ItemDataBody :: Find-in-page properties
        this.tmrFind         = null;
        this.foundClass      = "el-found";
        this.highlightClass  = "el-highlight";
        this.currentKeyword  = '';
        this.totalFound      = 0;
        this.highlightCTR    = 0;

        // ItemDataBody :: Delegate CLICK :: searchbox
        this.el.delegate('div.input-group-search button', 'click', function() {
            var inputGroup = $(this).parent().parent();
            var mode = inputGroup.attr('data-role') != undefined ? 'search_' + inputGroup.attr('data-role') : 'search_item';
            var key  = '';
            try {
                key = inputGroup.attr('data-key');
            } catch (e) { }
            itemData.pane.pane.formWizardTabPane.navItem.tab.sidebarMenuItem.sidebarMenu.sidebar.system.quickview.open({
                mode  : mode,
                target: inputGroup,
                key   : key
            });
        });

        // ItemDataBody :: Delegate CLICK :: img_upload
        this.el.delegate('.img-upload', 'click', function() {
            itemData.pane.pane.formWizardTabPane.navItem.tab.sidebarMenuItem.sidebarMenu.sidebar.system.quickview.open({
                mode  : 'upload_img',
                target: $(this)
            });
        });

        // ItemDataBody :: Delegate CLICK :: password_toggle
        this.el.delegate('div.input-group-password-toggle button', 'click', function() {
            var btn = $(this);
            var txt = btn.parent().parent().find('input');
            var fas = btn.find('.fas');
            if(fas.hasClass('fa-eye')) {
                fas.removeClass('fa-eye');
                fas.addClass('fa-eye-slash');
                txt.attr('type', 'text');
            }
            else {
                fas.removeClass('fa-eye-slash');
                fas.addClass('fa-eye');
                txt.attr('type', 'password');
            }
        });

        // ItemDataBody :: Delegate CLICK :: verify
        this.el.delegate('div.input-group-verifier button', 'click', function() {
            var btnVerify = $(this);
            var inpGroup  = btnVerify.parent().parent();
            if(inpGroup.find('input[type="text"]').val() == '') {
                Pace.restart();
                btnVerify.prop('disabled', true);
                btnVerify.attr('disabled', true);
                var fa = btnVerify.find('.fas');
                fa.addClass('fa-spin');

                var model = system.root + system.models + inpGroup.attr('data-model') + '.php';
                var ajaxData = {};
                ajaxData['callback_' + inpGroup.attr('data-model').toLowerCase()] = inpGroup.attr('data-param');
                ajaxData['value'] = itemData.body.el.find(inpGroup.attr('data-href')).attr(inpGroup.attr('data-attr'));

                $.ajax({
                    type: 'POST',
                    url : model,
                    data: ajaxData,
                    success: function(data) {
                        var response = JSON.parse(data);
                        if(response.error != '') {
                            system.messageDialog.show(response.error, '', function() {
                                window.location.reload();
                            });
                        }
                        else {
                            if(response.success.message != '')
                                system.messageDialog.show(response.success.message, response.success.sub_message);
                            else {
                                inpGroup.find('input[type="text"]').val(response.success.data);
                            }
                        }
                        Pace.stop();
                        btnVerify.prop('disabled', false);
                        btnVerify.attr('disabled', false);
                        fa.removeClass('fa-spin');
                    },
                    error: function(data) {
                        Pace.stop();
                        system.messageDialog.show(
                            '<span class="text-danger">ERROR ' + data.status + ' ' + '(' + (data.status == 0 ? 'NO CONNECTION' : data.statusText) + ')' + '</span>',
                            '<b>UNABLE TO VERIFY <span class="text-info">' + inpGroup.attr('data-param') + '</span> FROM </b>' + '<span class="text-primary">' + model + '</span>',
                            function() {
                                window.location.reload();
                            }
                        );
                    }
                });
            }
        });

        // ItemDataBody :: Delegate CHANGE :: select[data-details]
        this.el.delegate('select[data-details]', 'change', function() {
            var divDetails = itemData.body.el.find($(this).attr('data-details'));
            if(divDetails.length > 0)
                divDetails.html($(this).children('option').filter(':selected').attr('data-detail'));
        });

        // ItemDataBody :: Delegate BLUR :: .input-group-currency input.form-control
        this.el.delegate('.input-group-currency input.form-control', 'blur', function() {
            $(this).val(system.parseCurrency($(this).val(), $(this).hasClass('text-int')));
            if(system.parseAmount($(this).val()) <= 0)
                $(this).css({'opacity':'0.6'});
            else
                $(this).css({'opacity':'1'});
        });

        // ItemDataBody :: Delegate CLICK :: .input-group-currency input.form-control
        this.el.delegate('.input-group-currency input.form-control', 'click', function() {
            var txt = $(this);
            if(!txt.attr('readonly')) {
                if (system.parseAmount(txt.val()) == 0)
                    txt.val('');
            }
        });

        // ItemDataBody :: Delegate ENTER :: .input-group-currency input.form-control
        this.el.delegate('.input-group-currency input.form-control', 'keyup', function(e) {
            if(e.keyCode == 13)
                $(this).blur();
        });

        // ItemDataBody :: Delegate CLICK :: .input-group-search .img-avatar
        this.el.delegate('.input-group-search .img-avatar', 'click', function() {
            var inputGroupSearch = $(this).parent().parent().parent();
            var link = system.sidebar.menu.generateItemLink(inputGroupSearch.attr('data-model'), parseInt(inputGroupSearch.attr('data-key')));
            if(link != '#')
                window.open(link, '_blank');
        });

        // ItemDataBody :: Delegate CLICK :: .input-group-search .search-label
        this.el.delegate('.input-group-search .search-label', 'click', function() {
            var inputGroupSearch = $(this).parent().parent();
            var link = system.sidebar.menu.generateItemLink(inputGroupSearch.attr('data-model'), parseInt(inputGroupSearch.attr('data-key')));
            if(link != '#')
                window.open(link, '_blank');
        });

        // ItemDataBody :: Execute app-specific itemDataBody delegates in main.js
        this.finishDelegates();
    }
} ItemDataBody.prototype = Object.create(El.prototype);


    /************************************************************************************************
     * @function ItemDataBody.showLoading
     * @description Show loading status while fetching selected item's data
     *
     */
    ItemDataBody.prototype.showLoading = function() {
        // ItemDataBody.showLoading() :: Disable itemData buttons
        this.itemData.header.disableButtons();
        var itemGuide = this.el.find('.item-guide');
        if(itemGuide.length > 0)
            itemGuide.remove();
        this.el.find('> div').each(function() {
            $(this).remove();
        });
        if(this.el.html() != '')
            this.el.append("<p class='text-montserrat text-bold no-margin padding-bottom-15 p-wait'><span class='fas fa-circle-notch fa-spin'></span> PLEASE WAIT...</p>");
    };


    /************************************************************************************************
     * @function ItemDataBody.hideLoading
     * @description Hide loading status after fetching selected item's data
     *
     */
    ItemDataBody.prototype.hideLoading = function() {
        // ItemDataBody.hideLoading() :: Enable itemData buttons
        this.itemData.header.enableButtons();
        this.itemData.header.showButtons();
        this.el.find('.p-wait').remove();
    };


    /************************************************************************************************
     * @function ItemDataBody.find
     * @description Find the specified string in ItemDataBody.el
     * @param {string} str - the string to find
     *
     */
    ItemDataBody.prototype.find = function(str) {
        // ItemDataBody.find() ::  Unhighlight previous keywords
        if (this.currentKeyword != '') {
            this.el.unhighlight({className: this.foundClass});
            this.el.unhighlight({className: this.highlightClass});
        }

        // ItemDataBody.find() ::  Highlight currentKeyword
        this.currentKeyword = str;
        this.el.highlight(str, {className: this.foundClass});
        this.totalFound = this.el.find('.' + this.foundClass).length;
        this.highlightCTR = 0;
        this.doHighlight();
    };


    /************************************************************************************************
     * @function ItemDataBody.highlightPrev
     * @description Highlight previous keyword
     *
     */
    ItemDataBody.prototype.highlightPrev = function() {
        this.highlightCTR -= 1;
        if(this.highlightCTR < 0) {
            if(this.totalFound > 0)
                this.highlightCTR = this.totalFound - 1;
            else
                this.highlightCTR = 0;
        }
        this.doHighlight();
    };


    /************************************************************************************************
     * @function ItemDataBody.highlightNext
     * @description Highlight next keyword
     *
     */
    ItemDataBody.prototype.highlightNext = function() {
        this.highlightCTR += 1;
        if(this.highlightCTR >= this.totalFound)
            this.highlightCTR = 0;
        this.doHighlight();
    };


    /************************************************************************************************
     * @function ItemDataBody.doHighlight
     * @description Do the highlighting of keyword
     *
     */
    ItemDataBody.prototype.doHighlight = function() {
        var itemDataBody  = this;
        var foundElements = itemDataBody.el.find('.' + itemDataBody.foundClass);
        foundElements.each(function(i) {
            var foundElement = $(this);
            if(i == itemDataBody.highlightCTR) {
                if(!foundElement.hasClass(itemDataBody.highlightClass))
                    foundElement.addClass(itemDataBody.highlightClass);

                itemDataBody.el.css({'scroll-behavior':'unset'});
                itemDataBody.el.scrollTop(foundElement.offset().top - itemDataBody.el.offset().top + itemDataBody.el.scrollTop() - 35);
                itemDataBody.el.css({'scroll-behavior':'smooth'});
            }
            else {
                if(foundElement.hasClass(itemDataBody.highlightClass))
                    foundElement.removeClass(itemDataBody.highlightClass);
            }
        });
    };


    /************************************************************************************************
     * @function ItemDataBody.getFormData
     * @description Collect form data
     * @param {bool} [isForPrint] - get only the values of .print-param elements
     * @return {object}
     *
     */
    ItemDataBody.prototype.getFormData = function(isForPrint) {
        if(isForPrint == undefined)
            isForPrint = false;
        var system = this.itemData.pane.pane.formWizardTabPane.navItem.tab.sidebarMenuItem.sidebarMenu.sidebar.system;
        var obj = {
            forms: [],
            state: ''
        };

        function processForm(row) {
	        row.find('> .form, > .callback').each(function() {
	            var formEl = $(this);
	            if(formEl.hasClass('forms') || formEl.hasClass('callback'))
	            	processForm(formEl);
	            else {
		            var form = {
		                class: formEl.attr('class').split(' ')[1],
		                rows : []
		            };

		            // ItemDataBody.getFormData :: Traverse rows
		            formEl.find('> .row.clearfix').each(function() {
		                var rowEl = $(this);
		                var row   = {};

		                // ItemDataBody.getFormData :: Traverse columns
		                rowEl.find('> div').each(function() {
		                    var colEl = $(this);
		                    var type  = colEl.attr('data-type');
		                    var el    = null;
		                    var id    = '';
		                    var val   = '';

                            if(!isForPrint || (isForPrint && colEl.hasClass('print-param'))) {
                                // ItemDataBody.getFormData :: Get column id and value depending on its type
                                if(type == 'label') {
                                    el  = colEl.find('label');
                                    id  = el.attr('id');
                                    val = el.text();
                                }
                                else if(type == 'text') {
                                    el  = colEl.find('input[type="text"]');
                                    id  = el.attr('id');
                                    val = el.val();
                                }
                                else if(type == 'hidden') {
                                    el  = colEl.find('input[type="hidden"]');
                                    id  = el.attr('id');
                                    val = el.val();
                                }
                                else if(type == 'textarea') {
                                    el  = colEl.find('textarea');
                                    id  = el.attr('id');
                                    val = el.val();
                                }
                                else if(type == 'number') {
                                    el  = colEl.find('input[type="number"]');
                                    id  = el.attr('id');
                                    val = el.val();
                                }
                                else if(type == 'time') {
                                    el  = colEl.find('input[type="time"]');
                                    id  = el.attr('id');
                                    val = el.val();
                                }
                                else if(type == 'select') {
                                    el  = colEl.find('select');
                                    id  = el.attr('id');
                                    val = el.val();
                                }
                                else if(type == 'toggle' || type == 'checkbox') {
                                    el  = colEl.find('input[type="checkbox"]');
                                    id  = el.attr('id');
                                    val = el.prop('checked') ? 1 : 0;
                                }
                                else if(type == 'searchbox') {
                                    var div = colEl.find('.input-group-search');
                                    el  = div.find('input[type="text"]');
                                    id  = el.attr('id');
                                    val = div.attr('data-key');
                                }
                                else if(type == 'labelbox') {
                                    var div = colEl.find('.input-group-label');
                                    el  = div.find('span.form-control');
                                    id  = el.attr('id');
                                    val = div.attr('data-key');
                                }
                                else if(type == 'verifier') {
                                    el  = colEl.find('input[type="text"]');
                                    id  = el.attr('id');
                                    val = el.val();
                                }
                                else if(type == 'password_toggle') {
                                    el  = colEl.find('input');
                                    id  = el.attr('id');
                                    val = el.val();
                                }
                                else if(type == 'percentage') {
                                    el  = colEl.find('input');
                                    id  = el.attr('id');
                                    val = parseFloat(el.val());
                                }
                                else if(type == 'img_upload') {
                                    el  = colEl.find('img.img-upload');
                                    id  = el.attr('id');
                                    var arrSrc = el.attr('src').split('/');
                                    val = arrSrc[arrSrc.length-1].split('?')[0];
                                }
                                else if(type == 'currency') {
                                    el = colEl.find('input[type="text"]');
                                    id  = el.attr('id');
                                    val = system.parseAmount(el.val());
                                }
                                else if(type == 'date') {
                                    el = colEl.find('input[type="text"]');
                                    id  = el.attr('id');
                                    val = el.val();
                                }

                                row[id] = val;
                                obj.state += '"' + id + '"' + ':"' + val.toString() + '", ';
                            }
		                });
		                form.rows.push(row);
		            });
		            obj.forms.push(form);
		        }
	        });
	    }
	    processForm(this.el);
        return obj;
    };


    /************************************************************************************************
     * @function ItemDataBody.executeCallback
     * @description Execute a callback for a given callback-form
     * @param {string} callbackForm - the callback form class
     *
     */
    ItemDataBody.prototype.executeCallback = function(callbackForm) {
        if(this.callbacks != undefined) {
            if(this.callbacks[callbackForm] != undefined)
                this.callbacks[callbackForm]();
        }
    };


    /************************************************************************************************
     * @function ItemDataBody.renderInputObjects
     * @description Render input objects
     * @param {object} [parent] - the parent object (ItemDataBody.el if null)
     *
     */
    ItemDataBody.prototype.renderInputObjects = function(parent) {
        var itemData = this.itemData;
        var system   = itemData.pane.pane.formWizardTabPane.navItem.tab.sidebarMenuItem.sidebarMenu.sidebar.system;

        // initialize parent object
        if(parent == null)
            parent = this.el;
        else if(parent.length <= 0)
            parent = this.el;

        // ItemDataBody.prototype.renderInputObjects :: switchery_toggles
        parent.find("[data-init-plugin='switchery']").each(function() {
            var el      = $(this);
            var proceed = false;
            var nextEl  = el.next();
            if(nextEl != null) {
                if(!nextEl.hasClass('switchery'))
                    proceed = true;
            }
            else
                proceed = true;

            if(proceed) {
                new Switchery(el.get(0), {
                    color: (el.data("color") != null ? $.Pages.getColor(el.data("color")) : $.Pages.getColor('success')),
                    size: (el.data("size") != null ? el.data("size") : "default")
                });
            }
        });

        // ItemDataBody.prototype.renderInputObjects :: date_pickers
        var container = parent.find('.bootstrap-iso form');
        container     = container.length > 0 ? container.parent() : 'body';
        parent.find('input.date-picker').each(function(i) {
            var d  = $(this).val();
            var dp = $(this).datepicker({
                format          : 'MM d, yyyy',
                container       : container,
                todayHighlight  : true,
                autoclose       : true
            });

            if(d != '')
                dp.datepicker('setDate', new Date(d));

            var dataCallback = $(this).attr('data-callback');
            if (typeof dataCallback !== typeof undefined && dataCallback !== false) {
                dp.on('changeDate', function(e) {
                    setTimeout(function() {
                        itemData.body.executeCallback(dataCallback);
                    }, 1);
                });
            }
        });

        // ItemDataBody.prototype.renderInputObjects :: value_to_format
        parent.find('.value-to-format').each(function() {
            var value  = $(this).text();
            var format = $(this).attr('data-format');
            if(format == 'currency')
                value = '&#8369; ' + system.parseCurrency(value);
            $(this).html(value);
        });

    };


/****************************************************************************************************
 * @constructor Quickview extends El
 * @param {System} system [the parent system object]
 * @param {string} selector [the element selector]
 *
 */
function Quickview(system, selector) {
    this.cl = 'quickview';
    El.apply(this, arguments);
    if(this.isConstructed()) {
        this.header = new QuickviewHeader(this, '.quickview-header');
        this.toggle = new QuickviewToggle(this, '.quickview-toggle');
        this.body   = new QuickviewBody(this, '.quickview-body');
    }
} Quickview.prototype = Object.create(El.prototype);


    /************************************************************************************************
     * @function Quickview.open
     * @description Show the Quickview element
     * @param {object} options [quickview configurations]
     *
     */
    Quickview.prototype.open = function(options) {
        if(!this.el.hasClass('open')) {
            var quickview = this;
            var h1 = "";
            var h2 = "";

            var target = null;
            var isOverwritingContent = true;

            if(options.mode == 'search_list' || options.mode == 'search_item' || options.mode == 'search_dropdown') {
                // Quickview.open() :: Cache variables from the options object
                target = options.target;

                // Quickview.open() :: Write quickview title
                if(options.mode == 'search_list')
                    quickview.header.writeTitle('Search: ' + target.pane.pane.formWizardTabPane.navItem.tab.title);
                else if(options.mode == 'search_item' || options.mode == 'search_dropdown')
                    quickview.header.writeTitle('Search: ' + target.find('label').text());

                // Quickview.open() :: Decide to overwrite content or not
                if(quickview.options != undefined) {
                    if(quickview.options.mode == options.mode) {
                        if(options.mode == 'search_list') {
                            if (quickview.options.target.pane.pane.formWizardTabPane.navItem.tab.title == target.pane.pane.formWizardTabPane.navItem.tab.title)
                                isOverwritingContent = false;
                        }
                        else if(options.mode == 'search_item' || options.mode == 'search_dropdown') {
                            if (quickview.options.target.attr('data-model') == target.attr('data-model'))
                                isOverwritingContent = false;
                        }
                    }
                }

                // Quickview.open() :: Assign new options object
                quickview.options = options;

                // Quickview.open() :: If isOverwritingContent
                if(isOverwritingContent) {
                    h1 += "<div class='input-group' style='padding: 2px'>";
                        h1 += "<input type='text' class='txt-search form-control' style='background: #fff'>";
                        h1 += "<div class='input-group-btn'>";
                            h1 += "<button class='btn btn-success btn-search'><span class='fas fa-search'></span><span class='lbl'></button>";
                        h1 += "</div>";
                    h1 += "</div>";

                    quickview.body.writeContentTitle(h1);
                    quickview.body.writeContentBody(h2);
                    quickview.body.btnSearch = new Button(quickview.body, '.btn-search', 'btn-search');
                    quickview.body.txtSearch = new TextBox(quickview.body, '.txt-search', 'txt-search');

                    // Quickview.open() :: Event :: Search Button Click
                    quickview.body.btnSearch.el.on('click', function() {
                        quickview.__search();
                    });

                    // Quickview.open() :: Event :: Search TextBox Enter
                    var T;
                    quickview.body.txtSearch.el.on('keyup', function(e) {
                        if(quickview.options.mode == 'search_dropdown') {
                            if(T != null)
                                clearTimeout(T);
                            T = setTimeout(function() {
                                quickview.__search();
                            }, 640);
                        }
                        else {
                            if(e.keyCode == 13)
                                quickview.__search();
                        }
                    });

                    // Quickview.open() :: Auto-search when 'search_dropdown'
                    if(options.mode == 'search_dropdown') {
                        quickview.__search();
                    }
                }
                // Quickview.open() :: If NOT isOverwritingContent
                else {
                    if(options.key !== '') {
                        var previousItem = quickview.body.el.find('.list-item[data-id="' + options.key + '"]');
                        if(previousItem.length > 0) {
                            quickview.body.el.find('.list-item.active').removeClass('active');
                            previousItem.addClass('active');
                        }
                    }
                }

                // Quickview.open() :: Focus on TextBox
                quickview.body.txtSearch.focus(true);
            }

            else if(options.mode == 'upload_img') {
                // Quickview.open() :: Cache variables from the options object
                target = options.target;
                var isReadOnly = target.attr('readonly') !== undefined;

                // Quickview.open() :: Extract the filename from the target
                var arrSrc = target.attr('src').split('/');
                target.file = arrSrc[arrSrc.length-1].split('?')[0];
                if(target.file == target.attr('data-default'))
                    target.file = '';

                // Quickview.open() :: Write quickview title
                if(options.mode == 'upload_img')
                    quickview.header.writeTitle((!isReadOnly ? 'Upload' : 'View') + ': ' + target.attr('alt'));

                // Quickview.open() :: Decide to overwrite content or not
                if(quickview.options != undefined) {
                    if(quickview.options.mode == options.mode) {
                        if(options.mode == 'upload_img') {
                            if (quickview.options.target.attr('src') == target.attr('src'))
                                isOverwritingContent = false;
                        }
                    }
                }

                // Quickview.open() :: Assign new options object
                quickview.options = options;

                // Quickview.open() :: If isOverwritingContent
                h1 += "<form method='post' action='" + quickview.system.root + quickview.system.models + "__init.php' enctype='multipart/form-data'>";
                    h1 += "<input type='file' id='upload_file' name='upload_file' class='hidden' accept='image/*'>";
                    h1 += "<input type='hidden' id='dir' name='dir' value='" + target.attr('data-dir') + "'>";
                    h1 += "<input type='hidden' id='type' name='type' value='" + target.attr('data-ftype') + "'>";
                h1 += "</form>";
                h1 += "<div class='input-group' style='padding: 2px; width: 100%'>";
                    h1 += "<input type='text' class='txt-file form-control text-center' style='background: #fff; width: 80%' value='" + target.file + "' readonly disabled>";
                    h1 += "<div class='input-group-btn' style='width: 20%'>";
                        h1 += "<label for='" + (!isReadOnly ? "upload_file" : "") + "' class='btn btn-success btn-browse no-padding-left no-padding-right' style='width: 100%'><span class='fas fa-ellipsis-h'></span><span class='lbl'></span></label>";
                    h1 += "</div>";
                h1 += "</div>";
                h1 += "<div class='btn-group' style='padding: 2px; width: 100%'>";
                    h1 += "<button class='btn btn-default btn-cancel' style='width: 40%'"+ (isReadOnly ? " disabled" : "") + "><span class='fas fa-times-circle fa-fw'></span><span class='lbl'></span></button>";
                    h1 += "<button class='btn btn-default btn-clear' style='width: 40%'"+ (isReadOnly ? " disabled" : "") + "><span class='fas fa-trash fa-fw'></span><span class='lbl'></span></button>";
                    h1 += "<button class='btn btn-success btn-ok no-padding' style='width: 20%'"+ (isReadOnly ? " disabled" : "") + "><span class='fas fa-check fa-fw'></span><span class='lbl'></span></button>";
                h1 += "</div>";

                h2 += "<div style='padding: 2px 3px 2px 2px'>";
                    h2 += "<div style='position: relative; width: 100%'>";
                        h2 += "<img class='img' style='width: 100%' src='" + target.attr('src') + "' alt='" + target.attr('src') + "'>";
                        h2 += "<div class='overlay hidden' style='width: 100%; height: 100%; position: absolute; z-index: 1; opacity: 0.5; background: #000; left: 0; top: 0'></div>";
                        h2 += "<div class='loader hidden' style='width: 100%; height: 100%; position: absolute; z-index: 2; left: 0; top: 0;'>";
                            h2 += "<table class='w-100 h-100'><tr><td align='center' class='text-white'>";
                                h2 += "<p class='text-montserrat text-bold'><span class='fas fa-circle-notch fa-spin' style='font-size: 1.5em'></span><br><span class='status'></span></p>";
                            h2 += "</td></tr></table>";
                        h2 += "</div>";
                    h2 += "</div>";
                h2 += "</div>";

                quickview.body.writeContentTitle(h1);
                quickview.body.writeContentBody(h2);
                quickview.body.file      = new FileBox(quickview.body, '#upload_file', 'upload_file');
                quickview.body.txtFile   = new TextBox(quickview.body, '.txt-file', 'txt-file');
                quickview.body.btnBrowse = new Button(quickview.body, '.btn-browse', 'btn-browse');
                quickview.body.btnCancel = new Button(quickview.body, '.btn-cancel', 'btn-cancel');
                quickview.body.btnClear  = new Button(quickview.body, '.btn-clear', 'btn-clear');
                quickview.body.btnOk     = new Button(quickview.body, '.btn-ok', 'btn-ok');
                quickview.body.img       = new El(quickview.body, 'img.img');
                quickview.body.overlay   = new El(quickview.body, 'div.overlay');
                quickview.body.loader    = new El(quickview.body, 'div.loader');

                // Quickview.open() :: Event :: file Change
                quickview.body.file.el.on('change', function() {
                    // Quickview.open() :: Display upload status
                    function displayStatus(html) {
                        quickview.body.loader.el.find('.status').html(html);
                    }

                    // Quickview.open() :: Show upload loading
                    function showLoading() {
                        quickview.body.overlay.show();
                        quickview.body.loader.show();
                        quickview.body.btnBrowse.disable({showSpinner: true});
                        quickview.body.btnBrowse.el.attr('for', '');
                        quickview.body.btnOk.disable({showSpinner: true});
                        quickview.body.btnClear.disable({showSpinner: false});
                        quickview.body.btnCancel.el.removeClass('btn-default');
                        quickview.body.btnCancel.el.addClass('btn-danger');
                    }

                    // Quickview.open() :: Hide upload loading
                    function hideLoading() {
                        quickview.body.overlay.hide();
                        quickview.body.loader.hide();
                        quickview.body.btnBrowse.enable();
                        quickview.body.btnBrowse.el.attr('for', quickview.body.file.el.attr('id'));
                        quickview.body.btnOk.enable();
                        quickview.body.btnClear.enable();
                        quickview.body.btnCancel.el.removeClass('btn-danger');
                        quickview.body.btnCancel.el.addClass('btn-default');
                    }

                    Pace.restart();
                    showLoading();
                    $(this).parent().ajaxForm({
                        beforeSend: function() {
                            displayStatus("TRANSFERRING <br>0%");
                        },
                        uploadProgress: function(event, position, total, percent) {
                            displayStatus("TRANSFERRING <br>" + percent.toString() + "%");
                            if(percent >= 100) {
                                displayStatus("UPLOADING<br>PLEASE WAIT...");
                            }
                        },
                        success: function(data) {
                            var response = JSON.parse(data);
                            if (response.error != '') {
                                quickview.system.messageDialog.show(response.error, '', function () {
                                    window.location.reload();
                                });
                            }
                            else {
                                if (response.success.message != '') {
                                    hideLoading();
                                    quickview.body.overlay.hide();
                                    quickview.body.loader.hide();
                                    quickview.system.messageDialog.show(response.success.message, response.success.sub_message);
                                }
                                else {
                                    var file = response.success.data;
                                    var src  = quickview.system.root + target.attr('data-dir') + '/' + file;
                                    if(src != '') {
                                        displayStatus("FETCHING FILE<br>PLEASE WAIT...");
                                        quickview.body.img.el.attr('src', src);
                                        quickview.body.img.el.off();
                                        quickview.body.img.el.on("load", function () {
                                            quickview.body.txtFile.el.val(file);
                                            hideLoading();
                                        });
                                    }
                                }
                            }
                        },
                        error: function(data) {
                            Pace.stop();
                            hideLoading();
                            quickview.system.messageDialog.show(
                                '<span class="text-danger">ERROR ' + data.status + ' ' + '(' + (data.status == 0 ? 'NO CONNECTION' : data.statusText) + ')' + '</span>',
                                '<b>UNABLE TO UPLOAD <span class="text-info">' + target.attr('alt') + '</span> TO </b>' + '<span class="text-primary">' + model + '</span>',
                                function() {
                                    window.location.reload();
                                }
                            );
                        }
                    }).submit();
                });

                // Quickview.open() :: Event :: btnClear Click
                quickview.body.btnClear.el.on('click', function() {
                    quickview.body.img.el.attr('src', quickview.system.root + target.attr('data-dir') + '/' + target.attr('data-default'));
                    quickview.body.img.el.off();
                    quickview.body.img.el.on("load", function () {
                        quickview.body.txtFile.el.val('');
                    });
                });

                // Quickview.open() :: Event :: btnOk Click
                quickview.body.btnOk.el.on('click', function() {
                    target.attr('src', quickview.body.img.el.attr('src'));
                    quickview.close();
                });
            }

            // Quickview.open() :: Append overlay
            this.system.el.append("<div id='quickview-overlay' class='hidden'></div>");
            var overlay = this.system.el.find('#quickview-overlay');
            overlay.hide();
            overlay.removeClass('hidden');
            overlay.fadeIn(240);

            // Quickview.open() :: Add .open class to el
            this.el.addClass('open');

            // Quickview.open() :: Adjust $('.quickview-content-body') height
            this.el.find('.quickview-content-body').css({
                'height' : (quickview.getHeight() - quickview.header.getHeight() - parseInt(quickview.el.find('.quickview-content-header').css('height'))).toString() + 'px'
            });
        }
    };


    /************************************************************************************************
     * @function Quickview.close
     * @description Hide the Quickview element
     * @param {function} [callback] [the callback function]
     *
     */
    Quickview.prototype.close = function(callback) {
        if(this.el.hasClass('open')) {

            // remove overlay
            var overlay = this.system.el.find('#quickview-overlay');
            overlay.fadeOut(240, function () {
                overlay.remove();
                if(callback != undefined) {
                    callback();
                }
            });

            // remove .open class to el
            this.el.removeClass('open');
        }
    };



/****************************************************************************************************
 * @constructor QuickviewHeader extends El
 * @param {Quickview} quickview [the parent Quickview object]
 * @param {string} selector [the element selector]
 *
 */
function QuickviewHeader(quickview, selector) {
    this.cl = 'quickviewHeader';
    El.apply(this, arguments);
} QuickviewHeader.prototype = Object.create(El.prototype);


    /************************************************************************************************
     * @function Quickview.writeTitle
     * @description Write to QuickviewHeader.el('.quickview-title') element
     * @param {string} html [the html to be written]
     *
     */
    QuickviewHeader.prototype.writeTitle = function(html) {
        var el = this.el.find('.quickview-title');
        if(el != undefined)
            el.html(html);
    };



/****************************************************************************************************
 * @constructor QuickviewToggle extends El
 * @param {Quickview} quickview [the parent Quickview object]
 * @param {string} selector [the element selector]
 *
 */
function QuickviewToggle(quickview, selector) {
    this.cl = 'quickviewToggle';
    El.apply(this, arguments);
    if(this.isConstructed()) {
        this.el.on('click', function() {
            quickview.close();
        });
    }
} QuickviewToggle.prototype = Object.create(El.prototype);



/****************************************************************************************************
 * @constructor QuickviewBody extends El
 * @param {Quickview} quickview [the parent Quickview object]
 * @param {string} selector [the element selector]
 *
 */
function QuickviewBody(quickview, selector) {
    this.cl = 'quickviewBody';
    El.apply(this, arguments);
} QuickviewBody.prototype = Object.create(El.prototype);


    /************************************************************************************************
     * @function QuickviewBody.writeContentTitle
     * @description Write to QuickviewBody.el('.quickview-content-header') element
     * @param {string} html [the html to be written]
     *
     */
    QuickviewBody.prototype.writeContentTitle = function(html) {
        var el = this.el.find('.quickview-content-header');
        if(el != undefined)
            el.html(html);
    };


    /************************************************************************************************
     * @function QuickviewBody.writeContentBody
     * @description Write to QuickviewBody.el('.quickview-content-body') element
     * @param {string} html [the html to be written]
     *
     */
    QuickviewBody.prototype.writeContentBody = function(html) {
        var el = this.el.find('.quickview-content-body');
        if(el != undefined)
            el.html(html);
    };


    /************************************************************************************************
     * @function QuickviewBody.showSearchLoading
     * @description Show the 'searching' loading message on the QuickviewBody element
     *
     */
    QuickviewBody.prototype.showSearchLoading = function() {
        var h = "";
        h += "<div class='card social-card share no-margin no-border w-100 card-wait'>";
            h += "<div class='card-header clearfix no-border no-border-radius overflow-hidden'>";
                h += "<h5 class='text-montserrat no-wrap'><span class='fas fa-circle-notch fa-spin'></span> SEARCHING</h5>";
                h += "<h6 class='no-wrap text-uppercase sub-title'>Please wait...</h6>";
            h += "</div>";
        h += "</div>";
        this.writeContentBody(h);
    };


    /************************************************************************************************
     * @function QuickviewBody.hideSearchLoading
     * @description Hide the loading message after from the QuickviewBody element
     *
     */
    QuickviewBody.prototype.hideSearchLoading = function() {
        var cardWait = this.el.find('.card-wait');
        if(cardWait.length > 0) {
            cardWait.remove();
        }
    };


    /************************************************************************************************
     * @function QuickviewBody.appendSearchResult
     * @description Append search result item to QuickviewBody.el('.quickview-content-body') element
     * @param {ListItem} listItem [the ListItem object to be appended]
     *
     */
    QuickviewBody.prototype.appendSearchResult = function(listItem) {
        // Quickview.appendSearchResult() :: Cache objects for future reference
        var quickview = this.quickview;
        var system    = quickview.system;
        var menuItem  = system.sidebar.menu.getActiveMenuItem();
        var tab       = menuItem.getActiveTab();
        var tabPane   = system.content.body.contentFormWizard.body.tabPanes[tab.index];
        var itemData  = tabPane.pane.paneRight.itemData;

        var el = this.el.find('.quickview-content-body');
        if(el != undefined) {
            el.append(listItem.getHTML({isForDialog: false}));
            listItem.el = el.find(listItem.selector);

            // QuickviewBody.appendSearchResult() :: Events :: Searched Item Click
            listItem.el.on('click', function(e) {
                e.preventDefault();

                function proceed() {
                    // QuickviewBody.appendSearchResult() :: Item Click :: Add .active class to this searched item
                    if(!$(this).hasClass('active')) {
                        $(this).parent().find('.active').removeClass('active');
                        $(this).addClass('active');
                    }

                    // QuickviewBody.appendSearchResult() :: Item Click :: {mode: 'search_list'}
                    if(quickview.options.mode == 'search_list') {
                        // QuickviewBody.appendSearchResult() :: Cache list and navItem
                        var list    = quickview.options.target;
                        var navItem = list.pane.pane.formWizardTabPane.navItem;

                        // QuickviewBody.appendSearchResult() :: Item Click :: Set tab activeItem
                        navItem.tab.activeItem = listItem.id;

                        // QuickviewBody.appendSearchResult() :: Item Click :: Empty listBody items
                        list.body.emptyItems();

                        // QuickviewBody.appendSearchResult() :: Item Click :: Append new list to listBody and close quickview
                        list.body.__list('append', function() {
                            if(window.innerWidth <= 767) {
                                setTimeout(function () {
                                    list.body.getActiveListItem().el.click();
                                }, 512);
                            }
                            else {
                                menuItem.href = listItem.href;
                                menuItem.updateHistory();
                            }
                        });

                        if(window.innerWidth <= 767)
                            quickview.close();
                    }

                    // QuickviewBody.appendSearchResult() :: Item Click :: {mode: 'search_item', 'search_dropdown'}
                    else if(quickview.options.mode == 'search_item' || quickview.options.mode == 'search_dropdown') {
                        // QuickviewBody.appendSearchResult() :: Item Click :: Clear related dropdowns
                        if(quickview.options.mode == 'search_dropdown') {
                            if (parseInt(quickview.options.target.attr('data-key')) != parseInt(listItem.id)) {
                                var parent = quickview.options.target;
                                while(true) {
                                    var child = itemData.body.el.find('[data-ref="' + parent.attr('data-href') + '"]');
                                    if(child.length <= 0)
                                        break;
                                    child.attr('data-key', 0);
                                    child.find('input').val('');
                                    parent = child;
                                }
                            }
                        }

                        quickview.options.target.attr('data-key', listItem.id);
                        var searchInput = quickview.options.target.find('input');
                        if(listItem.searchtitle != '')
                            searchInput.val(listItem.searchtitle);
                        else
                            searchInput.val(listItem.maintitle);

                        if(listItem.avatar != '') {
                            var imgAvatar = quickview.options.target.find('.img-avatar');
                            if(imgAvatar.length > 0)
                                imgAvatar.attr('src', listItem.avatar);
                        }

                        var dataCallback = quickview.options.target.attr('data-callback');
                        if (typeof dataCallback !== typeof undefined && dataCallback !== false) {
                            setTimeout(function() {
                                itemData.body.executeCallback(dataCallback);
                            }, 64);
                        }

                        // QuickviewBody.appendSearchResult() :: Item Click :: Sync related dropdowns
                        if(quickview.options.target.attr('data-sync') != undefined) {
                            var relatedSearchBoxes = itemData.body.el.find('div.input-group-search[data-sync="' + quickview.options.target.attr('data-sync') + '"]');
                            if(relatedSearchBoxes.length > 0) {
                                relatedSearchBoxes.each(function() {
                                    var relatedSearchInput = $(this).find('input');
                                    if(relatedSearchInput.attr('id') != searchInput.attr('id')) {
                                        $(this).attr('data-key', listItem.id);
                                        relatedSearchInput.val(listItem.maintitle);
                                        if(listItem.avatar != '') {
                                            imgAvatar = $(this).find('.img-avatar');
                                            if(imgAvatar.length > 0)
                                                imgAvatar.attr('src', listItem.avatar);
                                        }
                                    }
                                });
                            }
                        }

                        quickview.close();
                    }
                }

                if(quickview.options.mode == 'search_list') {
                    system.askToSave(function() {
                        proceed();
                    });
                }
                else
                    proceed();
            });

            // QuickviewBody.appendSearchResult() :: Activate current dropdown item
            if(quickview.options.mode == 'search_dropdown') {
                var listItemEl = quickview.body.el.find('.list-item[data-id="' + quickview.options.target.attr('data-key') + '"]');
                if(listItemEl.length > 0) {
                    quickview.body.el.find('.list-item.active').removeClass('active');
                    listItemEl.addClass('active');
                    listItemEl.parent().scrollTop(listItemEl.offset().top - listItemEl.parent().offset().top + listItemEl.parent().scrollTop());
                }
            }
        }
    };
