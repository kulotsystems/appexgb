/************************************************************************************************
 * @file select.js
 * @function ListItem.__select
 * @description Get the item data from the model and write them in the itemData.body
 *
 * @author Arvic S. Babol
 * @support Kulot Systems (kulotsystems@gmail.com)
 *
 */
ListItem.prototype.__select = function() {
    // ListItem.__select() :: Cache objects for future reference
    var listItem = this;
    var list     = this.listBody.list;
    var pane     = list.pane.pane;
    var navItem  = pane.formWizardTabPane.navItem;
    var tab      = navItem.tab;
    var itemData = pane.paneRight.itemData;
    var system   = tab.sidebarMenuItem.sidebarMenu.sidebar.system;
    var model    = system.root + system.models + tab.model + '.php';

	// ListItem.__select() :: Reset system.ajaxTimer
	if(system.ajaxTimer != null)
		clearTimeout(system.ajaxTimer);

	// ListItem.__select() :: Increment listBody.selectCTR
	list.body.selectCTR += 1;

	// ListItem.__select() :: Prepare AJAX data
    var ajaxData = {};
    ajaxData['select_' + tab.model.toLowerCase()] = listItem.id;
	ajaxData['counter'] = list.body.selectCTR;

    // ListItem.__select() :: Show loading message and initiate AJAX request!
    Pace.restart();
    itemData.body.el.html("<p class='no-margin text-montserrat text-bold'><span class='fa fa-info-circle text-info'></span> <span class='item-action-label'>" + (tab.hasControl('update') ? 'Editing' : 'Viewing') + "</span> <span class='text-success'>" + listItem.maintitle + "</span> under <span class='text-success'>" + tab.title + "</span>.</p>");
    itemData.body.showLoading();
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
                // ListItem.__select() :: Hide loading message
                itemData.body.hideLoading();

                // ListItem.__select() :: Process server response.success
                if (response.success.message != '')
                    system.messageDialog.show(response.success.message, response.success.sub_message);
                else {
					var activeListItem = list.body.getActiveListItem();
					if(activeListItem) {
						if(parseInt(activeListItem.id) == parseInt(response.success.data.item_id) && parseInt(response.success.data.counter) == parseInt(list.body.selectCTR)) {
							if(tab.hasControl('update'))
								itemData.body.el.append("<p class='text-montserrat text-bold no-margin padding-bottom-15 item-guide'>To save changes, press the <span class='text-success'><span class='fa fa-save'></span> SAVE</span> button above.</p>");
							else {
								var itemDate = listItem.el.find('.item-date');
								if(itemDate.length > 0)
									itemData.body.el.append("<p class='text-montserrat text-bold no-margin padding-bottom-15 item-guide'>" + itemDate.text() + "</p>");
							}

							// ListItem.__select() :: Prepare content object
							var o = {
								h : '', // {string} html to be appended on itemData.body.el element
								v : [], // {Array}  values to be written on inputs to avoid truncated content [[id: value], [id: value], ...],
								c : []  // {Array}  callback_forms
							};

							// ListItem.__select() :: Generate o.h and collect o.v
							function getForms(forms, parent) {
								for(var i=0; i<forms.length; i++) {
									var form = forms[i];
									o.h += "<div class='" + form.class + "'";
									if(form.param != undefined)
										o.h += " data-param='" + form.param + "'";
									if(form.id != undefined)
										o.h += " data-id='" + form.id + "'";
									if(form.style != undefined)
										o.h += " style='" + form.style + "'";
									if(form.hrefs != undefined)
										o.h += " data-hrefs='" + form.hrefs + "'";
									if(form.attrs != undefined)
										o.h += " data-attrs='" + form.attrs + "'";
									o.h += ">";
										var arrClass = form.class.split(' ');
										if(arrClass[0] == 'callback') {
											o.h += "<label class='callback-label'><span class='fas " + form.icon + " fa-fw'></span> <b class='text-montserrat'>" + form.label + "</b></label>";

											// Update itemData.body.callbacks
											itemData.body.callbacks[form.param] = function() {
												// Reset o object
												o = { h : '', v : [], c : [] };

												var callbackForm  = parent.find('.callback[data-param="' + form.param + '"]');
												var callbackBtn   = new Button(itemData.body, 'div[data-type="button-callback"] button[data-param="' + form.param + '"]', 'callback-btn');
												var callbackLabel = callbackForm.find('.callback-label');
												var callbackIcon  = callbackLabel.find('.fas');
												var model    = system.root + system.models + form.model + '.php';
												var ajaxData = {};
												ajaxData['callback_' + form.model.toLowerCase()] = form.param;
												ajaxData['value'] = form.value;
												ajaxData['vals']  = [];
												if(form.hrefs != undefined && form.attrs != undefined) {
													var hrefs = JSON.parse(form.hrefs);
													var attrs = JSON.parse(form.attrs);
													for(var x=0; x<hrefs.length; x++) {
														if(attrs[x] == 'value')
															ajaxData['vals'].push(itemData.body.el.find(hrefs[x]).val());
														else if(attrs[x] == 'data-key')
															ajaxData['vals'].push(itemData.body.el.find(hrefs[x]).parent().parent().attr('data-key'));
													}
												}

												system.cover.show();
												Pace.restart();
												itemData.header.disableButtons();
												callbackForm.css({'opacity':'0.5'});
												callbackIcon.removeClass(form.icon);
												callbackIcon.addClass('fa-circle-notch');
												callbackIcon.addClass('fa-spin');
												if(callbackBtn.isConstructed())
													callbackBtn.disable({showSpinner: true});
												$.ajax({
													type : 'POST',
													url  : model,
													data : ajaxData,
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
																var callbackForm = itemData.body.el.find('div.callback[data-id="' + response.success.data.id + '"]');
																if(callbackForm.length > 0) {
																	getForms(response.success.data.forms, callbackForm);
																}
															}
														}
														setTimeout(function() {
															system.cover.hide();
															Pace.stop();
															itemData.header.enableButtons();
															callbackForm.css({'opacity':'1'});
															callbackIcon.removeClass('fa-circle-notch');
															callbackIcon.removeClass('fa-spin');
															callbackIcon.addClass(form.icon);
															if(callbackBtn.isConstructed())
																callbackBtn.enable('fa-check');
														}, 1);
													},
													error: function(data) {
														Pace.stop();
														system.messageDialog.show(
															'<span class="text-danger">ERROR ' + data.status + ' ' + '(' + (data.status == 0 ? 'NO CONNECTION' : data.statusText) + ')' + '</span>',
															'<b>UNABLE TO EXECUTE CALLBACK <span class="text-info">' + form.param + '</span> FROM </b>' + '<span class="text-primary">' + model + '</span>',
															function() {
																window.location.reload();
															}
														);
													}
												});
											};

											// Push callback_form to o.c
											o.c.push(form.param);

											// Render callback forms
											getForms(form.form.forms);
										}
										else if(form.forms != undefined)
											getForms(form.forms);
										else {
											for(var j=0; j<form.rows.length; j++) {
												var row = form.rows[j];
												o.h += "<div class='row clearfix'>";
													for(var k=0; k<row.length; k++) {
														var col = row[k];
														o.h += "<div class='" + col.grid + "' data-type='" + col.type + "'>";
															/** DIV *********************************************************************************************/
															if(col.type == 'div') {
																o.h += "<div id='" + col.id + "'>" + col.value + "</div>";
															}

															/** LABEL *******************************************************************************************/
															else if(col.type == 'label') {
																o.h += "<br>";
																o.h += "<label id='" + col.id + "'><b class='text-montserrat'>" + col.value + "</b></label>";
															}

															/** BUTTON ******************************************************************************************/
															else if(col.type == 'button') {
																o.h += "<br>";
																o.h += "<button class='" + col.class + "' id='" + col.id + "'><span class='text-montserrat'>" + col.value + "</span></button>";
															}

															/** BUTTON CALLBACK *********************************************************************************/
															else if(col.type == 'button-callback') {
																o.h += "<button class='" + col.class + "' id='" + col.id + "' style='width: 100%; height: 100%; border-radius: 0' data-model='" + col.model + "' data-param='" + col.param + "'>";
																	o.h += "<span class='fas " + col.icon + " fa-fw'></span>";
																	o.h += "<span class='lbl text-montserrat'> " + col.value + "</span>";
																o.h += "</button>";
															}

															/** IMG UPLOAD **************************************************************************************/
															else if(col.type == 'img_upload') {
																o.h += "<div align='center' style='padding: 15px; background: #eee; border-radius: 2px'>";
																	o.h += "<div style='position: relative; width: " + col.size + "; height: " + col.size + "; overflow: hidden; border-radius: 2px'>";
																		o.h += "<img src='" + col.value + "' class='img-upload cursor-pointer' id='" + col.id + "' style='position: absolute; top: 0; left: 0; width: 100%;' alt='" + col.alt_text + "' data-dir='" + col.dir + "' data-default='" + col.default + "' data-ftype='" + col.f_type + "'";
                                                                        if(col.readonly)
                                                                            o.h += " readonly";
																		o.h += ">";
																	o.h += "</div>";
																o.h += "</div>";
															}

															/** CHECKBOX ****************************************************************************************/
															else if(col.type == 'checkbox') {
																o.h += "<div class='checkbox check-success checkbox-circle padding-bottom-15'>";
																	o.h += "<input type='checkbox' id='" + col.id + "' data-value='" + col.value + "'" + (col.value == '1' ? ' checked' : '') + " style='vertical-align: top'>";
																	o.h += "<label for='" + col.id + "'>";
																		o.h += col.label;
																		if(col.description != undefined)
																			o.h += col.description;
																	o.h += "</label>";
																o.h += "</div>";
															}

															/** SEARCHBOX ***************************************************************************************/
															else if(col.type == 'searchbox') {
																o.h += "<div class='form-group form-group-default input-group input-group-search";
																if(col.class != undefined)
																	o.h += " " + col.class;
																o.h += "' data-href='#" + col.id + "' data-key='" + col.key + "' data-model='" + col.model + "'";
																if(col.role != undefined) {
																	if(col.role != '')
																		o.h += " data-role='" + col.role + "'";
																}
																if(col.ref != undefined) {
																	if(col.ref != '')
																		o.h += " data-ref='" + col.ref + "'";
																}
																if(col.trigger_callback != undefined) {
																	if(col.trigger_callback != '')
																		o.h += " data-callback='" + col.trigger_callback + "'";
																}
																if(col.sync_num != undefined) {
																	o.h += " data-sync='" + col.sync_num + "'";
																}
																o.h += ">";
																	if(col.avatar != null) {
																		o.h += "<div class='input-group-addon bg-transparent h-c-50'>";
																			o.h += "<span class='thumbnail-wrapper d32 circular inline cursor-pointer'>";
																				o.h += "<img class='img-avatar' src='" + col.avatar + "' alt='[img]'>";
																			o.h += "</span>";
																		o.h += "</div>";
																	}
																	o.h += "<div class='form-input-group'>";
																		o.h += "<label class='inline cursor-pointer search-label' for='" + col.id + "'>" + col.label + "</label>";
																		o.h += "<input type='text' id='" + col.id + "' class='form-control text-bold' readonly>";
																		o.v.push([col.id, col.value]);
																	o.h += "</div>";
																	if(!col.disabled) {
																		o.h += "<div class='input-group-addon bg-transparent h-c-50'>";
																			o.h += "<button class='btn btn-default text-success btn-xs'>";
																				o.h += "<span class='fa fa-search fa-fw'></span>";
																			o.h += "</button>";
																		o.h += "</div>";
																	}
																o.h += "</div>";
															}

															/** LABEL BOX ***************************************************************************************/
															else if(col.type == 'labelbox') {
																o.h += "<div class='form-group form-group-default input-group input-group-label' data-key='" + col.key + "'>";
																	if(col.avatar != null) {
																		o.h += "<div class='input-group-addon bg-transparent h-c-50'>";
																			o.h += "<span class='thumbnail-wrapper d32 circular inline cursor-pointer'>";
																				o.h += "<img class='img-avatar' src='" + col.avatar + "' alt='[img]'>";
																			o.h += "</span>";
																		o.h += "</div>";
																		o.h += "<div class='form-input-group'>";
																			o.h += "<label class='inline' for='" + col.id + "'>" + col.label + "</label>";
																			o.h += "<small id='" + col.id + "' class='form-control text-bold'>" + col.value + "</small>";
																		o.h += "</div>";
																	}
																o.h += "</div>";
															}

															/** VERIFIER ****************************************************************************************/
															else if(col.type == 'verifier') {
																o.h += "<div class='form-group form-group-default input-group input-group-verifier' data-href='" + col.href + "' data-attr='" + col.attr + "' data-model='" + col.model + "' data-param='" + col.param + "'";
																if(col.data_id != undefined)
																	o.h += " data-id='" + col.data_id + "'";
																o.h += ">";
																	o.h += "<div class='form-input-group'>";
																		o.h += "<label class='inline' for='" + col.id + "'>" + col.label + "</label>";
																		o.h += "<input type='text' class='form-control monospace text-bold' id='" + col.id + "' readonly>";
																		o.v.push([col.id, col.value]);
																	o.h += "</div>"
																	o.h += "<div class='input-group-addon bg-transparent h-c-50'>";
																		o.h += "<button class='btn btn-default btn-xs cursor-pointer'>";
																			if(col.icon == undefined)
																				o.h += "<span class='fas fa-sync text-success'></span>";
																			else
																				o.h += "<span class='fas " + col.icon + " text-success'></span>";
																		o.h += "</button>";
																	o.h += "</div>";
																o.h += "</div>";
															}

															/** PASSWORD TOGGLE *********************************************************************************/
															else if(col.type == 'password_toggle') {
																o.h += "<div class='form-group form-group-default input-group input-group-password-toggle'>";
																	o.h += "<div class='form-input-group'>";
																		o.h += "<label class='inline' for='" + col.id + "'>" + col.label + "</label>";
																		o.h += "<input type='password' class='form-control monospace text-bold' id='" + col.id + "'>";
																		o.v.push([col.id, col.value]);
																	o.h += "</div>";
																	o.h += "<div class='input-group-addon bg-transparent h-c-50'>";
																		o.h += "<button class='btn btn-default btn-xs cursor-pointer'>";
																			o.h += "<span class='fas fa-eye'></span>";
																		o.h += "</button>";
																	o.h += "</div>";
																o.h += "</div>";
															}

															/** PERCENTAGE **************************************************************************************/
															else if(col.type == 'percentage') {
																o.h += "<div class='form-group form-group-default input-group input-group-percentage'>";
																	o.h += "<div class='form-input-group'>";
																		o.h += "<label class='inline' for='" + col.id + "'>" + col.label + "</label>";
																		o.h += "<input type='text' class='form-control txt-amount text-bold' id='" + col.id + "' style='font-size: 14px;'>";
																		o.v.push([col.id, col.value]);
																	o.h += "</div>";
																	o.h += "<div class='input-group-addon bg-transparent h-c-50'>";
																		o.h += "<table class='h-100 w-100'>";
																			o.h += "<tbody>";
																				o.h += "<tr>";
																					o.h += "<td valign='bottom'><span class='fa fa-percent'></span></td>";
																				o.h += "</tr>";
																			o.h += "</tbody>";
																		o.h += "</table>";
																	o.h += "</div>";
																o.h += "</div>";
															}

															/** CURRENCY *****************************************************************************************/
															else if(col.type == 'currency') {
																o.h += "<div class='form-group form-group-default input-group input-group-currency input-group-currency-lock'>";
																	o.h += "<div class='form-input-group'>";
																		o.h += "<label class='inline'>" + col.label + "</label>";
																		o.h += "<input type='text' class='form-control text-right text-bold";
																		var isInt = false;
																		if(col.is_int != undefined)
																			isInt = col.is_int;
																		o.h += isInt ? ' text-int' : '';
																		if(col.text_class != undefined)
																			o.h += " " + col.text_class;
																		if(col.class != undefined)
																			o.h += " " + col.class;
																		o.h += "' id='" + col.id + "'";
																		if(system.parseAmount(col.value) <= 0)
																			o.h += " style='opacity: 0.6'";
																		if(col.cell_row !=undefined)
																			o.h += " data-cell-row='" + col.cell_row + "'";
																		if(col.cell_col != undefined)
																			o.h += " data-cell-col='" + col.cell_col + "'";
																		if(col.value_init != undefined)
																			o.h += " data-value-init='" + col.value_init.toString() + "'";
																		if(col.readonly != undefined)
																			o.h += col.readonly ? " readonly" : "";
																		if(col.disabled != undefined)
																			o.h += col.disabled ? " disabled" : "";
																		o.h += ">";
																		o.v.push([col.id, system.parseCurrency(col.value, isInt)]);
																	o.h += "</div>";
																	var showSymbol = col.show_symbol != undefined ? col.show_symbol : true;
																	if(showSymbol) {
																		o.h += "<div class='input-group-addon bg-transparent h-c-50''><table class='h-100 w-100'><tr><td valign='bottom'><span class='";
																		if(col.text_class != undefined)
																			o.h += " " + col.text_class;
																		o.h += "'";
																		if(col.disabled != undefined) {
																			if(col.disabled)
																				o.h += " style='opacity: 0.5'";
																			else
																				o.h += " style='opacity: 0.8'";
																		}
																		o.h += ">&#8369;</span></td></tr></table></div>";
																	}
																o.h += "</div>";
															}

															/** DATE *********************************************************************************************/
															else if(col.type == 'date') {
																o.h += "<div class='form-group form-group-default form-group-date";
																if(col.class != undefined)
																	o.h += " " + col.class;
																o.h += "'>";
																	o.h += "<label class='inline' for='" + col.id + "'>" + col.label + "</label>";
																	o.h += "<input type='text' class='form-control date-opt date-picker cursor-pointer text-bold' id='" + col.id + "'";
																	if(col.trigger_callback != undefined)
																		o.h += " data-callback='" + col.trigger_callback + "'"
																	o.h += " readonly>";
																	o.v.push([col.id, col.value]);
																o.h += "</div>";
															}

															else {
																o.h += "<div class='form-group form-group-default";
																if(col.type == 'toggle')
																	o.h += " input-group";
																if(col.required)
																	o.h += " required";
																o.h += "'>";

																	/** TEXTBOX *****************************************************************************************/
																	if(col.type == 'text' || col.type == 'hidden' ||  col.type == 'textarea' || col.type == 'number' || col.type == 'time') {
																		o.h += "<label for='" + col.id + "'>" + col.label + "</label>";
																		if(col.type == 'textarea') {
																			o.h += "<textarea id='" + col.id + "' class='form-control'";
																			if(col.disabled != undefined)
																				o.h += (col.disabled ? ' disabled' : '');
																			if(col.readonly != undefined)
																				o.h += (col.readonly ? ' readonly' : '');
																			o.h += ">";
																			o.h += "</textarea>";
																		}
																		else {
																			o.h += "<input type='" + col.type + "' id='" + col.id + "' class='form-control text-bold";
																			if(col.class != undefined)
																				o.h += " " + col.class;
																			if(col.text_class != undefined)
																				o.h += " " + col.text_class;
																			o.h += "'";
																			if(col.pholder != undefined)
																				o.h += " placeholder='" + col.pholder + "'";
																			if(col.disabled != undefined)
																				o.h += (col.disabled ? ' disabled' : '');
																			if(col.readonly != undefined)
																				o.h += (col.readonly ? ' readonly' : '');
																			o.h += ">";
																		}
																		o.v.push([col.id, col.value]);
																	}

																	/** SELECT ******************************************************************************************/
																	else if(col.type == 'select') {
																		o.h += "<label for='" + col.id + "'>" + col.label + "</label>";
																		o.h += "<select id='" + col.id + "' class='form-control text-bold'";
																		if(col.details != undefined) {
																			if(col.details != '')
																				o.h += " data-details='" + col.details + "'";
																		}
																		o.h += ">";
																		for(var x=0; x<col.options.length; x++) {
																			var option = col.options[x];
																			o.h += "<option value='" + option.value + "'";
																			if(option.detail != undefined)
																				o.h += " data-detail='" + option.detail.replace("'", "\'") + "'";
																			if(option.selected)
																				o.h += " selected";
																			o.h += ">";
																			o.h += option.label;
																			o.h += "</option>";
																		}
																		o.h += "</select>";
																	}

																	/** TOGGLE ******************************************************************************************/
																	else if(col.type == 'toggle') {
																		o.h += "<div class='form-input-group'>";
																			o.h += "<label class='inline' for='" + col.id + "'>" + col.label + "</label>";
																		o.h += "</div>";
																		o.h += "<div class='input-group-addon bg-transparent h-c-50'>";
																			o.h += " <input type='checkbox' id='" + col.id + "' data-init-plugin='switchery' data-size='small' data-color='success'" + (col.value == 1 ? ' checked' : '');
																			if(col.disabled != null)
																				o.h += (col.disabled ? ' disabled' : '');
																			o.h += ">";
																		o.h += "</div>";
																	}
																o.h += "</div>";
															}
														o.h += "</div>";
													}
												o.h += "</div>";
											}
										}
									o.h += "</div>";
								}

								if(parent != undefined) {
									// ListItem.__select() :: Remove previous forms
									parent.find('> div.form, > div.div').each(function() {
										$(this).remove();
									});

									// ListItem.__select() :: Append o.h and assign o.v
									parent.append(o.h);
									for (var i = 0; i < o.v.length; i++) {
										parent.find('#' + o.v[i][0]).val(o.v[i][1]);
									}

									// ListItem.select() :: Render switchery toggles
									itemData.body.renderInputObjects(parent);

									var activeNavItem = system.content.body.contentFormWizard.header.getActiveNavItem();
									if(activeNavItem) {
										if(activeNavItem.tab.title == 'EMPLOYEES') {
											function getFingerprintEnrollment() {
												ajaxData = {};
												ajaxData['callback_' + activeNavItem.tab.model.toLowerCase()] = 'get-fingerprint-enrollment';
												ajaxData['value'] = listItem.id;
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
															var citizenID = response.success.data.citizen_id;
															var fingers   = response.success.data.fingers;
															var listItem2 = list.body.getActiveListItem();
															if(listItem2) {
																if(listItem2.id == citizenID) {
																	for(var i=0; i<fingers.length; i++) {
																		var inputGroupVerifier = itemData.body.el.find('.input-group-verifier[data-id="' + citizenID + '|' + fingers[i].id + '"]');
																		if(inputGroupVerifier.length > 0) {
																			inputGroupVerifier.find('input').val(fingers[i].status);
																		}
																	}
																}
															}
															system.ajaxTimer = setTimeout(function() {
																getFingerprintEnrollment();
															}, 1000);
														}
													},
													error: function(data) {
														system.messageDialog.show(
															'<span class="text-danger">ERROR ' + data.status + ' ' + '(' + (data.status == 0 ? 'NO CONNECTION' : data.statusText) + ')' + '</span>',
															'<b>UNABLE TO GET FINGERPRRINT ENROLLMENT FROM </b> <span class="text-primary">' + model + '</span>',
															function() {
																window.location.reload();
															}
														);
													}
												});

											}
											setTimeout(function() {
												getFingerprintEnrollment();
											}, 1);
										}
									}
								}

								// ListItem.__select() :: Focus on .txt-find-in-page
								itemData.header.txtFindInPage.focus();

								// ListItem.__select() :: Store itemDataBody formState
								itemData.body.formState = itemData.body.getFormData().state;
							}

							// ListItem.__select() :: Get forms from server response
							getForms(response.success.data.item_data, itemData.body.el);
                		}
                	}
                }
            }
        },
        error: function(data) {
            Pace.stop();
            system.messageDialog.show(
                '<span class="text-danger">ERROR ' + data.status + ' ' + '(' + (data.status == 0 ? 'NO CONNECTION' : data.statusText) + ')' + '</span>',
                '<b>UNABLE TO SELECT <span class="text-info">' + tab.title + ' (' + listItem.maintitle + ')' + '</span> FROM </b>' + '<span class="text-primary">' + model + '</span>',
                function() {
                    window.location.reload();
                }
            );
        }
    });

    // ListItem.__select() :: Mobile view interaction
    if(window.innerWidth <= 767 && listItem.options.isItemClicked) {
        pane.hidePane('left');
        pane.showPane('right');
        pane.formWizardTabPane.slide('left');
    }
};
