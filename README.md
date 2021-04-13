# Install

`composer require plasticstudio/iconfield`

# Description

Simplifies the use of icons in a way content authors can set icons without interfering with the asset library. Instead, the web developer provides the icon set which the end-user can use but not manipulate.

![IconField](https://raw.githubusercontent.com/jaedb/IconField/master/screenshot.jpg)

# Requirements

- SilverStripe 4

# Usage

- Import the required classes:

```
use PlasticStudio\IconField\Icon;
use PlasticStudio\IconField\IconField;
```

- Set your `$db` field to type `Icon` (eg `'PageIcon' => Icon::class`)
- `IconField::create($name, $title)`
  - `$name` is the database field as defined in your class
  - `$title` is the label for this field
- Add a folder containing icons to your project; icons in this folder will be used by the field as options which you can select. the default location of this folder (as defined in `_config/config.yml`) is `app/client/assets/icons/default`. If your project has a `public` directory, you'll need to make sure the path to this folder is exposed. You can override this global default in your project's own config like so:

```
PlasticStudio\IconField:
  icons_directory: app/client/assets/different/path/to/icons
```

- You can also set an icon folder path on a per-field basis by using `setFolderName()`, eg:

```
IconField::create('SocialIcon, 'Icon')->setFolderName('app/client/assets/icons/social')
```

- Use your icon in templates as you would any other property (eg `$PageIcon`). If your icon is an SVG, the SVG image data will be injected into the template. To prevent this, you can call `$PageIcon.IMG` instead to enforce use of `<img>` tags.
