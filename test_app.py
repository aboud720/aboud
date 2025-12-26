"""
Test suite for the application
"""
import unittest
from app import calculate_discount, hash_password
from werkzeug.security import check_password_hash

class TestApp(unittest.TestCase):
    
    def test_calculate_discount_normal(self):
        """Test normal discount calculation"""
        result = calculate_discount(100, 0.1)
        self.assertEqual(result, 90)
    
    def test_calculate_discount_full(self):
        """Test full discount (100%)"""
        result = calculate_discount(100, 1.0)
        self.assertEqual(result, 0)
    
    def test_calculate_discount_over_100_percent(self):
        """Test over 100% discount - should raise ValueError"""
        with self.assertRaises(ValueError):
            calculate_discount(100, 1.5)
    
    def test_calculate_discount_negative_rate(self):
        """Test negative discount rate - should raise ValueError"""
        with self.assertRaises(ValueError):
            calculate_discount(100, -0.1)
    
    def test_calculate_discount_negative_amount(self):
        """Test negative amount - should raise ValueError"""
        with self.assertRaises(ValueError):
            calculate_discount(-100, 0.1)
    
    def test_hash_password(self):
        """Test password hashing uses secure algorithm"""
        password = "testpassword123"
        hashed = hash_password(password)
        
        # Verify hash is not the original password
        self.assertIsNotNone(hashed)
        self.assertNotEqual(hashed, password)
        
        # Verify hash is werkzeug pbkdf2:sha256 format
        self.assertTrue(hashed.startswith('pbkdf2:sha256:'))
        
        # Verify password can be checked correctly
        self.assertTrue(check_password_hash(hashed, password))
        self.assertFalse(check_password_hash(hashed, "wrongpassword"))

if __name__ == '__main__':
    unittest.main()
