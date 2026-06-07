<?php
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('app'));
foreach($files as $file) {
    if($file->getExtension() === 'php') {
        $c = file_get_contents($file);
        
        // Remove withoutVerifying()->
        $c2 = str_replace('withoutVerifying()->', '', $c);
        
        // Replace private string $projectId = 'doremi-admin';
        $c3 = preg_replace('/(private\s+string\s+\$projectId\s*=\s*)[\'"]doremi-admin[\'"]/', '$1env(\'FIREBASE_PROJECT_ID\', \'doremi-admin\')', $c2);
        
        // Replace $projectId = 'doremi-admin';
        $c4 = preg_replace('/(\$projectId\s*=\s*)[\'"]doremi-admin[\'"]/', '$1env(\'FIREBASE_PROJECT_ID\', \'doremi-admin\')', $c3);
        
        if($c !== $c4) {
            file_put_contents($file, $c4);
            echo "Updated " . $file->getPathname() . "\n";
        }
    }
}
