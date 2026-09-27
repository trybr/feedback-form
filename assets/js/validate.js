(function (global) {
    'use strict';

    const EMAIL_REG = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*\.[a-zA-Z]{2,}$/;
    const PHONE_DIGITS_REG = /^7\d{10}$/;

    const getRow = (field) => field.closest('.form__row');

    const getErrorSlot = (field) => {
        const row = getRow(field);
        return row ? row.querySelector(`[data-error-for="${field.id}"]`) : null;
    };

    const getCheckboxWrap = (field) => field.closest('.checkbox');

    const setError = (field, msg) => {
        field.classList.add('is-invalid');

        const slot = getErrorSlot(field);
        if (slot) slot.textContent = msg || '';

        if (field.type === 'checkbox') {
            const wrap = getCheckboxWrap(field);
            if (wrap) wrap.classList.add('is-invalid');
        }
    };

    const clearError = (field) => {
        field.classList.remove('is-invalid');

        const slot = getErrorSlot(field);
        if (slot) slot.textContent = '';

        if (field.type === 'checkbox') {
            const wrap = getCheckboxWrap(field);
            if (wrap) wrap.classList.remove('is-invalid');
        }
    };

    const getFieldValue = (field) => {
        if (field.type === 'checkbox') return field.checked ? '1' : '';
        return field.value.trim();
    };

    const validateField = (field) => {
        const value = getFieldValue(field);

        // обязательность
        if (field.hasAttribute('required') && !value) {
            setError(field, 'Поле обязательно для заполнения');
            return false;
        }

        // телефон
        if (field.hasAttribute('data-phone') && value) {
            const digits = value.replace(/\D/g, '');
            if (!PHONE_DIGITS_REG.test(digits)) {
                setError(field, 'Введите телефон в формате +7 (___) ___-__-__');
                return false;
            }
        }

        // e-mail
        if (field.hasAttribute('data-email') && value) {
            if (!EMAIL_REG.test(value)) {
                setError(field, 'Введите корректный e-mail');
                return false;
            }
        }

        // длина
        const max = parseInt(field.getAttribute('data-max') ?? '0', 10);
        if (max && value.length > max) {
            setError(field, `Максимум ${max} символов`);
            return false;
        }

        clearError(field);
        return true;
    };

    const validateForm = (form) => {
        const fields = form.querySelectorAll('[required], [data-email], [data-phone], [data-max]');
        const invalid = [];

        fields.forEach((field) => {
            if (!validateField(field)) invalid.push(field);
        });

        return { valid: invalid.length === 0, invalid };
    };

    /* Счётчики */

    const updateCounter = (field, counter, max) => {
        const len = field.value.length;
        counter.textContent = `${len} / ${max}`;
        counter.classList.toggle('is-warning', len >= max - 50);
        counter.classList.toggle('is-danger',  len >= max);
    };

    const initCounters = () => {
        document.querySelectorAll('[data-counter]').forEach((field) => {
            const max = parseInt(field.getAttribute('data-counter'), 10);
            const counter = document.querySelector(`[data-counter-for="${field.id}"]`);
            if (!counter) return;

            const handler = () => updateCounter(field, counter, max);
            field.addEventListener('input', handler);
            handler(); // первичная отрисовка
        });
    };

    /* Валидация */

    const initLiveValidation = () => {
        const form = document.getElementById('feedbackForm');
        if (!form) return;

        form.querySelectorAll('input, select, textarea').forEach((field) => {
            field.addEventListener('blur', () => validateField(field));

            field.addEventListener('input', () => {
                if (field.classList.contains('is-invalid')) validateField(field);
            });

            field.addEventListener('change', () => {
                if (field.type === 'checkbox' || field.tagName === 'SELECT') {
                    validateField(field);
                }
            });
        });
    };

    /* Инициализация */

    document.addEventListener('DOMContentLoaded', () => {
        initLiveValidation();
        initCounters();
    });

    global.FormValidator = {
        validateField,
        validateForm,
        setError,
        clearError
    };
})(window);
