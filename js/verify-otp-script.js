/**
 * OTP Verification Page Script
 * 
 * This script detects when a user is on the OTP verification page
 * and outputs a message to the console.
 */

(function() {
    // Wait for the DOM to be fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Check if we're on the verification page by looking for specific elements
        const h3Elements = document.querySelectorAll('h3');
        let isVerifyPage = false;
        
        // Check if any h3 element contains "Verify OTP" text
        h3Elements.forEach(function(heading) {
            if (heading.textContent.includes('Verify OTP')) {
                isVerifyPage = true;
            }
        });
        
        const otpForm = document.getElementById('otp_form');
        const otpInput = document.querySelector('input[name="otp_token"]');
        
        // If any of these elements exist, we're likely on the verify page
        if (isVerifyPage || otpForm || otpInput) {
            console.log("We are in the verify page");
        }
    });
    
    // jQuery version (as a fallback if jQuery is available)
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(function($) {
            if ($('h3:contains("Verify OTP")').length || 
                $('#otp_form').length || 
                $('input[name="otp_token"]').length) {
               
            }
        });
    }
    console.log("We are in the verify page");
})(); 
console.log("We are in the verify page");
