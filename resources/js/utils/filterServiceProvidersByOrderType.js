/**
 * Service providers eligible for an order type: waste orders need waste_collection or
 * general; recycling orders need recycling or general. Any other/unset order type passes all.
 */
export function filterServiceProvidersByOrderType(providers, orderType) {
    if (!providers?.length) {
        return [];
    }
    return providers.filter((provider) => {
        const providerTypes = provider.types || [];
        if (orderType === 'waste') {
            return providerTypes.some((type) => ['waste_collection', 'general'].includes(type));
        }
        if (orderType === 'recycling') {
            return providerTypes.some((type) => ['recycling', 'general'].includes(type));
        }
        return true;
    });
}
