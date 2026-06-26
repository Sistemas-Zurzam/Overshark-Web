import './bootstrap';

const sidebar = document.querySelector('[data-sidebar]');
const overlay = document.querySelector('[data-sidebar-overlay]');
const toggle = document.querySelector('[data-sidebar-toggle]');

const closeSidebar = () => {
    sidebar?.classList.add('-translate-x-full');
    overlay?.classList.add('hidden');
};

toggle?.addEventListener('click', () => {
    sidebar?.classList.toggle('-translate-x-full');
    overlay?.classList.toggle('hidden');
});

overlay?.addEventListener('click', closeSidebar);

const cartDrawer = document.querySelector('[data-cart-drawer]');
const cartOverlay = document.querySelector('[data-cart-overlay]');
const cartOpenButtons = document.querySelectorAll('[data-cart-open]');
const cartCloseButtons = document.querySelectorAll('[data-cart-close]');

const openCart = () => {
    cartDrawer?.classList.remove('translate-x-full');
    cartOverlay?.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
};

const closeCart = () => {
    cartDrawer?.classList.add('translate-x-full');
    cartOverlay?.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
};

cartOpenButtons.forEach((button) => button.addEventListener('click', openCart));
cartCloseButtons.forEach((button) => button.addEventListener('click', closeCart));
cartOverlay?.addEventListener('click', closeCart);

if (cartDrawer?.dataset.open === 'true') {
    openCart();
}

const webMenu = document.querySelector('[data-web-menu]');
const webMenuToggle = document.querySelector('[data-web-menu-toggle]');

webMenuToggle?.addEventListener('click', () => {
    const isHidden = webMenu?.classList.toggle('hidden');
    webMenuToggle.setAttribute('aria-expanded', String(!isHidden));
});

webMenu?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
        webMenu.classList.add('hidden');
        webMenuToggle?.setAttribute('aria-expanded', 'false');
    });
});

document.querySelectorAll('[data-carousel="slide"]').forEach((carousel) => {
    const items = Array.from(carousel.querySelectorAll('[data-carousel-item]'));
    const indicators = Array.from(carousel.querySelectorAll('[data-carousel-slide-to]'));
    const previous = carousel.querySelector('[data-carousel-prev]');
    const next = carousel.querySelector('[data-carousel-next]');
    let activeIndex = Math.max(0, items.findIndex((item) => !item.classList.contains('hidden')));

    const showSlide = (index) => {
        if (!items.length) {
            return;
        }

        activeIndex = (index + items.length) % items.length;

        items.forEach((item, itemIndex) => {
            item.classList.toggle('hidden', itemIndex !== activeIndex);
            item.classList.toggle('block', itemIndex === activeIndex);
        });

        indicators.forEach((indicator, indicatorIndex) => {
            const isActive = indicatorIndex === activeIndex;
            indicator.classList.toggle('is-active', isActive);
            indicator.setAttribute('aria-current', String(isActive));
        });
    };

    indicators.forEach((indicator) => {
        indicator.addEventListener('click', () => showSlide(Number(indicator.dataset.carouselSlideTo)));
    });

    previous?.addEventListener('click', () => showSlide(activeIndex - 1));
    next?.addEventListener('click', () => showSlide(activeIndex + 1));

    if (items.length > 1) {
        window.setInterval(() => showSlide(activeIndex + 1), 6000);
    }

    showSlide(activeIndex);
});

const setProductImage = (article, src) => {
    const image = article?.querySelector('[data-product-card-image]');

    if (image && src) {
        image.setAttribute('src', src);
        image.style.transform = '';
        image.style.transformOrigin = '';
    }
};

const bindProductThumbnails = (article) => {
    article?.querySelectorAll('[data-product-thumbnail]').forEach((thumbnail) => {
        thumbnail.addEventListener('click', () => setProductImage(article, thumbnail.dataset.image));
    });
};

const parseProductImages = (button) => {
    try {
        return JSON.parse(button.dataset.images || '[]');
    } catch {
        return [];
    }
};

const renderProductThumbnails = (article, images) => {
    const thumbnails = article?.querySelector('[data-product-thumbnails]');

    if (!thumbnails || !images.length) {
        return;
    }

    thumbnails.innerHTML = images.slice(0, 5).map((src) => `
        <button type="button" data-product-thumbnail data-image="${src}" class="h-24 w-24 shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-[#F7F7F7] p-1 transition hover:border-cyan-600 sm:h-28 sm:w-full">
            <img src="${src}" alt="" class="h-full w-full rounded-lg object-cover">
        </button>
    `).join('');
    bindProductThumbnails(article);
};

document.querySelectorAll('article').forEach(bindProductThumbnails);

document.querySelectorAll('[data-product-color]').forEach((button) => {
    button.addEventListener('click', () => {
        const card = button.closest('article');

        setProductImage(card, button.dataset.image);

        const selectedColor = card?.querySelector('[data-product-selected-color]');

        if (selectedColor && button.dataset.colorName) {
            selectedColor.textContent = button.dataset.colorName;
        }

        if (button.dataset.colorName) {
            const cartColor = card?.querySelector('[data-cart-color]');
            if (cartColor) {
                cartColor.value = button.dataset.colorName;
            }

            card?.querySelectorAll('[data-product-color][data-color-name]').forEach((swatch) => {
                swatch.classList.remove('ring-2', 'ring-slate-950');
            });

            button.classList.add('ring-2', 'ring-slate-950');
            renderProductThumbnails(card, parseProductImages(button));
        }
    });
});

document.querySelectorAll('[data-product-colors-expand]').forEach((button) => {
    button.addEventListener('click', () => {
        const card = button.closest('article');
        const expanded = button.getAttribute('aria-expanded') === 'true';

        card?.querySelectorAll('[data-extra-product-color]').forEach((swatch) => {
            swatch.classList.toggle('hidden', expanded);
        });

        button.textContent = expanded ? button.dataset.collapsedLabel : button.dataset.expandedLabel;
        button.setAttribute('aria-expanded', String(!expanded));
        button.setAttribute('aria-label', expanded ? 'Mostrar colores extra' : 'Ocultar colores extra');
    });
});

const parseAvailableSizes = (button) => {
    try {
        return JSON.parse(button.dataset.sizes || '[]');
    } catch {
        return [];
    }
};

const updateColorsForSize = (article, size) => {
    const colorButtons = Array.from(article.querySelectorAll('[data-product-color][data-sizes]'));

    colorButtons.forEach((button) => {
        const availableSizes = parseAvailableSizes(button);
        button.classList.toggle('hidden', !availableSizes.includes(size));
    });

    const activeColor = colorButtons.find((button) => button.classList.contains('ring-2') && !button.classList.contains('hidden'));

    if (!activeColor) {
        colorButtons.find((button) => !button.classList.contains('hidden'))?.click();
    }
};

document.querySelectorAll('[data-product-size]').forEach((button) => {
    button.addEventListener('click', () => {
        const article = button.closest('article');

        if (!article) {
            return;
        }

        article.querySelectorAll('[data-product-size]').forEach((sizeButton) => {
            sizeButton.classList.remove('bg-slate-950', 'text-white');
            sizeButton.classList.add('bg-white', 'text-slate-950');
        });

        button.classList.add('bg-slate-950', 'text-white');
        button.classList.remove('bg-white', 'text-slate-950');

        const cartSize = article.querySelector('[data-cart-size]');
        if (cartSize) {
            cartSize.value = button.dataset.productSize;
        }

        updateColorsForSize(article, button.dataset.productSize);
    });
});

document.querySelectorAll('article').forEach((article) => {
    const selectedSize = article.querySelector('[data-product-size].bg-slate-950') || article.querySelector('[data-product-size]');

    if (selectedSize) {
        updateColorsForSize(article, selectedSize.dataset.productSize);
    }
});

document.querySelectorAll('[data-product-info-tabs]').forEach((tabs) => {
    const buttons = Array.from(tabs.querySelectorAll('[data-product-info-tab]'));
    const panels = Array.from(tabs.querySelectorAll('[data-product-info-panel]'));

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            buttons.forEach((tabButton) => {
                const isActive = tabButton === button;
                tabButton.classList.toggle('border-slate-950', isActive);
                tabButton.classList.toggle('text-slate-950', isActive);
                tabButton.classList.toggle('border-transparent', !isActive);
                tabButton.classList.toggle('text-slate-500', !isActive);
            });

            panels.forEach((panel) => {
                panel.classList.toggle('hidden', panel.dataset.productInfoPanel !== button.dataset.productInfoTab);
            });
        });
    });
});

const productZoomModal = document.querySelector('[data-product-zoom-modal]');
const productZoomImage = document.querySelector('[data-product-zoom-image]');
const productZoomOpen = document.querySelector('[data-product-zoom-open]');
const productZoomClose = document.querySelector('[data-product-zoom-close]');
const productLensFrame = document.querySelector('[data-product-zoom-frame]');
const productLensToggle = document.querySelector('[data-product-lens-toggle]');
const sizeGuideModal = document.querySelector('[data-size-guide-modal]');
const sizeGuideOpen = document.querySelector('[data-size-guide-open]');
const sizeGuideClose = document.querySelector('[data-size-guide-close]');
const locationModal = document.querySelector('[data-location-modal]');
const locationOpen = document.querySelector('[data-location-modal-open]');
const locationCloseButtons = document.querySelectorAll('[data-location-modal-close]');
const locationSearch = document.querySelector('[data-location-search]');
const locationSearchClear = document.querySelector('[data-location-search-clear]');
const locationConfirm = document.querySelector('[data-location-confirm]');
const selectedLocationLabel = document.querySelector('[data-selected-location-label]');
const selectedLocationValue = document.querySelector('[data-selected-location-value]');
const selectedLocationLat = document.querySelector('[data-selected-location-lat]');
const selectedLocationLng = document.querySelector('[data-selected-location-lng]');
const locationMapElement = document.querySelector('[data-location-map]');
const locationMapLoading = document.querySelector('[data-location-map-loading]');
const checkoutAddressInput = document.querySelector('input[name="address"]');
const checkoutDepartmentSelect = document.querySelector('select[name="departamento_id"]');
const checkoutProvinceSelect = document.querySelector('select[name="provincia_id"]');
const checkoutDistrictSelect = document.querySelector('select[name="distrito_id"]');
const shalomAgencyModal = document.querySelector('[data-shalom-agency-modal]');
const shalomAgencyOpen = document.querySelector('[data-shalom-agency-modal-open]');
const shalomAgencyCloseButtons = document.querySelectorAll('[data-shalom-agency-modal-close]');
const shalomAgencySearch = document.querySelector('[data-shalom-agency-search]');
const shalomAgencySearchClear = document.querySelector('[data-shalom-agency-search-clear]');
const shalomAgencyConfirm = document.querySelector('[data-shalom-agency-confirm]');
const shalomAgencyList = document.querySelector('[data-shalom-agency-list]');
const shalomAgencyMapElement = document.querySelector('[data-shalom-agency-map]');
const shalomAgencyMapLoading = document.querySelector('[data-shalom-agency-map-loading]');
const shalomSelectedName = document.querySelector('[data-shalom-selected-name]');
const shalomSelectedAddress = document.querySelector('[data-shalom-selected-address]');
let isProductLensActive = false;
let checkoutLocationMap = null;
let checkoutLocationMarker = null;
let checkoutLocationGeocoder = null;
let checkoutLocationAutocomplete = null;
let checkoutLocationPosition = { lat: -11.9635, lng: -77.0736 };
let checkoutLocationAddressComponents = [];
let shalomAgencyMap = null;
let shalomAgencyMarker = null;
let selectedShalomAgency = null;
let shalomAgencyGeocoder = null;
let shalomAgenciesLoaded = false;

const closeProductZoom = () => {
    productZoomModal?.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
};

const closeSizeGuide = () => {
    sizeGuideModal?.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
};

const closeLocationModal = () => {
    locationModal?.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
};

const closeShalomAgencyModal = () => {
    shalomAgencyModal?.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
};

const normalizeMapPosition = (position) => ({
    lat: typeof position.lat === 'function' ? position.lat() : position.lat,
    lng: typeof position.lng === 'function' ? position.lng() : position.lng,
});

const normalizeLocationName = (value = '') => value
    .toString()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/\b(provincia|province|region|department|departamento|district|distrito|peru)\b/gi, '')
    .replace(/\s+/g, ' ')
    .trim()
    .toLowerCase();

const cleanLocationName = (value = '') => value
    .toString()
    .replace(/\b(Province|Region|Department|Provincia|Departamento|Distrito)\b/gi, '')
    .replace(/\s+/g, ' ')
    .trim();

const getAddressComponent = (components, types) => {
    const component = components.find((item) => types.some((type) => item.types.includes(type)));

    return component?.long_name || '';
};

const findSelectOptionByText = (select, candidates, predicate = null) => {
    if (!select) {
        return null;
    }

    const normalizedCandidates = candidates
        .map(cleanLocationName)
        .map(normalizeLocationName)
        .filter(Boolean);

    return Array.from(select.options).find((option) => {
        if (!option.value || (predicate && !predicate(option))) {
            return false;
        }

        return normalizedCandidates.includes(normalizeLocationName(option.textContent));
    }) || null;
};

const setSelectValueFromOption = (select, option) => {
    if (!select || !option) {
        return false;
    }

    select.value = option.value;
    select.dispatchEvent(new Event('change', { bubbles: true }));

    return true;
};

const fillCheckoutLocationFields = () => {
    const address = selectedLocationLabel?.textContent?.trim() || '';

    if (checkoutAddressInput && address) {
        checkoutAddressInput.value = address;
        checkoutAddressInput.dispatchEvent(new Event('input', { bubbles: true }));
        checkoutAddressInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    const components = checkoutLocationAddressComponents || [];
    const departmentName = getAddressComponent(components, ['administrative_area_level_1']);
    const provinceName = getAddressComponent(components, ['administrative_area_level_2']);
    const districtCandidates = [
        getAddressComponent(components, ['locality']),
        getAddressComponent(components, ['administrative_area_level_3']),
        getAddressComponent(components, ['sublocality_level_1']),
        getAddressComponent(components, ['sublocality']),
    ];

    const departmentOption = findSelectOptionByText(checkoutDepartmentSelect, [departmentName, provinceName]);
    setSelectValueFromOption(checkoutDepartmentSelect, departmentOption);

    const provinceOption = findSelectOptionByText(
        checkoutProvinceSelect,
        [provinceName, departmentName],
        (option) => !departmentOption || option.dataset.departamentoId === departmentOption.value,
    );
    setSelectValueFromOption(checkoutProvinceSelect, provinceOption);

    const districtOption = findSelectOptionByText(
        checkoutDistrictSelect,
        districtCandidates,
        (option) => !provinceOption || option.dataset.provinciaId === provinceOption.value,
    );
    setSelectValueFromOption(checkoutDistrictSelect, districtOption);

    window.localStorage.setItem('checkoutLocation', JSON.stringify({
        address,
        lat: checkoutLocationPosition.lat,
        lng: checkoutLocationPosition.lng,
        departamento: departmentOption?.textContent?.trim() || '',
        provincia: provinceOption?.textContent?.trim() || '',
        distrito: districtOption?.textContent?.trim() || districtCandidates.find(Boolean) || '',
    }));
};

const setSelectedLocation = (position, address, components = null) => {
    checkoutLocationPosition = normalizeMapPosition(position);

    if (address && selectedLocationLabel) {
        selectedLocationLabel.textContent = address;
    }

    if (components) {
        checkoutLocationAddressComponents = components;
    }

    if (selectedLocationLat) {
        selectedLocationLat.value = checkoutLocationPosition.lat.toFixed(7);
    }

    if (selectedLocationLng) {
        selectedLocationLng.value = checkoutLocationPosition.lng.toFixed(7);
    }
};

const reverseGeocodeLocation = (position) => {
    if (!checkoutLocationGeocoder) {
        setSelectedLocation(position);
        return;
    }

    checkoutLocationGeocoder.geocode({ location: normalizeMapPosition(position) }, (results, status) => {
        const address = status === 'OK' && results?.[0]?.formatted_address
            ? results[0].formatted_address
            : `${checkoutLocationPosition.lat.toFixed(6)}, ${checkoutLocationPosition.lng.toFixed(6)}`;

        setSelectedLocation(position, address, results?.[0]?.address_components || []);
    });
};

const moveCheckoutLocationMarker = (position, address = null, components = null) => {
    const normalizedPosition = normalizeMapPosition(position);

    checkoutLocationMarker?.setPosition(normalizedPosition);
    checkoutLocationMap?.panTo(normalizedPosition);

    if (address) {
        setSelectedLocation(normalizedPosition, address, components);
    } else {
        reverseGeocodeLocation(normalizedPosition);
    }
};

const initCheckoutLocationMap = () => {
    if (!locationMapElement || checkoutLocationMap || !window.google?.maps) {
        return;
    }

    locationMapLoading?.classList.add('hidden');
    checkoutLocationGeocoder = new window.google.maps.Geocoder();
    checkoutLocationMap = new window.google.maps.Map(locationMapElement, {
        center: checkoutLocationPosition,
        zoom: 16,
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: true,
    });

    checkoutLocationMarker = new window.google.maps.Marker({
        position: checkoutLocationPosition,
        map: checkoutLocationMap,
        draggable: true,
        title: 'Ubicacion de entrega',
    });

    setSelectedLocation(checkoutLocationPosition, selectedLocationLabel?.textContent?.trim() || 'Akpana 1261, Lima 15427');

    checkoutLocationMap.addListener('click', (event) => moveCheckoutLocationMarker(event.latLng));
    checkoutLocationMarker.addListener('dragend', (event) => moveCheckoutLocationMarker(event.latLng));

    if (locationSearch && window.google.maps.places) {
        checkoutLocationAutocomplete = new window.google.maps.places.Autocomplete(locationSearch, {
            componentRestrictions: { country: 'pe' },
            fields: ['address_components', 'formatted_address', 'geometry', 'name'],
        });

        checkoutLocationAutocomplete.addListener('place_changed', () => {
            const place = checkoutLocationAutocomplete.getPlace();

            if (!place.geometry?.location) {
                return;
            }

            moveCheckoutLocationMarker(
                place.geometry.location,
                place.formatted_address || place.name,
                place.address_components || [],
            );
            checkoutLocationMap.setZoom(17);
        });
    }
};

const getShalomAgencyData = (option) => ({
    name: option?.dataset.name || '',
    address: option?.dataset.address || '',
    zone: option?.dataset.zone || '',
    province: option?.dataset.province || '',
    department: option?.dataset.department || '',
    lat: Number(option?.dataset.lat || 0),
    lng: Number(option?.dataset.lng || 0),
});

const getStoredCheckoutLocation = () => {
    try {
        return JSON.parse(window.localStorage.getItem('checkoutLocation') || '{}');
    } catch {
        return {};
    }
};

const escapeHtml = (value = '') => value
    .toString()
    .replace(/&/g, '&amp;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');

const geocodeShalomAgency = (agency) => {
    if (!shalomAgencyGeocoder || !shalomAgencyMap || !agency?.address) {
        return;
    }

    const query = [agency.address, agency.zone, agency.province, agency.department, 'Peru']
        .filter(Boolean)
        .join(', ');

    shalomAgencyGeocoder.geocode({ address: query }, (results, status) => {
        if (status !== 'OK' || !results?.[0]?.geometry?.location) {
            return;
        }

        const position = results[0].geometry.location;
        shalomAgencyMarker?.setPosition(position);
        shalomAgencyMap.panTo(position);
        shalomAgencyMap.setZoom(15);
    });
};

const selectShalomAgencyOption = (option) => {
    if (!option) {
        return;
    }

    selectedShalomAgency = getShalomAgencyData(option);

    shalomAgencyList?.querySelectorAll('[data-shalom-agency-option]').forEach((item) => {
        const isSelected = item === option;
        item.setAttribute('aria-pressed', String(isSelected));
        item.classList.toggle('border-red-300', isSelected);
        item.classList.toggle('bg-red-50/30', isSelected);
        item.classList.toggle('border-slate-100', !isSelected);
    });

    geocodeShalomAgency(selectedShalomAgency);
};

const renderShalomAgencies = (agencies) => {
    if (!shalomAgencyList) {
        return;
    }

    if (!agencies.length) {
        shalomAgencyList.innerHTML = `
            <div class="rounded-lg border border-slate-100 bg-white px-4 py-6 text-center text-sm font-bold text-slate-500">
                No encontramos agencias para esta ubicacion.
            </div>
        `;
        return;
    }

    shalomAgencyList.innerHTML = agencies.map((agency, index) => `
        <button
            type="button"
            data-shalom-agency-option
            data-name="${escapeHtml(agency.name || '')}"
            data-address="${escapeHtml(agency.address || '')}"
            data-zone="${escapeHtml(agency.zone || '')}"
            data-province="${escapeHtml(agency.province || '')}"
            data-department="${escapeHtml(agency.department || '')}"
            class="flex w-full items-center gap-4 rounded-lg border bg-white px-4 py-4 text-left transition hover:border-red-300 ${index === 0 ? 'border-red-300 bg-red-50/30' : 'border-slate-100'}"
            aria-pressed="${index === 0 ? 'true' : 'false'}"
        >
            <span class="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-red-600 text-[10px] font-black italic text-white">SHALOM</span>
            <span class="min-w-0 flex-1">
                <span class="flex items-center gap-3">
                    <span class="block truncate text-sm font-black">${escapeHtml(agency.name || 'Agencia Shalom')}</span>
                    ${agency.badge ? `<span class="rounded-md bg-slate-100 px-2 py-1 text-[11px] font-black">${escapeHtml(agency.badge)}</span>` : ''}
                </span>
                <span class="mt-2 block truncate text-xs font-medium text-slate-600">${escapeHtml(agency.address || '')}</span>
            </span>
        </button>
    `).join('');

    shalomAgencyList.querySelectorAll('[data-shalom-agency-option]').forEach((option) => {
        option.addEventListener('click', () => selectShalomAgencyOption(option));
    });

    selectShalomAgencyOption(shalomAgencyList.querySelector('[data-shalom-agency-option]'));
};

const fetchShalomAgencies = () => {
    if (!shalomAgencyList?.dataset.url) {
        return;
    }

    const location = getStoredCheckoutLocation();
    const params = new URLSearchParams({
        address: location.address || '',
        departamento: location.departamento || '',
        provincia: location.provincia || '',
        distrito: location.distrito || '',
        lat: location.lat || '',
        lng: location.lng || '',
        q: shalomAgencySearch?.value || '',
    });

    shalomAgencyList.innerHTML = `
        <div class="rounded-lg border border-slate-100 bg-white px-4 py-6 text-center text-sm font-bold text-slate-500">
            Cargando agencias...
        </div>
    `;

    window.fetch(`${shalomAgencyList.dataset.url}?${params.toString()}`, {
        headers: { Accept: 'application/json' },
    })
        .then((response) => response.json())
        .then((data) => {
            shalomAgenciesLoaded = true;
            renderShalomAgencies(data.agencies || []);
        })
        .catch(() => {
            shalomAgencyList.innerHTML = `
                <div class="rounded-lg border border-slate-100 bg-white px-4 py-6 text-center text-sm font-bold text-slate-500">
                    No se pudieron cargar las agencias.
                </div>
            `;
        });
};

const initShalomAgencyMap = () => {
    if (!shalomAgencyMapElement || shalomAgencyMap || !window.google?.maps) {
        return;
    }

    const location = getStoredCheckoutLocation();
    const center = {
        lat: Number(location.lat || -12.0464),
        lng: Number(location.lng || -77.0428),
    };

    shalomAgencyMapLoading?.classList.add('hidden');
    shalomAgencyGeocoder = new window.google.maps.Geocoder();
    shalomAgencyMap = new window.google.maps.Map(shalomAgencyMapElement, {
        center,
        zoom: 15,
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: true,
    });

    shalomAgencyMarker = new window.google.maps.Marker({
        position: center,
        map: shalomAgencyMap,
        title: 'Agencia Shalom',
    });
};

productZoomOpen?.addEventListener('click', () => {
    const currentImage = document.querySelector('[data-product-card-image]');

    if (productZoomImage && currentImage?.getAttribute('src')) {
        productZoomImage.setAttribute('src', currentImage.getAttribute('src'));
    }

    productZoomModal?.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
});

productZoomClose?.addEventListener('click', closeProductZoom);
productLensToggle?.addEventListener('click', () => {
    const image = productLensFrame?.querySelector('[data-product-card-image]');
    isProductLensActive = !isProductLensActive;
    productLensToggle.setAttribute('aria-pressed', String(isProductLensActive));
    productLensToggle.classList.toggle('ring-2', isProductLensActive);
    productLensToggle.classList.toggle('ring-slate-950', isProductLensActive);
    productLensFrame?.classList.toggle('cursor-zoom-in', !isProductLensActive);
    productLensFrame?.classList.toggle('cursor-zoom-out', isProductLensActive);

    if (!isProductLensActive && image) {
        image.style.transform = '';
        image.style.transformOrigin = '';
    }
});

productLensFrame?.addEventListener('mousemove', (event) => {
    if (!isProductLensActive) {
        return;
    }

    const image = productLensFrame.querySelector('[data-product-card-image]');
    const rect = productLensFrame.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width) * 100;
    const y = ((event.clientY - rect.top) / rect.height) * 100;

    if (image) {
        image.style.transformOrigin = `${x}% ${y}%`;
        image.style.transform = 'scale(2.15)';
    }
});

productLensFrame?.addEventListener('mouseleave', () => {
    const image = productLensFrame.querySelector('[data-product-card-image]');

    if (image) {
        image.style.transform = '';
        image.style.transformOrigin = '';
    }
});
sizeGuideOpen?.addEventListener('click', () => {
    sizeGuideModal?.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
});
sizeGuideClose?.addEventListener('click', closeSizeGuide);

locationOpen?.addEventListener('click', () => {
    locationModal?.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    initCheckoutLocationMap();

    if (checkoutLocationMap) {
        window.google.maps.event.trigger(checkoutLocationMap, 'resize');
        checkoutLocationMap.setCenter(checkoutLocationPosition);
    }

    window.setTimeout(() => locationSearch?.focus(), 80);
});

locationCloseButtons.forEach((button) => button.addEventListener('click', closeLocationModal));

shalomAgencyOpen?.addEventListener('click', () => {
    shalomAgencyModal?.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    initShalomAgencyMap();

    if (shalomAgencyMap && selectedShalomAgency) {
        window.google.maps.event.trigger(shalomAgencyMap, 'resize');
        shalomAgencyMap.setCenter({ lat: selectedShalomAgency.lat, lng: selectedShalomAgency.lng });
    }

    if (!shalomAgenciesLoaded) {
        fetchShalomAgencies();
    }

    window.setTimeout(() => shalomAgencySearch?.focus(), 80);
});

shalomAgencyCloseButtons.forEach((button) => button.addEventListener('click', closeShalomAgencyModal));

locationSearchClear?.addEventListener('click', () => {
    if (locationSearch) {
        locationSearch.value = '';
        locationSearch.focus();
    }
});

shalomAgencySearchClear?.addEventListener('click', () => {
    if (shalomAgencySearch) {
        shalomAgencySearch.value = '';
        shalomAgencySearch.focus();
        fetchShalomAgencies();
    }
});

shalomAgencySearch?.addEventListener('input', () => {
    window.clearTimeout(shalomAgencySearch.searchTimeout);
    shalomAgencySearch.searchTimeout = window.setTimeout(fetchShalomAgencies, 250);
});

shalomAgencyConfirm?.addEventListener('click', () => {
    if (selectedShalomAgency) {
        if (shalomSelectedName) {
            shalomSelectedName.textContent = selectedShalomAgency.name;
        }

        if (shalomSelectedAddress) {
            shalomSelectedAddress.textContent = selectedShalomAgency.address;
        }
    }

    closeShalomAgencyModal();
});

locationConfirm?.addEventListener('click', () => {
    if (selectedLocationValue && selectedLocationLabel) {
        selectedLocationValue.value = selectedLocationLabel.textContent.trim();
    }

    fillCheckoutLocationFields();
    closeLocationModal();
});

window.addEventListener('checkout-location-map-ready', initCheckoutLocationMap);
window.addEventListener('checkout-agency-map-ready', initShalomAgencyMap);

productZoomModal?.addEventListener('click', (event) => {
    if (event.target === productZoomModal) {
        closeProductZoom();
    }
});

sizeGuideModal?.addEventListener('click', (event) => {
    if (event.target === sizeGuideModal) {
        closeSizeGuide();
    }
});

locationModal?.addEventListener('click', (event) => {
    if (event.target === locationModal) {
        closeLocationModal();
    }
});

shalomAgencyModal?.addEventListener('click', (event) => {
    if (event.target === shalomAgencyModal) {
        closeShalomAgencyModal();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeProductZoom();
        closeSizeGuide();
        closeLocationModal();
        closeShalomAgencyModal();
        closeCart();
    }
});

document.querySelectorAll('[data-qty-input]').forEach((input) => {
    const wrapper = input.closest('div');
    const decrement = wrapper?.querySelector('[data-qty-dec]');
    const increment = wrapper?.querySelector('[data-qty-inc]');

    decrement?.addEventListener('click', () => {
        input.value = String(Math.max(1, Number(input.value || 1) - 1));
        const cartQty = input.closest('article')?.querySelector('[data-cart-qty]');
        if (cartQty) {
            cartQty.value = input.value;
        }
    });

    increment?.addEventListener('click', () => {
        input.value = String(Number(input.value || 1) + 1);
        const cartQty = input.closest('article')?.querySelector('[data-cart-qty]');
        if (cartQty) {
            cartQty.value = input.value;
        }
    });
});

const bannerForm = document.querySelector('[data-banner-form]');

document.querySelectorAll('[data-banner-form-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        bannerForm?.classList.toggle('hidden');
        bannerForm?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});

document.querySelectorAll('[data-banner-edit-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const form = document.getElementById(button.dataset.bannerEditToggle);
        form?.classList.toggle('hidden');
        form?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });
});

document.querySelectorAll('[data-banner-button-preview]').forEach((preview) => {
    preview.querySelectorAll('[data-banner-button-drag]').forEach((button) => {
        const form = preview.closest('article')?.querySelector('form[id^="banner-edit-"]');
        const index = button.dataset.bannerButtonDrag;
        const inputX = form?.querySelector(`[data-banner-button-x="${index}"]`);
        const inputY = form?.querySelector(`[data-banner-button-y="${index}"]`);

        const moveButton = (event) => {
            const rect = preview.getBoundingClientRect();
            const x = Math.min(100, Math.max(0, ((event.clientX - rect.left) / rect.width) * 100));
            const y = Math.min(100, Math.max(0, ((event.clientY - rect.top) / rect.height) * 100));

            button.style.left = `${x}%`;
            button.style.top = `${y}%`;

            if (inputX) {
                inputX.value = x.toFixed(2);
            }

            if (inputY) {
                inputY.value = y.toFixed(2);
            }
        };

        button.addEventListener('pointerdown', (event) => {
            event.preventDefault();
            button.setPointerCapture(event.pointerId);
            moveButton(event);
        });

        button.addEventListener('pointermove', (event) => {
            if (button.hasPointerCapture(event.pointerId)) {
                moveButton(event);
            }
        });

        button.addEventListener('pointerup', (event) => {
            if (button.hasPointerCapture(event.pointerId)) {
                button.releasePointerCapture(event.pointerId);
            }
        });
    });
});

const comboForm = document.querySelector('[data-combo-form]');

document.querySelectorAll('[data-combo-form-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        comboForm?.classList.toggle('hidden');
        comboForm?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});

const paymentForm = document.querySelector('[data-payment-form]');

document.querySelectorAll('[data-payment-form-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        paymentForm?.classList.toggle('hidden');
        paymentForm?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});

document.querySelectorAll('[data-payment-edit-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const paymentId = button.dataset.paymentEditToggle;
        const editRow = document.querySelector(`[data-payment-edit-row="${paymentId}"]`);

        document.querySelectorAll('[data-payment-edit-row]').forEach((row) => {
            if (row !== editRow) {
                row.classList.add('hidden');
            }
        });

        editRow?.classList.toggle('hidden');
        editRow?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
});

const combosMenu = document.querySelector('[data-combos-menu]');
const combosToggles = document.querySelectorAll('[data-combos-toggle]');
const combosChevron = document.querySelector('[data-combos-chevron]');

const closeCombosMenu = () => {
    if (combosMenu) {
        combosMenu.dataset.state = 'closed';
    }
    combosChevron?.classList.remove('rotate-180');
    combosToggles.forEach((toggle) => toggle.setAttribute('aria-expanded', 'false'));
};

const openCombosMenu = () => {
    if (combosMenu) {
        combosMenu.dataset.state = 'open';
    }
    combosChevron?.classList.add('rotate-180');
    combosToggles.forEach((toggle) => toggle.setAttribute('aria-expanded', 'true'));
};

combosToggles.forEach((toggle) => {
    toggle.addEventListener('click', (event) => {
        event.stopPropagation();
        const isOpen = toggle.getAttribute('aria-expanded') === 'true';
        isOpen ? closeCombosMenu() : openCombosMenu();
    });
});

combosMenu?.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeCombosMenu));
combosMenu?.addEventListener('click', (event) => event.stopPropagation());
document.addEventListener('click', closeCombosMenu);
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeCombosMenu();
    }
});
