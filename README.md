# CMB2 Mapbox

![CMB2 Mapbox Banner](https://robclark.io/assets/cmb2mapbox_banner_new.jpg)

This plugin adds a new CMB2 fieldtype for adding a single point to a Mapbox map. This plugin requires CMB2 and a Mapbox access token.

![The field.](https://robclark.io/assets/cmb2mapbox_screenshot.jpg)

## Description

This is the initial release of a plugin that extends CMB2 by adding a new fieldtype called "mapbox_map", which allows content editors to add a single point to a map.

Location data for the chosen location is saved as an array under the meta key of your choice:

```
Array
(
    [lat] => 34.72724208492225
    [lng] => -86.58798437717418
    [lnglat] => -86.58798437717418,34.72724208492225
)
```

## Installation

1. Upload the `cmb2-mapbox` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Provide your Mapbox access token and optionally set the default map center by going to Settings > CMB2 Mapbox
4. Add a mapbox_map field to a CMB2 metabox.
5. Profit.