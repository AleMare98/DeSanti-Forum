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
                renderAiDraft(data.draft, data.draft_token);
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

function renderAiDraft(draft, draftToken) {
    var target = document.getElementById('ai-draft');
    if (!target || !draft || !Array.isArray(draft.categories) || !draftToken) return;
    target.replaceChildren(); target.hidden = false;
    var heading = document.createElement('h3'); heading.textContent = 'Bozza AI — modifica prima di pubblicare'; target.appendChild(heading);
    var note = document.createElement('p'); note.className = 'helper-text'; note.textContent = 'La pubblicazione crea tutti gli elementi della bozza in un’unica operazione.'; target.appendChild(note);
    var form = document.createElement('form'); form.action = 'actions/publish_forum_draft.php'; form.method = 'post'; form.dataset.action = 'publish_forum_draft';
    addHidden(form, 'csrf_token', csrfToken()); addHidden(form, 'draft_token', draftToken);
    var jsonField = document.createElement('input'); jsonField.type = 'hidden'; jsonField.name = 'draft_json'; form.appendChild(jsonField);

    draft.categories.forEach(function (category, categoryIndex) {
        var section = document.createElement('fieldset'); section.className = 'ai-draft-category';
        var legend = document.createElement('legend'); legend.textContent = 'Categoria ' + (categoryIndex + 1); section.appendChild(legend);
        section.dataset.categoryIndex = categoryIndex;
        appendDraftField(section, 'Nome categoria', 'category-name', category.name, 'text');
        (category.threads || []).forEach(function (thread, threadIndex) {
            var item = document.createElement('div'); item.className = 'ai-draft-thread'; item.dataset.threadIndex = threadIndex;
            var subheading = document.createElement('h4'); subheading.textContent = 'Discussione ' + (threadIndex + 1); item.appendChild(subheading);
            appendDraftField(item, 'Titolo', 'thread-title', thread.title, 'text');
            appendDraftField(item, 'Testo', 'thread-content', thread.content, 'textarea');
            (thread.comments || []).forEach(function (comment, commentIndex) {
                appendDraftField(item, 'Commento ' + (commentIndex + 1), 'comment', comment, 'textarea');
            });
            section.appendChild(item);
        });
        form.appendChild(section);
    });
    var error = document.createElement('div'); error.className = 'form-error'; error.setAttribute('role', 'alert'); error.hidden = true; form.appendChild(error);
    var button = document.createElement('button'); button.type = 'submit'; button.textContent = 'Pubblica bozza'; form.appendChild(button);
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        jsonField.value = JSON.stringify(collectDraft(form));
        submitForm(form);
    });
    target.appendChild(form);
}

function addHidden(form, name, value) { var input = document.createElement('input'); input.type = 'hidden'; input.name = name; input.value = value; form.appendChild(input); }

function appendDraftField(parent, labelText, className, value, type) {
    var label = document.createElement('label'); label.textContent = labelText;
    var field = type === 'textarea' ? document.createElement('textarea') : document.createElement('input');
    field.className = className; field.value = typeof value === 'string' ? value : ''; field.required = true;
    if (type !== 'textarea') field.type = 'text';
    if (className === 'category-name') field.maxLength = 100;
    if (className === 'thread-title') field.maxLength = 255;
    if (className === 'thread-content') field.maxLength = 10000;
    if (className === 'comment') field.maxLength = 5000;
    label.appendChild(field); parent.appendChild(label);
}

function collectDraft(form) {
    var draft = { categories: [] };
    form.querySelectorAll('.ai-draft-category').forEach(function (section) {
        var category = { name: section.querySelector('.category-name').value, threads: [] };
        section.querySelectorAll('.ai-draft-thread').forEach(function (item) {
            var thread = { title: item.querySelector('.thread-title').value, content: item.querySelector('.thread-content').value, comments: [] };
            item.querySelectorAll('.comment').forEach(function (field) { thread.comments.push(field.value); });
            category.threads.push(thread);
        });
        draft.categories.push(category);
    });
    return draft;
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
