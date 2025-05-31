jQuery(document).ready(function($) {

    // Add validation rules for classes
    $.validator.addClassRules('validate-location', {
        required: true,
        minlength: 2
    });

    $.validator.addClassRules('validate-address', {
        required: true,
        minlength: 5
    });

    $.validator.addClassRules('validate-email', {
        required: true,
        email: true
    });

    function isDuplicateLocation(locationName) {
        var duplicate = false;
        $('.wp-list-table tbody tr').each(function() {
            var existingLocation = $(this).find('input[name$="[location]"]').val().trim().toLowerCase();
            if (existingLocation === locationName.toLowerCase()) {
                duplicate = true;
                return false; // break loop
            }
        });
        return duplicate;
    }

    function saveLocation(locations, action = 'save', oldName = '') {
        if (locations === '') {
            return;
        }

        $.ajax({
            url: nbtSettings.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: action === 'add' ? 'add_nbt_location' : 'edit_nbt_location',
                locations: locations,
                nonce: nbtSettings.nonce,
            },
            success: function(response) {
                if (response.success) {
                    if (action === 'add') {
                        var nbtLocationsCount = $('.wp-list-table tbody tr').length;
                        var newRow = `<tr data-location="${response.data.location}" data-index="${nbtLocationsCount}">
                            <td><input type="text" value="${response.data.location}" class="validate-location" name="nbt_location[${nbtLocationsCount}][location]" required/></td>
                            <td><input type="text" value="${response.data.address}" class="validate-address" name="nbt_location[${nbtLocationsCount}][address]" required /></td>
                            <td><input type="text" value="${response.data.email}" class="validate-email" name="nbt_location[${nbtLocationsCount}][email]" required /></td>
                            <td>
                                <a type="button" class="remove-location-btn">
                                    <span class="dashicons dashicons-trash"></span>
                                </a>
                            </td>
                        </tr>`;
                        $('#location-table tbody').append(newRow);

                        $('#nbt-default-location').append(`<option value="${response.data.location}">${response.data.location}</option>`);
                        $(".save-btn").attr("disabled", false);
                        $(".notice").remove();
                        $('#location-table').before('<div class="notice notice-success is-dismissible"><p>Location Added successfully</p></div>');
                        $('#nbt-new-location').val('');
                        $('#nbt-new-address').val('');
                        $('#nbt-new-email').val('');
                    } else if (action === 'edit') {
                        $(".notice").remove();
                        $('#location-table').before('<div class="notice notice-success is-dismissible"><p>Location Updated successfully</p></div>');
                        // Update the default location dropdown with the new names
                        var $defaultSelect = $('#nbt-default-location');
                        $defaultSelect.empty();
                        $.each(locations, function(index, loc) {
                            if(loc.location) {
                                $defaultSelect.append('<option value="'+loc.location+'">'+loc.location+'</option>');
                            }
                        });
                        // Set the correct default location as selected
                        if (response.data && response.data.default_location) {
                            $defaultSelect.val(response.data.default_location).trigger('change');
                        }
                    }
                } else {
                    alert("update Ajax error:" + response.error);
                }
            }
        });
    }

    $('#nbt-default-location').on('change', function() {
        var defaultLocation = $(this).val();
        $.ajax({
            url: nbtSettings.ajax_url,
            type: 'POST',
            data: {
                action: 'save_nbt_default_location',
                default_location: defaultLocation,
                nonce: nbtSettings.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#location-table').before('<div class="notice notice-success is-dismissible"><p>Default location saved successfully.</p></div>');
                } else {
                    alert(response.data);
                }
            }
        });
    });

    $('#add-location-form').validate({
        rules: {
            location: {
                required: true,
                minlength: 3
            },
            email: {
                required: true,
                email: true
            },
            address: {
                required: true,
                minlength: 5,
                maxlength: 100
            }
        },
        messages: {
            location: {
                required: "Location name is required.",
                minlength: "Location name must be at least 3 characters long."
            },
            email: {
                required: "Email is required.",
                email: "Please enter a valid email address."
            },
            address: {
                required: "Address is required.",
                minlength: "Address must be at least 5 characters long.",
                maxlength: "Address cannot exceed 100 characters."
            }
        },
        submitHandler: function(form) {
            var locationName = $('#nbt-new-location').val().trim();
            if (isDuplicateLocation(locationName)) {
                alert('Location name already exists!');
                return false;
            }
            var locations = {
                'location': locationName,
                'address': $('#nbt-new-address').val().trim(),
                'email': $('#nbt-new-email').val().trim()
            };
            saveLocation(locations, 'add');
        }
    });

    $('#location-form').validate({
        ignore: [],
        errorPlacement: function(error, element) {
            error.insertAfter(element);
        },
        submitHandler: function(form) {
            var arr = $('.wp-list-table tbody tr').find('input[name^="nbt_location"]').serializeArray();
            var nbtLocations = {};
            var locationNames = [];
            var duplicateFound = false;

            $.each(arr, function(index, field) {
                var nameParts = field.name.match(/nbt_location\[(\d+)\]\[(\w+)\]/);
                if (nameParts) {
                    var indexKey = nameParts[1];
                    var fieldName = nameParts[2];

                    if (!nbtLocations[indexKey]) {
                        nbtLocations[indexKey] = {};
                    }
                    nbtLocations[indexKey][fieldName] = field.value;

                    if (fieldName === 'location') {
                        if (locationNames.includes(field.value.trim().toLowerCase())) {
                            duplicateFound = true;
                        } else {
                            locationNames.push(field.value.trim().toLowerCase());
                        }
                    }
                }
            });

            if (duplicateFound) {
                alert('Duplicate location names found. Please ensure all location names are unique.');
                return false;
            }

            saveLocation(nbtLocations, 'edit');
        }
    });

    $(document).on('click', '.remove-location-btn', function() {
        var $row = $(this).closest('tr');
        var name = $row.find('input[name^="nbt_location"]').val();

        if (confirm('Are you sure you want to remove this location?')) {
            $.ajax({
                url: nbtSettings.ajax_url,
                type: 'POST',
                data: {
                    action: 'remove_nbt_location',
                    location: name,
                    nonce: nbtSettings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $row.remove();
                        $('#nbt-default-location option[value="' + name + '"]').remove();
                        $('#location-table').before('<div class="notice notice-success is-dismissible"><p>Location deleted successfully</p></div>');
                        if ($('#location-table tbody tr').length < 1) {
                            $(".save-btn").attr("disabled", true);
                        }
                    } else {
                        alert(response.data);
                    }
                }
            });
        }
    });
});
