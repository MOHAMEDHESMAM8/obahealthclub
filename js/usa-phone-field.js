/**
 * USA Phone Field for PMPro Forms - OBA Health Club Implementation
 * Version: 3.1
 * Specific for: https://obahealthclub.com/membership-checkout/?level=4
 */
(function($) {
    // Only run on the specific checkout page
    if (window.location.href.indexOf('membership-checkout') === -1 || 
        window.location.href.indexOf('level=4') === -1) {
        return;
    }
    
    console.log('OBA Health Club Phone Field: Script loaded');
    
    // Wait until DOM is fully loaded and all scripts are loaded
    $(window).on('load', function() {
        // Use a timeout to ensure this runs after all other scripts
        setTimeout(initPhoneField, 500);
    });
    
    // Also try on document ready (backup)
    $(document).ready(function() {
        initPhoneField();
    });
    
    // Main initialization function
    function initPhoneField() {
        console.log('OBA Health Club: Initializing USA Phone Field');
        
        // First, clean up any existing implementations
        cleanupPhoneFields();
        
        // Then add or initialize the phone field
        setupPhoneField();
    }
    
    // Clean up any existing fields and intl-tel-input instances
    function cleanupPhoneFields() {
        console.log('OBA Health Club: Cleaning up existing phone fields');
        
        // First, destroy any existing intl-tel-input instances
        if ($('#phone_paidmembership').length > 0) {
            if (typeof $('#phone_paidmembership').data('iti') !== 'undefined') {
                $('#phone_paidmembership').data('iti').destroy();
                $('#phone_paidmembership').removeData('iti');
            }
            
            // Alternative method if the above doesn't work
            if ($('#phone_paidmembership').closest('.intl-tel-input').length) {
                var $input = $('#phone_paidmembership');
                var $parent = $input.parent();
                $input.insertAfter($parent);
                $parent.remove();
            }
        }
        
        // Remove any duplicate containers
        $('.intl-tel-input .intl-tel-input').each(function() {
            var $child = $(this);
            var contents = $child.contents();
            $child.replaceWith(contents);
        });
        
        // Remove duplicate flag containers
        $('.flag-container').each(function(index) {
            if (index > 0) {
                $(this).remove();
            }
        });
    }
    
    // Setup the phone field or initialize an existing one
    function setupPhoneField() {
        console.log('OBA Health Club: Setting up phone field');
        
        // Add the field if it doesn't exist
        $('[id$="phone_type"]').each(function() {
            if ($('#phone_paidmembership').length === 0) {
                $('<div class="pmpro_form_field pmpro_form_field-required">' +
                  '<label for="phone_paidmembership" class="pmpro_form_label">Phone <span class="pmpro_asterisk"> <abbr title="Required Field">*</abbr></span></label>' +
                  '<input id="phone_paidmembership" size="30" class="pmpro_form_input pmpro_form_input-required" style="width:100%;" name="phone_paidmembership" required="true" type="text" autocomplete="off" placeholder="(201) 555-0123">' +
                  '<input id="country_code" name="country_code" type="hidden" value="1">' +
                  '</div>').insertAfter(this);
            }
        });
        
        // Only proceed if the element exists
        var $phoneInput = $('#phone_paidmembership');
        if ($phoneInput.length === 0) {
            console.log('OBA Health Club: Phone input element not found');
            return;
        }
        
        // Make sure the element isn't already wrapped in intl-tel-input
        if ($phoneInput.closest('.intl-tel-input').length > 0) {
            cleanupPhoneFields(); // Clean up again just to be safe
        }
        
        // Check if intlTelInput library is available
        if (typeof window.intlTelInput !== 'function') {
            // If not available, load it dynamically
            console.log('OBA Health Club: Loading intlTelInput library');
            
            loadScript('https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/js/intlTelInput.min.js', function() {
                loadScript('https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/js/utils.js', function() {
                    // Now that libraries are loaded, initialize the phone input
                    initializePhoneInput($phoneInput);
                });
            });
            
            // Also load CSS
            loadCSS('https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/css/intlTelInput.min.css');
        } else {
            // If already available, initialize directly
            console.log('OBA Health Club: intlTelInput library already loaded');
            initializePhoneInput($phoneInput);
        }
        
        // Form validation
        var $form = $phoneInput.closest('form');
        $form.off('submit.phoneValidation').on('submit.phoneValidation', function(e) {
            var phoneValue = $phoneInput.val().replace(/\D/g, '');
            
            if (phoneValue.length !== 10) {
                e.preventDefault();
                alert('Please enter a valid 10-digit US phone number.');
                $phoneInput.focus();
                return false;
            }
            
            // If we get here, format the phone properly for submission
            var formattedPhone = phoneValue.replace(/(\d{3})(\d{3})(\d{4})/, '$1-$2-$3');
            $phoneInput.val(formattedPhone);
        });
    }
    
    // Initialize the phone input field with intlTelInput
    function initializePhoneInput($phoneInput) {
        console.log('OBA Health Club: Initializing intlTelInput');
        
        var iti = window.intlTelInput($phoneInput[0], {
            onlyCountries: ['us'],
            initialCountry: 'us',
            preferredCountries: ['us'],
            separateDialCode: false, // Changed to false to hide the dial code
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/js/utils.js"
        });
        
        // Store the instance for later reference
        $phoneInput.data('iti', iti);
        
        // Remove any existing event handlers and add US phone formatting
        $phoneInput.off('input').on('input', function() {
            let value = this.value.replace(/\D/g, '');
            
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            
            // Format as (xxx) xxx-xxxx
            if (value.length > 0) {
                if (value.length <= 3) {
                    value = value;
                } else if (value.length <= 6) {
                    value = '(' + value.substring(0, 3) + ') ' + value.substring(3);
                } else {
                    value = '(' + value.substring(0, 3) + ') ' + 
                            value.substring(3, 6) + '-' + 
                            value.substring(6, 10);
                }
            }
            
            this.value = value;
        });
        
        // Apply modifications after phone field is initialized
        hideDialCodeAndArrow();
    }
    
    // Function to hide the dial code and arrow
    function hideDialCodeAndArrow() {
        // Hide the selected dial code
        $('.selected-dial-code').css('display', 'none');
        
        // Hide the arrow
        $('.iti-arrow').css('display', 'none');
        
        // Adjust padding since we're hiding the dial code
        $('#phone_paidmembership').css('padding-left', '52px');
    }
    
    // Helper function to load scripts dynamically
    function loadScript(url, callback) {
        var script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = url;
        script.onload = callback;
        document.head.appendChild(script);
    }
    
    // Helper function to load CSS dynamically
    function loadCSS(url) {
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.type = 'text/css';
        link.href = url;
        document.head.appendChild(link);
    }
    
    // Add CSS to hide the country list dropdown and other elements
    var style = document.createElement('style');
    style.textContent = `
        .country-list {
            display: none !important;
        }
        .selected-dial-code {
            display: none !important;
        }
        .iti-arrow {
            display: none !important;
        }
        .iti__flag-container {
            left: 0;
        }
    `;
    document.head.appendChild(style);
})(jQuery);