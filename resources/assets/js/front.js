jQuery(document).ready(function() {

    function selectPointCallback(point) {
        parcelMachineAddressDesc = point.address.line1;

        let selected_point_data = '';

        if('undefined' == typeof point.location_description) {
            selected_point_data = '<div class="easypack_selected_point_data" id="easypack_selected_point_data">\n'
                + '<div id="selected-parcel-machine-id">' + point.name + '</div>\n'
                + '<span id="selected-parcel-machine-desc">' + point.address.line1 + '</span>'
                + '<input type="hidden" id="parcel_machine_id"\n'
                + ' name="parcel_machine_id" class="parcel_machine_id" value="'+point.name+'"/>\n'
                + '<input type="hidden" id="parcel_machine_desc"\n'
                + ' name="parcel_machine_desc" class="parcel_machine_desc" value="'+parcelMachineAddressDesc+'"/></div>';
        } else {
            selected_point_data = '<div class="easypack_selected_point_data" id="easypack_selected_point_data">\n'
                + '<div id="selected-parcel-machine-id">' + point.name + '</div>\n'
                + '<span id="selected-parcel-machine-desc">' + point.address.line1 + '</span>\n'
                + '<span id="selected-parcel-machine-desc1">' + '(' + point.location_description + ')</span>'
                + '<input type="hidden" id="parcel_machine_id"\n'
                + ' name="parcel_machine_id" class="parcel_machine_id" value="'+point.name+'"/>\n'
                + '<input type="hidden" id="parcel_machine_desc"\n'
                + ' name="parcel_machine_desc" class="parcel_machine_desc" value="'+parcelMachineAddressDesc+'"/></div>\n';
        }
        jQuery('#easypack_italy_geowidget').after(selected_point_data);

        if( point.location_description ) {
            InpostItalyPointObject = { 'pointName': point.name, 'pointDesc': point.address.line1, 'pointAddDesc': point.location_description };
        } else {
            InpostItalyPointObject = { 'pointName': point.name, 'pointDesc': point.address.line1, 'pointAddDesc': '' };
        }
        // Put the object into storage
        localStorage.setItem('InpostItalyPointObject', JSON.stringify(InpostItalyPointObject));

        // remove map div - so it has to render again
        jQuery('#widget-modal').parent('div').remove();

    }

    jQuery( document.body ).on('update_checkout', function() {
        jQuery('#easypack_italy_geowidget').remove();
        jQuery('#easypack_selected_point_data').remove();
    });

    jQuery( document.body ).on('updated_checkout', function() {

        let method = jQuery('input[name^="shipping_method"]:checked').val();
        let postfix = '';

        if('undefined' == typeof method || null === method ) {
            method = jQuery('input[name^="shipping_method"]').val();
        }

        if(typeof method != 'undefined' && method !== null) {
            if (method.indexOf(':') > -1) {
                let arr = method.split(':');
                method = arr[0];
                postfix = arr[1];
            }
        }

        let selected_point_data = jQuery('#easypack_selected_point_data');

        if(typeof method != 'undefined' && method !== null) {

            let selector = '#shipping_method_0_easypack_italy_parcel_machines' + postfix;

            let map_button = '<div class="easypack_italy_geowidget" id="easypack_italy_geowidget">\n' +
                easypack_front_map.button_text1 + '</div>';

            let li = jQuery(selector).parent('li');

            if (method === 'easypack_italy_parcel_machines') {

                window.easyPackAsyncInit = function () {
                    easyPack.init({
                        apiEndpoint: 'https://api-it-points.easypack24.net/v1/',
                        defaultLocale: 'it',
                        mapType: 'osm',
                        searchType: 'google',
                        points: {
                            types: ['pop', 'parcel_locker']
                        },
                        map: {
                            initialTypes: ['pop', 'parcel_locker'],
                            useGeolocation: false,
                        },
                        display: {
                        }
                    });
                };

                jQuery(li).after(map_button);
                jQuery('#ship-to-different-address').hide();

                let InpostItalyPointObject = localStorage.getItem('InpostItalyPointObject');

                if (InpostItalyPointObject !== null) {

                    let point,
                        selected_point_data,
                        desc;

                    let pointData = JSON.parse(InpostItalyPointObject);

                    if( typeof pointData.pointName != 'undefined' && pointData.pointName !== null ) {
                        point = pointData.pointName;
                    }

                    if( typeof pointData.pointDesc != 'undefined' && pointData.pointDesc !== null ) {
                        desc = pointData.pointDesc;
                    } else {
                        desc = '';
                    }

                    if( pointData.pointAddDesc.length > 0 ) {
                        additional_desc = ' (' + pointData.pointAddDesc + ')';
                    } else {
                        additional_desc = '';
                    }

                    if( point ) {
                        selected_point_data = '<div class="easypack_selected_point_data" id="easypack_selected_point_data">\n'
                            + '<span class="font-height-600">' +  easypack_front_map.selected_text + '</span>\n'
                            + '<div id="selected-parcel-machine-id">' + point + '</div>\n'
                            + '<span id="selected-parcel-machine-desc">' + desc + '</span>\n'
                            + '<span id="selected-parcel-machine-desc1">' + additional_desc + '</span>'
                            + '<input type="hidden" id="parcel_machine_id" name="parcel_machine_id" class="parcel_machine_id" value="' + point + '"/>\n'
                            + '<input type="hidden" id="parcel_machine_desc" name="parcel_machine_desc" class="parcel_machine_desc" value="' + desc + '"/></div>';

                        jQuery('#easypack_italy_geowidget').after(selected_point_data);
                        jQuery("#easypack_italy_geowidget").text(easypack_front_map.button_text2);
                    }
                }

            } else {

                jQuery('.easypack_italy_geowidget').each(function(ind, elem) {
                    jQuery(elem).remove();
                });

                jQuery('.easypack_selected_point_data').each(function(ind, elem) {
                    jQuery(elem).remove();
                });
                //empty hidden values of selected point
                jQuery('.parcel_machine_id').val('');
                jQuery('.parcel_machine_desc').val('');
                jQuery('#ship-to-different-address').show();
            }

            jQuery("#easypack_italy_geowidget").click(function (e) {
                e.preventDefault();

                jQuery('#easypack_selected_point_data').remove();

                easyPack.modalMap(function (point, modal) {
                    modal.closeModal();

                    if (point) {
                        jQuery("#easypack_italy_geowidget").text(easypack_front_map.button_text2);
                        selectPointCallback(point);
                    }
                }, {width: window.innerWidth, height: window.innerHeight});

                return true;
            });

        }
    });

    inp_waitForElm('.types-list').then((elm) => {
        var controlDiv = document.createElement('li');
        controlDiv.classList.add('custom-my-location');
        controlDiv.id = 'custom-my-location';

        var clickHandler = function() {

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function (position) {
                    latit = position.coords.latitude;
                    longit = position.coords.longitude;
                    window.mapController.setCenterFromArray([latit, longit]);
                    //window.mapController.setCenterFromArray([41.1171432, 16.8718715]);
                })

            }
        };

        var firstChild = document.createElement('button');
        firstChild.style.backgroundColor = '#fff';
        firstChild.style.border = 'none';
        firstChild.style.outline = 'none';
        firstChild.style.width = '28px';
        firstChild.style.height = '28px';
        firstChild.style.borderRadius = '2px';
        firstChild.style.boxShadow = '0 1px 4px rgba(0,0,0,0.3)';
        firstChild.style.cursor = 'pointer';
        firstChild.style.marginRight = '10px';
        firstChild.style.padding = '0';
        firstChild.title = 'Your Location';
        firstChild.id = 'easypack-my-position';
        firstChild.classList.add('leaflet-control-locate');
        firstChild.addEventListener('click', clickHandler, false);
        controlDiv.appendChild(firstChild);

        var secondChild = document.createElement('div');
        secondChild.style.margin = '5px';
        secondChild.style.width = '18px';
        secondChild.style.height = '18px';
        secondChild.style.backgroundImage = 'url(' + easypack_front_map.location_icon + ')';
        secondChild.style.backgroundSize = '180px 18px';
        secondChild.style.backgroundPosition = '0 0';
        secondChild.style.backgroundRepeat = 'no-repeat';
        firstChild.appendChild(secondChild);

        let bar = document.querySelector('.types-list');
        bar.appendChild(controlDiv);

        let search_input = document.querySelector('#easypack-search');
        search_input.setAttribute('placeholder', easypack_front_map.placeholder_text);

    });

});

let popup_shown = false;

document.addEventListener('click', function (e) {
    e = e || window.event;
    var target = e.target || e.srcElement;

    if (target.hasAttribute('id') && target.getAttribute('id') == 'easypack-search') {

        let search_was_started = false;
        let mapSearchNoticeModal;

        const search_input_x = document.querySelector('.search-input');

        search_input_x.addEventListener("search", function(event) {
            window.mapController.setCenterFromArray([41.898386, 12.516985]);
        });

        target.addEventListener('keyup',function(){

            if(this.value.length > 0) {
                search_was_started = true;
            }

            if(search_was_started && this.value.length < 1) {
                // return map to Rome position
                window.mapController.setCenterFromArray([41.898386, 12.516985]);
            }
        });
    }

}, false);


function inp_waitForElm(selector) {
    return new Promise(resolve => {
        if (document.querySelector(selector)) {
            return resolve(document.querySelector(selector));
        }

        const observer = new MutationObserver(mutations => {
            if (document.querySelector(selector)) {
                resolve(document.querySelector(selector));
                observer.disconnect();
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });
}