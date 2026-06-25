<?php
$file = 'app/Notifications/InAppNotification.php';
$lines = file($file);
$newLines = array_merge(array_slice($lines, 0, 201), array_slice($lines, 451));
file_put_contents($file, implode("", $newLines));

// Also delete WhatsApp files if they exist
$filesToDelete = [
    'app/Notifications/Messages/WhatsAppMessage.php',
    'app/Channels/WhatsAppChannel.php',
    'app/Services/WhatsAppService.php',
    'config/whatsapp.php'
];

foreach ($filesToDelete as $f) {
    if (file_exists($f)) {
        unlink($f);
        echo "Deleted $f\n";
    }
}
echo "Done.\n";
