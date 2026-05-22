/* ══════════════════════════════════════════════════════════════ */
/* API UTILITY - Handles all communication with PHP backend */
/* ══════════════════════════════════════════════════════════════ */

const API = {
  // Base URL - change to your server address
  BASE_URL: 'http://localhost:8000/api',
  
  // Token storage keys
  ACCESS_TOKEN_KEY: 'accessToken',
  USER_KEY: 'userData',

  /**
   * Get stored access token
   */
  getAccessToken() {
    return localStorage.getItem(this.ACCESS_TOKEN_KEY);
  },

  /**
   * Get stored user data
   */
  getUser() {
    const user = localStorage.getItem(this.USER_KEY);
    return user ? JSON.parse(user) : null;
  },

  /**
   * Save tokens and user data after login
   */
  setSession(user, accessToken) {
    localStorage.setItem(this.USER_KEY, JSON.stringify(user));
    localStorage.setItem(this.ACCESS_TOKEN_KEY, accessToken);
  },

  /**
   * Clear all session data (logout)
   */
  clearSession() {
    localStorage.removeItem(this.ACCESS_TOKEN_KEY);
    localStorage.removeItem(this.USER_KEY);
  },

  /**
   * Normalize frontend payload keys to backend shape (lowerCamelCase).
   * Accepts either lowerCamelCase or legacy PascalCase keys.
   */
  normalizeUserPayload(data = {}) {
    const pick = (camel, pascal) => {
      if (data[camel] !== undefined) return data[camel];
      if (data[pascal] !== undefined) return data[pascal];
      return undefined;
    };

    const normalized = {
      fullName: pick('fullName', 'FullName'),
      email: pick('email', 'Email'),
      phoneNumber: pick('phoneNumber', 'PhoneNumber'),
      userType: pick('userType', 'UserType'),
      accountStatus: pick('accountStatus', 'AccountStatus'),
    };

    // Some pages pass password as `password` already; keep it if present
    if (data.password !== undefined) normalized.password = data.password;

    // Remove undefined keys so backend can use defaults
    Object.keys(normalized).forEach(k => normalized[k] === undefined && delete normalized[k]);
    return normalized;
  },

  /**
   * Make authenticated API request with bearer token
   */
  async request(endpoint, options = {}) {
    const url = this.BASE_URL + endpoint;
    const headers = {
      'Content-Type': 'application/json',
      ...options.headers,
    };

    // Add Bearer token if available
    const token = this.getAccessToken();
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    try {
      const response = await fetch(url, {
        ...options,
        headers,
      });

      const data = await response.json();

      // Handle errors
      if (!response.ok) {
        throw {
          status: response.status,
          error: data.error || data.message || 'Request failed',
          code: data.code || response.status,
        };
      }

      return data.data || data;
    } catch (err) {
      console.error('API Error:', err);
      throw err;
    }
  },

  /**
   * POST /api/auth/login
   * Login with email and password
   */
  async login(email, password, userType = 'Member') {
    const response = await this.request('/auth/login', {
      method: 'POST',
      body: JSON.stringify({
        email,
        password,
        userType,
      }),
    });

    // Save session on successful login
    if (response.user && response.token) {
      this.setSession(response.user, response.token);
    }

    return response;
  },

  /**
   * POST /api/auth/forgot-password
   * Request password reset token
   */
  async forgotPassword(email) {
    return await this.request('/auth/forgot-password', {
      method: 'POST',
      body: JSON.stringify({ email }),
    });
  },

  /**
   * POST /api/auth/reset-password
   * Reset password with token
   */
  async resetPassword(token, newPassword) {
    return await this.request('/auth/reset-password', {
      method: 'POST',
      body: JSON.stringify({
        token,
        newPassword,
      }),
    });
  },

  /**
   * POST /api/auth/change-password (requires authentication)
   * Change password for logged-in user
   */
  async changePassword(currentPassword, newPassword) {
    return await this.request('/auth/change-password', {
      method: 'POST',
      body: JSON.stringify({
        currentPassword,
        newPassword,
      }),
    });
  },

  /**
   * GET /api/users
   * List all users with optional filters
   */
  async listUsers(search = '', typeFilter = '', statusFilter = '') {
    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (typeFilter) params.append('type', typeFilter);
    if (statusFilter) params.append('status', statusFilter);

    const queryString = params.toString();
    const endpoint = queryString ? `/users?${queryString}` : '/users';

    return await this.request(endpoint, {
      method: 'GET',
    });
  },

  /**
   * GET /api/users/:id
   * Get user by ID
   */
  async getUser(userId) {
    return await this.request(`/users/${userId}`, {
      method: 'GET',
    });
  },

  /**
   * POST /api/users
   * Create a new user
   */
  async createUser(data) {
    const payload = this.normalizeUserPayload(data);
    return await this.request('/users', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
  },

  /**
   * PUT /api/users/:id
   * Update user profile
   */
  async updateUser(userId, data) {
    const payload = this.normalizeUserPayload(data);
    return await this.request(`/users/${userId}`, {
      method: 'PUT',
      body: JSON.stringify(payload),
    });
  },

  /**
   * PATCH /api/users/:id/status
   * Update user account status
   */
  async updateUserStatus(userId, status) {
    return await this.request(`/users/${userId}/status`, {
      method: 'PATCH',
      body: JSON.stringify({ accountStatus: status }),
    });
  },

  /**
   * GET /api/health
   * Check backend health
   */
  async health() {
    return await this.request('/health', {
      method: 'GET',
    });
  },
};

// Export for module usage if needed
if (typeof module !== 'undefined' && module.exports) {
  module.exports = API;
}
