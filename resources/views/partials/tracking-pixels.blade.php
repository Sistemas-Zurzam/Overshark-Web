{{--
    Píxeles de anuncios (Admin → Configuración → Integraciones → Píxeles). Los IDs se validan con
    App\Support\Integrations::PIXEL_PATTERNS; los eventos los anotan las rutas con App\Support\PixelEvents.
--}}
@php
    $pixels = \App\Support\Integrations::pixels();
    $pixelEvents = array_filter($pixels) ? \App\Support\PixelEvents::pull() : [];
@endphp
@if ($pixels['meta'])
    <script>
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', @json($pixels['meta']));
        fbq('track', 'PageView');
    </script>
@endif
@if ($pixels['ga4'])
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $pixels['ga4'] }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @json($pixels['ga4']));
    </script>
@endif
@if ($pixels['tiktok'])
    <script>
        !function (w, d, t) {w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie","holdConsent","revokeConsent","grantConsent"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var r="https://analytics.tiktok.com/i18n/pixel/events.js",o=n&&n.partner;ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=r,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};n=document.createElement("script");n.type="text/javascript",n.async=!0,n.src=r+"?sdkid="+e+"&lib="+t;e=document.getElementsByTagName("script")[0];e.parentNode.insertBefore(n,e)};
        ttq.load(@json($pixels['tiktok']));
        ttq.page();
        }(window, document, 'ttq');
    </script>
@endif
@if ($pixelEvents)
    <script>
        (function (events) {
            var ga4 = {ViewContent: 'view_item', AddToCart: 'add_to_cart', InitiateCheckout: 'begin_checkout', Purchase: 'purchase'};
            var tiktok = {ViewContent: 'ViewContent', AddToCart: 'AddToCart', InitiateCheckout: 'InitiateCheckout', Purchase: 'CompletePayment'};
            events.forEach(function (event) {
                var name = event[0], data = event[1], items = data.items || [];
                var ids = items.map(function (item) { return item.id; });
                var count = items.reduce(function (total, item) { return total + item.quantity; }, 0);
                if (window.fbq) {
                    fbq('track', name, {
                        value: data.value, currency: data.currency, content_type: 'product', content_ids: ids, num_items: count,
                        contents: items.map(function (item) { return {id: item.id, quantity: item.quantity, item_price: item.price}; })
                    }, data.order_id ? {eventID: name + '-' + data.order_id} : {});
                }
                if (window.gtag) {
                    var params = {currency: data.currency, value: data.value, items: items.map(function (item) { return {item_id: item.id, item_name: item.name, price: item.price, quantity: item.quantity}; })};
                    if (data.order_id) { params.transaction_id = data.order_id; }
                    gtag('event', ga4[name], params);
                }
                if (window.ttq) {
                    ttq.track(tiktok[name], {
                        value: data.value, currency: data.currency, content_type: 'product',
                        contents: items.map(function (item) { return {content_id: item.id, content_name: item.name, price: item.price, quantity: item.quantity}; })
                    }, data.order_id ? {event_id: name + '-' + data.order_id} : {});
                }
            });
        })(@json($pixelEvents));
    </script>
@endif
