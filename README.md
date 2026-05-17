# 📋 Task Tracker System

A modern, full-featured student task management web application built with **PHP**, **MySQL**, **HTML/CSS**, and **vanilla JavaScript** — no frameworks required.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.x-4479A1?logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6-F7DF1E?logo=javascript&logoColor=black)
![License](https://img.shields.io/badge/License-MIT-green)

---

## 📖 Overview

Task Tracker is a productivity web app designed for students to manage their academic and personal tasks. It features a clean split-screen authentication flow, a sidebar-based dashboard, real-time task filtering and sorting, a calendar view, category management, analytics reports, and a full account recovery system with email OTP verification.

---

## ✨ Features

### 🔐 Authentication & Security
- Secure login and registration with `password_hash()` / `password_verify()`
- **Forgot Password** flow with 6-digit email OTP verification
- OTP expiration (10 minutes), rate limiting, and max-attempt lockout
- Session-based authentication scoped per user
- All queries use **prepared statements** (SQL injection prevention)

### 📊 Dashboard
- Live statistics: Total, Completed, Pending, Overdue tasks
- Add new tasks inline with title, deadline, and category
- Real-time task list with status badges and deadline indicators
- Category filter bar populated dynamically from the database

### ✅ Task Management
- Add, edit, complete, and delete tasks
- Smart sorting: Deadline Ascending/Descending, A–Z, Z–A
- Filter by status: All / Completed / Pending / Overdue
- Real-time search by task title
- Pagination (10 tasks per page)
- Overdue detection based on today's date

### 🏷️ Categories
- Create and delete custom categories
- Categories sync across all pages (dashboard, all tasks, filters)
- Task count per category displayed live

### 📅 Calendar
- **Month view** — grid with task dots (green/yellow/red by status)
- **Week view** — 7-column grid with task pills
- **Day view** — detailed task list for a selected day
- Navigate between months/weeks/days with prev/next controls

### 📈 Reports
- Donut chart: Task status distribution (Completed / Pending / Overdue)
- Bar chart: Tasks by category
- Recent tasks activity list

### 👤 Profile Management
- Edit First Name, Last Name, Username, Email
- Change password with current password verification
- Password strength meter
- Live UI update after save (no page reload)

### 📱 Mobile Responsive
- Hamburger menu with slide-in sidebar
- Touch-friendly inputs and buttons
- Responsive layouts for all screen sizes (320px → desktop)
- No horizontal overflow on any page

---

## 🛠 Tech Stack

| Layer      | Technology                          |
|------------|-------------------------------------|
| Backend    | PHP 8 (procedural)                  |
| Database   | MySQL 8 via MySQLi                  |
| Frontend   | HTML5, CSS3, Vanilla JavaScript ES6 |
| Server     | Apache (XAMPP)                      |
| Email      | SMTP via raw PHP socket (no library)|
| Fonts      | Inter (system font stack, no CDN)   |

---

## 🚀 Getting Started

### Prerequisites

- [XAMPP](https://www.apachefriends.org/) v8.x or any Apache + PHP 8 + MySQL stack
- Git

### Installation

**1. Clone the repository**

```bash
git clone https://github.com/ShanneGabrielCastillo/task-tracker.git
cd task-tracker
```

**2. Move to your web server root**

```
C:\xampp\htdocs\task-tracker3\
```

**3. Create the database**

- Open **phpMyAdmin** → `http://localhost/phpmyadmin`
- Create a new database: `task_tracker3`
- Import `task_tracker.sql`

**4. Configure the database connection**

```bash
cp db.example.php db.php
```

Edit `db.php` with your credentials:

```php
$host = 'localhost';
$user = 'root';          // your MySQL username
$pass = '';              // your MySQL password
$db   = 'task_tracker3';
```

**5. Configure email (for Forgot Password OTP)**

Edit `mailer.php`:

```php
define('MAIL_USER', 'your_gmail@gmail.com');
define('MAIL_PASS', 'your_16_char_app_password');  // Gmail App Password
define('MAIL_FROM', 'your_gmail@gmail.com');
```

> To generate a Gmail App Password: Google Account → Security → 2-Step Verification → App Passwords

**6. Start the app**

Start Apache and MySQL in XAMPP, then open:

```
http://localhost/task-tracker3/index.php
```

---

## 📁 Project Structure

```
task-tracker3/
│
├── 📄 index.php              # Login page
├── 📄 register.php           # Registration page
├── 📄 forgot_password.php    # Step 1: Request OTP
├── 📄 verify_otp.php         # Step 2: Verify OTP
├── 📄 reset_password.php     # Step 3: Set new password
│
├── 📄 dashboard.php          # Main dashboard
├── 📄 all_tasks.php          # All tasks table view
├── 📄 calendar.php           # Calendar (Month/Week/Day)
├── 📄 categories.php         # Category management
├── 📄 reports.php            # Analytics & charts
├── 📄 profile.php            # Edit profile page
├── 📄 edit_task.php          # Edit task form
│
├── 📄 add_task.php           # API: create task
├── 📄 update_task.php        # API: update task
├── 📄 delete_task.php        # API: delete task
├── 📄 update_profile.php     # API: update profile/password
├── 📄 category_add.php       # API: create category
├── 📄 category_delete.php    # API: delete category
├── 📄 get_categories.php     # API: fetch categories
│
├── 📄 auth.php               # Session authentication guard
├── 📄 user_helper.php        # Display name helper (session cache)
├── 📄 mailer.php             # SMTP email helper + OTP email template
├── 📄 logout.php             # Logout handler
│
├── 📄 db.php                 # DB connection (excluded from repo)
├── 📄 db.example.php         # DB connection template
├── 📄 task_tracker.sql       # Full database schema
│
├── 📄 script.js              # Dashboard task logic (render/filter/sort)
├── 📄 sidebar.js             # Mobile hamburger menu
└── 📄 style.css              # All styles (4000+ lines, fully documented)
```

---

## 🗄️ Database Schema

```sql
users              — id, username, email, password, first_name, last_name
tasks              — id, user_id, title, deadline, status, category
categories         — id, user_id, name, description, created_at
password_resets    — id, user_id, otp_hash, reset_token, expires_at, used, verified, attempts
```

---

## 📸 Screenshots
<img width="1363" height="635" alt="image" src="https://github.com/user-attachments/assets/ca7e6702-94ff-4c92-babc-8bc7498d69ff" />
<img width="1351" height="628" alt="image" src="https://github.com/user-attachments/assets/403e2b5d-4914-46ff-acc3-ca864cc54b09" />
<img width="1364" height="631" alt="image" src="https://github.com/user-attachments/assets/66e90d35-5311-45fa-97cb-b9e36ffbdec4" />
<img width="1365" height="631" alt="image" src="https://github.com/user-attachments/assets/c9727fab-ec86-4378-bb90-d46c4f433678" />
<img width="1365" height="630" alt="image" src="https://github.com/user-attachments/assets/c8cbc120-5188-475f-91f1-fc818d39dca8" />
<img width="1350" height="632" alt="image" src="https://github.com/user-attachments/assets/22a4fb30-67aa-4361-a735-0d211fc9b20d" />
<img width="1347" height="625" alt="image" src="https://github.com/user-attachments/assets/e8cb46de-44dc-44fe-8257-8829111ab2fe" />
<img width="1348" height="627" alt="image" src="https://github.com/user-attachments/assets/e8d9319a-c160-48a9-9969-7cce06884ae2" />
<img width="1351" height="632" alt="image" src="https://github.com/user-attachments/assets/4970d836-2625-48e5-85f4-926139498eb9" />
<img width="1357" height="634" alt="image" src="https://github.com/user-attachments/assets/d85ecf44-35fa-4fad-bb4c-91aaa76a89e5" />
<img width="1361" height="630" alt="image" src="https://github.com/user-attachments/assets/9d41b05a-0872-4bd7-bb06-28030008a4e4" />
<img width="1341" height="627" alt="image" src="https://github.com/user-attachments/assets/f3ccd0b5-da9f-4a7e-82b6-f965a2a520d4" />
<img width="1350" height="629" alt="image" src="https://github.com/user-attachments/assets/4aad5d6d-3518-4285-bb40-52920b5cecd0" />
<img width="1348" height="624" alt="image" src="https://github.com/user-attachments/assets/6ca2ed00-6fcb-40b4-91bd-fa737cea4b95" />
<img width="720" height="1600" alt="47530701-68d4-447d-a195-1df1161ec6fa" src="https://github.com/user-attachments/assets/570ca3ed-e920-4e80-a64d-ea1c687fd4ed" />
<img width="720" height="1600" alt="960024ef-9b8b-4938-b647-60fdfbc3eaf0" src="https://github.com/user-attachments/assets/10ad3d46-b4a6-4c2a-9b3d-2dfee31f7e81" />
<img width="720" height="1600" alt="ec146fb8-9726-461b-95df-d2a5c4b6a185" src="https://github.com/user-attachments/assets/138319cb-0ba3-4047-ae75-110309aa7f79" />
<img width="720" height="1600" alt="733d3881-3d47-4eb4-a462-d03439af6b82" src="https://github.com/user-attachments/assets/08061b74-23cf-4446-a8a5-91ecbaf2d0c8" />
<img width="720" height="1600" alt="2f3e1b43-fcdd-4a2a-8b6f-671389925298" src="https://github.com/user-attachments/assets/dfcb5492-8c7e-4d8d-b095-0056c1b2eede" />
<img width="720" height="1600" alt="17f41ded-d0b8-4b78-92b7-0048bd1efa6e" src="https://github.com/user-attachments/assets/131646a8-7f66-4b1e-ac6d-06540eb16c75" />
<img width="720" height="1600" alt="97fd0ec9-2082-47dd-875f-bbf7435bb311" src="https://github.com/user-attachments/assets/929d5a53-1a41-44f4-8dfd-6b88ef44c04c" />
<img width="720" height="1600" alt="694f69f3-5662-406a-b62e-252d00023836" src="https://github.com/user-attachments/assets/038b847f-c454-42c8-9aad-28571fe56217" />
<img width="720" height="1600" alt="4897f106-d5cf-4b71-b514-75f3bf9df319" src="https://github.com/user-attachments/assets/b143b0b2-e5e4-4998-aa60-de3ca5ec4106" />
<img width="720" height="1600" alt="288f7686-d100-4249-9847-a5f8674f59c7" src="https://github.com/user-attachments/assets/75b70bea-d22f-44fc-b05e-2b36367bc6e8" />
<img width="720" height="1600" alt="5147aa90-6aad-4e96-a387-31f84532e2b7" src="https://github.com/user-attachments/assets/6bb41d10-a24e-42fd-8862-95d7a8d488ae" />
<img width="720" height="1600" alt="0756536a-4b45-4aa3-8ed8-b9ac2634494c" src="https://github.com/user-attachments/assets/ce616f3c-7bae-4970-929a-664e052ee92a" />


---

## 🔒 Security Notes

| Feature | Implementation |
|---|---|
| Password storage | `password_hash()` with `PASSWORD_DEFAULT` (bcrypt) |
| SQL injection | All queries use MySQLi prepared statements |
| XSS prevention | All output uses `htmlspecialchars()` |
| OTP security | Stored as bcrypt hash, expires in 10 min, max 5 attempts |
| Session isolation | All data scoped to `$_SESSION['user_id']` |
| CSRF | Forms use POST; API endpoints validate session before any write |

---

## 🌐 Running with ngrok (Public URL)

To share your local app publicly:

```bash
ngrok http 80
```

Then visit: `https://your-subdomain.ngrok-free.app/task-tracker3/index.php`

> Note: `ngrok.exe` is excluded from the repository via `.gitignore`.

---

## 🔮 Future Improvements

- [ ] Push notifications for upcoming deadlines
- [ ] Task reminders via email
- [ ] Team/shared task boards
- [ ] Dark mode toggle
- [ ] Drag-and-drop task reordering
- [ ] File attachments on tasks
- [ ] Export tasks to CSV/PDF
- [ ] Google Calendar sync

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Commit your changes: `git commit -m "Add your feature"`
4. Push to the branch: `git push origin feature/your-feature`
5. Open a Pull Request

---

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

---

*Built with ❤️ as a student productivity tool.*
