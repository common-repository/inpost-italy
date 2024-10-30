jQuery(document).ready(function() {
	
	let choosed_point = '';
	
	let current_point =  jQuery("#parcel_machine_id").val();

	// get data of point choosed during checkout
	(async () => {
	  choosed_point = await fetchParcelPointData(current_point).catch(error => {
			error.message;
		});
	})();

    window.easyPackAsyncInit = function () {
        easyPack.init({
            //apiEndpoint: 'https://api-it-local-points.easypack24.net/v1/',
            apiEndpoint: 'https://api-it-points.easypack24.net/v1/',
            defaultLocale: 'it',
            mapType: 'osm',
            searchType: 'google',
            points: {
                types: ['pop', 'parcel_locker'],
            },
            map: {
                initialTypes: ['pop', 'parcel_locker'],
                useGeolocation: false
            },
            display: {
                showTypesFilters: true,
                showSearchBar: true
            }
        });
    };


    async function fetchParcelPointData(point) {

        const query = new URLSearchParams({
            name: point.trim()
        }).toString();

        const resp = await fetch(
            `https://api-shipx-it.easypack24.net/v1/points?${query}`,
            {method: 'GET'}
        );

        return data = await resp.text();
       
    }


    jQuery("#parcel_machine_id").click(function (e) {

        e.preventDefault();
        easyPack.modalMap(function (point, modal) {
            modal.closeModal();

            if (point) {
                //console.log(point);
                let settings_page_machine_input = jQuery("#easypack_default_machine_id");
                if( typeof settings_page_machine_input != 'undefined'
                    && settings_page_machine_input !== null ) {
                    jQuery(settings_page_machine_input).val(point.name);
                }
                let order_page_machine_input = jQuery("#parcel_machine_id");
                if( typeof order_page_machine_input != 'undefined'
                    && order_page_machine_input !== null ) {
                    jQuery(order_page_machine_input).val(point.name);
                }
                // destroy map div
                //jQuery('#widget-modal').parent('div').remove();

            }
        }, {width: window.innerWidth, height: window.innerHeight});

        return true;
    });
	
	
	waitForElm('.list-point-link').then((elm) => {
			
			const obj = JSON.parse(choosed_point);
			let location = obj.items[0].location;
			let lon = location[Object.keys(location)[1]];
			let lat = location[Object.keys(location)[0]];
			
			window.mapController.setCenterFromArray([lon, lat]);
			
			setTimeout(function() {
                // open the point details
				let point_to_show = document.querySelector('[href="#' + current_point + '"]');
				simulateClick(point_to_show);

			}, 1500);

		});

	// replace map search input placeholder
	waitForElm('.types-list').then((elm) => {
        let search_input = document.querySelector('#easypack-search');
        search_input.setAttribute('placeholder', easypack_admin_map.placeholder_text);
	});


});


document.addEventListener('click', function (e) {
    e = e || window.event;
    var target = e.target || e.srcElement;

    if (target.hasAttribute('id') && target.getAttribute('id') == 'easypack-search') {

        let search_was_started = false;

        const obj = JSON.parse(choosed_point);
        let location = obj.items[0].location;
        let lon = location[Object.keys(location)[1]];
        let lat = location[Object.keys(location)[0]];

        
        const search_input_x = document.querySelector('.search-input');

        search_input_x.addEventListener("search", function(event) {
            window.mapController.setCenterFromArray([lon, lat]);
        });

        target.addEventListener('keyup',function(){

            if(this.value.length > 0) {
                search_was_started = true;
            }

            if(search_was_started && this.value.length < 1) {
                // return map to initial parcel locker position
                window.mapController.setCenterFromArray([lon, lat]);
            }
        });
    }

}, false);


async function waitForElm(selector) {
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


var simulateClick = function (elem) {
    // Create our event (with options)
    var evt = new MouseEvent('click', {
        bubbles: true,
        cancelable: true,
        view: window
    });
    // If cancelled, don't dispatch our event
    var canceled = !elem.dispatchEvent(evt);
};