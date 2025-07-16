<?php
session_start();

// Simple password protection
$password = '<PASSWORD>'; // Change this!
$config_file = 'config.json';

if ($_POST['password'] ?? '' === $password) {
    $_SESSION['authenticated'] = true;
}

if ($_POST['logout'] ?? false) {
    $_SESSION['authenticated'] = false;
}

if (!($_SESSION['authenticated'] ?? false)) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Admin Login</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100">
        <div class="min-h-screen flex items-center justify-center">
            <form method="post" class="bg-white p-8 rounded-lg shadow-md">
                <h2 class="text-2xl mb-4">Admin Login</h2>
                <input type="password" name="password" placeholder="Password" 
                       class="w-full p-3 border rounded mb-4" required>
                <button type="submit" class="w-full bg-black text-white p-3 rounded">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Handle form submission
if ($_POST['save'] ?? false) {
    $config = [
        'site' => [
            'title' => $_POST['site_title'] ?? '',
            'organization' => $_POST['organization'] ?? '',
            'department' => $_POST['department'] ?? '',
            'pageTitle' => $_POST['page_title'] ?? '',
            'pageSubtitle' => $_POST['page_subtitle'] ?? ''
        ],
        'dataSource' => [
            'name' => $_POST['data_source_name'] ?? '',
            'description' => $_POST['data_source_description'] ?? ''
        ],
        'academicNote' => $_POST['academic_note'] ?? '',
        'footer' => [
            'organization' => $_POST['footer_organization'] ?? '',
            'department' => $_POST['footer_department'] ?? ''
        ],
        'technical' => [
            'sheetId' => $_POST['sheet_id'] ?? '',
            'gid' => $_POST['gid'] ?? ''
        ]
    ];
    
    file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
    $success = "Configuration saved successfully!";
}

// Load current config
$config = json_decode(file_get_contents($config_file), true);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Content Editor</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto py-8 px-4">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold">Content Editor</h1>
                <form method="post" class="inline">
                    <input type="hidden" name="logout" value="1">
                    <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded">Logout</button>
                </form>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <form method="post" class="space-y-6">
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-xl font-semibold mb-4">Site Information</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Page Title</label>
                                <input type="text" name="site_title" value="<?= htmlspecialchars($config['site']['title']) ?>" 
                                       class="w-full p-3 border rounded">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Organization</label>
                                <input type="text" name="organization" value="<?= htmlspecialchars($config['site']['organization']) ?>" 
                                       class="w-full p-3 border rounded">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Department</label>
                                <input type="text" name="department" value="<?= htmlspecialchars($config['site']['department']) ?>" 
                                       class="w-full p-3 border rounded">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Main Heading</label>
                                <input type="text" name="page_title" value="<?= htmlspecialchars($config['site']['pageTitle']) ?>" 
                                       class="w-full p-3 border rounded">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Subtitle</label>
                                <textarea name="page_subtitle" class="w-full p-3 border rounded h-20"><?= htmlspecialchars($config['site']['pageSubtitle']) ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-semibold mb-4">Data Source</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Source Name</label>
                                <input type="text" name="data_source_name" value="<?= htmlspecialchars($config['dataSource']['name']) ?>" 
                                       class="w-full p-3 border rounded">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Description</label>
                                <textarea name="data_source_description" class="w-full p-3 border rounded h-24"><?= htmlspecialchars($config['dataSource']['description']) ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Academic Note</label>
                                <textarea name="academic_note" class="w-full p-3 border rounded h-24"><?= htmlspecialchars($config['academicNote']) ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-xl font-semibold mb-4">Footer</h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Footer Organization</label>
                            <input type="text" name="footer_organization" value="<?= htmlspecialchars($config['footer']['organization']) ?>" 
                                   class="w-full p-3 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Footer Department</label>
                            <input type="text" name="footer_department" value="<?= htmlspecialchars($config['footer']['department']) ?>" 
                                   class="w-full p-3 border rounded">
                        </div>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-xl font-semibold mb-4">Technical Settings</h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Google Sheet ID</label>
                            <input type="text" name="sheet_id" value="<?= htmlspecialchars($config['technical']['sheetId']) ?>" 
                                   class="w-full p-3 border rounded font-mono text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Sheet GID</label>
                            <input type="text" name="gid" value="<?= htmlspecialchars($config['technical']['gid']) ?>" 
                                   class="w-full p-3 border rounded font-mono text-sm">
                        </div>
                    </div>
                </div>
                
                <div class="pt-6">
                    <button type="submit" name="save" value="1" 
                            class="bg-black text-white px-8 py-3 rounded-lg hover:bg-gray-800">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
