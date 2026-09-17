# Attendance Tracking System DOREMI

![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Firebase](https://img.shields.io/badge/Firebase-039BE5?style=for-the-badge&logo=Firebase&logoColor=white)
![Google Gemini](https://img.shields.io/badge/Google%20Gemini-8E75B2?style=for-the-badge&logo=google%20gemini&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)

A dual-platform HR administration and management system developed for Doremi Services & Rental Sdn. Bhd. This system streamlines attendance tracking, leave management, and staff directories through a powerful Laravel web portal, backed by a serverless Google Cloud Firestore database.

It features advanced Generative AI integrations via the Google Gemini API to assist HR administrators with intelligent insights and decision-making.

## 🚀 Key Features

### 📅 Core HR Modules
* **Real-time Attendance Tracking**: Monitors staff clock-ins and clock-outs seamlessly, ensuring accurate timekeeping synchronized instantly via Firebase Firestore.
* **Comprehensive Leave Management**: Allows employees to submit leave requests while providing HR administrators with an intuitive dashboard to approve, reject, and track leave balances.
* **Staff Directory & Task Assignment**: Centralized database for employee information and an integrated system for assigning and tracking daily operational tasks.

### 🧠 AI-Powered Analytics (Powered by Google Gemini API)
* **Overtime & Fatigue Pattern Analysis**: Intelligently analyzes 90-day historical overtime data to identify fatigue risks, specifically flagging consecutive shifts with under 8-hour rest gaps to ensure employee well-being.
* **Predictive Leave Trend Insights**: Uses historical data to identify seasonal leave patterns, helping management proactively plan resource allocation during peak absence periods.
* **Automated KPI Review Summaries**: Consolidates employee performance metrics into concise, easy-to-read qualitative summaries for rapid performance reviews.
* **Flexi-credit Utilization Engine**: Analyzes employee benefit usage to recommend optimal utilization of staff flexi-credits.

### 🛡️ System Architecture & Reliability
* **Serverless Backend**: Utilizes Google Cloud Firestore as a scalable, real-time NoSQL backend to guarantee lightning-fast data retrieval across platforms.
* **Resilient Fallback Mechanism**: Features custom local PHP fallback logic that ensures core HR admin functions (like basic attendance and leave tracking) remain fully operational even if the external AI APIs experience downtime.
* **Cross-Platform Synchronization**: Serves as the central command portal (built in Laravel) that syncs data bidirectionally with a partner-developed Flutter mobile application for staff on-the-go.

### 📊 Reporting & Compliance
* **Automated PDF Exporting**: Integrates DomPDF to generate official, formatted PDF reports for monthly attendance, leave records, and AI analytics to meet company audit requirements.

## 🛠️ Tech Stack

* **Framework:** Laravel 12 (PHP 8.2+)
* **Database:** Google Cloud Firestore (NoSQL) via `kreait/laravel-firebase`
* **AI Integration:** Google Gemini API
* **Frontend:** Blade Templates, JavaScript, HTML, CSS, Tailwind CSS (via Laravel Breeze)
* **Testing:** Pest PHP
* **Utilities:** DomPDF for report generation

## ⚙️ Installation & Setup

If you wish to run this project locally, follow these steps:

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/doremi-hr-system.git
   cd doremi-hr-system
   ```

2. **Install PHP Dependencies**
   ```bash
   composer install
   ```

3. **Install NPM Dependencies**
   ```bash
   npm install
   npm run build
   ```

4. **Environment Setup**
   Copy the example environment file and generate an application key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Firebase / Firestore Configuration**
   You will need to add your Firebase credentials. Place your Firebase Service Account JSON file in the project and update the `.env` variables accordingly:
   ```env
   FIREBASE_CREDENTIALS=/path/to/your/firebase_credentials.json
   FIREBASE_DATABASE_URL=https://your-project-id.firebaseio.com
   ```

6. **Gemini API Configuration**
   Add your Gemini API key to the `.env` file:
   ```env
   GEMINI_API_KEY=your_api_key_here
   ```

7. **Run the Development Server**
   ```bash
   php artisan serve
   ```
   Visit `http://localhost:8000` in your browser.

## 📄 License

This project is proprietary software developed for Doremi Services & Rental Sdn. Bhd.
