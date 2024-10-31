jQuery(document).ready(function($) {
    // Triggered when the product attribute changes
    $(document).on('change', 'select[data-setting="product_attributes"]', function() {
        var attribute_name = $(this).val();

        $.ajax({
            url: ibtgWooElementor.ajaxurl,
            method: 'POST',
            data: {
                'action': 'get_attribute_terms',
                'attribute_name': attribute_name,
                'nonce': ibtgWooElementor.nonce // Include the nonce in the AJAX request for security
            },
            success: function(response) {
                // Validate response format
                if (typeof response !== 'object' || response === null) {
                    console.error('Invalid response format');
                    return;
                }

                var terms = response; // Assuming response is the expected JSON object
                var termsControl = $('select[data-setting="attribute_terms"]');

                // Clear previous options and append new ones
                termsControl.empty();
                $.each(terms, function(slug, name) {
                    // HTML escaping is handled by jQuery's text() method
                    termsControl.append($('<option></option>').attr('value', slug).text(name));
                });

                // Refresh the control to show new values
                termsControl.trigger('change');
            },
            error: function(jqXHR, textStatus, errorThrown) {
                // Log error to console
                console.error("AJAX request failed: " + textStatus + ', ' + errorThrown);
                // Implement user-friendly error handling/display here if needed
            }
        });
    });
});
