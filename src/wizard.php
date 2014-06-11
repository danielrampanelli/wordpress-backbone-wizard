<?php

require_once(__DIR__.'/../vendor/autoload.php');

$arguments = new cli\Arguments($_SERVER['argv']);

$arguments->addOption(array('theme', 't'), array(
    'description' => 'Full name of the theme'
));

$arguments->addOption(array('slug', 's'), array(
    'description' => 'Slug for the theme (will be lowercased and stripped of spaces)'
));

$arguments->addFlag('confirm', 'Do not ask for confirmation before proceeding');

$arguments->addFlag(array('help', 'h'), 'Show the help screen');

$arguments->parse();

if ($arguments['help']) {
    die($arguments->getHelpScreen()."\n\n");
}

$theme = $arguments['theme'];
if (empty($theme)) {
    $theme = cli\prompt('Full name of the theme?');
}

$slug = $arguments['slug'];
if (empty($slug)) {
    $slug = cli\prompt('Slug for the theme (empty for using the name)?');
}

$theme = trim($theme);
$slug = trim($slug);

if (empty($theme) && empty($slug)) {
    die('Invalid values for "Theme" and "Slug", I need at least one of them.');
}

if (empty($theme)) {
    $theme = $slug;
}

if (empty($slug)) {
    $slug = $theme;
}

$slug = strtolower(trim($slug));
$slug = preg_replace('/[\s\/]/', '-', $slug);
$slug = preg_replace('/-{2,}/', '-', $slug);
$slug = trim($slug, '-');

printf("Name: %s\n", $theme);
printf("Theme: %s\n", $slug);

if (!$arguments['confirm']) {
    $choice = cli\choose('Confirm installing the backbone project');
    if ($choice == 'n') {
        die("Operation aborted, no changes performed.\n");
    }
}

printf("%s\n", str_repeat('-', 10));

print("Checking out project ...\n");

shell_exec('wget -q -O - https://github.com/neuralquery/wordpress-backbone/archive/master.tar.gz | tar zx --strip 1');
shell_exec('rm README.md');

print("Setting up project ...\n");

shell_exec("sed -i '' -e 's/%THEME_NAME%/{$theme}/g' $(find . -type f)");
shell_exec("sed -i '' -e 's/%THEME_SLUG%/{$slug}/g' $(find . -type f)");
shell_exec("for f in $(find . -name '%THEME_SLUG%'); do mv \$f \"`dirname \$f`/{$slug}\"; done");

file_put_contents('./wp-config.php',
    str_replace('// %SECRET_KEYS%',
        trim(file_get_contents('https://api.wordpress.org/secret-key/1.1/salt/')),
        file_get_contents('./wp-config.php')
    )
);

print("Project created.\n");

?>