/**
 * Jeilo CDW - Custom JavaScript
 * Handles registration form, Paystack payments, and UI interactions.
 */

document.addEventListener('DOMContentLoaded', function () {

    // ---- Registration Form (AJAX) ----
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

            const formData = new FormData(this);

            fetch('register-handler.php', {
                method: 'POST',
                body: formData,
            })
            .then(response => response.text())
            .then(text => {
                let data;
                try {
                    data = JSON.parse(text);
                } catch (err) {
                    console.error('Non-JSON response:', text);
                    throw new Error('Invalid server response');
                }

                if (data.success) {
                    if (data.is_paid && data.auth_url) {
                        // Redirect to Paystack
                        window.location.href = data.auth_url;
                    } else {
                        // Free event - show success
                        document.getElementById('registrationForm').style.display = 'none';
                        const successDiv = document.getElementById('registrationSuccess');
                        successDiv.style.display = 'block';
                        document.getElementById('successMessage').textContent = data.message || 'Registration successful!';

                        if (data.event_slug) {
                            document.cookie = 'jcdw_reg_' + formData.get('event_id') + '=1; max-age=' + (86400 * 30) + '; path=/';
                        }
                    }
                } else {
                    const errorDiv = document.getElementById('registrationError');
                    const errorMessage = document.getElementById('errorMessage');
                    errorMessage.textContent = data.message || 'Registration failed. Please try again.';
                    errorDiv.style.display = 'block';
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const errorDiv = document.getElementById('registrationError');
                const errorMessage = document.getElementById('errorMessage');
                errorMessage.textContent = 'An error occurred. Please try again.';
                errorDiv.style.display = 'block';
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }

    // ---- Form Validation Enhancement ----
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // ---- Auto-dismiss alerts after 5 seconds ----
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });

});
