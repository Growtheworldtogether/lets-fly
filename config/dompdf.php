<?php

// Only what differs from the PDF package's own settings (they are merged in).
return [
    // The PDF tool looks for <app>/public by itself. On a host where the website files sit in public_html next to
    // the app folder that folder does not exist, and every PDF download failed with "Cannot resolve public path".
    'public_path' => public_path(),
];
