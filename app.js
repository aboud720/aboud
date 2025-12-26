const express = require('express');
const bcrypt = require('bcrypt');
const app = express();

// In-memory user store (for demonstration)
const users = [];
// Fixed: Using Map for O(1) user lookup by ID instead of O(n) find()
const usersById = new Map();

// Middleware to parse JSON
app.use(express.json());

// User registration endpoint
app.post('/register', async (req, res) => {
  const { username, password } = req.body;
  
  // Input validation for username
  if (!username || username.length < 3) {
    return res.status(400).json({ error: 'Username must be at least 3 characters' });
  }
  
  // Fixed: Added comprehensive password validation
  // Check for empty/null password
  if (!password) {
    return res.status(400).json({ error: 'Password is required' });
  }
  
  // Fixed: Enforce minimum password length for security
  if (password.length < 8) {
    return res.status(400).json({ error: 'Password must be at least 8 characters' });
  }
  
  // Fixed: Enforce maximum password length to prevent DoS attacks on bcrypt
  if (password.length > 72) {
    return res.status(400).json({ error: 'Password must not exceed 72 characters' });
  }
  
  // Check if user already exists
  const existingUser = users.find(u => u.username === username);
  if (existingUser) {
    return res.status(400).json({ error: 'Username already exists' });
  }
  
  // Fixed: Using bcrypt for secure password hashing with salt rounds
  const saltRounds = 10;
  const hashedPassword = await bcrypt.hash(password, saltRounds);
  
  const newUser = {
    id: users.length + 1,
    username: username,
    password: hashedPassword
  };
  
  users.push(newUser);
  // Fixed: Store user in Map for O(1) lookup performance
  usersById.set(newUser.id, newUser);
  res.status(201).json({ message: 'User registered successfully', userId: newUser.id });
});

// User login endpoint
app.post('/login', async (req, res) => {
  const { username, password } = req.body;
  
  const user = users.find(u => u.username === username);
  if (!user) {
    return res.status(401).json({ error: 'Invalid credentials' });
  }
  
  // Fixed: Using bcrypt.compare() for secure password verification
  const passwordMatch = await bcrypt.compare(password, user.password);
  if (!passwordMatch) {
    return res.status(401).json({ error: 'Invalid credentials' });
  }
  
  res.json({ message: 'Login successful', userId: user.id });
});

// Get user profile endpoint
app.get('/users/:id', (req, res) => {
  const userId = parseInt(req.params.id);
  
  // Fixed: Using Map.get() for O(1) lookup instead of O(n) find()
  const user = usersById.get(userId);
  
  if (!user) {
    return res.status(404).json({ error: 'User not found' });
  }
  
  // Security: Don't return password hash
  const { password, ...userWithoutPassword } = user;
  res.json(userWithoutPassword);
});

// Get all users endpoint
app.get('/users', (req, res) => {
  // Return all users without passwords
  const usersWithoutPasswords = users.map(u => {
    const { password, ...rest } = u;
    return rest;
  });
  res.json(usersWithoutPasswords);
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
  console.log(`Server running on port ${PORT}`);
});

module.exports = app;
