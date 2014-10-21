jQuery(document).ready(function(jQuery) {

	var usedPlugins = [];
	var cdnjsSelected = jQuery('#cdnjs-selected');
	var cdnjsScripts = jQuery('#cdnjs_settings_scripts');
	cdnjsSelected.find('.index').each(function() {
		usedPlugins.push(jQuery(this).find('input.plugin_name').val());
	});

	function remoteAjaxAutoComplete(element, url) {

		jQuery(element).select2({
			width:              "55%",
			id:                 'name',
			multiple:           false,
			placeholder:        cdnjs_text.search_placeholder,
			minimumInputLength: 2,
			ajax:               {
				url:      url,
				dataType: 'json',
				//cache: true,
				data:     function(term, page) {
					return {
						search:     term,
						page_limit: 20
					}
				},
				results:  function(data, page) {
					if(usedPlugins) {
						for(var j = 0; j < data.results.length; j++) {
							for(var i = 0; i < usedPlugins.length; i++) {
								if(usedPlugins[i] === data.results[j].name) {
									data.results[j].disabled = true;
									break;
								} else {
									data.results[j].disabled = false;
								}
							}
						}
					}
					//console.log(data);
					return data;
				}
			},
			formatResult:       formatResults,
			formatSelection:    formatSelection
		});

	}

	function formatResults(data) {
		return '<div id="opt-' + data.filename + '" class="cdnjs-selection" title="' + data.description + '">' + data.name + '</div>';
	}

	function formatSelection(data, container) {
		usedPlugins.push(data.name);
		return data.name;
	}

	remoteAjaxAutoComplete('#cdnjs_settings_scripts', '//api.cdnjs.com/libraries?fields=version,filename,description,assets');

	//on event handler
	cdnjsScripts.on('select2-selecting', function(e, data) {
		if(!e.object) {
			e.object = data;
		}
		jQuery('.cdnjs-selected label, .cdnjs-selected #cdnjs-selected').show();

		addLibraryRow(e.object, '#cdnjs-selected tbody');
	});

	//on event handler
	cdnjsScripts.on('select2-close', function(e, data) {
		cdnjsScripts.select2('data', null);
	});

	onAssetChange();

	cdnjsSelected.find('tbody').sortable({
		cursor: "move",
		handle: "i.fa-arrows-v",
		stop:   function(event, ui) {
			cdnjsSelected.find('tr').removeClass('alternate');
			cdnjsSelected.find('tr:odd').addClass('alternate');
		}
	});

	cdnjsSelected.find('tr:odd').addClass('alternate');

	jQuery('.wp-cdnjs-remove-row').live('click', function(e) {
		var el = jQuery(this).parent().parent();
		var name = el.find('input.plugin_name').val();

		el.remove();

		for(var i = usedPlugins.length - 1; i >= 0; i--) {
			if(usedPlugins[i] === name) {
				usedPlugins.splice(i, 1);
			}
		}
	});

});

//This is for loading with data and also called when selecting a plugin
function onAssetChange() {
	jQuery('.select2-assets').not('.select2-offscreen').each(function(i, obj) {
		//Attach select2 to all asset dropdowns
		var objID = jQuery('#' + obj.id);
		objID.select2({
			width:           "100%",
			//plugins:   [],
			//id:     'name',
			placeholder:     cdnjs_text.add_assets,
			formatNoMatches: function() {
				return cdnjs_text.no_addl_assets;
			},
			ajax:            {
				url:      '//api.cdnjs.com/libraries?fields=assets',
				dataType: 'json',
				data:     function(term, page) {
					return {
						search: jQuery(this).data("plugin-name")
					};
				},
				results:  function(data, page) {
					var results = [];
					var used_assets = [];

					theMainAsset = objID.data("asset-id");
					//console.log(theMainAsset);
					/*var used_assets = jQuery('#' + theMainAsset + '-asset-holder input').map(function() {
						return jQuery(this).val();
					}).get();*/
					jQuery('#' + theMainAsset + '-asset-holder input').each(function() {
						used_assets.push(jQuery(this).val());
					});
					used_assets.push(objID.data("asset-file").replace('.min', ''));

					console.log('used_assets');
					console.log(used_assets);

					var assets = data.results[0].assets[0].files;
					//console.log(assets);

					for(i = 0; i < assets.length; i++) {

						if(jQuery.inArray(assets[i].name, used_assets) == -1 ) {
							//console.log(assets[i].name);

							if(getFileExtension(assets[i].name)) {
								results.push({
									id:   assets[i].name,
									text: assets[i].name
								});
							}
						}

					}

					return {
						results: results
					};

				}
			}
		});

		//Call on change for assest
		objID.on('select2-selecting', function(e, data) {
			if(!e.object) {
				e.object = data;
			}
			//console.log(jQuery('#'+obj.id).data( "plugin" ));
			addAssetRow(e.object, objID.attr('id'));
		});

		objID.on('select2-close', function(e, data) {
			objID.select2('data', null);
		});
	});
}

function getFileExtension(filename) {
	//console.dir(filename);
	if(filename.split('.').pop() == 'css' || filename.split('.').pop() == 'js') {
		return true;
	} else {
		return false;
	}
}

function cleanName(str) {
	return str.replace(/[^a-zA-Z\d ]/g, '-').toLowerCase();
}

function onAssetSelect(fieldID) {
	jQuery('#' + fieldID).on('select2-selecting', function(e, data) {
		if(!e.object) {
			e.object = data;
		}
		addAssetRow(e.object, fieldID);
	});
}

function addLibraryRow(data, location) {
	var assets = data.assets[0].files;
	var default_asset = data.filename;
	var nameID = cleanName(data.name);

	// check to see if the default asset in minified, if not check to see if a min exists
	if(default_asset.indexOf('.min.') == -1) {
		for(i = 0; i < assets.length; i++) {
			// if min version exists make it the default
			//console.log(assets[i]);
			var tmp = assets[i].name;
			if(default_asset == tmp.replace('.min', '')) {
				default_asset = assets[i].name;
				//console.dir(assets);
			}
		}
	}

	var row = '<tr id="' + nameID + '-row" class="index">';
	row += '<td class="wp-cdnjs_move"><i class="fa fa-arrows-v"></i></td>';
	row += '<td class="wp-cdnjs_name"><strong>' + data.name + '</strong> <br/>' + cdnjs_text.version + ': ' + data.version;
	row += '<input type="hidden" name="cdnjs[cdnjs_settings_scripts][' + nameID + '][name]" class="plugin_name" value="' + data.name + '"/>';
	row += '<input type="hidden" name="cdnjs[cdnjs_settings_scripts][' + nameID + '][version]" class="plugin_version" value="' + data.version + '"/>';
	row += '</td>';
	row += '<td class="wp-cdnjs_assets">';
	row += '<div id="' + cleanName(default_asset) + '-asset-holder" class="inluded_assets"><div><strong>' + cdnjs_text.inc_assets + ':</strong></div>';
	row += '<div id="' + cleanName(default_asset) + '-asset-row">';
	row += default_asset + ' *';
	row += '<input type="hidden" name="cdnjs[cdnjs_settings_scripts][' + nameID + '][assets][]" value="' + default_asset + '">';
	row += '</div>';
	row += '</div>';
	row += '</td>';
	row += '<td class="wp-cdnjs_version"><input type="hidden" id="' + nameID + '" data-plugin-name="' + data.name + '" data-asset-id="' + cleanName(default_asset) + '" data-asset-file="' + default_asset + '" class="select2-assets"></td>';
	row += '<td class="wp-cdnjs_location"><select name="cdnjs[cdnjs_settings_scripts][' + nameID + '][location]" id=""><option value="0" selected="selected">' + cdnjs_text.footer + '</option><option value="1">' + cdnjs_text.header + '</option></select></td>';
	row += '<td class="wp-cdnjs_enable"><input type="hidden" name="cdnjs[cdnjs_settings_scripts][' + nameID + '][enabled]" id="" value="0"><input type="checkbox" name="cdnjs[cdnjs_settings_scripts][' + nameID + '][enabled]" id="" value="1" checked="checked"></td>';
	row += '<td><span class="wp-cdnjs-remove-row button-secondary">' + cdnjs_text.remove + '</span></td>';
	row += '</tr>';

	jQuery(location).append(row);
	jQuery('#cdnjs-selected tr:odd').addClass('alternate');
	onAssetChange();
}

function addAssetRow(data, location) {
	var nameID = cleanName(data.text);
	var row = '<div id="' + nameID + '-asset-row">';
	row += '&bull; ' + data.text;
	row += ' <i title="' + cdnjs_text.remove + '" style="cursor:pointer" class="fa fa-times" onclick="removeRow(\'#' + nameID + '-asset-row\');"></i><br />';
	row += '<input type="hidden" name="cdnjs[cdnjs_settings_scripts][' + location + '][assets][]" value="' + data.text + '"/>';
	row += '</div>';
	jQuery('#' + location + '-row div.inluded_assets').append(row);
}


function removeRow(row_id) {
	jQuery(row_id).remove();
}
