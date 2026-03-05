<?php
/**
 * ------------------------------------------------------------------------
 *  Author : Ngonidzashe Jiji
 *  Handles: Instagram: @davidrichchild
 *           Telegram: t.me/david_richchild
 *           TikTok: davidrichchild
 *  URLs    : https://arrissadata.com
 *            https://arrissatechnologies.com
 *            https://arrissa.trade
 *
 *  Course  : https://www.udemy.com/course/6804721
 *
 *  Permission:
 *    You are granted permission to use, copy, modify, and distribute this
 *    code for personal or commercial projects, provided that the author
 *    details above remain intact and visible in the distributed code or
 *    accompanying documentation.
 *
 *  Requirements:
 *    - Keep this header (author details, URLs, and course link) in the
 *      distributed source or bundled output. Do not remove or hide it.
 *
 *  Disclaimer:
 *    This software is provided "AS IS", without any warranty. The author
 *    will not be liable for any damages or claims arising from its use.
 *
 *  Version: 1.0
 *  Date:    2025-09-20
 * ------------------------------------------------------------------------
 */
/**
 * n8n Chat - ChatGPT Style Dark Theme Interface with Model Selection
 * ----------------------------------------------------------------
 * - Centered chat widget on desktop with rounded corners
 * - Model selection dropdown with session reset
 * - Complete dark theme integration
 * - Mobile-friendly WhatsApp-style design with proper input positioning
 */

// =====================
// CONFIGURATION
// =====================
$webhookUrl = WEBHOOK_URL_HERE"; 
$chatTitle  = "Arrissa AI";
$chatSubtitle = "Your AI assistant";
$initialMessages = [
    "Hello! I'm Arrissa AI. How can I help you today?",
    "Feel free to ask me anything."
];
$enableStreaming = false;

// Available AI Models - Easy to modify
$availableModels = [
    'analysis-model-1' => 'Analysis Model 1',
	'analysis-model-2' => 'Analysis Model 2',
	'analysis-model-3' => 'Analysis Model 3'
];

// Handle model change with new session creation
if (isset($_POST['selected_model']) && array_key_exists($_POST['selected_model'], $availableModels)) {
    // Create new session when model changes
    session_start();
    unset($_SESSION['chat_session_id']);
    $_SESSION['selected_model'] = $_POST['selected_model'];
    $_SESSION['chat_session_id'] = bin2hex(random_bytes(16));
    
    // Return the new session info
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'newSessionId' => $_SESSION['chat_session_id'],
        'selectedModel' => $_POST['selected_model']
    ]);
    exit;
}

// Generate proper session ID (32-char hex)
session_start();
if (!isset($_SESSION['chat_session_id'])) {
    $_SESSION['chat_session_id'] = bin2hex(random_bytes(16));
}
$sessionId = $_SESSION['chat_session_id'];

// Handle model selection
$selectedModel = $_SESSION['selected_model'] ?? 'analysis-model-1';
// =====================
// END CONFIGURATION
// =====================
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title><?= htmlspecialchars($chatTitle) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover" />
  <link href="https://cdn.jsdelivr.net/npm/@n8n/chat/dist/style.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    :root {
      /* ChatGPT Dark Theme Colors */
      --primary-color: #10a37f;
      --primary-hover: #0d8f6b;
      --primary-active: #0a7c5a;
      
      --bg-primary: #0f0f10;
      --bg-secondary: #1a1a1b;
      --bg-tertiary: #2a2a2b;
      --bg-quaternary: #3a3a3b;
      
      --text-primary: #ffffff;
      --text-secondary: #b4b4b4;
      --text-tertiary: #8e8ea0;
      --text-quaternary: #6e6e80;
      
      --border-primary: #3e3e40;
      --border-secondary: #4a4a4c;
      
      --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.2);
      --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.3);
      --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.4);
      
      --radius-sm: 8px;
      --radius-md: 12px;
      --radius-lg: 16px;
      --radius-xl: 20px;
      --radius-full: 50px;
      
      --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html {
      height: 100%;
      /* Prevent zoom on iOS */
      -webkit-text-size-adjust: 100%;
      -ms-text-size-adjust: 100%;
    }

    body {
      height: 100%;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: var(--bg-primary);
      color: var(--text-primary);
      overflow: hidden;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      /* Prevent rubber band scrolling on iOS */
      overscroll-behavior: none;
      /* Ensure proper height on mobile */
      position: fixed;
      width: 100%;
      top: 0;
      left: 0;
    }

    .app-container {
      display: flex;
      height: 100vh;
      height: 100dvh; /* Dynamic viewport height for mobile */
      width: 100vw;
      position: relative;
      overflow: hidden;
    }

    /* =====================
       SIDEBAR STYLES
    ===================== */
    .sidebar {
      width: 280px;
      background: var(--bg-secondary);
      border-right: 1px solid var(--border-primary);
      display: flex;
      flex-direction: column;
      transition: var(--transition);
      z-index: 100;
    }

    .sidebar-header {
      padding: 20px;
      border-bottom: 1px solid var(--border-primary);
      background: var(--bg-tertiary);
    }

    .new-chat-btn {
      width: 100%;
      padding: 14px 18px;
      background: var(--bg-quaternary);
      border: 1px solid var(--border-secondary);
      border-radius: var(--radius-md);
      color: var(--text-primary);
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: var(--transition);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      font-family: inherit;
      margin-bottom: 16px;
    }

    .new-chat-btn:hover {
      background: var(--primary-color);
      border-color: var(--primary-color);
      transform: translateY(-1px);
      box-shadow: var(--shadow-md);
    }

    .new-chat-btn:active {
      transform: translateY(0);
      background: var(--primary-active);
    }

    .new-chat-btn i {
      font-size: 16px;
    }

    /* Model Selection */
    .model-selection {
      margin-top: 8px;
    }

    .model-label {
      font-size: 12px;
      color: var(--text-tertiary);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 8px;
      font-weight: 600;
    }

    .model-dropdown {
      width: 100%;
      padding: 12px 16px;
      background: var(--bg-secondary);
      border: 1px solid var(--border-primary);
      border-radius: var(--radius-md);
      color: var(--text-primary);
      font-size: 14px;
      font-family: inherit;
      cursor: pointer;
      transition: var(--transition);
      appearance: none;
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
      background-position: right 12px center;
      background-repeat: no-repeat;
      background-size: 16px;
      padding-right: 40px;
    }

    .model-dropdown:hover {
      border-color: var(--border-secondary);
      background-color: var(--bg-tertiary);
    }

    .model-dropdown:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(16, 163, 127, 0.1);
    }

    .model-dropdown option {
      background: var(--bg-secondary);
      color: var(--text-primary);
      padding: 8px;
    }

    .sidebar-content {
      flex: 1;
      padding: 20px;
      overflow-y: auto;
    }

    .session-info {
      background: var(--bg-tertiary);
      border: 1px solid var(--border-primary);
      border-radius: var(--radius-md);
      padding: 16px;
      margin-bottom: 20px;
    }

    .session-label {
      font-size: 12px;
      color: var(--text-tertiary);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 8px;
      font-weight: 600;
    }

    .session-id {
      font-family: 'Monaco', 'Menlo', 'Consolas', monospace;
      font-size: 13px;
      color: var(--text-secondary);
      word-break: break-all;
      padding: 8px 12px;
      background: var(--bg-primary);
      border-radius: var(--radius-sm);
      border: 1px solid var(--border-primary);
    }

    .current-model {
      background: var(--bg-tertiary);
      border: 1px solid var(--border-primary);
      border-radius: var(--radius-md);
      padding: 16px;
	  margin-bottom:20px;
    }

    .current-model-name {
      font-size: 14px;
      color: var(--primary-color);
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* Model changing indicator */
    .model-changing {
      opacity: 0.6;
      pointer-events: none;
    }

    .model-changing::after {
      content: " (Switching...)";
      color: var(--text-tertiary);
      font-size: 12px;
    }

    /* =====================
       MAIN CONTENT STYLES
    ===================== */
    .main-content {
      flex: 1;
      display: flex;
      flex-direction: column;
      background: var(--bg-primary);
      position: relative;
      padding: 24px; /* Padding around the chat widget */
      overflow: hidden;
    }

    .mobile-menu-btn {
      display: none;
      position: fixed;
      top: 20px;
      left: 20px;
      background: var(--bg-tertiary);
      border: 1px solid var(--border-primary);
      color: var(--text-primary);
      width: 44px;
      height: 44px;
      border-radius: var(--radius-md);
      cursor: pointer;
      transition: var(--transition);
      align-items: center;
      justify-content: center;
      z-index: 200;
      box-shadow: var(--shadow-md);
    }

    .mobile-menu-btn:hover {
      background: var(--bg-quaternary);
      border-color: var(--border-secondary);
    }

    .chat-container {
      flex: 1;
      width: 100%;
      max-width: 900px;
      margin: 0 auto;
      display: flex;
      flex-direction: column;
      background: var(--bg-secondary);
      border: 1px solid var(--border-primary);
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-lg);
      overflow: hidden;
      position: relative;
      height: 100%;
    }

    #n8n-chat {
      flex: 1;
      width: 100%;
      height: 100%;
      min-height: 600px;
      border-radius: var(--radius-xl);
      display: flex;
      flex-direction: column;
    }

    /* =====================
       MOBILE RESPONSIVE
    ===================== */
    @media (max-width: 768px) {
      body {
        /* Prevent address bar from affecting layout */
        height: 100vh;
        height: 100dvh;
      }

      .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        height: 100dvh;
        transform: translateX(-100%);
        z-index: 1000;
        box-shadow: var(--shadow-lg);
      }

      .sidebar.open {
        transform: translateX(0);
      }

      .mobile-menu-btn {
        display: flex;
        /* Adjust for safe area on notched devices */
        top: max(20px, env(safe-area-inset-top, 20px));
        left: max(20px, env(safe-area-inset-left, 20px));
      }

      .app-container {
        height: 100vh;
        height: 100dvh;
      }

      .main-content {
        padding: 0;
        /* Account for safe areas */
        padding-top: env(safe-area-inset-top, 0);
        padding-bottom: env(safe-area-inset-bottom, 0);
        padding-left: env(safe-area-inset-left, 0);
        padding-right: env(safe-area-inset-right, 0);
      }

      .chat-container {
        max-width: 100%;
        border-radius: 0;
        border: none;
        box-shadow: none;
        height: 100%;
        /* Ensure it fills available space properly */
        min-height: 0;
      }

      #n8n-chat {
        border-radius: 0;
        min-height: 0;
        height: 100%;
      }
    }

    /* =====================
       SIDEBAR OVERLAY
    ===================== */
    .sidebar-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      height: 100dvh;
      background: rgba(0, 0, 0, 0.6);
      backdrop-filter: blur(4px);
      z-index: 999;
      opacity: 0;
      transition: var(--transition);
    }

    .sidebar-overlay.active {
      display: block;
      opacity: 1;
    }

    /* =====================
       N8N CHAT WIDGET CUSTOMIZATION
    ===================== */
    :root {
      /* Override n8n chat variables for dark theme */
      --chat--color-primary: var(--primary-color) !important;
      --chat--color-primary-shade-50: var(--primary-hover) !important;
      --chat--color-primary-shade-100: var(--primary-active) !important;
      --chat--color-secondary: var(--primary-color) !important;
      --chat--color-secondary-shade-50: var(--primary-hover) !important;
      
      --chat--color-white: var(--text-primary) !important;
      --chat--color-light: var(--bg-secondary) !important;
      --chat--color-light-shade-50: var(--bg-tertiary) !important;
      --chat--color-light-shade-100: var(--bg-quaternary) !important;
      --chat--color-medium: var(--border-primary) !important;
      --chat--color-dark: var(--bg-primary) !important;
      --chat--color-disabled: var(--text-quaternary) !important;
      --chat--color-typing: var(--text-tertiary) !important;

      --chat--spacing: 1rem !important;
      --chat--border-radius: var(--radius-lg) !important;
      --chat--transition-duration: 0.2s !important;

      --chat--header-height: 0 !important;
      --chat--header--padding: 0 !important;
      --chat--header--background: transparent !important;
      --chat--header--color: transparent !important;
      --chat--header--border-top: none !important;
      --chat--header--border-bottom: none !important;

      --chat--textarea--height: 56px !important;

      --chat--message--font-size: 15px !important;
      --chat--message--padding: 16px 20px !important;
      --chat--message--border-radius: 18px !important;
      --chat--message-line-height: 1.5 !important;
      
      --chat--message--bot--background: var(--bg-tertiary) !important;
      --chat--message--bot--color: var(--text-primary) !important;
      --chat--message--bot--border: 1px solid var(--border-primary) !important;
      
      --chat--message--user--background: var(--primary-color) !important;
      --chat--message--user--color: white !important;
      --chat--message--user--border: none !important;
      
      --chat--message--pre--background: var(--bg-quaternary) !important;

      --chat--toggle--background: var(--primary-color) !important;
      --chat--toggle--hover--background: var(--primary-hover) !important;
      --chat--toggle--active--background: var(--primary-active) !important;
      --chat--toggle--color: white !important;
      --chat--toggle--size: 64px !important;
    }

    /* Hide n8n default header completely */
    :global(.n8n-chat .chat-header),
    :global(.n8n-chat-header),
    :global([class*="header"]) {
      display: none !important;
      height: 0 !important;
      padding: 0 !important;
      margin: 0 !important;
      visibility: hidden !important;
    }

    /* Style the main chat container */
    :global(.n8n-chat) {
      background: var(--bg-primary) !important;
      color: var(--text-primary) !important;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
      border-radius: var(--radius-xl) !important;
      overflow: hidden !important;
      height: 100% !important;
      display: flex !important;
      flex-direction: column !important;
    }

    /* Style the input container - CRITICAL FOR MOBILE */
    :global(.n8n-chat .chat-input-container) {
      background: var(--bg-secondary) !important;
      border-top: 1px solid var(--border-primary) !important;
      padding: 16px 20px !important;
      /* Ensure it stays at bottom on mobile */
      position: relative !important;
      bottom: 0 !important;
      left: 0 !important;
      right: 0 !important;
      z-index: 100 !important;
      /* Account for safe area on mobile */
      padding-bottom: max(16px, env(safe-area-inset-bottom, 16px)) !important;
      /* Prevent it from being pushed off screen */
      flex-shrink: 0 !important;
      min-height: 80px !important;
    }

    /* Style the input field */
    :global(.n8n-chat .chat-input) {
      background: var(--bg-tertiary) !important;
      border: 1px solid var(--border-primary) !important;
      border-radius: var(--radius-lg) !important;
      color: var(--text-primary) !important;
      padding: 16px 60px 16px 20px !important;
      font-size: 16px !important; /* Prevent zoom on iOS */
      font-family: inherit !important;
      transition: var(--transition) !important;
      width: 100% !important;
      min-height: 48px !important;
      resize: none !important;
      /* Prevent input from being cut off */
      box-sizing: border-box !important;
    }

    :global(.n8n-chat .chat-input:focus) {
      border-color: var(--primary-color) !important;
      box-shadow: 0 0 0 3px rgba(16, 163, 127, 0.1) !important;
      background: var(--bg-quaternary) !important;
      outline: none !important;
    }

    :global(.n8n-chat .chat-input::placeholder) {
      color: var(--text-tertiary) !important;
    }

    /* Style the send button */
    :global(.n8n-chat .chat-input-send-button) {
      background: var(--primary-color) !important;
      border: none !important;
      border-radius: var(--radius-full) !important;
      width: 44px !important;
      height: 44px !important;
      transition: var(--transition) !important;
      position: absolute !important;
      right: 8px !important;
      top: 50% !important;
      transform: translateY(-50%) !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      color: white !important;
      cursor: pointer !important;
      /* Ensure button is always accessible */
      z-index: 10 !important;
    }

    :global(.n8n-chat .chat-input-send-button:hover) {
      background: var(--primary-hover) !important;
      transform: translateY(-50%) scale(1.05) !important;
      box-shadow: var(--shadow-md) !important;
    }

    :global(.n8n-chat .chat-input-send-button:active) {
      background: var(--primary-active) !important;
      transform: translateY(-50%) scale(0.95) !important;
    }

    /* Style message bubbles */
    :global(.n8n-chat .chat-message) {
      margin-bottom: 16px !important;
    }

    :global(.n8n-chat .chat-message.user) {
      margin-left: 80px !important;
    }

    :global(.n8n-chat .chat-message.bot) {
      margin-right: 80px !important;
    }

    /* Style the chat messages container */
    :global(.n8n-chat .chat-messages-container) {
      padding: 24px !important;
      background: var(--bg-primary) !important;
      flex: 1 !important;
      overflow-y: auto !important;
      /* Ensure proper scrolling on mobile */
      -webkit-overflow-scrolling: touch !important;
      /* Account for input container */
      padding-bottom: 100px !important;
    }

    /* Mobile specific adjustments */
    @media (max-width: 768px) {
      :global(.n8n-chat .chat-input-container) {
        padding: 12px 16px !important;
        padding-bottom: max(12px, env(safe-area-inset-bottom, 12px)) !important;
        min-height: 70px !important;
      }

      :global(.n8n-chat .chat-input) {
        font-size: 16px !important; /* Prevent zoom on iOS */
        padding: 14px 55px 14px 16px !important;
        min-height: 44px !important;
      }

      :global(.n8n-chat .chat-input-send-button) {
        width: 40px !important;
        height: 40px !important;
        right: 6px !important;
      }

      :global(.n8n-chat .chat-messages-container) {
        padding: 16px !important;
        padding-bottom: 80px !important;
      }

      :global(.n8n-chat .chat-message.user) {
        margin-left: 40px !important;
      }

      :global(.n8n-chat .chat-message.bot) {
        margin-right: 40px !important;
      }
    }

    /* Scrollbar styling */
    :global(.n8n-chat .chat-messages-container::-webkit-scrollbar) {
      width: 6px !important;
    }

    :global(.n8n-chat .chat-messages-container::-webkit-scrollbar-track) {
      background: var(--bg-secondary) !important;
    }

    :global(.n8n-chat .chat-messages-container::-webkit-scrollbar-thumb) {
      background: var(--border-primary) !important;
      border-radius: 3px !important;
    }

    :global(.n8n-chat .chat-messages-container::-webkit-scrollbar-thumb:hover) {
      background: var(--border-secondary) !important;
    }

    /* Loading animation */
    .loading-container {
      display: flex;
      align-items: center;
      justify-content: center;
      height: 200px;
      color: var(--text-secondary);
      font-size: 14px;
      flex-direction: column;
      gap: 16px;
    }

    .loading-spinner {
      width: 32px;
      height: 32px;
      border: 3px solid var(--bg-tertiary);
      border-top: 3px solid var(--primary-color);
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    /* Custom scrollbar for sidebar */
    .sidebar-content::-webkit-scrollbar {
      width: 6px;
    }

    .sidebar-content::-webkit-scrollbar-track {
      background: var(--bg-tertiary);
    }

    .sidebar-content::-webkit-scrollbar-thumb {
      background: var(--border-primary);
      border-radius: 3px;
    }

    .sidebar-content::-webkit-scrollbar-thumb:hover {
      background: var(--border-secondary);
    }

    /* Fix for iOS Safari viewport issues */
    @supports (-webkit-touch-callout: none) {
      .app-container {
        height: -webkit-fill-available;
      }
      
      @media (max-width: 768px) {
        .app-container {
          height: -webkit-fill-available;
        }
        
        .sidebar {
          height: -webkit-fill-available;
        }
        
        .sidebar-overlay {
          height: -webkit-fill-available;
        }
      }
    }
  </style>
</head>
<body>
  <div class="app-container">
    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn" id="mobileMenuBtn" onclick="toggleSidebar()">
      <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <button class="new-chat-btn" onclick="startNewChat()">
          <i class="fas fa-plus"></i>
          New conversation
        </button>
        
        <div class="model-selection">
          <div class="model-label">AI Model</div>
          <select class="model-dropdown" id="modelSelect" onchange="changeModel()">
            <?php foreach ($availableModels as $key => $name): ?>
              <option value="<?= htmlspecialchars($key) ?>" <?= $key === $selectedModel ? 'selected' : '' ?>>
                <?= htmlspecialchars($name) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      
      <div class="sidebar-content">
        <div class="current-model">
          <div class="session-label">Current Model</div>
          <div class="current-model-name">
            <i class="fas fa-robot"></i>
            <span id="currentModelName"><?= htmlspecialchars($availableModels[$selectedModel]) ?></span>
          </div>
        </div>
        
        <div class="session-info">
          <div class="session-label">Session ID</div>
          <div class="session-id"><?= $sessionId ?></div>
        </div>
      </div>
    </div>

    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <!-- Main Content -->
    <div class="main-content">
      <div class="chat-container">
        <div id="n8n-chat">
          <div class="loading-container">
            <div class="loading-spinner"></div>
            <div>Initializing <?= htmlspecialchars($chatTitle) ?>...</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script type="module">
    import { createChat } from 'https://cdn.jsdelivr.net/npm/@n8n/chat/dist/chat.bundle.es.js';

    // Global variables
    window.chatInstance = null;
    window.currentSessionId = "<?= $sessionId ?>";
    window.currentModel = "<?= $selectedModel ?>";

    // Initialize chat with proper configuration
    function initializeChat() {
      console.log('🚀 Initializing Arrissa AI Chat');
      console.log('📱 Session ID:', window.currentSessionId);
      console.log('🤖 Model:', window.currentModel);
      
      try {
        window.chatInstance = createChat({
          webhookUrl: "<?= $webhookUrl ?>",
          target: "#n8n-chat",
          mode: "fullscreen",
          
          // Session configuration
          loadPreviousSession: true,
          chatSessionKey: "sessionId",
          chatInputKey: "chatInput",
          
          // UI Configuration
          showWelcomeScreen: false,
          enableStreaming: <?= $enableStreaming ? 'true' : 'false' ?>,
          initialMessages: <?= json_encode($initialMessages) ?>,
          
          // Metadata
          metadata: {
            sessionId: window.currentSessionId,
            model: window.currentModel,
            userAgent: navigator.userAgent,
            timestamp: new Date().toISOString()
          },
          
          // Webhook configuration
          webhookConfig: {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-Session-ID': window.currentSessionId,
              'X-Model': window.currentModel
            }
          },
          
          // Internationalization
          i18n: {
            en: {
              title: "",
              subtitle: "",
              inputPlaceholder: "Message <?= addslashes($chatTitle) ?>...",
              footer: "",
              getStarted: "Start chatting"
            }
          }
        });

        console.log('✅ Chat initialized successfully');
        
      } catch (error) {
        console.error('❌ Chat initialization failed:', error);
      }

      // Ensure proper payload format
      interceptWebhookRequests();
    }

    // Intercept and format webhook requests
    function interceptWebhookRequests() {
      const originalFetch = window.fetch;
      
      window.fetch = function(...args) {
        const [url, options] = args;
        
        if (url.includes('<?= parse_url($webhookUrl, PHP_URL_PATH) ?>')) {
          if (options && options.body) {
            try {
              const data = JSON.parse(options.body);
              
              // Ensure strict format with model selection
              const formattedData = {
                sessionId: window.currentSessionId,
                action: data.action || "sendMessage",
                chatInput: data.chatInput || data.message || "",
                model: window.currentModel
              };
              
              options.body = JSON.stringify(formattedData);
              
              console.log('📤 Webhook payload:', formattedData);
              
            } catch (error) {
              console.warn('⚠️ Could not format webhook payload:', error);
            }
          }
        }
        
        return originalFetch.apply(this, args);
      };
    }

    // Model selection change handler with new session creation
    window.changeModel = async function() {
      const modelSelect = document.getElementById('modelSelect');
      const newModel = modelSelect.value;
      const modelName = modelSelect.options[modelSelect.selectedIndex].text;
      
      console.log('🔄 Changing model to:', newModel);
      
      // Add visual feedback
      const currentModelElement = document.getElementById('currentModelName');
      currentModelElement.classList.add('model-changing');
      
      try {
        // Send model change request to create new session
        const response = await fetch(window.location.href, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `selected_model=${encodeURIComponent(newModel)}`
        });
        
        if (response.ok) {
          const result = await response.json();
          
          if (result.status === 'success') {
            // Update session and model
            window.currentSessionId = result.newSessionId;
            window.currentModel = result.selectedModel;
            
            // Clear chat history
            Object.keys(localStorage).forEach(key => {
              if (key.includes('n8n-chat')) {
                localStorage.removeItem(key);
              }
            });
            
            // Update UI
            document.getElementById('currentModelName').textContent = modelName;
            document.querySelector('.session-id').textContent = window.currentSessionId;
            
            console.log('✅ Model changed and new session created');
            console.log('📱 New Session ID:', window.currentSessionId);
            
            // Reinitialize chat with new session
            if (window.chatInstance) {
              // Destroy existing instance
              const chatContainer = document.getElementById('n8n-chat');
              chatContainer.innerHTML = '<div class="loading-container"><div class="loading-spinner"></div><div>Switching model...</div></div>';
              
              // Reinitialize after a short delay
              setTimeout(() => {
                initializeChat();
              }, 500);
            }
          }
        } else {
          throw new Error('Failed to change model');
        }
        
      } catch (error) {
        console.error('❌ Failed to change model:', error);
        // Revert dropdown selection
        modelSelect.value = window.currentModel;
        alert('Failed to change model. Please try again.');
      } finally {
        // Remove visual feedback
        currentModelElement.classList.remove('model-changing');
      }
    };

    // Mobile sidebar functions
    window.toggleSidebar = function() {
      const sidebar = document.getElementById('sidebar');
      const overlay = document.getElementById('sidebarOverlay');
      const menuBtn = document.getElementById('mobileMenuBtn');
      
      sidebar.classList.toggle('open');
      overlay.classList.toggle('active');
      
      // Update menu button icon
      const icon = menuBtn.querySelector('i');
      if (sidebar.classList.contains('open')) {
        icon.className = 'fas fa-times';
        document.body.style.overflow = 'hidden';
      } else {
        icon.className = 'fas fa-bars';
        document.body.style.overflow = '';
      }
    };

    window.closeSidebar = function() {
      const sidebar = document.getElementById('sidebar');
      const overlay = document.getElementById('sidebarOverlay');
      const menuBtn = document.getElementById('mobileMenuBtn');
      
      sidebar.classList.remove('open');
      overlay.classList.remove('active');
      document.body.style.overflow = '';
      
      // Reset menu button icon
      const icon = menuBtn.querySelector('i');
      icon.className = 'fas fa-bars';
    };

    // Start new chat function
    window.startNewChat = function() {
      if (confirm('🔄 Start a new conversation?\n\nThis will clear your current chat history and create a fresh session.')) {
        // Clear all chat-related localStorage
        Object.keys(localStorage).forEach(key => {
          if (key.includes('n8n-chat') || key.includes(window.currentSessionId)) {
            localStorage.removeItem(key);
          }
        });
        
        // Request new session
        fetch(window.location.href, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'action=new_session'
        }).then(() => {
          console.log('🔄 Starting new session...');
          window.location.reload();
        }).catch(error => {
          console.error('❌ Failed to start new session:', error);
        });
      }
    };

    // Handle escape key to close sidebar on mobile
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape') {
        closeSidebar();
      }
    });

    // Handle window resize
    window.addEventListener('resize', function() {
      if (window.innerWidth > 768) {
        closeSidebar();
      }
    });

    // Prevent zoom on iOS when focusing input
    document.addEventListener('touchstart', function() {}, { passive: true });

    // Handle viewport changes on mobile (address bar show/hide)
    let vh = window.innerHeight * 0.01;
    document.documentElement.style.setProperty('--vh', `${vh}px`);

    window.addEventListener('resize', () => {
      let vh = window.innerHeight * 0.01;
      document.documentElement.style.setProperty('--vh', `${vh}px`);
    });

    // Initialize chat when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
      console.log('🎯 DOM loaded, initializing chat...');
      setTimeout(initializeChat, 100);
    });

    // Debug information
    console.log('🔧 Chat Configuration:');
    console.log('- Webhook URL:', "<?= $webhookUrl ?>");
    console.log('- Session ID:', window.currentSessionId);
    console.log('- Current Model:', window.currentModel);
    console.log('- Available Models:', <?= json_encode($availableModels) ?>);
    console.log('- Streaming:', <?= $enableStreaming ? 'true' : 'false' ?>);
  </script>

  <?php
  // Handle AJAX requests
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'new_session') {
      unset($_SESSION['chat_session_id']);
      unset($_SESSION['selected_model']);
      http_response_code(200);
      exit('OK');
    }
  }
  ?>
</body>
</html>