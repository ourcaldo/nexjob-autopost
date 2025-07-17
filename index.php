<?php
/**
 * Nexjob Autopost WordPress Plugin
 * 
 * This is a WordPress plugin that must be installed in a WordPress environment.
 * It is not a standalone web application.
 * 
 * To use this plugin:
 * 1. Install WordPress on your server
 * 2. Upload this plugin folder to wp-content/plugins/
 * 3. Activate the plugin through the WordPress admin
 * 4. Configure the plugin settings in the WordPress admin dashboard
 */

// If this is being accessed directly, show information
?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Nexjob Autopost WordPress Plugin</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 800px;
                margin: 50px auto;
                padding: 20px;
                background-color: #f5f5f5;
            }
            .container {
                background: white;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            h1 {
                color: #333;
                border-bottom: 2px solid #0073aa;
                padding-bottom: 10px;
            }
            .highlight {
                background-color: #e7f3ff;
                padding: 15px;
                border-left: 4px solid #0073aa;
                margin: 20px 0;
            }
            .step {
                margin: 10px 0;
                padding: 10px;
                background-color: #f9f9f9;
                border-radius: 5px;
            }
            .features {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 15px;
                margin-top: 20px;
            }
            .feature {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                border-left: 3px solid #28a745;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🚀 Nexjob Autopost WordPress Plugin</h1>
            
            <div class="highlight">
                <strong>Important:</strong> This is a WordPress plugin that must be installed in a WordPress environment. It cannot run as a standalone application.
            </div>

            <h2>📋 Installation Instructions</h2>
            <div class="step">
                <strong>Step 1:</strong> Install WordPress on your server
            </div>
            <div class="step">
                <strong>Step 2:</strong> Upload this plugin folder to <code>wp-content/plugins/nexjob-autopost/</code>
            </div>
            <div class="step">
                <strong>Step 3:</strong> Activate the plugin through the WordPress admin dashboard
            </div>
            <div class="step">
                <strong>Step 4:</strong> Configure the plugin settings in WordPress admin under "Nexjob Autopost"
            </div>

            <h2>✨ Plugin Features</h2>
            <div class="features">
                <div class="feature">
                    <h3>📊 Statistics Dashboard</h3>
                    <p>View API request statistics and latest 50 logs</p>
                </div>
                <div class="feature">
                    <h3>📋 Logs Management</h3>
                    <p>Dedicated page for viewing and managing all API request logs</p>
                </div>
                <div class="feature">
                    <h3>🔄 Bulk Actions</h3>
                    <p>Separate page for bulk operations and retry failed requests</p>
                </div>
                <div class="feature">
                    <h3>⚙️ Configuration Management</h3>
                    <p>Manage multiple autopost configurations with different settings</p>
                </div>
                <div class="feature">
                    <h3>🛠️ Settings Page</h3>
                    <p>Configure API settings, retry options, and email notifications</p>
                </div>
                <div class="feature">
                    <h3>🔗 API Integration</h3>
                    <p>Automatically sends job postings to NexPocket API with proper format</p>
                </div>
            </div>

            <h2>🔧 Technical Details</h2>
            <p>This plugin automatically sends POST requests to the NexPocket API whenever a new post is created in the "lowongan-kerja" custom post type. It features:</p>
            <ul>
                <li>Automatic retry mechanism for failed API requests</li>
                <li>Email notifications for failures and successes</li>
                <li>Comprehensive logging system</li>
                <li>Multiple configuration support</li>
                <li>Clean, separated admin interface</li>
            </ul>

            <p><strong>Version:</strong> 1.0.0</p>
            <p><strong>Requires:</strong> WordPress 5.0 or higher</p>
            <p><strong>Tested up to:</strong> WordPress 6.4</p>
        </div>
    </body>
    </html>
    <?php
?>