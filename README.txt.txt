PROJECT TITLE:
Booklend A Textbook & Reviewer Lending System
 
GITHUB REPOSITORY:
https://github.com/bbblueshades/Booklend.git
 
LIVE APPLICATION
----- NA -----
 
TECHNOLOGY STACK
Frontend: 		HTML5, CSS3, JavaScript ES6+ (modules, async/await, Fetch API), 
			Bootstrap 5, Chart.js

Backend: 		PHP (PDO with prepared statements) on Apache 

Database: 		MySQL  MariaDB (5 tables users, subjects, books, 
			borrow_requests, notifications)

API:			Open Library API (book lookup and covers) and the project's own PHP
			JSON API (books, requests, notifications, profile, admin)

ModernEdge Features:	QR code integration (QR generation per book and camera scanning for handover and return), 
			in-system notifications with automatic refresh, dark mode saved in localStorage, 
			charts and data visualization (Chart.js) with CSV export
 
ADMIN ACCOUNT:
Email: 			admin@booklend.local
Password: 		password
 
NOTES
- A regular user account can be created from the Register page. Two accounts 
  are needed to test the full borrowing flow one owner and one borrower.

- Borrowing flow Requested - Approved - Borrowed - Returned (or Rejected). 
  The owner approves or rejects, then scans the book's QR code to confirm 
  the handover and, later, the return. The loan period is 14 days.

- QR scanning uses the device camera. Allow camera access, and use the
  live HTTPS URL (browsers block the camera on plain HTTP).

- To run locally (XAMPP) start Apache and MySQL, import
  database/database.sql (it creates the booklend database), copy
  includesconfig.example.php to includesconfig.php and set the database
  credentials, then open http://localhost/booklend/public/login.php

- config.php is not included in the GitHub repository because it holds
  database credentials.