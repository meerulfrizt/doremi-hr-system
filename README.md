# Attendance Tracking System DOREMI

![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Firebase](https://img.shields.io/badge/Firebase-039BE5?style=for-the-badge&logo=Firebase&logoColor=white)
![Google Gemini](https://img.shields.io/badge/Google%20Gemini-8E75B2?style=for-the-badge&logo=google%20gemini&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)

A dual-platform HR administration and management system developed for Doremi Services & Rental Sdn. Bhd. This system streamlines attendance tracking, leave management, and staff directories through a powerful Laravel web portal, backed by a serverless Google Cloud Firestore database.

It features advanced Generative AI integrations via the Google Gemini API to assist HR administrators with intelligent insights and decision-making.

## 🚀 Key Features

* **Attendance & Leave Management**: Real-time tracking of staff clock-ins/outs and leave applications synchronized via Firebase Firestore.
* **AI-Powered Analytics (Gemini API)**:
  * **Overtime Pattern Analysis**: Analyzes 90-day historical data to flag fatigue risks (e.g., shifts with under 8-hour gaps).
  * **Leave Trend Insights**: Identifies seasonal leave patterns to aid resource planning.
  * **Automated KPI Summaries**: Generates concise performance review summaries.
  * **Flexi-credit Utilization**: Recommends optimal usage of staff flexi-credits.
* **Resilient Architecture**: Built-in local PHP fallback logic ensures core admin functions remain accessible even during external API outages.
* **Audit & Export**: Automated PDF generation for attendance and analytics records using DomPDF to meet compliance and audit requirements.
* **Cross-Platform Sync**: Acts as the central admin portal, synchronizing data seamlessly with a partner-built Flutter mobile application.

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
