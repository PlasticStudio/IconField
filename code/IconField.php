<?php

namespace PlasticStudio\IconField;

use DirectoryIterator;
use SilverStripe\Core\Path;
use SilverStripe\Dev\Debug;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\Forms\FormField;
use SilverStripe\View\Requirements;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Core\Manifest\ModuleResourceLoader;

class IconField extends OptionsetField
{
    private static $folder_name;

    /**
     * Construct the field
     *
     * @param string $name
     * @param null|string $title
     * @param string $sourceFolder (legacy arg, for backwards-compatibility)
     *
     * @return array icons to provide as source array for the field
     **/
    public function __construct($name, $title = null, $sourceFolder = null)
    {
        if ($sourceFolder) {
            // TODO: set deprecation notice
            // eg, "IconField no longer accepts Source Folder as a third parameter. Please use IconField->setFolderName() instead"
            $this->setFolderName($sourceFolder);
        }
        parent::__construct($name, $title, []);
    }

    /**
     * Gets the icons folder name
     *
     * @return string
     */
    public function getFolderName()
    {
        if (is_null(self::$folder_name)) {
            self::$folder_name = Config::inst()->get('PlasticStudio\IconField', 'icons_directory');
        }
        return self::$folder_name;
    }

    public function setFolderName($folder_name)
    {
        self::$folder_name = $folder_name;
        return $this;
    }

    public function setSourceIcons()
    {
        $icons = [];
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg'];
        $relative_folder_path = ModuleResourceLoader::singleton()->resolveURL($this->getFolderName());
        $absolute_folder_path = Path::join(Director::publicFolder(), $relative_folder_path);

        // Scan each directory for files
        if (file_exists($absolute_folder_path)) {
            $directory = new DirectoryIterator($absolute_folder_path);
            foreach ($directory as $fileinfo) {
                if ($fileinfo->isFile()) {
                    $extension = strtolower(pathinfo($fileinfo->getFilename(), PATHINFO_EXTENSION));

                    // Only add to our available icons if it's an extension we're after
                    if (in_array($extension, $extensions)) {
                        // $value = Controller::join_links($sourceFolder, $fileinfo->getFilename());
                        $value = Path::join($relative_folder_path, $fileinfo->getFilename());
                        $title = $fileinfo->getFilename();
                        $icons[$value] = $title;
                    }
                }
            }
        }

        $this->source = $icons;
        return $this;
    }

    /**
     * Build the field
     *
     * @return HTML
     **/
    public function Field($properties = [])
    {
        Requirements::css('plasticstudio/iconfield:css/IconField.css');
        $this->setSourceIcons();
        $source = $this->getSource();
        $options = [];

        // Add a clear option
        $options[] = ArrayData::create([
            'ID' => 'none',
            'Name' => $this->name,
            'Value' => '',
            'Title' => '',
            'isChecked' => (!$this->value || $this->value == '')
        ]);

        if ($source) {
            foreach ($source as $value => $title) {
                $itemID = $this->ID() . '_' . preg_replace('/[^a-zA-Z0-9]/', '', $value);
                $options[] = ArrayData::create([
                    'ID' => $itemID,
                    'Name' => $this->name,
                    'Value' => $value,
                    'Title' => $title,
                    'isChecked' => $value == $this->value
                ]);
            }
        }

        $properties = array_merge($properties, [
            'Options' => ArrayList::create($options)
        ]);

        $this->setTemplate('IconField');

        // return $this->customise($properties)->renderWith('IconField');
        return FormField::Field($properties);
    }

    /**
     * Handle extra classes
     **/
    public function extraClass()
    {
        $classes = ['field', 'IconField', parent::extraClass()];

        if (($key = array_search('icon', $classes)) !== false) {
            unset($classes[$key]);
        }

        return implode(' ', $classes);
    }
}
