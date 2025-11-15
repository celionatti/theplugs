// ========== GLOBAL FORM HELPER ==========

class FormHelper {
  /**
   * Get value from input element by ID
   * @param {string} elementId
   * @returns {string}
   */
  static getValue(elementId) {
    const element = document.getElementById(elementId);
    return element ? element.value : "";
  }

  /**
   * Set value to input element by ID
   * @param {string} elementId
   * @param {any} value
   */
  static setValue(elementId, value) {
    const element = document.getElementById(elementId);
    if (element) {
      element.value = value;
    }
  }

  /**
   * Get checked state from checkbox by ID
   * @param {string} elementId
   * @returns {boolean}
   */
  static getChecked(elementId) {
    const element = document.getElementById(elementId);
    return element ? element.checked : false;
  }

  /**
   * Set checked state to checkbox by ID
   * @param {string} elementId
   * @param {boolean} checked
   */
  static setChecked(elementId, checked) {
    const element = document.getElementById(elementId);
    if (element && element.type === "checkbox") {
      element.checked = Boolean(checked);
    }
  }

  /**
   * Get selected radio button value by name
   * @param {string} name
   * @returns {string|null}
   */
  static getRadioValue(name) {
    const element = document.querySelector(`input[name="${name}"]:checked`);
    return element ? element.value : null;
  }

  /**
   * Set radio button by name and value
   * @param {string} name
   * @param {string} value
   */
  static setRadioValue(name, value) {
    const radio = document.querySelector(
      `input[name="${name}"][value="${value}"]`
    );
    if (radio) {
      radio.checked = true;
    }
  }

  /**
   * Populate form with data object
   * @param {object} data - Object with key-value pairs
   * @param {function} keyTransform - Optional function to transform keys
   */
  static populateForm(data, keyTransform = null) {
    for (const [key, value] of Object.entries(data)) {
      const elementId = keyTransform ? keyTransform(key) : key;
      const element = document.getElementById(elementId);

      if (element) {
        if (element.type === "checkbox") {
          element.checked = Boolean(value);
        } else if (element.type === "radio") {
          const radio = document.querySelector(
            `input[name="${element.name}"][value="${value}"]`
          );
          if (radio) radio.checked = true;
        } else {
          element.value = value;
        }
      }
    }
  }

  /**
   * Collect form data from elements
   * @param {Array<string>} elementIds - Array of element IDs to collect
   * @returns {object} - Object with element IDs as keys and values
   */
  static collectData(elementIds) {
    const data = {};

    elementIds.forEach((id) => {
      const element = document.getElementById(id);
      if (element) {
        if (element.type === "checkbox") {
          data[id] = element.checked;
        } else if (element.type === "radio") {
          const checked = document.querySelector(
            `input[name="${element.name}"]:checked`
          );
          if (checked) data[id] = checked.value;
        } else {
          data[id] = element.value;
        }
      }
    });

    return data;
  }

  /**
   * Reset form elements
   * @param {Array<string>} elementIds - Array of element IDs to reset
   */
  static resetForm(elementIds) {
    elementIds.forEach((id) => {
      const element = document.getElementById(id);
      if (element) {
        if (element.type === "checkbox") {
          element.checked = false;
        } else if (element.type === "radio") {
          element.checked = false;
        } else {
          element.value = "";
        }
      }
    });
  }

  /**
   * Validate form elements
   * @param {Array<object>} rules - Array of validation rules
   * @returns {object} - Object with isValid boolean and errors array
   */
  static validate(rules) {
    const errors = [];

    rules.forEach((rule) => {
      const element = document.getElementById(rule.id);
      if (!element) return;

      const value = element.value;

      if (rule.required && !value) {
        errors.push(`${rule.label || rule.id} is required`);
      }

      if (rule.minLength && value.length < rule.minLength) {
        errors.push(
          `${rule.label || rule.id} must be at least ${
            rule.minLength
          } characters`
        );
      }

      if (rule.maxLength && value.length > rule.maxLength) {
        errors.push(
          `${rule.label || rule.id} must not exceed ${
            rule.maxLength
          } characters`
        );
      }

      if (rule.pattern && !rule.pattern.test(value)) {
        errors.push(`${rule.label || rule.id} format is invalid`);
      }

      if (rule.custom && !rule.custom(value)) {
        errors.push(rule.message || `${rule.label || rule.id} is invalid`);
      }
    });

    return {
      isValid: errors.length === 0,
      errors: errors,
    };
  }
}

// Make globally available
window.FormHelper = FormHelper;
