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

    // Location-based price update for variable products
    if ($('.variations_form').length) {
        var currentLocation = '';
        if (typeof Cookies !== 'undefined') {
            currentLocation = Cookies.get('location_price') || '';
        } else {
            // fallback: try to get from select
            currentLocation = $('.nbt-location-selector').val() || '';
        }
        function updateVariationPriceDisplay(variation) {
            if (!variation) return;
            var priceHtml = '';
            if (currentLocation && variation['_' + currentLocation + '_price']) {
                var price = variation['_' + currentLocation + '_price'];
                var salePrice = variation['_' + currentLocation + '_sale_price'];
                if (salePrice && salePrice !== '' && salePrice !== '0') {
                    priceHtml = '<del>' + variation.display_price + '</del> <ins>' + salePrice + '</ins>';
                } else {
                    priceHtml = price;
                }
            } else {
                // fallback to default WooCommerce price_html
                priceHtml = variation.price_html;
            }
            // Update the price display
            $('.single_variation .woocommerce-Price-amount, .single_variation .price, .product .price').html(priceHtml);
        }
        $('.variations_form').on('show_variation', function(event, variation) {
            updateVariationPriceDisplay(variation);
        });
        // Also update on location change
        $(document).on('change', '.nbt-location-selector', function() {
            var selectedVariation = $('.variations_form').data('variation_id');
            var productVariations = JSON.parse($('.variations_form').attr('data-product_variations'));
            var found = null;
            for (var i = 0; i < productVariations.length; i++) {
                if (productVariations[i].variation_id == selectedVariation) {
                    found = productVariations[i];
                    break;
                }
            }
            if (found) {
                updateVariationPriceDisplay(found);
            }
        });
    }
});