<?php

namespace PlasticStudio\IconField;

use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\DB;
use SilverStripe\Core\Path;
use SilverStripe\Control\Director;
use SilverStripe\Core\Manifest\ModuleResourceLoader;

class Icon extends DBField
{
    private static $casting = array(
        'URL' => 'HTMLFragment',
        'IMG' => 'HTMLFragment',
        'SVG' => 'HTMLFragment'
    );

    public function requireField()
    {
        DB::require_field($this->tableName, $this->name, 'Varchar(1024)');
    }

    /**
     * Default casting for this field
     *
     * @return string
     */
    public function forTemplate()
    {
        return $this->getTag();
    }


    /**
     * Default casting for this field
     *
     * @return string
     */
    public function getTag()
    {
        $url = $this->URL() ?? '';

        // We are an SVG, so return the SVG data
        if (substr($url, strlen($url) - 4) === '.svg') {
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
    public function URL()
    {
        return $this->getValue();
    }


    /**
     * Construct IMG tag
     *
     * @return string
     **/
    public function IMG()
    {
        $url = ModuleResourceLoader::singleton()->resolveURL($this->URL());

        return '<img class="icon" src="'.$url.'" />';
    }


    /**
     * Construct SVG data
     *
     * @return string
     **/
    public function SVG()
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
            return false;
        }

        $svg = file_get_contents($filePath);
        return '<span class="icon svg">'.$svg.'</span>';
    }

    /**
     * (non-PHPdoc)
     * @see DBField::scaffoldFormField()
     */
    public function scaffoldFormField($title = null, $params = null)
    {
        return IconField::create($this->name, $title);
    }
}
