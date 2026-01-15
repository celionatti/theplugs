// ========== GLOBAL STRING HELPER ==========

class StringHelper {
  /**
   * Convert snake_case to camelCase
   * @param {string} str
   * @returns {string}
   */
  static snakeToCamel(str) {
    return str.replace(/_([a-z])/g, (g) => g[1].toUpperCase());
  }

  /**
   * Convert camelCase to snake_case
   * @param {string} str
   * @returns {string}
   */
  static camelToSnake(str) {
    return str.replace(/([A-Z])/g, "_$1").toLowerCase();
  }

  /**
   * Convert kebab-case to camelCase
   * @param {string} str
   * @returns {string}
   */
  static kebabToCamel(str) {
    return str.replace(/-([a-z])/g, (g) => g[1].toUpperCase());
  }

  /**
   * Convert camelCase to kebab-case
   * @param {string} str
   * @returns {string}
   */
  static camelToKebab(str) {
    return str.replace(/([A-Z])/g, "-$1").toLowerCase();
  }

  /**
   * Capitalize first letter
   * @param {string} str
   * @returns {string}
   */
  static capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
  }

  /**
   * Truncate string
   * @param {string} str
   * @param {number} length
   * @param {string} suffix
   * @returns {string}
   */
  static truncate(str, length = 100, suffix = "...") {
    if (str.length <= length) return str;
    return str.substring(0, length) + suffix;
  }
}

// Make globally available
window.StringHelper = StringHelper;
