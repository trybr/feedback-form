(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('feedbackForm');
        const captchaImg = document.getElementById('captchaImg');
        const captchaRefresh = document.getElementById('captchaRefresh');
        const captchaInput = document.getElementById('captcha');

        if (!form) return;

        const submitBtn = document.getElementById('submitBtn');
        const resultBox = document.getElementById('formResult');

        /* Помощники */

        const showResult = (type, html) => {
            resultBox.className = `form__result is-${type}`;
            resultBox.innerHTML = html;
            resultBox.style.display = 'block';
        };

        const hideResult = () => {
            resultBox.style.display = 'none';
        };

        const clearErrors = () => {
            form.querySelectorAll('.is-invalid').forEach((el) => {
                el.classList.remove('is-invalid');
            });
            form.querySelectorAll('.form__error').forEach((el) => {
                el.textContent = '';
            });
        };

        const scrollToField = (field) => {
            field.scrollIntoView({ behavior: 'smooth', block: 'center' });
            field.focus();
        };

        const setSubmitState = (loading) => {
            submitBtn.disabled = loading;
            submitBtn.textContent = loading ? 'Отправка...' : 'Отправить';
        };

        /* Обработка ошибок сервера */

        const applyServerErrors = (errors) => {
            if (!errors) return;

            clearErrors();

            Object.entries(errors).forEach(([name, msg]) => {
                const field = form.querySelector(`[name="${name}"]`);
                if (field) window.FormValidator.setError(field, msg);
            });

            const first = form.querySelector('.is-invalid');
            if (first) scrollToField(first);
        };

        /* Отправка Формы */

        const sendForm = async () => {
            const fd = new FormData(form);

            setSubmitState(true);
            hideResult();

            try {
                const response = await fetch(form.getAttribute('action'), {
                    method: 'POST',
                    body: fd,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const resp = await response.json();

                if (resp.success) {
                    form.reset();
                    clearErrors();
                    showResult('success', resp.message || 'Сообщение отправлено.');
                } else {
                    applyServerErrors(resp.errors);
                    showResult('error', resp.message || 'Не удалось отправить сообщение.');
                }
            } catch (err) {
                console.error('Ошибка отправки:', err);
                showResult('error', 'Ошибка соединения с сервером. Попробуйте позже.');
            } finally {
                if (captchaImg) {
                    captchaImg.src = `inc/captcha.php?t=${Date.now()}`;
                    captchaInput.value = '';
                }
                setSubmitState(false);
            }
        };

        captchaRefresh?.addEventListener('click', () => {
            captchaImg.src = `inc/captcha.php?t=${Date.now()}`;
            captchaInput.value = '';
        });

        /* Обработчик Submit */

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const check = window.FormValidator.validateForm(form);
            if (!check.valid) {
                const first = check.invalid[0];
                if (first) scrollToField(first);
                showResult('error', 'Проверьте правильность заполнения полей формы.');
                return;
            }

            await sendForm();
        });
    });
})();
