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

To add a maxbox_map field:

```
$cmb2->add_field(
    array(
        'name'     => __( 'Map Location', 'mtsi' ),
        'desc'     => __( 'Drop a pin for this location.', 'mtsi' ),
        'id'   => '_my_meta_key',
        'type'     => 'mapbox_map',
    )
);
```

Optionally, you can set the default zoom for the map (used when loaded with no previously-set location):

```
$cmb2->add_field(
    array(
        'name'     => __( 'Map Location', 'mtsi' ),
        'desc'     => __( 'Drop a pin for this location.', 'mtsi' ),
        'id'   => '_my_meta_key',
        'type'     => 'mapbox_map',
        'default_zoom' => 3,
    )
);
```

A PHP class, CMB2_MB_Map, has also been provided for building maps with pins from multiple posts. Documentation coming shortly.

## Installation

1. Upload the `cmb2-mapbox` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Provide your Mapbox access token and optionally set the default map center by going to Settings > CMB2 Mapbox
4. Add a mapbox_map field to a CMB2 metabox.
5. Profit.