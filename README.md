# NH3 Mag - Translated static content

> **This "plugin" does not contain any code.**

It only provides a safe place where NH3 Mag users can manage translation of custom content, such as categories names.

## Why?!

The NH3 Mag uses the categories names as defined in the category section of the WordPress site as a translation key, in order to display said names in several languages.

Since categories can be added an deleted by the admin users at any time, their names can not be added to the `.pot` model file of the theme.

Using [Loco Translate][loco], those admin users can add the names of the created categories in the theme model and, thus, translate theme in the different languages. Since workflow has an issue, though: those added categories names are not part of the source code of the theme. This means that, whenever some changes or updates are made to the NH3 theme, the `.pot` model file will be generated anew from the content of the source code, effectively removing all those user added categories names.

Separating translatable string of the source code (in the theme) from translatable user generated string (in this plugin) prevents the issue.

## Content

The plugin is **only** composed of a single folder, `languages`, that contains in itself only one file, `nh3-mag-translated-custom-content.pot`, which is an empty model file.

## Managing custom content translations

Admin users can manage their custom content translations by using the [Loco Translate][loco] WordPress Plugin, through its **Loco Translate > Plugins** menu entry, and then select the **NH3 Mag - Translated Custom Content** link.

[loco]: https://wordpress.org/plugins/loco-translate/
