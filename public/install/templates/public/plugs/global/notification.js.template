// ========== GLOBAL NOTIFICATION SYSTEM ==========

class NotificationHelper {
  /**
   * Show a notification
   * @param {string} message - Notification message
   * @param {string} type - Notification type (success, error, info, warning)
   * @param {number} duration - Auto-dismiss duration in milliseconds (0 = no auto-dismiss)
   */
  static show(message, type = "info", duration = 5000) {
    // Remove existing notifications
    document
      .querySelectorAll(".custom-notification")
      .forEach((el) => el.remove());

    // Create notification element
    const notification = document.createElement("div");
    notification.className = "custom-notification";

    // Set color based on type
    const colors = {
      success: "#28a745",
      error: "#dc3545",
      info: "#17a2b8",
      warning: "#ffc107",
    };

    notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            max-width: 500px;
            padding: 1rem 1.5rem;
            background-color: ${colors[type] || colors.info};
            color: ${type === "warning" ? "#000" : "white"};
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            animation: slideIn 0.3s ease-out;
        `;

    notification.innerHTML = `
            <span style="flex: 1;">${message}</span>
            <button style="background: none; border: none; color: ${
              type === "warning" ? "#000" : "white"
            }; font-size: 1.5rem; cursor: pointer; padding: 0; line-height: 1; flex-shrink: 0;">&times;</button>
        `;

    // Add click to close
    notification.querySelector("button").addEventListener("click", () => {
      notification.remove();
    });

    document.body.appendChild(notification);

    // Auto remove after specified duration
    if (duration > 0) {
      setTimeout(() => {
        if (notification.parentNode) {
          notification.style.animation = "slideOut 0.3s ease-out";
          setTimeout(() => notification.remove(), 300);
        }
      }, duration);
    }
  }

  /**
   * Show success notification
   */
  static success(message, duration = 5000) {
    this.show(message, "success", duration);
  }

  /**
   * Show error notification
   */
  static error(message, duration = 5000) {
    this.show(message, "error", duration);
  }

  /**
   * Show info notification
   */
  static info(message, duration = 5000) {
    this.show(message, "info", duration);
  }

  /**
   * Show warning notification
   */
  static warning(message, duration = 5000) {
    this.show(message, "warning", duration);
  }
}

// Add animation styles
if (!document.getElementById("notification-styles")) {
  const style = document.createElement("style");
  style.id = "notification-styles";
  style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
  document.head.appendChild(style);
}

// Make globally available
window.NotificationHelper = NotificationHelper;
