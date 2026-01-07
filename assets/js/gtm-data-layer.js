/**
 * Pushes a generic event to the Google Tag Manager dataLayer.
 * @param {string} eventName - The name of the event (e.g., 'lead_generated', 'user_registered').
 * @param {object} [eventData] - Additional event data.
 */
function pushGTMEvent(eventName, eventData = {}) {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
        event: eventName,
        ...eventData
    });
    console.log(`GTM Event Pushed: ${eventName}`, eventData);
}

/**
 * Pushes an e-commerce event to the Google Tag Manager dataLayer following GA4's ecommerce schema.
 * @param {string} eventName - The GA4 ecommerce event name (e.g., 'add_to_cart', 'purchase').
 * @param {object} ecommerceData - The ecommerce object (items, value, currency, etc.).
 */
function pushGTMEcommerceEvent(eventName, ecommerceData) {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
        event: eventName,
        ecommerce: ecommerceData
    });
    console.log(`GTM Ecommerce Event Pushed: ${eventName}`, ecommerceData);
}

 /**
 * Formats items for Meta Pixel's 'contents' property.
 * @param {Array} ga4Items - Array of GA4-style items.
 * @returns {Array} Array formatted for Meta Pixel contents.
 */
function formatItemsForMeta(ga4Items) {
    if (!Array.isArray(ga4Items)) return [];
    return ga4Items.map(item => ({
        id: item.item_id,
        quantity: item.quantity,
        item_price: item.price
    }));
}

/**
 * Extracts item_ids for Meta Pixel's 'content_ids' property.
 * @param {Array} ga4Items - Array of GA4-style items.
 * @returns {Array} Array of item_ids.
 */
function getItemIdsForMeta(ga4Items) {
    if (!Array.isArray(ga4Items)) return [];
    return ga4Items.map(item => item.item_id);
}