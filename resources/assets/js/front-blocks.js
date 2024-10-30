var geowidgetSelectedPointItaly;

function inpost_italy_change_react_input(input,value){
	if (typeof input != 'undefined' && input !== null) {
		var nativeInputValueSetter = Object.getOwnPropertyDescriptor(
			window.HTMLInputElement.prototype,
			"value"
		).set;
		nativeInputValueSetter.call( input, value );
		var inputEvent = new Event( "input", { bubbles: true } );
		input.dispatchEvent( inputEvent );
	}
}


function inpost_italy_select_point_callback(point) {
	parcelMachineAddressDesc = point.address.line1;

	let selected_point_data = '';
	let InpostItalyPointObject;

	inpost_italy_change_react_input( document.getElementById( 'inpost-italy-parcel-locker-id' ), point.name );

	if ('undefined' == typeof point.location_description) {
		selected_point_data = '<div class="easypack_selected_point_data" id="easypack_selected_point_data">\n'
			+ '<div id="selected-parcel-machine-id">' + point.name + '</div>\n'
			+ '<span id="selected-parcel-machine-desc">' + point.address.line1 + '</span></div>\n';
	} else {
		selected_point_data = '<div class="easypack_selected_point_data" id="easypack_selected_point_data">\n'
			+ '<div id="selected-parcel-machine-id">' + point.name + '</div>\n'
			+ '<span id="selected-parcel-machine-desc">' + point.address.line1 + '</span>\n'
			+ '<span id="selected-parcel-machine-desc1">(' + point.location_description + ')</span></div>\n';
	}

	jQuery( '#easypack_selected_point_data_wrap' ).html( selected_point_data );
	jQuery( '#easypack_selected_point_data_wrap' ).show();

	if ( point.location_description ) {
		InpostItalyPointObject = { 'pointName': point.name, 'pointDesc': point.address.line1, 'pointAddDesc': point.location_description };
	} else {
		InpostItalyPointObject = { 'pointName': point.name, 'pointDesc': point.address.line1, 'pointAddDesc': '' };
	}
	// Put the object into storage.
	localStorage.setItem( 'InpostItalyPointObject', JSON.stringify( InpostItalyPointObject ) );

	geowidgetSelectedPointItaly = selected_point_data;
	// remove map div - so it has to render again.
	jQuery( '#widget-modal' ).parent( 'div' ).remove();

}



function inpost_italy_show_prev_saved_point(saved_obj) {
	let additional_desc = '';
	let point           = null;
	let desc            = '';

	let pointData = JSON.parse( saved_obj );

	if (typeof pointData.pointName != 'undefined' && pointData.pointName !== null) {
		point = pointData.pointName;
	}

	if (typeof pointData.pointDesc != 'undefined' && pointData.pointDesc !== null) {
		desc = pointData.pointDesc;
	} else {
		desc = '';
	}

	if (typeof pointData.pointAddDesc != 'undefined' && pointData.pointAddDesc !== null) {
		if (pointData.pointAddDesc.length > 0) {
			additional_desc = ' (' + pointData.pointAddDesc + ')';
		}
	} else {
		additional_desc = '';
	}

	if (point) {
		let saved_point = '<div class="easypack_selected_point_data" id="easypack_selected_point_data">\n'
			+ '<div id="selected-parcel-machine-id">' + point + '</div>\n'
			+ '<span id="selected-parcel-machine-desc">' + desc + '</span>\n'
			+ '<span id="selected-parcel-machine-desc1">' + additional_desc + '</span></div>';

		inpost_italy_change_react_input( document.getElementById( 'inpost-italy-parcel-locker-id' ), point );

		inpost_italy_wait_element( '.easypack_selected_point_data_wrap' ).then(
			(elm) => {
				jQuery( '#easypack_selected_point_data_wrap' ).html( saved_point );
				jQuery( '#easypack_selected_point_data_wrap' ).show();
			}
		);

	}
}




jQuery( document ).ready(
	function () {
		window.easyPackAsyncInit = function () {
			easyPack.init(
				{
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
				}
			);
		};

		let modal       = document.createElement( 'div' );
		modal.innerHTML = `
		<div id="inpost_italy_checkout_validation_modal" style="
			display: none;
			position: fixed;
			top: 0;
			left: 0;
			width: 100%; 
			height: 100%; 
			background-color: rgba( 0, 0, 0, 0.5 );
			justify-content: center;
			align-items: center;
			z-index: 1000;">
			<div style="
			background-color: white;
			width: 90%; 
			max-width: 300px;
			padding: 20px;
			position: relative;
			text-align: center;
			border-radius: 10px;
			box-shadow: 0px 4px 10px rgba( 0, 0, 0, 0.1 );">
			<span id="inp_it_close_modal_cross" style="
				position: absolute;
				top: 10px;
				right: 15px;
				font-size: 20px;
				cursor: pointer;">&times;</span>
			<div style="margin:20px 0; font-size:18px;">
				Seleziona Punto InPost
			</div>
			<button id="inp_it_close_modal_button" style="
				padding: 10px 20px;
				background-color: #007BFF;
				color: white;
				border: none;
				border-radius: 5px;
				cursor: pointer;
				font-size: 16px;">
				Ok
			</button>
			</div>
		</div>
		`;

		// Append modal to body.
		document.body.appendChild( modal );

		// Event Listeners for closing modal.
		document.getElementById( 'inp_it_close_modal_cross' ).addEventListener( 'click', inpost_italy_close_modal );
		document.getElementById( 'inp_it_close_modal_button' ).addEventListener( 'click', inpost_italy_close_modal );

		setTimeout(
			function () {

				let map_button = jQuery( '#easypack_italy_geowidget' );

				if (typeof map_button != 'undefined' && map_button !== null) {
					jQuery( '#shipping-phone' ).prop( 'required', true );
					jQuery( 'label[for="shipping-phone"]' ).text( 'Telefono (richiesto)' );

					let InpostItalyPointObject = localStorage.getItem( 'InpostItalyPointObject' );
					if (InpostItalyPointObject !== null) {
						inpost_italy_show_prev_saved_point( InpostItalyPointObject );
					}

				} else {
					jQuery( '#easypack_selected_point_data' ).remove();
					jQuery( '#shipping-phone' ).prop( 'required', false );
					jQuery( 'label[for="shipping-phone"]' ).text( 'Telefono *' );
				}

				jQuery( 'input[name^="radio-control-"]' ).on(
					'change',
					function () {
						if (this.checked) {

							jQuery( '.easypack_selected_point_data' ).each(
								function (ind, elem) {
									jQuery( elem ).remove();
								}
							);
							if (jQuery( this ).attr( 'id' ).indexOf( 'easypack_italy_parcel_machines' ) !== -1) {
								let label = jQuery( this ).parent( 'label' );

								jQuery( '#shipping-phone' ).prop( 'required', true );
								jQuery( 'label[for="shipping-phone"]' ).text( 'Telefono (richiesto)' );

								let InpostItalyPointObject = localStorage.getItem( 'InpostItalyPointObject' );

								if (InpostItalyPointObject !== null) {
									inpost_italy_show_prev_saved_point( InpostItalyPointObject );
								}

							} else {
								jQuery( '#shipping-phone' ).prop( 'required', false );
								jQuery( 'label[for="shipping-phone"]' ).text( 'Telefono *' );
							}
						}
					}
				);

			},
			1000
		);

		// Inpost map customization after loading.
		inpost_italy_wait_element( '.types-list' ).then(
			(elm) => {
            var controlDiv = document.createElement( 'li' );
            controlDiv.classList.add( 'custom-my-location' );
            controlDiv.id                    = 'custom-my-location';
            var clickHandler                 = function () {
					if (navigator.geolocation) {
						navigator.geolocation.getCurrentPosition(
							function (position) {
								latit  = position.coords.latitude;
								longit = position.coords.longitude;
								window.mapController.setCenterFromArray( [latit, longit] );
								// window.mapController.setCenterFromArray([41.1171432, 16.8718715]);.
							}
						)

					}
				};
            var firstChild                   = document.createElement( 'button' );
            firstChild.style.backgroundColor = '#fff';
            firstChild.style.border          = 'none';
            firstChild.style.outline         = 'none';
            firstChild.style.width           = '28px';
            firstChild.style.height          = '28px';
            firstChild.style.borderRadius    = '2px';
            firstChild.style.boxShadow       = '0 1px 4px rgba(0,0,0,0.3)';
            firstChild.style.cursor          = 'pointer';
            firstChild.style.marginRight     = '10px';
            firstChild.style.padding         = '0';
            firstChild.title                 = 'Your Location';
            firstChild.id                    = 'easypack-my-position';
            firstChild.classList.add( 'leaflet-control-locate' );
            firstChild.addEventListener( 'click', clickHandler, false );
            controlDiv.appendChild( firstChild );
            var secondChild                      = document.createElement( 'div' );
            secondChild.style.margin             = '5px';
            secondChild.style.width              = '18px';
            secondChild.style.height             = '18px';
            secondChild.style.backgroundImage    = 'url(' + easypack_blocks.location_icon + ')';
            secondChild.style.backgroundSize     = '180px 18px';
            secondChild.style.backgroundPosition = '0 0';
            secondChild.style.backgroundRepeat   = 'no-repeat';
            firstChild.appendChild( secondChild );
            let bar = document.querySelector( '.types-list' );
            bar.appendChild( controlDiv );
            let search_input = document.querySelector( '#easypack-search' );
            search_input.setAttribute( 'placeholder', easypack_blocks.placeholder_text );
			}
		);
	}
);


function inpost_italy_open_modal() {
	document.getElementById( 'inpost_italy_checkout_validation_modal' ).style.display = 'flex';
}

function inpost_italy_close_modal() {
	document.getElementById( 'inpost_italy_checkout_validation_modal' ).style.display = 'none';

	// Scroll to map button.
	let scrollToElement = document.getElementById( 'easypack_italy_geowidget' );

	if (scrollToElement) {
		scrollToElement.scrollIntoView( {behavior: 'smooth' } );
	}

}


function inpost_italy_wait_element(selector) {
	return new Promise(
		resolve => {
			if (document.querySelector( selector )) {
				return resolve( document.querySelector( selector ) );
			}
			const observer = new MutationObserver(
            mutations => {
					if (document.querySelector( selector )) {
						resolve( document.querySelector( selector ) );
						observer.disconnect();
					}
            }
        );
		observer.observe(
			document.body,
			{
				childList: true,
				subtree: true
			}
		);
		}
	);
}


document.addEventListener(
	'click',
	function (e) {
		e          = e || window.event;
		var target = e.target || e.srcElement;

		if (target.hasAttribute( 'id' ) && target.getAttribute( 'id' ) === 'easypack-search') {
			let search_was_started = false;
			let mapSearchNoticeModal;
			const search_input_x = document.querySelector( '.search-input' );
			search_input_x.addEventListener(
				"search",
				function (event) {
					window.mapController.setCenterFromArray( [41.898386, 12.516985] );
				}
			);
			target.addEventListener(
				'keyup',
				function () {
					if (this.value.length > 0) {
						search_was_started = true;
					}
					if (search_was_started && this.value.length < 1) {
						// return map to Rome position.
						window.mapController.setCenterFromArray( [41.898386, 12.516985] );
					}
				}
			);
		}

		if ( target.hasAttribute( 'id' ) ) {
			if (target.getAttribute( 'id' ) === 'easypack_italy_geowidget') {
				e.preventDefault();
				jQuery( '.easypack_selected_point_data' ).each(
					function (ind, elem) {
						jQuery( elem ).remove();
					}
				);
				easyPack.modalMap(
					function (point, modal) {
						modal.closeModal();
						if (point) {
							jQuery( "#easypack_italy_geowidget" ).text( easypack_blocks.button_text2 );
							inpost_italy_select_point_callback( point );
						}
					},
					{width: window.innerWidth, height: window.innerHeight}
				);
				return true;
			}
		}

		if ( target.classList.contains( 'wc-block-components-checkout-place-order-button' )
			|| target.classList.contains( 'wc-block-checkout__actions_row' ) ) {

			let reactjs_input       = document.getElementById( 'inpost-italy-parcel-locker-id' );
			let reactjs_input_lalue = false;
			if (typeof reactjs_input != 'undefined' && reactjs_input !== null) {
				reactjs_input_lalue = reactjs_input.value;
				if ( ! reactjs_input_lalue ) {
					inpost_italy_open_modal();
				}
			}
		}

		if ( target.classList.contains( 'wc-block-components-button__text' ) ) {
			let parent = target.parentNode;
			if ( parent.classList.contains( 'wc-block-components-checkout-place-order-button' ) ) {
				let reactjs_input       = document.getElementById( 'inpost-italy-parcel-locker-id' );
				let reactjs_input_lalue = false;
				if (typeof reactjs_input != 'undefined' && reactjs_input !== null) {
					reactjs_input_lalue = reactjs_input.value;
					if ( ! reactjs_input_lalue ) {
						inpost_italy_open_modal();
					}
				}
			}
		}
	}
);
