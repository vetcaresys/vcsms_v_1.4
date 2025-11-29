document.addEventListener("DOMContentLoaded", function () {
    loadAdminNotifications();

    // Load when bell icon is clicked
    document.getElementById("notifDropdown").addEventListener("click", loadAdminNotifications);

    // 💡 NEW: Listener for Mark All button
    document.getElementById("mark_all_btn").addEventListener("click", markAllAsRead);
});

function loadAdminNotifications() {
    fetch("../clinic_fetch_notifications.php")
        .then(res => res.json())
        .then(data => {
            const list = document.getElementById("notif_list");
            const count = document.getElementById("notif_count");
            const markAllBtn = document.getElementById("mark_all_btn"); // Get the button

            list.innerHTML = "";
            let unreadCount = 0;

            if (!data || data.length === 0) {
                list.innerHTML = `<li class="text-center text-muted py-3">No notifications</li>`;
                count.textContent = "";
                markAllBtn.disabled = true; // Disable button if no notifs
                return;
            }

            // Locate the loadAdminNotifications function and replace the following loop:
            data.forEach(n => {
                if (n.status === "unread") unreadCount++;

                list.innerHTML += `
                <li>
                    <a href="${n.link ?? '#'}" class="dropdown-item d-flex justify-content-between align-items-start notif-item ${n.status === "unread" ? 'bg-light' : ''}"
                    data-id="${n.notif_id}">
                        <div class="w-100"> 
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-secondary fw-semibold" style="font-size: 0.75rem;">
                                    <i class="bi bi-calendar"></i> ${n.display_date}
                                </small>
                                <small class="text-muted" style="font-size: 0.7rem;">
                                    <i class="bi bi-clock"></i> ${n.display_time}
                                </small>
                            </div>

                            <span style="
                                font-size: 0.85rem; 
                                max-width: 100%; 
                                display: block; 
                                overflow: hidden; 
                                text-overflow: ellipsis; 
                                white-space: nowrap;
                                /* Conditional Style: Use font-weight: bold (700) if unread, normal (400) if read */
                                font-weight: ${n.status === "unread" ? '700' : '400'};
                            ">
                            ${n.status === "unread" ? `<span class="badge bg-danger ms-2">New</span>` : ""}

                                ${n.subject}

                            </span>
                            
                            <small class="text-muted" style="font-size: 0.78rem;">${n.message}</small>
                        </div>
                    </a>
                </li>
                <li><hr class="dropdown-divider my-0"></li>
            `;
            });
            count.textContent = unreadCount > 0 ? unreadCount : "";
            markAllBtn.disabled = (unreadCount === 0); // Enable button only if there are unread notifications
        });
}

// 💡 NEW: Function to mark all notifications as read
function markAllAsRead() {
    Swal.fire({
        title: 'Mark all as read?',
        text: "All current unread notifications will be marked as read.",
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Mark All'
    }).then((result) => {
        if (result.isConfirmed) {
            // Fetch the new PHP endpoint to update the database
            fetch("../clinic_mark_all_as_read.php", { method: 'POST' })
                .then(response => {
                    if (response.ok) {
                        Swal.fire('Success!', 'All notifications marked as read.', 'success');
                        // Reload the notifications immediately after success
                        loadAdminNotifications();
                    } else {
                        Swal.fire('Error!', 'Could not mark all as read.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    Swal.fire('Error!', 'Network or server issue.', 'error');
                });
        }
    });
}

// Mark as read when opening a notification (Your original function, updated for clarity)
document.addEventListener("click", function (e) {
    if (e.target.closest(".notif-item")) {
        const notifItem = e.target.closest(".notif-item");
        const id = notifItem.dataset.id;
        // Only send the request if it's currently marked as unread
        if (notifItem.classList.contains('bg-light')) {
            fetch(`../mark_as_read.php?id=${id}`);
            // Simple visual update after click
            notifItem.classList.remove('bg-light');
            notifItem.querySelector('.badge')?.remove();
            loadAdminNotifications(); // Reload count
        }
    }
});