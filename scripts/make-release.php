<?php

/**
 * Packs the site into lets-fly-release.zip (or, with --public-html, lets-fly-release-public_html.zip).
 * Run it from the project folder after "composer install --no-dev --optimize-autoloader".
 * The GitHub job "Build release zip" does both and publishes the zips on the Releases page.
 *
 * Left out: .env, local databases, logs, sessions, cached views, uploaded files, tests, docs and tools.
 * A live site keeps its own .env, database and uploads, so installing a package over it is safe.
 *
 * Layouts:
 *   standard      the zip root is the app; the domain points at public/
 *   --public-html two folders: public_html (the public files) and app (everything else, out of the web's reach)
 */
if (PHP_SAPI !== 'cli') {
    exit(1);
}
if (! class_exists(ZipArchive::class)) {
    fwrite(STDERR, "The PHP zip extension is off. Turn on extension=zip in php.ini and try again.\n");
    exit(1);
}

$root = realpath(__DIR__.'/..');
$publicHtml = in_array('--public-html', $argv, true);
$out = $root.DIRECTORY_SEPARATOR.($publicHtml ? 'lets-fly-release-public_html.zip' : 'lets-fly-release.zip');
$place = function (string $rel) use ($publicHtml): string {
    if (! $publicHtml) {
        return $rel;
    }

    return $rel === 'public' ? 'public_html' : (str_starts_with($rel, 'public/') ? 'public_html/'.substr($rel, 7) : 'app/'.$rel);
};

if (! is_file($root.'/vendor/autoload.php')) {
    fwrite(STDERR, "Missing vendor/. Run: composer install --no-dev --optimize-autoloader\n");
    exit(1);
}
if (is_dir($root.'/vendor/pestphp')) {
    fwrite(STDERR, "vendor/ still has the testing tools. Run: composer install --no-dev --optimize-autoloader\n");
    exit(1);
}

$skipDirs = [
    '.git', '.github', '.idea', '.vscode', '.claude', 'node_modules', 'tests', 'docs', 'scripts',
    'storage/app/private', 'storage/app/public', 'storage/app/updates', 'storage/app/update-backups',
    'storage/framework/cache/data', 'storage/framework/testing', 'public/storage', 'public/hot',
];
$skipFiles = [
    '#^\.env$#', '#^\.env\.(?!example$)#', '#\.sqlite(-journal)?$#', '#^lets-fly-release(-public_html)?\.zip$#',
    '#^storage/logs/.+\.log$#', '#^storage/framework/(sessions|views)/(?!\.gitignore$)#',
    '#^storage/app/(installed\.lock|install-key\.txt)$#', '#^bootstrap/cache/(?!\.gitignore$).+\.php$#',
    '#(^|/)(Thumbs\.db|\.DS_Store)$#', '#^\.phpunit#', '#^phpunit\.xml$#', '#^version\.json$#',
];

// The version this package is, read by Admin, System to offer and install updates.
$version = json_encode([
    'tag' => getenv('RELEASE_TAG') ?: 'local-'.gmdate('Ymd-Hi'),
    'commit' => substr((string) (getenv('GITHUB_SHA') ?: trim((string) @shell_exec('git -C '.escapeshellarg($root).' rev-parse HEAD'))), 0, 7),
    'built_at' => gmdate('c'),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";

@unlink($out);
$zip = new ZipArchive;
if ($zip->open($out, ZipArchive::CREATE) !== true) {
    fwrite(STDERR, "Cannot write {$out}\n");
    exit(1);
}

$files = 0;
$it = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        function (SplFileInfo $f) use ($root, $skipDirs) {
            $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($root) + 1));

            return ! ($f->isDir() && (in_array($rel, $skipDirs, true) || in_array($f->getFilename(), ['.git', '.github'], true))) && ! $f->isLink();
        }
    ),
    RecursiveIteratorIterator::SELF_FIRST
);
foreach ($it as $f) {
    $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($root) + 1));
    if ($f->isDir()) {
        $zip->addEmptyDir($place($rel));

        continue;
    }
    foreach ($skipFiles as $pattern) {
        if (preg_match($pattern, $rel)) {
            continue 2;
        }
    }
    $zip->addFile($f->getPathname(), $place($rel));
    $files++;
}
$zip->addFromString($place('version.json'), $version);

// Empty folders the site needs to write into.
foreach (['storage/app/private', 'storage/app/public', 'storage/framework/cache/data', 'storage/logs', 'storage/framework/sessions', 'storage/framework/views', 'bootstrap/cache'] as $dir) {
    $zip->addEmptyDir($place($dir));
}
$zip->close();

printf("Made %s (%d files, %.1f MB)\n", basename($out), $files, filesize($out) / 1048576);
