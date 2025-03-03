define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/shipping-rates-validator',
        'Magento_Checkout/js/model/shipping-rates-validation-rules',
        '../model/shipping-rates-validator',
        '../model/shipping-rates-validation-rules'
    ],
    function (
        Component,
        defaultShippingRatesValidator,
        defaultShippingRatesValidationRules,
        openFreightshippingRatesValidator,
        openFreightshippingRatesValidationRules
    ) {
        'use strict';
        defaultShippingRatesValidator.registerValidator('tig', openFreightshippingRatesValidator);
        defaultShippingRatesValidationRules.registerRules('tig', openFreightshippingRatesValidationRules);
        return Component;
    }
);