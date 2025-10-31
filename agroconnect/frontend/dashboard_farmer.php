<?php
// Session validation and authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Check if user is a farmer
if ($_SESSION['role'] !== 'farmer') {
    die('<h1>Access Denied</h1><p>This dashboard is for farmers only.</p>');
}

// Get user data from session
$userId = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'];
$hubId = $_SESSION['hub_id'] ?? null;
$preferredLanguage = $_SESSION['preferred_language'] ?? 'en';
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Dashboard - AgroConnect</title>

    <!-- External Resources -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* CSS Variables - Matching index.php */
        :root {
            --primary-color: #10b981;
            --primary-dark: #059669;
            --primary-light: #34d399;
            --secondary-color: #f59e0b;
            --accent-color: #0ea5e9;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --background-light: #f9fafb;
            --white: #ffffff;
            --border-radius: 12px;
            --border-radius-lg: 20px;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background-light);
            color: var(--text-dark);
            line-height: 1.6;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
        }

        /* Dashboard Layout */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: #f5f5f5;
            border-right: 1px solid #e0e0e0;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: var(--white);
        }

        .sidebar-header h2 {
            font-size: 20px;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            font-size: 13px;
            opacity: 0.9;
        }

        .sidebar-nav {
            padding: 20px 0;
        }

        .nav-item {
            width: 100%;
            padding: 15px 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            background: none;
            border: none;
            color: var(--text-dark);
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: left;
        }

        .nav-item i {
            font-size: 18px;
            width: 20px;
        }

        .nav-item:hover {
            background-color: #e8e8e8;
        }

        .nav-item.active {
            background-color: var(--primary-color);
            color: var(--white);
            border-left: 4px solid var(--primary-dark);
        }

        /* Main Content Area */
        .main-content {
            margin-left: 250px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* Header Bar */
        .dashboard-header {
            background: var(--white);
            border-bottom: 1px solid #e0e0e0;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: var(--shadow-sm);
        }

        .dashboard-title {
            font-size: 24px;
            color: var(--text-dark);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .user-greeting {
            font-size: 15px;
            color: var(--text-dark);
        }

        .user-greeting strong {
            color: var(--primary-color);
        }

        .language-selector-dashboard {
            display: flex;
            gap: 10px;
        }

        .lang-btn {
            padding: 6px 14px;
            border: 2px solid var(--primary-color);
            background: transparent;
            color: var(--primary-color);
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .lang-btn:hover {
            background: var(--primary-color);
            color: var(--white);
        }

        .lang-btn.active {
            background: var(--primary-color);
            color: var(--white);
        }

        /* Content Sections */
        .content-area {
            padding: 30px;
            flex: 1;
        }

        .section {
            display: none;
        }

        .section.active {
            display: block;
        }

        .section-header {
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 28px;
            margin-bottom: 10px;
            color: var(--text-dark);
        }

        .section-description {
            color: var(--text-light);
            font-size: 15px;
        }

        /* Equipment Grid */
        .equipment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .equipment-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .equipment-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .equipment-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background-color: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .equipment-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .equipment-image-placeholder {
            width: 100%;
            height: 180px;
            background: linear-gradient(135deg, #e0e0e0, #f5f5f5);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            color: #9e9e9e;
        }

        .equipment-details {
            padding: 20px;
        }

        .equipment-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-dark);
        }

        .equipment-price {
            font-size: 16px;
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 10px;
        }

        .equipment-owner {
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 15px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .status-badge.active {
            background-color: #4caf50;
            color: var(--white);
        }

        .status-badge.inactive {
            background-color: #9e9e9e;
            color: var(--white);
        }

        .equipment-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: var(--white);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            color: var(--white);
        }

        .btn-secondary:hover {
            background-color: #d97706;
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: var(--white);
        }

        .btn-small {
            padding: 8px 16px;
            font-size: 13px;
        }

        .btn-add {
            margin-bottom: 20px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #d0d0d0;
        }

        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: var(--text-dark);
        }

        .empty-state p {
            font-size: 15px;
        }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-xl);
            width: 90%;
            max-width: 550px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }

        .modal-header {
            padding: 25px 30px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 22px;
            color: var(--text-dark);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: var(--text-light);
            cursor: pointer;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .modal-close:hover {
            background-color: #f0f0f0;
            color: var(--text-dark);
        }

        .modal-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-size: 14px;
        }

        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: var(--border-radius);
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }

        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-error {
            color: #ef4444;
            font-size: 13px;
            margin-top: 5px;
        }

        .modal-footer {
            padding: 20px 30px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        /* Search Bar */
        .search-bar {
            margin-bottom: 20px;
        }

        .search-input {
            width: 100%;
            max-width: 500px;
            padding: 12px 20px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: var(--border-radius-lg);
            font-size: 15px;
            transition: all 0.3s ease;
            background: var(--white);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .search-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
            max-width: 500px;
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 16px;
        }

        /* Bookings List */
        .bookings-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .booking-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            padding: 25px;
            display: flex;
            gap: 20px;
            transition: all 0.3s ease;
        }

        .booking-card:hover {
            box-shadow: var(--shadow-lg);
        }

        .booking-image {
            width: 100px;
            height: 100px;
            border-radius: var(--border-radius);
            object-fit: cover;
            background: linear-gradient(135deg, #e0e0e0, #f5f5f5);
            flex-shrink: 0;
        }

        .booking-info {
            flex: 1;
        }

        .booking-equipment-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text-dark);
        }

        .booking-detail {
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .booking-detail i {
            width: 16px;
            color: var(--primary-color);
        }

        .booking-statuses {
            display: flex;
            gap: 10px;
            margin-top: 12px;
            flex-wrap: wrap;
        }

        .booking-status-badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .booking-status-badge.pending {
            background-color: #9e9e9e;
            color: var(--white);
        }

        .booking-status-badge.approved {
            background-color: #4caf50;
            color: var(--white);
        }

        .booking-status-badge.rejected {
            background-color: #f44336;
            color: var(--white);
        }

        .booking-status-badge.in_progress {
            background-color: #2196f3;
            color: var(--white);
        }

        .booking-status-badge.completed {
            background-color: #616161;
            color: var(--white);
        }

        /* Notifications List */
        .notifications-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .notification-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            padding: 20px;
            display: flex;
            gap: 15px;
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
        }

        .notification-card.unread {
            background-color: #e8f4f8;
            border-left-color: var(--accent-color);
        }

        .notification-card.read {
            border-left-color: #d0d0d0;
        }

        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .notification-icon.success {
            background-color: #d1fae5;
            color: #059669;
        }

        .notification-icon.error {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .notification-icon.info {
            background-color: #dbeafe;
            color: #2563eb;
        }

        .notification-content {
            flex: 1;
        }

        .notification-message {
            font-size: 15px;
            color: var(--text-dark);
            margin-bottom: 6px;
            font-weight: 500;
        }

        .notification-time {
            font-size: 13px;
            color: var(--text-light);
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 90px;
            right: 20px;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 400px;
        }

        .toast {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 16px 20px;
            box-shadow: var(--shadow-xl);
            border-left: 4px solid var(--primary-color);
            transform: translateX(450px);
            opacity: 0;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .toast.show {
            transform: translateX(0);
            opacity: 1;
        }

        .toast.success {
            border-left-color: #10b981;
        }

        .toast.error {
            border-left-color: #ef4444;
        }

        .toast i {
            font-size: 20px;
        }

        .toast.success i {
            color: #10b981;
        }

        .toast.error i {
            color: #ef4444;
        }

        .toast-content {
            flex: 1;
        }

        .toast-title {
            font-weight: 600;
            margin-bottom: 4px;
            font-size: 15px;
        }

        .toast-message {
            font-size: 14px;
            color: var(--text-light);
        }

        /* Loading Spinner */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(16, 185, 129, 0.3);
            border-radius: 50%;
            border-top-color: var(--primary-color);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .loading-overlay.show {
            display: flex;
        }

        .loading-content {
            text-align: center;
        }

        .loading-content .loading-spinner {
            width: 50px;
            height: 50px;
            border-width: 5px;
            margin-bottom: 15px;
        }

        /* Cost Preview */
        .cost-preview {
            background: linear-gradient(135deg, #e8f5f1, #d1fae5);
            padding: 20px;
            border-radius: var(--border-radius);
            margin: 20px 0;
            text-align: center;
        }

        .cost-preview-label {
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 5px;
        }

        .cost-preview-amount {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .cost-preview-duration {
            font-size: 14px;
            color: var(--text-light);
            margin-top: 5px;
        }

        .info-note {
            background-color: #fff7ed;
            border-left: 4px solid var(--secondary-color);
            padding: 12px 15px;
            border-radius: 6px;
            font-size: 14px;
            color: #78350f;
            margin: 15px 0;
        }

        /* Equipment Summary in Booking Modal */
        .equipment-summary {
            background: var(--background-light);
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
        }

        .equipment-summary h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: var(--text-dark);
        }

        .equipment-summary-detail {
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 5px;
        }

        /* Hamburger Menu (Mobile) */
        .hamburger {
            display: none;
            flex-direction: column;
            gap: 5px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 10px;
        }

        .hamburger span {
            width: 25px;
            height: 3px;
            background-color: var(--text-dark);
            border-radius: 2px;
            transition: all 0.3s ease;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .equipment-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .hamburger {
                display: flex;
            }

            .dashboard-header {
                padding: 15px 20px;
            }

            .dashboard-title {
                font-size: 20px;
            }

            .user-greeting {
                display: none;
            }

            .equipment-grid {
                grid-template-columns: 1fr;
            }

            .booking-card {
                flex-direction: column;
            }

            .booking-image {
                width: 100%;
                height: 150px;
            }

            .modal {
                width: 95%;
            }

            .content-area {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <p>Loading...</p>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Dashboard Container -->
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>AgroConnect</h2>
                <p>Farmer Dashboard</p>
            </div>
            <nav class="sidebar-nav">
                <button class="nav-item active" data-section="my-equipment">
                    <i class="fas fa-tractor"></i>
                    <span>My Equipment</span>
                </button>
                <button class="nav-item" data-section="browse-equipment">
                    <i class="fas fa-search"></i>
                    <span>Browse Equipment</span>
                </button>
                <button class="nav-item" data-section="my-bookings">
                    <i class="fas fa-calendar-alt"></i>
                    <span>My Bookings</span>
                </button>
                <button class="nav-item" data-section="notifications">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                </button>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header Bar -->
            <header class="dashboard-header">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <button class="hamburger" id="hamburgerBtn">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                    <h1 class="dashboard-title">Dashboard</h1>
                </div>
                <div class="header-right">
                    <div class="user-greeting">
                        Welcome, <strong><?php echo htmlspecialchars($fullName); ?></strong>
                    </div>
                    <div class="language-selector-dashboard">
                        <button class="lang-btn active" data-lang="en">EN</button>
                        <button class="lang-btn" data-lang="hi">HI</button>
                        <button class="lang-btn" data-lang="kn">KN</button>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <div class="content-area">
                <!-- My Equipment Section -->
                <section id="my-equipment" class="section active">
                    <div class="section-header">
                        <h2 class="section-title">My Equipment</h2>
                        <p class="section-description">Manage your agricultural equipment listings</p>
                    </div>
                    <button class="btn btn-primary btn-add" id="addEquipmentBtn">
                        <i class="fas fa-plus"></i>
                        Add New Equipment
                    </button>
                    <div class="equipment-grid" id="myEquipmentGrid">
                        <!-- Equipment cards will be inserted here -->
                    </div>
                </section>

                <!-- Browse Equipment Section -->
                <section id="browse-equipment" class="section">
                    <div class="section-header">
                        <h2 class="section-title">Browse Equipment</h2>
                        <p class="section-description">Find and book equipment from other farmers</p>
                    </div>
                    <div class="search-bar">
                        <div class="search-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" class="search-input" id="searchInput" placeholder="Search equipment by name...">
                        </div>
                    </div>
                    <div class="equipment-grid" id="browseEquipmentGrid">
                        <!-- Equipment cards will be inserted here -->
                    </div>
                </section>

                <!-- My Bookings Section -->
                <section id="my-bookings" class="section">
                    <div class="section-header">
                        <h2 class="section-title">My Bookings</h2>
                        <p class="section-description">Track your equipment booking requests</p>
                    </div>
                    <div class="bookings-list" id="bookingsList">
                        <!-- Booking cards will be inserted here -->
                    </div>
                </section>

                <!-- Notifications Section -->
                <section id="notifications" class="section">
                    <div class="section-header">
                        <h2 class="section-title">Notifications</h2>
                        <p class="section-description">Stay updated on your booking activities</p>
                    </div>
                    <div class="notifications-list" id="notificationsList">
                        <!-- Notification cards will be inserted here -->
                    </div>
                </section>
            </div>
        </main>
    </div>

    <!-- Add/Edit Equipment Modal -->
    <div class="modal-overlay" id="equipmentModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" id="equipmentModalTitle">Add New Equipment</h3>
                <button class="modal-close" onclick="closeEquipmentModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="equipmentForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="equipmentId" name="equipment_id">

                    <div class="form-group">
                        <label class="form-label">Equipment Name *</label>
                        <input type="text" class="form-input" id="equipmentName" name="equipment_name"
                               placeholder="e.g., Tractor, Harvester" required>
                        <div class="form-error" id="nameError"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-textarea" id="equipmentDescription" name="description"
                                  placeholder="Brief description of the equipment"></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Rental Price *</label>
                        <input type="number" class="form-input" id="rentalPrice" name="rental_price"
                               placeholder="500" min="1" required>
                        <div class="form-error" id="priceError"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Price Unit *</label>
                        <select class="form-select" id="priceUnit" name="price_unit" required>
                            <option value="per_day">Per Day</option>
                            <option value="per_hour">Per Hour</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Equipment Image</label>
                        <input type="file" class="form-input" id="equipmentImage" name="equipment_image"
                               accept="image/jpeg,image/png,image/jpg">
                        <div class="form-error" id="imageError"></div>
                        <small style="color: var(--text-light); font-size: 13px;">Max size: 5MB. Formats: JPG, PNG, JPEG</small>
                    </div>

                    <div class="form-error" id="formError"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeEquipmentModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="equipmentSubmitBtn">
                        <span id="equipmentSubmitText">Add Equipment</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Booking Modal -->
    <div class="modal-overlay" id="bookingModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Book Equipment</h3>
                <button class="modal-close" onclick="closeBookingModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="bookingForm">
                <div class="modal-body">
                    <input type="hidden" id="bookingEquipmentId">
                    <input type="hidden" id="bookingRentalPrice">
                    <input type="hidden" id="bookingPriceUnit">

                    <div class="equipment-summary" id="bookingEquipmentSummary">
                        <!-- Equipment details will be inserted here -->
                    </div>

                    <div class="form-group">
                        <label class="form-label">Start Date & Time *</label>
                        <input type="datetime-local" class="form-input" id="startDateTime" name="start_datetime" required>
                        <div class="form-error" id="startDateError"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">End Date & Time *</label>
                        <input type="datetime-local" class="form-input" id="endDateTime" name="end_datetime" required>
                        <div class="form-error" id="endDateError"></div>
                    </div>

                    <div class="cost-preview" id="costPreview" style="display: none;">
                        <div class="cost-preview-label">Estimated Total</div>
                        <div class="cost-preview-amount" id="costAmount">�0</div>
                        <div class="cost-preview-duration" id="costDuration"></div>
                    </div>

                    <div class="info-note">
                        <i class="fas fa-info-circle"></i>
                        Booking subject to admin approval. You will be notified once approved.
                    </div>

                    <div class="form-error" id="bookingFormError"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeBookingModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="bookingSubmitBtn">
                        <span id="bookingSubmitText">Request Booking</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // User data from PHP session
        const userData = {
            user_id: <?php echo json_encode($userId); ?>,
            full_name: <?php echo json_encode($fullName); ?>,
            hub_id: <?php echo json_encode($hubId); ?>,
            preferred_language: <?php echo json_encode($preferredLanguage); ?>,
            role: <?php echo json_encode($role); ?>
        };

        // Current language
        let currentLanguage = userData.preferred_language;

        // Data caches
        let myEquipmentData = [];
        let availableEquipmentData = [];
        let bookingsData = [];
        let notificationsData = [];

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            initializeNavigation();
            initializeLanguageSelector();
            initializeMobileMenu();
            loadMyEquipment();
            setupEquipmentForm();
            setupBookingForm();
        });

        // Navigation between sections
        function initializeNavigation() {
            const navItems = document.querySelectorAll('.nav-item');
            const sections = document.querySelectorAll('.section');

            navItems.forEach(item => {
                item.addEventListener('click', function() {
                    const targetSection = this.getAttribute('data-section');

                    // Update active nav item
                    navItems.forEach(nav => nav.classList.remove('active'));
                    this.classList.add('active');

                    // Show target section
                    sections.forEach(section => section.classList.remove('active'));
                    document.getElementById(targetSection).classList.add('active');

                    // Load section data
                    loadSectionData(targetSection);

                    // Close mobile menu
                    document.getElementById('sidebar').classList.remove('open');
                });
            });
        }

        // Load data for specific section
        function loadSectionData(section) {
            switch(section) {
                case 'my-equipment':
                    loadMyEquipment();
                    break;
                case 'browse-equipment':
                    loadAvailableEquipment();
                    break;
                case 'my-bookings':
                    loadMyBookings();
                    break;
                case 'notifications':
                    loadNotifications();
                    break;
            }
        }

        // Language selector
        function initializeLanguageSelector() {
            const langBtns = document.querySelectorAll('.lang-btn');

            langBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const lang = this.getAttribute('data-lang');

                    // Update active button
                    langBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');

                    // Change language
                    changeLanguage(lang);
                });
            });
        }

        // Change language
        function changeLanguage(lang) {
            currentLanguage = lang;
            // TODO: Implement translation logic
            showToast('Language changed to ' + lang.toUpperCase(), 'success');
        }

        // Mobile menu toggle
        function initializeMobileMenu() {
            const hamburger = document.getElementById('hamburgerBtn');
            const sidebar = document.getElementById('sidebar');

            if (hamburger) {
                hamburger.addEventListener('click', function() {
                    sidebar.classList.toggle('open');
                });
            }

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    if (!sidebar.contains(e.target) && !hamburger.contains(e.target)) {
                        sidebar.classList.remove('open');
                    }
                }
            });
        }

        // ============================================
        // MY EQUIPMENT SECTION
        // ============================================

        async function loadMyEquipment() {
            showLoading();
            try {
                const response = await fetch('../api/equipment_get_my.php');
                const result = await response.json();

                if (result.status === 'success') {
                    myEquipmentData = result.data;
                    renderMyEquipment(myEquipmentData);
                } else {
                    showToast(result.message || 'Failed to load equipment', 'error');
                }
            } catch (error) {
                console.error('Error loading equipment:', error);
                showToast('Unable to connect. Please check your internet connection.', 'error');
            } finally {
                hideLoading();
            }
        }

        function renderMyEquipment(equipment) {
            const grid = document.getElementById('myEquipmentGrid');

            if (!equipment || equipment.length === 0) {
                grid.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-tractor"></i>
                        <h3>No Equipment Listed</h3>
                        <p>No equipment listed yet. Add your first equipment!</p>
                    </div>
                `;
                return;
            }

            grid.innerHTML = equipment.map(item => `
                <div class="equipment-card">
                    ${item.image_url ?
                        `<div class="equipment-image"><img src="../${item.image_url}" alt="${item.equipment_name}"></div>` :
                        `<div class="equipment-image-placeholder"><i class="fas fa-tractor"></i></div>`
                    }
                    <div class="equipment-details">
                        <h3 class="equipment-name">${item.equipment_name}</h3>
                        <p class="equipment-price">�${item.rental_price} ${item.price_unit === 'per_day' ? 'per day' : 'per hour'}</p>
                        <span class="status-badge ${item.is_active == 1 ? 'active' : 'inactive'}">
                            ${item.is_active == 1 ? 'Active' : 'Inactive'}
                        </span>
                        <div class="equipment-actions">
                            <button class="btn btn-outline btn-small" onclick="editEquipment(${item.equipment_id})">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-secondary btn-small" onclick="toggleEquipmentStatus(${item.equipment_id}, ${item.is_active})">
                                ${item.is_active == 1 ? '<i class="fas fa-toggle-off"></i> Deactivate' : '<i class="fas fa-toggle-on"></i> Activate'}
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Add equipment button
        document.getElementById('addEquipmentBtn').addEventListener('click', function() {
            openEquipmentModal('add');
        });

        function openEquipmentModal(mode, equipmentId = null) {
            const modal = document.getElementById('equipmentModal');
            const title = document.getElementById('equipmentModalTitle');
            const submitText = document.getElementById('equipmentSubmitText');
            const form = document.getElementById('equipmentForm');

            form.reset();
            clearFormErrors();

            if (mode === 'add') {
                title.textContent = 'Add New Equipment';
                submitText.textContent = 'Add Equipment';
                document.getElementById('equipmentId').value = '';
            } else if (mode === 'edit') {
                title.textContent = 'Edit Equipment';
                submitText.textContent = 'Save Changes';

                // Find equipment data
                const equipment = myEquipmentData.find(e => e.equipment_id == equipmentId);
                if (equipment) {
                    document.getElementById('equipmentId').value = equipment.equipment_id;
                    document.getElementById('equipmentName').value = equipment.equipment_name;
                    document.getElementById('equipmentDescription').value = equipment.description || '';
                    document.getElementById('rentalPrice').value = equipment.rental_price;
                    document.getElementById('priceUnit').value = equipment.price_unit;
                }
            }

            modal.classList.add('show');
        }

        function closeEquipmentModal() {
            document.getElementById('equipmentModal').classList.remove('show');
        }

        function editEquipment(equipmentId) {
            openEquipmentModal('edit', equipmentId);
        }

        function setupEquipmentForm() {
            const form = document.getElementById('equipmentForm');

            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                // Clear previous errors
                clearFormErrors();

                // Validate form
                if (!validateEquipmentForm()) {
                    return;
                }

                // Prepare form data
                const formData = new FormData(form);

                // Determine if this is an add or update operation
                const equipmentId = document.getElementById('equipmentId').value;
                const isUpdate = equipmentId !== '';
                const apiUrl = isUpdate ? '../api/equipment_update.php' : '../api/equipment_add.php';

                // Show loading
                const submitBtn = document.getElementById('equipmentSubmitBtn');
                const submitText = document.getElementById('equipmentSubmitText');
                const originalText = submitText.textContent;
                submitBtn.disabled = true;
                submitText.innerHTML = '<div class="loading-spinner"></div> Saving...';

                try {
                    const response = await fetch(apiUrl, {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.status === 'success') {
                        showToast('Equipment saved successfully!', 'success');
                        closeEquipmentModal();
                        loadMyEquipment();
                    } else {
                        document.getElementById('formError').textContent = result.message || 'Failed to save equipment';
                    }
                } catch (error) {
                    console.error('Error saving equipment:', error);
                    document.getElementById('formError').textContent = 'Unable to connect. Please check your internet connection.';
                } finally {
                    submitBtn.disabled = false;
                    submitText.textContent = originalText;
                }
            });
        }

        function validateEquipmentForm() {
            let isValid = true;

            // Validate name
            const name = document.getElementById('equipmentName').value.trim();
            if (name.length < 3) {
                document.getElementById('nameError').textContent = 'Name must be at least 3 characters';
                isValid = false;
            }

            // Validate price
            const price = parseFloat(document.getElementById('rentalPrice').value);
            if (price <= 0 || isNaN(price)) {
                document.getElementById('priceError').textContent = 'Price must be a positive number';
                isValid = false;
            }

            // Validate image (if provided)
            const imageInput = document.getElementById('equipmentImage');
            if (imageInput.files.length > 0) {
                const file = imageInput.files[0];
                const maxSize = 5 * 1024 * 1024; // 5MB
                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];

                if (file.size > maxSize) {
                    document.getElementById('imageError').textContent = 'Image size must be less than 5MB';
                    isValid = false;
                }

                if (!allowedTypes.includes(file.type)) {
                    document.getElementById('imageError').textContent = 'Only JPG, PNG, and JPEG formats are allowed';
                    isValid = false;
                }
            }

            return isValid;
        }

        function clearFormErrors() {
            document.querySelectorAll('.form-error').forEach(el => el.textContent = '');
        }

        async function toggleEquipmentStatus(equipmentId, currentStatus) {
            const newStatus = currentStatus == 1 ? 0 : 1;

            try {
                const response = await fetch('../api/equipment_update_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        equipment_id: equipmentId,
                        is_active: newStatus
                    })
                });

                const result = await response.json();

                if (result.status === 'success') {
                    showToast('Equipment status updated!', 'success');
                    loadMyEquipment();
                } else {
                    showToast(result.message || 'Failed to update status', 'error');
                }
            } catch (error) {
                console.error('Error updating status:', error);
                showToast('Unable to connect. Please check your internet connection.', 'error');
            }
        }

        // ============================================
        // BROWSE EQUIPMENT SECTION
        // ============================================

        async function loadAvailableEquipment() {
            showLoading();
            try {
                const response = await fetch('../api/equipment_get_available.php');
                const result = await response.json();

                if (result.status === 'success') {
                    availableEquipmentData = result.data;
                    renderAvailableEquipment(availableEquipmentData);
                } else {
                    showToast(result.message || 'Failed to load equipment', 'error');
                }
            } catch (error) {
                console.error('Error loading equipment:', error);
                showToast('Unable to connect. Please check your internet connection.', 'error');
            } finally {
                hideLoading();
            }
        }

        function renderAvailableEquipment(equipment) {
            const grid = document.getElementById('browseEquipmentGrid');

            if (!equipment || equipment.length === 0) {
                grid.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h3>No Equipment Available</h3>
                        <p>No equipment available for booking at the moment. Check back later!</p>
                    </div>
                `;
                return;
            }

            grid.innerHTML = equipment.map(item => `
                <div class="equipment-card">
                    ${item.image_url ?
                        `<div class="equipment-image"><img src="../${item.image_url}" alt="${item.equipment_name}"></div>` :
                        `<div class="equipment-image-placeholder"><i class="fas fa-tractor"></i></div>`
                    }
                    <div class="equipment-details">
                        <h3 class="equipment-name">${item.equipment_name}</h3>
                        <p class="equipment-price">�${item.rental_price} ${item.price_unit === 'per_day' ? 'per day' : 'per hour'}</p>
                        <p class="equipment-owner">Owner: ${item.owner_name || 'Unknown'}</p>
                        <div class="equipment-actions">
                            <button class="btn btn-primary btn-small" onclick="openBookingModal(${item.equipment_id})">
                                <i class="fas fa-calendar-check"></i> Book Now
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', debounce(function(e) {
            const searchTerm = e.target.value.toLowerCase().trim();

            if (searchTerm === '') {
                renderAvailableEquipment(availableEquipmentData);
            } else {
                const filtered = availableEquipmentData.filter(item =>
                    item.equipment_name.toLowerCase().includes(searchTerm)
                );
                renderAvailableEquipment(filtered);
            }
        }, 300));

        // Debounce function
        function debounce(func, delay) {
            let timeoutId;
            return function(...args) {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => func.apply(this, args), delay);
            };
        }

        // ============================================
        // BOOKING MODAL
        // ============================================

        function openBookingModal(equipmentId) {
            const equipment = availableEquipmentData.find(e => e.equipment_id == equipmentId);
            if (!equipment) return;

            // Set equipment data
            document.getElementById('bookingEquipmentId').value = equipment.equipment_id;
            document.getElementById('bookingRentalPrice').value = equipment.rental_price;
            document.getElementById('bookingPriceUnit').value = equipment.price_unit;

            // Render equipment summary
            document.getElementById('bookingEquipmentSummary').innerHTML = `
                <h3>${equipment.equipment_name}</h3>
                <p class="equipment-summary-detail"><strong>Price:</strong> �${equipment.rental_price} ${equipment.price_unit === 'per_day' ? 'per day' : 'per hour'}</p>
                <p class="equipment-summary-detail"><strong>Owner:</strong> ${equipment.owner_name || 'Unknown'}</p>
            `;

            // Reset form
            document.getElementById('bookingForm').reset();
            document.getElementById('costPreview').style.display = 'none';
            clearFormErrors();

            // Show modal
            document.getElementById('bookingModal').classList.add('show');
        }

        function closeBookingModal() {
            document.getElementById('bookingModal').classList.remove('show');
        }

        function setupBookingForm() {
            const form = document.getElementById('bookingForm');
            const startInput = document.getElementById('startDateTime');
            const endInput = document.getElementById('endDateTime');

            // Calculate cost when dates change
            startInput.addEventListener('input', updateBookingCost);
            endInput.addEventListener('input', updateBookingCost);

            // Form submission
            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                clearFormErrors();

                if (!validateBookingForm()) {
                    return;
                }

                const equipmentId = document.getElementById('bookingEquipmentId').value;
                const startDateTime = document.getElementById('startDateTime').value;
                const endDateTime = document.getElementById('endDateTime').value;
                const totalCost = calculateTotalCost();

                // Show loading
                const submitBtn = document.getElementById('bookingSubmitBtn');
                const submitText = document.getElementById('bookingSubmitText');
                submitBtn.disabled = true;
                submitText.innerHTML = '<div class="loading-spinner"></div> Submitting...';

                try {
                    const response = await fetch('../api/booking_create.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            equipment_id: equipmentId,
                            start_datetime: startDateTime,
                            end_datetime: endDateTime,
                            total_cost: totalCost
                        })
                    });

                    const result = await response.json();

                    if (result.status === 'success') {
                        showToast('Booking request submitted! You\'ll be notified once approved.', 'success');
                        closeBookingModal();
                        loadMyBookings();
                    } else {
                        document.getElementById('bookingFormError').textContent = result.message || 'Failed to create booking';
                    }
                } catch (error) {
                    console.error('Error creating booking:', error);
                    document.getElementById('bookingFormError').textContent = 'Unable to connect. Please check your internet connection.';
                } finally {
                    submitBtn.disabled = false;
                    submitText.textContent = 'Request Booking';
                }
            });
        }

        function updateBookingCost() {
            const startDateTime = document.getElementById('startDateTime').value;
            const endDateTime = document.getElementById('endDateTime').value;

            if (!startDateTime || !endDateTime) {
                document.getElementById('costPreview').style.display = 'none';
                return;
            }

            const cost = calculateTotalCost();
            if (cost > 0) {
                const priceUnit = document.getElementById('bookingPriceUnit').value;
                const duration = calculateDuration();
                const unit = priceUnit === 'per_day' ? 'days' : 'hours';

                document.getElementById('costAmount').textContent = '�' + cost;
                document.getElementById('costDuration').textContent = `(${duration} ${unit})`;
                document.getElementById('costPreview').style.display = 'block';
            } else {
                document.getElementById('costPreview').style.display = 'none';
            }
        }

        function calculateTotalCost() {
            const startDateTime = document.getElementById('startDateTime').value;
            const endDateTime = document.getElementById('endDateTime').value;
            const rentalPrice = parseFloat(document.getElementById('bookingRentalPrice').value);
            const priceUnit = document.getElementById('bookingPriceUnit').value;

            if (!startDateTime || !endDateTime || !rentalPrice) return 0;

            const start = new Date(startDateTime);
            const end = new Date(endDateTime);
            const diffMs = end - start;

            if (diffMs <= 0) return 0;

            let duration;
            if (priceUnit === 'per_day') {
                duration = Math.ceil(diffMs / (1000 * 60 * 60 * 24)); // Days
            } else {
                duration = Math.ceil(diffMs / (1000 * 60 * 60)); // Hours
            }

            return Math.round(duration * rentalPrice);
        }

        function calculateDuration() {
            const startDateTime = document.getElementById('startDateTime').value;
            const endDateTime = document.getElementById('endDateTime').value;
            const priceUnit = document.getElementById('bookingPriceUnit').value;

            if (!startDateTime || !endDateTime) return 0;

            const start = new Date(startDateTime);
            const end = new Date(endDateTime);
            const diffMs = end - start;

            if (diffMs <= 0) return 0;

            if (priceUnit === 'per_day') {
                return Math.ceil(diffMs / (1000 * 60 * 60 * 24));
            } else {
                return Math.ceil(diffMs / (1000 * 60 * 60));
            }
        }

        function validateBookingForm() {
            let isValid = true;

            const startDateTime = document.getElementById('startDateTime').value;
            const endDateTime = document.getElementById('endDateTime').value;

            if (!startDateTime) {
                document.getElementById('startDateError').textContent = 'Start date is required';
                isValid = false;
            }

            if (!endDateTime) {
                document.getElementById('endDateError').textContent = 'End date is required';
                isValid = false;
            }

            if (startDateTime && endDateTime) {
                const start = new Date(startDateTime);
                const end = new Date(endDateTime);
                const now = new Date();

                if (start < now) {
                    document.getElementById('startDateError').textContent = 'Start date cannot be in the past';
                    isValid = false;
                }

                if (end <= start) {
                    document.getElementById('endDateError').textContent = 'End date must be after start date';
                    isValid = false;
                }
            }

            return isValid;
        }

        // ============================================
        // MY BOOKINGS SECTION
        // ============================================

        async function loadMyBookings() {
            showLoading();
            try {
                const response = await fetch('../api/booking_get_my.php');
                const result = await response.json();

                if (result.status === 'success') {
                    bookingsData = result.data;
                    renderMyBookings(bookingsData);
                } else {
                    showToast(result.message || 'Failed to load bookings', 'error');
                }
            } catch (error) {
                console.error('Error loading bookings:', error);
                showToast('Unable to connect. Please check your internet connection.', 'error');
            } finally {
                hideLoading();
            }
        }

        function renderMyBookings(bookings) {
            const list = document.getElementById('bookingsList');

            if (!bookings || bookings.length === 0) {
                list.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-calendar-alt"></i>
                        <h3>No Bookings Yet</h3>
                        <p>You haven't made any bookings yet. Browse available equipment to get started!</p>
                    </div>
                `;
                return;
            }

            list.innerHTML = bookings.map(booking => {
                const startDate = new Date(booking.start_datetime).toLocaleString();
                const endDate = new Date(booking.end_datetime).toLocaleString();
                const createdDate = new Date(booking.created_at).toLocaleDateString();

                return `
                    <div class="booking-card">
                        ${booking.image_url ?
                            `<img src="../${booking.image_url}" alt="${booking.equipment_name}" class="booking-image">` :
                            `<div class="booking-image" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #e0e0e0, #f5f5f5);"><i class="fas fa-tractor" style="font-size: 30px; color: #9e9e9e;"></i></div>`
                        }
                        <div class="booking-info">
                            <h3 class="booking-equipment-name">${booking.equipment_name}</h3>
                            <div class="booking-detail">
                                <i class="fas fa-calendar"></i>
                                <span><strong>Start:</strong> ${startDate}</span>
                            </div>
                            <div class="booking-detail">
                                <i class="fas fa-calendar"></i>
                                <span><strong>End:</strong> ${endDate}</span>
                            </div>
                            <div class="booking-detail">
                                <i class="fas fa-rupee-sign"></i>
                                <span><strong>Total Cost:</strong> �${booking.total_cost}</span>
                            </div>
                            <div class="booking-detail">
                                <i class="fas fa-clock"></i>
                                <span>Booked on: ${createdDate}</span>
                            </div>
                            <div class="booking-statuses">
                                <span class="booking-status-badge ${booking.booking_status}">${formatStatus(booking.booking_status)}</span>
                                ${booking.delivery_status ? `<span class="booking-status-badge ${booking.delivery_status}">${formatStatus(booking.delivery_status)}</span>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function formatStatus(status) {
            return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        }

        // ============================================
        // NOTIFICATIONS SECTION
        // ============================================

        async function loadNotifications() {
            showLoading();
            try {
                const response = await fetch('../api/notifications_get_my.php');
                const result = await response.json();

                if (result.status === 'success') {
                    notificationsData = result.data;
                    renderNotifications(notificationsData);
                } else {
                    showToast(result.message || 'Failed to load notifications', 'error');
                }
            } catch (error) {
                console.error('Error loading notifications:', error);
                showToast('Unable to connect. Please check your internet connection.', 'error');
            } finally {
                hideLoading();
            }
        }

        function renderNotifications(notifications) {
            const list = document.getElementById('notificationsList');

            if (!notifications || notifications.length === 0) {
                list.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-bell"></i>
                        <h3>No Notifications</h3>
                        <p>No notifications yet. You'll be notified about booking updates here.</p>
                    </div>
                `;
                return;
            }

            list.innerHTML = notifications.map(notif => {
                const message = formatNotificationMessage(notif.message_key, notif.message_params);
                const icon = getNotificationIcon(notif.message_key);
                const iconClass = getNotificationIconClass(notif.message_key);
                const timeAgo = getTimeAgo(notif.created_at);

                return `
                    <div class="notification-card ${notif.is_read == 0 ? 'unread' : 'read'}">
                        <div class="notification-icon ${iconClass}">
                            <i class="fas ${icon}"></i>
                        </div>
                        <div class="notification-content">
                            <p class="notification-message">${message}</p>
                            <p class="notification-time">${timeAgo}</p>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function formatNotificationMessage(messageKey, paramsJson) {
            let params = {};
            try {
                params = JSON.parse(paramsJson || '{}');
            } catch (e) {
                console.error('Error parsing notification params:', e);
            }

            const messages = {
                'booking_approved': `Booking Approved! Your booking for ${params.equipment_name || 'equipment'} has been approved by the admin.`,
                'booking_rejected': `Booking Rejected: Your booking request for ${params.equipment_name || 'equipment'} was rejected.`,
                'delivery_update': `Delivery Update: Your ${params.equipment_name || 'equipment'} is ${params.delivery_status || 'updated'}.`,
                'booking_completed': `Booking Completed: Your booking for ${params.equipment_name || 'equipment'} has been marked as completed.`
            };

            return messages[messageKey] || 'You have a new notification';
        }

        function getNotificationIcon(messageKey) {
            const icons = {
                'booking_approved': 'fa-check-circle',
                'booking_rejected': 'fa-times-circle',
                'delivery_update': 'fa-truck',
                'booking_completed': 'fa-check-circle'
            };

            return icons[messageKey] || 'fa-bell';
        }

        function getNotificationIconClass(messageKey) {
            if (messageKey === 'booking_approved' || messageKey === 'booking_completed') {
                return 'success';
            } else if (messageKey === 'booking_rejected') {
                return 'error';
            } else {
                return 'info';
            }
        }

        function getTimeAgo(datetime) {
            const now = new Date();
            const created = new Date(datetime);
            const diffMs = now - created;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
            if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
            if (diffDays < 30) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;

            return created.toLocaleDateString();
        }

        // ============================================
        // UTILITY FUNCTIONS
        // ============================================

        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;

            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

            toast.innerHTML = `
                <i class="fas ${icon}"></i>
                <div class="toast-content">
                    <div class="toast-title">${type === 'success' ? 'Success' : 'Error'}</div>
                    <div class="toast-message">${message}</div>
                </div>
            `;

            container.appendChild(toast);

            setTimeout(() => toast.classList.add('show'), 10);

            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        function showLoading() {
            document.getElementById('loadingOverlay').classList.add('show');
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').classList.remove('show');
        }

        // Close modals on overlay click
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('show');
                }
            });
        });
    </script>
</body>
</html>
