// Forum application JavaScript

document.addEventListener('DOMContentLoaded', function () {
    // Confirm before deleting threads/comments
    var deleteButtons = document.querySelectorAll('.btn-delete');

    deleteButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            var message = this.getAttribute('data-confirm') || 'Are you sure you want to delete this?';

            if (!confirm(message)) {
                event.preventDefault();
            }
        });
    });
});
