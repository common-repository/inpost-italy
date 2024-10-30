function selectPointCallback(point) {
    parcelMachineAddressDesc = point.address.line1;
    jQuery('.parcel_machine_id').val(point.name);
    jQuery('.parcel_machine_desc').val(parcelMachineAddressDesc);

    jQuery('*[id*=selected-parcel-machine]').each(function(ind, elem) {
        jQuery(elem).removeClass('hidden-punto-data');
    });

    if('undefined' == typeof point.location_description) {
        jQuery('*[id*=selected-parcel-machine-id]').each(function(ind, elem) {
            jQuery(elem).html(point.name);
        });
        jQuery('*[id*=selected-parcel-machine-desc]').each(function(ind, elem) {
            jQuery(elem).html(point.address.line1);
        });

    } else {

        jQuery('*[id*=selected-parcel-machine-id]').each(function(ind, elem) {
            jQuery(elem).html(point.name);
        });
        jQuery('*[id*=selected-parcel-machine-desc]').each(function(ind, elem) {
            jQuery(elem).html(point.address.line1);
        });
        jQuery('*[id*=selected-parcel-machine-desc1]').each(function(ind, elem) {
            jQuery(elem).html('(' + point.location_description + ')');
        });
    }

    // remove map div - so it has to render again
    jQuery('#widget-modal').parent('div').remove();

    // for some templates like Divi - add hidden fields for Parcel locker validation dynamically
    var form = document.getElementsByClassName('checkout woocommerce-checkout')[0];
    var additionalInput1 = document.createElement('input');
    additionalInput1.type = 'hidden';
    additionalInput1.name = 'parcel_machine_id';
    additionalInput1.value = point.name;

    var additionalInput2 = document.createElement('input');
    additionalInput2.type = 'hidden';
    additionalInput2.name = 'parcel_machine_desc';
    additionalInput2.value = parcelMachineAddressDesc;

    if(form) {
        form.appendChild(additionalInput1);
        form.appendChild(additionalInput2);
    }
}

jQuery( document.body ).on('updated_checkout', function() {
    const { __, _x, _n, sprintf } = wp.i18n;
    // create modal with map
    window.easyPackAsyncInit = function () {
        easyPack.init({
            apiEndpoint: 'https://api-it-points.easypack24.net/v1/',
            defaultLocale: 'it',
            mapType: 'osm',
            searchType: 'google',
            showNonOperatingLockers: false,
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

    if (method === 'easypack_italy_parcel_machines') {
        //jQuery('#ship-to-different-address').hide();
    } else {
        jQuery('#easypack_italy_geowidget').remove();
        jQuery('#easypack_selected_point_data').remove();
        //empty hidden values of selected point
        jQuery('.parcel_machine_id').val('');
        jQuery('.parcel_machine_desc').val('');
    }


    jQuery("#easypack_italy_geowidget").click(function (e) {
        e.preventDefault();
        jQuery('#easypack_selected_point_data').remove();
        easyPack.modalMap(function (point, modal) {
            modal.closeModal();
            if (point) {
                selectPointCallback(point);
            }
        }, {width: window.innerWidth, height: window.innerHeight});
        return true;
    });

    waitForElm('.types-list').then((elm) => {
        let geo_location_button = document.querySelector('#custom-my-location');
        if( 'undefined' === typeof geo_location_button || null === geo_location_button ) {
            var controlDiv = document.createElement('li');
            controlDiv.classList.add('custom-my-location');
            controlDiv.id = 'custom-my-location';

            var clickHandler = function () {
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
        }

        let search_input = document.querySelector('#easypack-search');
        if(typeof search_input != 'undefined' && search_input !== null) {
            search_input.setAttribute('placeholder', __('Type a city, address or postal code and select your choice. Or type an Inpost machine number and press magnifier icon', 'inpost-italy') );
        }

    });

});

document.addEventListener('click', function (e) {
    e = e || window.event;
    var target = e.target || e.srcElement;

    if (target.hasAttribute('id') && target.getAttribute('id') === 'easypack_italy_geowidget') {

        jQuery('#easypack_selected_point_data').remove();
        easyPack.modalMap(function (point, modal) {
            modal.closeModal();
            if (point) {
                selectPointCallback(point);
            }
        }, {width: window.innerWidth, height: window.innerHeight});
        return true;
    }

    if (target.hasAttribute('id') && target.getAttribute('id') === 'easypack-search') {
        let search_was_started = false;
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


function waitForElm(selector) {
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