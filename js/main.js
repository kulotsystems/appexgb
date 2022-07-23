/**
 * MAIN METHOD
 *
 */

// ITEM DATA BODY EVENTS
ItemDataBody.prototype.finishDelegates = function() {
    var itemDataBody = this;
	var system       = itemDataBody.itemData.pane.pane.formWizardTabPane.navItem.tab.sidebarMenuItem.sidebarMenu.sidebar.system;

	// ItemDataBody :: Delegate CLICK :: span.btn-add-location
	itemDataBody.el.delegate('span.btn-add-location', 'click', function() {
		// cache elements
		var parentForm = itemDataBody.el.find($(this).attr('data-ref'));
		var formBreak    = parentForm.find('>.form-group-break.hidden');
		var formAttached = parentForm.find('>.form-group-attached.hidden');
		var formIndex    = parentForm.find('>.form-group-attached').length.toString();

		// create locations form
		var h = "";
		h += "<div class='form form-group-break'>";
			h += formBreak.html();
		h += "</div>";
		h += "<div class='form form-group-attached'>";
			h += formAttached.html();
		h += "</div>";
		var form = $("<div>" + h + "</div>");

		// modify form href and ref attributes
		form.find('.n-location').text(formIndex);
		var inputGroupB = form.find('.input-group-search[data-model="PhBarangay"]');
		var inputGroupM = form.find('.input-group-search[data-model="PhMuncity"]');
		var inputGroupP = form.find('.input-group-search[data-model="PhProvince"]');
		var inputGroupR = form.find('.input-group-search[data-model="PhRegion"]');
		inputGroupB.attr('data-href', '#srch-barangay-new-' + formIndex);
		inputGroupM.attr('data-href', '#srch-muncity-new-'  + formIndex);
		inputGroupP.attr('data-href', '#srch-province-new-' + formIndex);
		inputGroupR.attr('data-href', '#srch-region-new-'   + formIndex);
		inputGroupB.attr('data-ref' , '#srch-muncity-new-'  + formIndex);
		inputGroupM.attr('data-ref' , '#srch-province-new-' + formIndex);
		inputGroupP.attr('data-ref' , '#srch-region-new-'   + formIndex);

		// append form to parentForm and copy the new input values
		parentForm.append(form.html());
		form = parentForm.find('>.form-group-attached:last-child');
		form.find('.input-group-search[data-model="PhBarangay"] input').val(formAttached.find('.input-group-search[data-model="PhBarangay"] input').val());
		form.find('.input-group-search[data-model="PhMuncity"] input').val(formAttached.find('.input-group-search[data-model="PhMuncity"] input').val());
		form.find('.input-group-search[data-model="PhProvince"] input').val(formAttached.find('.input-group-search[data-model="PhProvince"] input').val());
		form.find('.input-group-search[data-model="PhRegion"] input').val(formAttached.find('.input-group-search[data-model="PhRegion"] input').val());

		// render input objects in parentForm
		itemDataBody.renderInputObjects(parentForm);
	});

	// ItemDataBody :: Delegate CLICK :: span.btn-add-restructure
	itemDataBody.el.delegate('span.btn-add-restructure', 'click', function() {
		// cache elements
		var parentForm = itemDataBody.el.find($(this).attr('data-ref'));
		var formBreak    = parentForm.find('>.form-group-break.hidden');
		var formAttached = parentForm.find('>.form-group-attached.hidden');
		var formIndex    = parentForm.find('>.form-group-attached').length.toString();

		// create restructure form
		var h = "";
		h += "<div class='form form-group-break'>";
		h += formBreak.html();
		h += "</div>";
		h += "<div class='form form-group-attached'>";
		h += formAttached.html();
		h += "</div>";
		var form = $("<div>" + h + "</div>");

		// modify form count
		form.find('.n-restructure').text(formIndex);

		// append form to parentForm and copy the new input values
		parentForm.append(form.html());
		form = parentForm.find('>.form-group-attached:last-child');
		form.find('.input-group-search[data-model="Area"] input').val(formAttached.find('.input-group-search[data-model="Area"] input').val());
		form.find('.input-group-search[data-model="Offer"] input').val(formAttached.find('.input-group-search[data-model="Offer"] input').val());
		form.find('input.date-picker').val(formAttached.find('input.date-picker').val());

		// render input objects in parentForm
        itemDataBody.renderInputObjects(parentForm);
	});

	// ItemDataBody :: Delegate CLICK :: span.btn-remove-location
	itemDataBody.el.delegate('span.btn-remove-location', 'click', function() {
		// cache elements
		var formBreak     = $(this).parent().parent().parent().parent().parent();
		var formLocations = formBreak.next();
		var parentForm    = formBreak.parent();

		// remove elements
		formLocations.fadeOut(511, function() {
			formLocations.remove();
		});
		formBreak.fadeOut(512, function() {
			formBreak.remove();

			// fix .n-location labels
			var i = 0;
			parentForm.find('.n-location').each(function() {
				var n = parseInt($(this).text());
				if(n > 0) {
					i += 1;
					$(this).text(i.toString());
				}
			});
		});
	});

	// ItemDataBody :: Delegate CLICK :: span.btn-remove-restructure
	itemDataBody.el.delegate('span.btn-remove-restructure', 'click', function() {
		// cache elements
		var formBreak     = $(this).parent().parent().parent().parent().parent();
		var formRestructure = formBreak.next();
		var parentForm    = formBreak.parent();

		// remove elements
		formRestructure.fadeOut(511, function() {
			formRestructure.remove();
		});
		formBreak.fadeOut(512, function() {
			formBreak.remove();

			// fix .n-location labels
			var i = 0;
			parentForm.find('.n-restructure').each(function() {
				var n = parseInt($(this).text());
				if(n > 0) {
					i += 1;
					$(this).text(i.toString());
				}
			});
		});
	});

	// ItemDataBody :: Delegate CLICK :: .button-callback button
	itemDataBody.el.delegate('div[data-type="button-callback"] button', 'click', function() {
		itemDataBody.executeCallback($(this).attr('data-param'));
	});


	// ItemDataBody :: UX Improvements
	function applyActiveBG(el) {
		if(!el.hasClass('bg-active'))
			el.addClass('bg-active');
	}
	function removeActiveBG(el) {
		if(el.hasClass('bg-active'))
			el.removeClass('bg-active');
	}

	// ItemDataBody :: Delegate MOUSEOVER :: .form-group-hover-top
	itemDataBody.el.delegate('.form-group-hover-top', 'mouseover', function() {
		applyActiveBG($(this));
		applyActiveBG($(this).next());
	});

	// ItemDataBody :: Delegate MOUSEOUT :: .form-group-hover-top
	itemDataBody.el.delegate('.form-group-hover-top', 'mouseout', function() {
		removeActiveBG($(this));
		removeActiveBG($(this).next());
	});

	// ItemDataBody :: Delegate MOUSEOVER :: .form-group-hover-bottom
	itemDataBody.el.delegate('.form-group-hover-bottom', 'mouseover', function() {
		applyActiveBG($(this));
		applyActiveBG($(this).prev());
	});

	// ItemDataBody :: Delegate MOUSEOUT :: .form-group-hover-bottom
	itemDataBody.el.delegate('.form-group-hover-bottom', 'mouseout', function() {
		removeActiveBG($(this));
		removeActiveBG($(this).prev());
	});

	// ItemDataBody :: Delegate KEYUP :: input[data-cell-row][data-cell-col]
	itemDataBody.el.delegate('input[data-cell-row][data-cell-col]', 'keyup', function(e) {
		var cell = $(this);
		var key  = e.keyCode;
		if(key >= 37 && key <= 40) {
			var row  = parseInt(cell.attr('data-cell-row'));
			var col  = parseInt(cell.attr('data-cell-col'));
			var rowTarget = row;
			var colTarget = col;

			if (key == 37) {      // left
				if(system.isTextSelected(cell[0]) || cell[0].selectionStart <= 0)
					colTarget = col - 1;
			}
			else if (key == 38) { // up
				rowTarget = row - 1;
				var scrollPos = itemDataBody.el.scrollTop() - 108;
				if(scrollPos <= 0)
					scrollPos = 0;
				itemDataBody.el.scrollTop(scrollPos);
			}
			else if (key == 39) { // right
				if(system.isTextSelected(cell[0]) || cell[0].selectionStart >= cell[0].value.length)
					colTarget = col + 1;
			}
			else if (key == 40) { // down
				rowTarget = row  + 1;
				var scrollPos = itemDataBody.el.scrollTop() + 108;
				if(scrollPos >=  itemDataBody.el[0].scrollHeight)
					scrollPos = itemDataBody.el[0].scrollHeight;
				itemDataBody.el.scrollTop(scrollPos);
			}

			if(rowTarget != row || colTarget != col) {
				var target = itemDataBody.el.find('input[data-cell-row="' + rowTarget.toString() + '"][data-cell-col="' + colTarget.toString() + '"]');
				if(target.length > 0) {
					target.select();
				}
			}
		}
	});

	// ItemDataBody :: Delegate FOCUS :: input[data-cell-row][data-cell-col]
	itemDataBody.el.delegate('input[data-cell-row][data-cell-col]', 'focus', function(e) {
		var inputGroup = $(this).parent();
		var parentForm = inputGroup.parent().parent().parent().parent();
		if(inputGroup.length > 0 && parentForm.length > 0) {
			parentForm.parent().find('.form-group-hover.bg-active').removeClass('bg-active');
			if(!inputGroup.hasClass('focussed'))
				inputGroup.addClass('focussed');
			if(!parentForm.hasClass('bg-active')) {
				parentForm.addClass('bg-active');
				parentForm.prev().addClass('bg-active');
			}
		}
	});

	// ItemDataBody :: Delegate BLUR :: input.daily-collection
	itemDataBody.el.delegate('input.daily-collection', 'blur', function(e) {
		var txt   = $(this);
		var row   = txt.attr('data-cell-row');
		var col   = txt.attr('data-cell-col');
		var total = 0;
		itemDataBody.el.find('input.daily-collection[data-cell-row="' + row + '"]').each(function() {
			total += system.parseAmount($(this).val());
		});
		var txtTotal    = itemDataBody.el.find('input.total-daily-collection[data-cell-row="' + row + '"]');
		txtTotal.val(system.parseCurrency(total.toString(), true));

		var txtBalance  = itemDataBody.el.find('input.current-balance[data-cell-row="' + row + '"]');
		var balanceInit = system.parseAmount(txtBalance.attr('data-value-init'));
		var balanceFin  = balanceInit - total;
		txtBalance.val(system.parseCurrency(balanceFin.toString(), true));

		// SUMMARY :: SubTotal
		var subTotal = 0;
		itemDataBody.el.find('input.daily-collection[data-cell-col="' + col + '"]').each(function() {
			subTotal += system.parseAmount($(this).val());
		});
		var txtSubTotal = itemDataBody.el.find('input#txt-subtotal-' + col);
		txtSubTotal.val(system.parseCurrency(subTotal.toString(), true));

		// SUMMARY :: TotalTotal
		var totalTotal = 0;
		itemDataBody.el.find('input.txt-subtotal').each(function() {
			totalTotal += system.parseAmount($(this).val());
		});
		var txtTotalTotal = itemDataBody.el.find('input#txt-total-total');
		txtTotalTotal.val(system.parseCurrency(totalTotal.toString(), true));

		// SUMMARY :: TotalBalance
		var txtTotalBalance  = itemDataBody.el.find('input#txt-total-balance');
		balanceInit = system.parseAmount(txtTotalBalance.attr('data-value-init'));
		balanceFin  = balanceInit - totalTotal;
		txtTotalBalance.val(system.parseCurrency(balanceFin.toString(), true));
	});
};


$(function() {
    var system = new System($('body'));
    system.sidebar.menu.activateCurrentMenuItem();

    window.onresize = function() {
        system.positionElements({});
    };

    // sidebar menu item prevent default
    system.el.find('.sidebar-menu-item > a').on('click', function(e) {
        e.preventDefault();
    });

    // EVENT: BROWSER BACK OR FORWARD BUTTON
    window.onpopstate = function(event) {
        var page = 'home';
        var tab  =  1;
        var item = -1;
        var location = document.location.toString().split('?');
        if(location.length > 1) {
            var parameters = location[1].split('=');
            var pages      = parameters[0].split('-');
            if(pages.length > 1) {
                page = pages[0];
                tab  = parseInt(pages[1]);
                if(pages.length > 2) {
                    item = parseInt(pages[2]);
                }
            }
        }

        var sidebarMenuItem = system.sidebar.menu.searchMenuItem('?'+page);
        if(sidebarMenuItem) {
            // deactivate previous tab
            var activeTab = sidebarMenuItem.getActiveTab();
            if(activeTab)
                activeTab.isActive = false;

            // activate new tab
            activeTab = sidebarMenuItem.searchTab(tab);
            if(activeTab) {
                activeTab.isActive   = true;
                activeTab.activeItem = item;
            }

            // activate the sidebarMenuItem
            sidebarMenuItem.activate({isMenuClicked: false});
        }
        else
            window.location.reload();
    };

    // EVENT: USER LOGOUT
    $('#btnShowLogoutPrompt').on('click', function() {
        system.confirmDialog.show("CONFIRM LOGOUT", "<span style='font-size: 1.2em'>Do you really want to logout?</span>", function() {
            Pace.restart();
            $.ajax({
                type: 'POST',
                url: system.index + 'php/ajax/login.php',
                data: {
                    logout_user: 1
                },
                success: function(data) {
                    var respose = JSON.parse(data);
                    if(respose.success == "1") {
                        system.confirmDialog.hide(function() {
                            system.el.fadeOut();
                            window.open(system.index + 'admin/index.php?logout', '_self');
                        });
                    }
                    Pace.stop();
                },
                error: function(data) {
                    system.confirmDialog.hide(function() {
                        Pace.stop();
                        system.messageDialog.show(
                            '<span class="text-danger">ERROR ' + data.status + ' ' + '(' + (data.status == 0 ? 'NO CONNECTION' : data.statusText) + ')' + '</span>',
                            '<b>UNABLE TO LOGOUT FROM </b>' + '<span class="text-primary">' + model + '</span>',
                            function () {
                                window.location.reload();
                            }
                        );
                    });
                }
            });
        });
    });
});


// GENERATE DASHBOARD
System.prototype.generateDashboard = function() {
    var h = "";

    return h;
};


// UPDATE DASHBOARD
System.prototype.updateDashboard = function(model) {
    var system = this;
};
