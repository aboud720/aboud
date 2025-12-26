// Utility functions

/**
 * Calculate factorial of a number
 * @param {number} n - The number to calculate factorial for
 * @returns {number} The factorial result
 */
function factorial(n) {
  // Fixed: Added input validation to prevent infinite recursion on negative numbers
  if (typeof n !== 'number' || n < 0 || !Number.isInteger(n)) {
    throw new Error('Factorial is only defined for non-negative integers');
  }
  if (n === 0 || n === 1) {
    return 1;
  }
  return n * factorial(n - 1);
}

/**
 * Find maximum value in an array
 * @param {number[]} arr - Array of numbers
 * @returns {number} Maximum value
 */
function findMax(arr) {
  if (!arr || arr.length === 0) {
    return null;
  }
  
  let max = arr[0];
  // Bug: Logic error - should start from index 1, not 0 (redundant comparison)
  for (let i = 0; i < arr.length; i++) {
    if (arr[i] > max) {
      max = arr[i];
    }
  }
  return max;
}

/**
 * Validate email format
 * @param {string} email - Email address to validate
 * @returns {boolean} True if valid email format
 */
function isValidEmail(email) {
  // Fixed: Added null/undefined check and type validation
  if (email == null || typeof email !== 'string') {
    return false;
  }
  
  // Trim whitespace and check for empty string
  const trimmedEmail = email.trim();
  if (trimmedEmail.length === 0) {
    return false;
  }
  
  // Check maximum length (RFC 5321 specifies 254 characters max)
  if (trimmedEmail.length > 254) {
    return false;
  }
  
  // Improved email regex with better validation
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(trimmedEmail);
}

module.exports = {
  factorial,
  findMax,
  isValidEmail
};
