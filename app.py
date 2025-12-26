"""
Simple user authentication and data processing application
"""
import sqlite3
from flask import Flask, request, jsonify
from werkzeug.security import generate_password_hash, check_password_hash

app = Flask(__name__)

# Database setup
def init_db():
    conn = sqlite3.connect('users.db')
    cursor = conn.cursor()
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY,
            username TEXT UNIQUE,
            password TEXT,
            email TEXT,
            is_admin INTEGER DEFAULT 0
        )
    ''')
    conn.commit()
    conn.close()

# User login endpoint
@app.route('/login', methods=['POST'])
def login():
    username = request.json.get('username')
    password = request.json.get('password')
    
    # Query database for user
    conn = sqlite3.connect('users.db')
    cursor = conn.cursor()
    query = "SELECT * FROM users WHERE username = ?"
    cursor.execute(query, (username,))
    user = cursor.fetchone()
    conn.close()
    
    # Verify password using secure hash comparison
    if user and check_password_hash(user[2], password):
        return jsonify({"success": True, "message": "Login successful"})
    else:
        return jsonify({"success": False, "message": "Invalid credentials"}), 401

# Password hashing function
def hash_password(password):
    """Hash password using werkzeug's secure pbkdf2:sha256"""
    return generate_password_hash(password, method='pbkdf2:sha256')

# User registration endpoint
@app.route('/register', methods=['POST'])
def register():
    username = request.json.get('username')
    password = request.json.get('password')
    email = request.json.get('email')
    
    hashed_password = hash_password(password)
    
    conn = sqlite3.connect('users.db')
    cursor = conn.cursor()
    try:
        cursor.execute(
            "INSERT INTO users (username, password, email) VALUES (?, ?, ?)",
            (username, hashed_password, email)
        )
        conn.commit()
        return jsonify({"success": True, "message": "User registered successfully"})
    except sqlite3.IntegrityError:
        return jsonify({"success": False, "message": "Username already exists"}), 400
    finally:
        conn.close()

# Data processing function
def process_user_data(user_ids):
    """Process data for multiple users"""
    conn = sqlite3.connect('users.db')
    cursor = conn.cursor()
    
    results = []
    for user_id in user_ids:
        cursor.execute("SELECT * FROM users WHERE id = ?", (user_id,))
        user = cursor.fetchone()
        if user:
            results.append({
                'id': user[0],
                'username': user[1],
                'email': user[3]
            })
    
    conn.close()
    return results

# Endpoint to get multiple users
@app.route('/users', methods=['POST'])
def get_users():
    user_ids = request.json.get('user_ids', [])
    users = process_user_data(user_ids)
    return jsonify(users)

# Calculate discount based on purchase amount
def calculate_discount(amount, discount_rate):
    """Calculate discount for a purchase"""
    # Validate inputs
    if amount < 0:
        raise ValueError("Amount cannot be negative")
    
    if discount_rate < 0 or discount_rate > 1.0:
        raise ValueError("Discount rate must be between 0 and 1.0 (0% to 100%)")
    
    discount = amount * discount_rate
    final_amount = amount - discount
    
    return final_amount

# Checkout endpoint
@app.route('/checkout', methods=['POST'])
def checkout():
    try:
        amount = float(request.json.get('amount', 0))
        discount_rate = float(request.json.get('discount_rate', 0))
        
        final_amount = calculate_discount(amount, discount_rate)
        return jsonify({"success": True, "final_amount": final_amount})
    except ValueError as e:
        return jsonify({"success": False, "error": str(e)}), 400
    except Exception as e:
        return jsonify({"success": False, "error": "Invalid input"}), 400

if __name__ == '__main__':
    init_db()
    app.run(debug=True)
