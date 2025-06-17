jQuery( document ).ready(function($) {
   
    setTimeout(function() {
        if ($('#price-popup-form').length) {
            $.magnificPopup.open({
                items: {
                    src: '#price-popup-form' 
                },
                type: 'inline',
                closeOnBgClick:false,
                enableEscapeKey: false,
                showCloseBtn:false
              });
        }
    }, 100);
    $('input[type=radio][name=location_price]').click(function() {
        if ($(this).is(':checked')) {
            $("#price-popup-form").submit();
        }
    });
    $(".location_price").on("change", function(){
         $("#header_location").submit();
    });
    $("body").on("focus", "#shipping_date", function(){
        $("#shipping_date").datepicker({
            dateFormat: 'dd-mm-yy',
            minDate: '+1d',
             beforeShowDay: noSunday, 
        });
       
          function noSunday(date){ 
             return [date.getDay() != 0, ''];
          }; 
    });

     $("body").on("change", "#shipping_date", function(){
        
        var pickupdate = $(this).val();
        // console.log(pickupdate);
        $.ajax({
            url : myAjax.ajaxurl,
            type : 'post',
            data : {
                action : 'update_pickup_date_session',
                pickupdate : pickupdate,
            },
            success : function( response ) {
                return true;
            },error: function (error) {
                console.log('error: ' + eval(error));
            }
        });
    }); 
   
    var $variationForm = $('#variations_form');

    $('.variations_form select').on('change', function() {
        var productVariations = JSON.parse($(".variations_form").attr("data-product_variations"));
        console.log(productVariations);
        // Fetch and update the prices dynamically here
        // This could involve an AJAX request to retrieve updated prices for the selected variation
        // Update the displayed prices on the page
    });

    // Location selector submit on change
    $(document).on('change', '.nbt-location-selector', function() {
        $('#nbt-location-selector-form').submit();
    });

    // --- Addon enable/disable logic for variable products ---
    function setAddonsEnabled(enabled) {
        // Target all addon input fields inside .yith-wapo-addon
        var $addonFields = $('.yith-wapo-addon :input, .yith-wapo-addon select, .yith-wapo-addon textarea');
        $addonFields.prop('disabled', !enabled);
        // Show/hide overlay message
        if (!enabled) {
            if ($('#nbt-addon-overlay').length === 0) {
                var overlay = $('<div id="nbt-addon-overlay" style="position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,0.7);z-index:10;display:flex;align-items:center;justify-content:center;"><span style="font-size:1.1em;color:#444;background:#fff;padding:12px 24px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);">Please select a variation to enable addons.</span></div>');
                // Position overlay over the addons container
                var $container = $('#yith-wapo-container');
                $container.css('position', 'relative');
                $container.append(overlay);
            }
        } else {
            $('#nbt-addon-overlay').remove();
        }
    }

    function isVariationSelected() {
        // WooCommerce sets a hidden input with name="variation_id" when a variation is selected
        var $form = $('.variations_form');
        var variationId = $form.find('input[name="variation_id"]').val();
        return variationId && variationId !== '' && variationId !== '0';
    }

    // Only apply for variable products with addons
    if ($('.variations_form').length && $('#yith-wapo-container').length) {
        // On page load
        setAddonsEnabled(isVariationSelected());
        // On variation selection
        $('.variations_form').on('found_variation hide_variation reset_data', function(e) {
            setAddonsEnabled(isVariationSelected());
        });
    }
});