// Forum application JavaScript

var handledForms = new WeakSet();

document.addEventListener('DOMContentLoaded', function () {
    attachFormHandlers();
});

// Intercept all form submissions and send them via AJAX
function attachFormHandlers() {
    var forms = document.querySelectorAll('form[data-action]');

    forms.forEach(function (form) {
        if (handledForms.has(form)) return;
        handledForms.add(form);
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            handleFormSubmit(form);
        });
    });
}

// Send form data via fetch and handle the JSON response
function handleFormSubmit(form) {
    var action = form.getAttribute('data-action');
    var submitButton = form.querySelector('button[type="submit"]');
    var errorContainer = form.parentNode.querySelector('.form-error') || document.getElementById('form-error');

    // Disable button while request is in progress
    submitButton.disabled = true;

    // Hide any previous errors
    if (errorContainer) {
        errorContainer.style.display = 'none';
        errorContainer.textContent = '';
    }

    var formData = new FormData(form);

    fetch(form.getAttribute('action'), {
        method: 'POST',
        body: new URLSearchParams(formData),
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(function (response) {
        return response.json().then(function (data) {
            return { ok: response.ok, data: data };
        });
    })
    .then(function (result) {
        submitButton.disabled = false;

        if (!result.ok) {
            showError(errorContainer, result.data.error || 'Something went wrong.');
            return;
        }

        handleSuccess(action, result.data, form);
    })
    .catch(function () {
        submitButton.disabled = false;
        showError(errorContainer, 'Network error. Please try again.');
    });
}

// Display an error message near the form
function showError(container, message) {
    if (container) {
        container.textContent = message;
        container.style.display = 'block';
    } else {
        alert(message);
    }
}

// Handle successful responses based on the action type
function handleSuccess(action, data, form) {
    switch (action) {
        case 'login':
            onLoginSuccess(data);
            break;
        case 'register':
            onRegisterSuccess(data);
            break;
        case 'create_thread':
            onThreadCreated(data, form);
            break;
        case 'create_comment':
            onCommentCreated(data, form);
            break;
        case 'delete_thread':
            onThreadDeleted(form);
            break;
        case 'delete_comment':
            onCommentDeleted(form);
            break;
        case 'create_category':
            onCategoryCreated(data, form);
            break;
        case 'generate_forum_ai':
            onAiForumGenerated(data, form);
            break;
        case 'logout':
            onLogoutSuccess();
            break;
    }
}

// After login: update the navbar and redirect to the index page
function onLoginSuccess(data) {
    updateNavbar(data.username, data.role);
    window.location.href = '?page=index';
}

// After registration: update the navbar and redirect to the index page
function onRegisterSuccess(data) {
    updateNavbar(data.username, data.role);
    window.location.href = '?page=index';
}

// After logout: update the navbar to the logged-out state
function onLogoutSuccess() {
    updateNavbar(null, null);
}

// Replace the navbar links based on login state
function updateNavbar(username, role) {
    var navLinks = document.getElementById('nav-links');
    if (!navLinks) {
        return;
    }

    if (username === null) {
        navLinks.innerHTML = '<span id="nav-logged-out">'
            + '<a href="?page=login">Login</a>'
            + '<a href="?page=register">Register</a>'
            + '</span>';
        return;
    }

    var html = '<span class="nav-user" id="nav-logged-in">';
    html += 'Logged in as <strong id="nav-username">' + escapeHtml(username) + '</strong>';
    html += '</span>';

    if (role === 'admin') {
        html += '<a href="?page=admin" id="nav-admin-link">Admin Panel</a>';
    }

    html += '<form action="actions/logout.php" method="POST" data-action="logout" style="display:inline">';
    html += '<input type="hidden" name="csrf_token" value="' + getCsrfToken() + '">';
    html += '<button type="submit" id="nav-logout-link" class="nav-link-btn">Logout</button>';
    html += '</form>';

    navLinks.innerHTML = html;
    var logoutForm = navLinks.querySelector('form[data-action="logout"]');
    if (logoutForm) {
        handledForms.add(logoutForm);
        logoutForm.addEventListener('submit', function (event) {
            event.preventDefault();
            handleFormSubmit(logoutForm);
        });
    }
}

// After creating a thread: add it to the list and clear the form
function onThreadCreated(data, form) {
    var threadList = document.getElementById('thread-list');
    var emptyState = document.getElementById('empty-threads');

    // Remove the "no threads yet" message if present
    if (emptyState) {
        emptyState.remove();
    }

    // Create the thread list container if it didn't exist
    if (!threadList) {
        threadList = document.createElement('div');
        threadList.className = 'thread-list';
        threadList.id = 'thread-list';

        var emptyThreads = document.getElementById('empty-threads');
        if (emptyThreads) {
            emptyThreads.parentNode.insertBefore(threadList, emptyThreads.nextSibling);
        } else {
            // Insert before the create-form's parent
            var createForm = form.closest('.create-form');
            createForm.parentNode.insertBefore(threadList, createForm.nextSibling);
        }
    }

    var thread = data.thread;

    var item = document.createElement('div');
    item.className = 'thread-item';
    item.innerHTML = '<a href="?page=thread&id=' + thread.id + '">' + escapeHtml(thread.title) + '</a>'
        + '<span class="thread-meta">by ' + escapeHtml(thread.username)
        + ' on ' + escapeHtml(thread.created_at) + '</span>';

    // Add to the top of the list (newest first)
    threadList.insertBefore(item, threadList.firstChild);

    // Clear the form
    form.reset();

    // Navigate to the new thread
    window.location.href = '?page=thread&id=' + thread.id;
}

// After posting a comment: append it to the comments section and clear the form
function onCommentCreated(data, form) {
    var commentsList = document.getElementById('comments-list');
    var emptyState = document.getElementById('empty-comments');
    var countSpan = document.getElementById('comments-count');

    // Remove the "no comments yet" message if present
    if (emptyState) {
        emptyState.remove();
    }

    // Update the comment count
    if (countSpan) {
        var currentCount = parseInt(countSpan.textContent, 10) || 0;
        countSpan.textContent = currentCount + 1;
    }

    var comment = data.comment;

    var div = document.createElement('div');
    div.className = 'comment';
    div.id = 'comment-' + comment.id;
    div.innerHTML = '<div class="comment-header">'
        + '<strong>' + escapeHtml(comment.username) + '</strong>'
        + '<span>' + escapeHtml(comment.created_at) + '</span>'
        + '</div>'
        + '<div class="comment-content">'
        + escapeHtml(comment.content).replace(/\n/g, '<br>')
        + '</div>';

    commentsList.appendChild(div);

    // Clear the form
    form.reset();
}

// After deleting a thread: navigate back to the category
function onThreadDeleted(form) {
    var categoryIdInput = form.querySelector('input[name="category_id"]');
    var categoryId = categoryIdInput ? categoryIdInput.value : '';

    if (categoryId) {
        window.location.href = '?page=category&id=' + categoryId;
    } else {
        window.location.href = '?page=index';
    }
}

// After deleting a comment: remove it from the page and update the count
function onCommentDeleted(form) {
    var commentIdInput = form.querySelector('input[name="comment_id"]');
    var commentId = commentIdInput ? 'comment-' + commentIdInput.value : '';

    var commentElement = document.getElementById(commentId);
    if (commentElement) {
        commentElement.remove();
    }

    // Update the comment count
    var countSpan = document.getElementById('comments-count');
    if (countSpan) {
        var currentCount = parseInt(countSpan.textContent, 10) || 0;
        countSpan.textContent = Math.max(0, currentCount - 1);
    }
}

// After creating a category: add it to the list and clear the form
function onCategoryCreated(data, form) {
    var categoryList = document.getElementById('category-list');
    var emptyState = document.getElementById('empty-categories');

    // Remove the "no categories yet" message if present
    if (emptyState) {
        emptyState.remove();
    }

    // Create the category list container if it didn't exist
    if (!categoryList) {
        categoryList = document.createElement('div');
        categoryList.className = 'category-list';
        categoryList.id = 'category-list';

        var heading = document.querySelector('h2');
        if (heading && heading.nextSibling) {
            heading.parentNode.insertBefore(categoryList, heading.nextSibling);
        }
    }

    var cat = data.category;

    var item = document.createElement('div');
    item.className = 'category-item';
    item.innerHTML = '<a href="?page=category&id=' + cat.id + '">' + escapeHtml(cat.name) + '</a>'
        + '<span class="category-meta">created on ' + escapeHtml(cat.created_at) + '</span>';

    // Add to the top of the list (newest first)
    categoryList.insertBefore(item, categoryList.firstChild);

    // Clear the form
    form.reset();

    // Show success message briefly
    var errorContainer = form.parentNode.querySelector('.form-error') || document.getElementById('form-error');
    if (errorContainer) {
        errorContainer.textContent = 'Category created successfully.';
        errorContainer.className = 'alert alert-success form-error';
        errorContainer.style.display = 'block';

        setTimeout(function () {
            errorContainer.style.display = 'none';
            errorContainer.className = 'alert alert-error form-error';
        }, 2000);
    }
}

function onAiForumGenerated(data, form) {
    var errorContainer = form.parentNode.querySelector('.form-error') || document.getElementById('form-error');
    var summary = data.summary || {};

    if (errorContainer) {
        errorContainer.textContent = 'AI generation completed: '
            + (summary.categories || 0) + ' categories, '
            + (summary.threads || 0) + ' threads, '
            + (summary.comments || 0) + ' comments.';
        errorContainer.className = 'alert alert-success form-error';
        errorContainer.style.display = 'block';
    }

    setTimeout(function () {
        window.location.reload();
    }, 1200);
}

// Escape HTML to prevent XSS when inserting dynamic content
function escapeHtml(text) {
    if (text === null || text === undefined) {
        return '';
    }

    var div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}

// Get the CSRF token from the hidden field in the page
function getCsrfToken() {
    var field = document.getElementById('csrf-token-field');
    return field ? field.value : '';
}
