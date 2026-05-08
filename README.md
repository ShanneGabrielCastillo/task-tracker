# 📋 Task Tracker System

A modern, full-featured student task management web application built with PHP, MySQL, HTML, CSS, and vanilla JavaScript.

---

## ✨ Features

- **Authentication** — Secure login and registration with password hashing
- **Dashboard** — Overview with live statistics (Total, Completed, Pending, Overdue)
- **Task Management** — Add, edit, complete, and delete tasks
- **Smart Sorting** — Sort tasks by deadline or alphabetically (A–Z / Z–A)
- **Filtering** — Filter by status (All / Completed / Pending / Overdue) and category
- **Real-time Search** — Instant task search without page reload
- **Categories** — Create and delete custom task categories
- **Calendar View** — Month, Week, and Day views with task indicators
- **Reports** — Donut chart for task status distribution and bar chart by category
- **Responsive Design** — Works on mobile, tablet, and desktop
- **Modern UI** — Split-screen login, sidebar navigation, smooth animations

---

## 🛠 Tech Stack

| Layer      | Technology                        |
|------------|-----------------------------------|
| Backend    | PHP 8 (procedural)                |
| Database   | MySQL via MySQLi                  |
| Frontend   | HTML5, CSS3, Vanilla JavaScript   |
| Server     | Apache (XAMPP)                    |
| Fonts      | Inter (system font stack)         |

---

## 🚀 Getting Started

### Prerequisites

- [XAMPP](https://www.apachefriends.org/) (or any Apache + PHP + MySQL stack)
- Git

### Installation

**1. Clone the repository**

```bash
git clone https://github.com/yourusername/task-tracker.git
```

**2. Move to your web server directory**

```
C:\xampp\htdocs\task-tracker\
```

**3. Set up the database**

- Open **phpMyAdmin** at `http://localhost/phpmyadmin`
- Create a new database named `task_tracker3`
- Import the `task_tracker.sql` file

**4. Configure the database connection**

Copy the example config file and fill in your credentials:

```bash
cp db.example.php db.php
```

Edit `db.php`:

```php
$host = 'localhost';
$user = 'root';       // your MySQL username
$pass = '';           // your MySQL password
$db   = 'task_tracker3';
```

**5. Run the app**

Start Apache and MySQL in XAMPP, then open:

```
http://localhost/task-tracker/index.php
```

---

## 📁 Project Structure

```
task-tracker/
├── index.php            # Login page
├── register.php         # Registration page
├── dashboard.php        # Main dashboard
├── all_tasks.php        # All tasks table view
├── calendar.php         # Calendar view
├── categories.php       # Category management
├── reports.php          # Analytics & reports
├── edit_task.php        # Edit task form
├── add_task.php         # Add task API endpoint
├── update_task.php      # Update task API endpoint
├── delete_task.php      # Delete task API endpoint
├── category_add.php     # Add category API endpoint
├── category_delete.php  # Delete category API endpoint
├── get_categories.php   # Fetch categories API endpoint
├── auth.php             # Session authentication guard
├── db.php               # Database connection (not in repo)
├── db.example.php       # Database config template
├── logout.php           # Logout handler
├── script.js            # Client-side task logic
├── style.css            # All styles
└── task_tracker.sql     # Database schema
```

---

## 📸 Screenshots

> _Add screenshots of your app here_

---

## 🔒 Security Notes

- Passwords are hashed using `password_hash()` with `PASSWORD_DEFAULT`
- All database queries use **prepared statements** to prevent SQL injection
- User input is sanitized with `htmlspecialchars()` before output
- Tasks are scoped to the authenticated user — no cross-user data access

---

## 📄 License

This project is open source and available under the [MIT License](LICENSE).
