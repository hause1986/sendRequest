(function () {
    'use strict';

    const API_URL = './api/request.php';

    const $ = (id) => document.getElementById(id);

    function createInput(placeholder) {
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'field__input';
        input.placeholder = placeholder;
        input.autocomplete = 'off';
        return input;
    }

    function addKvRow(container, keyPlaceholder, valuePlaceholder) {
        const row = document.createElement('div');
        row.className = 'kv-row';

        const keyInput = createInput(keyPlaceholder);
        const valueInput = createInput(valuePlaceholder);
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn--danger';
        removeBtn.textContent = '×';
        removeBtn.title = 'Удалить';
        removeBtn.setAttribute('aria-label', 'Удалить');
        removeBtn.addEventListener('click', () => row.remove());

        row.append(keyInput, valueInput, removeBtn);
        container.appendChild(row);
        keyInput.focus();
    }

    function collectKv(container) {
        const rows = container.querySelectorAll('.kv-row');
        const result = [];
        rows.forEach((row) => {
            const [keyInput, valueInput] = row.querySelectorAll('input');
            const key = keyInput.value.trim();
            if (!key) return;
            result.push({ key, value: valueInput.value });
        });
        return result;
    }

    function formatJson(text) {
        try {
            return JSON.stringify(JSON.parse(text), null, 2);
        } catch {
            return text;
        }
    }

    function setStatus(text, type) {
        const el = $('response-status');
        el.textContent = text;
        el.className = 'response-status' + (type ? ' ' + type : '');
    }

    function resetResponse() {
        setStatus('—', null);
        $('response-time').textContent = '—';
        $('response-output').textContent = 'Здесь появится ответ сервера…';
    }

    function getSelectedMethod() {
        const checked = document.querySelector('input[name="http-method"]:checked');
        return checked ? checked.value : 'GET';
    }

    async function sendRequest() {
        const url = $('request-url').value.trim();
        if (!url) {
            setStatus('Ошибка', 'error');
            $('response-output').textContent = 'Введите URL запроса.';
            return;
        }

        const form = new FormData();
        form.append('method', getSelectedMethod());
        form.append('url', url);
        form.append('raw_headers', $('raw-headers').value);

        collectKv($('params-list')).forEach((pair, i) => {
            form.append(`params[${i}][key]`, pair.key);
            form.append(`params[${i}][value]`, pair.value);
        });

        collectKv($('headers-list')).forEach((pair, i) => {
            form.append(`headers[${i}][key]`, pair.key);
            form.append(`headers[${i}][value]`, pair.value);
        });

        setStatus('Отправка…', null);
        $('response-time').textContent = '—';
        $('response-output').textContent = 'Запрос выполняется на сервере…';

        try {
            const res = await fetch(API_URL, {
                method: 'POST',
                body: form,
                credentials: 'same-origin'
            });

            const data = await res.json();

            if (!data.ok) {
                setStatus('Ошибка', 'error');
                $('response-output').textContent = data.error || 'Неизвестная ошибка';
                return;
            }

            const r = data.response;
            const ok = r.status >= 200 && r.status < 300;
            setStatus(`${r.status} ${r.status_text}`, ok ? 'ok' : 'error');
            $('response-time').textContent = `Время: ${r.time_ms} мс · Размер: ${r.size} Б`;

            const ct = (r.headers['content-type'] || '').toLowerCase();
            const body = ct.includes('json') ? formatJson(r.body) : r.body;

            $('response-output').textContent = body || '(пустой ответ)';
        } catch (err) {
            setStatus('Сетевая ошибка', 'error');
            $('response-output').textContent =
                'Не удалось получить ответ от PHP-эндпоинта.\n\n' +
                'Причина: ' + err.message;
        }
    }

    // --- Инициализация ---

    $('add-param').addEventListener('click', () => addKvRow($('params-list'), 'Ключ', 'Значение'));
    $('add-header').addEventListener('click', () => addKvRow($('headers-list'), 'Имя заголовка', 'Значение'));
    $('send-request').addEventListener('click', sendRequest);

    $('clear-form').addEventListener('click', () => {
        $('request-url').value = '';
        $('raw-headers').value = '';
        $('params-list').innerHTML = '';
        $('headers-list').innerHTML = '';
        addKvRow($('params-list'), 'Ключ', 'Значение');
        addKvRow($('headers-list'), 'Имя заголовка', 'Значение');
        resetResponse();
    });

    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') sendRequest();
    });

    // Стартовые строки
    addKvRow($('params-list'), 'Ключ', 'Значение');
    addKvRow($('headers-list'), 'Имя заголовка', 'Значение');
})();