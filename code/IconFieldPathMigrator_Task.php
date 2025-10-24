<?php

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Versioned\Versioned;
use Symfony\Component\Console\Input\InputInterface;
use SilverStripe\PolyExecution\PolyOutput;

class IconFieldPathMigrator_BuildTask extends BuildTask
{
    /**
     * 1. Update IconField fields to use new folder path, eg `IconField::create('SocialIcon', 'Icon', 'SiteIcons')`
     * 1. Set up new folder in assets/SiteIcons in the CMS
     * 2. Copy the icons into the folder
     * 3. Publish the icon files
     * 4. Run this task - include params
     */

    protected string $title = 'Update icon file paths to assets folder';
    protected bool $enabled = true;
    
    public function run(InputInterface $input, PolyOutput $output): int
    {
        // Get query parameters (supports both CLI & HTTP dev/tasks)
        $vars = $_GET ?? [];

        if (!isset($vars['classname']) || !isset($vars['field'])) {
            $output->writeLine('Pass both class and field in the query string, eg ?classname=Skeletor\DataObjects\SummaryPanel&field=SVGIcon');
            $output->writeLine('If new folder is not "SiteIcons", pass new-path in the query string, eg &new-path=NewFolder');
            $output->writeLine('Classname must include namespace');
            return 1;
        }

        $classname = $vars['classname'];
        $iconField = $vars['field'];
        $folderPath = isset($vars['new-path']) ? 'assets/' . $vars['new-path'] : 'assets/SiteIcons';

        if (!ClassInfo::exists($classname)) {
            $output->writeLine("Class {$classname} does not exist. Make sure to include namespace.");
            return 1;
        }

        $objects = $classname::get();
        $schema = DataObject::getSchema();

        if (!$schema->classHasTable($classname)) {
            $output->writeLine("Class {$classname} does not have a database table.");
            return 1;
        }

        $tableName = Convert::raw2sql($schema->tableName($classname));
        $iconCol = Convert::raw2sql($iconField);

        if (!$objects || !$tableName) {
            $output->writeLine("No objects found for class {$classname}");
            return 0;
        }

        foreach ($objects as $object) {
            $originIconPath = $object->$iconField;

            if ($originIconPath) {
                $originIconName = basename($originIconPath);
                $newIconPath = $folderPath . '/' . $originIconName;

                $output->writeLine("Updating {$object->Title}");
                $output->writeLine("Origin: {$originIconPath}");
                $output->writeLine("New path: {$newIconPath}");

                DB::prepared_query(
                    "UPDATE {$tableName} SET {$iconCol} = ? WHERE ID = ?",
                    [$newIconPath, $object->ID]
                );
                $output->writeLine("{$tableName} updated");

                if ($object->hasExtension(Versioned::class)) {
                    $tableNameVersioned = $tableName . '_Versions';
                    DB::prepared_query(
                        "UPDATE {$tableNameVersioned} SET {$iconCol} = ? WHERE RecordID = ?",
                        [$newIconPath, $object->ID]
                    );
                    $output->writeLine("{$tableNameVersioned} updated");

                    if ($object->isPublished()) {
                        $tableNameLive = $tableName . '_Live';
                        DB::prepared_query(
                            "UPDATE {$tableNameLive} SET {$iconCol} = ? WHERE ID = ?",
                            [$newIconPath, $object->ID]
                        );
                        $output->writeLine("{$tableNameLive} updated");
                    }
                }

                $output->writeLine("Panel icon updated");
            } else {
                $output->writeLine("{$object->Title} - No icon, skipped");
            }

            $output->writeLine("-------");
        }

        return 0;
    }
}