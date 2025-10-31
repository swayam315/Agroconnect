<?php
// --- 1. REAL AUTHENTICATION & STATE MANAGEMENT ---
session_start(); // Start or resume the PHP session

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userName = $_SESSION['full_name'] ?? '';
$userRole = $_SESSION['role'] ?? '';
// Create an avatar initial from the user's name
$userAvatar = $userName ? strtoupper(substr($userName, 0, 1)) : '';
// --- End of Auth Check ---


// --- 2. GET ALL HUB DATA FROM DATABASE ---
// (This is your existing PHP code)
require_once 'api/db_connect.php'; // Use our existing DB connection

$hubs = []; // Default to an empty array
try {
    $stmt = $pdo->prepare("SELECT hub_id, hub_name, address, latitude, longitude 
                          FROM center_hubs 
                          WHERE is_active = 1");
    $stmt->execute();
    $hubs = $stmt->fetchAll();
} catch (Exception $e) {
    // If the database fails, the $hubs array will be empty, 
    // and the map will simply load without any markers.
}

// Now, we will pass this $hubs array to our JavaScript
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgroConnect - Share Farm Equipment, Grow Together</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>
    
    <style>
        /* ALL 1500+ LINES OF YOUR CSS (Unchanged) */
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
            --glass-bg: rgba(255, 255, 255, 0.25);
            --glass-border: rgba(255, 255, 255, 0.18);
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --border-radius: 12px;
            --border-radius-lg: 20px;
            --border-radius-xl: 32px;
            --spacing-xs: 0.5rem;
            --spacing-sm: 1rem;
            --spacing-md: 1.5rem;
            --spacing-lg: 2rem;
            --spacing-xl: 3rem;
            --spacing-2xl: 4rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--background-light);
            color: var(--text-dark);
            line-height: 1.7;
            scroll-behavior: smooth;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            line-height: 1.3;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--spacing-md);
        }

        /* Enhanced Glassmorphism Effects */
        .glass {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            box-shadow: var(--shadow-lg);
        }

        .glass-dark {
            background: rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Focus styles for accessibility */
        *:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }

        /* Loading spinner */
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: var(--white);
            animation: spin 1s ease-in-out infinite;
            margin-right: 8px;
            vertical-align: middle;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Toast notifications */
        .toast-container {
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 350px;
        }

        .toast {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 16px 20px;
            box-shadow: var(--shadow-xl);
            border-left: 4px solid var(--primary-color);
            transform: translateX(400px);
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

        .toast.warning {
            border-left-color: #f59e0b;
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

        .toast.warning i {
            color: #f59e0b;
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

        .toast-close {
            background: none;
            border: none;
            font-size: 16px;
            cursor: pointer;
            color: var(--text-light);
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
        }

        .toast-close:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }

        /* Map Section */
        .map-section {
            padding: var(--spacing-2xl) 0;
            background-color: var(--background-light);
        }

        .map-container {
            height: 500px;
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-xl);
            margin-top: 30px;
        }

        #equipmentMap {
            height: 100%;
            width: 100%;
        }

        /* Enhanced micro-interactions */
        .btn {
            padding: 14px 28px;
            border-radius: var(--border-radius-lg);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            position: relative;
            overflow: hidden;
            font-size: 16px;
        }

        .btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }

        .btn:focus:not(:active)::after {
            animation: ripple 1s ease-out;
        }

        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            100% {
                transform: scale(20, 20);
                opacity: 0;
            }
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: var(--white);
            box-shadow: var(--shadow-lg);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
        }

        .btn-outline {
            background-color: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn-outline:hover {
            background-color: var(--primary-color);
            color: var(--white);
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        /* Enhanced card animations */
        .category-card, .step-card, .stat-card {
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            transform-style: preserve-3d;
            perspective: 1000px;
        }

        .category-card:hover, .step-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: var(--shadow-xl);
        }

        /* Header Styles with Glassmorphism */
        header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            transition: all 0.4s ease;
        }

        header.scrolled {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: var(--shadow-lg);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        header.scrolled .logo h1 {
            color: var(--text-dark);
        }

        header.scrolled nav ul li a {
            color: var(--text-dark);
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-sm) 0;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-img {
            height: 50px;
            width: auto;
            transition: all 0.3s ease;
        }

        .logo h1 {
            color: var(--white);
            font-size: 28px;
            font-weight: 700;
            transition: color 0.3s ease;
        }

        .logo span {
            color: var(--secondary-color);
        }

        nav ul {
            display: flex;
            list-style: none;
        }

        nav ul li {
            margin-left: 25px;
        }

        nav ul li a {
            text-decoration: none;
            color: var(--white);
            font-weight: 500;
            transition: color 0.3s;
            display: flex;
            align-items: center;
            padding: 5px 0;
            position: relative;
        }

        nav ul li a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background-color: var(--secondary-color);
            transition: width 0.3s ease;
        }

        nav ul li a:hover::after, nav ul li a.active::after {
            width: 100%;
        }

        nav ul li a i {
            margin-right: 8px;
        }

        nav ul li a:hover, nav ul li a.active {
            color: var(--secondary-color);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .language-selector {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            position: relative;
        }

        .language-selector i {
            color: var(--white);
        }

        header.scrolled .language-selector i {
            color: var(--text-dark);
        }

        .language-selector select {
            border: none;
            background: transparent;
            font-size: 14px;
            color: var(--white);
            cursor: pointer;
            appearance: none;
            padding-right: 15px;
        }

        header.scrolled .language-selector select {
            color: var(--text-dark);
        }

        .language-selector::after {
            content: '\f107';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            color: var(--white);
            pointer-events: none;
        }

        header.scrolled .language-selector::after {
            color: var(--text-dark);
        }

        .auth-buttons {
            display: flex;
            gap: 15px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info {
            text-align: right;
        }

        .user-name {
            font-weight: 600;
            color: var(--white);
        }

        header.scrolled .user-name {
            color: var(--text-dark);
        }

        .user-role {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.8);
        }

        header.scrolled .user-role {
            color: var(--text-light);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            transition: transform 0.3s;
            box-shadow: var(--shadow-md);
        }

        .user-avatar:hover {
            transform: scale(1.1);
        }

        .btn-logout {
            background-color: #ef4444;
            color: var(--white);
            padding: 8px 15px;
            font-size: 14px;
        }

        .btn-logout:hover {
            background-color: #dc2626;
        }

        /* Mobile Menu Styles */
        .hamburger {
            display: none;
            flex-direction: column;
            justify-content: space-between;
            width: 30px;
            height: 21px;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 0;
        }

        .hamburger span {
            display: block;
            height: 3px;
            width: 100%;
            background-color: var(--white);
            border-radius: 3px;
            transition: all 0.3s ease;
        }

        header.scrolled .hamburger span {
            background-color: var(--text-dark);
        }

        .hamburger.active span:nth-child(1) {
            transform: translateY(9px) rotate(45deg);
        }

        .hamburger.active span:nth-child(2) {
            opacity: 0;
        }

        .hamburger.active span:nth-child(3) {
            transform: translateY(-9px) rotate(-45deg);
        }

        .mobile-menu {
            display: none;
            position: fixed;
            top: 80px;
            left: 0;
            width: 100%;
            height: calc(100vh - 80px);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            z-index: 99;
            padding: 20px;
            box-shadow: var(--shadow-lg);
            overflow-y: auto;
        }

        .mobile-menu.active {
            display: block;
        }

        .mobile-nav {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .mobile-nav a {
            display: flex;
            align-items: center;
            padding: 15px;
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 500;
            border-radius: var(--border-radius);
            transition: all 0.3s;
        }

        .mobile-nav a:hover, .mobile-nav a.active {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--primary-color);
            transform: translateX(5px);
        }

        .mobile-nav a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            overflow-y: auto;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            position: relative;
            background-color: var(--white);
            margin: 50px auto;
            width: 90%;
            max-width: 1000px;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-xl);
            transform: scale(0.9);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .modal.show .modal-content {
            transform: scale(1);
            opacity: 1;
        }

        .close-modal {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-light);
            z-index: 10;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s;
        }

        .close-modal:hover {
            background-color: var(--background-light);
            color: var(--text-dark);
            transform: rotate(90deg);
        }

        /* Form Validation Styles */
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .error-message {
            color: #ef4444;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }

        .form-control.error {
            border-color: #ef4444;
        }

        .form-control.success {
            border-color: #10b981;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Hero Section with Enhanced Animations - REMOVED GREEN OVERLAY */
        .hero {
            background: url('hero.jpeg'); /* Make sure you have hero.jpeg in your root folder */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            color: var(--white);
            padding: 160px 0 100px;
            text-align: center;
            position: relative;
            overflow: hidden;
            min-height: 80vh;
            display: flex;
            align-items: center;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.1) 0%, transparent 50%);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(1deg); }
        }

        .hero h2 {
            font-size: clamp(32px, 5vw, 64px);
            margin-bottom: 24px;
            font-weight: 700;
            line-height: 1.2;
            position: relative;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .hero p {
            font-size: clamp(18px, 2.5vw, 22px);
            max-width: 700px;
            margin: 0 auto 40px;
            line-height: 1.6;
            opacity: 0.9;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .hero .btn {
            transform: translateY(0);
            transition: all 0.3s ease;
        }

        .hero .btn:hover {
            transform: translateY(-3px);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(255, 255, 255, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 255, 255, 0); }
        }

        /* Stats Section */
        .stats {
            padding: var(--spacing-2xl) 0;
            background-color: var(--white);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 30px;
            text-align: center;
        }

        .stat-card {
            padding: 40px 20px;
            border-radius: var(--border-radius-lg);
            transition: transform 0.3s;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: var(--white);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: translateX(-100%);
        }

        .stat-card:hover::before {
            animation: shine 1.5s ease-out;
        }

        @keyframes shine {
            100% { transform: translateX(100%); }
        }

        .stat-card:hover {
            transform: translateY(-10px) rotateX(5deg);
        }

        .stat-icon {
            font-size: 40px;
            color: var(--white);
            margin-bottom: 20px;
        }

        .stat-number {
            font-size: 48px;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 10px;
            min-height: 60px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stat-text {
            font-size: 18px;
            color: var(--white);
            font-weight: 500;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        /* Categories Section */
        .categories {
            padding: var(--spacing-2xl) 0;
            text-align: center;
            background: var(--background-light);
        }

        .section-title {
            font-size: 42px;
            margin-bottom: 60px;
            color: var(--text-dark);
            position: relative;
            font-weight: 700;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            border-radius: 2px;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 40px;
            margin-top: 40px;
        }

        .category-card {
            background-color: var(--white);
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        .category-img-container {
            height: 220px;
            width: 100%;
            overflow: hidden;
        }

        .category-img {
            height: 100%;
            width: 100%;
            object-fit: cover;
            background-color: #f5f5f5;
            transition: transform 0.5s ease;
        }

        .category-card:hover .category-img {
            transform: scale(1.1);
        }

        .category-content {
            padding: 30px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .category-content h3 {
            font-size: 24px;
            margin-bottom: 15px;
            color: var(--text-dark);
            font-weight: 600;
        }

        .category-content p {
            color: var(--text-light);
            margin-bottom: 25px;
            flex-grow: 1;
            font-size: 16px;
            line-height: 1.7;
        }

        /* How It Works Section */
        .how-it-works {
            padding: var(--spacing-2xl) 0;
            background-color: var(--white);
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .step-card {
            background-color: var(--white);
            border-radius: var(--border-radius-lg);
            padding: 40px 30px;
            text-align: center;
            box-shadow: var(--shadow-lg);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            overflow: hidden;
        }

        .step-number {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 80px;
            font-weight: 900;
            color: rgba(16, 185, 129, 0.1);
            line-height: 1;
        }

        .step-icon {
            font-size: 50px;
            color: var(--primary-color);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }

        .step-card:hover .step-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .step-card h3 {
            font-size: 22px;
            margin-bottom: 15px;
            color: var(--text-dark);
            font-weight: 600;
        }

        .step-card p {
            color: var(--text-light);
            line-height: 1.7;
        }

        /* About Us Section */
        .about-us {
            padding: var(--spacing-2xl) 0;
            background-color: var(--background-light);
        }

        .about-container {
            display: flex;
            align-items: center;
            gap: 50px;
        }

        .about-content {
            flex: 1;
        }

        .about-content h2 {
            font-size: 42px;
            margin-bottom: 20px;
            color: var(--text-dark);
            position: relative;
            font-weight: 700;
        }

        .about-content h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 80px;
            height: 4px;
            background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            border-radius: 2px;
        }

        .about-content p {
            margin-bottom: 20px;
            color: var(--text-light);
            line-height: 1.7;
        }

        .about-image {
            flex: 1;
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-xl);
        }

        .about-image img {
            width: 100%;
            height: auto;
            display: block;
            transition: transform 0.5s;
        }

        .about-image:hover img {
            transform: scale(1.05);
        }

        /* CTA Section */
        .cta {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: var(--spacing-2xl) 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" preserveAspectRatio="none"><path d="M0,0 L1000,100 L0,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
            background-size: cover;
            opacity: 0.1;
        }

        .cta h2 {
            font-size: 42px;
            margin-bottom: 20px;
            font-weight: 700;
            position: relative;
            z-index: 2;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .cta p {
            font-size: 20px;
            max-width: 600px;
            margin: 0 auto 30px;
            line-height: 1.6;
            position: relative;
            z-index: 2;
            opacity: 0.9;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .cta .btn {
            background-color: var(--white);
            color: var(--primary-color);
            font-size: 18px;
            padding: 16px 32px;
            position: relative;
            z-index: 2;
        }

        .cta .btn:hover {
            background-color: var(--secondary-color);
            color: var(--white);
        }

        /* Footer */
        footer {
            background-color: #1f2937;
            color: var(--white);
            padding: 60px 0 20px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }

        .footer-logo-img {
            height: 40px;
            width: auto;
        }

        .footer-logo h2 {
            color: var(--white);
            font-size: 28px;
            font-weight: 700;
        }

        .footer-logo span {
            color: var(--secondary-color);
        }

        .footer-logo p {
            color: #9ca3af;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .footer-contact h3, .footer-social h3 {
            font-size: 20px;
            margin-bottom: 20px;
            position: relative;
            font-weight: 600;
        }

        .footer-contact h3::after, .footer-social h3::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 40px;
            height: 3px;
            background-color: var(--primary-color);
        }

        .contact-info {
            margin-bottom: 20px;
        }

        .contact-info p {
            margin-bottom: 8px;
            color: #9ca3af;
        }

        .social-icons {
            display: flex;
            gap: 15px;
        }

        .social-icons a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: #374151;
            border-radius: 50%;
            color: var(--white);
            text-decoration: none;
            transition: all 0.3s;
        }

        .social-icons a:hover {
            background-color: var(--primary-color);
            transform: translateY(-3px);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #374151;
            color: #9ca3af;
            font-size: 14px;
        }

        .help-link {
            text-align: center;
            margin-top: 20px;
        }

        .help-link a {
            color: #9ca3af;
            text-decoration: none;
            transition: color 0.3s;
        }

        .help-link a:hover {
            color: var(--primary-light);
        }

        /* Auth Pages - UPDATED WITH NEW BACKGROUND IMAGE */
        .auth-container {
            display: flex;
            min-height: 100vh;
        }

        .auth-left {
            flex: 1;
            background: url('https://get.pxhere.com/photo/landscape-nature-forest-grass-fence-sky-field-farm-lawn-meadow-house-hill-flower-building-barn-country-rural-green-red-crop-pasture-corn-agriculture-trees-outdoors-woods-hdr-clouds-fields-estate-wisconsin-rural-area-1291613.jpg');
            background-size: cover;
            background-position: center;
            color: var(--white);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 40px;
            position: relative;
        }

        .auth-left::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4); /* Dark overlay for better text readability */
        }

        .auth-left h1 {
            font-size: 42px;
            margin-bottom: 20px;
            font-weight: 700;
            position: relative;
            z-index: 2;
        }

        .auth-left p {
            font-size: 18px;
            max-width: 500px;
            line-height: 1.6;
            position: relative;
            z-index: 2;
        }

        .auth-right {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            background-color: var(--white);
        }

        .auth-form {
            width: 100%;
            max-width: 400px;
        }

        .auth-form h2 {
            font-size: 32px;
            margin-bottom: 10px;
            color: var(--text-dark);
            font-weight: 600;
        }

        .auth-form p {
            color: var(--text-light);
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-dark);
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #d1d5db;
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .remember-me {
            display: flex;
            align-items: center;
        }

        .remember-me input {
            margin-right: 8px;
        }

        .forgot-password {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s;
        }

        .forgot-password:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .auth-buttons-group {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 20px;
        }

        .auth-links {
            text-align: center;
            margin-top: 20px;
        }

        .auth-links a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .auth-links a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .back-home {
            display: inline-flex;
            align-items: center;
            color: var(--primary-color);
            text-decoration: none;
            margin-top: 20px;
            font-weight: 500;
            transition: color 0.3s;
        }

        .back-home:hover {
            color: var(--primary-dark);
        }

        .back-home i {
            margin-right: 8px;
            transition: transform 0.3s;
        }

        .back-home:hover i {
            transform: translateX(-5px);
        }

        /* Floating CTA Button */
        .floating-cta {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-xl);
            z-index: 100;
            cursor: pointer;
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
        }

        .floating-cta:hover {
            transform: scale(1.1);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
        }

        /* Animation Classes */
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }

        .animate-on-scroll.animated {
            opacity: 1;
            transform: translateY(0);
        }

        /* Tilt effect for cards */
        .tilt-card {
            transform-style: preserve-3d;
            transform: perspective(1000px);
        }

        /* Image optimization */
        img[data-src] {
            opacity: 0;
            transition: opacity 0.3s;
        }

        img.loaded {
            opacity: 1;
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .auth-container {
                flex-direction: column;
            }
            
            .auth-left {
                padding: 60px 40px;
            }
            
            .header-right {
                gap: 15px;
            }
            
            .user-profile {
                gap: 10px;
            }
            
            .categories-grid {
                grid-template-columns: 1fr;
            }
            
            .about-container {
                flex-direction: column;
            }
            
            .about-image {
                order: -1;
            }
        }

        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                padding: 15px 0;
            }
            
            .logo {
                margin-bottom: 15px;
            }
            
            nav ul {
                display: none;
            }
            
            .hamburger {
                display: flex;
            }
            
            .header-right {
                flex-direction: column;
                gap: 10px;
                width: 100%;
                align-items: center;
            }
            
            .hero {
                padding: 140px 0 80px;
            }
            
            .hero h2 {
                font-size: clamp(28px, 6vw, 48px);
            }
            
            .hero p {
                font-size: clamp(16px, 3vw, 20px);
            }
            
            .section-title {
                font-size: 36px;
            }
            
            .auth-left h1 {
                font-size: 36px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .map-container {
                height: 400px;
            }
        }

        @media (max-width: 576px) {
            nav ul li {
                margin-left: 15px;
            }
            
            .auth-buttons {
                flex-direction: column;
                gap: 10px;
                width: 100%;
            }
            
            .btn {
                width: 100%;
            }
            
            .hero {
                padding: 120px 0 60px;
            }
            
            .categories, .cta, .stats, .how-it-works, .about-us {
                padding: 60px 0;
            }
            
            .user-info {
                display: none;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .categories-grid {
                grid-template-columns: 1fr;
            }
            
            .steps-grid {
                grid-template-columns: 1fr;
            }

            .map-container {
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="toast-container" id="toastContainer"></div>

    <div class="floating-cta" id="floatingCta">
        <i class="fas fa-comments fa-lg"></i>
    </div>

    <header id="mainHeader">
        <div class="container header-container">
            <div class="logo">
                <img src="logo.png" alt="AgroConnect Logo" class="logo-img" onerror="this.style.display='none'">
                <h1>Agro<span>Connect</span></h1>
            </div>
            
            <nav class="desktop-nav">
                <ul>
                    <li><a href="#" class="active"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
                </ul>
            </nav>
            
            <button class="hamburger" id="hamburger" aria-label="Toggle menu" aria-expanded="false" aria-controls="mobileMenu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <div class="header-right">
                <div class="language-selector" aria-label="Language selector">
                    <i class="fas fa-globe" aria-hidden="true"></i>
                    <select aria-label="Select language">
                        <option value="en">English</option>
                        <option value="hi">हिन्दी (Hindi)</option>
                        <option value="kn">ಕನ್ನಡ (Kannada)</option>
                    </select>
                </div>
                
                <div class="user-profile" id="userProfile" style="display: <?php echo $isLoggedIn ? 'flex' : 'none'; ?>;">
                    <div class="user-info">
                        <div class="user-name">Welcome, <?php echo htmlspecialchars($userName); ?></div>
                        <div class="user-role"><?php echo htmlspecialchars(ucfirst($userRole)); ?></div>
                    </div>
                    <div class="user-avatar" aria-hidden="true"><?php echo htmlspecialchars($userAvatar); ?></div>
                    <button class="btn btn-logout" id="logoutBtn" aria-label="Logout">Logout</button>
                </div>
                
                <div class="auth-buttons" id="authButtons" style="display: <?php echo $isLoggedIn ? 'none' : 'flex'; ?>;">
                    <a href="#" class="btn btn-outline" id="loginBtn" aria-label="Login">Login</a>
                    <a href="#" class="btn btn-primary" id="signupBtn" aria-label="Sign up">Sign Up</a>
                </div>
            </div>
        </div>
        
        <div class="mobile-menu" id="mobileMenu" role="navigation" aria-label="Mobile navigation">
            <nav class="mobile-nav">
                <a href="#" class="active"><i class="fas fa-home"></i> Home</a>
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a>
            </nav>
        </div>
    </header>

    <section class="hero" id="hero">
        <div class="container">
            <h2 id="heroTitle">Share Farm Equipment, Grow Together</h2>
            <p id="heroSubtitle">Connect with farmers in your community to share resources, reduce costs, and increase productivity.</p>
            <a href="#" class="btn btn-primary" id="getStartedBtn">Get Started</a>
        </div>
    </section>

    <section class="stats" id="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card tilt-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-number" data-target="500" data-animated="false">0</div>
                    <div class="stat-text">Farmers Connected</div>
                </div>
                <div class="stat-card tilt-card">
                    <div class="stat-icon"><i class="fas fa-tractor"></i></div>
                    <div class="stat-number" data-target="200" data-animated="false">0</div>
                    <div class="stat-text">Equipment Available</div>
                </div>
                <div class="stat-card tilt-card">
                    <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-number" data-target="1000" data-animated="false">0</div>
                    <div class="stat-text">Successful Bookings</div>
                </div>
                <div class="stat-card tilt-card">
                    <div class="stat-icon"><i class="fas fa-warehouse"></i></div>
                    <div class="stat-number" data-target="50" data-animated="false">0</div>
                    <div class="stat-text">Farming Centers</div>
                </div>
            </div>
        </div>
    </section>

    <section class="categories" id="categories">
        <div class="container">
            <h2 class="section-title">Explore Our Categories</h2>
            <div class="categories-grid">
                <div class="category-card animate-on-scroll tilt-card">
                    <div class="category-img-container">
                        <img 
                            src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='220' viewBox='0 0 400 220'%3E%3Crect width='400' height='220' fill='%23f5f5f5'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='18' fill='%23999'%3ELoading Tractors...%3C/text%3E%3C/svg%3E" 
                            data-src="https://blog.machinefinder.com/wp-content/uploads/2013/07/John-Deere-9R.jpg" 
                            alt="Tractors" 
                            class="category-img"
                            loading="lazy"
                            width="400"
                            height="220">
                    </div>
                    <div class="category-content">
                        <h3>Tractors</h3>
                        <p>Find and share tractors of all sizes for your farming needs. Our platform connects tractor owners with farmers who need equipment for plowing, tilling, and other agricultural tasks.</p>
                        <a href="#" class="btn btn-outline">Browse</a>
                    </div>
                </div>
                <div class="category-card animate-on-scroll tilt-card">
                    <div class="category-img-container">
                        <img 
                            src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='220' viewBox='0 0 400 220'%3E%3Crect width='400' height='220' fill='%23f5f5f5'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='18' fill='%23999'%3ELoading Harvesters...%3C/text%3E%3C/svg%3E" 
                            data-src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTgnqVmQpl8eg1IaFHQscraUYToEQ78L0DmiNcRnyZkjhmTJdcai_dfElnaQ6yl3-Rd84E&usqp=CAU" 
                            alt="Harvesters" 
                            class="category-img"
                            loading="lazy"
                            width="400"
                            height="220">
                    </div>
                    <div class="category-content">
                        <h3>Harvesters</h3>
                        <p>Efficient harvesting equipment for all types of crops. Access combine harvesters, fruit pickers, and other specialized equipment to maximize your harvest efficiency and reduce labor costs.</p>
                        <a href="#" class="btn btn-outline">Browse</a>
                    </div>
                </div>
                <div class="category-card animate-on-scroll tilt-card">
                    <div class="category-img-container">
                        <img 
                            src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='220' viewBox='0 0 400 220'%3E%3Crect width='400' height='220' fill='%23f5f5f5'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='18' fill='%23999'%3ELoading Irrigation...%3C/text%3E%3C/svg%3E" 
                            data-src="https://cpimg.tistatic.com/2984631/s/6/drip-irrigation-equipment.jpg" 
                            alt="Irrigation Equipment" 
                            class="category-img"
                            loading="lazy"
                            width="400"
                            height="220">
                    </div>
                    <div class="category-content">
                        <h3>Irrigation Equipment</h3>
                        <p>Modern irrigation solutions for optimal water usage. Find drip irrigation systems, sprinklers, and water pumps to ensure your crops receive the right amount of water at the right time.</p>
                        <a href="#" class="btn btn-outline">Browse</a>
                    </div>
                </div>
                <div class="category-card animate-on-scroll tilt-card">
                    <div class="category-img-container">
                        <img 
                            src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='220' viewBox='0 0 400 220'%3E%3Crect width='400' height='220' fill='%23f5f5f5'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='18' fill='%23999'%3ELoading Planting...%3C/text%3E%3C/svg%3E" 
                            data-src="https://www.deere.com/assets/images/region-4/products/planting-equipment/r4k002571_rrd_1024X576.jpg" 
                            alt="Planting Equipment" 
                            class="category-img"
                            loading="lazy"
                            width="400"
                            height="220">
                    </div>
                    <div class="category-content">
                        <h3>Planting Equipment</h3>
                        <p>Tools and machinery for efficient planting and seeding. Access seed drills, planters, and transplanters to optimize your planting process and ensure proper seed placement.</p>
                        <a href="#" class="btn btn-outline">Browse</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="map-section" id="map">
        <div class="container">
            <h2 class="section-title">Find Hubs Near You</h2>
            <p style="text-align: center; max-width: 700px; margin: 0 auto 30px; color: var(--text-light);">
                Browse available center hub areas using our interactive map. All our hubs across India are shown here.
            </p>
            <div class="map-container">
                <div id="equipmentMap"></div>
            </div>
        </div>
    </section>

    <section class="how-it-works" id="howItWorks">
        <div class="container">
            <h2 class="section-title">How Our Platform Works</h2>
            <div class="steps-grid">
                <div class="step-card animate-on-scroll tilt-card">
                    <div class="step-number">1</div>
                    <div class="step-icon"><i class="fas fa-user-plus"></i></div>
                    <h3>Create Your Account</h3>
                    <p>Sign up as a farmer or equipment owner. Fill in your details, verify your account, and get started in minutes.</p>
                </div>
                <div class="step-card animate-on-scroll tilt-card">
                    <div class="step-number">2</div>
                    <div class="step-icon"><i class="fas fa-search"></i></div>
                    <h3>Browse or List Equipment</h3>
                    <p>Find the equipment you need or list your own equipment for others to rent. Filter by type, location, and availability.</p>
                </div>
                <div class="step-card animate-on-scroll tilt-card">
                    <div class="step-number">3</div>
                    <div class="step-icon"><i class="fas fa-handshake"></i></div>
                    <h3>Connect & Book</h3>
                    <p>Connect with other farmers, discuss terms, and book equipment directly through our secure platform.</p>
                </div>
                <div class="step-card animate-on-scroll tilt-card">
                    <div class="step-number">4</div>
                    <div class="step-icon"><i class="fas fa-check-circle"></i></div>
                    <h3>Approval by Owner</h3>
                    <p>Equipment owners review and approve rental requests. This ensures equipment availability and confirms the booking.</p>
                </div>
                <div class="step-card animate-on-scroll tilt-card">
                    <div class="step-number">5</div>
                    <div class="step-icon"><i class="fas fa-bell"></i></div>
                    <h3>Successful Booking & Real-time Updates</h3>
                    <p>Get instant confirmation and real-time notifications about equipment status, pickup details, and rental period.</p>
                </div>
                <div class="step-card animate-on-scroll tilt-card">
                    <div class="step-number">6</div>
                    <div class="step-icon"><i class="fas fa-language"></i></div>
                    <h3>Multi-language Support</h3>
                    <p>Access our platform in multiple languages including English, Hindi, and Kannada for a seamless user experience.</p>
                </div>
                <div class="step-card animate-on-scroll tilt-card">
                    <div class="step-number">7</div>
                    <div class="step-icon"><i class="fas fa-robot"></i></div>
                    <h3>Chatbot (Text+Voice) Support</h3>
                    <p>Get instant help from our AI chatbot with both text and voice options for any questions or platform-related issues.</p>
                </div>
                <div class="step-card animate-on-scroll tilt-card">
                    <div class="step-number">8</div>
                    <div class="step-icon"><i class="fas fa-users"></i></div>
                    <h3>Community Space</h3>
                    <p>Join our farming community to share knowledge, ask questions, and connect with other farmers and equipment owners.</p>
                </div>
                <div class="step-card animate-on-scroll tilt-card">
                    <div class="step-number">9</div>
                    <div class="step-icon"><i class="fas fa-tractor"></i></div>
                    <h3>Grow Together</h3>
                    <p>Use the equipment, complete your farming tasks efficiently, and build lasting relationships in the farming community.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="about-us" id="about">
        <div class="container">
            <div class="about-container">
                <div class="about-content animate-on-scroll">
                    <h2>About AgroConnect</h2>
                    <p>AgroConnect is a revolutionary platform that connects farmers and equipment owners, creating a collaborative ecosystem for agricultural growth. Our mission is to make farming more efficient, sustainable, and profitable for everyone involved.</p>
                    <p>Founded in 2020, we've grown to become the largest equipment-sharing platform in the agricultural sector, serving thousands of farmers across multiple regions. We believe that by sharing resources, we can reduce costs, increase productivity, and build stronger farming communities.</p>
                    <p>Our platform offers a wide range of farming equipment, from tractors and harvesters to irrigation systems and planting tools. With our easy-to-use booking system, secure payment processing, and dedicated support team, we make equipment sharing simple and reliable.</p>
                    <p>Join our growing community today and experience the future of collaborative farming!</p>
                </div>
                <div class="about-image animate-on-scroll">
                    <img 
                        src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='600' height='400' viewBox='0 0 600 400'%3E%3Crect width='600' height='400' fill='%23f5f5f5'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='18' fill='%23999'%3ELoading Agriculture Image...%3C/text%3E%3C/svg%3E" 
                        data-src="https://www.shutterstock.com/image-photo/technology-modern-agriculture-farmer-working-260nw-2323123453.jpg" 
                        alt="Modern Agriculture" 
                        loading="lazy"
                        width="600"
                        height="400">
                </div>
            </div>
        </div>
    </section>

    <section class="cta" id="cta">
        <div class="container">
            <h2>Ready to Join AgroConnect?</h2>
            <p>Become a part of our growing community of farmers and equipment owners today!</p>
            <a href="#" class="btn">Join Now</a>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div>
                    <div class="footer-logo">
                        <img src="logo.png" alt="AgroConnect Logo" class="footer-logo-img" onerror="this.style.display='none'">
                        <h2>Agro<span>Connect</span></h2>
                    </div>
                    <p>Connecting farmers, sharing resources, and cultivating a better future for agriculture.</p>
                </div>
                <div class="footer-contact">
                    <h3>Contact</h3>
                    <div class="contact-info">
                        <p><i class="fas fa-phone"></i> +1 (123) 456-7890</p>
                        <p><i class="fas fa-envelope"></i> contact@agroconnect.com</p>
                    </div>
                </div>
                <div class="footer-social">
                    <h3>Follow Us</h3>
                    <div class="social-icons">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; AgroConnect 2025. All Rights Reserved.</p>
            </div>
            <div class="help-link">
                <a href="#">Help</a>
            </div>
        </div>
    </footer>

    <div id="loginModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="loginModalTitle" aria-describedby="loginModalDesc">
        <div class="modal-content">
            <button class="close-modal" id="closeLoginModal" aria-label="Close login modal">&times;</button>
            <div class="auth-container">
                <div class="auth-left">
                    <h1>AgroConnect</h1>
                    <p>Share Farm Equipment, Grow Together</p>
                </div>
                <div class="auth-right">
                    <div class="auth-form">
                        <h2 id="loginModalTitle">Welcome Back</h2>
                        <p id="loginModalDesc">Sign in to your AgroConnect account</p>
                        <form id="loginForm" novalidate>
                            <div class="form-group">
                                <label for="login_identifier">Email or Phone</label>
                                <input type="text" id="login_identifier" class="form-control" placeholder="Enter your email or phone" required>
                                <div class="error-message" id="login_identifierError">Please enter a valid email or phone</div>
                            </div>
                            <div class="form-group">
                                <label for="login_password">Password</label>
                                <input type="password" id="login_password" class="form-control" placeholder="Enter your password" required>
                                <div class="error-message" id="login_passwordError">Password is required</div>
                            </div>
                            <div class="form-options">
                                <div class="remember-me">
                                    <input type="checkbox" id="remember">
                                    <label for="remember">Remember me</label>
                                </div>
                                <a href="#" class="forgot-password">Forgot your password?</a>
                            </div>
                            <div class="auth-buttons-group">
                                <button type="submit" class="btn btn-primary" id="loginSubmit">Login</button>
                                <button type="button" class="btn btn-outline" id="goToSignup">Go to Signup</button>
                            </div>
                        </form>
                        <a href="#" class="back-home" id="backHomeFromLogin"><i class="fas fa-arrow-left"></i> Back to Homepage</a>
                        <div class="help-link">
                            <a href="#">Help</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="signupModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="signupModalTitle" aria-describedby="signupModalDesc">
        <div class="modal-content">
            <button class="close-modal" id="closeSignupModal" aria-label="Close signup modal">&times;</button>
            <div class="auth-container">
                <div class="auth-left">
                    <h1>AgroConnect</h1>
                    <p>Share Farm Equipment, Grow Together</p>
                </div>
                <div class="auth-right">
                    <div class="auth-form">
                        <h2 id="signupModalTitle">Create Account</h2>
                        <p id="signupModalDesc">Join AgroConnect community today</p>
                        <form id="signupForm" novalidate>
                            <div class="form-group">
                                <label for="fullName">Full Name</label>
                                <input type="text" id="fullName" class="form-control" placeholder="Enter your full name" required>
                                <div class="error-message" id="fullNameError">Please enter your full name</div>
                            </div>
                            <div class="form-group">
                                <label for="signupEmail">Email Address</label>
                                <input type="email" id="signupEmail" class="form-control" placeholder="Enter your email" required>
                                <div class="error-message" id="signupEmailError">Please enter a valid email address</div>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" class="form-control" placeholder="Enter your phone number" required>
                                <div class="error-message" id="phoneError">Please enter a valid phone number</div>
                            </div>
                            <div class="form-group">
                                <label for="role">Your Role</label>
                                <select id="role" class="form-control" required>
                                    <option value="">Select your role</option>
                                    <option value="farmer">Farmer</option>
                                    <option value="admin">Center Admin</option>
                                </select>
                                <div class="error-message" id="roleError">Please select your role</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="hub_id">Your Nearest Center Hub</label>
                                <select id="hub_id" class="form-control" required>
                                    <option value="">Select your nearest hub</option>
                                    </select>
                                <div class="error-message" id="hub_idError">Please select your hub</div>
                            </div>

                            <div class="form-group">
                                <label for="signupPassword">Password</label>
                                <input type="password" id="signupPassword" class="form-control" placeholder="Enter your password" required minlength="6">
                                <div class="error-message" id="signupPasswordError">Password must be at least 6 characters</div>
                            </div>
                            <div class="form-group">
                                <label for="confirmPassword">Confirm Password</label>
                                <input type="password" id="confirmPassword" class="form-control" placeholder="Confirm your password" required>
                                <div class="error-message" id="confirmPasswordError">Passwords do not match</div>
                            </div>
                            <div class="auth-buttons-group">
                                <button type="submit" class="btn btn-primary" id="signupSubmit">Create Account</button>
                            </div>
                        </form>
                        <div class="auth-links">
                            <p>Already have an account? <a href="#" id="goToLogin">Login here</a></p>
                        </div>
                        <a href="#" class="back-home" id="backHomeFromSignup"><i class="fas fa-arrow-left"></i> Back to Homepage</a>
                        <div class="help-link">
                            <a href="#">Help</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        // This line makes the $hubs array from our PHP code available to the JavaScript below
        const allHubData = <?php echo json_encode($hubs); ?>;
    </script>

    <script>
        // This script block is replaced with the REAL, functional code
        document.addEventListener('DOMContentLoaded', function() {
            // Register ScrollTrigger plugin (Unchanged)
            gsap.registerPlugin(ScrollTrigger);
            
            // --- ALL YOUR GSAP & ANIMATION CODE (Unchanged) ---
            // (Hero, Scroll, Tilt, Stats Counter, etc.)
            const heroTimeline = gsap.timeline();
            heroTimeline.from('#heroTitle', { duration: 1, y: 50, opacity: 0, ease: 'power3.out' })
                .from('#heroSubtitle', { duration: 0.8, y: 30, opacity: 0, ease: 'power2.out' }, '-=0.5')
                .from('#getStartedBtn', { duration: 0.6, scale: 0.8, opacity: 0, ease: 'back.out(1.7)' }, '-=0.3');
            const header = document.getElementById('mainHeader');
            window.addEventListener('scroll', function() {
                if (window.scrollY > 50) header.classList.add('scrolled');
                else header.classList.remove('scrolled');
            });
            gsap.utils.toArray('.animate-on-scroll').forEach(element => {
                gsap.fromTo(element, { y: 50, opacity: 0 }, {
                    y: 0, opacity: 1, duration: 0.8, ease: 'power2.out',
                    scrollTrigger: { trigger: element, start: 'top 80%', end: 'bottom 20%', toggleActions: 'play none none reverse' }
                });
            });
            document.querySelectorAll('.tilt-card').forEach(card => {
                card.addEventListener('mousemove', (e) => {
                    const cardRect = card.getBoundingClientRect();
                    const x = e.clientX - cardRect.left, y = e.clientY - cardRect.top;
                    const centerX = cardRect.width / 2, centerY = cardRect.height / 2;
                    const rotateY = (x - centerX) / 25, rotateX = (centerY - y) / 25;
                    card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-12px) scale(1.02)`;
                });
                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) translateY(0) scale(1)';
                });
            });
            gsap.to('.floating-cta', { y: -10, duration: 1.5, repeat: -1, yoyo: true, ease: 'power1.inOut' });
            const statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach(stat => {
                ScrollTrigger.create({
                    trigger: stat, start: 'top 80%',
                    onEnter: () => {
                        if (stat.getAttribute('data-animated') === 'false') {
                            const target = parseInt(stat.getAttribute('data-target'));
                            gsap.to(stat, {
                                innerText: target, duration: 2, snap: { innerText: 1 }, ease: 'power2.out',
                                onUpdate: function() { stat.innerText = Math.ceil(this.targets()[0].innerText); }
                            });
                            stat.setAttribute('data-animated', 'true');
                        }
                    }
                });
            });
            // --- End of Animation Code ---


            // --- Dynamic Map Function (Unchanged) ---
            function initMap() {
                const mapContainer = document.getElementById('equipmentMap');
                if (!mapContainer) return;
                const map = L.map('equipmentMap').setView([20.5937, 78.9629], 5);
                const bounds = L.latLngBounds(L.latLng(6.4627, 68.1097), L.latLng(35.5133, 97.3954));
                map.setMaxBounds(bounds);
                map.on('drag', () => map.panInsideBounds(bounds, { animate: false }));
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                    maxBounds: bounds, maxZoom: 10, minZoom: 4
                }).addTo(map);
                
                allHubData.forEach(hub => {
                    const customIcon = L.divIcon({
                        html: `<div style="background-color: var(--primary-color); width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 10px rgba(0,0,0,0.2);"></div>`,
                        className: 'custom-marker', iconSize: [26, 26], iconAnchor: [13, 13]
                    });
                    const marker = L.marker([hub.latitude, hub.longitude], { icon: customIcon })
                        .addTo(map)
                        .bindPopup(`<div style="padding: 10px; font-family: 'Inter', sans-serif;"><h4 style="margin: 0 0 8px 0; color: #1f2937;">${hub.hub_name}</h4><p style="margin: 0; color: #6b7280; font-size: 14px;">${hub.address || 'Central Hub'}</p></div>`);
                    marker.on('popupopen', function() {
                        const content = this.getPopup().getElement();
                        gsap.fromTo(content, { scale: 0.8, opacity: 0 }, { scale: 1, opacity: 1, duration: 0.3, ease: 'back.out(1.7)' });
                    });
                });
            }
            initMap();


            // --- GSAP Modal Animation Functions (Unchanged) ---
            function openModal(modal) {
                gsap.to(modal, { display: 'block', duration: 0 });
                gsap.fromTo(modal.querySelector('.modal-content'), 
                    { scale: 0.8, opacity: 0 },
                    { scale: 1, opacity: 1, duration: 0.3, ease: 'back.out(1.7)' }
                );
            }
            function closeModal(modal) {
                gsap.to(modal.querySelector('.modal-content'), {
                    scale: 0.8, opacity: 0, duration: 0.2, ease: 'power2.in',
                    onComplete: () => gsap.to(modal, { display: 'none', duration: 0 })
                });
            }


            // --- Toast Notification System (Unchanged) ---
            window.showToast = function(title, message, type = 'info', duration = 5000) {
                const toastContainer = document.getElementById('toastContainer');
                const toast = document.createElement('div');
                toast.className = `toast ${type}`;
                const icons = { info: 'fas fa-info-circle', success: 'fas fa-check-circle', error: 'fas fa-exclamation-circle', warning: 'fas fa-exclamation-triangle' };
                toast.innerHTML = `<i class="${icons[type]}"></i><div class="toast-content"><div class="toast-title">${title}</div><div class="toast-message">${message}</div></div><button class="toast-close">&times;</button>`;
                toastContainer.appendChild(toast);
                gsap.fromTo(toast, { x: 100, opacity: 0 }, { x: 0, opacity: 1, duration: 0.4, ease: 'power2.out', onComplete: () => toast.classList.add('show') });
                toast.querySelector('.toast-close').addEventListener('click', () => hideToast(toast));
                if (duration > 0) setTimeout(() => hideToast(toast), duration);
            }
            function hideToast(toast) {
                gsap.to(toast, { x: 100, opacity: 0, duration: 0.3, ease: 'power2.in', onComplete: () => toast.parentNode?.removeChild(toast) });
            }

            
            // --- Mobile Navigation (Unchanged) ---
            const hamburger = document.getElementById('hamburger');
            const mobileMenu = document.getElementById('mobileMenu');
            hamburger.addEventListener('click', () => {
                const isExpanded = hamburger.getAttribute('aria-expanded') === 'true';
                hamburger.setAttribute('aria-expanded', !isExpanded);
                hamburger.classList.toggle('active');
                mobileMenu.classList.toggle('active');
            });

            
            // === *** NEW: REAL BACKEND CONNECTIONS *** ===
            
            // ** 1. REAL Logout **
            // We check if the logout button exists (it's only there if logged in)
            const logoutBtn = document.getElementById('logoutBtn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', () => {
                    fetch('api/logout.php', { method: 'POST' })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'success') {
                                showToast('Logged Out', 'You have been logged out.', 'info');
                                location.reload(); // Reload the page
                            }
                        })
                        .catch(err => {
                            console.error('Logout Error:', err);
                            showToast('Error', 'Logout failed. Please try again.', 'error');
                        });
                });
            }


            // ** 2. REAL Login **
            const loginForm = document.getElementById('loginForm');
            const loginSubmit = document.getElementById('loginSubmit');
            
            loginForm.addEventListener('submit', (e) => {
                e.preventDefault();
                if (!validateLoginForm()) return; // Run new validation

                loginSubmit.disabled = true;
                loginSubmit.innerHTML = '<span class="spinner"></span> Logging in...';
                
                const formData = {
                    login_identifier: document.getElementById('login_identifier').value,
                    password: document.getElementById('login_password').value
                };

                fetch('api/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showToast('Welcome Back!', data.message, 'success');
                        // SUCCESS! Reload the page.
                        // PHP will see the new session and log us in.
                        location.reload();
                    } else {
                        // Show error from backend (e.g., "Invalid password")
                        showToast('Login Failed', data.message, 'error');
                        loginSubmit.disabled = false;
                        loginSubmit.innerHTML = 'Login';
                    }
                })
                .catch(err => {
                    console.error('Login Error:', err);
                    showToast('Error', 'Could not connect to the server.', 'error');
                    loginSubmit.disabled = false;
                    loginSubmit.innerHTML = 'Login';
                });
            });


            // ** 3. REAL Signup **
            const signupForm = document.getElementById('signupForm');
            const signupSubmit = document.getElementById('signupSubmit');
            
            signupForm.addEventListener('submit', (e) => {
                e.preventDefault();
                if (!validateSignupForm()) return; // Run new validation

                signupSubmit.disabled = true;
                signupSubmit.innerHTML = '<span class="spinner"></span> Creating Account...';
                
                const formData = {
                    full_name: document.getElementById('fullName').value,
                    email: document.getElementById('signupEmail').value,
                    phone: document.getElementById('phone').value,
                    role: document.getElementById('role').value,
                    hub_id: document.getElementById('hub_id').value,
                    password: document.getElementById('signupPassword').value
                };

                fetch('api/register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showToast('Account Created!', data.message, 'success');
                        // Close signup, open login so they can log in
                        closeModal(signupModal);
                        openModal(loginModal);
                    } else {
                        // Show specific error from backend (e.g., "Email already in use")
                        showToast('Registration Failed', data.message, 'error');
                    }
                })
                .catch(err => {
                    console.error('Signup Error:', err);
                    showToast('Error', 'Could not connect to the server.', 'error');
                })
                .finally(() => {
                    // Always re-enable the button
                    signupSubmit.disabled = false;
                    signupSubmit.innerHTML = 'Create Account';
                });
            });
            
            
            // --- MODAL & FORM HELPERS (Unchanged from your file) ---
            const loginBtn = document.getElementById('loginBtn');
            const signupBtn = document.getElementById('signupBtn');
            const loginModal = document.getElementById('loginModal');
            const signupModal = document.getElementById('signupModal');
            const goToSignup = document.getElementById('goToSignup');
            const goToLogin = document.getElementById('goToLogin');
            const getStartedBtn = document.getElementById('getStartedBtn');

            // --- Modal Click Handlers (Updated for Hub Dropdown) ---
            loginBtn.addEventListener('click', (e) => { e.preventDefault(); openModal(loginModal); });
            
            function openSignup() {
                populateHubsDropdown(); // Call helper function
                openModal(signupModal);
                document.body.style.overflow = 'hidden';
                signupModal.setAttribute('aria-hidden', 'false');
                setTimeout(() => document.getElementById('fullName').focus(), 100);
                trapFocus(signupModal);
            }
            
            signupBtn.addEventListener('click', (e) => { e.preventDefault(); openSignup(); });
            getStartedBtn.addEventListener('click', (e) => { e.preventDefault(); openSignup(); });
            
            goToSignup.addEventListener('click', (e) => {
                e.preventDefault();
                closeModal(loginModal);
                setTimeout(openSignup, 300);
            });
            goToLogin.addEventListener('click', (e) => {
                e.preventDefault();
                closeModal(signupModal);
                setTimeout(() => openModal(loginModal), 300);
            });
            
            document.getElementById('closeLoginModal').addEventListener('click', (e) => { e.preventDefault(); closeModal(loginModal); });
            document.getElementById('closeSignupModal').addEventListener('click', (e) => { e.preventDefault(); closeModal(signupModal); });
            document.getElementById('backHomeFromLogin').addEventListener('click', (e) => { e.preventDefault(); closeModal(loginModal); });
            document.getElementById('backHomeFromSignup').addEventListener('click', (e) => { e.preventDefault(); closeModal(signupModal); });

            function trapFocus(modal) {
                const focusableElements = modal.querySelectorAll(
                    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
                );
                const firstElement = focusableElements[0];
                const lastElement = focusableElements[focusableElements.length - 1];
                modal.addEventListener('keydown', function(e) {
                    if (e.key === 'Tab') {
                        if (e.shiftKey) {
                            if (document.activeElement === firstElement) { e.preventDefault(); lastElement.focus(); }
                        } else {
                            if (document.activeElement === lastElement) { e.preventDefault(); firstElement.focus(); }
                        }
                    }
                });
            }

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    if (loginModal.style.display === 'block') closeModal(loginModal);
                    if (signupModal.style.display === 'block') closeModal(signupModal);
                }
            });
            window.addEventListener('click', (e) => {
                if (e.target === loginModal) closeModal(loginModal);
                if (e.target === signupModal) closeModal(signupModal);
            });


            // --- Form validation helpers (Unchanged from your file) ---
            function showError(inputId, errorId, message) {
                const input = document.getElementById(inputId);
                const error = document.getElementById(errorId);
                input?.classList.add('error');
                input?.classList.remove('success');
                if (error) { error.textContent = message; error.style.display = 'block'; }
            }
            function showSuccess(inputId) {
                const input = document.getElementById(inputId);
                const error = document.getElementById(inputId + 'Error');
                input?.classList.remove('error');
                input?.classList.add('success');
                if (error) error.style.display = 'none';
            }
            function validateEmail(email) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email); }
            function validatePhone(phone) { return /^(?:\+91)?[6-9]\d{9}$/.test(phone.replace(/[\s-]/g, '')); }

            // --- ** NEW: Validation functions to match new forms ** ---
            function validateLoginForm() {
                let isValid = true;
                const identifier = document.getElementById('login_identifier').value;
                const password = document.getElementById('login_password').value;
                
                if (!identifier) {
                    showError('login_identifier', 'login_identifierError', 'Email or Phone is required'); isValid = false;
                } else { showSuccess('login_identifier'); }
                
                if (!password) {
                    showError('login_password', 'login_passwordError', 'Password is required'); isValid = false;
                } else { showSuccess('login_password'); }
                
                return isValid;
            }
            
            function validateSignupForm() {
                let isValid = true;
                const fields = ['fullName', 'signupEmail', 'phone', 'role', 'hub_id', 'signupPassword', 'confirmPassword'];
                fields.forEach(id => showSuccess(id)); // Reset all fields

                const fullName = document.getElementById('fullName').value;
                const email = document.getElementById('signupEmail').value;
                const phone = document.getElementById('phone').value;
                const role = document.getElementById('role').value;
                const hub_id = document.getElementById('hub_id').value;
                const password = document.getElementById('signupPassword').value;
                const confirmPassword = document.getElementById('confirmPassword').value;

                if (!fullName) { showError('fullName', 'fullNameError', 'Full name is required'); isValid = false; }
                if (!email) { showError('signupEmail', 'signupEmailError', 'Email is required'); isValid = false; }
                else if (!validateEmail(email)) { showError('signupEmail', 'signupEmailError', 'Invalid email address'); isValid = false; }
                if (!phone) { showError('phone', 'phoneError', 'Phone number is required'); isValid = false; }
                else if (!validatePhone(phone)) { showError('phone', 'phoneError', 'Invalid 10-digit Indian phone number'); isValid = false; }
                if (!role) { showError('role', 'roleError', 'Please select your role'); isValid = false; }
                if (!hub_id) { showError('hub_id', 'hub_idError', 'Please select your nearest hub'); isValid = false; }
                if (!password) { showError('signupPassword', 'signupPasswordError', 'Password is required'); isValid = false; }
                else if (password.length < 6) { showError('signupPassword', 'signupPasswordError', 'Password must be at least 6 characters'); isValid = false; }
                if (password !== confirmPassword) { showError('confirmPassword', 'confirmPasswordError', 'Passwords do not match'); isValid = false; }
                else if (!confirmPassword) { showError('confirmPassword', 'confirmPasswordError', 'Please confirm your password'); isValid = false; }
                
                return isValid;
            }

            // --- ** NEW: Helper function to fill hub dropdown ** ---
            function populateHubsDropdown() {
                const hubSelect = document.getElementById('hub_id');
                // Only populate if it's empty (has 1 option: "Select...")
                if (hubSelect.options.length > 1) return;

                // Use the 'allHubData' we got from PHP at the top
                allHubData.forEach(hub => {
                    const option = document.createElement('option');
                    option.value = hub.hub_id;
                    option.textContent = hub.hub_name;
                    hubSelect.appendChild(option);
                });
            }


            // --- Real-time validation (Unchanged from your file) ---
            const formInputs = document.querySelectorAll('.form-control');
            formInputs.forEach(input => {
                input.addEventListener('blur', () => {
                    const inputId = input.id;
                    switch(inputId) {
                        case 'email': // This ID is from your original login form
                        case 'login_identifier': // This is the new one
                        case 'signupEmail':
                            if (input.value && !validateEmail(input.value)) {
                                showError(inputId, inputId + 'Error', 'Please enter a valid email address');
                            } else if (input.value) {
                                showSuccess(inputId);
                            }
                            break;
                        case 'phone':
                            if (input.value && !validatePhone(input.value)) {
                                showError(inputId, inputId + 'Error', 'Please enter a valid 10-digit Indian phone number');
                            } else if (input.value) {
                                showSuccess(inputId);
                            }
                            break;
                        case 'signupPassword':
                            if (input.value && input.value.length < 6) {
                                showError(inputId, inputId + 'Error', 'Password must be at least 6 characters');
                            } else if (input.value) {
                                showSuccess(inputId);
                            }
                            break;
                        case 'confirmPassword':
                            const password = document.getElementById('signupPassword').value;
                            if (input.value && password !== input.value) {
                                showError(inputId, inputId + 'Error', 'Passwords do not match');
                            } else if (input.value) {
                                showSuccess(inputId);
                            }
                            break;
                        default:
                            if (input.value) {
                                showSuccess(inputId);
                            }
                    }
                });
            });

            // Lazy load images (Unchanged)
            function lazyLoadImages() {
                const lazyImages = document.querySelectorAll('img[data-src]');
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.getAttribute('data-src');
                            img.classList.add('loaded');
                            img.removeAttribute('data-src');
                            imageObserver.unobserve(img);
                        }
                    });
                });
                lazyImages.forEach(img => {
                    imageObserver.observe(img);
                });
            }

            // Simple routing for navigation links (Unchanged)
            document.querySelectorAll('nav a, .mobile-nav a').forEach(link => {
                link.addEventListener('click', (e) => {
                    const href = link.getAttribute('href');
                    if (href.startsWith('#')) {
                        e.preventDefault();
                        const targetId = href.substring(1);
                        const targetElement = document.getElementById(targetId);
                        if (targetElement) {
                            gsap.to(window, {
                                duration: 1,
                                scrollTo: { y: targetElement, offsetY: 80 },
                                ease: 'power2.inOut'
                            });
                        }
                    }
                });
            });

            // Initialize all features when page loads (Unchanged)
            window.addEventListener('load', () => {
                lazyLoadImages();
                setTimeout(() => {
                    showToast('Welcome to AgroConnect', 'Start sharing farm equipment with your community today!', 'info', 6000);
                }, 1000);
            });

            // Floating CTA click handler (Unchanged)
            document.getElementById('floatingCta').addEventListener('click', function() {
                console.log("Floating button clicked. Chatbot should open.");
            });
        });
    </script>
    
    <script src="js/chat-widget.js"></script>

</body>
</html>