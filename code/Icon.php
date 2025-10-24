<?php

namespace PlasticStudio\IconField;

use SilverStripe\ORM\DB;
use SilverStripe\Core\Path;
use SilverStripe\Forms\FormField;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Core\Manifest\ModuleResourceLoader;

class Icon extends DBField
{
    private static $casting = array(
        'URL' => 'HTMLFragment',
        'IMG' => 'HTMLFragment',
        'SVG' => 'HTMLFragment'
    );

    public function requireField(): void
    {
        DB::require_field($this->tableName, $this->name, 'Varchar(1024)');
    }

    /**
     * Default casting for this field
     *
     * @return string
     */
    public function forTemplate(): string
    {
        return $this->getTag();
    }


    /**
     * Default casting for this field
     *
     * @return string
     */
    public function getTag(): string
    {
        $url = $this->URL() ?? '';

        // We are an SVG, so return the SVG data
        if (substr($url, -4) === '.svg') {
            return $this->SVG();
        } else {
            return $this->IMG();
        }
    }


    /**
     * Get just the URL for this icon
     *
     * @return string
     **/
    public function URL(): string
    {
        return (string)$this->getValue();
    }


    /**
     * Construct IMG tag
     *
     * @return string
     **/
    public function IMG(): string
    {
        $url = ModuleResourceLoader::singleton()->resolveURL($this->URL());

        if (!$url) {
            return ''; // always return a string
        }

        // check for default width and height in the site's config.yml
        $width = false;
        $default_width = Config::inst()->get(IconField::class, 'default_width');
        if ($default_width) {
            $width = 'width="'.$default_width.'"';
        }

        $height = false;
        $default_height = Config::inst()->get(IconField::class, 'default_height');
        if ($default_height) {
            $height = 'height="'.$default_height.'"';
        }

        return '<img class="icon" src="'.$url.'" '.$width.' '.$height.' alt="" />'; // alt is blank because icons generally don't add value to screen readers
    }


    /**
     * Construct SVG data
     *
     * @return string
     **/
    public function SVG(): string
    {
        $url = $this->URL() ?? '';

        if (substr($url, strlen($url) - 4) !== '.svg') {
            user_error('Deprecation notice: Direct access to $Icon.SVG in templates is deprecated, please use $Icon', E_USER_WARNING);
        }

        $filePath = Path::join(
            Director::publicDir() ? Director::publicFolder() : Director::baseFolder(),
            $url
        );

        if (!file_exists($filePath)) {
            return ''; // always return a string
        }

        $svg = file_get_contents($filePath);
        return '<span class="icon svg">'.$svg.'</span>';
    }

    /**
     * @see DBField::scaffoldFormField()
     */
    public function scaffoldFormField(?string $title = null, array $params = []): ?FormField
    {
        return IconField::create($this->name, $title);
    }

}
