// ========== GLOBAL API HELPER ==========

class ApiHelper {
  /**
   * Generic API call method
   * @param {string} url - API endpoint URL
   * @param {string} method - HTTP method (GET, POST, PUT, DELETE)
   * @param {object|null} data - Request body data
   * @param {object|null} queryParams - URL query parameters
   * @returns {Promise<object>} - API response
   */
  static async call(url, method = "GET", data = null, queryParams = null) {
    const options = {
      method: method,
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    };

    // Add body for non-GET requests
    if (method !== "GET" && data) {
      options.body = JSON.stringify(data);
    }

    // Build URL with query params
    if (queryParams) {
      const params = new URLSearchParams(queryParams);
      url += "?" + params.toString();
    }

    try {
      const response = await fetch(url, options);

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      return await response.json();
    } catch (error) {
      console.error("API call failed:", error);
      throw error;
    }
  }

  /**
   * GET request
   */
  static async get(url, queryParams = null) {
    return this.call(url, "GET", null, queryParams);
  }

  /**
   * POST request
   */
  static async post(url, data = null) {
    return this.call(url, "POST", data);
  }

  /**
   * PUT request
   */
  static async put(url, data = null) {
    return this.call(url, "PUT", data);
  }

  /**
   * DELETE request
   */
  static async delete(url, data = null) {
    return this.call(url, "DELETE", data);
  }
}

// Make globally available
window.ApiHelper = ApiHelper;
