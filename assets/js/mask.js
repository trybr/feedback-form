(function () {
    'use strict';

    const input = document.getElementById('phone');
    if (!input) return;

    const template = '+7 (___) ___-__-__';

    const format = (digits) => {
        // digits — строка только из цифр, БЕЗ ведущей 7
        const prefix = '+7';
        const tail = template.slice(2); // " (___) ___-__-__"
        let out = '';
        let di = 0;

        while (out.length < tail.length && di < digits.length) {
            const ch = tail[out.length];
            if (ch === '_') {
                out += digits[di];
                di++;
            } else {
                out += ch;
            }
        }
        while (out.length < tail.length && tail[out.length] !== '_') {
            out += tail[out.length];
        }
        return prefix + out;
    };

    const normalizeDigits = (raw) => {
        // оставляем только цифры, приводим 8 → 7, гарантируем ведущую 7
        let digits = (raw || '').replace(/\D/g, '');
        if (digits[0] === '8') digits = '7' + digits.slice(1);
        if (digits[0] !== '7') digits = '7' + digits;
        // отдаём без ведущей 7 — её format() добавит сам
        return digits.slice(1, 11); // максимум 10 цифр после +7
    };

    const render = () => {
        const digits = input.dataset.digits ?? '';
        input.value = digits ? format(digits) : '';
    };

    const setCaretToEnd = () => {
        const len = input.value.length;
        input.selectionStart = input.selectionEnd = len;
    };

    // инициализация: если в поле уже что-то есть (например, из value)
    if (input.value) {
        input.dataset.digits = normalizeDigits(input.value);
        render();
    } else {
        input.dataset.digits = '';
    }

    input.addEventListener('focus', () => {
        if (!input.dataset.digits) {
            input.value = '+7 (';
            input.dataset.digits = '';
            setCaretToEnd();
        }
    });

    input.addEventListener('blur', () => {
        if (!input.dataset.digits) {
            input.value = '';
        }
    });

    input.addEventListener('beforeinput', (e) => {
        let digits = input.dataset.digits ?? '';

        if (e.inputType === 'deleteContentBackward' ||
            e.inputType === 'deleteContentForward') {
            if (!digits) return;
            digits = digits.slice(0, -1);
            e.preventDefault();
        } else if (e.inputType === 'insertText' && /^\d$/.test(e.data)) {
            if (digits.length >= 10) return;
            digits += e.data;
            e.preventDefault();
        } else {
            // любой другой ввод (буквы, вставка, автозамена) — блокируем
            if (e.inputType.startsWith('insert')) {
                e.preventDefault();
            }
            return;
        }

        input.dataset.digits = digits;
        input.value = digits ? format(digits) : '+7 (';
        setCaretToEnd();
    });

    // На всякий случай — блокируем вставку из буфера, если она не цифровая
    input.addEventListener('paste', (e) => {
        const text = (e.clipboardData || window.clipboardData).getData('text') || '';
        const digits = normalizeDigits((input.dataset.digits ?? '') + text);
        input.dataset.digits = digits;
        render();
        e.preventDefault();
        setCaretToEnd();
    });
})();
