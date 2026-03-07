import React from 'react';
import { Copy, Check, Download, FileCode, Database as DbIcon, Settings } from 'lucide-react';

interface Props {
  config: any;
}

export function PHPCodeViewer({ config }: Props) {
  const [copied, setCopied] = React.useState<string | null>(null);

  const copyToClipboard = (text: string, id: string) => {
    navigator.clipboard.writeText(text);
    setCopied(id);
    setTimeout(() => setCopied(null), 2000);
  };

  const phpInstallerCode = `<?php
/**
 * Payhub Professional Installer
 * Stage 1: Requirements Check
 * Stage 2: Database Configuration
 * Stage 3: Admin Setup
 * Stage 4: Success
 */

session_start();

$requirements = [
    'php_version' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'pdo' => extension_loaded('pdo_mysql'),
    'bcmath' => extension_loaded('bcmath'),
    'mbstring' => extension_loaded('mbstring'),
    'openssl' => extension_loaded('openssl'),
];

$stage = isset($_GET['stage']) ? (int)$_GET['stage'] : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($stage === 2) {
        // Handle Database Connection
        $host = $_POST['db_host'];
        $name = $_POST['db_name'];
        $user = $_POST['db_user'];
        $pass = $_POST['db_pass'];

        try {
            $pdo = new PDO("mysql:host=$host;dbname=$name", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Save config to file
            $configContent = "<?php\\n";
            $configContent .= "define('DB_HOST', '$host');\\n";
            $configContent .= "define('DB_NAME', '$name');\\n";
            $configContent .= "define('DB_USER', '$user');\\n";
            $configContent .= "define('DB_PASS', '$pass');\\n";
            
            file_put_contents('../config.php', $configContent);
            
            header("Location: ?stage=3");
            exit;
        } catch (PDOException $e) {
            $error = "Connection failed: " . $e->getMessage();
        }
    }
    
    if ($stage === 3) {
        // Handle Admin Setup
        $email = $_POST['admin_email'];
        $password = password_hash($_POST['admin_pass'], PASSWORD_DEFAULT);
        
        // Insert admin into database (Assuming table exists)
        require '../config.php';
        $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
        $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'admin')");
        $stmt->execute([$email, $password]);
        
        header("Location: ?stage=4");
        exit;
    }
}

// Simple UI rendering logic would go here...
echo "<h1>Payhub Installer - Stage $stage</h1>";
?>`;

  const configPhpCode = `<?php
/**
 * Database Configuration File
 */

define('DB_HOST', '${config.db_host || 'localhost'}');
define('DB_NAME', '${config.db_name || 'payhub_db'}');
define('DB_USER', '${config.db_user || 'root'}');
define('DB_PASS', '${config.db_pass || ''}');

// Establish Connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}
?>`;

  const sqlSchema = `-- Payhub Database Schema
CREATE TABLE IF NOT EXISTS \`users\` (
  \`id\` int(11) NOT NULL AUTO_INCREMENT,
  \`email\` varchar(255) NOT NULL,
  \`password\` varchar(255) NOT NULL,
  \`role\` enum('user','admin') DEFAULT 'user',
  \`created_at\` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (\`id\`),
  UNIQUE KEY \`email\` (\`email\`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS \`transactions\` (
  \`id\` int(11) NOT NULL AUTO_INCREMENT,
  \`user_id\` int(11) NOT NULL,
  \`amount\` decimal(10,2) NOT NULL,
  \`status\` enum('pending','completed','failed') DEFAULT 'pending',
  \`created_at\` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (\`id\`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;`;

  const files = [
    { id: 'installer', name: 'install/index.php', code: phpInstallerCode, icon: FileCode },
    { id: 'config', name: 'config.php', code: configPhpCode, icon: Settings },
    { id: 'sql', name: 'database.sql', code: sqlSchema, icon: DbIcon },
  ];

  return (
    <div className="flex flex-col h-full">
      <div className="p-6 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
        <div>
          <h3 className="font-bold text-slate-800">PHP Source Files</h3>
          <p className="text-xs text-slate-500">Copy these files to your cPanel file manager.</p>
        </div>
        <button className="flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-xs font-bold rounded-lg hover:bg-slate-800 transition-all">
          <Download size={14} />
          Download All (.zip)
        </button>
      </div>

      <div className="flex-1 overflow-y-auto p-6 space-y-8 max-h-[600px]">
        {files.map((file) => {
          const Icon = file.icon;
          return (
            <div key={file.id} className="space-y-3">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <Icon size={16} className="text-slate-400" />
                  <span className="text-sm font-bold text-slate-700">{file.name}</span>
                </div>
                <button
                  onClick={() => copyToClipboard(file.code, file.id)}
                  className="flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-all"
                >
                  {copied === file.id ? <Check size={14} /> : <Copy size={14} />}
                  {copied === file.id ? 'Copied!' : 'Copy Code'}
                </button>
              </div>
              <div className="relative group">
                <pre className="p-5 bg-slate-900 text-slate-300 rounded-2xl text-xs font-mono overflow-x-auto border border-slate-800 leading-relaxed">
                  {file.code}
                </pre>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}
