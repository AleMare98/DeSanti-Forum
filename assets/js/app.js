document.addEventListener('DOMContentLoaded', function () {
    attachAsyncForms();
    attachChat();
});

function attachAsyncForms() {
    document.querySelectorAll('form[data-action]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) return;
            event.preventDefault();
            submitForm(form);
        });
    });
}

function submitForm(form) {
    var button = form.querySelector('button[type="submit"]');
    var error = form.parentElement.querySelector('.form-error');
    if (button) button.disabled = true;
    hideError(error);

    fetch(form.action, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: new URLSearchParams(new FormData(form))
    })
        .then(readJson)
        .then(function (data) {
            if (form.dataset.action === 'generate_forum_ai') {
                renderAiDraft(data.draft);
                form.reset();
                return;
            }
            if (data.thread && data.thread.id) {
                window.location.assign('index.php?page=thread&id=' + encodeURIComponent(data.thread.id));
                return;
            }
            window.location.reload();
        })
        .catch(function (reason) { showError(error, reason.message); })
        .finally(function () { if (button) button.disabled = false; });
}

function attachChat() {
    var panel = document.getElementById('chat-panel');
    if (!panel) return;
    var categoryId = panel.dataset.categoryId;
    var form = document.getElementById('chat-form');
    var error = document.getElementById('chat-error');
    var timer = window.setInterval(function () { loadChat(categoryId, panel, error); }, 10000);

    loadChat(categoryId, panel, error);
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        var button = form.querySelector('button');
        button.disabled = true;
        hideError(error);
        fetch(form.action, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: new URLSearchParams(new FormData(form)) })
            .then(readJson)
            .then(function () { form.reset(); loadChat(categoryId, panel, error); })
            .catch(function (reason) { showError(error, reason.message); })
            .finally(function () { button.disabled = false; });
    });
    window.addEventListener('beforeunload', function () { window.clearInterval(timer); }, { once: true });
}

function loadChat(categoryId, panel, error) {
    fetch('actions/get_chat_messages.php?category_id=' + encodeURIComponent(categoryId), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(readJson)
        .then(function (data) { renderMessages(data.messages || [], panel); })
        .catch(function (reason) { showError(error, reason.message); });
}

function renderMessages(messages, panel) {
    var container = document.getElementById('chat-messages');
    container.replaceChildren();
    if (!messages.length) {
        var empty = document.createElement('p'); empty.className = 'chat-empty'; empty.textContent = 'Nessun messaggio per ora.'; container.appendChild(empty); return;
    }
    messages.forEach(function (message) {
        var article = document.createElement('article'); article.className = 'chat-message';
        var meta = document.createElement('p'); meta.className = 'chat-message-meta'; meta.textContent = message.username + ' · ' + message.created_at;
        var text = document.createElement('p'); text.textContent = message.content;
        article.append(meta, text);
        if (panel.dataset.canDelete === '1') {
            var remove = document.createElement('button'); remove.type = 'button'; remove.className = 'chat-delete'; remove.textContent = 'Elimina';
            remove.addEventListener('click', function () { deleteChatMessage(message.id, panel); }); article.appendChild(remove);
        }
        container.appendChild(article);
    });
    container.scrollTop = container.scrollHeight;
}

function deleteChatMessage(messageId, panel) {
    if (!window.confirm('Eliminare questo messaggio?')) return;
    fetch('actions/delete_chat_message.php', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: new URLSearchParams({ message_id: messageId, csrf_token: csrfToken() }) })
        .then(readJson)
        .then(function () { loadChat(panel.dataset.categoryId, panel, document.getElementById('chat-error')); })
        .catch(function (reason) { showError(document.getElementById('chat-error'), reason.message); });
}

function renderAiDraft(draft) {
    var target = document.getElementById('ai-draft');
    if (!target || !draft || !Array.isArray(draft.categories)) return;
    target.replaceChildren(); target.hidden = false;
    var heading = document.createElement('h3'); heading.textContent = 'Bozza AI — non pubblicata'; target.appendChild(heading);
    draft.categories.forEach(function (category) {
        var section = document.createElement('section'); var title = document.createElement('h4'); title.textContent = category.name; section.appendChild(title);
        category.threads.forEach(function (thread) { var item = document.createElement('article'); var h5 = document.createElement('h5'); h5.textContent = thread.title; var content = document.createElement('p'); content.textContent = thread.content; item.append(h5, content); section.appendChild(item); });
        target.appendChild(section);
    });
}

function readJson(response) {
    return response.json().catch(function () { throw new Error('Risposta non valida dal server.'); }).then(function (data) {
        if (!response.ok || !data.success) throw new Error(data.error || 'Operazione non riuscita.');
        return data;
    });
}

function showError(element, message) { if (element) { element.textContent = message; element.hidden = false; } }
function hideError(element) { if (element) { element.textContent = ''; element.hidden = true; } }
function csrfToken() { var token = document.getElementById('csrf-token-field'); return token ? token.value : ''; }
