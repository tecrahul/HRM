<script>
    (() => {
        const forms = document.querySelectorAll('form[data-inline-validation]');
        if (forms.length === 0) {
            return;
        }

        const humanizeFieldName = (value) => {
            if (typeof value !== 'string' || value.trim() === '') {
                return 'this field';
            }

            return value
                .replace(/[_-]+/g, ' ')
                .replace(/\s+/g, ' ')
                .trim()
                .toLowerCase();
        };

        const labelTextForField = (form, field) => {
            if (!(field instanceof HTMLElement)) {
                return '';
            }

            if (field.id) {
                const label = form.querySelector(`label[for="${field.id}"]`);
                if (label) {
                    return label.textContent?.trim() ?? '';
                }
            }

            return '';
        };

        const ensureErrorElement = (field) => {
            const fieldName = field.getAttribute('name');
            if (!fieldName) {
                return null;
            }

            const existing = field.form?.querySelector(`.client-error[data-client-error-for="${fieldName}"]`);
            if (existing instanceof HTMLElement) {
                return existing;
            }

            const error = document.createElement('p');
            error.className = 'error client-error text-xs text-red-600 mt-1';
            error.dataset.clientErrorFor = fieldName;
            error.hidden = true;

            const wrapper = field.closest('.input-wrap');
            if (wrapper instanceof HTMLElement) {
                wrapper.insertAdjacentElement('afterend', error);
            } else {
                field.insertAdjacentElement('afterend', error);
            }

            return error;
        };

        const validationMessage = (form, field) => {
            const fieldName = field.getAttribute('name') ?? '';
            const labelText = labelTextForField(form, field);
            const humanName = humanizeFieldName(labelText || fieldName);
            const type = (field.getAttribute('type') || '').toLowerCase();
            const value = field.value?.trim?.() ?? '';
            const validity = field.validity;

            if (validity.valueMissing) {
                if (type === 'email') {
                    return 'Please enter your email address.';
                }

                if (fieldName.includes('password_confirmation')) {
                    return 'Please confirm your password.';
                }

                if (type === 'password') {
                    return 'Please enter your password.';
                }

                return `Please enter ${humanName}.`;
            }

            if (validity.typeMismatch && type === 'email') {
                return 'Please enter a valid email address.';
            }

            if (validity.typeMismatch && type === 'url') {
                return 'Please enter a valid URL.';
            }

            if (validity.tooShort) {
                const minLength = field.getAttribute('minlength');
                return minLength
                    ? `Please use at least ${minLength} characters for ${humanName}.`
                    : `Please enter more characters for ${humanName}.`;
            }

            if (validity.tooLong) {
                const maxLength = field.getAttribute('maxlength');
                return maxLength
                    ? `Please use no more than ${maxLength} characters for ${humanName}.`
                    : `Please shorten ${humanName}.`;
            }

            if (validity.patternMismatch) {
                return `Please provide a valid ${humanName}.`;
            }

            if (fieldName.includes('password_confirmation')) {
                const passwordField = form.querySelector('input[name="password"]');
                if (passwordField instanceof HTMLInputElement && passwordField.value && passwordField.value !== value) {
                    return 'Password confirmation does not match.';
                }
            }

            return '';
        };

        const showFieldError = (field, message) => {
            const error = ensureErrorElement(field);
            if (!(error instanceof HTMLElement)) {
                return;
            }

            const hasError = message.trim().length > 0;
            error.textContent = message;
            error.hidden = !hasError;
            field.setAttribute('aria-invalid', hasError ? 'true' : 'false');
        };

        forms.forEach((form) => {
            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            form.setAttribute('novalidate', 'novalidate');

            const inputs = Array.from(form.querySelectorAll('input, select, textarea'))
                .filter((field) => {
                    if (!(field instanceof HTMLElement)) {
                        return false;
                    }

                    if (field instanceof HTMLInputElement && field.type === 'hidden') {
                        return false;
                    }

                    if (field.hasAttribute('disabled')) {
                        return false;
                    }

                    const type = (field.getAttribute('type') || '').toLowerCase();

                    return field.hasAttribute('required')
                        || type === 'email'
                        || type === 'url'
                        || field.hasAttribute('pattern')
                        || field.hasAttribute('minlength')
                        || field.getAttribute('name') === 'password_confirmation';
                });

            let submitAttempted = false;

            const validateField = (field) => {
                const message = validationMessage(form, field);
                showFieldError(field, message);
                return message === '';
            };

            const validateForm = () => {
                let firstInvalidField = null;

                inputs.forEach((field) => {
                    const isValid = validateField(field);
                    if (!isValid && firstInvalidField === null) {
                        firstInvalidField = field;
                    }
                });

                return firstInvalidField;
            };

            inputs.forEach((field) => {
                field.addEventListener('blur', () => {
                    field.dataset.touched = 'true';
                    validateField(field);
                });

                field.addEventListener('input', () => {
                    if (submitAttempted || field.dataset.touched === 'true') {
                        validateField(field);
                    }
                });
            });

            form.addEventListener('submit', (event) => {
                submitAttempted = true;
                const firstInvalidField = validateForm();
                if (firstInvalidField) {
                    event.preventDefault();
                    if (firstInvalidField instanceof HTMLElement) {
                        firstInvalidField.focus();
                    }
                }
            });
        });
    })();
</script>
