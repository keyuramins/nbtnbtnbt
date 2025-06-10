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
    //  $('#shipping_date').datepicker({
    //     dateFormat: 'dd-mm-yy',
    //      minDate: '+15d', // Set the minimum date to today
    // });
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

    // Admin: Change variation price field labels to use default location name
    if (typeof nbtDefaultLocation !== 'undefined' && nbtDefaultLocation) {
        function updateVariationPriceLabels() {
            var defaultLocation = nbtDefaultLocation;
            // For each variation panel
            $(".woocommerce_variation").each(function() {
                // Regular price
                $(this).find('label[for^="variable_regular_price_"]').each(function() {
                    var label = $(this);
                    label.text(defaultLocation + ' Price ($)');
                });
                // Sale price
                $(this).find('label[for^="variable_sale_price_"]').each(function() {
                    var label = $(this);
                    label.text(defaultLocation + ' Sale Price ($)');
                });
            });
        }
        // Initial run
        updateVariationPriceLabels();
        // Also run after variations are loaded/changed
        $(document).on('woocommerce_variations_loaded woocommerce_variations_added', updateVariationPriceLabels);
    }
});