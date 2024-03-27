// .env.js

const dotenv = require('dotenv');

// Load environment variables from .env file
dotenv.config();

module.exports = {
  APP_NAME: process.env.APP_NAME || 'Laravel',
  APP_ENV: process.env.APP_ENV || 'local',
  APP_KEY: process.env.APP_KEY,
  APP_DEBUG: process.env.APP_DEBUG || true,
  APP_URL: process.env.APP_URL || 'http://localhost',
  // Define other environment variables similarly
};
