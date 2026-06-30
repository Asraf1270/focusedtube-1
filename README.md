# 🎬 FocusedTube

[![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/yourusername/focusedtube)
[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](CONTRIBUTING.md)

**FocusedTube** is a self-hosted YouTube video library that stores metadata without downloading videos. Watch your favorite YouTube content without distractions, ads, or recommendations. Perfect for educational institutions, content curators, or anyone who wants a focused video watching experience.

![FocusedTube Screenshot](https://via.placeholder.com/800x400?text=FocusedTube+Demo)

---

## ✨ Features

### 📺 Video Management
- Import videos from YouTube using URL
- Auto-fetch metadata (title, description, thumbnail, duration, statistics)
- YouTube embed player integration
- Video categories and tags
- Related videos recommendations
- View count tracking
- Watch history

### 🔍 Search & Discovery
- Full-text search across titles, descriptions, channels, and tags
- Advanced filters (category, tags)
- Sort by relevance, newest, oldest, most views
- Search suggestions with autocomplete

### 👤 User Features
- User registration and authentication
- Watch later playlist
- Favorites
- Custom playlists
- Watch history
- Comments on videos
- Like/Dislike videos
- User profiles

### 🎨 Modern UI
- Mobile-first responsive design
- Dark/Light mode toggle
- Glassmorphism effects
- Smooth animations
- Loading skeletons
- Infinite scroll
- Lazy loading images
- YouTube-inspired layout (not a copy)

### 🔧 Admin Panel
- Dashboard with analytics
- Video management (import, edit, delete)
- User management (roles, status)
- Category management
- Tag management
- System settings
- API configuration
- Backup & restore
- Activity logs
- Maintenance mode

### 🛡️ Security
- CSRF protection
- XSS prevention
- Input validation
- Password hashing (bcrypt)
- Session security
- Rate limiting
- Role-based access control
- Secure headers

### ⚡ Performance
- File-based JSON database (no MySQL required)
- Page caching
- API response caching
- Optimized assets
- Gzip compression
- Image lazy loading
- Minified CSS/JS

---

## 🚀 Quick Start

### Requirements
- PHP 8.0 or higher
- Apache/Nginx with mod_rewrite
- YouTube Data API v3 key

### Installation

1. **Clone the repository**
```bash
git clone https://github.com/yourusername/focusedtube.git
cd focusedtube