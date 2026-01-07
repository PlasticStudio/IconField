<?php

namespace PlasticStudio\IconField;

use DirectoryIterator;
use SilverStripe\Core\Path;
use SilverStripe\Assets\Folder;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\ArrayData;
use SilverStripe\Forms\FormField;
use SilverStripe\View\Requirements;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\UploadReceiver;
use SilverStripe\Core\Manifest\ModuleResourceLoader;

class IconField extends OptionsetField
{
    use UploadReceiver;

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
            $this->setFolderName($sourceFolder);
        }

        parent::__construct($name, $title, []);
        
        $this->setSourceIcons();
    }

    /**
     * Gets the icons folder name
     *
     * @return string
     */
    public function getFolderName()
    {
        if (is_null(self::$folder_name)) {
            self::$folder_name = Config::inst()->get(IconField::class, 'icons_folder_name');
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

        $relative_folder_path = Folder::find_or_make($this->getFolderName())->Filename;
        $absolute_folder_path = Path::join(
            ASSETS_PATH,
            $relative_folder_path,
        );

        // Scan each directory for files
        if (file_exists($absolute_folder_path)) {
            $directory = new DirectoryIterator($absolute_folder_path);
            foreach ($directory as $fileinfo) {
                if ($fileinfo->isFile()) {
                    $extension = strtolower(pathinfo($fileinfo->getFilename(), PATHINFO_EXTENSION));

                    // Only add to our available icons if it's an extension we're after
                    if (in_array($extension, $extensions)) {
                        $value = Path::join('assets', $relative_folder_path, $fileinfo->getFilename());
                        $title = $fileinfo->getFilename();

                        // don't include resized images in the list (for non-svg files)
                        if (!str_contains($title, '__FitMax')) {
                            $icons[$value] = $title;
                        }
                        
                    }
                }
            }
        }

        $this->source = $icons;
        return $this;
    }

    /**
     * Generate full relative path from partial relative path.
     * Uses ModuleResourceLoader->resolveURL to handle addition of _resources dir etc
     * For example, the icon path is stored in the db as 'app/client/assets/icons/default/icon.svg'
     * but we need the full relative path to render the icon in the field template: '/_resources/app/client/assets/icons/default/icon.svg'
     * @param string partial relative path
     *
     * @return string full relative path
     */

    public function getFullRelativePath($path)
    {
        return ModuleResourceLoader::singleton()->resolveURL($path);
    }

    /**
     * Build the field
     *
     * @return HTML
     **/
    public function Field($properties = [])
    {
        Requirements::css('plasticstudio/iconfield:css/IconField.css');
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

        $folderLink = Folder::find_or_make($this->getFolderName())->CMSEditLink();

        $this->setDescription('Icon files can be managed in the Files section <a href="' . $folderLink . '">' . $this->getFolderName() . ' folder</a>.<br />SVG icons are recommended.');

        $this->setTemplate('IconField');

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