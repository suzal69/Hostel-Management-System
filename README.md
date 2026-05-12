# Hostel Management System 🏠 

## Project Overview 
A comprehensive management solution designed to automate student records, room allocations, and fee tracking for hostels. This project bridges the gap between manual record-keeping and digital efficiency. 

## Features 
* **Student Management:** Register, update, and remove student profiles. 
* **Room Allocation:** Track room availability and assign students to specific wings. 
* **Fee Tracking:** Monitor payment statuses and generate basic financial reports. 
* **Search Functionality:** Quickly find student details by ID or Name. 

## 🚀 Key Feature: Smart Complaint Priority 
Unlike traditional systems, this project includes a **Python-based Analysis Engine** that scans student complaints. By evaluating the "weight" of the text, it automatically flags urgent issues (e.g., "Water leakage") as **High Priority**, helping hostel wardens address critical problems first. 

## 🔒 Key Feature: Secure Email Verification
To prevent unauthorized access, the system uses **PHPMailer** to send unique, time-sensitive authentication links. This multi-step verification process ensures that student data remains private and that every account is tied to a valid email address.

## Tech Stack 
* **Frontend:** HTML5, CSS3, JavaScript (for interactive UI and dynamic forms). 
* **Backend:** PHP (Server-side logic and session management). 
* **Database:** MySQL (Relational data storage for students, rooms, and billing). 
* **Intelligence Layer:** Python (Natural Language Processing used to categorize complaint severity into High, Medium, or Low). 
* **Tools:** VS Code, XAMPP/WAMP. 

## Why I Built This 
This project was developed as my **BIM 6th Semester Summer Project** at Tribhuvan University. The goal was to solve the real-world inefficiencies of paper-based record-keeping in student accommodations. I focused on improving **data integrity** through MySQL and enhancing **user accessibility** with a dynamic web interface, while adding an analytical layer with Python to prioritize student concerns.
