(() => {
    "use strict";
    const e = window.wc.blocksCheckout, t = window.wp.element, a = window.wp.i18n,
        l = window.wp.data, {ExperimentalOrderMeta: o} = wc.blocksCheckout;

    function i({handleLockerChange: e, inpostLocker: a}) {
        return (0, t.createElement)("div", {
            className: "inpost-italy-parcel-locker-wrap",
            style: {display: "none"}
        }, (0, t.createElement)("input", {
            value: a,
            type: "text",
            id: "inpost-italy-parcel-locker-id",
            onChange: e,
            required: !0
        }))
    }

    const n = JSON.parse('{"apiVersion":2,"name":"inpost-italy/inpost-italy-block","version":"2.0.0","title":"Inpost Italy Shipping Options Block","category":"woocommerce","description":"Adds map button abd input to save Locker ID value.","supports":{"html":false,"align":false,"multiple":false,"reusable":false},"parent":["woocommerce/checkout-shipping-methods-block"],"attributes":{"lock":{"type":"object","default":{"remove":true,"move":true}},"text":{"type":"string","source":"html","selector":".wp-block-inpost-italy","default":""}},"textdomain":"inpost-italy","editorStyle":""}');
    (0, e.registerCheckoutBlock)({
        metadata: n, component: ({checkoutExtensionData: e, extensions: n}) => {
            let s = !1;
            const [c, r] = (0, t.useState)(""), {setExtensionData: p} = e, d = "inpost-italy-parcel-locker-id-error", {
                setValidationErrors: u,
                clearValidationError: m
            } = (0, l.useDispatch)("wc/store/validation");
            let k = (0, l.useSelect)((e => e("wc/store/cart").getShippingRates()));
            if (null != k) {
                let e = k[Object.keys(k)[0]];
                if (null != e) {
                    const t = e.shipping_rates;
                    if (null != t) for (let e of t) if (!0 === e.selected && "easypack_italy_parcel_machines" === e.method_id) {
                        s = !0;
                        break
                    }
                }
            }
            const y = (0, t.useCallback)((() => {
                s && !c && u({
                    [d]: {
                        message: (0, a.__)("Please provide Inpost Locker Point ID.", "inpost-italy"),
                        hidden: !0
                    }
                })
            }), [c, u, m, s]), w = (0, t.useCallback)((() => {
                if (c || !s) return m(d), !0
            }), [c, u, m, s]);
            return (0, t.useEffect)((() => {
                y(), w(), p("inpost", "inpost-italy-parcel-locker-id", c)
            }), [c, p, w]), (0, t.createElement)(t.Fragment, null, s && (0, t.createElement)(t.Fragment, null, (0, t.createElement)("div", {
                className: "easypack_italy_geowidget",
                id: "easypack_italy_geowidget"
            }, (0, a.__)("Select InPost Point", "inpost-italy")), (0, t.createElement)("div", {
                id: "easypack_selected_point_data_wrap",
                className: "easypack_selected_point_data_wrap",
                style: {display: "none"}
            }), (0, t.createElement)(o, null, (0, t.createElement)(i, {
                inpostLocker: c, handleLockerChange: e => {
                    const t = e.target.value;
                    r(t)
                }
            }))))
        }
    })
})();