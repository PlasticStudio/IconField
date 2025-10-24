<?php

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Versioned\Versioned;

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

    public function run($request): void
    {
        $vars = $request->getVars();

        if (!isset($vars['classname']) || !isset($vars['field'])) {
            echo 'Pass both class and field in the query string, eg ?classname=Skeletor\DataObjects\SummaryPanel&field=SVGIcon' . '<br>';
            echo 'If new folder is not \'SiteIcons\', pass new-path in the query string, eg &new-path=NewFolder' . '<br>';
            echo 'Classname needs to include namespacing' . '<br>';
            return;
        }

        $classname = $vars['classname'];
        $iconField = $vars['field'];

        // check for folder path
        if ( isset($vars['new-path']) ) {
            $folderPath = 'assets/' . $vars['new-path'];
        } else {
            $folderPath = 'assets/SiteIcons';
        }

        // check if site is namespaced
        if (!ClassInfo::exists($classname)) {
            die("Class $classname does not exist. Make sure to add the namespacing.");
        }

        $objects = $classname::get();
        $schema = DataObject::getSchema();

        if (!$schema->classHasTable($classname)) {
            die("Class $classname does not have a table.");
        }

        $tableName = Convert::raw2sql($schema->tableName($classname));// Sanitize column name
        $iconCol = Convert::raw2sql($iconField); // Sanitize column name

        if ($objects && $tableName) {
            
            foreach ($objects as $object) {

                $originIconPath = $object->$iconField;

                // if there is an icon
                if ($originIconPath) {
                    
                    $originIconName = basename($originIconPath);
                    $newIconPath = $folderPath . '/' . $originIconName;

                    echo "Updating {$object->Title}<br>";
                    echo "Origin: {$originIconPath}<br>";
                    echo "New path: {$newIconPath}<br>";

                    DB::prepared_query("UPDATE {$tableName} SET {$iconCol} = ? WHERE ID = ?", [$newIconPath, $object->ID]);
                    
                    echo "{$tableName} updated<br>";

                    // Handle versioned objects
                    if ($object->hasExtension(Versioned::class)) {

                        $tableNameVersioned = $tableName.'_Versions';
                        DB::prepared_query("UPDATE {$tableNameVersioned} SET {$iconCol} = ? WHERE RecordID = ?", [$newIconPath, $object->ID]);
                        
                        echo "{$tableNameVersioned} updated<br>";

                        if ($object->isPublished()) {
                            $tableNameLive = $tableName.'_Live';
                            DB::prepared_query("UPDATE {$tableNameLive} SET {$iconCol} = ? WHERE ID = ?", [$newIconPath, $object->ID]);
                            
                            echo "{$tableNameLive} updated<br>";
                        }
                    }


                    echo "Panel icon updated<br>";
                } else {
                    echo "{$object->Title}<br>No icon - skipped<br>";
                }

                echo '<br />-------<br />';
            }
        } else {
            echo "No objects found for class {$classname}<br>";
        }
    }
}