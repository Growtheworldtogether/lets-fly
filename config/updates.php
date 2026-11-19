<?php

return [
    // Where Admin, System looks for new versions: a GitHub repository whose Releases page holds the zips made by the
    // "Build release zip" job. A token (read access to contents) is only needed if the repository is private.
    'repo' => env('UPDATE_REPO', 'Growtheworldtogether/CHANGE-ME'),
    'token' => env('UPDATE_TOKEN'),
];
